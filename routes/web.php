<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\IndicatorResultController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SatkerController;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', function () {
    if (auth()->user()->role === 'admin') {
        
        $awal = now()->startOfMonth();
        $akhir = now()->endOfMonth();

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
            $tugasQuery = \App\Models\Indicator::where('satker_id', $satkerId)->whereBetween('periode', [$awal, $akhir]);
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

            if (! is_null($progres) && ! is_null($rataKualitas)) {
                return round(($progres * $bobotProgres) + ($rataKualitas * $bobotKualitas), 1);
            } elseif (! is_null($progres)) {
                return round($progres, 1);
            }
            return null;
        };

        $satkerRingkas = \App\Models\Satker::orderBy('nama_satker')->get()
            ->map(function ($satker) use ($awal, $akhir, $hitungSkorSatker, $kategoriIkpa) {
                $skor = $hitungSkorSatker($satker->id, $awal, $akhir);
                $kategori = $kategoriIkpa($skor);

                return (object) [
                    'id' => $satker->id,
                    'nama_satker' => $satker->nama_satker,
                    'nilai' => $skor,
                    'kategori_label' => $kategori['label'],
                    'kategori_badge' => $kategori['badge'],
                ];
            });

        $totalSatker = $satkerRingkas->count();
        $rataRataKinerja = $satkerRingkas->whereNotNull('nilai')->avg('nilai');

        $totalSangatBaik = $satkerRingkas->where('kategori_label', 'Sangat Baik')->count();
        $totalBaik = $satkerRingkas->where('kategori_label', 'Baik')->count();
        $totalCukup = $satkerRingkas->where('kategori_label', 'Cukup')->count();
        $totalKurang = $satkerRingkas->where('kategori_label', 'Kurang')->count();
        $totalPerluPerhatian = $totalCukup + $totalKurang;
        $persenSangatBaik = $totalSatker > 0 ? round($totalSangatBaik / $totalSatker * 100, 2) : 0;
        $persenPerluPerhatian = $totalSatker > 0 ? round($totalPerluPerhatian / $totalSatker * 100, 2) : 0;
        $persenKurang = $totalSatker > 0 ? round($totalKurang / $totalSatker * 100, 2) : 0;

        
        $trendMini = collect();
        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->copy()->subMonths($i);
            $rata = \App\Models\IndicatorResult::whereNotNull('indicator_results.nilai')
                ->join('indicators', 'indicator_results.indicator_id', '=', 'indicators.id')
                ->whereYear('indicators.periode', $bulan->year)
                ->whereMonth('indicators.periode', $bulan->month)
                ->avg('indicator_results.nilai');

            $trendMini->push([
                'bulan' => $bulan->translatedFormat('M Y'),
                'nilai' => $rata ? round($rata, 2) : 0,
            ]);
        }

        $ikpaBulanIni = $trendMini->last()['nilai'] ?? 0;
        $ikpaBulanLalu = $trendMini->count() > 1 ? $trendMini[$trendMini->count() - 2]['nilai'] : null;
        $selisihBulanLalu = ! is_null($ikpaBulanLalu) ? round($ikpaBulanIni - $ikpaBulanLalu, 2) : null;

        
        $satkerPrioritasMini = $satkerRingkas
            ->sort(function ($a, $b) {
                $urutan = ['Kurang' => 0, 'Belum Dinilai' => 0, 'Cukup' => 1, 'Baik' => 2, 'Sangat Baik' => 3];
                $ra = $urutan[$a->kategori_label] ?? 4;
                $rb = $urutan[$b->kategori_label] ?? 4;
                if ($ra !== $rb) {
                    return $ra <=> $rb;
                }
                return ($a->nilai ?? -1) <=> ($b->nilai ?? -1);
            })
            ->values()
            ->take(5);

        return view('admin.dashboard', compact(
            'totalSatker', 'rataRataKinerja', 'selisihBulanLalu',
            'totalSangatBaik', 'totalBaik', 'totalCukup', 'totalKurang', 'totalPerluPerhatian',
            'persenSangatBaik', 'persenPerluPerhatian', 'persenKurang',
            'trendMini', 'satkerPrioritasMini'
        ));
    }

    return redirect()->route('user.inbox');
})->middleware(['auth'])->name('dashboard');


    Route::middleware('auth')->group(function () {

    Route::post('/indicator/{indicator_id}/upload', [IndicatorResultController::class, 'store'])->name('indicator.upload');

    Route::middleware('admin')->group(function () {
        Route::get('/indicators', [IndicatorController::class, 'index'])->name('indicators.index');
        Route::post('/indicators', [IndicatorController::class, 'store'])->name('indicators.store');
        Route::get('/indicators/{id}', [IndicatorController::class, 'show'])->name('indicators.show');
        Route::post('/indicators-bobot', [IndicatorController::class, 'updateBobot'])->name('indicators.bobot.update');
        Route::post('/indicator-results/{id}/nilai', [IndicatorResultController::class, 'updateStatus'])->name('indicator-results.updateStatus');

        Route::get('/satkers', [SatkerController::class, 'index'])->name('satkers.index');
        Route::post('/satkers', [SatkerController::class, 'store'])->name('satkers.store');
        Route::delete('/satkers/{id}', [SatkerController::class, 'destroy'])->name('satkers.destroy');
        Route::get('/satkers/cetak-kredensial', [SatkerController::class, 'cetakKredensialForm'])->name('satkers.cetakKredensialForm');
        Route::post('/satkers/cetak-kredensial', [SatkerController::class, 'cetakKredensial'])->name('satkers.cetakKredensial');
        Route::get('/satkers/cetak-kredensial/hasil', [SatkerController::class, 'cetakKredensialHasil'])->name('satkers.cetakKredensialHasil');

        
        Route::get('/monitoring-ikpa', function () {
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
        
            // Notifikasi terbaru untuk admin yang sedang login
            $notifikasiTerbaru = \App\Models\Notification::where('user_id', auth()->id())
                ->latest()
                ->take(4)
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
        
            return view('admin.monitoring', compact(
                'indicators', 'satkers', 'satkerPerformance',
                'granularitas', 'periodeAktif', 'tahunAktif', 'triwulanAktif', 'semesterAktif', 'labelPeriodeAktif', 'trendRange',
                'totalSatker', 'rataRataKinerja', 'selisihBulanLalu', 'trendBulanan',
                'totalSangatBaik', 'totalBaik', 'totalCukup', 'totalKurang', 'totalPerluPerhatian',
                'persenSangatBaik', 'persenBaik', 'persenCukup', 'persenKurang', 'persenPerluPerhatian',
                'satkerPrioritas', 'nilaiPerIndikator', 'notifikasiTerbaru',
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

            return view('admin.monitoring-cetak', compact('satker', 'baris', 'rataRata', 'labelPeriodeAktif'));
        })->name('monitoring.cetak');

        Route::get('/peringatan', [\App\Http\Controllers\PeringatanSatkerController::class, 'index'])->name('peringatan.index');
        Route::post('/peringatan', [\App\Http\Controllers\PeringatanSatkerController::class, 'store'])->name('peringatan.store');
        Route::post('/peringatan/{id}/selesai', [\App\Http\Controllers\PeringatanSatkerController::class, 'selesaikan'])->name('peringatan.selesai');
        Route::delete('/peringatan/{id}', [\App\Http\Controllers\PeringatanSatkerController::class, 'destroy'])->name('peringatan.destroy');
    });

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