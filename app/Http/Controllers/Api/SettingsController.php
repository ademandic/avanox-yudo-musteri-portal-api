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
use App\Services\PortalAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    protected PortalAuthService $authService;

    public function __construct(PortalAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Kullanıcı ayarlarını getir
     * GET /api/settings
     */
    public function index(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => new UserResource($user->load(['company'])),
                'preferences' => [
                    'theme' => $user->portal_theme ?? 'light',
                    'language' => $user->portal_language ?? 'tr',
                ],
            ],
        ]);
    }

    /**
     * Tema tercihini güncelle
     * PUT /api/settings/theme
     */
    public function updateTheme(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => 'required|string|in:light,dark,system',
        ], [
            'theme.required' => 'Tema seçimi gereklidir.',
            'theme.in' => 'Geçerli bir tema seçin (light, dark, system).',
        ]);

        $user = Auth::guard('api')->user();

        $user->update([
            'portal_theme' => $validated['theme'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tema tercihi güncellendi.',
            'data' => [
                'theme' => $user->portal_theme,
            ],
        ]);
    }

    /**
     * Dil tercihini güncelle
     * PUT /api/settings/language
     */
    public function updateLanguage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language' => 'required|string|in:tr,en',
        ], [
            'language.required' => 'Dil seçimi gereklidir.',
            'language.in' => 'Geçerli bir dil seçin (tr, en).',
        ]);

        $user = Auth::guard('api')->user();

        $user->update([
            'portal_language' => $validated['language'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dil tercihi güncellendi.',
            'data' => [
                'language' => $user->portal_language,
            ],
        ]);
    }

    /**
     * Profil bilgilerini güncelle
     * PUT /api/settings/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'surname' => 'sometimes|string|max:100',
        ], [
            'first_name.max' => 'Ad en fazla 100 karakter olabilir.',
            'surname.max' => 'Soyad en fazla 100 karakter olabilir.',
        ]);

        $user = Auth::guard('api')->user();

        // Sadece izin verilen alanları güncelle
        $updateData = array_filter($validated, fn($value) => $value !== null);

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil bilgileri güncellendi.',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Şifre değiştir
     * PUT /api/settings/password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ], [
            'current_password.required' => 'Mevcut şifre gereklidir.',
            'new_password.required' => 'Yeni şifre gereklidir.',
            'new_password.confirmed' => 'Şifre tekrarı eşleşmiyor.',
            'new_password.min' => 'Şifre en az 8 karakter olmalıdır.',
        ]);

        $user = Auth::guard('api')->user();

        $result = $this->authService->changePassword(
            $user,
            $validated['current_password'],
            $validated['new_password']
        );

        $statusCode = match ($result['error'] ?? null) {
            'invalid_current_password' => 422,
            'same_password' => 422,
            default => $result['success'] ? 200 : 400,
        };

        return response()->json($result, $statusCode);
    }
}
