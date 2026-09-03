@extends('layouts.app')
@section('title', 'Kelola Pengembalian - Panel Admin')
@section('header-title', 'Manajemen Transaksi Pengembalian')

@section('content')
@if(session('success'))
    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm text-sm">
        {{ session('success') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
    <div class="p-5 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
        <h3 class="text-lg font-bold text-gray-800">Riwayat Pengembalian Alat</h3>
        <div class="flex items-center gap-3 w-full md:w-auto">
            <!-- Form Search -->
            <form action="{{ route('admin.pengembalian.index') }}" method="GET" class="flex w-full md:w-80">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari peminjam / kondisi..."
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-r-lg transition">
                    Cari
                </button>
            </form>

            <a href="{{ route('admin.pengembalian.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap">
                + Proses Pengembalian
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                    <th class="py-3 px-4 border-b">Peminjam</th>
                    <th class="py-3 px-4 border-b">Alat Dikembalikan</th>
                    <th class="py-3 px-4 border-b">Tgl Kembali</th>
                    <th class="py-3 px-4 border-b">Kondisi</th>
                    <th class="py-3 px-4 border-b">Denda</th>
                    <th class="py-3 px-4 border-b">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm">
                @forelse($pengembalians as $pengembalian)
                <tr class="hover:bg-gray-50 transition align-top">
                    <td class="py-3 px-4 border-b font-medium text-gray-900">
                        {{ $pengembalian->peminjaman->user->name ?? 'User Dihapus' }}
                    </td>
                    <td class="py-3 px-4 border-b">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($pengembalian->peminjaman->detailPinjam as $detail)
                                <li>
                                    <span class="font-semibold">{{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}</span>
                                    <span class="text-xs bg-gray-200 px-1.5 py-0.5 rounded">({{ $detail->jumlah }} pcs)</span>
                                </li>
                            @endforeach
                        </ul>
                    </td>
                    <td class="py-3 px-4 border-b text-xs text-gray-600">
                        {{ $pengembalian->tgl_kembali }}
                    </td>
                    <td class="py-3 px-4 border-b">
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ $pengembalian->kondisi_kembali }}
                        </span>
                    </td>
                    <td class="py-3 px-4 border-b font-semibold text-red-600">
                        Rp {{ number_format($pengembalian->denda, 0, ',', '.') }}
                    </td>
                    <td class="py-3 px-4 border-b">
                        <form action="{{ route('admin.pengembalian.destroy', $pengembalian->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus riwayat pengembalian ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-semibold transition">
                                Hapus
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-4 text-center text-gray-500">Belum ada data pengembalian.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-200 bg-gray-50">
        {{ $pengembalians->links() }}
    </div>
</div>
@endsection