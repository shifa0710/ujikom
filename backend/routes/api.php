<?php

use App\Http\Controllers\API\PengembalianController;
use App\Http\Controllers\API\PeminjamanController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AlatController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\LogAktivitasController;
use App\Http\Controllers\API\KategoriController;

// Public Routes (Tidak perlu token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes (Wajib membawa Bearer Token dari Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/log-aktivitas', [LogAktivitasController::class, 'index']);
    
    Route::apiResource('kategori', KategoriController::class);

    // Hanya Admin
    Route::middleware('role.admin')->group(function () {
        Route::apiResource('alat', AlatController::class);
        Route::apiResource('users', UserController::class);
        Route::get('/laporan-peminjaman', [LaporanController::class, 'index']);
        Route::get('/log-aktivitas', [LogAktivitasController::class, 'index']);
        Route::get('/katalog', [AlatController::class, 'katalog']);
        Route::get('/peminjaman', [PeminjamanController::class, 'index']);
        Route::get('/peminjaman/{peminjaman}', [PeminjamanController::class,'show']);
        Route::post('/peminjaman/{peminjaman}/approve', [PeminjamanController::class, 'approve']);
        Route::put('/peminjaman/{peminjaman}', [PeminjamanController::class,'update']);
        Route::delete('/peminjaman/{peminjaman}', [PeminjamanController::class,'destroy']);
        Route::get('/pengembalian', [PengembalianController::class, 'index']);
        Route::get('/pengembalian/{pengembalian}', [PengembalianController::class,'show']);
        Route::put('/pengembalian/{pengembalian}', [PengembalianController::class,'update']);
        Route::delete('/pengembalian/{pengembalian}', [PengembalianController::class, 'destroy']);
        // Route untuk hak akses admin
    });

    //Hanya Petugas
    Route::middleware('role.petugas')->group(function () {
        Route::post('/peminjaman/{peminjaman}/approve', [PeminjamanController::class, 'approve']);
        Route::post('/pengembalian', [PengembalianController::class, 'store']);
        // Route untuk hak akses petugas
    });
    
    //Hanya Peminjam
    Route::middleware('role.peminjam')->group(function () {
        Route::post('/peminjaman', [PeminjamanController::class, 'store']);
        Route::get('/riwayat-pinjam', [PeminjamanController::class, 'riwayat']);
        Route::post('/peminjam/peminjaman', [App\Http\Controllers\WEB\PeminjamController::class, 'ajukanPeminjaman'])->name('peminjam.peminjaman.ajukan');
        Route::get('/katalog', [AlatController::class, 'katalog']);
    // Route untuk hak akses peminjam
    });
});