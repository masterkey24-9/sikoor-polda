<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Indicator extends Model
{
    protected $fillable = ['judul', 'deskripsi', 'file_pdf', 'satker_id', 'periode'];

    protected $casts = [
        'periode' => 'date',
    ];

    public function satker()
    {
        return $this->belongsTo(Satker::class);
    }

    public function results()
    {
        return $this->hasMany(IndicatorResult::class);
    }
}