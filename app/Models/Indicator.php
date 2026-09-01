<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Indicator extends Model
{
    protected $fillable = ['batch_id', 'judul', 'deskripsi', 'file_pdf', 'file_excel', 'satker_id', 'periode'];

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