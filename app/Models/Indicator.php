<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Indicator extends Model
{
    protected $fillable = ['judul', 'deskripsi', 'tenggat_waktu', 'satker_id'];

    public function satker()
    {
        return $this->belongsTo(Satker::class);
    }
}