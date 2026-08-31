<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bobot rumus kinerja satker
    |--------------------------------------------------------------------------
    |
    | Skor akhir kinerja satker = (bobot_progres * progres_pengumpulan)
    |                            + (bobot_kualitas * rata_rata_nilai_admin)
    |
    | - progres_pengumpulan: persentase tugas (indicator) yang sudah dikirim
    |   laporannya oleh satker, dari total tugas yang ditugaskan.
    | - rata_rata_nilai_admin: rata-rata kolom `nilai` (0-100) yang diisi admin
    |   saat menilai laporan, hanya dari laporan yang SUDAH dinilai.
    |
    | Kedua bobot ini WAJIB berjumlah 1.0 (100%). Silakan sesuaikan sesuai
    | kebijakan penilaian kinerja di Polda Anda.
    |
    */

    'bobot_progres' => 0.4,
    'bobot_kualitas' => 0.6,

    /*
    |--------------------------------------------------------------------------
    | Ambang batas kategori warna kinerja satker
    |--------------------------------------------------------------------------
    |
    | Skor akhir >= ambang_hijau                    => 'Hijau'
    | Skor akhir >= ambang_kuning (tapi < hijau)     => 'Kuning'
    | Skor akhir <  ambang_kuning                    => 'Merah'
    |
    */

    'ambang_hijau' => 95,
    'ambang_kuning' => 89,

];
