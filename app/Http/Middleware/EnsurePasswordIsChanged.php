<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Kalau satker login dan belum pernah ganti password sendiri (akun baru
     * dibuat admin, atau baru saja direset lewat "cetak kredensial"), paksa
     * ke halaman ganti password dulu sebelum bisa akses halaman lain.
     *
     * Route ganti password itu sendiri (& logout) sengaja dikecualikan supaya
     * tidak terjadi redirect loop.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $rutePengecualian = [
            'password.paksa.ganti',
            'password.update', // form ganti password itu sendiri (dipakai ulang dari halaman ini)
            'logout',
        ];

        if ($user && $user->mustChangePassword() && ! $request->routeIs($rutePengecualian)) {
            return redirect()->route('password.paksa.ganti');
        }

        return $next($request);
    }
}
