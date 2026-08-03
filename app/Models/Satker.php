<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Satker extends Model
{
    protected $fillable = ['nama_satker'];

    public function user()
    {
        return $this->hasOne(User::class);
    }
}