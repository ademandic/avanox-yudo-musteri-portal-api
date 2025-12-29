<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Portal User Model - Full CRUD
 * Portal müşteri kullanıcıları için.
 */
class PortalUser extends Authenticatable implements JWTSubject
{
    protected $table = 'portal_users';

    protected $fillable = [
        'contact_id',
        'company_id',
        'email',
        'password',
        'remember_token',
        'last_login_at',
        'last_login_ip',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
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
        ];
    }

    /**
     * İlişkili iletişim kişisi (ERP'den)
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * İlişkili firma (ERP'den)
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Kullanıcının oluşturduğu talepler
     */
    public function portalRequests(): HasMany
    {
        return $this->hasMany(PortalRequest::class);
    }

    /**
     * Kullanıcının kabul ettiği davet
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(PortalInvitation::class, 'id', 'portal_user_id');
    }

    /**
     * Scope: Sadece aktif kullanıcılar
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Kullanıcının tam adı (contact'tan)
     */
    public function getFullNameAttribute(): ?string
    {
        return $this->contact?->full_name;
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
