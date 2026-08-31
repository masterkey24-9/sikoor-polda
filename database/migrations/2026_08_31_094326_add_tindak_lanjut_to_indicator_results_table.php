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
        Schema::table('indicator_results', function (Blueprint $table) {
            // Catatan tindak lanjut per laporan/indicator. Diisi awal dengan
            // saran otomatis (berdasarkan warna kategori nilai), tapi admin
            // bebas mengubah/menambah isinya sebelum disimpan.
            $table->text('tindak_lanjut')->nullable()->after('catatan_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indicator_results', function (Blueprint $table) {
            $table->dropColumn('tindak_lanjut');
        });
    }
};
