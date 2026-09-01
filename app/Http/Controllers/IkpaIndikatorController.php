<?php

namespace App\Http\Controllers;

use App\Models\IkpaBobotIndikator;
use App\Models\IndicatorResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class IkpaIndikatorController extends Controller
{
    /**
     * Halaman "Indikator IKPA": kartu rata-rata capaian tiap indikator untuk
     * periode berjalan (atau periode yang dipilih lewat ?periode=Y-m), plus
     * panel "Pengaturan Bobot Indikator" untuk kelola daftar indikator.
     */
    public function index(Request $request)
    {
        $periodeFilter = $request->query('periode') ?: now()->format('Y-m');
        $periodeAktif = \Carbon\Carbon::createFromFormat('Y-m', $periodeFilter);
        $awal = $periodeAktif->copy()->startOfMonth();
        $akhir = $periodeAktif->copy()->endOfMonth();

        $daftarBobot = IkpaBobotIndikator::orderBy('urutan')->get();

        // Rata-rata nilai (capaian %) per judul indikator, dari laporan yang
        // sudah dinilai admin, dibatasi ke periode yang sedang dilihat.
        $rataPerJudul = IndicatorResult::whereNotNull('indicator_results.nilai')
            ->join('indicators', 'indicator_results.indicator_id', '=', 'indicators.id')
            ->whereBetween('indicators.periode', [$awal, $akhir])
            ->select('indicators.judul', DB::raw('AVG(indicator_results.nilai) as rata'))
            ->groupBy('indicators.judul')
            ->pluck('rata', 'judul');

        // Ambang "capaian aman" per indikator: di bawah ini dianggap perlu
        // tindak lanjut segera. Dipakai murni untuk tampilan kartu di halaman ini.
        $ambangAman = (float) config('sikoor.ikpa_ambang_cukup', 70);

        $kartuIndikator = $daftarBobot->map(function ($item) use ($rataPerJudul, $ambangAman) {
            $rata = isset($rataPerJudul[$item->nama]) ? round((float) $rataPerJudul[$item->nama], 2) : null;
            $amanTarget = ! is_null($rata) && $rata >= $ambangAman;

            return [
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
                'bobot' => $item->bobot,
                'rata' => $rata,
                'aman' => $amanTarget,
                'kelas_bar' => is_null($rata) ? 'bg-slate-200' : ($amanTarget ? 'bg-emerald-500' : 'bg-red-500'),
                'status_label' => is_null($rata)
                    ? 'Belum ada laporan'
                    : ($amanTarget ? 'Sesuai target' : 'Perlu tindak lanjut segera'),
                'status_kelas' => is_null($rata)
                    ? 'text-slate-400'
                    : ($amanTarget ? 'text-emerald-600' : 'text-red-500'),
            ];
        });

        $totalBobot = $daftarBobot->sum('bobot');

        return view('admin.ikpa-indikator', [
            'kartuIndikator' => $kartuIndikator,
            'daftarBobot' => $daftarBobot,
            'totalBobot' => $totalBobot,
            'periodeAktif' => $periodeAktif,
        ]);
    }

    /**
     * Tambah indikator IKPA baru (kartu baru). Kode dibuat otomatis kalau
     * tidak diisi supaya admin cukup mengisi nama & bobot.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150', Rule::unique('ikpa_bobot_indikator', 'nama')],
            'bobot' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'nama.unique' => 'Indikator dengan nama tersebut sudah ada.',
        ]);

        IkpaBobotIndikator::create([
            'kode' => IkpaBobotIndikator::kodeBerikutnya(),
            'nama' => $validated['nama'],
            'bobot' => $validated['bobot'],
            'urutan' => (int) IkpaBobotIndikator::max('urutan') + 1,
        ]);

        return redirect()->route('ikpa-indikator.index')->with('success', 'Indikator baru berhasil ditambahkan.');
    }

    /**
     * Ubah nama/bobot indikator yang sudah ada dari panel Pengaturan Bobot.
     */
    public function update(Request $request, IkpaBobotIndikator $ikpaIndikator)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150', Rule::unique('ikpa_bobot_indikator', 'nama')->ignore($ikpaIndikator->id)],
            'bobot' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $ikpaIndikator->update($validated);

        return redirect()->route('ikpa-indikator.index')->with('success', 'Indikator berhasil diperbarui.');
    }

    /**
     * Hapus indikator dari daftar baku. Laporan lama dengan judul ini tetap
     * tersimpan apa adanya, hanya tidak lagi muncul di dropdown/kartu.
     */
    public function destroy(IkpaBobotIndikator $ikpaIndikator)
    {
        $ikpaIndikator->delete();

        return redirect()->route('ikpa-indikator.index')->with('success', 'Indikator berhasil dihapus.');
    }
}
