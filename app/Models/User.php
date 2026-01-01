<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * User Model - ERP + Portal
 *
 * Bu model hem ERP kullanıcılarını (satışçı/tasarımcı) hem de
 * Portal kullanıcılarını (müşteri) temsil eder.
 *
 * - is_portal_user = false: ERP kullanıcısı (READONLY)
 * - is_portal_user = true: Portal kullanıcısı (Full CRUD)
 */
class User extends Authenticatable implements JWTSubject
{
    use HasRoles;

    protected $table = 'users';

    /**
     * Portal kullanıcıları için yazılabilir alanlar
     * ERP kullanıcıları READONLY kalacak
     */
    protected $fillable = [
        // Temel alanlar (portal kullanıcıları için)
        'first_name',
        'surname',
        'email',

        // Portal alanları
        'is_portal_user',
        'company_id',
        'contact_id',
        'is_company_admin',
        'is_active',
        'password',

        // 2FA alanları
        'two_factor_code',
        'two_factor_expires_at',
        'two_factor_attempts',
        'locked_until',

        // Session alanları
        'last_login_at',
        'last_login_ip',
        'last_activity_at',
        'current_session_id',

        // Tercihler
        'portal_theme',
        'portal_language',
    ];

    protected $hidden = [
        'password',
        'email_password',
        'remember_token',
        'two_factor_code',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_portal_user' => 'boolean',
        'is_company_admin' => 'boolean',
        'is_active' => 'boolean',
        'two_factor_expires_at' => 'datetime',
        'two_factor_attempts' => 'integer',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * JWT identifier
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * JWT custom claims
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'company_id' => $this->company_id,
            'email' => $this->email,
            'is_portal_user' => $this->is_portal_user,
            'is_company_admin' => $this->is_company_admin,
        ];
    }

    /**
     * Kullanıcının tam adı
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->surname}");
    }

    /**
     * İlişkili firma
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * İlişkili contact (firma yetkilisi)
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Bu satışçının sorumlu olduğu firmalar (ERP)
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'sales_person_id');
    }

    /**
     * Bu satışçının/tasarımcının atandığı işler (ERP)
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /**
     * Bu satışçının gönderdiği davetler (ERP)
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(PortalInvitation::class, 'invited_by_user_id');
    }

    /**
     * Bu admin'in gönderdiği davetler (Portal)
     */
    public function sentPortalInvitations(): HasMany
    {
        return $this->hasMany(PortalInvitation::class, 'invited_by_portal_user_id');
    }

    /**
     * Kullanıcının oluşturduğu talepler (Portal)
     */
    public function portalRequests(): HasMany
    {
        return $this->hasMany(PortalRequest::class, 'user_id');
    }

    /**
     * Scope: Sadece portal kullanıcıları
     */
    public function scopePortalUsers($query)
    {
        return $query->where('is_portal_user', true);
    }

    /**
     * Scope: Sadece aktif portal kullanıcıları
     */
    public function scopeActivePortalUsers($query)
    {
        return $query->where('is_portal_user', true)->where('is_active', true);
    }

    /**
     * Scope: Sadece ERP kullanıcıları
     */
    public function scopeErpUsers($query)
    {
        return $query->where('is_portal_user', false);
    }

    /**
     * Scope: Belirli firmadaki portal kullanıcıları
     */
    public function scopeOfCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Portal kullanıcısı mı?
     */
    public function isPortalUser(): bool
    {
        return $this->is_portal_user ?? false;
    }

    /**
     * Firma admini mi?
     */
    public function isCompanyAdmin(): bool
    {
        return $this->is_company_admin ?? false;
    }

    /**
     * Son giriş bilgisini güncelle
     */
    public function updateLastLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }
}
