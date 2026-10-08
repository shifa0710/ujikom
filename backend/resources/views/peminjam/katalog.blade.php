@extends('layouts.app')

@section('title', 'Katalog Alat - Sistem Peminjaman')
@section('header-title', 'Katalog Peralatan Laboratorium')

@section('content')
    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-lg shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Tampilkan Error Validasi Laravel jika ada -->
    @if ($errors->any())
        <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-lg shadow-sm">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

   <form action="/peminjam/peminjaman/ajukan" method="POST" id="formPeminjaman">
        @csrf

        <!-- Card Input Rencana Tanggal Kembali -->
        <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Rencana Tanggal Pengembalian</label>
            <input type="date" name="tgl_kembali_plan" class="w-full md:w-1/3 p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm" min="{{ date('Y-m-d') }}" required>
        </div>

        <!-- Tabel Katalog Alat -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
            <div class="p-5 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">Daftar Alat Tersedia</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                            <th class="py-3 px-4 border-b text-center" width="60">Pilih</th>
                            <th class="py-3 px-4 border-b">Nama Alat</th>
                            <th class="py-3 px-4 border-b">Kategori</th>
                            <th class="py-3 px-4 border-b text-center">Stok</th>
                            <th class="py-3 px-4 border-b" width="150">Jumlah Pinjam</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        @forelse($alats as $alat)
                            <tr class="hover:bg-gray-50 transition border-b border-gray-100">
                                <td class="py-3 px-4 text-center">
                                    <input type="checkbox" name="alat_id[]" value="{{ $alat->id }}" class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 check-alat">
                                </td>
                                <td class="py-3 px-4 font-medium text-gray-900">{{ $alat->nama_alat }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">
                                        {{ $alat->kategori->nama_kategori ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full {{ $alat->stok > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $alat->stok }} Unit
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <input type="number" name="jumlah[{{ $alat->id }}]" value="1" min="1" max="{{ $alat->stok }}" class="w-full p-1.5 border border-gray-300 rounded focus:ring-emerald-500 focus:border-emerald-500 text-center">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">Belum ada peralatan yang tersedia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-gray-50 border-t border-gray-200 text-right">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm transition">
                    Ajukan Peminjaman
                </button>
            </div>
        </div>
    </form>

    <script>
        document.getElementById('formPeminjaman').addEventListener('submit', function(e) {
            // Memakai class .check-alat agar tidak error saat checking selector array
            const checkboxes = document.querySelectorAll('.check-alat:checked');
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('Silakan centang minimal satu alat yang ingin dipinjam terlebih dahulu!');
            }
        });
    </script>
@endsection