<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Menampilkan Form Halaman Login
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Menampilkan Form Halaman Register
    public function showRegister()
    {
        return view('auth.register');
    }

    // Function untuk memproses autentikasi login user
    public function login(Request $request)
    {
        // 1. Validasi Input: Memastikan email & password wajib diisi
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

    // Menampilkan Form Halaman Register
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    // Function untuk memproses pendaftaran user baru
    public function register(Request $request)
    {
        // Validasi data input dari form register
        $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'no_telepon' => ['required', 'string', 'max:15'],
            'password'   => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        // Simpan data ke database tabel users
        User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'no_telepon' => $request->no_hp,
            'password'   => Hash::make($request->password), // Password wajib di-hash demi keamanan
            'role'       => 'peminjam', // Default role otomatis set ke 'peminjam'
        ]);

        // Setelah berhasil register, arahkan ke halaman login dengan pesan sukses
        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan login.');
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