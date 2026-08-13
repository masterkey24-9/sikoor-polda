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
            // Nilai capaian dalam persen (0-100), diisi admin saat menilai laporan satker
            $table->unsignedTinyInteger('nilai')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indicator_results', function (Blueprint $table) {
            $table->dropColumn('nilai');
        });
    }
};