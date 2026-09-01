<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndikatorBobot extends Model
{
    protected $fillable = ['judul', 'bobot'];

    protected $casts = [
        'bobot' => 'decimal:2',
    ];
}
