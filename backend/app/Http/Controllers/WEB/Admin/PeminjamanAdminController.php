<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\User;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanAdminController extends Controller
{
// CRUD PEMINJAMAN
    // 1. Menampilkan tabel daftar transaksi peminjaman beserta peminjam & detail alat
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

    // 2. Menampilkan form tambah transaksi peminjaman baru
    public function createPeminjaman()
    {
        $users = User::where('role', 'peminjam')->get();
        $alats = Alat::where('stok', '>', 0)->get();
        return view('admin.peminjaman.create', compact('users', 'alats'));
    }

    // 3. Menyimpan data transaksi peminjaman baru + validasi stok alat
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

    // 4. Memperbarui status peminjaman (Menyetujui/Menyelesaikan + Kurang stok jika dipinjam, Tambah stok jika selesai)
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

    // 5. Menghapus data peminjaman (Kembalikan stok alat jika statusnya masih dipinjam)
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
}