<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IkpaIndikatorController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\IndicatorResultController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SatkerController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }

    return view('landing');
});

Route::get('/dashboard', function () {
    if (auth()->user()->role === 'admin') {
        // ================= PERIODE FILTER (multi-granularitas) =================
        // Granularitas: bulanan (default) | triwulan | semester | tahunan
        $granularitas = in_array(request('granularitas'), ['bulanan', 'triwulan', 'semester', 'tahunan'])
            ? request('granularitas')
            : 'bulanan';
    
        
        $periodeFilter = request('periode') ?: now()->format('Y-m');
        $periodeAktif  = \Carbon\Carbon::createFromFormat('Y-m', $periodeFilter);
    
        $tahunAktif     = (int) (request('tahun') ?: now()->year);
        $triwulanAktif  = (int) (request('triwulan') ?: ceil(now()->month / 3));
        $semesterAktif  = (int) (request('semester') ?: (now()->month <= 6 ? 1 : 2));
    
        // Tentukan rentang tanggal (awal-akhir) periode aktif sesuai granularitas yang dipilih.
        switch ($granularitas) {
            case 'triwulan':
                $bulanAwalTriwulan = ($triwulanAktif - 1) * 3 + 1;
                $rangeAwal  = \Carbon\Carbon::create($tahunAktif, $bulanAwalTriwulan, 1)->startOfMonth();
                $rangeAkhir = $rangeAwal->copy()->addMonths(2)->endOfMonth();
                $labelPeriodeAktif = "Triwulan {$triwulanAktif} {$tahunAktif}";
                break;
    
            case 'semester':
                $bulanAwalSemester = $semesterAktif === 1 ? 1 : 7;
                $rangeAwal  = \Carbon\Carbon::create($tahunAktif, $bulanAwalSemester, 1)->startOfMonth();
                $rangeAkhir = $rangeAwal->copy()->addMonths(5)->endOfMonth();
                $labelPeriodeAktif = "Semester {$semesterAktif} {$tahunAktif}";
                break;
    
            case 'tahunan':
                $rangeAwal  = \Carbon\Carbon::create($tahunAktif, 1, 1)->startOfYear();
                $rangeAkhir = \Carbon\Carbon::create($tahunAktif, 12, 31)->endOfYear();
                $labelPeriodeAktif = "Tahun {$tahunAktif}";
                break;
    
            case 'bulanan':
            default:
                $rangeAwal  = $periodeAktif->copy()->startOfMonth();
                $rangeAkhir = $periodeAktif->copy()->endOfMonth();
                $labelPeriodeAktif = $periodeAktif->translatedFormat('F Y');
                break;
        }
    
        
        // atau 12 Bulan Terakhir. Untuk mode triwulan/semester/tahunan, jumlah titik tren
        // tetap mengikuti default masing-masing (kurang relevan dipotong ke satuan bulan).
        $trendRange = (int) request('trend_range', 6);
        if (! in_array($trendRange, [6, 12], true)) {
            $trendRange = 6;
        }
    
        $jumlahTitikTren = $granularitas === 'bulanan'
            ? $trendRange
            : ['triwulan' => 4, 'semester' => 4, 'tahunan' => 5][$granularitas];
        $panjangBulanPeriode = ['bulanan' => 1, 'triwulan' => 3, 'semester' => 6, 'tahunan' => 12][$granularitas];
    
        $query = \App\Models\Indicator::with(['satker', 'results'])
            ->whereBetween('periode', [$rangeAwal, $rangeAkhir]);
    
        if (request()->filled('satker_id')) {
            $query->where('satker_id', request('satker_id'));
        }
    
        $indicators = $query->latest()
            ->get()
            ->map(function ($item) {
                $item->satker_nama = $item->satker->nama_satker ?? '-';
                $item->status = $item->results->count() > 0 ? 'terkirim' : 'pending';
                return $item;
            });
    
        
        $judulDetailTabel = ['Penyerapan Anggaran', 'Deviasi Halaman III DIPA', 'Penyelesaian Tagihan', 'Belanja Kontraktual', 'Pengelolaan UP/TUP'];
        $detailKosong = array_fill_keys($judulDetailTabel, null);
    
        $detailIndikatorPerSatker = [];
        foreach ($indicators as $ind) {
            if (! in_array($ind->judul, $judulDetailTabel, true)) {
                continue;
            }
            $latestResult = $ind->results->sortByDesc('created_at')->first();
            $detailIndikatorPerSatker[$ind->satker_id][$ind->judul] =
                ($latestResult && ! is_null($latestResult->nilai)) ? (float) $latestResult->nilai : null;
        }
    
        $satkers = \App\Models\Satker::orderBy('nama_satker')->get();
    
        
        $kategoriIkpa = function (?float $nilai) {
            if (is_null($nilai)) {
                return ['label' => 'Belum Dinilai', 'badge' => 'bg-slate-100 text-slate-500'];
            }
            if ($nilai >= config('sikoor.ikpa_ambang_sangat_baik', 90)) {
                return ['label' => 'Sangat Baik', 'badge' => 'bg-emerald-50 text-emerald-600'];
            }
            if ($nilai >= config('sikoor.ikpa_ambang_baik', 80)) {
                return ['label' => 'Baik', 'badge' => 'bg-blue-50 text-blue-600'];
            }
            if ($nilai >= config('sikoor.ikpa_ambang_cukup', 70)) {
                return ['label' => 'Cukup', 'badge' => 'bg-amber-50 text-amber-600'];
            }
            return ['label' => 'Kurang', 'badge' => 'bg-red-50 text-red-600'];
        };
    
        
        $hitungSkorSatker = function (int $satkerId, \Carbon\Carbon $awal, \Carbon\Carbon $akhir) {
            $tugasQuery = \App\Models\Indicator::where('satker_id', $satkerId)
                ->whereBetween('periode', [$awal, $akhir]);
    
            $totalTugas = (clone $tugasQuery)->count();
            $tugasSelesai = (clone $tugasQuery)->whereHas('results')->count();
            $progres = $totalTugas > 0 ? ($tugasSelesai / $totalTugas) * 100 : null;
    
            $rataKualitas = \App\Models\IndicatorResult::whereHas('indicator', function ($q) use ($satkerId, $awal, $akhir) {
                    $q->where('satker_id', $satkerId)->whereBetween('periode', [$awal, $akhir]);
                })
                ->whereNotNull('nilai')
                ->avg('nilai');
    
            $bobotProgres = config('sikoor.bobot_progres', 0.4);
            $bobotKualitas = config('sikoor.bobot_kualitas', 0.6);
    
            $skor = null;
            if (! is_null($progres) && ! is_null($rataKualitas)) {
                $skor = round(($progres * $bobotProgres) + ($rataKualitas * $bobotKualitas), 1);
            } elseif (! is_null($progres)) {
                
                $skor = round($progres, 1);
            }
    
            return ['skor' => $skor, 'total_tugas' => $totalTugas, 'tugas_selesai' => $tugasSelesai];
        };
    
        
        $rangeAwalSebelumnya = $rangeAwal->copy()->subMonths($panjangBulanPeriode);
        $rangeAkhirSebelumnya = $rangeAwal->copy()->subDay()->endOfDay();
    
        
        $prioritasPembinaan = function (string $kategoriLabel, ?float $skorAkhir, ?float $skorSebelumnya) {
            if (is_null($skorAkhir)) {
                return ['label' => 'Tinggi', 'badge' => 'bg-red-50 text-red-600'];
            }
    
            $turunSignifikan = ! is_null($skorSebelumnya) && ($skorSebelumnya - $skorAkhir) >= 5;
    
            $level = match ($kategoriLabel) {
                'Kurang' => 'Tinggi',
                'Cukup' => $turunSignifikan ? 'Tinggi' : 'Sedang',
                'Baik', 'Sangat Baik' => $turunSignifikan ? 'Sedang' : 'Rendah',
                default => 'Sedang',
            };
    
            $badge = match ($level) {
                'Tinggi' => 'bg-red-50 text-red-600',
                'Sedang' => 'bg-amber-50 text-amber-600',
                default => 'bg-emerald-50 text-emerald-600',
            };
    
            return ['label' => $level, 'badge' => $badge];
        };
    
        $satkerPerformance = $satkers
            ->when(request()->filled('satker_id'), fn ($collection) => $collection->where('id', request('satker_id')))
            ->map(function ($satker) use ($rangeAwal, $rangeAkhir, $rangeAwalSebelumnya, $rangeAkhirSebelumnya, $kategoriIkpa, $hitungSkorSatker, $prioritasPembinaan, $detailIndikatorPerSatker, $detailKosong) {
                $skorSekarang = $hitungSkorSatker($satker->id, $rangeAwal, $rangeAkhir);
                $skorAkhir = $skorSekarang['skor'];
                $totalTugas = $skorSekarang['total_tugas'];
                $tugasSelesai = $skorSekarang['tugas_selesai'];
    
                $skorPeriodeLalu = $hitungSkorSatker($satker->id, $rangeAwalSebelumnya, $rangeAkhirSebelumnya)['skor'];
    
                $status = 'Belum ada tugas';
                if (!is_null($skorAkhir)) {
                    $ambangBaik = config('sikoor.ambang_baik', 85);
                    $ambangCukup = config('sikoor.ambang_cukup', 60);
                    $status = $skorAkhir >= $ambangBaik ? 'Baik' : ($skorAkhir >= $ambangCukup ? 'Cukup' : 'Perlu Perhatian');
                }
    
                // Aktivitas terakhir satker ini: laporan terbaru, atau kalau belum ada, tugas terbaru
                $lastResultAt = \App\Models\IndicatorResult::whereHas('indicator', fn ($q) => $q->where('satker_id', $satker->id))->max('created_at');
                $lastIndicatorAt = \App\Models\Indicator::where('satker_id', $satker->id)->max('created_at');
                $updateTerakhir = collect([$lastResultAt, $lastIndicatorAt])
                    ->filter()
                    ->map(fn ($d) => \Carbon\Carbon::parse($d))
                    ->sortDesc()
                    ->first();
    
                $kategori = $kategoriIkpa($skorAkhir);
                $prioritas = $prioritasPembinaan($kategori['label'], $skorAkhir, $skorPeriodeLalu);
    
                // Label tren dibanding periode sebelumnya, buat indikator kecil di tabel prioritas
                $trendLabel = 'Baru';
                if (! is_null($skorAkhir) && ! is_null($skorPeriodeLalu)) {
                    $selisih = round($skorAkhir - $skorPeriodeLalu, 1);
                    $trendLabel = $selisih > 0 ? "Naik {$selisih}" : ($selisih < 0 ? 'Turun ' . abs($selisih) : 'Tetap');
                }
    
                return (object) [
                    'id' => $satker->id,
                    'nama_satker' => $satker->nama_satker,
                    'total_tugas' => $totalTugas,
                    'tugas_selesai' => $tugasSelesai,
                    'nilai' => $skorAkhir,
                    'status' => $status,
                    'kategori_label' => $kategori['label'],
                    'kategori_badge' => $kategori['badge'],
                    'prioritas_label' => $prioritas['label'],
                    'prioritas_badge' => $prioritas['badge'],
                    'trend_label' => $trendLabel,
                    'detail_indikator' => array_merge($detailKosong, $detailIndikatorPerSatker[$satker->id] ?? []),
                    'update_terakhir' => $updateTerakhir,
                ];
            })
            ->values();
    
        $totalSatker = $satkerPerformance->count();
        $rataRataKinerja = $satkerPerformance->whereNotNull('nilai')->avg('nilai');

        // Satker terbaik periode ini: nilai tertinggi, TAPI hanya dihitung kalau
        // masuk kategori Hijau (skema lama ambang_hijau, default 95) alias rentang 95-100.
        // Kalau tidak ada satker yang tembus 95, widget ini akan kosong (bukan nampilin
        // yang di bawah 95 sebagai "terbaik").
        $ambangHijau = config('sikoor.ambang_hijau', 95);
        $satkerTerbaikHijau = $satkerPerformance
            ->filter(fn ($sp) => ! is_null($sp->nilai) && $sp->nilai >= $ambangHijau && $sp->nilai <= 100)
            ->sortByDesc('nilai')
            ->first();

        // Peringkat satker Hijau (95-100) urut dari nilai tertinggi, buat tabel
        // "Peringkat Satker Terbaik" di dashboard. Satker di bawah 95 tidak ikut tampil
        // di sini sama sekali (bukan cuma diurutkan ke bawah).
        $satkerRankingHijau = $satkerPerformance
            ->filter(fn ($sp) => ! is_null($sp->nilai) && $sp->nilai >= $ambangHijau && $sp->nilai <= 100)
            ->sortByDesc('nilai')
            ->values();
    
        
        $urutanPrioritas = ['Tinggi' => 0, 'Sedang' => 1, 'Rendah' => 2];
        $satkerPrioritas = $satkerPerformance
            ->sort(function ($a, $b) use ($urutanPrioritas) {
                $rankA = $urutanPrioritas[$a->prioritas_label] ?? 3;
                $rankB = $urutanPrioritas[$b->prioritas_label] ?? 3;
                if ($rankA !== $rankB) {
                    return $rankA <=> $rankB;
                }
                return ($a->nilai ?? -1) <=> ($b->nilai ?? -1);
            })
            ->values()
            ->take(8);
        $satkerPrioritasMini = $satkerPrioritas->take(5);
        
        $labelPeriode = function (\Carbon\Carbon $awal) use ($granularitas) {
            return match ($granularitas) {
                'triwulan' => 'TW' . ceil($awal->month / 3) . ' ' . $awal->year,
                'semester' => 'S' . ($awal->month <= 6 ? 1 : 2) . ' ' . $awal->year,
                'tahunan'  => (string) $awal->year,
                default    => $awal->translatedFormat('M Y'),
            };
        };
    
        
        $trendBulanan = collect();
        for ($i = $jumlahTitikTren - 1; $i >= 0; $i--) {
            $awalPeriode  = $rangeAwal->copy()->subMonths($panjangBulanPeriode * $i);
            $akhirPeriode = $awalPeriode->copy()->addMonths($panjangBulanPeriode)->subDay()->endOfDay();
    
            $rataPeriodeIni = \App\Models\IndicatorResult::whereNotNull('indicator_results.nilai')
                ->join('indicators', 'indicator_results.indicator_id', '=', 'indicators.id')
                ->whereBetween('indicators.periode', [$awalPeriode, $akhirPeriode])
                ->when(request()->filled('satker_id'), fn ($q) => $q->where('indicators.satker_id', request('satker_id')))
                ->avg('indicator_results.nilai');
    
            $trendBulanan->push([
                'bulan' => $labelPeriode($awalPeriode),
                'nilai' => $rataPeriodeIni ? round($rataPeriodeIni, 2) : 0,
            ]);
        }
    
        
        $ikpaPeriodeIni = $trendBulanan->last()['nilai'] ?? 0;
        $ikpaPeriodeSebelumnya = $trendBulanan->count() > 1 ? $trendBulanan[$trendBulanan->count() - 2]['nilai'] : null;
        $selisihBulanLalu = ! is_null($ikpaPeriodeSebelumnya) ? round($ikpaPeriodeIni - $ikpaPeriodeSebelumnya, 2) : null;
    
        
        $totalSangatBaik = $satkerPerformance->where('kategori_label', 'Sangat Baik')->count();
        $totalBaik = $satkerPerformance->where('kategori_label', 'Baik')->count();
        $totalCukup = $satkerPerformance->where('kategori_label', 'Cukup')->count();
        $totalKurang = $satkerPerformance->where('kategori_label', 'Kurang')->count();
        
        $totalPerluPerhatian = $totalCukup + $totalKurang;
    
        $persenSangatBaik = $totalSatker > 0 ? round($totalSangatBaik / $totalSatker * 100, 2) : 0;
        $persenBaik = $totalSatker > 0 ? round($totalBaik / $totalSatker * 100, 2) : 0;
        $persenCukup = $totalSatker > 0 ? round($totalCukup / $totalSatker * 100, 2) : 0;
        $persenKurang = $totalSatker > 0 ? round($totalKurang / $totalSatker * 100, 2) : 0;
        $persenPerluPerhatian = $totalSatker > 0 ? round($totalPerluPerhatian / $totalSatker * 100, 2) : 0;
    
        
        $urutanIndikatorBaku = [
            'Revisi DIPA', 'Deviasi Halaman III DIPA', 'Penyerapan Anggaran', 'Belanja Kontraktual',
            'Penyelesaian Tagihan', 'Pengelolaan UP/TUP', 'Dispensasi SPM', 'Retur SP2D', 'Capaian Output',
        ];
    
        
        $trafficLightIndikator = function (?float $nilai) use ($kategoriIkpa) {
            $label = $kategoriIkpa($nilai)['label'];
    
            return match ($label) {
                'Sangat Baik', 'Baik' => ['warna' => 'Baik', 'bar' => 'bg-emerald-500', 'teks' => 'text-emerald-600', 'badge' => 'bg-emerald-50 text-emerald-600'],
                'Cukup' => ['warna' => 'Cukup', 'bar' => 'bg-amber-500', 'teks' => 'text-amber-600', 'badge' => 'bg-amber-50 text-amber-600'],
                'Kurang' => ['warna' => 'Kurang', 'bar' => 'bg-red-500', 'teks' => 'text-red-600', 'badge' => 'bg-red-50 text-red-600'],
                default => ['warna' => 'Belum Dinilai', 'bar' => 'bg-slate-200', 'teks' => 'text-slate-400', 'badge' => 'bg-slate-100 text-slate-500'],
            };
        };
    
        
        $judulIndikatorAktif = collect(config('sikoor.jenis_indikator', []));
    
        $rataPerJudul = \App\Models\IndicatorResult::whereNotNull('indicator_results.nilai')
            ->join('indicators', 'indicator_results.indicator_id', '=', 'indicators.id')
            ->when(request()->filled('satker_id'), fn ($q) => $q->where('indicators.satker_id', request('satker_id')))
            ->whereBetween('indicators.periode', [$rangeAwal, $rangeAkhir])
            ->select('indicators.judul', DB::raw('AVG(indicator_results.nilai) as rata'))
            ->groupBy('indicators.judul')
            ->pluck('rata', 'judul');
    
        $nilaiPerIndikator = $judulIndikatorAktif
            ->map(function ($judul) use ($rataPerJudul, $trafficLightIndikator) {
                $rata = isset($rataPerJudul[$judul]) ? round((float) $rataPerJudul[$judul], 2) : null;
                $tl = $trafficLightIndikator($rata);
    
                return [
                    'judul' => $judul,
                    'rata' => $rata,
                    'warna' => $tl['warna'],
                    'kelas_bar' => $tl['bar'],
                    'kelas_teks' => $tl['teks'],
                    'kelas_badge' => $tl['badge'],
                ];
            })
            ->sortBy(function ($item) use ($urutanIndikatorBaku) {
                $posisi = array_search($item['judul'], $urutanIndikatorBaku, true);
                return $posisi !== false ? sprintf('0-%02d', $posisi) : '1-' . $item['judul'];
            })
            ->values();
    
        
        if (! request()->filled('satker_id')) {
            \App\Http\Controllers\NotificationController::generateNotifikasiIkpa(
                $satkerPerformance, $nilaiPerIndikator, $indicators, $rangeAkhir, $labelPeriodeAktif
            );
        }
    
        // Early Warning untuk admin yang sedang login — hanya notifikasi otomatis
        // hasil deteksi kondisi IKPA (bukan chat/dokumen biasa).
        $tipeEarlyWarning = ['penurunan_ikpa', 'deviasi_anggaran', 'keterlambatan_tagihan', 'batas_tindak_lanjut'];
        $earlyWarnings = \App\Models\Notification::where('user_id', auth()->id())
            ->whereIn('type', $tipeEarlyWarning)
            ->latest()
            ->take(6)
            ->get();

        // Progress tindak lanjut: status laporan tiap tugas (indicator) sesuai filter satker/periode saat ini
        $tindakLanjutSelesai = 0;
        $tindakLanjutProses = 0;
        $tindakLanjutBelum = 0;
        foreach ($indicators as $ind) {
            $latestResult = $ind->results->sortByDesc('created_at')->first();
            if (! $latestResult) {
                $tindakLanjutBelum++;
            } elseif ($latestResult->status === 'diterima') {
                $tindakLanjutSelesai++;
            } else {
                // 'dikirim' (menunggu dinilai admin) atau 'direvisi' (menunggu satker perbaiki)
                $tindakLanjutProses++;
            }
        }
        $totalTindakLanjut = $indicators->count();

        return view('admin.dashboard', compact(
            'indicators', 'satkers', 'satkerPerformance',
            'granularitas', 'periodeAktif', 'tahunAktif', 'triwulanAktif', 'semesterAktif', 'labelPeriodeAktif', 'trendRange',
            'totalSatker', 'rataRataKinerja', 'selisihBulanLalu', 'trendBulanan',
            'totalSangatBaik', 'totalBaik', 'totalCukup', 'totalKurang', 'totalPerluPerhatian',
            'persenSangatBaik', 'persenBaik', 'persenCukup', 'persenKurang', 'persenPerluPerhatian',
            'satkerPrioritas', 'satkerPrioritasMini', 'nilaiPerIndikator', 'earlyWarnings', 'satkerTerbaikHijau', 'satkerRankingHijau',
            'tindakLanjutSelesai', 'tindakLanjutProses', 'tindakLanjutBelum', 'totalTindakLanjut'
        ));
    }

    return redirect()->route('monitoring.saya');
})->middleware(['auth', 'password.change'])->name('dashboard');

Route::middleware(['auth', 'password.change'])->group(function () {

    Route::post('/indicator/{indicator_id}/upload', [IndicatorResultController::class, 'store'])->name('indicator.upload');

    Route::middleware('admin')->group(function () {
        Route::get('/indicators', [IndicatorController::class, 'index'])->name('indicators.index');
        Route::post('/indicators', [IndicatorController::class, 'store'])->name('indicators.store');
        route::put('/indicators/{id}', [IndicatorController::class, 'update'])->name('indicators.update');
        Route::get('/indicators/{id}', [IndicatorController::class, 'show'])->name('indicators.show');
        Route::get('/indicators/riwayat', [IndicatorController::class, 'riwayat'])->name('indicators.riwayat');
        Route::get('/indicators/riwayat/{batchId}', [IndicatorController::class, 'riwayatDetail'])->name('indicators.riwayat.detail');
        Route::post('/indicator-results/{id}/nilai', [IndicatorResultController::class, 'updateStatus'])->name('indicator-results.updateStatus');
        Route::get('/satkers', [SatkerController::class, 'index'])->name('satkers.index');
        Route::post('/satkers', [SatkerController::class, 'store'])->name('satkers.store');
        Route::delete('/satkers/{id}', [SatkerController::class, 'destroy'])->name('satkers.destroy');
        Route::get('/satkers/cetak-kredensial', [SatkerController::class, 'cetakKredensialForm'])->name('satkers.cetakKredensialForm');
        Route::post('/satkers/cetak-kredensial', [SatkerController::class, 'cetakKredensial'])->name('satkers.cetakKredensial');
        route::get('/satkers/{id}', [SatkerController::class, 'show'])->name('satkers.show');
        route::put('/satkers/{id}', [SatkerController::class, 'update'])->name('satkers.update');
        Route::resource('ikpa-indikator', IkpaIndikatorController::class)
    ->parameters(['ikpa-indikator' => 'ikpaIndikator'])
    ->only(['index', 'store', 'update', 'destroy']);
        Route::get('/monitoring-ikpa', function () {
            $granularitas = in_array(request('granularitas'), ['bulanan', 'triwulan', 'semester', 'tahunan'])
                ? request('granularitas')
                : 'bulanan';

            $periodeFilter = request('periode') ?: now()->format('Y-m');
            $periodeAktif  = \Carbon\Carbon::createFromFormat('Y-m', $periodeFilter);

            $tahunAktif    = (int) (request('tahun') ?: now()->year);
            $triwulanAktif = (int) (request('triwulan') ?: ceil(now()->month / 3));
            $semesterAktif = (int) (request('semester') ?: (now()->month <= 6 ? 1 : 2));

            switch ($granularitas) {
                case 'triwulan':
                    $bulanAwalTriwulan = ($triwulanAktif - 1) * 3 + 1;
                    $rangeAwal  = \Carbon\Carbon::create($tahunAktif, $bulanAwalTriwulan, 1)->startOfMonth();
                    $rangeAkhir = $rangeAwal->copy()->addMonths(2)->endOfMonth();
                    $labelPeriodeAktif = "Triwulan {$triwulanAktif} {$tahunAktif}";
                    break;

                case 'semester':
                    $bulanAwalSemester = $semesterAktif === 1 ? 1 : 7;
                    $rangeAwal  = \Carbon\Carbon::create($tahunAktif, $bulanAwalSemester, 1)->startOfMonth();
                    $rangeAkhir = $rangeAwal->copy()->addMonths(5)->endOfMonth();
                    $labelPeriodeAktif = "Semester {$semesterAktif} {$tahunAktif}";
                    break;

                case 'tahunan':
                    $rangeAwal  = \Carbon\Carbon::create($tahunAktif, 1, 1)->startOfYear();
                    $rangeAkhir = \Carbon\Carbon::create($tahunAktif, 12, 31)->endOfYear();
                    $labelPeriodeAktif = "Tahun {$tahunAktif}";
                    break;

                case 'bulanan':
                default:
                    $rangeAwal  = $periodeAktif->copy()->startOfMonth();
                    $rangeAkhir = $periodeAktif->copy()->endOfMonth();
                    $labelPeriodeAktif = $periodeAktif->translatedFormat('F Y');
                    break;
            }

            $query = \App\Models\Indicator::with(['satker', 'results'])
                ->whereBetween('periode', [$rangeAwal, $rangeAkhir]);

            if (request()->filled('satker_id')) {
                $query->where('satker_id', request('satker_id'));
            }

            $indicators = $query->latest()
                ->get()
                ->map(function ($item) {
                    $item->satker_nama = $item->satker->nama_satker ?? '-';
                    $item->status = $item->results->count() > 0 ? 'terkirim' : 'pending';
                    return $item;
                });

            // Detail nilai per-satker untuk 5 indikator spesifik (kolom tabel)
            $judulDetailTabel = ['Penyerapan Anggaran', 'Deviasi Halaman III DIPA', 'Penyelesaian Tagihan', 'Belanja Kontraktual', 'Pengelolaan UP/TUP'];
            $detailKosong = array_fill_keys($judulDetailTabel, null);

            $detailIndikatorPerSatker = [];
            foreach ($indicators as $ind) {
                if (! in_array($ind->judul, $judulDetailTabel, true)) {
                    continue;
                }
                $latestResult = $ind->results->sortByDesc('created_at')->first();
                $detailIndikatorPerSatker[$ind->satker_id][$ind->judul] =
                    ($latestResult && ! is_null($latestResult->nilai)) ? (float) $latestResult->nilai : null;
            }

            $satkers = \App\Models\Satker::orderBy('nama_satker')->get();

            $kategoriIkpa = function (?float $nilai) {
                if (is_null($nilai)) {
                    return ['label' => 'Belum Dinilai', 'badge' => 'bg-slate-100 text-slate-500'];
                }
                if ($nilai >= config('sikoor.ikpa_ambang_sangat_baik', 90)) {
                    return ['label' => 'Sangat Baik', 'badge' => 'bg-emerald-50 text-emerald-600'];
                }
                if ($nilai >= config('sikoor.ikpa_ambang_baik', 80)) {
                    return ['label' => 'Baik', 'badge' => 'bg-blue-50 text-blue-600'];
                }
                if ($nilai >= config('sikoor.ikpa_ambang_cukup', 70)) {
                    return ['label' => 'Cukup', 'badge' => 'bg-amber-50 text-amber-600'];
                }
                return ['label' => 'Kurang', 'badge' => 'bg-red-50 text-red-600'];
            };

            $satkerPerformance = $satkers
                ->when(request()->filled('satker_id'), fn ($collection) => $collection->where('id', request('satker_id')))
                ->map(function ($satker) use ($rangeAwal, $rangeAkhir, $kategoriIkpa, $detailIndikatorPerSatker, $detailKosong) {
                    $tugasQuery = \App\Models\Indicator::where('satker_id', $satker->id)
                        ->whereBetween('periode', [$rangeAwal, $rangeAkhir]);

                    $totalTugas = (clone $tugasQuery)->count();
                    $tugasSelesai = (clone $tugasQuery)->whereHas('results')->count();
                    $progres = $totalTugas > 0 ? ($tugasSelesai / $totalTugas) * 100 : null;

                    $rataKualitas = \App\Models\IndicatorResult::whereHas('indicator', function ($q) use ($satker, $rangeAwal, $rangeAkhir) {
                            $q->where('satker_id', $satker->id)->whereBetween('periode', [$rangeAwal, $rangeAkhir]);
                        })
                        ->whereNotNull('nilai')
                        ->avg('nilai');

                    $bobotProgres = config('sikoor.bobot_progres', 0.4);
                    $bobotKualitas = config('sikoor.bobot_kualitas', 0.6);

                    $skorAkhir = null;
                    if (! is_null($progres) && ! is_null($rataKualitas)) {
                        $skorAkhir = round(($progres * $bobotProgres) + ($rataKualitas * $bobotKualitas), 1);
                    } elseif (! is_null($progres)) {
                        $skorAkhir = round($progres, 1);
                    }

                    $lastResultAt = \App\Models\IndicatorResult::whereHas('indicator', fn ($q) => $q->where('satker_id', $satker->id))->max('created_at');
                    $lastIndicatorAt = \App\Models\Indicator::where('satker_id', $satker->id)->max('created_at');
                    $updateTerakhir = collect([$lastResultAt, $lastIndicatorAt])
                        ->filter()
                        ->map(fn ($d) => \Carbon\Carbon::parse($d))
                        ->sortDesc()
                        ->first();

                    $kategori = $kategoriIkpa($skorAkhir);

                    return (object) [
                        'id' => $satker->id,
                        'nama_satker' => $satker->nama_satker,
                        'total_tugas' => $totalTugas,
                        'tugas_selesai' => $tugasSelesai,
                        'nilai' => $skorAkhir,
                        'kategori_label' => $kategori['label'],
                        'kategori_badge' => $kategori['badge'],
                        'detail_indikator' => array_merge($detailKosong, $detailIndikatorPerSatker[$satker->id] ?? []),
                        'update_terakhir' => $updateTerakhir,
                    ];
                })
                ->values();
        $tindakLanjutSelesai = 0;
        $tindakLanjutProses = 0;
        $tindakLanjutBelum = 0;
        foreach ($indicators as $ind) {
            $latestResult = $ind->results->sortByDesc('created_at')->first();
            if (!$latestResult) {
                $tindakLanjutBelum++;
            } elseif ($latestResult->status === 'diterima') {
                $tindakLanjutSelesai++;
            } else {
                $tindakLanjutProses++;
            }
        }
        $totalTindakLanjut = $indicators->count();
            return view('admin.monitoring', compact(
    'indicators', 'satkers', 'satkerPerformance',
    'granularitas', 'periodeAktif', 'tahunAktif', 'triwulanAktif', 'semesterAktif', 'labelPeriodeAktif',
    'tindakLanjutSelesai', 'tindakLanjutProses', 'tindakLanjutBelum', 'totalTindakLanjut'
));
        })->name('monitoring.ikpa');

       
        Route::get('/monitoring/cetak/{satker}', function (\App\Models\Satker $satker) {
            $granularitas = in_array(request('granularitas'), ['bulanan', 'triwulan', 'semester', 'tahunan'])
                ? request('granularitas') : 'bulanan';
            $tahunAktif = (int) (request('tahun') ?: now()->year);
            $triwulanAktif = (int) (request('triwulan') ?: ceil(now()->month / 3));
            $semesterAktif = (int) (request('semester') ?: (now()->month <= 6 ? 1 : 2));
            $periodeFilter = request('periode') ?: now()->format('Y-m');
            $periodeAktif = \Carbon\Carbon::createFromFormat('Y-m', $periodeFilter);

            switch ($granularitas) {
                case 'triwulan':
                    $bulanAwal = ($triwulanAktif - 1) * 3 + 1;
                    $rangeAwal = \Carbon\Carbon::create($tahunAktif, $bulanAwal, 1)->startOfMonth();
                    $rangeAkhir = $rangeAwal->copy()->addMonths(2)->endOfMonth();
                    $labelPeriodeAktif = "Triwulan {$triwulanAktif} {$tahunAktif}";
                    break;
                case 'semester':
                    $bulanAwal = $semesterAktif === 1 ? 1 : 7;
                    $rangeAwal = \Carbon\Carbon::create($tahunAktif, $bulanAwal, 1)->startOfMonth();
                    $rangeAkhir = $rangeAwal->copy()->addMonths(5)->endOfMonth();
                    $labelPeriodeAktif = "Semester {$semesterAktif} {$tahunAktif}";
                    break;
                case 'tahunan':
                    $rangeAwal = \Carbon\Carbon::create($tahunAktif, 1, 1)->startOfYear();
                    $rangeAkhir = \Carbon\Carbon::create($tahunAktif, 12, 31)->endOfYear();
                    $labelPeriodeAktif = "Tahun {$tahunAktif}";
                    break;
                default:
                    $rangeAwal = $periodeAktif->copy()->startOfMonth();
                    $rangeAkhir = $periodeAktif->copy()->endOfMonth();
                    $labelPeriodeAktif = $periodeAktif->translatedFormat('F Y');
            }

            $indicatorsSatker = \App\Models\Indicator::with('results')
                ->where('satker_id', $satker->id)
                ->whereBetween('periode', [$rangeAwal, $rangeAkhir])
                ->get();

            $baris = $indicatorsSatker->map(function ($ind) {
                $latest = $ind->results->sortByDesc('created_at')->first();
                return [
                    'judul' => $ind->judul,
                    'status' => $latest->status ?? 'Belum lapor',
                    'nilai' => $latest->nilai ?? null,
                    'catatan' => $latest->catatan_admin ?? null,
                ];
            });

            $rataRata = $baris->pluck('nilai')->filter()->avg();

            $tindakLanjutSelesai = 0;
$tindakLanjutProses = 0;
$tindakLanjutBelum = 0;
foreach ($indicators as $ind) {
    $latestResult = $ind->results->sortByDesc('created_at')->first();
    if (!$latestResult) {
        $tindakLanjutBelum++;
    } elseif ($latestResult->status === 'diterima') {
        $tindakLanjutSelesai++;
    } else {
        $tindakLanjutProses++;
    }
}
$totalTindakLanjut = $indicators->count();
            return view('admin.monitoring-cetak', compact('satker', 'baris', 'rataRata', 'labelPeriodeAktif'));
        })->name('monitoring.cetak');

        Route::get('/peringatan', [\App\Http\Controllers\PeringatanSatkerController::class, 'index'])->name('peringatan.index');
        Route::post('/peringatan', [\App\Http\Controllers\PeringatanSatkerController::class, 'store'])->name('peringatan.store');
        Route::post('/peringatan/{id}/selesai', [\App\Http\Controllers\PeringatanSatkerController::class, 'selesaikan'])->name('peringatan.selesai');
        Route::delete('/peringatan/{id}', [\App\Http\Controllers\PeringatanSatkerController::class, 'destroy'])->name('peringatan.destroy');
    });

    // ================= MONITORING KINERJA (khusus satker sendiri) =================
    // Satker hanya boleh memantau performanya sendiri di dashboard ini, tidak boleh
    // melihat atau memilih data satker lain (berbeda dengan /monitoring-ikpa yang
    // khusus admin dan bisa melihat semua satker).
    Route::get('/monitoring-saya', function () {
        if (auth()->user()->role === 'admin') {
            return redirect()->route('monitoring.ikpa');
        }

        $satkerId = auth()->user()->satker_id;

        abort_unless($satkerId, 403, 'Akun Anda belum terhubung ke satker manapun.');

        // ================= PERIODE FILTER (multi-granularitas) =================
        $granularitas = in_array(request('granularitas'), ['bulanan', 'triwulan', 'semester', 'tahunan'])
            ? request('granularitas')
            : 'bulanan';

        $periodeFilter = request('periode') ?: now()->format('Y-m');
        $periodeAktif  = \Carbon\Carbon::createFromFormat('Y-m', $periodeFilter);

        $tahunAktif     = (int) (request('tahun') ?: now()->year);
        $triwulanAktif  = (int) (request('triwulan') ?: ceil(now()->month / 3));
        $semesterAktif  = (int) (request('semester') ?: (now()->month <= 6 ? 1 : 2));

        switch ($granularitas) {
            case 'triwulan':
                $bulanAwalTriwulan = ($triwulanAktif - 1) * 3 + 1;
                $rangeAwal  = \Carbon\Carbon::create($tahunAktif, $bulanAwalTriwulan, 1)->startOfMonth();
                $rangeAkhir = $rangeAwal->copy()->addMonths(2)->endOfMonth();
                $labelPeriodeAktif = "Triwulan {$triwulanAktif} {$tahunAktif}";
                break;

            case 'semester':
                $bulanAwalSemester = $semesterAktif === 1 ? 1 : 7;
                $rangeAwal  = \Carbon\Carbon::create($tahunAktif, $bulanAwalSemester, 1)->startOfMonth();
                $rangeAkhir = $rangeAwal->copy()->addMonths(5)->endOfMonth();
                $labelPeriodeAktif = "Semester {$semesterAktif} {$tahunAktif}";
                break;

            case 'tahunan':
                $rangeAwal  = \Carbon\Carbon::create($tahunAktif, 1, 1)->startOfYear();
                $rangeAkhir = \Carbon\Carbon::create($tahunAktif, 12, 31)->endOfYear();
                $labelPeriodeAktif = "Tahun {$tahunAktif}";
                break;

            case 'bulanan':
            default:
                $rangeAwal  = $periodeAktif->copy()->startOfMonth();
                $rangeAkhir = $periodeAktif->copy()->endOfMonth();
                $labelPeriodeAktif = $periodeAktif->translatedFormat('F Y');
                break;
        }

        $trendRange = (int) request('trend_range', 6);
        if (! in_array($trendRange, [6, 12], true)) {
            $trendRange = 6;
        }

        $jumlahTitikTren = $granularitas === 'bulanan'
            ? $trendRange
            : ['triwulan' => 4, 'semester' => 4, 'tahunan' => 5][$granularitas];
        $panjangBulanPeriode = ['bulanan' => 1, 'triwulan' => 3, 'semester' => 6, 'tahunan' => 12][$granularitas];

        // Semua query di bawah ini SELALU dikunci ke satker_id milik user yang login.
        $indicators = \App\Models\Indicator::with(['satker', 'results'])
            ->where('satker_id', $satkerId)
            ->whereBetween('periode', [$rangeAwal, $rangeAkhir])
            ->latest()
            ->get()
            ->map(function ($item) {
                $item->satker_nama = $item->satker->nama_satker ?? '-';
                $item->status = $item->results->count() > 0 ? 'terkirim' : 'pending';
                return $item;
            });

        $judulDetailTabel = ['Penyerapan Anggaran', 'Deviasi Halaman III DIPA', 'Penyelesaian Tagihan', 'Belanja Kontraktual', 'Pengelolaan UP/TUP'];
        $detailKosong = array_fill_keys($judulDetailTabel, null);

        $detailIndikatorSatker = $detailKosong;
        foreach ($indicators as $ind) {
            if (! in_array($ind->judul, $judulDetailTabel, true)) {
                continue;
            }
            $latestResult = $ind->results->sortByDesc('created_at')->first();
            $detailIndikatorSatker[$ind->judul] =
                ($latestResult && ! is_null($latestResult->nilai)) ? (float) $latestResult->nilai : null;
        }

        $kategoriIkpa = function (?float $nilai) {
            if (is_null($nilai)) {
                return ['label' => 'Belum Dinilai', 'badge' => 'bg-slate-100 text-slate-500'];
            }
            if ($nilai >= config('sikoor.ikpa_ambang_sangat_baik', 90)) {
                return ['label' => 'Sangat Baik', 'badge' => 'bg-emerald-50 text-emerald-600'];
            }
            if ($nilai >= config('sikoor.ikpa_ambang_baik', 80)) {
                return ['label' => 'Baik', 'badge' => 'bg-blue-50 text-blue-600'];
            }
            if ($nilai >= config('sikoor.ikpa_ambang_cukup', 70)) {
                return ['label' => 'Cukup', 'badge' => 'bg-amber-50 text-amber-600'];
            }
            return ['label' => 'Kurang', 'badge' => 'bg-red-50 text-red-600'];
        };

        $hitungSkorSatker = function (int $id, \Carbon\Carbon $awal, \Carbon\Carbon $akhir) {
            $tugasQuery = \App\Models\Indicator::where('satker_id', $id)
                ->whereBetween('periode', [$awal, $akhir]);

            $totalTugas = (clone $tugasQuery)->count();
            $tugasSelesai = (clone $tugasQuery)->whereHas('results')->count();
            $progres = $totalTugas > 0 ? ($tugasSelesai / $totalTugas) * 100 : null;

            $rataKualitas = \App\Models\IndicatorResult::whereHas('indicator', function ($q) use ($id, $awal, $akhir) {
                    $q->where('satker_id', $id)->whereBetween('periode', [$awal, $akhir]);
                })
                ->whereNotNull('nilai')
                ->avg('nilai');

            $bobotProgres = config('sikoor.bobot_progres', 0.4);
            $bobotKualitas = config('sikoor.bobot_kualitas', 0.6);

            $skor = null;
            if (! is_null($progres) && ! is_null($rataKualitas)) {
                $skor = round(($progres * $bobotProgres) + ($rataKualitas * $bobotKualitas), 1);
            } elseif (! is_null($progres)) {
                $skor = round($progres, 1);
            }

            return ['skor' => $skor, 'total_tugas' => $totalTugas, 'tugas_selesai' => $tugasSelesai];
        };

        $rangeAwalSebelumnya = $rangeAwal->copy()->subMonths($panjangBulanPeriode);
        $rangeAkhirSebelumnya = $rangeAwal->copy()->subDay()->endOfDay();

        $skorSekarang = $hitungSkorSatker($satkerId, $rangeAwal, $rangeAkhir);
        $skorAkhir = $skorSekarang['skor'];
        $skorPeriodeLalu = $hitungSkorSatker($satkerId, $rangeAwalSebelumnya, $rangeAkhirSebelumnya)['skor'];
        $selisihBulanLalu = (! is_null($skorAkhir) && ! is_null($skorPeriodeLalu)) ? round($skorAkhir - $skorPeriodeLalu, 2) : null;

        $status = 'Belum ada tugas';
        if (! is_null($skorAkhir)) {
            $ambangBaik = config('sikoor.ambang_baik', 85);
            $ambangCukup = config('sikoor.ambang_cukup', 60);
            $status = $skorAkhir >= $ambangBaik ? 'Baik' : ($skorAkhir >= $ambangCukup ? 'Cukup' : 'Perlu Perhatian');
        }

        $satker = \App\Models\Satker::find($satkerId);
        $kategori = $kategoriIkpa($skorAkhir);

        $lastResultAt = \App\Models\IndicatorResult::whereHas('indicator', fn ($q) => $q->where('satker_id', $satkerId))->max('created_at');
        $lastIndicatorAt = \App\Models\Indicator::where('satker_id', $satkerId)->max('created_at');
        $updateTerakhir = collect([$lastResultAt, $lastIndicatorAt])
            ->filter()
            ->map(fn ($d) => \Carbon\Carbon::parse($d))
            ->sortDesc()
            ->first();

        $satkerPerformance = collect([(object) [
            'id' => $satkerId,
            'nama_satker' => $satker->nama_satker ?? '-',
            'total_tugas' => $skorSekarang['total_tugas'],
            'tugas_selesai' => $skorSekarang['tugas_selesai'],
            'nilai' => $skorAkhir,
            'status' => $status,
            'kategori_label' => $kategori['label'],
            'kategori_badge' => $kategori['badge'],
            'detail_indikator' => $detailIndikatorSatker,
            'update_terakhir' => $updateTerakhir,
        ]]);

        $labelPeriode = function (\Carbon\Carbon $awal) use ($granularitas) {
            return match ($granularitas) {
                'triwulan' => 'TW' . ceil($awal->month / 3) . ' ' . $awal->year,
                'semester' => 'S' . ($awal->month <= 6 ? 1 : 2) . ' ' . $awal->year,
                'tahunan'  => (string) $awal->year,
                default    => $awal->translatedFormat('M Y'),
            };
        };

        $trendBulanan = collect();
        for ($i = $jumlahTitikTren - 1; $i >= 0; $i--) {
            $awalPeriode  = $rangeAwal->copy()->subMonths($panjangBulanPeriode * $i);
            $akhirPeriode = $awalPeriode->copy()->addMonths($panjangBulanPeriode)->subDay()->endOfDay();

            $rataPeriodeIni = \App\Models\IndicatorResult::whereNotNull('indicator_results.nilai')
                ->join('indicators', 'indicator_results.indicator_id', '=', 'indicators.id')
                ->where('indicators.satker_id', $satkerId)
                ->whereBetween('indicators.periode', [$awalPeriode, $akhirPeriode])
                ->avg('indicator_results.nilai');

            $trendBulanan->push([
                'bulan' => $labelPeriode($awalPeriode),
                'nilai' => $rataPeriodeIni ? round($rataPeriodeIni, 2) : 0,
            ]);
        }

        $urutanIndikatorBaku = \App\Models\IkpaBobotIndikator::namaTerurut();

        $trafficLightIndikator = function (?float $nilai) use ($kategoriIkpa) {
            $label = $kategoriIkpa($nilai)['label'];

            return match ($label) {
                'Sangat Baik', 'Baik' => ['warna' => 'Baik', 'bar' => 'bg-emerald-500', 'teks' => 'text-emerald-600', 'badge' => 'bg-emerald-50 text-emerald-600'],
                'Cukup' => ['warna' => 'Cukup', 'bar' => 'bg-amber-500', 'teks' => 'text-amber-600', 'badge' => 'bg-amber-50 text-amber-600'],
                'Kurang' => ['warna' => 'Kurang', 'bar' => 'bg-red-500', 'teks' => 'text-red-600', 'badge' => 'bg-red-50 text-red-600'],
                default => ['warna' => 'Belum Dinilai', 'bar' => 'bg-slate-200', 'teks' => 'text-slate-400', 'badge' => 'bg-slate-100 text-slate-500'],
            };
        };

        $judulIndikatorAktif = collect(\App\Models\IkpaBobotIndikator::namaTerurut());

        $rataPerJudul = \App\Models\IndicatorResult::whereNotNull('indicator_results.nilai')
            ->join('indicators', 'indicator_results.indicator_id', '=', 'indicators.id')
            ->where('indicators.satker_id', $satkerId)
            ->whereBetween('indicators.periode', [$rangeAwal, $rangeAkhir])
            ->select('indicators.judul', DB::raw('AVG(indicator_results.nilai) as rata'))
            ->groupBy('indicators.judul')
            ->pluck('rata', 'judul');

        $nilaiPerIndikator = $judulIndikatorAktif
            ->map(function ($judul) use ($rataPerJudul, $trafficLightIndikator) {
                $rata = isset($rataPerJudul[$judul]) ? round((float) $rataPerJudul[$judul], 2) : null;
                $tl = $trafficLightIndikator($rata);

                return [
                    'judul' => $judul,
                    'rata' => $rata,
                    'warna' => $tl['warna'],
                    'kelas_bar' => $tl['bar'],
                    'kelas_teks' => $tl['teks'],
                    'kelas_badge' => $tl['badge'],
                ];
            })
            ->sortBy(function ($item) use ($urutanIndikatorBaku) {
                $posisi = array_search($item['judul'], $urutanIndikatorBaku, true);
                return $posisi !== false ? sprintf('0-%02d', $posisi) : '1-' . $item['judul'];
            })
            ->values();

        $notifikasiTerbaru = \App\Models\Notification::where('user_id', auth()->id())
            ->latest()
            ->take(4)
            ->get();

        // ================= DOKUMEN BARU DARI ADMIN (auto-terbuka) =================
        // Menggantikan menu "Dokumen masuk": setiap indicator yang dikirim admin ke
        // satker ini dan membawa lampiran PDF/Excel, tapi belum pernah "dibuka" satker,
        // otomatis ditampilkan/dibuka di halaman ini (lihat modal di user/monitoring.blade.php).
        // Tidak dibatasi oleh filter periode di atas supaya dokumen baru tetap kelihatan
        // walau satker sedang melihat periode lain.
        $dokumenBaru = \App\Models\Indicator::where('satker_id', $satkerId)
            ->whereNull('dibuka_pada')
            ->where(function ($q) {
                $q->whereNotNull('file_pdf')->orWhereNotNull('file_excel');
            })
            ->oldest()
            ->get();

        $tindakLanjutSelesai = 0;
        $tindakLanjutProses = 0;
        $tindakLanjutBelum = 0;
foreach ($indicators as $ind) {
    $latestResult = $ind->results->sortByDesc('created_at')->first();
    if (!$latestResult) {
        $tindakLanjutBelum++;
    } elseif ($latestResult->status === 'diterima') {
        $tindakLanjutSelesai++;
    } else {
        $tindakLanjutProses++;
    }
}
$totalTindakLanjut = $indicators->count();

        return view('user.monitoring', compact(
            'satker', 'indicators', 'satkerPerformance',
            'granularitas', 'periodeAktif', 'tahunAktif', 'triwulanAktif', 'semesterAktif', 'labelPeriodeAktif', 'trendRange',
            'skorAkhir', 'selisihBulanLalu', 'kategori', 'trendBulanan',
            'nilaiPerIndikator', 'notifikasiTerbaru', 'dokumenBaru',
            'tindakLanjutSelesai', 'tindakLanjutProses', 'tindakLanjutBelum', 'totalTindakLanjut'
        ));
    })->name('monitoring.saya');

    // Dipanggil otomatis lewat AJAX begitu dokumen dari admin selesai ditampilkan
    // ke satker di halaman Monitoring Kinerja, supaya dokumen yang sama tidak
    // otomatis terbuka lagi di kunjungan berikutnya.
    Route::post('/dokumen/{indicator}/dibuka', function (\App\Models\Indicator $indicator) {
        abort_unless($indicator->satker_id === auth()->user()->satker_id, 403);

        if (! $indicator->dibuka_pada) {
            $indicator->dibuka_pada = now();
            $indicator->save();
        }

        return response()->json(['status' => 'ok']);
    })->name('dokumen.tandaiDibuka');

    // Dipoll berkala (lihat script di user/monitoring.blade.php) selama satker sedang
    // berada di halaman Monitoring Kinerja, supaya dokumen PDF/Excel yang baru dikirim
    // admin langsung otomatis muncul TANPA satker perlu reload halaman atau klik notifikasi.
    Route::get('/dokumen/cek-baru', function () {
        $satkerId = auth()->user()->satker_id;
        abort_unless($satkerId, 403);

        $dokumen = \App\Models\Indicator::where('satker_id', $satkerId)
            ->whereNull('dibuka_pada')
            ->where(function ($q) {
                $q->whereNotNull('file_pdf')->orWhereNotNull('file_excel');
            })
            ->oldest()
            ->get(['id', 'judul', 'deskripsi', 'file_pdf', 'file_excel'])
            ->map(function ($dok) {
                return [
                    'id' => $dok->id,
                    'judul' => $dok->judul,
                    'deskripsi' => $dok->deskripsi,
                    'file_pdf_url' => $dok->file_pdf ? asset('storage/' . $dok->file_pdf) : null,
                    'file_excel_url' => $dok->file_excel ? asset('storage/' . $dok->file_excel) : null,
                ];
            });

        return response()->json(['dokumen' => $dokumen]);
    })->name('dokumen.cekBaru');

    // Cetak laporan IKPA untuk satker sendiri saja (tidak menerima parameter satker
    // dari luar, supaya user satker tidak bisa mencetak laporan satker lain).
    Route::get('/monitoring-saya/cetak', function () {
        if (auth()->user()->role === 'admin') {
            return redirect()->route('monitoring.ikpa');
        }

        $satker = \App\Models\Satker::findOrFail(auth()->user()->satker_id);

        $granularitas = in_array(request('granularitas'), ['bulanan', 'triwulan', 'semester', 'tahunan'])
            ? request('granularitas') : 'bulanan';
        $tahunAktif = (int) (request('tahun') ?: now()->year);
        $triwulanAktif = (int) (request('triwulan') ?: ceil(now()->month / 3));
        $semesterAktif = (int) (request('semester') ?: (now()->month <= 6 ? 1 : 2));
        $periodeFilter = request('periode') ?: now()->format('Y-m');
        $periodeAktif = \Carbon\Carbon::createFromFormat('Y-m', $periodeFilter);

        switch ($granularitas) {
            case 'triwulan':
                $bulanAwal = ($triwulanAktif - 1) * 3 + 1;
                $rangeAwal = \Carbon\Carbon::create($tahunAktif, $bulanAwal, 1)->startOfMonth();
                $rangeAkhir = $rangeAwal->copy()->addMonths(2)->endOfMonth();
                $labelPeriodeAktif = "Triwulan {$triwulanAktif} {$tahunAktif}";
                break;
            case 'semester':
                $bulanAwal = $semesterAktif === 1 ? 1 : 7;
                $rangeAwal = \Carbon\Carbon::create($tahunAktif, $bulanAwal, 1)->startOfMonth();
                $rangeAkhir = $rangeAwal->copy()->addMonths(5)->endOfMonth();
                $labelPeriodeAktif = "Semester {$semesterAktif} {$tahunAktif}";
                break;
            case 'tahunan':
                $rangeAwal = \Carbon\Carbon::create($tahunAktif, 1, 1)->startOfYear();
                $rangeAkhir = \Carbon\Carbon::create($tahunAktif, 12, 31)->endOfYear();
                $labelPeriodeAktif = "Tahun {$tahunAktif}";
                break;
            default:
                $rangeAwal = $periodeAktif->copy()->startOfMonth();
                $rangeAkhir = $periodeAktif->copy()->endOfMonth();
                $labelPeriodeAktif = $periodeAktif->translatedFormat('F Y');
        }

        $indicatorsSatker = \App\Models\Indicator::with('results')
            ->where('satker_id', $satker->id)
            ->whereBetween('periode', [$rangeAwal, $rangeAkhir])
            ->get();

        $baris = $indicatorsSatker->map(function ($ind) {
            $latest = $ind->results->sortByDesc('created_at')->first();
            return [
                'judul' => $ind->judul,
                'status' => $latest->status ?? 'Belum lapor',
                'nilai' => $latest->nilai ?? null,
                'catatan' => $latest->catatan_admin ?? null,
            ];
        });

        $rataRata = $baris->pluck('nilai')->filter()->avg();

        return view('admin.monitoring-cetak', compact('satker', 'baris', 'rataRata', 'labelPeriodeAktif'));
    })->name('monitoring.cetak.saya');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/data', [MessageController::class, 'data'])->name('messages.data');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/broadcast', [MessageController::class, 'broadcastStore'])->name('messages.broadcast');

    // Status online & indikator "sedang mengetik" untuk Live chat (cache-based, polling).
    Route::post('/chat/heartbeat', [MessageController::class, 'heartbeat'])->name('chat.heartbeat');
    Route::post('/chat/typing', [MessageController::class, 'typing'])->name('chat.typing');
    Route::get('/chat/status', [MessageController::class, 'status'])->name('chat.status');
    Route::get('/chat/online-satkers', [MessageController::class, 'onlineSatkers'])->name('chat.onlineSatkers');

    Route::get('/notifications/data', [NotificationController::class, 'data'])->name('notifications.data');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    Route::get('/inbox', function () {
    // Periode aktif: default bulan berjalan, bisa difilter satker lewat dropdown,
    // supaya alurnya konsisten dengan cara admin memfilter periode di Monitoring IKPA.
    $periodeFilter = request('periode') ?: now()->format('Y-m');
    $periodeAktif = \Carbon\Carbon::createFromFormat('Y-m', $periodeFilter);
    $rangeAwal = $periodeAktif->copy()->startOfMonth();
    $rangeAkhir = $periodeAktif->copy()->endOfMonth();

    $indicators = \App\Models\Indicator::with('results')
        ->where('satker_id', auth()->user()->satker_id)
        ->whereBetween('periode', [$rangeAwal, $rangeAkhir])
        ->latest()
        ->get();

    // Peringatan aktif untuk satker ini (buat running text), dan status terkunci
    // (buat sembunyikan form upload) kalau ada peringatan aktif yang batas waktunya lewat.
    $peringatanAktif = \App\Models\PeringatanSatker::where('satker_id', auth()->user()->satker_id)
        ->aktif()
        ->orderBy('batas_waktu')
        ->get();

    $terkunci = $peringatanAktif->contains(fn ($p) => $p->sudahLewatBatasWaktu());

    return view('user.inbox', compact('indicators', 'peringatanAktif', 'terkunci', 'periodeAktif'));
})->name('user.inbox');

    Route::get('/chat', function () {
        return view('user.chat');
    })->name('user.chat');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';