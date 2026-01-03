<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP Offer State Log Model - READ only from Portal
 * offer_state_logs tablosundaki teklif durumu geçmişini okur.
 * Bu tablo ERP tarafından güncellenir, portal sadece okur.
 */
class OfferStateLog extends Model
{
    protected $table = 'offer_state_logs';

    public $timestamps = false;

    protected $fillable = [
        'offer_id',
        'offer_states_id',
        'tarih_saat',
        'aciklama',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'offer_id' => 'integer',
        'offer_states_id' => 'integer',
        'user_id' => 'integer',
        'is_active' => 'integer',
        'tarih_saat' => 'datetime',
    ];

    /**
     * İlişkili teklif
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * State bilgisi
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(OfferState::class, 'offer_states_id');
    }

    /**
     * İşlemi yapan kullanıcı
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Belirli bir offer için loglar
     */
    public function scopeForOffer($query, int $offerId)
    {
        return $query->where('offer_id', $offerId);
    }

    /**
     * Scope: Aktif loglar
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: Portal'da görünecek state'ler
     */
    public function scopePortalVisible($query)
    {
        return $query->whereIn('offer_states_id', OfferState::PORTAL_VISIBLE_STATES);
    }

    /**
     * Scope: Tarihe göre sırala
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('tarih_saat', 'desc');
    }
}
