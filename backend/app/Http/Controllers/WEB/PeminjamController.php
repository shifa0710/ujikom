<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamController extends Controller
{
    // Melihat daftar/katalog alat yang tersedia
    public function katalogAlat()
    {
        $alats = Alat::with('kategori')->where('stok', '>', 0)->get();
        return view('peminjam.katalog', compact('alats'));
    }

    public function ajukanPeminjaman(Request $request)
    {
        // 1. Validasi input dengan pesan penjelas
        $request->validate([
            'tgl_kembali_plan' => 'required|date',
            'alat_id'          => 'required|array|min:1',
            'jumlah'           => 'required|array',
        ], [
            'tgl_kembali_plan.required' => 'Tanggal rencana pengembalian wajib diisi.',
            'alat_id.required'          => 'Pilih minimal satu alat untuk dipinjam.',
            'alat_id.min'               => 'Pilih minimal satu alat untuk dipinjam.',
        ]);

        DB::beginTransaction();
        try {
            // 2. Buat header peminjaman
            $peminjaman = Peminjaman::create([
                'user_id'          => auth()->id(),
                'tgl_pinjam'       => now()->toDateString(),
                'tgl_kembali_plan' => $request->tgl_kembali_plan,
                'status'           => 'diajukan',
            ]);

            // 3. Masukkan daftar alat yang dipinjam ke detail_pinjam
            foreach ($request->alat_id as $alatId) {
                $jumlahPinjam = $request->jumlah[$alatId] ?? 1;

                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id'       => $alatId,
                    'jumlah'        => $jumlahPinjam,
                ]);
            }

            DB::commit();

            // Direct khusus menggunakan route name lengkap
            return redirect()->route('peminjam.riwayat')->with('success', 'Peminjaman berhasil diajukan!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal mengajukan peminjaman: ' . $e->getMessage());
        }
    }

    // Melihat riwayat peminjaman user yang sedang login
    public function riwayatPeminjaman()
    {
        $peminjamans = Peminjaman::with('detailPinjam.alat')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('peminjam.riwayat', compact('peminjamans'));
    }
}