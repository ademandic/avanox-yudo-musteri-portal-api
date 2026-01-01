<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP Contact Model - READONLY
 * Mevcut ERP tablosuna bağlı, sadece okuma yapılır.
 */
class Contact extends Model
{
    protected $table = 'contacts';

    protected $fillable = []; // Readonly - no fillable fields

    protected $casts = [
        'default_contact' => 'boolean',
        'cargo_contact' => 'boolean',
        'payment_contact' => 'boolean',
        'design_contact' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Kişinin bağlı olduğu firma
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Bu email ile kayıtlı portal kullanıcısı (varsa)
     * Portal kullanıcıları artık users tablosunda is_portal_user=true olarak tutulur
     */
    public function portalUser()
    {
        return User::where('email', $this->email)
            ->where('is_portal_user', true)
            ->first();
    }

    /**
     * Tam isim
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->name} {$this->surname}");
    }

    /**
     * Scope: Sadece aktif kişiler
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: Tasarım yetkilileri
     */
    public function scopeDesignContacts($query)
    {
        return $query->where('design_contact', 1);
    }
}
