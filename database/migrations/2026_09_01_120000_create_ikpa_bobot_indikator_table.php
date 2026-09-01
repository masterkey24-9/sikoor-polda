<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ikpa_bobot_indikator', function (Blueprint $table) {
            $table->id();
            // Kode singkat untuk indikator, mis. "IK01". Ditampilkan di tabel Pengaturan Bobot.
            $table->string('kode', 20)->unique();
            // "nama" adalah judul indikator, HARUS sama persis dengan kolom `judul` di tabel
            // indicators supaya nilai capaian bisa dicocokkan. Ini menggantikan
            // config('sikoor.jenis_indikator') sebagai satu-satunya sumber kebenaran,
            // supaya admin bisa menambah/mengubah/menghapus jenis indikator lewat UI.
            $table->string('nama')->unique();
            // Bobot indikator dalam persen (0 - 100). Total seluruh bobot idealnya 100.
            $table->decimal('bobot', 5, 2)->default(0);
            // Urutan tampil di halaman Indikator IKPA & panel Monitoring.
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ikpa_bobot_indikator');
    }
};
