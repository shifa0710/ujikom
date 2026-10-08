<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsPeminjam
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Cek apakah user sudah login
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 2. Cek role user (gunakan strtolower untuk menghindari beda kapital misal 'Peminjam' / 'peminjam')
        if (strtolower(auth()->user()->role) !== 'peminjam') {
            abort(403, 'Akses ditolak. Anda bukan Peminjam.');
        }

        return $next($request);
    }
}