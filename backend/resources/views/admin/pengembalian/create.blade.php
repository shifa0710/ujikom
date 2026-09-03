@extends('layouts.app') 

@section('title', 'Manajemen Transaksi Pengembalian')
@section('header', 'Manajemen Transaksi Pengembalian')

@section('content')
<div class="p-6">
    <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <!-- Header Card -->
        <div class="p-4 border-b border-gray-100 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">Proses Pengembalian Alat</h3>
        </div>

        <div class="p-6">
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('admin.pengembalian.store') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Transaksi Peminjaman -->
                <div>
                    <label for="peminjaman_id" class="block text-sm font-medium text-gray-700 mb-1">Pilih Transaksi Peminjaman</label>
                    <select name="peminjaman_id" id="peminjaman_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm @error('peminjaman_id') border-red-500 @enderror" required>
                        <option value="">-- Pilih Transaksi Peminjaman --</option>
                        @foreach($peminjamans as $peminjaman)
                            <option value="{{ $peminjaman->id }}" {{ old('peminjaman_id') == $peminjaman->id ? 'selected' : '' }}>
                                [ID: {{ $peminjaman->id }}] {{ $peminjaman->user->name ?? 'Peminjam' }} - 
                                Alat: 
                                @foreach($peminjaman->detailPinjam as $detail)
                                    {{ $detail->alat->nama_alat ?? '' }} ({{ $detail->jumlah }} pcs), 
                                @endforeach
                                (Status: {{ ucfirst($peminjaman->status) }})
                            </option>
                        @endforeach
                    </select>
                    @error('peminjaman_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanggal Kembalikan -->
                <div>
                    <label for="tgl_kembali" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Dikembalikan</label>
                    <input type="date" name="tgl_kembali" id="tgl_kembali" value="{{ old('tgl_kembali', date('Y-m-d')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm @error('tgl_kembali') border-red-500 @enderror" required>
                    @error('tgl_kembali')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kondisi Alat -->
                <div>
                    <label for="kondisi_kembali" class="block text-sm font-medium text-gray-700 mb-1">Kondisi Alat Saat Kembali</label>
                    <input type="text" name="kondisi_kembali" id="kondisi_kembali" placeholder="Contoh: Baik / Rusak Ringan / Lengkap" value="{{ old('kondisi_kembali', 'Lengkap dan Berfungsi Baik') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm @error('kondisi_kembali') border-red-500 @enderror" required>
                    @error('kondisi_kembali')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Denda Manual -->
                <div>
                    <label for="denda" class="block text-sm font-medium text-gray-700 mb-1">Denda Total (Rp) - Manual</label>
                    <input type="number" name="denda" id="denda" placeholder="Kosongkan jika ingin dihitung otomatis" value="{{ old('denda') }}" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm @error('denda') border-red-500 @enderror">
                    <p class="text-xs text-gray-500 mt-1">*Ketik nominal jika ingin isi manual. Jika dikosongkan/0, denda akan dihitung otomatis dari tarif harian.</p>
                    @error('denda')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tarif Denda Harian (Kalkulasi Otomatis) -->
                <div>
                    <label for="denda_per_hari" class="block text-sm font-medium text-gray-700 mb-1">Tarif Denda Harian (Rp / Hari)</label>
                    <input type="number" name="denda_per_hari" id="denda_per_hari" placeholder="1000" value="{{ old('denda_per_hari', 1000) }}" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm @error('denda_per_hari') border-red-500 @enderror">
                    <p class="text-xs text-gray-500 mt-1">Digunakan jika Denda Manual dikosongkan (dikali jumlah hari keterlambatan).</p>
                    @error('denda_per_hari')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tombol Aksi -->
                <div class="flex gap-2 pt-4">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg text-sm transition shadow">
                        Simpan Pengembalian
                    </button>
                    <a href="{{ route('admin.pengembalian.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-medium px-4 py-2 rounded-lg text-sm transition">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection