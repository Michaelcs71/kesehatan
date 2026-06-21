<?php

return [
    // Semua satuan menit; diubah di sini tanpa migrasi.
    'interval_ulang_menit' => env('PENGINGAT_INTERVAL_ULANG', 10),
    'wa_pasien_setelah_menit' => env('PENGINGAT_WA_PASIEN_MENIT', 30),
    'wa_pmo_setelah_menit' => env('PENGINGAT_WA_PMO_MENIT', 60),
    'batas_akhir_menit' => env('PENGINGAT_BATAS_AKHIR_MENIT', 120),

    'kanal' => [
        'web_push' => env('PENGINGAT_KANAL_PUSH', true),
        'whatsapp' => env('PENGINGAT_KANAL_WA', true),
    ],

    'aktif' => [
        'mo' => true,
        'cgd' => true,
    ],

    'cgd' => [
        // Jam pengiriman pengingat H-1 (sehari sebelum tgl_jadwal_cgd).
        'jam_h1' => env('PENGINGAT_CGD_JAM_H1', '17:00'),
    ],

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@kesehatan.test'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    'whatsapp' => [
        'driver' => env('WA_DRIVER', 'log'), // 'log' (dev) | 'cloud_api' (prod)
        'cloud_api' => [
            'token' => env('WA_CLOUD_TOKEN'),
            'phone_id' => env('WA_CLOUD_PHONE_ID'),
            'template_mo' => env('WA_TEMPLATE_MO', 'pengingat_obat'),
            'template_cgd' => env('WA_TEMPLATE_CGD', 'pengingat_cgd'),
            'lang' => env('WA_TEMPLATE_LANG', 'id'),
            'base_url' => env('WA_CLOUD_BASE_URL', 'https://graph.facebook.com/v21.0'),
        ],
    ],
];
