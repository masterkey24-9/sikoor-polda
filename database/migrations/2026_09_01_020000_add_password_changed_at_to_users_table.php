<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Selama kolom ini masih NULL, satker dianggap belum pernah mengganti
     * password sendiri sejak akunnya dibuat/direset admin — dipakai untuk
     * memaksa ganti password di percobaan login berikutnya (lihat
     * App\Http\Middleware\EnsurePasswordIsChanged).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'password_changed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('password_changed_at')->nullable()->after('password');
            });
        }

        // Backfill: akun yang SUDAH ADA sebelum fitur ini dianggap sudah pernah
        // pakai passwordnya sendiri (pakai created_at sebagai patokan), supaya
        // satker yang sudah aktif tidak tiba-tiba dipaksa ganti password.
        // Yang benar-benar akan diminta ganti password hanya akun BARU yang
        // dibuat (atau di-reset kredensialnya) SETELAH migration ini jalan.
        DB::table('users')->whereNull('password_changed_at')->update([
            'password_changed_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'password_changed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('password_changed_at');
            });
        }
    }
};
