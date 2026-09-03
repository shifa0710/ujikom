<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// 1. Import semua middleware yang dipakai
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsPetugas;
use App\Http\Middleware\IsPeminjam;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'          => CheckRole::class,
            'IsAdmin'       => IsAdmin::class,
            'IsPetugas'     => IsPetugas::class,
            'IsPeminjam'    => IsPeminjam::class,
            'role.admin'    => IsAdmin::class,
            'role.petugas'  => IsPetugas::class,
            'role.peminjam' => IsPeminjam::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();