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

        // Kinerja per satker = progres pengerjaan tugas
        // (jumlah indicator yang sudah dikirim laporannya / total indicator yang ditugaskan ke satker itu),
        // mengikuti filter periode di atas.
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

                $progres = $totalTugas > 0 ? round(($tugasSelesai / $totalTugas) * 100, 1) : null;

                $status = 'Belum ada tugas';
                if (!is_null($progres)) {
                    $status = $progres >= 85 ? 'Baik' : ($progres >= 60 ? 'Cukup' : 'Perlu Perhatian');
                }

                return (object) [
                    'id' => $satker->id,
                    'nama_satker' => $satker->nama_satker,
                    'total_tugas' => $totalTugas,
                    'tugas_selesai' => $tugasSelesai,
                    'nilai' => $progres,
                    'status' => $status,
                ];
            })
            ->values();

        $totalSatker = $satkerPerformance->count();
        $rataRataKinerja = $satkerPerformance->whereNotNull('nilai')->avg('nilai');
        $totalPerluPerhatian = $satkerPerformance->where('status', 'Perlu Perhatian')->count();

        return view('admin.monitoring', compact(
            'indicators', 'satkers', 'satkerPerformance',
            'totalSatker', 'rataRataKinerja', 'totalPerluPerhatian'
        ));
    }

    return redirect()->route('user.inbox');
})->middleware(['auth'])->name('dashboard');

    Route::middleware('auth')->group(function () {
    Route::get('/indicators', [IndicatorController::class, 'index'])->name('indicators.index');
    Route::post('/indicators', [IndicatorController::class, 'store'])->name('indicators.store');

    Route::post('/indicator/{indicator_id}/upload', [IndicatorResultController::class, 'store'])->name('indicator.upload');
    Route::get('/indicators/{id}', [IndicatorController::class, 'show'])->name('indicators.show');
    
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/data', [MessageController::class, 'data'])->name('messages.data');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');

    Route::get('/satkers', [SatkerController::class, 'index'])->name('satkers.index');
    Route::post('/satkers', [SatkerController::class, 'store'])->name('satkers.store');
    Route::delete('/satkers/{id}', [SatkerController::class, 'destroy'])->name('satkers.destroy');

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