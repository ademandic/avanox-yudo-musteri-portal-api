<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Portal Request State Model - READONLY
 * Talep durum tanımlamaları.
 */
class PortalRequestState extends Model
{
    protected $table = 'portal_request_states';

    public $timestamps = false;

    // Durum sabitleri
    const STATE_RECEIVED = 1;      // Talep Alındı
    const STATE_REVIEWING = 2;     // İnceleniyor
    const STATE_IN_PROGRESS = 3;   // Çalışılıyor
    const STATE_REVISION = 4;      // Revizyon Bekliyor
    const STATE_COMPLETED = 5;     // Tamamlandı
    const STATE_CANCELLED = 6;     // İptal Edildi

    protected $fillable = [
        'name',
        'english_name',
        'color_class',
        'sort_order',
        'aciklama',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Bu durumda olan talepler
     */
    public function portalRequests(): HasMany
    {
        return $this->hasMany(PortalRequest::class, 'current_state_id');
    }

    /**
     * Bu duruma ait log kayıtları
     */
    public function stateLogs(): HasMany
    {
        return $this->hasMany(PortalRequestStateLog::class, 'portal_request_state_id');
    }

    /**
     * Scope: Sadece aktif durumlar
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: Sıralı
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
