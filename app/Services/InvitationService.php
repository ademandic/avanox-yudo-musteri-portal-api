<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\PortalInvitation;
use App\Models\PortalUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Invitation Service
 * Davetiye oluşturma ve kabul işlemleri.
 */
class InvitationService
{
    /**
     * Yeni davetiye oluştur
     */
    public function create(Contact $contact, User $invitedBy): PortalInvitation
    {
        $config = config('portal.invitation');

        // Aynı email için bekleyen davet var mı kontrol et
        $existingInvitation = PortalInvitation::where('email', $contact->email)
            ->pending()
            ->first();

        if ($existingInvitation && $existingInvitation->isValid()) {
            throw new \Exception('Bu email için zaten bekleyen bir davetiye mevcut.');
        }

        // Zaten kayıtlı bir portal kullanıcısı var mı?
        $existingUser = PortalUser::where('email', $contact->email)->first();
        if ($existingUser) {
            throw new \Exception('Bu email adresi ile kayıtlı bir kullanıcı zaten mevcut.');
        }

        return PortalInvitation::create([
            'contact_id' => $contact->id,
            'company_id' => $contact->company_id,
            'email' => $contact->email,
            'token' => $this->generateToken($config['token_length']),
            'invited_by_user_id' => $invitedBy->id,
            'sent_at' => now(),
            'expires_at' => now()->addDays($config['expires_in_days']),
            'status' => PortalInvitation::STATUS_PENDING,
            'is_active' => true,
        ]);
    }

    /**
     * Token ile davetiyeyi getir
     */
    public function getByToken(string $token): ?PortalInvitation
    {
        return PortalInvitation::where('token', $token)
            ->with(['contact', 'company', 'invitedBy'])
            ->first();
    }

    /**
     * Davetiyeyi kabul et ve kullanıcı oluştur
     */
    public function accept(PortalInvitation $invitation, string $password): PortalUser
    {
        if (!$invitation->isValid()) {
            if ($invitation->isExpired()) {
                // Süresi dolmuş olarak işaretle
                $invitation->update(['status' => PortalInvitation::STATUS_EXPIRED]);
                throw new \Exception('Davetiye süresi dolmuş.');
            }

            throw new \Exception('Davetiye geçerli değil.');
        }

        return DB::transaction(function () use ($invitation, $password) {
            // Portal kullanıcısı oluştur
            $portalUser = PortalUser::create([
                'contact_id' => $invitation->contact_id,
                'company_id' => $invitation->company_id,
                'email' => $invitation->email,
                'password' => Hash::make($password),
                'is_active' => true,
            ]);

            // Davetiyeyi kabul edildi olarak işaretle
            $invitation->update([
                'status' => PortalInvitation::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'portal_user_id' => $portalUser->id,
            ]);

            return $portalUser;
        });
    }

    /**
     * Davetiyeyi iptal et
     */
    public function cancel(PortalInvitation $invitation): bool
    {
        if ($invitation->isAccepted()) {
            throw new \Exception('Kabul edilmiş davetiye iptal edilemez.');
        }

        return $invitation->update([
            'status' => PortalInvitation::STATUS_CANCELLED,
            'is_active' => false,
        ]);
    }

    /**
     * Süresi dolan davetiyeleri güncelle
     */
    public function expireOldInvitations(): int
    {
        return PortalInvitation::where('status', PortalInvitation::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->update(['status' => PortalInvitation::STATUS_EXPIRED]);
    }

    /**
     * Güvenli token oluştur
     */
    protected function generateToken(int $length): string
    {
        return Str::random($length);
    }

    /**
     * Davetiyeyi yeniden gönder (yeni token ile)
     */
    public function resend(PortalInvitation $invitation, User $invitedBy): PortalInvitation
    {
        $config = config('portal.invitation');

        // Eski davetiyeyi iptal et
        $this->cancel($invitation);

        // Yeni davetiye oluştur
        return PortalInvitation::create([
            'contact_id' => $invitation->contact_id,
            'company_id' => $invitation->company_id,
            'email' => $invitation->email,
            'token' => $this->generateToken($config['token_length']),
            'invited_by_user_id' => $invitedBy->id,
            'sent_at' => now(),
            'expires_at' => now()->addDays($config['expires_in_days']),
            'status' => PortalInvitation::STATUS_PENDING,
            'is_active' => true,
        ]);
    }
}
