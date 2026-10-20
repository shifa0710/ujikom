<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;

class DashboardController extends Controller
{
    // Menampilkan halaman utama dashboard Admin & 10 riwayat/log aktivitas terbaru
    public function index()
    {
        $logs = LogAktivitas::with('user')->latest()->take(10)->get();
        return view('admin.dashboard', compact('logs'));
    }
}