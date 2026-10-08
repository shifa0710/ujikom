@extends('layouts.app')

@section('title', 'Riwayat Peminjaman - Sistem Peminjaman')
@section('header-title', 'Riwayat Peminjaman Saya')

@section('content')
    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tabel Riwayat Peminjaman -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="p-5 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Daftar Pengajuan & Peminjaman</h3>
            <a href="{{ route('peminjam.katalog') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                + Pinjam Alat Baru
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">No</th>
                        <th class="py-3 px-4 border-b">Tanggal Pinjam</th>
                        <th class="py-3 px-4 border-b">Rencana Kembali</th>
                        <th class="py-3 px-4 border-b">Daftar Alat</th>
                        <th class="py-3 px-4 border-b text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse($peminjamans as $index => $item)
                        <tr class="hover:bg-gray-50 transition border-b border-gray-100">
                            <td class="py-3 px-4 font-medium text-gray-900">{{ $index + 1 }}</td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($item->tgl_pinjam)->translatedFormat('d F Y') }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($item->tgl_kembali_plan)->translatedFormat('d F Y') }}
                            </td>
                            <td class="py-3 px-4">
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($item->detailPinjam as $detail)
                                        <li>
                                            <span class="font-medium text-gray-800">{{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}</span> 
                                            <span class="text-xs text-gray-500">({{ $detail->jumlah }} unit)</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($item->status === 'menunggu' || $item->status === 'pending')
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-800">
                                        Menunggu Persetujuan
                                    </span>
                                @elseif($item->status === 'disetujui' || $item->status === 'dipinjam')
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-blue-100 text-blue-800">
                                        Sedang Dipinjam
                                    </span>
                                @elseif($item->status === 'dikembalikan')
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800">
                                        Sudah Dikembalikan
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-rose-100 text-rose-800">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-gray-500">
                                Kamu belum pernah mengajukan peminjaman alat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection