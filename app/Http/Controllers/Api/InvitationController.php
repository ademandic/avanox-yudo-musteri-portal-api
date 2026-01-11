<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invitation\AcceptInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Http\Resources\UserResource;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;

class InvitationController extends Controller
{
    public function __construct(
        protected InvitationService $invitationService
    ) {}

    /**
     * Davetiye detayı
     * GET /api/invitations/{token}
     */
    public function show(string $token): JsonResponse
    {
        $invitation = $this->invitationService->getByToken($token);

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Davetiye bulunamadı.',
            ], 404);
        }

        // Süresi dolmuşsa güncelle
        if ($invitation->isExpired() && $invitation->status === \App\Models\PortalInvitation::STATUS_PENDING) {
            $invitation->update(['status' => \App\Models\PortalInvitation::STATUS_EXPIRED]);
            $invitation->refresh();
        }

        return response()->json([
            'success' => true,
            'data' => new InvitationResource($invitation),
        ]);
    }

    /**
     * Davetiyeyi kabul et ve kayıt ol
     * POST /api/invitations/{token}/accept
     */
    public function accept(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        $invitation = $this->invitationService->getByToken($token);

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Davetiye bulunamadı.',
            ], 404);
        }

        try {
            $user = $this->invitationService->accept(
                $invitation,
                $request->password,
                $request->ip()
            );

            // Otomatik login
            $jwtToken = auth('api')->login($user);

            // Session ID oluştur (tek oturum kontrolü için)
            $sessionId = \Illuminate\Support\Str::uuid()->toString();
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
                'last_activity_at' => now(),
                'current_session_id' => $sessionId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hesabınız başarıyla oluşturuldu.',
                'data' => [
                    'access_token' => $jwtToken,
                    'token_type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                    'session_id' => $sessionId,
                    'user' => new UserResource($user->load('company')),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
