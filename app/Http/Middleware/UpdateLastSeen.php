<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateLastSeen
{
    /**
     * Catat waktu aktivitas terakhir user yang sedang login.
     *
     * Dipanggil di tiap request halaman web (termasuk polling AJAX chat),
     * jadi selama tab chat terbuka & polling jalan, user tetap tercatat "online".
     * Dipakai untuk fitur status online & "terakhir online" di Live chat.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            // saveQuietly: update langsung tanpa memicu event model (biar ringan, dipanggil tiap request)
            Auth::user()->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
