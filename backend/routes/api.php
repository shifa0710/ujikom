<?php

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
        Route::get('/katalog', [AlatController::class, 'katalog']);
        // Route untuk hak akses admin
    });

    //Hanya Petugas
    Route::middleware('role.petugas')->group(function () {
        // Route untuk hak akses petugas
    });
    
    //Hanya Peminjam
    Route::middleware('role.peminjam')->group(function () {
        Route::get('/katalog', [AlatController::class, 'katalog']);
        // Route untuk hak akses peminjam
    });
});