<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migration ini ditulis idempoten karena tabel `messages` sebelumnya
     * dibuat di luar sistem migration (tidak ada file migration aslinya).
     * Jadi kita cek dulu keberadaan tabel/kolom sebelum membuat/menambah.
     */
    public function up(): void
    {
        if (! Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('satker_id')->nullable()->constrained('satkers')->onDelete('cascade');
                $table->text('pesan');
                $table->timestamps();
            });

            return;
        }

        // Tabel sudah ada sebelumnya: tambahkan kolom satker_id jika belum ada.
        if (! Schema::hasColumn('messages', 'satker_id')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->foreignId('satker_id')->nullable()->after('user_id')
                    ->constrained('satkers')->onDelete('cascade');
            });

            // Backfill data lama: pesan yang dikirim oleh user ber-role 'satker'
            // otomatis dimasukkan ke thread satker_id miliknya sendiri.
            // Pesan lama dari admin (belum ada konsep thread) dibiarkan null
            // dan perlu dicek/dikelompokkan manual jika diperlukan.
            \DB::statement('
                UPDATE messages
                INNER JOIN users ON users.id = messages.user_id
                SET messages.satker_id = users.satker_id
                WHERE users.role = "satker" AND users.satker_id IS NOT NULL
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('messages') && Schema::hasColumn('messages', 'satker_id')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('satker_id');
            });
        }
    }
};
