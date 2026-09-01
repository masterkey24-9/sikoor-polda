<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Tambah kolom batch_id: dipakai untuk mengelompokkan beberapa baris Indicator
     * yang dibuat dalam satu kali submit form "Buat & kirim indicator" (1 baris per
     * satker tujuan), supaya bisa ditampilkan sebagai SATU riwayat pengiriman.
     */
    public function up(): void
    {
        Schema::table('indicators', function (Blueprint $table) {
            $table->uuid('batch_id')->nullable()->after('id')->index();
        });

        // Backfill data lama: kelompokkan baris yang judul + periode + deskripsi + file-nya
        // sama dan dibuat dalam menit yang sama sebagai satu batch, supaya riwayat pengiriman
        // lama juga langsung muncul (bukan cuma indicator yang dibuat setelah migrasi ini).
        $rows = DB::table('indicators')->whereNull('batch_id')->orderBy('id')->get();

        $groups = [];
        foreach ($rows as $row) {
            $key = implode('|', [
                $row->judul,
                (string) $row->periode,
                (string) $row->deskripsi,
                $row->file_pdf ?? '',
                $row->file_excel ?? '',
                substr((string) $row->created_at, 0, 16), // bulatkan ke menit
            ]);
            $groups[$key][] = $row->id;
        }

        foreach ($groups as $ids) {
            DB::table('indicators')->whereIn('id', $ids)->update(['batch_id' => (string) Str::uuid()]);
        }
    }

    public function down(): void
    {
        Schema::table('indicators', function (Blueprint $table) {
            $table->dropColumn('batch_id');
        });
    }
};
