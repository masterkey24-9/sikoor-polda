<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('indicators', function (Blueprint $table) {
        $table->dropColumn('tenggat_waktu');
    });
}

public function down(): void
{
    Schema::table('indicators', function (Blueprint $table) {
        $table->date('tenggat_waktu')->nullable();
    });
}
};
