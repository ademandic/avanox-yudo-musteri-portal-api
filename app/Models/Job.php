<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ERP Job Model - READ + INSERT
 * Mevcut ERP tablosuna bağlı. Okuma ve yeni kayıt ekleme yapılabilir.
 */
class Job extends Model
{
    protected $table = 'jobs';

    protected $fillable = [
        'job_no',
        'job_category_id',
        'mold_maker_id',
        'mold_maker_contact_id',
        'mold_maker_ref_no',
        'sub_mold_maker_id',
        'sub_mold_maker_contact_id',
        'molder_t1_id',
        'molder_t1_contact_id',
        'molder_t1_ref_no',
        'final_customer_id',
        'final_customer_contact_id',
        'market_id',
        'product_name_id',
        'part_name_id',
        'part_description',
        'directory',
        'related_job_no',
        'related_yudo_id_no',
        'yudo_id_no',
        'aciklama',
        'related_service_id',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Müşteri firma (mold_maker)
     */
    public function moldMaker(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'mold_maker_id');
    }

    /**
     * Müşteri iletişim kişisi
     */
    public function moldMakerContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'mold_maker_contact_id');
    }

    /**
     * Atanan satışçı
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Teknik veriler
     */
    public function technicalData(): HasOne
    {
        return $this->hasOne(TechnicalData::class);
    }

    /**
     * İş'e bağlı dosyalar
     */
    public function files(): HasMany
    {
        return $this->hasMany(ErpFile::class, 'job_id');
    }

    /**
     * Portal talebi (varsa)
     */
    public function portalRequest(): HasOne
    {
        return $this->hasOne(PortalRequest::class);
    }

    /**
     * Scope: Sadece aktif işler
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: Belirli bir firmaya ait işler
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('mold_maker_id', $companyId);
    }
}
