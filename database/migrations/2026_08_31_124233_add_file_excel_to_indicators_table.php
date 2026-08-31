<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dicek dulu supaya migration ini aman dijalankan berkali-kali (idempoten) —
        // kalau kolomnya udah ada (misal pernah ditambah manual), migration ini tidak
        // akan error, cuma dilewati.
        if (! Schema::hasColumn('indicators', 'file_excel')) {
            Schema::table('indicators', function (Blueprint $table) {
                $table->string('file_excel')->nullable()->after('file_pdf');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('indicators', 'file_excel')) {
            Schema::table('indicators', function (Blueprint $table) {
                $table->dropColumn('file_excel');
            });
        }
    }
};