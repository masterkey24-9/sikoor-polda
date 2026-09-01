<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('indicator_results', 'tindak_lanjut')) {
            Schema::table('indicator_results', function (Blueprint $table) {
                $table->text('tindak_lanjut')->nullable()->after('catatan_admin');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('indicator_results', 'tindak_lanjut')) {
            Schema::table('indicator_results', function (Blueprint $table) {
                $table->dropColumn('tindak_lanjut');
            });
        }
    }
};