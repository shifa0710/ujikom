<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kategori\StoreKategoriRequest;
use App\Http\Requests\Kategori\UpdateKategoriRequest;
use App\Http\Resources\KategoriResource;
use App\Models\Kategori;
use Illuminate\Http\JsonResponse;

class KategoriController extends Controller
{
    public function index(): JsonResponse
    {
        $kategori = Kategori::latest()->get();
        return response()->json([
            'message' =>'Daftar kategori berhasil diambil.',
            'data' => KategoriResource::collection($kategori)
        ]);
    }

        public function store(\Illuminate\Http\Request $request): JsonResponse
    {
        $kategori = Kategori::create([
            'nama_kategori' => $request->nama_kategori
        ]);

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan.',
            'data' => new KategoriResource($kategori)
        ], 201);
    }

    public function show(Kategori $kategori): JsonResponse
    {
        return response()->json([
            'data' => new KategoriResource($kategori)
        ]);
    }

    public function update(\Illuminate\Http\Request $request, Kategori $kategori): JsonResponse
    {
        $kategori->update([
            'nama_kategori' => $request->nama_kategori
        ]);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data' => new KategoriResource($kategori)
        ], 200);
    }

    public function destroy(kategori $kategori): JsonResponse
    {
        $kategori->delete();
        return response()->json([
            'message' => 'Kategori berhasil dihapus.'
        ]);
    }
}