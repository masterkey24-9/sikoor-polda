<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Indicator extends Model
{
<<<<<<< HEAD
    protected $fillable = ['judul', 'deskripsi', 'file_pdf', 'file_excel', 'satker_id', 'periode', 'dibuka_pada'];
=======
    protected $fillable = ['batch_id', 'judul', 'deskripsi', 'file_pdf', 'file_excel', 'satker_id', 'periode'];
>>>>>>> a1006c07f8ad677c344ec6c364a782fe2871152b

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