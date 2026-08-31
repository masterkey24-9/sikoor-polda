<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\IndicatorResult;
use App\Models\PeringatanSatker;
use App\Models\Satker;
use Illuminate\Http\Request;


class PeringatanSatkerController extends Controller
{
    /**
     * Halaman admin: form buat peringatan baru + daftar peringatan yang sudah dibuat.
     */
    public function index()
    {
        $satkerMerah = $this->satkerBerkategoriMerah();

        $peringatan = PeringatanSatker::with('satker')
            ->latest()
            ->get();

        return view('admin.peringatan', compact('satkerMerah', 'peringatan'));
    }

    /**
     * Admin buat peringatan baru untuk satu satker (harus salah satu satker
     * berkategori merah bulan ini — divalidasi ulang di server, bukan cuma
     * dibatasi lewat dropdown di form).
     */
    public function store(Request $request)
    {
        $satkerMerahIds = $this->satkerBerkategoriMerah()->pluck('id')->all();

        $validated = $request->validate([
            'satker_id' => 'required|exists:satkers,id|in:' . implode(',', $satkerMerahIds ?: [0]),
            'pesan' => 'required|string|max:500',
            'batas_waktu' => 'required|date',
        ], [
            'satker_id.in' => 'Peringatan cuma bisa dibuat untuk satker yang bulan ini berkategori merah (Perlu Perhatian / nilai < 70).',
        ]);

        $validated['status'] = 'aktif';
        $validated['dibuat_oleh'] = auth()->id();

        PeringatanSatker::create($validated);

        return redirect()->route('peringatan.index')->with('success', 'Peringatan berhasil dibuat dan akan tampil ke satker terkait.');
    }

    /**
     * Admin menutup/menyelesaikan peringatan — ini yang "membuka kunci" upload
     * satker kalau sebelumnya sempat terkunci karena batas waktu lewat.
     */
    public function selesaikan($id)
    {
        $peringatan = PeringatanSatker::findOrFail($id);
        $peringatan->update(['status' => 'selesai']);

        return redirect()->route('peringatan.index')->with('success', 'Peringatan ditandai selesai, satker bisa mengirim laporan lagi.');
    }

    public function destroy($id)
    {
        PeringatanSatker::findOrFail($id)->delete();

        return redirect()->route('peringatan.index')->with('success', 'Peringatan dihapus.');
    }

    /**
     * Satker yang bulan ini berkategori merah: status "Perlu Perhatian" (skor gabungan
     * di bawah ambang_cukup) ATAU kategori nilai IKPA "Kurang" (nilai < ikpa_ambang_cukup).
     * Logic skornya sengaja disamakan dengan yang di routes/web.php (dashboard) supaya
     * satker yang "merah" di sini = yang "merah" juga di dashboard.
     *
     * Satker yang belum punya nilai sama sekali TIDAK dihitung merah di sini (beda kasus
     * dari "nilai rendah") — kalau mau termasuk juga, tinggal ubah kondisi is_null di bawah.
     */
    private function satkerBerkategoriMerah()
    {
        $awal = now()->startOfMonth();
        $akhir = now()->endOfMonth();

        $bobotProgres = config('sikoor.bobot_progres', 0.4);
        $bobotKualitas = config('sikoor.bobot_kualitas', 0.6);
        $ambangCukup = config('sikoor.ambang_cukup', 60);
        $ikpaAmbangCukup = config('sikoor.ikpa_ambang_cukup', 70);

        return Satker::orderBy('nama_satker')->get()
            ->filter(function ($satker) use ($awal, $akhir, $bobotProgres, $bobotKualitas, $ambangCukup, $ikpaAmbangCukup) {
                $tugasQuery = Indicator::where('satker_id', $satker->id)->whereBetween('periode', [$awal, $akhir]);
                $totalTugas = (clone $tugasQuery)->count();
                $tugasSelesai = (clone $tugasQuery)->whereHas('results')->count();
                $progres = $totalTugas > 0 ? ($tugasSelesai / $totalTugas) * 100 : null;

                $rataKualitas = IndicatorResult::whereHas('indicator', function ($q) use ($satker, $awal, $akhir) {
                        $q->where('satker_id', $satker->id)->whereBetween('periode', [$awal, $akhir]);
                    })
                    ->whereNotNull('nilai')
                    ->avg('nilai');

                $skor = null;
                if (! is_null($progres) && ! is_null($rataKualitas)) {
                    $skor = ($progres * $bobotProgres) + ($rataKualitas * $bobotKualitas);
                } elseif (! is_null($progres)) {
                    $skor = $progres;
                }

                if (is_null($skor)) {
                    return false;
                }

                return $skor < $ambangCukup || $skor < $ikpaAmbangCukup;
            })
            ->values();
    }
}
