<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Menampilkan Form Login
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // 1. Validasi Input (Email/Username & Password tidak boleh kosong)
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

    // 2. Logika Pengecekan Keamanan (Auth::attempt)
        if (Auth::attempt($credentials)) {

    // 3. Ganti Sesi (session()->regenerate)    
            $request->session()->regenerate();

            $user = Auth::user();

    // 4. Logika Redirect Berdasarkan Role
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            if ($user->role === 'petugas') {
                return redirect()->route('petugas.peminjaman.index');
            }
            if ($user->role === 'peminjam') {
                return redirect()->route('peminjam.katalog');
            }

            Auth::logout();
            return redirect()->route('login')->with('error', 'Role tidak dikenal.');
        }

    // 5. Jika Email atau Password Salah
        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    // Proses Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}