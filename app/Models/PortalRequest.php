<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Portal Request Model - Full CRUD
 * Müşteri talepleri için.
 */
class PortalRequest extends Model
{
    protected $table = 'portal_requests';

    // Talep tipleri
    const TYPE_DESIGN = 1;       // Tasarım Talebi
    const TYPE_OFFER = 2;        // Teklif Talebi
    const TYPE_BOTH = 3;         // Tasarım + Teklif
    const TYPE_CONTROLLER = 4;   // Kontrol Cihazı Talebi
    const TYPE_SPARE_PARTS = 5;  // Yedek Parça Talebi

    // Öncelik seviyeleri
    const PRIORITY_LOW = 1;
    const PRIORITY_NORMAL = 2;
    const PRIORITY_HIGH = 3;
    const PRIORITY_URGENT = 4;

    protected $fillable = [
        'request_no',
        'portal_user_id',
        'company_id',
        'job_id',
        'technical_data_id',
        'request_type',
        'customer_reference_code',
        'customer_mold_code',
        'customer_notes',
        'internal_notes',
        'expected_delivery_date',
        'priority',
        'current_state_id',
        'is_active',
    ];

    protected $casts = [
        'request_type' => 'integer',
        'priority' => 'integer',
        'technical_data_id' => 'integer',
        'expected_delivery_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Talebi oluşturan portal kullanıcısı (users tablosunda is_portal_user=true)
     */
    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'portal_user_id');
    }

    /**
     * İlişkili firma
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * İlişkili iş (ERP)
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
        return $this->belongsTo(TechnicalData::class);
    }

    /**
     * Mevcut durum
     */
    public function currentState(): BelongsTo
    {
        return $this->belongsTo(PortalRequestState::class, 'current_state_id');
    }

    /**
     * Durum geçmişi
     */
    public function stateLogs(): HasMany
    {
        return $this->hasMany(PortalRequestStateLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Talep tipi label'ı
     */
    public function getTypeLabelAttribute(): string
    {
        return config('portal.request_types')[$this->request_type] ?? 'Bilinmiyor';
    }

    /**
     * Öncelik label'ı
     */
    public function getPriorityLabelAttribute(): string
    {
        return config('portal.priorities')[$this->priority] ?? 'Normal';
    }

    /**
     * Talep iptal edilebilir mi?
     */
    public function isCancellable(): bool
    {
        // Sadece "Talep Alındı" veya "İnceleniyor" durumundayken iptal edilebilir
        return in_array($this->current_state_id, [
            PortalRequestState::STATE_RECEIVED,
            PortalRequestState::STATE_REVIEWING,
        ]);
    }

    /**
     * Talep düzenlenebilir mi?
     */
    public function isEditable(): bool
    {
        // Sadece "Talep Alındı" durumundayken düzenlenebilir
        return $this->current_state_id === PortalRequestState::STATE_RECEIVED;
    }

    /**
     * Scope: Sadece aktif talepler
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: Belirli bir firmaya ait talepler
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Belirli bir kullanıcıya ait talepler
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('portal_user_id', $userId);
    }

    /**
     * Scope: Belirli bir durumda olan talepler
     */
    public function scopeInState($query, int $stateId)
    {
        return $query->where('current_state_id', $stateId);
    }

    /**
     * Scope: Tasarım talepleri
     */
    public function scopeDesignRequests($query)
    {
        return $query->where('request_type', self::TYPE_DESIGN);
    }

    /**
     * Scope: Teklif talepleri
     */
    public function scopeOfferRequests($query)
    {
        return $query->where('request_type', self::TYPE_OFFER);
    }

    /**
     * Scope: Tasarım + Teklif talepleri
     */
    public function scopeBothRequests($query)
    {
        return $query->where('request_type', self::TYPE_BOTH);
    }
}
