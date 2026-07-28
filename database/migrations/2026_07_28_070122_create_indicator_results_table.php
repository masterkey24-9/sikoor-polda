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
    Schema::create('indicator_results', function (Blueprint $table) {
        $table->id();
        $table->foreignId('indicator_id')->constrained('indicators')->onDelete('cascade');
        $table->foreignId('satker_id')->constrained('satkers')->onDelete('cascade');
        $table->string('file_pdf'); // Path tempat file PDF disimpan
        $table->enum('status', ['dikirim', 'direvisi', 'diterima'])->default('dikirim');
        $table->text('catatan_admin')->nullable(); // Feedback dari admin
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicator_results');
    }
};
