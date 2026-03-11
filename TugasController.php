<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tugas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TugasController extends Controller
{
    // GET /api/tugas
    public function index(Request $request): JsonResponse
    {
        $query = Tugas::where('user_id', Auth::id());

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('prioritas')) $query->where('prioritas', $request->prioritas);
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($q2) => $q2->where('judul', 'like', "%$q%")->orWhere('mapel', 'like', "%$q%"));
        }

        $tugas = $query->orderByRaw("FIELD(status,'belum','sedang','selesai')")
                       ->orderBy('deadline')
                       ->get();

        $selesai = $tugas->where('status', 'selesai')->count();

        return response()->json([
            'status'  => true,
            'message' => 'Data tugas berhasil diambil',
            'total'   => $tugas->count(),
            'summary' => [
                'belum'   => $tugas->where('status', 'belum')->count(),
                'sedang'  => $tugas->where('status', 'sedang')->count(),
                'selesai' => $selesai,
                'progress'=> $tugas->count() ? round($selesai / $tugas->count() * 100) : 0,
            ],
            'data' => $tugas,
        ]);
    }

    // POST /api/tugas
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'judul'     => 'required|string|max:200',
            'mapel'     => 'required|string|max:100',
            'deadline'  => 'required|date',
            'prioritas' => 'required|in:rendah,sedang,tinggi',
            'status'    => 'in:belum,sedang,selesai',
            'deskripsi' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $tugas = Tugas::create(array_merge(
            $validator->validated(),
            ['user_id' => Auth::id(), 'status' => $request->status ?? 'belum']
        ));

        return response()->json([
            'status'  => true,
            'message' => 'Tugas berhasil ditambahkan',
            'data'    => $tugas,
        ], 201);
    }

    // GET /api/tugas/{id}
    public function show($id): JsonResponse
    {
        $tugas = Tugas::where('user_id', Auth::id())->find($id);

        if (!$tugas) {
            return response()->json(['status' => false, 'message' => 'Tugas tidak ditemukan'], 404);
        }

        return response()->json(['status' => true, 'data' => $tugas]);
    }

    // PUT /api/tugas/{id}
    public function update(Request $request, $id): JsonResponse
    {
        $tugas = Tugas::where('user_id', Auth::id())->find($id);

        if (!$tugas) {
            return response()->json(['status' => false, 'message' => 'Tugas tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul'     => 'sometimes|required|string|max:200',
            'mapel'     => 'sometimes|required|string|max:100',
            'deadline'  => 'sometimes|required|date',
            'prioritas' => 'sometimes|required|in:rendah,sedang,tinggi',
            'status'    => 'in:belum,sedang,selesai',
            'deskripsi' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $tugas->update($validator->validated());

        return response()->json([
            'status'  => true,
            'message' => 'Tugas berhasil diperbarui',
            'data'    => $tugas->fresh(),
        ]);
    }

    // DELETE /api/tugas/{id}
    public function destroy($id): JsonResponse
    {
        $tugas = Tugas::where('user_id', Auth::id())->find($id);

        if (!$tugas) {
            return response()->json(['status' => false, 'message' => 'Tugas tidak ditemukan'], 404);
        }

        $tugas->delete();

        return response()->json(['status' => true, 'message' => 'Tugas berhasil dihapus']);
    }
}
