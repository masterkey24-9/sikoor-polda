<?php

namespace Database\Seeders;

use App\Models\Satker;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 0. Seed 8 indikator IKPA baku beserta bobotnya
        $this->call(IkpaBobotIndikatorSeeder::class);

        // 1. Buat daftar satker (aman dijalankan berkali-kali, tidak akan dobel)
        $daftarSatker = [
            'Polresta Padang',
            'Polres Bukittinggi',
            'Polres Payakumbuh',
            'Polres Agam',
            'Polres Solok',
            'Polres Tanah Datar',
            'Polres Lima Puluh Kota',
            'Polres Sijunjung',
            'Polres Dharmasraya',
            'Polres Pasaman',
            'Polres Pasaman Barat',
            'Polres Solok Selatan',
            'Polres Pesisir Selatan',
            'Polres Kepulauan Mentawai',
            'Polres Padang Pariaman',
            'Polres Pariaman',
            'Polres Sawahlunto',
            'Polres Padang Panjang',
        ];

        foreach ($daftarSatker as $nama) {
            Satker::updateOrCreate(['nama_satker' => $nama]);
        }

        // 2. Buat akun admin
        User::updateOrCreate(
            ['email' => 'admin@polda.go.id'],
            [
                'name' => 'Admin Polda',
                'password' => 'admin123',
                'role' => 'admin',
                'satker_id' => null,
            ]
        );

        // 3. Buat 1 akun user untuk tiap satker (email dibuat otomatis dari nama satker)
        $satkers = Satker::all();

        foreach ($satkers as $satker) {
            $slug = str()->slug($satker->nama_satker); // contoh: "polresta-padang"

            User::updateOrCreate(
                ['email' => $slug . '@polda.go.id'],
                [
                    'name' => $satker->nama_satker,
                    'password' =>'rahasia123',
                    'role' => 'satker',
                    'satker_id' => $satker->id,
                ]
            );
        }
    }
}