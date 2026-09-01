<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use App\Models\Satker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IndicatorController extends Controller
{
    /**
     * Daftar semua indicator (khusus admin), dipakai di resources/views/indicators/index.blade.php
     */
    public function index()
    {
        $indicators = Indicator::with(['satker', 'results'])->latest()->get();
        $satkers = Satker::orderBy('nama_satker')->get();
        $jenisIndikator = \App\Models\IkpaBobotIndikator::namaTerurut();
        $ringkasanIndikator = $this->ringkasanIndikatorBulanIni($indicators, $jenisIndikator);

        return view('indicators.index', compact('indicators', 'satkers', 'jenisIndikator', 'ringkasanIndikator'));
    }

    /**
     * Buat indicator baru. Form memilih beberapa satker sekaligus (satker_id[]),
     * sementara tabel indicators hanya punya satu satker_id per baris,
     * jadi kita buat satu record Indicator untuk tiap satker yang dipilih.
     *
     * "judul" WAJIB salah satu dari daftar baku di tabel ikpa_bobot_indikator
     * (dikelola lewat halaman Indikator IKPA > Pengaturan Bobot Indikator) —
     * ini dipilih lewat dropdown di form, bukan diketik bebas, supaya nilainya selalu
     * konsisten dengan yang dipakai di logic halaman Monitoring (urutan panel indikator,
     * kolom detail tabel, notifikasi deviasi anggaran, dll).
     */
    public function store(Request $request)
    {
        $jenisIndikator = \App\Models\IkpaBobotIndikator::namaTerurut();

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

        // Satu batch_id dipakai untuk semua baris Indicator yang dibuat dari submit form ini,
        // supaya bisa ditampilkan sebagai satu riwayat pengiriman di halaman Riwayat Pengiriman.
        $batchId = (string) \Illuminate\Support\Str::uuid();

        foreach ($validated['satker_id'] as $satkerId) {
            $indicator = Indicator::create([
                'batch_id' => $batchId,
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
     * Daftar riwayat pengiriman indicator: satu baris di sini = satu kali submit
     * form "Buat & kirim indicator" (dikelompokkan lewat batch_id), bukan satu baris
     * per satker. Dipakai admin untuk lihat kapan & indicator apa saja yang pernah
     * dikirim, plus ringkasan berapa satker sudah lapor.
     */
    public function riwayat()
    {
        $batches = Indicator::with('results')
            ->whereNotNull('batch_id')
            ->get()
            ->groupBy('batch_id')
            ->map(function ($rows) {
                $first = $rows->first();
                $totalSatker = $rows->count();
                $sudahLapor = $rows->filter(fn ($ind) => $ind->results->isNotEmpty())->count();

                return (object) [
                    'batch_id' => $first->batch_id,
                    'judul' => $first->judul,
                    'deskripsi' => $first->deskripsi,
                    'periode' => $first->periode,
                    'file_pdf' => $first->file_pdf,
                    'file_excel' => $first->file_excel,
                    'dikirim_pada' => $first->created_at,
                    'total_satker' => $totalSatker,
                    'sudah_lapor' => $sudahLapor,
                ];
            })
            ->sortByDesc('dikirim_pada')
            ->values();

        return view('indicators.riwayat', compact('batches'));
    }

    /**
     * Detail satu riwayat pengiriman: daftar semua satker tujuan pada batch itu,
     * lengkap dengan status laporan masing-masing (belum lapor / menunggu dinilai /
     * perlu direvisi / diterima), dipakai untuk memonitor satu pengiriman spesifik.
     */
    public function riwayatDetail(string $batchId)
    {
        $indicators = Indicator::with(['satker', 'results'])
            ->where('batch_id', $batchId)
            ->get();

        if ($indicators->isEmpty()) {
            abort(404);
        }

        $info = $indicators->first();

        $daftarSatker = $indicators
            ->map(function ($ind) {
                $latestResult = $ind->results->sortByDesc('created_at')->first();

                return (object) [
                    'indicator_id' => $ind->id,
                    'nama_satker' => $ind->satker->nama_satker ?? '-',
                    'latest_result' => $latestResult,
                ];
            })
            ->sortBy('nama_satker')
            ->values();

        return view('indicators.riwayat-detail', compact('batchId', 'info', 'daftarSatker'));
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
     * Ringkasan per jenis indikator untuk bulan berjalan, menggantikan daftar mentah
     * "Daftar indicator" yang lama. Sengaja dibuat pakai kategori & warna traffic-light
     * (Hijau/Kuning/Merah) yang SAMA PERSIS dengan panel "Monitoring Indikator IKPA"
     * di halaman dashboard (lihat routes/web.php), supaya admin lihat status yang
     * konsisten baik dari halaman ini maupun dari dashboard monitoring.
     */
    private function ringkasanIndikatorBulanIni($indicators, array $jenisIndikator)
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

        $trafficLight = function (?float $nilai) use ($kategoriIkpa) {
            return match ($kategoriIkpa($nilai)) {
                'Sangat Baik', 'Baik' => ['warna' => 'Hijau', 'kelas' => 'bg-emerald-50 text-emerald-600'],
                'Cukup' => ['warna' => 'Kuning', 'kelas' => 'bg-amber-50 text-amber-600'],
                'Kurang' => ['warna' => 'Merah', 'kelas' => 'bg-red-50 text-red-600'],
                default => ['warna' => 'Belum Ada Laporan', 'kelas' => 'bg-slate-100 text-slate-500'],
            };
        };

        return collect($jenisIndikator)->map(function ($judul) use ($indicators, $awalBulanIni, $akhirBulanIni, $trafficLight) {
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
            $tl = $trafficLight($rata);

            return [
                'judul' => $judul,
                'total_satker' => $totalSatker,
                'sudah_lapor' => $sudahLapor,
                'rata' => $rata,
                'warna' => $tl['warna'],
                'kelas' => $tl['kelas'],
            ];
        });
    }
}