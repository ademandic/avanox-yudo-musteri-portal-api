<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP Job State Log Model - INSERT only from Portal
 * job_state_logs tablosuna log kaydı ekler.
 */
class JobStateLog extends Model
{
    protected $table = 'job_state_logs';

    // Portal tarafından kullanılacak state sabitleri
    const STATE_PORTAL_REQUEST_RECEIVED = 5; // Portal'dan talep geldi

    protected $fillable = [
        'job_id',
        'job_state_id',
        'aciklama',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'job_id' => 'integer',
        'job_state_id' => 'integer',
        'is_active' => 'integer',
        'created_by' => 'integer',
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
     * State bilgisi (job_states tablosu)
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(JobState::class, 'job_state_id');
    }

    /**
     * Oluşturan kullanıcı
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Portal talebi alındığında log kaydı oluştur
     */
    public static function logPortalRequestReceived(int $jobId, ?int $userId = null, ?string $description = null): self
    {
        return self::create([
            'job_id' => $jobId,
            'job_state_id' => self::STATE_PORTAL_REQUEST_RECEIVED,
            'aciklama' => $description ?? 'Portal üzerinden talep alındı',
            'is_active' => 1,
            'created_by' => $userId,
        ]);
    }

    /**
     * Scope: Belirli bir job için tüm loglar
     */
    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    /**
     * Scope: Aktif loglar
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
