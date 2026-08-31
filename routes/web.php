<?php

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
        $query = \App\Models\Indicator::with(['satker', 'results']);

        if (request()->filled('satker_id')) {
            $query->where('satker_id', request('satker_id'));
        }

        if (request()->filled('periode')) {
            $periode = \Carbon\Carbon::createFromFormat('Y-m', request('periode'));
            $query->whereYear('periode', $periode->year)->whereMonth('periode', $periode->month);
        }

        $indicators = $query->latest()
            ->get()
            ->map(function ($item) {
                $item->satker_nama = $item->satker->nama_satker ?? '-';
                $item->status = $item->results->count() > 0 ? 'terkirim' : 'pending';
                return $item;
            });

        $satkers = \App\Models\Satker::orderBy('nama_satker')->get();

        // Kinerja per satker = kombinasi progres pengerjaan (satker mengirim laporan
        // tepat waktu) DAN kualitas laporan yang sudah dinilai admin lewat kolom
        // `nilai` pada indicator_results (0-100). Sebelumnya nilai kinerja di sini
        // hanya dihitung dari rasio "jumlah dikirim / total tugas", sehingga
        // laporan asal-asalan pun bisa mendapat skor 100%. Sekarang:
        //   - progres  = seberapa banyak tugas yang sudah dikirim laporannya
        //   - kualitas = rata-rata nilai yang diberikan admin atas laporan yang SUDAH dinilai
        //   - skor akhir = 40% progres + 60% kualitas (laporan belum dinilai admin
        //     tidak ikut menaikkan/menurunkan skor kualitas, supaya tidak bias
        //     seolah-olah semua laporan otomatis "bagus")
        $periodeFilter = request('periode');
        $satkerPerformance = $satkers
            ->when(request()->filled('satker_id'), fn ($collection) => $collection->where('id', request('satker_id')))
            ->map(function ($satker) use ($periodeFilter) {
                $tugasQuery = \App\Models\Indicator::where('satker_id', $satker->id);

                if ($periodeFilter) {
                    $periode = \Carbon\Carbon::createFromFormat('Y-m', $periodeFilter);
                    $tugasQuery->whereYear('periode', $periode->year)->whereMonth('periode', $periode->month);
                }

                $totalTugas = (clone $tugasQuery)->count();
                $tugasSelesai = (clone $tugasQuery)->whereHas('results')->count();
                $progres = $totalTugas > 0 ? ($tugasSelesai / $totalTugas) * 100 : null;

                $rataKualitas = \App\Models\IndicatorResult::whereHas('indicator', function ($q) use ($satker, $periodeFilter) {
                        $q->where('satker_id', $satker->id);
                        if ($periodeFilter) {
                            $periode = \Carbon\Carbon::createFromFormat('Y-m', $periodeFilter);
                            $q->whereYear('periode', $periode->year)->whereMonth('periode', $periode->month);
                        }
                    })
                    ->whereNotNull('nilai')
                    ->avg('nilai');

                $bobotProgres = config('sikoor.bobot_progres', 0.4);
                $bobotKualitas = config('sikoor.bobot_kualitas', 0.6);

                $skorAkhir = null;
                if (! is_null($progres) && ! is_null($rataKualitas)) {
                    $skorAkhir = round(($progres * $bobotProgres) + ($rataKualitas * $bobotKualitas), 1);
                } elseif (! is_null($progres)) {
                    // Belum ada laporan yang dinilai admin sama sekali, sementara pakai progres saja
                    $skorAkhir = round($progres, 1);
                }

                $status = 'Belum ada tugas';
                if (!is_null($skorAkhir)) {
                    $ambangHijau = config('sikoor.ambang_hijau', 95);
                    $ambangKuning = config('sikoor.ambang_kuning', 89);
                    $status = $skorAkhir >= $ambangHijau ? 'Hijau' : ($skorAkhir >= $ambangKuning ? 'Kuning' : 'Merah');
                }

                return (object) [
                    'id' => $satker->id,
                    'nama_satker' => $satker->nama_satker,
                    'total_tugas' => $totalTugas,
                    'tugas_selesai' => $tugasSelesai,
                    'nilai' => $skorAkhir,
                    'status' => $status,
                ];
            })
            ->values();

        $totalSatker = $satkerPerformance->count();
        $rataRataKinerja = $satkerPerformance->whereNotNull('nilai')->avg('nilai');
        $totalPerluPerhatian = $satkerPerformance->where('status', 'Merah')->count();

        return view('admin.monitoring', compact(
            'indicators', 'satkers', 'satkerPerformance',
            'totalSatker', 'rataRataKinerja', 'totalPerluPerhatian'
        ));
    }

    return redirect()->route('user.inbox');
})->middleware(['auth'])->name('dashboard');

    Route::middleware('auth')->group(function () {
    // Upload laporan oleh satker — boleh diakses role satker, jadi tetap di luar grup 'admin'
    Route::post('/indicator/{indicator_id}/upload', [IndicatorResultController::class, 'store'])->name('indicator.upload');

    Route::middleware('admin')->group(function () {
        Route::get('/indicators', [IndicatorController::class, 'index'])->name('indicators.index');
        Route::post('/indicators', [IndicatorController::class, 'store'])->name('indicators.store');
        Route::get('/indicators/{id}', [IndicatorController::class, 'show'])->name('indicators.show');
        Route::post('/indicator-results/{id}/nilai', [IndicatorResultController::class, 'updateStatus'])->name('indicator-results.updateStatus');

        Route::get('/satkers', [SatkerController::class, 'index'])->name('satkers.index');
        Route::post('/satkers', [SatkerController::class, 'store'])->name('satkers.store');
        Route::delete('/satkers/{id}', [SatkerController::class, 'destroy'])->name('satkers.destroy');
    });

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/data', [MessageController::class, 'data'])->name('messages.data');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/online-status', [MessageController::class, 'onlineStatus'])->name('messages.onlineStatus');
    Route::get('/messages/live-status', [MessageController::class, 'liveStatus'])->name('messages.liveStatus');
    Route::post('/messages/typing', [MessageController::class, 'typing'])->name('messages.typing');
    Route::post('/messages/broadcast', [MessageController::class, 'broadcastStore'])->name('messages.broadcast');

    Route::get('/notifications/data', [NotificationController::class, 'data'])->name('notifications.data');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    Route::get('/inbox', function () {
    $indicators = \App\Models\Indicator::with('results')
        ->where('satker_id', auth()->user()->satker_id)
        ->latest()
        ->get();

    return view('user.inbox', compact('indicators'));
})->name('user.inbox');

    Route::get('/chat', function () {
        return view('user.chat');
    })->name('user.chat');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';