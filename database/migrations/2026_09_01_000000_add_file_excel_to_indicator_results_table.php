<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pakai raw SQL (bukan ->nullable()->change()) supaya tidak perlu tambah
     * dependency doctrine/dbal cuma untuk migration ini.
     */
    public function up(): void
    {
        // file_pdf tadinya WAJIB diisi (satu-satunya format laporan). Sekarang laporan
        // boleh Excel-only, jadi file_pdf perlu jadi nullable juga.
        DB::statement('ALTER TABLE indicator_results MODIFY file_pdf VARCHAR(255) NULL');

        if (! Schema::hasColumn('indicator_results', 'file_excel')) {
            DB::statement('ALTER TABLE indicator_results ADD COLUMN file_excel VARCHAR(255) NULL AFTER file_pdf');
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE indicator_results DROP COLUMN file_excel');
        DB::statement('ALTER TABLE indicator_results MODIFY file_pdf VARCHAR(255) NOT NULL');
    }
};
