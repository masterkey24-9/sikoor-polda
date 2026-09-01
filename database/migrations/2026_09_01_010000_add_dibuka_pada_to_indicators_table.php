<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menandai kapan satker "membuka" (otomatis dilihat di halaman Monitoring Kinerja)
     * dokumen PDF/Excel yang dikirim admin lewat sebuah indicator. Selama masih null,
     * dokumen tersebut dianggap baru dan akan otomatis ditampilkan/terbuka di halaman
     * Monitoring Kinerja satker bersangkutan.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('indicators', 'dibuka_pada')) {
            Schema::table('indicators', function (Blueprint $table) {
                $table->timestamp('dibuka_pada')->nullable()->after('file_excel');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('indicators', 'dibuka_pada')) {
            Schema::table('indicators', function (Blueprint $table) {
                $table->dropColumn('dibuka_pada');
            });
        }
    }
};
