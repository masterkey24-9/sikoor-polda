<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicatorResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'indicator_id',
        'satker_id',
        'file_pdf',
        'file_excel',
        'status',
        'catatan_admin',
        'tindak_lanjut',
        'nilai',
    ];

    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }

    public function satker()
    {
        return $this->belongsTo(Satker::class);
    }
}