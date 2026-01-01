<?php

/**
 * YUDO Customer Portal API
 *
 * @author    Avanox Bilişim Yazılım ve Danışmanlık LTD ŞTİ
 * @copyright 2025 Avanox Bilişim
 * @license   Proprietary - All rights reserved
 */

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PortalAuthService
{
    protected TwoFactorService $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Login işlemi - 2FA ile
     */
    public function login(string $email, string $password, string $ip): array
    {
        // Kullanıcıyı bul (portal kullanıcısı olmalı)
        $user = User::where('email', $email)
            ->where('is_portal_user', true)
            ->first();

        if (!$user) {
            return [
                'success' => false,
                'error' => 'invalid_credentials',
                'message' => 'Geçersiz kimlik bilgileri.',
            ];
        }

        // Aktif mi?
        if (!$user->is_active) {
            return [
                'success' => false,
                'error' => 'inactive',
                'message' => 'Hesabınız pasif durumdadır. Lütfen yöneticinizle iletişime geçin.',
            ];
        }

        // Hesap kilitli mi?
        if ($this->twoFactorService->isLocked($user)) {
            $remainingMinutes = $this->twoFactorService->getRemainingLockoutMinutes($user);
            return [
                'success' => false,
                'error' => 'locked',
                'message' => "Hesabınız kilitli. {$remainingMinutes} dakika sonra tekrar deneyin.",
                'remaining_minutes' => $remainingMinutes,
            ];
        }

        // Şifre doğru mu?
        if (!Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'error' => 'invalid_credentials',
                'message' => 'Geçersiz kimlik bilgileri.',
            ];
        }

        // 2FA atlanacak mı? (test/geliştirme için)
        if ($user->skip_two_factor) {
            return $this->createTokenResponse($user, $ip);
        }

        // 2FA kodu oluştur ve gönder
        $sent = $this->twoFactorService->generateAndSend($user);

        if (!$sent) {
            return [
                'success' => false,
                'error' => 'two_factor_failed',
                'message' => 'Doğrulama kodu gönderilemedi. Lütfen daha sonra tekrar deneyin.',
            ];
        }

        // 2FA bekleniyor
        return [
            'success' => true,
            'requires_2fa' => true,
            'message' => 'Doğrulama kodu e-posta adresinize gönderildi.',
            'user_id' => $user->id,
            'email_masked' => $this->maskEmail($user->email),
        ];
    }

    /**
     * Token oluştur ve döndür (2FA sonrası veya skip durumunda)
     */
    protected function createTokenResponse(User $user, string $ip): array
    {
        $token = Auth::guard('api')->login($user);

        if (!$token) {
            return [
                'success' => false,
                'error' => 'token_failed',
                'message' => 'Oturum oluşturulamadı.',
            ];
        }

        // Session ID oluştur
        $sessionId = Str::uuid()->toString();

        // Kullanıcı bilgilerini güncelle
        $user->update([
            'last_login_at' => Carbon::now(),
            'last_login_ip' => $ip,
            'last_activity_at' => Carbon::now(),
            'current_session_id' => $sessionId,
        ]);

        return [
            'success' => true,
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
                'session_id' => $sessionId,
                'user' => $this->getUserData($user),
            ],
        ];
    }

    /**
     * 2FA doğrulaması ve token oluşturma
     */
    public function verifyTwoFactor(int $userId, string $code, string $ip): array
    {
        $user = User::where('id', $userId)
            ->where('is_portal_user', true)
            ->first();

        if (!$user) {
            return [
                'success' => false,
                'error' => 'user_not_found',
                'message' => 'Kullanıcı bulunamadı.',
            ];
        }

        // 2FA kodunu doğrula
        $result = $this->twoFactorService->verify($user, $code);

        if (!$result['success']) {
            return $result;
        }

        return $this->createTokenResponse($user, $ip);
    }

    /**
     * 2FA kodu yeniden gönder
     */
    public function resendTwoFactor(int $userId): array
    {
        $user = User::where('id', $userId)
            ->where('is_portal_user', true)
            ->first();

        if (!$user) {
            return [
                'success' => false,
                'error' => 'user_not_found',
                'message' => 'Kullanıcı bulunamadı.',
            ];
        }

        return $this->twoFactorService->resend($user);
    }

    /**
     * Token yenileme
     */
    public function refreshToken(string $sessionId): array
    {
        try {
            $user = Auth::guard('api')->user();

            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'unauthenticated',
                    'message' => 'Oturum geçersiz.',
                ];
            }

            // Session ID kontrolü (tek oturum)
            if ($user->current_session_id !== $sessionId) {
                Auth::guard('api')->logout();
                return [
                    'success' => false,
                    'error' => 'session_invalidated',
                    'message' => 'Başka bir cihazdan giriş yapıldı. Oturumunuz sonlandırıldı.',
                ];
            }

            $token = Auth::guard('api')->refresh();

            // Last activity güncelle
            $user->update([
                'last_activity_at' => Carbon::now(),
            ]);

            return [
                'success' => true,
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
                    'session_id' => $sessionId,
                    'user' => $this->getUserData($user),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'refresh_failed',
                'message' => 'Token yenilenemedi. Lütfen tekrar giriş yapın.',
            ];
        }
    }

    /**
     * Çıkış
     */
    public function logout(): array
    {
        $user = Auth::guard('api')->user();

        if ($user) {
            $user->update([
                'current_session_id' => null,
            ]);
        }

        Auth::guard('api')->logout();

        return [
            'success' => true,
            'message' => 'Başarıyla çıkış yapıldı.',
        ];
    }

    /**
     * Aktivite güncelle (session timeout için)
     */
    public function updateActivity(User $user): void
    {
        $user->update([
            'last_activity_at' => Carbon::now(),
        ]);
    }

    /**
     * Session timeout kontrolü
     */
    public function isSessionTimedOut(User $user): bool
    {
        if (!$user->last_activity_at) {
            return false;
        }

        $timeoutMinutes = config('portal.session.timeout_minutes', 5);
        $lastActivity = Carbon::parse($user->last_activity_at);

        return $lastActivity->diffInMinutes(Carbon::now()) >= $timeoutMinutes;
    }

    /**
     * Session geçerli mi? (tek oturum kontrolü)
     */
    public function isSessionValid(User $user, string $sessionId): bool
    {
        return $user->current_session_id === $sessionId;
    }

    /**
     * Email'i maskele (ab***@domain.com)
     */
    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        if (strlen($name) <= 2) {
            $masked = $name . '***';
        } else {
            $masked = substr($name, 0, 2) . '***';
        }

        return $masked . '@' . $domain;
    }

    /**
     * Kullanıcı verilerini hazırla
     */
    protected function getUserData(User $user): array
    {
        // Company ilişkisini yükle
        $user->loadMissing('company');

        return [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'surname' => $user->surname,
            'name' => $user->full_name ?? $user->name,
            'company_id' => $user->company_id,
            'contact_id' => $user->contact_id,
            'is_company_admin' => $user->is_company_admin ?? false,
            'portal_theme' => $user->portal_theme ?? 'light',
            'portal_language' => $user->portal_language ?? 'tr',
            'company' => $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'tax_number' => $user->company->tax_number ?? null,
                'phone' => $user->company->phone ?? null,
                'email' => $user->company->email ?? null,
                'address' => $user->company->address ?? null,
            ] : null,
        ];
    }

    /**
     * Şifre değiştir
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): array
    {
        // Mevcut şifre doğru mu?
        if (!Hash::check($currentPassword, $user->password)) {
            return [
                'success' => false,
                'error' => 'invalid_current_password',
                'message' => 'Mevcut şifre yanlış.',
            ];
        }

        // Yeni şifre mevcut ile aynı mı?
        if (Hash::check($newPassword, $user->password)) {
            return [
                'success' => false,
                'error' => 'same_password',
                'message' => 'Yeni şifre mevcut şifreyle aynı olamaz.',
            ];
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return [
            'success' => true,
            'message' => 'Şifreniz başarıyla değiştirildi.',
        ];
    }
}
