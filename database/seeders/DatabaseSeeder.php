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
            // Nonaktifkan pemeriksaan foreign key sementara
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Bersihkan tabel dulu agar tidak ada data ganda jika dijalankan berulang
            User::truncate();
            Satker::truncate();

            // Aktifkan kembali
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // 1. Buat Data Satker
            $satker1 = Satker::create(['nama_satker' => 'Polresta Padang']);
            $satker2 = Satker::create(['nama_satker' => 'Polres Bukittinggi']);

            // 2. Buat Data Admin
            User::create([
                'name' => 'Admin Polda',
                'email' => 'admin@polda.go.id',
                'password' => Hash::make('rahasia123'),
                'role' => 'admin',
                'satker_id' => null, 
            ]);

            // 3. Buat Data User untuk Satker 1
            User::create([
                'name' => 'Polresta Padang',
                'email' => 'padang@polda.go.id',
                'password' => Hash::make('rahasia123'),
                'role' => 'satker',
                'satker_id' => $satker1->id,
            ]);

            // 4. Buat Data User untuk Satker 2
            User::create([
                'name' => 'Polres Bukittinggi',
                'email' => 'bukittinggi@polda.go.id',
                'password' => Hash::make('rahasia123'),
                'role' => 'satker',
                'satker_id' => $satker2->id,
            ]);

            $this->command->info('Seeding berhasil! Data Admin dan Satker sudah dibuat.');

        } catch (\Exception $e) {
            // Jika ada error, paksa terminal untuk menampilkan pesan errornya!
            dd('ERROR TERJADI: ' . $e->getMessage() . ' di baris ' . $e->getLine());
        }
    }
}