<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Portal Request State Log Model - INSERT Only
 * Talep durum geçmişi için.
 */
class PortalRequestStateLog extends Model
{
    protected $table = 'portal_request_state_logs';

    public $timestamps = false;

    protected $fillable = [
        'portal_request_id',
        'portal_request_state_id',
        'aciklama',
        'changed_by_user_id',
        'changed_by_portal_user_id',
        'is_active',
        'created_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Boot method - created_at otomatik ata
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->created_at) {
                $model->created_at = now();
            }
        });
    }

    /**
     * İlişkili talep
     */
    public function portalRequest(): BelongsTo
    {
        return $this->belongsTo(PortalRequest::class);
    }

    /**
     * İlişkili durum
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(PortalRequestState::class, 'portal_request_state_id');
    }

    /**
     * Değişikliği yapan ERP kullanıcısı
     */
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /**
     * Değişikliği yapan portal kullanıcısı (users tablosunda is_portal_user=true)
     */
    public function changedByPortalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_portal_user_id');
    }

    /**
     * Değişikliği yapan kişinin adı
     */
    public function getChangedByNameAttribute(): ?string
    {
        if ($this->changed_by_user_id) {
            return $this->changedByUser?->full_name;
        }

        if ($this->changed_by_portal_user_id) {
            return $this->changedByPortalUser?->full_name;
        }

        return 'Sistem';
    }

    /**
     * Scope: Sadece aktif loglar
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
