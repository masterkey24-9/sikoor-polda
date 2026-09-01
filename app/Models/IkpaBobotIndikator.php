<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IkpaBobotIndikator extends Model
{
    protected $table = 'ikpa_bobot_indikator';

    protected $fillable = [
        'kode',
        'nama',
        'bobot',
        'urutan',
    ];

    protected $casts = [
        'bobot' => 'float',
        'urutan' => 'integer',
    ];

    /**
     * Daftar nama indikator terurut sesuai `urutan`, dipakai sebagai pengganti
     * config('sikoor.jenis_indikator') di seluruh aplikasi (dropdown form
     * indicator baru, panel Monitoring Indikator IKPA, dst).
     */
    public static function namaTerurut(): array
    {
        return static::orderBy('urutan')->pluck('nama')->all();
    }

    /**
     * Generate kode berikutnya secara otomatis, format "IK01", "IK02", dst.
     */
    public static function kodeBerikutnya(): string
    {
        $terakhir = static::orderByDesc('id')->value('kode');
        $angka = 1;

        if ($terakhir && preg_match('/(\d+)$/', $terakhir, $m)) {
            $angka = ((int) $m[1]) + 1;
        } else {
            $angka = static::count() + 1;
        }

        return 'IK' . str_pad((string) $angka, 2, '0', STR_PAD_LEFT);
    }
}
