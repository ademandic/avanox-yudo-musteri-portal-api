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
        'Seçilmedi' => 'Seçilmedi',
        'ABS' => 'ABS',
        'ASA' => 'ASA',
        'PP' => 'PP',
        'PC' => 'PC',
        'ABS+PA' => 'ABS+PA',
        'ABS+PBT' => 'ABS+PBT',
        'ABS+PC' => 'ABS+PC',
        'ACS' => 'ACS',
        'AES' => 'AES',
        'AMA' => 'AMA',
        'AP' => 'AP',
        'APO' => 'APO',
        'ASA+PC' => 'ASA+PC',
        'CAB' => 'CAB',
        'COC' => 'COC',
        'CPVC' => 'CPVC',
        'E/BA' => 'E/BA',
        'EMAA' => 'EMAA',
        'EPDM' => 'EPDM',
        'EPDM+PP' => 'EPDM+PP',
        'EVA' => 'EVA',
        'FEP' => 'FEP',
        'GPPS' => 'GPPS',
        'MIPS' => 'MIPS',
        'HIPS' => 'HIPS',
        'IR+HIPS' => 'IR+HIPS',
        'HDPE' => 'HDPE',
        'LDPE' => 'LDPE',
        'LLDPE' => 'LLDPE',
        'LCP' => 'LCP',
        'MDPE' => 'MDPE',
        'MPPO' => 'MPPO',
        'PA' => 'PA',
        'PA11' => 'PA11',
        'PA12' => 'PA12',
        'PA46' => 'PA46',
        'PA6' => 'PA6',
        'PA610' => 'PA610',
        'PA66' => 'PA66',
        'PA666' => 'PA666',
        'PAI' => 'PAI',
        'PAT' => 'PAT',
        'PAR' => 'PAR',
        'PBT' => 'PBT',
        'PBTP' => 'PBTP',
        'PC+ABS' => 'PC+ABS',
        'PC+ASA' => 'PC+ASA',
        'PC+HIPS' => 'PC+HIPS',
        'PC+PBT' => 'PC+PBT',
        'PC+PET' => 'PC+PET',
        'PC+PS' => 'PC+PS',
        'PC+PSU' => 'PC+PSU',
        'PCTA' => 'PCTA',
        'PCTG' => 'PCTG',
        'PE' => 'PE',
        'PEK' => 'PEK',
        'PEEK' => 'PEEK',
        'PEI' => 'PEI',
        'PEI+PC' => 'PEI+PC',
        'PES' => 'PES',
        'PET' => 'PET',
        'PET+PBT' => 'PET+PBT',
        'PETG' => 'PETG',
        'PFA' => 'PFA',
        'PK' => 'PK',
        'PLA' => 'PLA',
        'PMI+PMMA' => 'PMI+PMMA',
        'PMMA' => 'PMMA',
        'PMMA+ASA' => 'PMMA+ASA',
        'PMMA+PC' => 'PMMA+PC',
        'PMP' => 'PMP',
        'POE' => 'POE',
        'POM' => 'POM',
        'PSU' => 'PSU',
        'PVC' => 'PVC',
        'PP+EPDM' => 'PP+EPDM',
        'PP+PA6' => 'PP+PA6',
        'PP+PE' => 'PP+PE',
        'PP+PPE' => 'PP+PPE',
        'PP+TPO' => 'PP+TPO',
        'PP+HT' => 'PP+HT',
        'PP+T20' => 'PP+T20',
        'PP+TD20' => 'PP+TD20',
        'PP6' => 'PP6',
        'PPA' => 'PPA',
        'PPE' => 'PPE',
        'PPE+HIPS' => 'PPE+HIPS',
        'PPE+PA' => 'PPE+PA',
        'PPE+PA6' => 'PPE+PA6',
        'PPE+PA66' => 'PPE+PA66',
        'PPE+PS' => 'PPE+PS',
        'PPE+SB' => 'PPE+SB',
        'PPO' => 'PPO',
        'PPO+PA' => 'PPO+PA',
        'PPO+PS' => 'PPO+PS',
        'PPS' => 'PPS',
        'PPSU' => 'PPSU',
        'PPV' => 'PPV',
        'PPVC' => 'PPVC',
        'PS' => 'PS',
        'PU' => 'PU',
        'SAN' => 'SAN',
        'SB' => 'SB',
        'TPE' => 'TPE',
        'TPO' => 'TPO',
        'TPU' => 'TPU',
        'TPV' => 'TPV',
        'ABS+PA6' => 'ABS+PA6',
        'ABS+PMMA' => 'ABS+PMMA',
        'PA66+PA6T' => 'PA66+PA6T',
        'PBT+ASA' => 'PBT+ASA',
        'PC+HC' => 'PC+HC',
        'PC+PMMA' => 'PC+PMMA',
        'MS' => 'MS',
        'DİĞER' => 'DİĞER',
    ],

    /*
    |--------------------------------------------------------------------------
    | Katkı Türleri (Sabit)
    |--------------------------------------------------------------------------
    */
    'additives' => [
        '0' => 'Seçilmedi',
        'Calcite' => 'Calcite',
        'Carbon Fiber' => 'Carbon Fiber',
        'Glass Fiber' => 'Glass Fiber',
        'Mineral' => 'Mineral',
        'Naturel Fiber' => 'Naturel Fiber',
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
