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

    /**
     * Saran tindak lanjut otomatis berdasarkan kategori warna nilai
     * (pakai ambang batas yang sama dengan kinerja satker di config/sikoor.php).
     * Ini cuma titik awal; admin tetap bebas mengubah/menambah isinya.
     */
    public static function suggestTindakLanjut(?int $nilai): ?string
    {
        if (is_null($nilai)) {
            return null;
        }

        $ambangHijau = config('sikoor.ambang_hijau', 95);
        $ambangKuning = config('sikoor.ambang_kuning', 89);

        if ($nilai >= $ambangHijau) {
            return 'Kinerja sangat baik. Pertahankan konsistensi ketepatan waktu dan kualitas laporan pada periode berikutnya.';
        }

        if ($nilai >= $ambangKuning) {
            return 'Kinerja cukup baik, namun masih ada ruang perbaikan. Mohon lengkapi/perbaiki bagian yang kurang pada laporan berikutnya.';
        }

        return 'Kinerja perlu tindak lanjut segera. Koordinasikan dengan satker terkait untuk evaluasi dan pendampingan.';
    }
}