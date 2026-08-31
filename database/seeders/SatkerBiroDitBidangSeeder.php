<?php

namespace Database\Seeders;

use App\Models\Satker;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SatkerBiroDitBidangSeeder extends Seeder
{
    /**
     * Daftar satker baru: Unsur Pembantu Pimpinan (Biro), Unsur Pelaksana Tugas Pokok
     * (Direktorat), dan Unsur Pengawas & Pendukung (Bidang/Yanma).
     *
     * Catatan: tabel 'satkers' cuma punya kolom 'nama_satker' (belum ada kolom kategori
     * atau deskripsi tugas), jadi pengelompokan Biro/Direktorat/Bidang di bawah ini
     * CUMA buat penanda supaya seeder-nya gampang dibaca — tidak ikut tersimpan ke
     * database. Kalau nanti perlu dikelompokkan juga di dashboard/filter, perlu
     * tambah kolom baru (migration) dulu.
     */
    public function run(): void
    {
        $daftarSatker = [
            // Unsur Pembantu Pimpinan (Biro)
            'Biro Ops',
            'Biro SDM',
            'Biro Rena',
            'Biro Logistik',

            // Unsur Pelaksana Tugas Pokok (Direktorat)
            'Ditreskrimum',
            'Ditreskrimsus',
            'Ditresnarkoba',
            'Ditlantas',
            'Ditsamapta',
            'Ditbinmas',
            'Ditintelkam',
            'Ditpolairud',
            'Ditpamobvit',

            // Unsur Pengawas & Pendukung (Bidang / Yanma)
            'Itwasda',
            'Bidang Propam',
            'Bidang Humas',
            'Bidang Dokkes',
            'Bidang Keuangan',
            'Bidang TIK',
            'Satker Yanma',
        ];

        // WAJIB diganti oleh masing-masing satker setelah login pertama kali.
        $passwordDefault = 'satker123';

        foreach ($daftarSatker as $nama) {
            // firstOrCreate: aman dijalankan berkali-kali (misal seeder ini dijalankan ulang
            // nggak sengaja), tidak akan bikin satker atau akun dobel kalau namanya sudah ada.
            $satker = Satker::firstOrCreate(['nama_satker' => $nama]);

            $emailDefault = Str::slug($nama) . '@poldasumbar.go.id';

            User::firstOrCreate(
                ['satker_id' => $satker->id],
                [
                    'name' => $nama,
                    'email' => $emailDefault,
                    'password' => $passwordDefault,
                    'role' => 'satker',
                ]
            );
        }
    }
}
