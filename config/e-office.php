<?php
return [
    'arsip_api_url' => env('E_OFFICE_ARSIP_API_URL', 'https://arsip.example.com'),
    'arsip_token' => env('E_OFFICE_ARSIP_TOKEN', ''),
    // WhatsApp Meta API configuration
    'whatsapp' => [
        'base_url' => env('E_OFFICE_WA_BASE_URL', 'https://graph.facebook.com/v24.0'),
        'phone_number_id' => env('E_OFFICE_WA_PHONE_NUMBER_ID', ''),
        'access_token' => env('E_OFFICE_WA_ACCESS_TOKEN', ''),
        'default_language' => env('E_OFFICE_WA_DEFAULT_LANG', 'id'),
        'default_template' => env('E_OFFICE_WA_DEFAULT_TEMPLATE', 'surat_masuk_baru'),
        'timeout' => env('E_OFFICE_WA_TIMEOUT', 10),
        'verify_token' => env('E_OFFICE_WA_VERIFY_TOKEN', 'change-me'),
        'rate_limit' => [
            'switch_per_minute' => env('E_OFFICE_WA_RATE_SWITCH', 12),
            'help_per_minute' => env('E_OFFICE_WA_RATE_HELP', 6),
            'claim_per_minute' => env('E_OFFICE_WA_RATE_CLAIM', 20),
            'note_per_minute' => env('E_OFFICE_WA_RATE_NOTE', 30),
        ],
    ],
];