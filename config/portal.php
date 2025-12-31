<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Güvenlik Ayarları
    |--------------------------------------------------------------------------
    */
    'api_key' => env('PORTAL_API_KEY'),

    'allowed_ips' => array_filter(
        explode(',', env('PORTAL_ALLOWED_IPS', ''))
    ),

    'rate_limit' => [
        'per_minute' => env('PORTAL_RATE_LIMIT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dosya Yükleme Ayarları (NFS Mount)
    |--------------------------------------------------------------------------
    | Klasör yapısı: {base_path}/Sales/{yıl}/{ay}/{job_no}/{subfolder}/{technical_data_id}/
    | Örnek: /mnt/yudo_data/Sales/2025/01/YT25-1/drawing_log/12345/kalip-cizim.pdf
    */
    'upload' => [
        'max_size' => env('FILE_UPLOAD_MAX_SIZE', 52428800), // 50MB
        'allowed_extensions' => [
            'pdf', 'jpg', 'jpeg', 'png',
            'dwg', 'step', 'stp', 'iges', 'igs', 'x_t',
            'ai', 'psd', 'zip', 'rar'
        ],
        'base_path' => env('FILE_STORAGE_PATH', '/mnt/yudo_data'),
        'subfolder' => env('FILE_STORAGE_SUBFOLDER', 'drawing_log'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Numara Formatları
    |--------------------------------------------------------------------------
    */
    'job_number' => [
        'prefix' => 'YT',
        'year_format' => 'y',  // 25, 26...
    ],

    'request_number' => [
        'prefix' => 'PR',
        'year_format' => 'Y',  // 2025, 2026...
        'padding' => 4,        // PR-2025-0001
    ],

    /*
    |--------------------------------------------------------------------------
    | Davetiye Ayarları
    |--------------------------------------------------------------------------
    */
    'invitation' => [
        'expires_in_days' => 7,
        'token_length' => 64,
    ],

    /*
    |--------------------------------------------------------------------------
    | Talep Tipleri
    |--------------------------------------------------------------------------
    */
    'request_types' => [
        1 => 'Tasarım Talebi',
        2 => 'Teklif Talebi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Öncelik Seviyeleri
    |--------------------------------------------------------------------------
    */
    'priorities' => [
        1 => 'Düşük',
        2 => 'Normal',
        3 => 'Yüksek',
        4 => 'Acil',
    ],

    /*
    |--------------------------------------------------------------------------
    | Malzeme Listesi (Sabit)
    |--------------------------------------------------------------------------
    */
    'materials' => [
        'ABS' => 'ABS',
        'PP' => 'PP (Polipropilen)',
        'PS' => 'PS (Polistiren)',
        'PA' => 'PA (Naylon)',
        'PC' => 'PC (Polikarbonat)',
        'POM' => 'POM (Asetal)',
        'PE' => 'PE (Polietilen)',
        'PET' => 'PET',
        'PMMA' => 'PMMA (Akrilik)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Katkı Türleri (Sabit)
    |--------------------------------------------------------------------------
    */
    'additives' => [
        'glass_fiber' => 'Cam Elyaf',
        'talc' => 'Talc',
        'mineral' => 'Mineral',
        'carbon_fiber' => 'Karbon Elyaf',
    ],

    /*
    |--------------------------------------------------------------------------
    | Meme Tipleri (Sabit)
    |--------------------------------------------------------------------------
    */
    'nozzle_types' => [
        'parca' => 'Parçaya (Direct Gate)',
        'yolluk' => 'Yolluğa (Runner)',
    ],
];
