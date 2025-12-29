<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\PortalUserResource;
use App\Models\PortalUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Login
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        // Kullanıcının aktif olup olmadığını kontrol et
        $user = PortalUser::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz kimlik bilgileri.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Hesabınız pasif durumdadır. Lütfen destek ile iletişime geçin.',
            ], 403);
        }

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz kimlik bilgileri.',
            ], 401);
        }

        // Son giriş bilgisini güncelle
        $user->updateLastLogin($request->ip());

        return $this->respondWithToken($token);
    }

    /**
     * Logout
     * POST /api/auth/logout
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Başarıyla çıkış yapıldı.',
        ]);
    }

    /**
     * Token yenileme
     * POST /api/auth/refresh
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = Auth::guard('api')->refresh();
            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token yenilenemedi. Lütfen tekrar giriş yapın.',
            ], 401);
        }
    }

    /**
     * Mevcut kullanıcı bilgisi
     * GET /api/auth/me
     */
    public function me(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        return response()->json([
            'success' => true,
            'data' => new PortalUserResource($user->load(['contact', 'company'])),
        ]);
    }

    /**
     * Token response oluştur
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        $user = Auth::guard('api')->user();

        return response()->json([
            'success' => true,
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
                'user' => new PortalUserResource($user->load(['contact', 'company'])),
            ],
        ]);
    }
}
