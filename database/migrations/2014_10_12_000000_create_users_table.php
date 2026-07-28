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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Tambahan untuk role dan relasi ke satker
            $table->enum('role', ['admin', 'satker'])->default('satker');
            $table->foreignId('satker_id')->nullable()->constrained('satkers')->onDelete('cascade');
            
            $table->rememberToken();
            $table->timestamps();
        }); 
    }       

    /**
     * Reverse the migrations.
     */
    public function down(): void // <-- Ini baris 34 di error Anda
    {
        Schema::dropIfExists('users');
    }