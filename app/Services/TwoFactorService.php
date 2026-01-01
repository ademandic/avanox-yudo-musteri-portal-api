<?php

/**
 * YUDO Customer Portal API
 *
 * @author    Avanox Bilişim Yazılım ve Danışmanlık LTD ŞTİ
 * @copyright 2025 Avanox Bilişim
 * @license   Proprietary - All rights reserved
 */

namespace App\Services;

use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class TwoFactorService
{
    /**
     * 2FA kod uzunluğu
     */
    protected int $codeLength = 6;

    /**
     * Kod geçerlilik süresi (dakika)
     */
    protected int $codeValidityMinutes;

    /**
     * Maksimum yanlış deneme sayısı
     */
    protected int $maxAttempts;

    /**
     * Hesap kilitleme süresi (dakika)
     */
    protected int $lockoutMinutes;

    public function __construct()
    {
        $this->codeValidityMinutes = config('portal.two_factor.code_validity_minutes', 5);
        $this->maxAttempts = config('portal.two_factor.max_attempts', 3);
        $this->lockoutMinutes = config('portal.two_factor.lockout_minutes', 15);
    }

    /**
     * Yeni 2FA kodu oluştur ve kullanıcıya gönder
     */
    public function generateAndSend(User $user): bool
    {
        // Hesap kilitli mi kontrol et
        if ($this->isLocked($user)) {
            return false;
        }

        // 6 haneli kod oluştur
        $code = $this->generateCode();

        // Kodu veritabanına kaydet
        $user->update([
            'two_factor_code' => $code,
            'two_factor_expires_at' => Carbon::now()->addMinutes($this->codeValidityMinutes),
            'two_factor_attempts' => 0,
        ]);

        // Email gönder
        $this->sendCodeByEmail($user, $code);

        return true;
    }

    /**
     * 2FA kodunu doğrula
     */
    public function verify(User $user, string $code): array
    {
        // Hesap kilitli mi kontrol et
        if ($this->isLocked($user)) {
            $remainingMinutes = $this->getRemainingLockoutMinutes($user);
            return [
                'success' => false,
                'error' => 'locked',
                'message' => "Hesabınız kilitli. {$remainingMinutes} dakika sonra tekrar deneyin.",
                'remaining_minutes' => $remainingMinutes,
            ];
        }

        // Kod süresi dolmuş mu?
        if ($this->isCodeExpired($user)) {
            return [
                'success' => false,
                'error' => 'expired',
                'message' => 'Doğrulama kodunun süresi dolmuş. Yeni kod talep edin.',
            ];
        }

        // Kod eşleşiyor mu?
        if ($user->two_factor_code !== $code) {
            return $this->handleFailedAttempt($user);
        }

        // Başarılı - 2FA alanlarını temizle
        $user->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'two_factor_attempts' => 0,
        ]);

        return [
            'success' => true,
            'message' => 'Doğrulama başarılı.',
        ];
    }

    /**
     * Yeni kod gönder (resend)
     */
    public function resend(User $user): array
    {
        // Hesap kilitli mi kontrol et
        if ($this->isLocked($user)) {
            $remainingMinutes = $this->getRemainingLockoutMinutes($user);
            return [
                'success' => false,
                'error' => 'locked',
                'message' => "Hesabınız kilitli. {$remainingMinutes} dakika sonra tekrar deneyin.",
                'remaining_minutes' => $remainingMinutes,
            ];
        }

        // Son kod ne zaman gönderilmiş? (spam koruması - 1 dakika)
        if ($user->two_factor_expires_at) {
            $codeCreatedAt = Carbon::parse($user->two_factor_expires_at)
                ->subMinutes($this->codeValidityMinutes);

            if ($codeCreatedAt->diffInSeconds(Carbon::now()) < 60) {
                return [
                    'success' => false,
                    'error' => 'too_soon',
                    'message' => 'Yeni kod göndermek için lütfen 1 dakika bekleyin.',
                ];
            }
        }

        // Yeni kod oluştur ve gönder
        $sent = $this->generateAndSend($user);

        if (!$sent) {
            return [
                'success' => false,
                'error' => 'send_failed',
                'message' => 'Kod gönderilemedi. Lütfen daha sonra tekrar deneyin.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Yeni doğrulama kodu e-posta adresinize gönderildi.',
        ];
    }

    /**
     * Hesap kilitli mi?
     */
    public function isLocked(User $user): bool
    {
        if (!$user->locked_until) {
            return false;
        }

        return Carbon::parse($user->locked_until)->isFuture();
    }

    /**
     * Kalan kilitleme süresi (dakika)
     */
    public function getRemainingLockoutMinutes(User $user): int
    {
        if (!$user->locked_until) {
            return 0;
        }

        $remaining = Carbon::now()->diffInMinutes(Carbon::parse($user->locked_until), false);
        return max(0, $remaining);
    }

    /**
     * 6 haneli rastgele kod oluştur
     */
    protected function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), $this->codeLength, '0', STR_PAD_LEFT);
    }

    /**
     * Kod süresi dolmuş mu?
     */
    protected function isCodeExpired(User $user): bool
    {
        if (!$user->two_factor_expires_at) {
            return true;
        }

        return Carbon::parse($user->two_factor_expires_at)->isPast();
    }

    /**
     * Başarısız deneme işlemi
     */
    protected function handleFailedAttempt(User $user): array
    {
        $attempts = $user->two_factor_attempts + 1;

        if ($attempts >= $this->maxAttempts) {
            // Hesabı kilitle
            $user->update([
                'two_factor_code' => null,
                'two_factor_expires_at' => null,
                'two_factor_attempts' => $attempts,
                'locked_until' => Carbon::now()->addMinutes($this->lockoutMinutes),
            ]);

            return [
                'success' => false,
                'error' => 'max_attempts',
                'message' => "Çok fazla yanlış deneme. Hesabınız {$this->lockoutMinutes} dakika kilitlendi.",
                'attempts' => $attempts,
                'remaining' => 0,
            ];
        }

        // Deneme sayısını artır
        $user->update([
            'two_factor_attempts' => $attempts,
        ]);

        $remaining = $this->maxAttempts - $attempts;

        return [
            'success' => false,
            'error' => 'invalid_code',
            'message' => "Geçersiz kod. {$remaining} deneme hakkınız kaldı.",
            'attempts' => $attempts,
            'remaining' => $remaining,
        ];
    }

    /**
     * E-posta ile kod gönder
     */
    protected function sendCodeByEmail(User $user, string $code): void
    {
        Mail::to($user->email)->send(new TwoFactorCodeMail($user, $code));
    }

    /**
     * Kullanıcının 2FA doğrulaması bekliyor mu?
     */
    public function isPending(User $user): bool
    {
        return $user->two_factor_code !== null
            && !$this->isCodeExpired($user)
            && !$this->isLocked($user);
    }
}
