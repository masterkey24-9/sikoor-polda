<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peringatan_satker', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satker_id')->constrained('satkers')->onDelete('cascade');
            $table->text('pesan');
            $table->dateTime('batas_waktu');
            // 'aktif'  = masih berlaku, jadi kunci upload kalau batas_waktu sudah lewat.
            // 'selesai' = admin sudah menutup/menyelesaikan peringatan ini, satker bisa upload lagi.
            $table->string('status')->default('aktif');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peringatan_satker');
    }
};
