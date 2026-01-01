<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifyTwoFactorRequest;
use App\Http\Requests\Auth\ResendTwoFactorRequest;
use App\Http\Resources\UserResource;
use App\Services\PortalAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected PortalAuthService $authService;

    public function __construct(PortalAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Login - İlk adım (2FA kodu gönderir)
     * POST /api/auth/login
     *
     * @response 200 {
     *   "success": true,
     *   "requires_2fa": true,
     *   "message": "Doğrulama kodu e-posta adresinize gönderildi.",
     *   "user_id": 123,
     *   "email_masked": "ab***@domain.com"
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->email,
            $request->password,
            $request->ip()
        );

        $statusCode = match ($result['error'] ?? null) {
            'invalid_credentials' => 401,
            'inactive' => 403,
            'locked' => 423,
            default => $result['success'] ? 200 : 400,
        };

        return response()->json($result, $statusCode);
    }

    /**
     * 2FA doğrulama - İkinci adım (Token döner)
     * POST /api/auth/verify-2fa
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "access_token": "...",
     *     "token_type": "bearer",
     *     "expires_in": 3600,
     *     "session_id": "uuid",
     *     "user": { ... }
     *   }
     * }
     */
    public function verifyTwoFactor(VerifyTwoFactorRequest $request): JsonResponse
    {
        $result = $this->authService->verifyTwoFactor(
            $request->user_id,
            $request->code,
            $request->ip()
        );

        $statusCode = match ($result['error'] ?? null) {
            'user_not_found' => 404,
            'expired' => 410,
            'invalid_code' => 422,
            'max_attempts', 'locked' => 423,
            default => $result['success'] ? 200 : 400,
        };

        return response()->json($result, $statusCode);
    }

    /**
     * 2FA kodu yeniden gönder
     * POST /api/auth/resend-2fa
     */
    public function resendTwoFactor(ResendTwoFactorRequest $request): JsonResponse
    {
        $result = $this->authService->resendTwoFactor($request->user_id);

        $statusCode = match ($result['error'] ?? null) {
            'user_not_found' => 404,
            'locked' => 423,
            'too_soon' => 429,
            default => $result['success'] ? 200 : 400,
        };

        return response()->json($result, $statusCode);
    }

    /**
     * Logout
     * POST /api/auth/logout
     */
    public function logout(): JsonResponse
    {
        $result = $this->authService->logout();
        return response()->json($result);
    }

    /**
     * Token yenileme
     * POST /api/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-ID', '');

        $result = $this->authService->refreshToken($sessionId);

        $statusCode = match ($result['error'] ?? null) {
            'unauthenticated' => 401,
            'session_invalidated' => 401,
            'refresh_failed' => 401,
            default => $result['success'] ? 200 : 400,
        };

        return response()->json($result, $statusCode);
    }

    /**
     * Mevcut kullanıcı bilgisi
     * GET /api/auth/me
     */
    public function me(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Oturum bulunamadı.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->load(['company'])),
        ]);
    }
}
