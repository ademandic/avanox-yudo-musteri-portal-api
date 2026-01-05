<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Portal Invitation Model - Full CRUD
 * Müşteri davetiye sistemi için.
 */
class PortalInvitation extends Model
{
    protected $table = 'portal_invitations';

    // Durum sabitleri
    const STATUS_PENDING = 1;
    const STATUS_ACCEPTED = 2;
    const STATUS_EXPIRED = 3;
    const STATUS_CANCELLED = 4;

    protected $fillable = [
        'contact_id',
        'company_id',
        'token',
        'email',
        'first_name',
        'last_name',
        'invited_by_user_id',
        'invited_by_portal_user_id',
        'invited_from_erp',
        'role_name',
        'invited_from_ip',
        'accepted_from_ip',
        'sent_at',
        'expires_at',
        'accepted_at',
        'portal_user_id',
        'status',
        'is_active',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'is_active' => 'boolean',
        'invited_from_erp' => 'boolean',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
     * Daveti gönderen ERP kullanıcısı
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * Daveti gönderen Portal Admin kullanıcısı
     */
    public function invitedByPortalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_portal_user_id');
    }

    /**
     * Oluşturulan portal kullanıcısı (users tablosunda is_portal_user=true)
     */
    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'portal_user_id');
    }

    /**
     * Davet geçerli mi?
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->is_active
            && $this->expires_at->isFuture();
    }

    /**
     * Davet süresi dolmuş mu?
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Davet kabul edilmiş mi?
     */
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Durum label'ı
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Bekliyor',
            self::STATUS_ACCEPTED => 'Kabul Edildi',
            self::STATUS_EXPIRED => 'Süresi Doldu',
            self::STATUS_CANCELLED => 'İptal Edildi',
            default => 'Bilinmiyor',
        };
    }

    /**
     * Scope: Geçerli davetler
     */
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                     ->where('is_active', 1)
                     ->where('expires_at', '>', now());
    }

    /**
     * Scope: Bekleyen davetler
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
