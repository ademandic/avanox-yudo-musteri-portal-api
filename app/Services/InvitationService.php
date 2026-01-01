<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\PortalInvitation;
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
     * Yeni davetiye oluştur (ERP User tarafından)
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
        $existingUser = User::where('email', $contact->email)
            ->where('is_portal_user', true)
            ->first();
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
     * Portal Admin tarafından davetiye oluştur
     */
    public function createByPortalAdmin(
        string $email,
        string $firstName,
        string $lastName,
        User $invitedBy,
        string $roleName = 'Portal User',
        ?string $ip = null
    ): PortalInvitation {
        $config = config('portal.invitation');

        // Aynı email için bekleyen davet var mı?
        $existingInvitation = PortalInvitation::where('email', $email)
            ->pending()
            ->first();

        if ($existingInvitation && $existingInvitation->isValid()) {
            throw new \Exception('Bu email için zaten bekleyen bir davetiye mevcut.');
        }

        // Zaten kayıtlı bir portal kullanıcısı var mı?
        $existingUser = User::where('email', $email)
            ->where('is_portal_user', true)
            ->first();
        if ($existingUser) {
            throw new \Exception('Bu email adresi ile kayıtlı bir kullanıcı zaten mevcut.');
        }

        // Max kullanıcı limiti kontrolü
        $maxUsers = config('portal.users.max_per_company', 10);
        $currentUsers = User::where('company_id', $invitedBy->company_id)
            ->where('is_portal_user', true)
            ->count();
        $pendingInvitations = PortalInvitation::where('company_id', $invitedBy->company_id)
            ->pending()
            ->count();

        if (($currentUsers + $pendingInvitations) >= $maxUsers) {
            throw new \Exception("Maksimum kullanıcı limitine ({$maxUsers}) ulaşıldı.");
        }

        return PortalInvitation::create([
            'contact_id' => null,
            'company_id' => $invitedBy->company_id,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'token' => $this->generateToken($config['token_length']),
            'invited_by_user_id' => null,
            'invited_by_portal_user_id' => $invitedBy->id,
            'role_name' => $roleName,
            'invited_from_ip' => $ip,
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
    public function accept(PortalInvitation $invitation, string $password, ?string $ip = null): User
    {
        if (!$invitation->isValid()) {
            if ($invitation->isExpired()) {
                // Süresi dolmuş olarak işaretle
                $invitation->update(['status' => PortalInvitation::STATUS_EXPIRED]);
                throw new \Exception('Davetiye süresi dolmuş.');
            }

            throw new \Exception('Davetiye geçerli değil.');
        }

        return DB::transaction(function () use ($invitation, $password, $ip) {
            // Portal kullanıcısı oluştur (users tablosunda)
            $user = User::create([
                'first_name' => $invitation->contact?->first_name ?? $invitation->first_name,
                'surname' => $invitation->contact?->surname ?? $invitation->last_name,
                'email' => $invitation->email,
                'password' => Hash::make($password),
                'is_portal_user' => true,
                'company_id' => $invitation->company_id,
                'is_company_admin' => $invitation->role_name === 'Portal Admin',
                'is_active' => true,
            ]);

            // Rol ata
            $roleName = $invitation->role_name ?? 'Portal User';
            $user->assignRole($roleName);

            // Davetiyeyi kabul edildi olarak işaretle
            $invitation->update([
                'status' => PortalInvitation::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'portal_user_id' => $user->id,
                'accepted_from_ip' => $ip,
            ]);

            return $user;
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
