<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ERP Job State Model - READ only
 * job_states tablosundaki state tanımlarını okur.
 */
class JobState extends Model
{
    protected $table = 'job_states';

    public $timestamps = false;

    // State sabitleri (ERP'deki tanımlar)
    const STATE_PORTAL_REQUEST_RECEIVED = 5; // Portal'dan talep geldi

    protected $fillable = [
        'name',
        'english_name',
        'aciklama',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    /**
     * Bu state'e ait log kayıtları
     */
    public function logs(): HasMany
    {
        return $this->hasMany(JobStateLog::class, 'job_state_id');
    }
}
