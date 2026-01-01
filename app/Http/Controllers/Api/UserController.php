<?php

/**
 * YUDO Customer Portal API
 *
 * @author    Avanox Bilişim Yazılım ve Danışmanlık LTD ŞTİ
 * @copyright 2025 Avanox Bilişim
 * @license   Proprietary - All rights reserved
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\PortalInvitation;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected InvitationService $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * Firma kullanıcılarını listele
     * GET /api/users
     */
    public function index(Request $request): JsonResponse
    {
        $currentUser = Auth::guard('api')->user();

        // Admin kontrolü
        if (!$currentUser->is_company_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Bu işlem için yetkiniz bulunmuyor.',
            ], 403);
        }

        // Aynı firmadaki kullanıcıları listele
        $users = User::where('company_id', $currentUser->company_id)
            ->where('is_portal_user', true)
            ->orderBy('created_at', 'desc')
            ->get();

        // Bekleyen davetleri de ekle
        $pendingInvitations = PortalInvitation::where('company_id', $currentUser->company_id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'users' => UserResource::collection($users),
                'pending_invitations' => $pendingInvitations->map(function ($invitation) {
                    return [
                        'id' => $invitation->id,
                        'email' => $invitation->email,
                        'created_at' => $invitation->created_at->toIso8601String(),
                        'expires_at' => $invitation->expires_at->toIso8601String(),
                    ];
                }),
                'total_users' => $users->count(),
                'total_pending' => $pendingInvitations->count(),
            ],
        ]);
    }

    /**
     * Kullanıcı durumunu değiştir (aktif/pasif)
     * POST /api/users/{id}/toggle-status
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $currentUser = Auth::guard('api')->user();

        // Admin kontrolü
        if (!$currentUser->is_company_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Bu işlem için yetkiniz bulunmuyor.',
            ], 403);
        }

        // Kullanıcıyı bul
        $user = User::where('id', $id)
            ->where('company_id', $currentUser->company_id)
            ->where('is_portal_user', true)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı bulunamadı.',
            ], 404);
        }

        // Kendini pasif yapamaz
        if ($user->id === $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Kendi hesabınızı pasif yapamazsınız.',
            ], 422);
        }

        // Durumu değiştir
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'aktif' : 'pasif';

        return response()->json([
            'success' => true,
            'message' => "Kullanıcı durumu {$status} olarak güncellendi.",
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Kullanıcı sil
     * DELETE /api/users/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $currentUser = Auth::guard('api')->user();

        // Admin kontrolü
        if (!$currentUser->is_company_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Bu işlem için yetkiniz bulunmuyor.',
            ], 403);
        }

        // Kullanıcıyı bul
        $user = User::where('id', $id)
            ->where('company_id', $currentUser->company_id)
            ->where('is_portal_user', true)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı bulunamadı.',
            ], 404);
        }

        // Kendini silemez
        if ($user->id === $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Kendi hesabınızı silemezsiniz.',
            ], 422);
        }

        // Kullanıcıyı sil (soft delete yapılmıyor, portal user flag kaldırılıyor)
        $user->update([
            'is_portal_user' => false,
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kullanıcı başarıyla silindi.',
        ]);
    }

    /**
     * Davetiye gönder
     * POST /api/users/invite
     */
    public function invite(Request $request): JsonResponse
    {
        $currentUser = Auth::guard('api')->user();

        // Admin kontrolü
        if (!$currentUser->is_company_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Bu işlem için yetkiniz bulunmuyor.',
            ], 403);
        }

        // Validasyon
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
            ],
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'role' => 'nullable|string|in:Portal User,Portal Admin',
        ], [
            'email.required' => 'E-posta adresi gereklidir.',
            'email.email' => 'Geçerli bir e-posta adresi girin.',
            'first_name.required' => 'Ad gereklidir.',
            'last_name.required' => 'Soyad gereklidir.',
        ]);

        // Email domain kontrolü (aynı domain olmalı)
        $currentDomain = explode('@', $currentUser->email)[1] ?? '';
        $inviteDomain = explode('@', $validated['email'])[1] ?? '';

        if ($currentDomain !== $inviteDomain) {
            return response()->json([
                'success' => false,
                'message' => "Sadece @{$currentDomain} uzantılı e-posta adreslerine davetiye gönderebilirsiniz.",
            ], 422);
        }

        // Davetiye oluştur (InvitationService kullan)
        try {
            $invitation = $this->invitationService->createByPortalAdmin(
                email: $validated['email'],
                firstName: $validated['first_name'],
                lastName: $validated['last_name'],
                invitedBy: $currentUser,
                roleName: $validated['role'] ?? 'Portal User',
                ip: $request->ip()
            );

            return response()->json([
                'success' => true,
                'message' => 'Davetiye başarıyla gönderildi.',
                'data' => [
                    'invitation_id' => $invitation->id,
                    'email' => $invitation->email,
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Bekleyen davetiyeyi iptal et
     * DELETE /api/users/invitations/{id}
     */
    public function cancelInvitation(Request $request, int $id): JsonResponse
    {
        $currentUser = Auth::guard('api')->user();

        // Admin kontrolü
        if (!$currentUser->is_company_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Bu işlem için yetkiniz bulunmuyor.',
            ], 403);
        }

        // Davetiyeyi bul
        $invitation = PortalInvitation::where('id', $id)
            ->where('company_id', $currentUser->company_id)
            ->whereNull('accepted_at')
            ->first();

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Davetiye bulunamadı veya zaten kabul edilmiş.',
            ], 404);
        }

        // Davetiyeyi sil
        $invitation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Davetiye başarıyla iptal edildi.',
        ]);
    }
}
