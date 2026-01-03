<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ERP Offer State Model - READ only
 * offer_states tablosundaki teklif durumu tanımlarını okur.
 */
class OfferState extends Model
{
    protected $table = 'offer_states';

    public $timestamps = false;

    // Portal'da müşteriye gösterilecek state ID'leri
    const PORTAL_VISIBLE_STATES = [1, 3, 4];

    // State sabitleri
    const STATE_WAITING_TO_BE_PREPARED = 1; // Hazırlanması Bekleniyor
    const STATE_OFFER_PREPARED = 3;          // Teklif Hazırlandı
    const STATE_SENT_TO_CUSTOMER = 4;        // Teklif Müşteriye Gönderildi

    protected $fillable = [
        'name',
        'english_name',
        'aciklama',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    /**
     * Bu state'e ait log kayıtları
     */
    public function logs(): HasMany
    {
        return $this->hasMany(OfferStateLog::class, 'offer_states_id');
    }

    /**
     * Scope: Portal'da görünecek state'ler
     */
    public function scopePortalVisible($query)
    {
        return $query->whereIn('id', self::PORTAL_VISIBLE_STATES);
    }

    /**
     * Portal'da görünür mü?
     */
    public function isPortalVisible(): bool
    {
        return in_array($this->id, self::PORTAL_VISIBLE_STATES);
    }
}
