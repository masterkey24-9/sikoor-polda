<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    // Kolom yang diizinkan untuk diisi secara massal
    protected $fillable = ['user_id', 'satker_id', 'pesan'];

    // Relasi: Sebuah pesan dimiliki oleh satu user (bisa Admin atau Satker)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: Sebuah pesan termasuk dalam thread percakapan satu Satker tertentu
    public function satker()
    {
        return $this->belongsTo(Satker::class);
    }
}