<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Denda Keterlambatan
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk denda keterlambatan pembayaran cicilan.
    |
    */

    // Enable/disable denda
    'denda_enabled' => env('DENDA_ENABLED', true),

    // Persentase denda per hari (dalam desimal)
    // Default: 0.1% per hari = 0.001
    'denda_per_hari' => env('DENDA_PER_HARI', 0.001),

    // Maksimal denda (dalam persen dari jumlah cicilan)
    // Default: 10% (jika telat 100 hari, max denda 10% dari cicilan)
    'denda_max_persen' => env('DENDA_MAX_PERSEN', 10),

    /*
    |--------------------------------------------------------------------------
    | Reminder Cicilan
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk reminder pembayaran cicilan.
    |
    */

    // Kirim reminder berapa hari sebelum jatuh tempo
    'reminder_hari_sebelum' => env('REMINDER_HARI_SEBELUM', 3),

    // Waktu eksekusi cron job (format: HH:MM)
    'reminder_waktu' => env('REMINDER_WAKTU', '08:00'),

    /*
    |--------------------------------------------------------------------------
    | Cron Job Cicilan Telat
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk cron job cek cicilan telat.
    |
    */

    // Waktu eksekusi cron job cek telat (format: HH:MM)
    'check_telat_waktu' => env('CHECK_TELAT_WAKTU', '00:00'),

    /*
    |--------------------------------------------------------------------------
    | Metode Pembayaran
    |--------------------------------------------------------------------------
    |
    | Metode pembayaran yang tersedia.
    |
    */

    'metode_bayar' => [
        'tunai' => 'Tunai',
        'transfer' => 'Transfer Bank',
        'lainnya' => 'Lainnya',
    ],

];
