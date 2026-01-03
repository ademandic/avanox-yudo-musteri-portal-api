<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ERP Drawing State Model - READ only
 * drawing_states tablosundaki tasarım durumu tanımlarını okur.
 */
class DrawingState extends Model
{
    protected $table = 'drawing_states';

    public $timestamps = false;

    // Portal'da müşteriye gösterilecek state ID'leri
    const PORTAL_VISIBLE_STATES = [1, 6, 18, 19, 21, 31];

    // State sabitleri
    const STATE_3D_STARTED = 1;           // 3D Resim Çizilmeye Başlandı
    const STATE_3D_REQUESTED = 6;         // 3D Resim İstendi
    const STATE_3D_PREPARED = 18;         // 3D Resim Hazır
    const STATE_3D_SENT_TO_CUSTOMER = 19; // 3D Resim Müşteriye Gönderildi
    const STATE_3D_REVISION_STARTED = 21; // 3D Resim Değişikliğine Başlandı
    const STATE_3D_IN_CONTROL = 31;       // 3D Resim Kontrolde

    protected $fillable = [
        'name',
        'english_name',
        'aciklama',
        'class_name',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    /**
     * Bu state'e ait log kayıtları
     */
    public function logs(): HasMany
    {
        return $this->hasMany(DrawingStateLog::class, 'drawing_state_id');
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
