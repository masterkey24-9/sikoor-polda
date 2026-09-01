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
    | Ada 2 skema ambang batas yang dipakai di aplikasi ini untuk 2 keperluan
    | yang beda (jangan dihapus salah satunya walau namanya mirip):
    |
    | 1. ambang_baik / ambang_cukup — dipakai untuk kolom "Status" satker
    |    (Baik / Cukup / Perlu Perhatian) di dashboard & panel prioritas.
    |    Skor akhir >= ambang_baik                  => 'Baik'
    |    Skor akhir >= ambang_cukup (tapi < baik)    => 'Cukup'
    |    Skor akhir <  ambang_cukup                  => 'Perlu Perhatian'
    |
    | 2. ikpa_ambang_* — dipakai untuk kolom "Kategori" ala e-MONEV Kemenkeu
    |    (Sangat Baik / Baik / Cukup / Kurang), dipakai di badge warna,
    |    traffic-light indikator, dan notifikasi otomatis.
    |
    | 3. ambang_hijau / ambang_kuning — skema lama (Hijau/Kuning/Merah),
    |    dipertahankan untuk kompatibilitas kalau masih dipakai di bagian lain.
    |    Skor akhir >= ambang_hijau                  => 'Hijau'
    |    Skor akhir >= ambang_kuning (tapi < hijau)   => 'Kuning'
    |    Skor akhir <  ambang_kuning                  => 'Merah'
    |
    */

    'ambang_baik' => 85,
    'ambang_cukup' => 60,
    'ikpa_ambang_sangat_baik' => 90,
    'ikpa_ambang_baik' => 80,
    'ikpa_ambang_cukup' => 70,

    'ambang_hijau' => 95,
    'ambang_kuning' => 89,

    /*
    |--------------------------------------------------------------------------
    | Jenis indikator IKPA
    |--------------------------------------------------------------------------
    |
    | Daftar baku jenis indikator yang bisa dipilih admin saat membuat tugas
    | baru (dropdown "Pilih Indikator"). Ini SENGAJA dijadikan satu-satunya
    | sumber kebenaran ("judul" harus persis sama dengan salah satu nilai di
    | sini) karena beberapa logic di halaman Monitoring mencocokkan string ini
    | secara persis: urutan tampilan panel "Monitoring Indikator IKPA", daftar
    | "judul anggaran" untuk notifikasi deviasi, dan kolom detail di tabel
    | "Monitoring IKPA Terbaru". Kalau menambah/mengubah jenis indikator,
    | cukup ubah di sini saja.
    |
    */

    'jenis_indikator' => [
        'Revisi DIPA',
        'Deviasi Halaman III DIPA',
        'Penyerapan Anggaran',
        'Belanja Kontraktual',
        'Penyelesaian Tagihan',
        'Pengelolaan UP/TUP',
        'Dispensasi SPM',
        'Capaian Output',
    ],

];