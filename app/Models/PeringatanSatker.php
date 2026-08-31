<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeringatanSatker extends Model
{
    protected $table = 'peringatan_satker';

    protected $fillable = ['satker_id', 'pesan', 'batas_waktu', 'status', 'dibuat_oleh'];

    protected $casts = [
        'batas_waktu' => 'datetime',
    ];

    public function satker()
    {
        return $this->belongsTo(Satker::class);
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Sudah lewat batas waktu ATAU belum? Dipakai untuk nge-kunci upload satker
     * dan untuk nentuin tampilan (Aktif / Lewat batas waktu) di halaman admin.
     */
    public function sudahLewatBatasWaktu(): bool
    {
        return $this->batas_waktu->isPast();
    }

    /**
     * Peringatan yang benar-benar mengunci upload: statusnya masih 'aktif'
     * DAN batas waktunya sudah lewat.
     */
    public function scopeMengunci($query)
    {
        return $query->where('status', 'aktif')->where('batas_waktu', '<', now());
    }

    /**
     * Semua peringatan yang masih 'aktif' (belum ditutup admin), dipakai untuk
     * running text di halaman satker — termasuk yang belum lewat batas waktu,
     * supaya satker dapat pengingat lebih awal juga, bukan cuma pas sudah telat.
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }
}
