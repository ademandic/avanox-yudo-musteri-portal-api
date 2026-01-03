<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP Drawing State Log Model - READ only from Portal
 * drawing_states_logs tablosundaki tasarım durumu geçmişini okur.
 * Bu tablo ERP tarafından güncellenir, portal sadece okur.
 */
class DrawingStateLog extends Model
{
    protected $table = 'drawing_states_logs';

    public $timestamps = false;

    protected $fillable = [
        'technical_data_id',
        'drawing_state_id',
        'tarih_saat',
        'aciklama',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'technical_data_id' => 'integer',
        'drawing_state_id' => 'integer',
        'user_id' => 'integer',
        'is_active' => 'integer',
        'tarih_saat' => 'datetime',
    ];

    /**
     * İlişkili teknik veri (page=1)
     */
    public function technicalData(): BelongsTo
    {
        return $this->belongsTo(TechnicalData::class);
    }

    /**
     * State bilgisi
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(DrawingState::class, 'drawing_state_id');
    }

    /**
     * İşlemi yapan kullanıcı
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Belirli bir technical_data için loglar
     */
    public function scopeForTechnicalData($query, int $technicalDataId)
    {
        return $query->where('technical_data_id', $technicalDataId);
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
        return $query->whereIn('drawing_state_id', DrawingState::PORTAL_VISIBLE_STATES);
    }

    /**
     * Scope: Tarihe göre sırala
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('tarih_saat', 'desc');
    }
}
