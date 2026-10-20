<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WEB\AuthController;
use App\Http\Controllers\WEB\PetugasController;
use App\Http\Controllers\WEB\PeminjamController;

use App\Http\Controllers\WEB\Admin\DashboardController;
use App\Http\Controllers\WEB\Admin\UserController;
use App\Http\Controllers\WEB\Admin\KategoriController;
use App\Http\Controllers\WEB\Admin\AlatController;
use App\Http\Controllers\WEB\Admin\PeminjamanAdminController;
use App\Http\Controllers\WEB\Admin\PengembalianAdminController;

// Halaman Utama Aplikasi
Route::get('/', function () {
    return view('welcome');
});

// Route Tamu (Hanya bisa dibuka oleh user yang BELUM LOGIN)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Route Khusus ADMIN (Diproteksi Login & Role Admin)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard Admin
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // CRUD User
    Route::get('/users', [UserController::class, 'indexUser'])->name('user.index');
    Route::get('/users/create', [UserController::class, 'createUser'])->name('user.create');
    Route::post('/users', [UserController::class, 'storeUsers'])->name('user.store');
    Route::get('/users/{id}/edit', [UserController::class, 'editUser'])->name('user.edit');
    Route::put('/users/{id}', [UserController::class, 'updateUser'])->name('user.update');
    Route::delete('/users/{id}', [UserController::class, 'destroyUser'])->name('user.destroy');

    // CRUD Kategori
    Route::get('/kategori', [KategoriController::class, 'indexKategori'])->name('kategori.index');
    Route::get('/kategori/create', [KategoriController::class, 'createKategori'])->name('kategori.create');
    Route::post('/kategori', [KategoriController::class, 'storeKategori'])->name('kategori.store');
    Route::get('/kategori/{id}/edit', [KategoriController::class, 'editKategori'])->name('kategori.edit');
    Route::put('/kategori/{id}', [KategoriController::class, 'updateKategori'])->name('kategori.update');
    Route::delete('/kategori/{id}', [KategoriController::class, 'destroyKategori'])->name('kategori.destroy');

    // CRUD Alat
    Route::get('/alat', [AlatController::class, 'indexAlat'])->name('alat.index');
    Route::get('/alat/create', [AlatController::class, 'createAlat'])->name('alat.create');
    Route::post('/alat', [AlatController::class, 'storeAlat'])->name('alat.store');
    Route::get('/alat/{id}/edit', [AlatController::class, 'editAlat'])->name('alat.edit');
    Route::put('/alat/{id}', [AlatController::class, 'updateAlat'])->name('alat.update');
    Route::delete('/alat/{id}', [AlatController::class, 'destroyAlat'])->name('alat.destroy');

    // CRUD Peminjaman
    Route::get('/peminjaman', [PeminjamanAdminController::class, 'indexPeminjaman'])->name('peminjaman.index');
    Route::get('/peminjaman/create', [PeminjamanAdminController::class, 'createPeminjaman'])->name('peminjaman.create');
    Route::post('/peminjaman', [PeminjamanAdminController::class, 'storePeminjaman'])->name('peminjaman.store');
    Route::put('/peminjaman/{id}/status', [PeminjamanAdminController::class, 'updateStatusPeminjaman'])->name('peminjaman.updateStatus');
    Route::delete('/peminjaman/{id}', [PeminjamanAdminController::class, 'destroyPeminjaman'])->name('peminjaman.destroy');

    // CRUD Pengembalian
    Route::get('/pengembalian', [PengembalianAdminController::class, 'indexPengembalian'])->name('pengembalian.index');
    Route::get('/pengembalian/create', [PengembalianAdminController::class, 'createPengembalian'])->name('pengembalian.create');
    Route::post('/pengembalian', [PengembalianAdminController::class, 'storePengembalian'])->name('pengembalian.store');
    Route::delete('/pengembalian/{id}', [PengembalianAdminController::class, 'destroyPengembalian'])->name('pengembalian.destroy');
});

// Route Khusus PETUGAS (Diproteksi Login & Role Petugas/Admin)
Route::middleware(['auth', 'role:petugas,admin'])->prefix('petugas')->name('petugas.')->group(function () {
    // Peminjaman & Persetujuan
    Route::get('/peminjaman', [PetugasController::class, 'indexPeminjaman'])->name('peminjaman.index');
    Route::post('/peminjaman/{id}/setujui', [PetugasController::class, 'setujuiPeminjaman'])->name('peminjaman.setujui');
    Route::post('/peminjaman/{id}/tolak', [PetugasController::class, 'tolakPeminjaman'])->name('peminjaman.tolak');

    // Pengembalian 
    Route::get('/pengembalian', [PetugasController::class, 'indexPengembalian'])->name('pengembalian.index');
    Route::post('/pengembalian/{id}', [PetugasController::class, 'prosesPengembalian'])->name('pengembalian.proses');

    // Pencetakan Laporan
    Route::get('/laporan', [PetugasController::class, 'laporan'])->name('laporan.index');
    Route::get('/laporan/cetak', [PetugasController::class, 'cetakLaporan'])->name('laporan.cetak');
});

// Route Khusus PEMINJAM / SISWA (Diproteksi Login & Role Peminjam)
Route::middleware(['auth', 'role:peminjam'])->prefix('peminjam')->name('peminjam.')->group(function () {
    // Katalog & Pengajuan Peminjaman
    Route::get('/katalog', [PeminjamController::class, 'katalogAlat'])->name('katalog');
    Route::post('/peminjaman/ajukan', [PeminjamController::class, 'ajukanPeminjaman'])->name('peminjaman.ajukan');
    Route::get('/riwayat', [PeminjamController::class, 'riwayatPeminjaman'])->name('riwayat');
});

// Route Logout (Hanya untuk User yang SUDAH LOGIN)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');