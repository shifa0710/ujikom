<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Menampilkan Form Halaman Login
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Function untuk memproses autentikasi login user
    public function login(Request $request)
    {
        // 1. Validasi Input: Memastikan email/username & password wajib diisi
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

    // 2. Logika Pengecekan Keamanan (Auth::attempt): Mencocokkan data input dengan database
        if (Auth::attempt($credentials)) {

    // 3. Ganti Sesi (session()->regenerate): Mencegah serangan Session Fixation demi keamanan    
            $request->session()->regenerate();

            $user = Auth::user();

    // 4. Logika Redirect (Pengarahan Halaman) Berdasarkan Role
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            if ($user->role === 'petugas') {
                return redirect()->route('petugas.peminjaman.index');
            }
            if ($user->role === 'peminjam') {
                return redirect()->route('peminjam.katalog');
            }

            // Pengaman tambahan jika role tidak terdaftar di sistem
            Auth::logout();
            return redirect()->route('login')->with('error', 'Role tidak dikenal.');
        }

    // 5. Jika Email atau Password Salah, kembalikan ke form login beserta pesan error
        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    // Proses Logout, menghapus sesi, dan memperbarui token CSRF
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}