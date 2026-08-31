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
    | Ambang batas kategori status kinerja
    |--------------------------------------------------------------------------
    |
    | Skor akhir >= ambang_baik      => 'Baik'
    | Skor akhir >= ambang_cukup     => 'Cukup'
    | Skor akhir <  ambang_cukup     => 'Perlu Perhatian'
    |
    */

    'ambang_baik' => 85,
    'ambang_cukup' => 60,
    'ikpa_ambang_sangat_baik' => 90,
    'ikpa_ambang_baik' => 80,
    'ikpa_ambang_cukup' => 70,

];
