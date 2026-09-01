<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\IndikatorBobot;
use App\Models\Satker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IndicatorController extends Controller
{
    /**
     * Daftar semua indicator (khusus admin), dipakai di resources/views/indicators/index.blade.php.
     *
     * Kalau ada ?satker_id=X di URL (dipakai tombol "Lihat" dari Monitoring IKPA), halaman ini
     * juga nampilin daftar tugas/laporan satker itu yang bisa langsung diklik ke halaman
     * penilaian (indicators.show) — "Ringkasan Indikator Bulan Ini" di bawahnya tetap hitung
     * dari SEMUA satker, nggak ikut kefilter.
     */
    public function index()
    {
        $indicators = Indicator::with(['satker', 'results'])->latest()->get();
        $satkers = Satker::orderBy('nama_satker')->get();
        $jenisIndikator = config('sikoor.jenis_indikator', []);
        $bobotIndikator = IndikatorBobot::pluck('bobot', 'judul');
        $ringkasanIndikator = $this->ringkasanIndikatorBulanIni($indicators, $jenisIndikator, $bobotIndikator);
        $totalBobot = $bobotIndikator->sum();

        $satkerFilterAktif = null;
        $indicatorsSatkerFilter = collect();

        if (request()->filled('satker_id')) {
            $satkerFilterAktif = Satker::find(request('satker_id'));
            $indicatorsSatkerFilter = $indicators->where('satker_id', (int) request('satker_id'))->values();
        }

        return view('indicators.index', compact(
            'indicators', 'satkers', 'jenisIndikator', 'ringkasanIndikator',
            'satkerFilterAktif', 'indicatorsSatkerFilter', 'bobotIndikator', 'totalBobot'
        ));
    }

    /**
     * Simpan perubahan bobot semua indikator sekaligus (form "Pengaturan Bobot Indikator").
     * Tidak memblokir kalau totalnya bukan 100% — cuma diperingatkan di tampilan, supaya
     * admin tetap bisa nyimpen kerjaan yang belum kelar disusun.
     */
    public function updateBobot(Request $request)
    {
        $jenisIndikator = config('sikoor.jenis_indikator', []);

        $validated = $request->validate([
            'bobot' => 'required|array',
            'bobot.*' => 'required|numeric|min:0|max:100',
        ]);

        foreach ($validated['bobot'] as $judul => $nilai) {
            if (! in_array($judul, $jenisIndikator, true)) {
                continue; // abaikan kalau ada judul yang bukan dari daftar baku
            }

            IndikatorBobot::updateOrCreate(['judul' => $judul], ['bobot' => $nilai]);
        }

        return redirect()->back()->with('success', 'Bobot indikator berhasil disimpan.');
    }

    /**
     * Buat indicator baru. Form memilih beberapa satker sekaligus (satker_id[]),
     * sementara tabel indicators hanya punya satu satker_id per baris,
     * jadi kita buat satu record Indicator untuk tiap satker yang dipilih.
     *
     * "judul" WAJIB salah satu dari daftar baku di config('sikoor.jenis_indikator') —
     * ini dipilih lewat dropdown di form, bukan diketik bebas, supaya nilainya selalu
     * konsisten dengan yang dipakai di logic halaman Monitoring (urutan panel indikator,
     * kolom detail tabel, notifikasi deviasi anggaran, dll).
     */
    public function store(Request $request)
    {
        $jenisIndikator = config('sikoor.jenis_indikator', []);

        $validated = $request->validate([
            'judul' => ['required', 'string', Rule::in($jenisIndikator)],
            'deskripsi' => 'nullable|string',
            'file_pdf' => 'nullable|mimes:pdf|max:10240',
            'file_excel' => 'nullable|mimes:xlsx,xls,csv|max:10240',
            'periode' => 'nullable|date_format:Y-m',
            'satker_id' => 'required|array|min:1',
            'satker_id.*' => 'exists:satkers,id',
        ], [
            'judul.in' => 'Jenis indikator tidak valid, silakan pilih dari daftar yang tersedia.',
            'file_excel.mimes' => 'Lampiran Excel harus berformat .xlsx, .xls, atau .csv.',
        ]);

        $filePathPdf = null;
        if ($request->hasFile('file_pdf')) {
            $filePathPdf = $request->file('file_pdf')->store('uploads', 'public');
        }

        $filePathExcel = null;
        if ($request->hasFile('file_excel')) {
            $filePathExcel = $request->file('file_excel')->store('uploads', 'public');
        }

        // Default periode ke bulan berjalan kalau admin tidak mengisi,
        // supaya indicator tetap muncul saat difilter per periode di dashboard.
        $periode = $validated['periode'] ?? now()->format('Y-m');
        $periode .= '-01';

        foreach ($validated['satker_id'] as $satkerId) {
            $indicator = Indicator::create([
                'judul' => $validated['judul'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'file_pdf' => $filePathPdf,
                'file_excel' => $filePathExcel,
                'satker_id' => $satkerId,
                'periode' => $periode,
            ]);

            NotificationController::notifyNewIndicator($indicator);
        }

        return redirect()->back()->with('success', 'Indicator berhasil dibuat dan dikirim ke satker terpilih.');
    }

    /**
     * Detail satu indicator + daftar laporan yang sudah masuk dari satker,
     * dipakai di resources/views/indicators/show.blade.php untuk admin menilai.
     */
    public function show($id)
    {
        $indicator = Indicator::with(['satker', 'results.satker'])->findOrFail($id);

        return view('indicators.show', compact('indicator'));
    }

    /**
     * Ringkasan per jenis indikator untuk bulan berjalan, dipakai di panel kartu
     * "Indikator IKPA". Kategori nilainya (Sangat Baik/Baik/Cukup/Kurang) SAMA PERSIS
     * ambang batasnya dengan panel "Monitoring Indikator IKPA" di dashboard — cuma
     * wording status di kartu ini pakai istilah "Sesuai target / Perlu perhatian /
     * Perlu tindak lanjut segera" (bukan Hijau/Kuning/Merah) sesuai desain kartu.
     */
    private function ringkasanIndikatorBulanIni($indicators, array $jenisIndikator, $bobotIndikator = null)
    {
        $awalBulanIni = now()->startOfMonth();
        $akhirBulanIni = now()->endOfMonth();

        $kategoriIkpa = function (?float $nilai) {
            if (is_null($nilai)) {
                return 'Belum Dinilai';
            }
            if ($nilai >= config('sikoor.ikpa_ambang_sangat_baik', 90)) {
                return 'Sangat Baik';
            }
            if ($nilai >= config('sikoor.ikpa_ambang_baik', 80)) {
                return 'Baik';
            }
            if ($nilai >= config('sikoor.ikpa_ambang_cukup', 70)) {
                return 'Cukup';
            }
            return 'Kurang';
        };

        $statusKartu = function (?float $nilai) use ($kategoriIkpa) {
            return match ($kategoriIkpa($nilai)) {
                'Sangat Baik', 'Baik' => ['label' => 'Sesuai target', 'kelas_teks' => 'text-emerald-600', 'kelas_bar' => 'bg-emerald-500', 'icon' => 'ti-circle-check'],
                'Cukup' => ['label' => 'Perlu perhatian', 'kelas_teks' => 'text-amber-600', 'kelas_bar' => 'bg-amber-500', 'icon' => 'ti-alert-triangle'],
                'Kurang' => ['label' => 'Perlu tindak lanjut segera', 'kelas_teks' => 'text-red-600', 'kelas_bar' => 'bg-red-500', 'icon' => 'ti-circle-x'],
                default => ['label' => 'Belum ada laporan', 'kelas_teks' => 'text-slate-400', 'kelas_bar' => 'bg-slate-300', 'icon' => 'ti-minus-circle'],
            };
        };

        return collect($jenisIndikator)->map(function ($judul) use ($indicators, $awalBulanIni, $akhirBulanIni, $statusKartu, $bobotIndikator) {
            $tugasBulanIni = $indicators->filter(fn ($ind) => $ind->judul === $judul
                && $ind->periode
                && $ind->periode->between($awalBulanIni, $akhirBulanIni));

            $totalSatker = $tugasBulanIni->count();
            $sudahLapor = $tugasBulanIni->filter(fn ($ind) => $ind->results->isNotEmpty())->count();

            $nilaiList = $tugasBulanIni
                ->flatMap(fn ($ind) => $ind->results)
                ->pluck('nilai')
                ->filter(fn ($n) => ! is_null($n));

            $rata = $nilaiList->isNotEmpty() ? round((float) $nilaiList->avg(), 2) : null;
            $status = $statusKartu($rata);

            return [
                'judul' => $judul,
                'bobot' => $bobotIndikator ? (float) ($bobotIndikator[$judul] ?? 0) : 0,
                'total_satker' => $totalSatker,
                'sudah_lapor' => $sudahLapor,
                'rata' => $rata,
                'status_label' => $status['label'],
                'kelas_teks' => $status['kelas_teks'],
                'kelas_bar' => $status['kelas_bar'],
                'icon' => $status['icon'],
            ];
        });
    }
}