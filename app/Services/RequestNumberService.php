<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Request Number Service
 * Yeni portal talep numarası oluşturur.
 * Format: PR-{YYYY}-{SIRA} (örn: PR-2025-0001)
 */
class RequestNumberService
{
    /**
     * Yeni request_no oluştur
     * Race condition'ı önlemek için transaction ve lock kullanılır.
     */
    public function generate(): string
    {
        return DB::transaction(function () {
            $config = config('portal.request_number');
            $year = date($config['year_format']); // 2025, 2026...
            $prefix = "{$config['prefix']}-{$year}-";
            $padding = $config['padding'];

            // O yıla ait son numarayı bul (lock ile)
            $lastRequest = DB::table('portal_requests')
                ->where('request_no', 'LIKE', $prefix . '%')
                ->orderByRaw("CAST(SUBSTRING(request_no, LEN(?) + 1, 10) AS INT) DESC", [$prefix])
                ->lockForUpdate()
                ->first();

            if ($lastRequest) {
                // Mevcut numaradan devam et
                $lastNumber = (int) str_replace($prefix, '', $lastRequest->request_no);
                $newNumber = $lastNumber + 1;
            } else {
                // Yılın ilk talebi
                $newNumber = 1;
            }

            return $prefix . str_pad($newNumber, $padding, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Request numarası formatını doğrula
     */
    public function validate(string $requestNo): bool
    {
        $config = config('portal.request_number');
        $pattern = '/^' . $config['prefix'] . '-\d{4}-\d{' . $config['padding'] . ',}$/';

        return (bool) preg_match($pattern, $requestNo);
    }

    /**
     * Request numarasından yıl bilgisini al
     */
    public function getYear(string $requestNo): ?int
    {
        if (!$this->validate($requestNo)) {
            return null;
        }

        $config = config('portal.request_number');
        $prefixLength = strlen($config['prefix']) + 1; // PR-
        $yearPart = substr($requestNo, $prefixLength, 4);

        return (int) $yearPart;
    }
}
