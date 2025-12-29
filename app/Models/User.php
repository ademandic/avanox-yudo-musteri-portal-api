<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ERP User Model - READONLY
 * Mevcut ERP tablosuna bağlı, sadece okuma yapılır.
 * Bu model ERP satışçı/tasarımcı kullanıcılarını temsil eder.
 */
class User extends Model
{
    protected $table = 'users';

    protected $fillable = []; // Readonly - no fillable fields

    protected $hidden = [
        'password',
        'email_password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Kullanıcının tam adı
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->surname}");
    }

    /**
     * Bu satışçının sorumlu olduğu firmalar
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'sales_person_id');
    }

    /**
     * Bu satışçının/tasarımcının atandığı işler
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /**
     * Bu satışçının gönderdiği davetler
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(PortalInvitation::class, 'invited_by_user_id');
    }
}
