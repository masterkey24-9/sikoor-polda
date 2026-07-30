<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Satker extends Model
{
    public function satker()
    {
    return $this->belongsTo(Satker::class);
    }
    use HasFactory;

    protected $fillable = ['nama_satker'];
}