<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ERP Offer Model - READ only
 * offers tablosundaki teklif kayıtlarını okur.
 * Bu tablo ERP tarafından oluşturulur, portal sadece okur.
 */
class Offer extends Model
{
    protected $table = 'offers';

    protected $fillable = [
        'job_id',
        'tech_data_id',
        'company_name',
        'offer_no',
        'is_active',
    ];

    protected $casts = [
        'job_id' => 'integer',
        'tech_data_id' => 'integer',
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
     * İlişkili teknik veri
     */
    public function technicalData(): BelongsTo
    {
        return $this->belongsTo(TechnicalData::class, 'tech_data_id');
    }

    /**
     * Offer state logları
     */
    public function stateLogs(): HasMany
    {
        return $this->hasMany(OfferStateLog::class);
    }

    /**
     * Portal'da görünecek state logları
     */
    public function portalVisibleStateLogs(): HasMany
    {
        return $this->hasMany(OfferStateLog::class)
            ->whereIn('offer_states_id', OfferState::PORTAL_VISIBLE_STATES)
            ->orderBy('tarih_saat', 'desc');
    }

    /**
     * Son state
     */
    public function latestState(): ?OfferStateLog
    {
        return $this->stateLogs()
            ->portalVisible()
            ->latestFirst()
            ->first();
    }

    /**
     * Scope: Aktif teklifler
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: Belirli bir job için teklifler
     */
    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }
}
