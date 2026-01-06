<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP File Model - READ + INSERT
 * Mevcut ERP tablosuna bağlı. Okuma ve yeni kayıt ekleme yapılabilir.
 * NOT: "File" yerine "ErpFile" kullanıyoruz çünkü "File" PHP reserved word.
 */
class ErpFile extends Model
{
    protected $table = 'files';

    /**
     * Portal müşterisinin görebileceği dosya türleri
     * Whitelist: Sadece bu türdeki dosyalar portal'da görünür
     */
    const PORTAL_VISIBLE_TYPES = [
        'technical_datas',      // Teknik veri dosyaları (portal uploads dahil)
        'drawing_state_logs',   // Tasarım dosyaları
        'portal_requests',      // Portal üzerinden yüklenen dosyalar
    ];

    protected $fillable = [
        'job_id',
        'baglanti_id',
        'baglanti_tablo_adi',
        'dosya_yolu',
        'dosya_adi',
        'extension',
        'dosya_url',
        'dosya_boyut',
        'aciklama',
        'user_id',
        'ilgili_work_log_id',
        'is_active',
    ];

    protected $casts = [
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
     * Scope: Sadece aktif dosyalar
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: Belirli bir tabloya bağlı dosyalar
     */
    public function scopeForTable($query, string $tableName, int $id)
    {
        return $query->where('baglanti_tablo_adi', $tableName)
                     ->where('baglanti_id', $id);
    }

    /**
     * Scope: Portal'da görünür dosya türleri
     */
    public function scopePortalVisible($query)
    {
        return $query->whereIn('baglanti_tablo_adi', self::PORTAL_VISIBLE_TYPES);
    }

    /**
     * Dosya boyutunu formatla
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->dosya_boyut;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
