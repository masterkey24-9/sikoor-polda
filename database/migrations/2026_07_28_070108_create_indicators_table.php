<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('indicators', function (Blueprint $table) {
        $table->id();
        $table->string('judul'); // Contoh: "Laporan Kinerja Bulanan"
        $table->text('deskripsi')->nullable();
        $table->date('tenggat_waktu'); // Deadline pengumpulan
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicators');
    }
};
