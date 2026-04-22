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
            'help_per_minute'   => env('E_OFFICE_WA_RATE_HELP', 6),
            'claim_per_minute'  => env('E_OFFICE_WA_RATE_CLAIM', 20),
            'note_per_minute'   => env('E_OFFICE_WA_RATE_NOTE', 30),
        ],
        // Anti-spam: abaikan pesan teks yang sama dari nomor yang sama dalam window ini (detik)
        'debounce_seconds'     => env('E_OFFICE_WA_DEBOUNCE_SECONDS', 5),
        // Anti-spam: simpan inboxid yang sudah diproses selama N detik untuk mencegah duplikat
        'dedup_ttl_seconds'    => env('E_OFFICE_WA_DEDUP_TTL', 60),
        // Teks template notifikasi surat masuk (Fonnte teks biasa)
        // TIDAK mengandung menu tindakan – pimpinan harus ketik DAFTAR untuk mulai.
        'templates' => [
            'surat_masuk_baru' => [
                'text' => "*Surat Masuk Baru* 📩\n\nNo. Surat : {var0}\nPengirim  : {var1}\nPerihal   : {var2}\nTanggal   : {var3}\nLink      : {var4}\n\nKetik *DAFTAR* untuk melihat semua surat pending dan memilih tindakan.",
            ],
        ],
    ],
];
