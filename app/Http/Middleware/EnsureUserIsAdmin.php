<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * Sebelumnya route seperti /satkers dan /indicators hanya dilindungi
     * middleware 'auth' biasa, jadi user dengan role 'satker' bisa
     * mengakses/mengubah data satker lain dan mengelola indicator lewat
     * URL langsung. Middleware ini menutup celah tersebut.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== 'admin') {
            abort(403, 'Akses ditolak. Halaman ini khusus admin.');
        }

        return $next($request);
    }
}
