<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
// CRUD USER
    // 1. Menampilkan tabel daftar seluruh user (bisa pencarian name, email, role)
    public function indexUser(Request $request)
    {
        $search = $request->input('search');

        $users = User::when($search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
        })
        ->latest()
        ->paginate(10)
        ->withQueryString();

        return view('admin.user.index', compact('users', 'search'));
    }

    // 2. Menampilkan form untuk tambah user baru
    public function createUser()
    {
        return view('admin.user.create');
    }

    // 3. Memproses dan menyimpan data user baru ke database + Enkripsi Password (Hash)
    public function storeUsers(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,petugas,peminjam',
        ]);

        // FIX: Menyimpan hasil create ke variabel $user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'no_hp' => $request->no_hp,
        ]);

        // Log Aktivitas
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menambahkan user baru: ' . $user->name . ' (' . $user->role . ')'
        ]);

        return redirect()->route('admin.user.index')->with('success', 'User berhasil ditambahkan.');
    }

    // 4. Menampilkan form edit data user berdasarkan ID
    public function editUser($id)
    {
        $user = User::findOrFail($id);
        return view('admin.user.edit', compact('user'));
    }

    // 5. Simpan perubahan data user ke database
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'required|in:admin,petugas,peminjam',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'no_hp' => $request->no_hp,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Log Aktivitas
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Memperbarui data user: ' . $user->name
        ]);

        return redirect()->route('admin.user.index')->with('success', 'Data user berhasil diperbarui.');
    }

    // 6. Hapus user dari database berdasarkan ID
    public function destroyUser($id)
    {
        // 1. Cari user berdasarkan ID
        $user = User::findOrFail($id);
        
        $namaUser = $user->name; // Simpan nama user sebelum di-delete

        // 2. Hapus data user dari database
        $user->delete();

        // 3. Catat transaksi ke Log Aktivitas
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menghapus user: ' . $namaUser
        ]);

        // 4. Redirect kembali ke halaman index user dengan pesan sukses
        return redirect()->route('admin.user.index')->with('success', 'User berhasil dihapus.');
    }
}