<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP TechnicalDataSystem Model - READ + INSERT
 * technical_data_systems tablosu - sistem verileri
 */
class TechnicalDataSystem extends Model
{
    protected $table = 'technical_data_systems';

    protected $fillable = [
        'technical_data_id',
        'baski_no',
        'parca_agirligi',
        'renk_degisimi',
        'parca_gorselligi',
        'et_kalinligi',
        'malzeme',
        'malzeme_katki',
        'malzeme_katki_yuzdesi',
        'yanmazlik',
        'hammadde_calisma_sicakligi',
        'kalip_sicakligi',
        'tedarik_sekli',
        'vidali_meme',
        'open_valve',
        'meme_sayisi',
        'acili_meme_sayisi',
        'piston_hareket_cinsi',
        'piston_modeli',
        'sirali_kullanimi',
        'side_silindir_sayisi',
        'sistem_adi',
        'meme_capi',
        'parcada_konumu',
        'valf_pimi_capi',
        'valve_pin_tipi',
        'tip_sekli',
        'tip_sekli_ozel_mi',
        'tip_malzeme',
        'gate_bush_tipi',
        'gate_bush_ozel_mi',
        'patlama_capi',
        'seal_cap',
        'gate_bush_sayisi',
        'farkli_meme_tipleri',
        'tek_meme_boyu',
        'manifold_tipi',
        'manifold_sekli',
        'iso_manifold_kat_sayisi',
        'manifold_malzemesi',
        'nl_tipi',
        'sr',
        'aciklama',
    ];

    protected $casts = [
        'parca_agirligi' => 'decimal:4',
        'et_kalinligi' => 'decimal:4',
        'malzeme_katki_yuzdesi' => 'float',
        'vidali_meme' => 'boolean',
        'tip_sekli_ozel_mi' => 'boolean',
        'gate_bush_ozel_mi' => 'boolean',
        'farkli_meme_tipleri' => 'boolean',
        'patlama_capi' => 'decimal:4',
        'tek_meme_boyu' => 'decimal:4',
        'sr' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * İlişkili teknik data
     */
    public function technicalData(): BelongsTo
    {
        return $this->belongsTo(TechnicalData::class);
    }
}
