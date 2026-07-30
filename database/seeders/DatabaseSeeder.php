<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Satker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            User::truncate();
            Satker::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // 1. Data Satker Polda Sumbar
            $daftarSatker = [
                'Polresta Padang', 'Polres Bukittinggi', 'Polres Payakumbuh',
                'Polres Agam', 'Polres Dharmasraya', 'Polres Kep. Mentawai',
                'Polres Lima Puluh Kota', 'Polres Padang Panjang', 'Polres Padang Pariaman',
                'Polres Pariaman', 'Polres Pasaman', 'Polres Pasaman Barat',
                'Polres Pesisir Selatan', 'Polres Sawahlunto', 'Polres Sijunjung',
                'Polres Solok', 'Polres Solok Kota', 'Polres Solok Selatan', 'Polres Tanah Datar'
            ];

            // 2. Buat Admin Utama
            User::create([
                'name' => 'Admin Polda',
                'email' => 'admin@polda.go.id',
                'password' => Hash::make('rahasia123'),
                'role' => 'admin',
                'satker_id' => null, 
            ]);

            // 3. Loop untuk membuat Satker dan User-nya secara otomatis
            foreach ($daftarSatker as $nama) {
                // Buat Satker
                $satker = Satker::create(['nama_satker' => $nama]);

                // Buat User untuk Satker tersebut
                // Kita buat email berdasarkan nama satker (contoh: agam@polda.go.id)
                $email = strtolower(str_replace(' ', '', $nama)) . '@polda.go.id';

                User::create([
                    'name' => $nama,
                    'email' => $email,
                    'password' => Hash::make('rahasia123'),
                    'role' => 'satker',
                    'satker_id' => $satker->id,
                ]);
            }

            $this->command->info('Seeding berhasil! Semua Polres sudah didaftarkan.');

        } catch (\Exception $e) {
            dd('ERROR TERJADI: ' . $e->getMessage() . ' di baris ' . $e->getLine());
        }
    }
}