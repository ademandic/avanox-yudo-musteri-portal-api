<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP Company Model - READONLY
 * Mevcut ERP tablosuna bağlı, sadece okuma yapılır.
 */
class Company extends Model
{
    protected $table = 'companies';

    protected $fillable = []; // Readonly - no fillable fields

    protected $casts = [
        'is_supplier' => 'boolean',
        'is_forwarder' => 'boolean',
        'is_molder' => 'boolean',
        'is_mold_maker' => 'boolean',
        'is_final_customer' => 'boolean',
        'is_t1' => 'boolean',
        'is_t2' => 'boolean',
        'international_or_local' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Firmaya ait iletişim kişileri
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Firmaya atanmış satışçı
     */
    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_person_id');
    }

    /**
     * Firmaya ait portal kullanıcıları
     */
    public function portalUsers(): HasMany
    {
        return $this->hasMany(PortalUser::class);
    }

    /**
     * Firmaya ait işler (mold_maker olarak)
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'mold_maker_id');
    }

    /**
     * Firmaya ait portal talepleri
     */
    public function portalRequests(): HasMany
    {
        return $this->hasMany(PortalRequest::class);
    }

    /**
     * Scope: Sadece aktif firmalar
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: Sadece kalıpçı firmalar
     */
    public function scopeMoldMakers($query)
    {
        return $query->where('is_mold_maker', 1);
    }
}
