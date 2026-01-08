<?php

namespace App\Services;

use App\Mail\UserCreatedMail;
use App\Models\Contact;
use App\Models\PortalInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
            $firstName = $invitation->contact?->first_name ?? $invitation->first_name ?? '';
            $lastName = $invitation->contact?->surname ?? $invitation->last_name ?? '';
            $fullName = trim($firstName . ' ' . $lastName);

            // Turkce karakterleri ASCII'ye cevir
            $name = strtr($fullName, [
                'ş' => 's', 'Ş' => 'S', 'ı' => 'i', 'İ' => 'I', 'ğ' => 'g', 'Ğ' => 'G',
                'ü' => 'u', 'Ü' => 'U', 'ö' => 'o', 'Ö' => 'O', 'ç' => 'c', 'Ç' => 'C'
            ]);

            $user = User::create([
                'name' => $name,
                'first_name' => $firstName,
                'surname' => $lastName,
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

    /**
     * ERP'den davetiye oluştur
     * ERP sisteminden portal'a kullanıcı davet etmek için kullanılır.
     */
    public function createFromErp(
        int $companyId,
        string $email,
        string $firstName,
        string $lastName,
        ?int $contactId = null,
        string $roleName = 'Portal User'
    ): PortalInvitation {
        $config = config('portal.invitation');

        // Bu firmada aynı email için bekleyen davet var mı?
        $existingInvitation = PortalInvitation::where('email', $email)
            ->where('company_id', $companyId)
            ->pending()
            ->first();

        if ($existingInvitation && $existingInvitation->isValid()) {
            throw new \Exception('Bu email için zaten bekleyen bir davetiye mevcut.');
        }

        // Bu firmada zaten kayıtlı bir portal kullanıcısı var mı?
        $existingUser = User::where('email', $email)
            ->where('company_id', $companyId)
            ->where('is_portal_user', true)
            ->first();

        if ($existingUser) {
            throw new \Exception('Bu email adresi ile kayıtlı bir kullanıcı zaten mevcut.');
        }

        // Firmada ilk kullanıcı mı? İlk kullanıcı otomatik admin olur
        $existingUsersCount = User::where('company_id', $companyId)
            ->where('is_portal_user', true)
            ->count();

        $acceptedInvitationsCount = PortalInvitation::where('company_id', $companyId)
            ->where('status', PortalInvitation::STATUS_ACCEPTED)
            ->count();

        // Eğer firmada hiç kullanıcı ve kabul edilmiş davetiye yoksa, ilk kullanıcı admin olur
        if ($existingUsersCount === 0 && $acceptedInvitationsCount === 0) {
            $roleName = 'Portal Admin';
        }

        // Davetiye oluştur
        $invitation = PortalInvitation::create([
            'contact_id' => $contactId,
            'company_id' => $companyId,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'token' => $this->generateToken($config['token_length']),
            'invited_by_user_id' => null,
            'invited_by_portal_user_id' => null,
            'invited_from_erp' => true,
            'role_name' => $roleName,
            'sent_at' => now(),
            'expires_at' => now()->addDays($config['expires_in_days']),
            'status' => PortalInvitation::STATUS_PENDING,
            'is_active' => true,
        ]);

        // Davetiye emaili gönder
        $this->sendInvitationEmail($invitation);

        return $invitation;
    }

    /**
     * Davetiye emaili gönder
     */
    protected function sendInvitationEmail(PortalInvitation $invitation): void
    {
        $portalUrl = config('portal.frontend_url', 'https://portal.yudo.com.tr');
        $inviteUrl = $portalUrl . '/davet/' . $invitation->token;

        Mail::to($invitation->email)->send(new \App\Mail\PortalInvitationMail($invitation, $inviteUrl));
    }

    /**
     * Direkt kullanıcı oluştur (davetiye olmadan)
     * Portal Admin tarafından kullanılır.
     */
    public function createUserDirectly(
        string $email,
        string $firstName,
        string $lastName,
        string $password,
        User $createdBy,
        string $roleName = 'Portal User',
        ?string $ip = null
    ): User {
        // Zaten kayıtlı bir portal kullanıcısı var mı?
        $existingUser = User::where('email', $email)
            ->where('is_portal_user', true)
            ->first();

        if ($existingUser) {
            throw new \Exception('Bu email adresi ile kayıtlı bir kullanıcı zaten mevcut.');
        }

        // Max kullanıcı limiti kontrolü
        $maxUsers = config('portal.users.max_per_company', 10);
        $currentUsers = User::where('company_id', $createdBy->company_id)
            ->where('is_portal_user', true)
            ->count();

        if ($currentUsers >= $maxUsers) {
            throw new \Exception("Maksimum kullanıcı limitine ({$maxUsers}) ulaşıldı.");
        }

        return DB::transaction(function () use ($email, $firstName, $lastName, $password, $createdBy, $roleName) {
            // Turkce karakterleri ASCII'ye cevir
            $fullName = trim($firstName . ' ' . $lastName);
            $name = strtr($fullName, [
                'ş' => 's', 'Ş' => 'S', 'ı' => 'i', 'İ' => 'I', 'ğ' => 'g', 'Ğ' => 'G',
                'ü' => 'u', 'Ü' => 'U', 'ö' => 'o', 'Ö' => 'O', 'ç' => 'c', 'Ç' => 'C'
            ]);

            // Portal kullanıcısı oluştur
            $user = User::create([
                'name' => $name,
                'first_name' => $firstName,
                'surname' => $lastName,
                'email' => $email,
                'password' => Hash::make($password),
                'is_portal_user' => true,
                'company_id' => $createdBy->company_id,
                'is_company_admin' => $roleName === 'Portal Admin',
                'is_active' => true,
                'must_change_password' => true, // İlk girişte şifre değiştirmeyi zorla
            ]);

            // Rol ata
            $user->assignRole($roleName);

            // Hoş geldin emaili gönder
            Mail::to($user->email)->send(new UserCreatedMail($user, $password));

            return $user;
        });
    }
}
