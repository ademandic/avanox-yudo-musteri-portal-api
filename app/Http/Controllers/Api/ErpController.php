<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ErpController extends Controller
{
    protected InvitationService $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * ERP'den portal davetiyesi gönder
     */
    public function sendInvitation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer',
            'contact_id' => 'nullable|integer',
            'email' => 'required|email',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'role' => 'nullable|string|in:Portal User,Portal Admin',
        ], [
            'company_id.required' => 'Firma ID gereklidir.',
            'email.required' => 'E-posta adresi gereklidir.',
            'email.email' => 'Geçerli bir e-posta adresi girin.',
            'first_name.required' => 'Ad gereklidir.',
            'last_name.required' => 'Soyad gereklidir.',
        ]);

        try {
            $invitation = $this->invitationService->createFromErp(
                companyId: $validated['company_id'],
                email: $validated['email'],
                firstName: $validated['first_name'],
                lastName: $validated['last_name'],
                contactId: $validated['contact_id'] ?? null,
                roleName: $validated['role'] ?? 'Portal User'
            );

            Log::info('ERP Portal Invitation sent', [
                'company_id' => $validated['company_id'],
                'email' => $validated['email'],
                'invitation_id' => $invitation->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Portal davetiyesi başarıyla gönderildi.',
                'data' => [
                    'invitation_id' => $invitation->id,
                    'email' => $invitation->email,
                    'expires_at' => $invitation->expires_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ERP Portal Invitation failed', [
                'company_id' => $validated['company_id'],
                'email' => $validated['email'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Firma için portal kullanıcılarını listele
     */
    public function getPortalUsers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer',
        ]);

        $users = \App\Models\User::where('company_id', $validated['company_id'])
            ->where('is_portal_user', true)
            ->get(['id', 'first_name', 'surname', 'email', 'is_company_admin', 'is_active', 'last_login_at']);

        $pendingInvitations = \App\Models\PortalInvitation::where('company_id', $validated['company_id'])
            ->pending()
            ->get(['id', 'email', 'first_name', 'last_name', 'expires_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
                'pending_invitations' => $pendingInvitations,
            ],
        ]);
    }
}
