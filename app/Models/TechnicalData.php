<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ERP TechnicalData Model - READ + INSERT
 * Mevcut ERP tablosuna bağlı. Okuma ve yeni kayıt ekleme yapılabilir.
 */
class TechnicalData extends Model
{
    protected $table = 'technical_datas';

    protected $fillable = [
        'job_id',
        'page',
        'teknik_data_tipi',
        'teknik_data_adi',
        'double_injection',
        'parca_gorselligi',
        'resim_ilgilileri',
        'resim_bilgi',
        'tedarik_sekli',
        'tedarik_sekli_2',
        'vidali_meme',
        'vidali_meme_2',
        'open_valve',
        'open_valve_2',
        'piston_hareket_cinsi',
        'piston_hareket_cinsi_2',
        'sirali_kullanimi',
        'sirali_kullanimi_2',
        'side_silindir_sayisi',
        'side_silindir_sayisi_2',
        'sistem_adi',
        'sistem_adi_2',
        'meme_capi',
        'meme_capi_2',
        'parcada_konumu',
        'parcada_konumu_2',
        'valf_pimi_capi',
        'valf_pimi_capi_2',
        'valve_pin_tipi',
        'valve_pin_tipi_2',
        'tip_sekli',
        'tip_sekli_2',
        'tip_sekli_ozel_mi',
        'tip_sekli_ozel_mi_2',
        'tip_malzeme',
        'tip_malzeme_2',
        'gate_bush_tipi',
        'gate_bush_tipi_2',
        'patlama_capi',
        'patlama_capi_2',
        'seal_cap',
        'seal_cap_2',
        'malzeme',
        'malzeme_2',
        'malzeme_katki',
        'malzeme_katki_2',
        'malzeme_katki_yuzdesi',
        'malzeme_katki_yuzdesi_2',
        'kalip_x',
        'kalip_y',
        'kalip_d',
        'kalip_e',
        'kalip_parca_sayisi',
        'meme_sayisi',
        'meme_sayisi_2',
        'parca_agirligi',
        'et_kalinligi',
        'aciklama',
        // Kontrol cihazı alanları
        'cihaz_tipi',
        'cihaz_modeli',
        'cihaz_bolg_sayisi',
        'soket_tipi',
        'soket_kitleme_tipi',
        'pim_baglanti_semasi',
        'cihaz_kablo_uzunlugu',
        'is_active',
    ];

    protected $casts = [
        'double_injection' => 'boolean',
        'vidali_meme' => 'boolean',
        'vidali_meme_2' => 'boolean',
        'tip_sekli_ozel_mi' => 'boolean',
        'tip_sekli_ozel_mi_2' => 'boolean',
        'kalip_x' => 'decimal:2',
        'kalip_y' => 'decimal:2',
        'kalip_d' => 'decimal:2',
        'kalip_e' => 'decimal:2',
        'parca_agirligi' => 'decimal:2',
        'et_kalinligi' => 'decimal:2',
        'malzeme_katki_yuzdesi' => 'decimal:2',
        'malzeme_katki_yuzdesi_2' => 'decimal:2',
        'is_active' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * İlişkili iş
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Drawing state logları (ERP tarafından oluşturulur)
     */
    public function drawingStateLogs(): HasMany
    {
        return $this->hasMany(DrawingStateLog::class);
    }

    /**
     * Portal'da görünecek drawing state logları
     */
    public function portalVisibleDrawingStateLogs(): HasMany
    {
        return $this->hasMany(DrawingStateLog::class)
            ->whereIn('drawing_state_id', DrawingState::PORTAL_VISIBLE_STATES)
            ->orderBy('tarih_saat', 'desc');
    }

    /**
     * Son drawing state
     */
    public function latestDrawingState(): ?DrawingStateLog
    {
        return $this->drawingStateLogs()
            ->portalVisible()
            ->latestFirst()
            ->first();
    }

    /**
     * Scope: Sadece aktif kayıtlar
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: Ana sistem (page = 1)
     */
    public function scopeMainSystem($query)
    {
        return $query->where('page', 1);
    }
}
