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
    | 2FA (İki Faktörlü Doğrulama) Ayarları
    |--------------------------------------------------------------------------
    */
    'two_factor' => [
        // Kod geçerlilik süresi (dakika)
        'code_validity_minutes' => env('PORTAL_2FA_CODE_VALIDITY', 5),

        // Maksimum yanlış deneme sayısı
        'max_attempts' => env('PORTAL_2FA_MAX_ATTEMPTS', 3),

        // Hesap kilitleme süresi (dakika)
        'lockout_minutes' => env('PORTAL_2FA_LOCKOUT_MINUTES', 15),

        // Yeni kod gönderme aralığı (saniye)
        'resend_cooldown_seconds' => env('PORTAL_2FA_RESEND_COOLDOWN', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Ayarları
    |--------------------------------------------------------------------------
    */
    'session' => [
        // İnaktivite timeout süresi (dakika)
        'timeout_minutes' => env('PORTAL_SESSION_TIMEOUT', 5),

        // Tek oturum kontrolü
        'single_session' => env('PORTAL_SINGLE_SESSION', true),
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
    | Kullanıcı Ayarları
    |--------------------------------------------------------------------------
    */
    'users' => [
        // Firma başına maksimum portal kullanıcı sayısı
        'max_per_company' => env('PORTAL_MAX_USERS_PER_COMPANY', 10),
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
        3 => 'Tasarım + Teklif',
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
