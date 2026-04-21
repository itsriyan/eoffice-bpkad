<?php
return [
    'arsip_api_url' => env('E_OFFICE_ARSIP_API_URL', 'https://arsip.example.com'),
    'arsip_token' => env('E_OFFICE_ARSIP_TOKEN', ''),
    // WhatsApp – Fonnte API configuration
    'whatsapp' => [
        'api_url' => env('E_OFFICE_WA_API_URL', 'https://api.fonnte.com'),
        'token' => env('E_OFFICE_WA_TOKEN', ''),
        'timeout' => env('E_OFFICE_WA_TIMEOUT', 10),
        // Device token used to verify incoming Fonnte webhook payloads (optional)
        'verify_token' => env('E_OFFICE_WA_VERIFY_TOKEN', 'change-me'),
        'rate_limit' => [
            'switch_per_minute' => env('E_OFFICE_WA_RATE_SWITCH', 12),
            'help_per_minute' => env('E_OFFICE_WA_RATE_HELP', 6),
            'claim_per_minute' => env('E_OFFICE_WA_RATE_CLAIM', 20),
            'note_per_minute' => env('E_OFFICE_WA_RATE_NOTE', 30),
        ],
        // Teks template pengganti WABA template (Fonnte menggunakan WA reguler)
        'templates' => [
            'surat_masuk_baru' => [
                'text' => "*Surat Masuk Baru*\n\nNo. Surat : {var0}\nPengirim  : {var1}\nPerihal   : {var2}\nTanggal   : {var3}\nLink      : {var4}\n\nSilakan pilih tindakan:",
                'buttons' => [
                    ['id' => 'DISPOSISI', 'title' => 'Disposisi'],
                    ['id' => 'ARSIPKAN',  'title' => 'Arsipkan'],
                    ['id' => 'TOLAK',     'title' => 'Tolak'],
                ],
            ],
        ],
    ],
];