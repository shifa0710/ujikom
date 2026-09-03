<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
        
        if ($request->user() && $request->user()->role === 'admin') {
            return $next($request);
        }
        return response()->json(['message' => 'Akses ditolak. Anda bukan Admin.'], 403);

    }
}