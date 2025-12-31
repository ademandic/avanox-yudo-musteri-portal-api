<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Job Number Service
 * Yeni iş numarası oluşturur.
 * Format: YT{YY}-{SIRA} (örn: YT26-1, YT26-2, YT26-125)
 */
class JobNumberService
{
    /**
     * Yeni job_no oluştur
     */
    public function generate(): string
    {
        $config = config('portal.job_number');
        $year = date($config['year_format']); // 25, 26...
        $prefix = "{$config['prefix']}{$year}-";

        // O yıla ait son numarayı bul
        $lastJob = DB::table('jobs')
            ->where('job_no', 'LIKE', $prefix . '%')
            ->orderByRaw("CAST(SUBSTRING(job_no, LEN(?) + 1, 10) AS INT) DESC", [$prefix])
            ->first();

        if ($lastJob) {
            // Mevcut numaradan devam et
            $lastNumber = (int) str_replace($prefix, '', $lastJob->job_no);
            $newNumber = $lastNumber + 1;
        } else {
            // Yılın ilk işi
            $newNumber = 1;
        }

        return $prefix . $newNumber;
    }

    /**
     * Job numarası formatını doğrula
     */
    public function validate(string $jobNo): bool
    {
        $config = config('portal.job_number');
        $pattern = '/^' . $config['prefix'] . '\d{2}-\d+$/';

        return (bool) preg_match($pattern, $jobNo);
    }

    /**
     * Job numarasından yıl bilgisini al
     */
    public function getYear(string $jobNo): ?int
    {
        if (!$this->validate($jobNo)) {
            return null;
        }

        $config = config('portal.job_number');
        $yearPart = substr($jobNo, strlen($config['prefix']), 2);

        return (int) ('20' . $yearPart);
    }
}
