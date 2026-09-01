<?php

namespace Database\Seeders;

use App\Models\IkpaBobotIndikator;
use Illuminate\Database\Seeder;

class IkpaBobotIndikatorSeeder extends Seeder
{
    /**
     * Seed 8 indikator IKPA baku beserta bobot resminya (total 100%).
     * Aman dijalankan berkali-kali (updateOrCreate berdasarkan nama).
     */
    public function run(): void
    {
        $daftar = [
            ['kode' => 'IK01', 'nama' => 'Revisi DIPA', 'bobot' => 10, 'urutan' => 1],
            ['kode' => 'IK02', 'nama' => 'Deviasi Halaman III DIPA', 'bobot' => 10, 'urutan' => 2],
            ['kode' => 'IK03', 'nama' => 'Penyerapan Anggaran', 'bobot' => 20, 'urutan' => 3],
            ['kode' => 'IK04', 'nama' => 'Belanja Kontraktual', 'bobot' => 10, 'urutan' => 4],
            ['kode' => 'IK05', 'nama' => 'Penyelesaian Tagihan', 'bobot' => 15, 'urutan' => 5],
            ['kode' => 'IK06', 'nama' => 'Pengelolaan UP/TUP', 'bobot' => 10, 'urutan' => 6],
            ['kode' => 'IK07', 'nama' => 'Dispensasi SPM', 'bobot' => 10, 'urutan' => 7],
            ['kode' => 'IK08', 'nama' => 'Capaian Output', 'bobot' => 15, 'urutan' => 8],
        ];

        foreach ($daftar as $item) {
            IkpaBobotIndikator::updateOrCreate(
                ['nama' => $item['nama']],
                $item
            );
        }
    }
}
