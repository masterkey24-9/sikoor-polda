<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Indicator extends Model
{
    protected $fillable = ['judul', 'deskripsi', 'file_pdf', 'file_excel', 'satker_id', 'periode', 'dibuka_pada'];

    protected $casts = [
        'periode' => 'date',
        'dibuka_pada' => 'datetime',
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