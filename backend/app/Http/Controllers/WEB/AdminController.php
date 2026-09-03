<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\Kategori;
use App\Models\User;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use App\Models\Pengembalian;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AdminController extends Controller
{
    // Menampilkan Dashboard Admin & Log Aktivitas
    public function index()
    {
        $logs = LogAktivitas::with('user')->latest()->take(10)->get();
        return view('admin.dashboard', compact('logs'));
    }

    // CRUD ALAT
    // Menampilkan daftar alat
    public function indexAlat(Request $request)
    {
        $search = $request->input('search');

        $alats = Alat::with('kategori')
            ->when($search, function ($query, $search) {
                return $query->where('nama_alat', 'like', "%{$search}%")
                    ->orWhere('status_kondisi', 'like', "%{$search}%")
                    ->orWhereHas('kategori', function ($q) use ($search) {
                        $q->where('nama_kategori', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.alat.index', compact('alats', 'search'));
    }

    // Menampilkan form tambah alat
    public function createAlat()
    {
        $kategoris = Kategori::all();
        return view('admin.alat.create', compact('kategoris'));
    }

    // Menyimpan Alat Baru
    public function storeAlat(Request $request)
    {
        $request->validate([
            'nama_alat' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'stok' => 'required|integer|min:0',
            'status_kondisi' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->except('gambar');

        // Handle upload gambar jika ada menggunakan storage disk public
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('alat', $filename, 'public');
            $data['gambar'] = 'storage/alat/' . $filename;
        }

        Alat::create($data);

        // Catat Log Aktivitas
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menambahkan alat baru : ' . $request->nama_alat
        ]);

        return redirect()->route('admin.alat.index')->with('success', 'Data alat berhasil ditambahkan.');
    }

    // Menampilkan form edit alat
    public function editAlat($id)
    {
        $alat = Alat::findOrFail($id);
        $kategoris = Kategori::all();
        return view('admin.alat.edit', compact('alat', 'kategoris'));
    }

    // Memperbarui data alat
    public function updateAlat(Request $request, $id)
    {
        $alat = Alat::findOrFail($id);

        $request->validate([
            'nama_alat' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'stok' => 'required|integer|min:0',
            'status_kondisi' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->except('gambar');

        // Handle update gambar jika ada file baru
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($alat->gambar) {
                $oldPath = str_replace('storage/', '', $alat->gambar);
                Storage::disk('public')->delete($oldPath);
            }

            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('alat', $filename, 'public');
            $data['gambar'] = 'storage/alat/' . $filename;
        }

        $alat->update($data);

        // Log Aktivitas
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Memperbarui data alat: ' . $alat->nama_alat
        ]);

        return redirect()->route('admin.alat.index')->with('success', 'Data alat berhasil diperbarui.');
    }

    // Menghapus data alat
    public function destroyAlat($id)
    {
        $alat = Alat::findOrFail($id);
        $namaAlat = $alat->nama_alat; // FIX: Simpan nama alat sebelum di-delete

        // Hapus file gambar fisik dari storage jika ada
        if ($alat->gambar) {
            $oldPath = str_replace('storage/', '', $alat->gambar);
            Storage::disk('public')->delete($oldPath);
        }

        $alat->delete();

        // Log Aktivitas
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menghapus alat: ' . $namaAlat
        ]);

        return redirect()->route('admin.alat.index')->with('success', 'Data berhasil dihapus.');
    }

    // CRUD PEMINJAMAN
    // 1. Menampilkan daftar peminjaman
    public function indexPeminjaman(Request $request)
    {
        $search = $request->input('search');

        $peminjamans = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->when($search, function ($query, $search) {
                return $query->where('status', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.peminjaman.index', compact('peminjamans', 'search'));
    }

    // 2. Menampilkan form tambah peminjaman
    public function createPeminjaman()
    {
        $users = User::where('role', 'peminjam')->get();
        $alats = Alat::where('stok', '>', 0)->get();
        return view('admin.peminjaman.create', compact('users', 'alats'));
    }

    // 3. Menyimpan data peminjaman baru
    public function storePeminjaman(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'tgl_pinjam' => 'required|date',
            'tgl_kembali_plan' => 'required|date|after_or_equal:tgl_pinjam',
            'alat_id' => 'required|array',
            'alat_id.*' => 'exists:alat,id', 
            'jumlah' => 'required|array',
            'jumlah.*' => 'integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Buat transaksi utama peminjaman
            $peminjaman = Peminjaman::create([
                'user_id' => $request->user_id,
                'tgl_pinjam' => $request->tgl_pinjam,
                'tgl_kembali_plan' => $request->tgl_kembali_plan,
                'status' => 'diajukan', // Status awal
            ]);

            // Simpan detail alat yang dipinjam
            foreach ($request->alat_id as $index => $alatId) {
                $jumlahPinjam = $request->jumlah[$index];

                $alat = Alat::findOrFail($alatId);

                // Validasi stok
                if ($alat->stok < $jumlahPinjam) {
                    throw new \Exception("Stok alat '{$alat->nama_alat}' tidak mencukupi.");
                }

                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id' => $alatId,
                    'jumlah' => $jumlahPinjam,
                ]);
            }

            // Log Aktivitas
            $userPeminjam = User::find($request->user_id);
            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => 'Membuat transaksi peminjaman baru untuk user: ' . ($userPeminjam->name ?? 'User')
            ]);

            DB::commit();
            return redirect()->route('admin.peminjaman.index')->with('success', 'Data peminjaman berhasil diajukan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // 4. Memperbarui status peminjaman
    public function updateStatusPeminjaman(Request $request, $id)
    {
        $peminjaman = Peminjaman::with('detailPinjam.alat')->findOrFail($id);

        $request->validate([
            'status' => 'required|in:diajukan,dipinjam,selesai,telat',
        ]);

        DB::beginTransaction();
        try {
            $statusLama = $peminjaman->status;
            $statusBaru = $request->status;

            // Logika pengelolaan stok otomatis
            if ($statusLama != 'dipinjam' && $statusBaru == 'dipinjam') {
                foreach ($peminjaman->detailPinjam as $detail) {
                    $alat = $detail->alat;
                    if ($alat->stok < $detail->jumlah) {
                        throw new \Exception("Stok alat {$alat->nama_alat} tidak mencukupi untuk dipinjam.");
                    }
                    $alat->decrement('stok', $detail->jumlah);
                }
            } elseif ($statusLama == 'dipinjam' && ($statusBaru == 'selesai')) {
                foreach ($peminjaman->detailPinjam as $detail) {
                    if ($detail->alat) {
                        $detail->alat->increment('stok', $detail->jumlah);
                    }
                }
            }

            $peminjaman->update(['status' => $statusBaru]);

            // Log Aktivitas
            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => 'Mengubah status peminjaman ID #' . $peminjaman->id . ' menjadi ' . $statusBaru
            ]);

            DB::commit();
            return redirect()->route('admin.peminjaman.index')->with('success', 'Status peminjaman berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    // 5. Menghapus data peminjaman
    public function destroyPeminjaman($id)
    {
        $peminjaman = Peminjaman::with('detailPinjam.alat')->findOrFail($id);
        $peminjamanId = $peminjaman->id; // FIX: Ambil ID peminjaman ke variabel sebelum delete

        // Jika statusnya sedang dipinjam, kembalikan stok terlebih dahulu sebelum dihapus
        if ($peminjaman->status == 'dipinjam') {
            foreach ($peminjaman->detailPinjam as $detail) {
                if ($detail->alat) {
                    $detail->alat->increment('stok', $detail->jumlah);
                }
            }
        }

        $peminjaman->delete();

        // Log Aktivitas
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menghapus data peminjaman ID #' . $peminjamanId
        ]);

        return redirect()->route('admin.peminjaman.index')->with('success', 'Data peminjaman berhasil dihapus.');
    }

    // CRUD PENGEMBALIAN
    // 1. Menampilkan daftar pengembalian
    public function indexPengembalian(Request $request)
    {
        $search = $request->input('search');

        $pengembalians = Pengembalian::with(['peminjaman.user', 'peminjaman.detailPinjam.alat', 'petugas'])
            ->when($search, function ($query, $search) {
                return $query->whereHas('peminjaman.user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.pengembalian.index', compact('pengembalians', 'search'));
    }

    // 2. Menampilkan form pengembalian
    public function createPengembalian()
    {
        $peminjamans = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->whereIn('status', ['dipinjam', 'telat'])
            ->get();

        return view('admin.pengembalian.create', compact('peminjamans'));
    }

    // 3. Menyimpan data pengembalian & mengembalikan stok otomatis
    public function storePengembalian(Request $request)
    {
        $request->validate([
            'peminjaman_id'   => 'required|exists:peminjaman,id',
            'tgl_kembali'     => 'required|date',
            'kondisi_kembali' => 'required|string|max:255',
            'denda'           => 'nullable|numeric|min:0',
            'tarif_harian'    => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($request->peminjaman_id);
            
            $totalDenda = 0;

            if ($request->filled('denda') && $request->denda > 0) {
                $totalDenda = $request->denda;
            } else {
                $tglRencana = Carbon::parse($peminjaman->tgl_kembali_plan ?? $peminjaman->tgl_kembali)->startOfDay();
                $tglKembali = Carbon::parse($request->tgl_kembali)->startOfDay();
                
                $tarifHarian = $request->tarif_harian ?? 1000;

                if ($tglKembali->greaterThan($tglRencana)) {
                    $selisihHari = $tglRencana->diffInDays($tglKembali);
                    $totalDenda  = $selisihHari * $tarifHarian;
                }
            }

            Pengembalian::create([
                'peminjaman_id'   => $request->peminjaman_id,
                'tgl_kembali'     => $request->tgl_kembali,
                'kondisi_kembali' => $request->kondisi_kembali,
                'denda'           => $totalDenda,
                'petugas_id'      => auth()->id(),
            ]);

            foreach ($peminjaman->detailPinjam as $detail) {
                if ($detail->alat) {
                    $detail->alat->increment('stok', $detail->jumlah);
                }
            }

            $peminjaman->update(['status' => 'selesai']);

            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => 'Memproses pengembalian peminjaman ID #' . $peminjaman->id
            ]);

            DB::commit();
            return redirect()->route('admin.pengembalian.index')
                ->with('success', 'Pengembalian berhasil diproses. Total denda: Rp ' . number_format($totalDenda, 0, ',', '.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // 4. Menghapus data pengembalian
    public function destroyPengembalian($id)
    {
        $pengembalian = Pengembalian::findOrFail($id);
        $pengembalianId = $pengembalian->id;
        
        $pengembalian->delete();

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menghapus data pengembalian ID #' . $pengembalianId
        ]);

        return redirect()->route('admin.pengembalian.index')->with('success', 'Data pengembalian berhasil dihapus.');
    }

    // CRUD USER
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

    public function createUser()
    {
        return view('admin.user.create');
    }

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

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        return view('admin.user.edit', compact('user'));
    }

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

    public function destroyUser($id)
    {
        $user = User::findOrFail($id);
        $namaUser = $user->name; // FIX: Simpan nama user sebelum di-delete

        $user->delete();

        // Log Aktivitas
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menghapus user: ' . $namaUser
        ]);

        return redirect()->route('admin.user.index')->with('success', 'User berhasil dihapus.');
    }

    // CRUD KATEGORI
    public function indexKategori(Request $request)
    {
        $search = $request->input('search');

        $kategoris = Kategori::when($search, function ($query, $search) {
            return $query->where('nama_kategori', 'like', "%{$search}%");
        })
        ->latest()
        ->paginate(5)
        ->withQueryString();

        return view('admin.kategori.index', compact('kategoris', 'search'));
    }

    public function createKategori()
    {
        return view('admin.kategori.create');
    }

    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori',
        ]);

        Kategori::create([
            'nama_kategori' => $request->nama_kategori,
        ]);

        // Log Aktivitas
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menambahkan kategori baru: ' . $request->nama_kategori
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function editKategori($id)
    {
        $kategori = Kategori::findOrFail($id);
        return view('admin.kategori.edit', compact('kategori'));
    }

    public function updateKategori(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);

        $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori,' . $id,
        ]);

        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
        ]);

        // Log Aktivitas
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Memperbarui kategori: ' . $request->nama_kategori
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroyKategori($id)
    {
        $kategori = Kategori::findOrFail($id);
        $namaKategori = $kategori->nama_kategori; // FIX: Simpan nama kategori sebelum di-delete

        if ($kategori->alats()->count() > 0) {
            return redirect()->route('admin.kategori.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh data alat.');
        }

        $kategori->delete();

        // Log Aktivitas
        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menghapus kategori: ' . $namaKategori
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil dihapus.');
    }
}