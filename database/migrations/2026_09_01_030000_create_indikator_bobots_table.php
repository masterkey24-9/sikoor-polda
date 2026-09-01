<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indikator_bobots', function (Blueprint $table) {
            $table->id();
            $table->string('judul')->unique();
            $table->decimal('bobot', 5, 2)->default(0);
            $table->timestamps();
        });

        // Bobot default (total 100%) — bisa diubah admin nanti lewat halaman
        // "Pengaturan Bobot Indikator".
        $default = [
            'Revisi DIPA' => 10,
            'Deviasi Halaman III DIPA' => 10,
            'Penyerapan Anggaran' => 20,
            'Belanja Kontraktual' => 10,
            'Penyelesaian Tagihan' => 15,
            'Pengelolaan UP/TUP' => 10,
            'Dispensasi SPM' => 10,
            'Capaian Output' => 15,
        ];

        foreach ($default as $judul => $bobot) {
            DB::table('indikator_bobots')->insert([
                'judul' => $judul,
                'bobot' => $bobot,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('indikator_bobots');
    }
};
