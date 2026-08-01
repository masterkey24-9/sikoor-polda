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
        $indicators = \App\Models\Indicator::with(['satker', 'results'])
            ->latest()
            ->get()
            ->map(function ($item) {
                $item->satker_nama = $item->satker->nama_satker ?? '-';
                $item->status = $item->results->count() > 0 ? 'terkirim' : 'pending';
                return $item;
            });

        return view('admin.monitoring', compact('indicators'));
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