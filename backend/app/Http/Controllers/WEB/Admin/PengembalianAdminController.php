<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PengembalianAdminController extends Controller
{
// CRUD PENGEMBALIAN
    // 1. Menampilkan tabel daftar riwayat pengembalian barang
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

    // 2. Menampilkan form pengembalian alat
    public function createPengembalian()
    {
        $peminjamans = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->whereIn('status', ['dipinjam', 'telat'])
            ->get();

        return view('admin.pengembalian.create', compact('peminjamans'));
    }

    // 3. Menyimpan data transaksi pengembalian + hitung denda otomatis + kembalikan stok alat
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

    // 4. Menghapus data pengembalian dari database
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
}