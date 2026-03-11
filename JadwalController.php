<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class JadwalController extends Controller
{
    // GET /api/jadwal
    public function index(Request $request): JsonResponse
    {
        $query = Jadwal::where('user_id', Auth::id());

        if ($request->filled('hari'))   $query->where('hari', $request->hari);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) $query->where('mapel', 'like', "%{$request->search}%");

        $jadwals = $query->orderByRaw("FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')")
                         ->orderBy('jam_mulai')
                         ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Data jadwal berhasil diambil',
            'total'   => $jadwals->count(),
            'data'    => $jadwals,
        ]);
    }

    // POST /api/jadwal
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mapel'       => 'required|string|max:100',
            'pengajar'    => 'nullable|string|max:100',
            'hari'        => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'jam_mulai'   => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'ruangan'     => 'nullable|string|max:100',
            'status'      => 'in:aktif,selesai,pending',
            'catatan'     => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $jadwal = Jadwal::create(array_merge(
            $validator->validated(),
            ['user_id' => Auth::id(), 'status' => $request->status ?? 'aktif']
        ));

        return response()->json([
            'status'  => true,
            'message' => 'Jadwal berhasil ditambahkan',
            'data'    => $jadwal,
        ], 201);
    }

    // GET /api/jadwal/{id}
    public function show($id): JsonResponse
    {
        $jadwal = Jadwal::where('user_id', Auth::id())->find($id);

        if (!$jadwal) {
            return response()->json(['status' => false, 'message' => 'Jadwal tidak ditemukan'], 404);
        }

        return response()->json(['status' => true, 'data' => $jadwal]);
    }

    // PUT /api/jadwal/{id}
    public function update(Request $request, $id): JsonResponse
    {
        $jadwal = Jadwal::where('user_id', Auth::id())->find($id);

        if (!$jadwal) {
            return response()->json(['status' => false, 'message' => 'Jadwal tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'mapel'       => 'sometimes|required|string|max:100',
            'pengajar'    => 'nullable|string|max:100',
            'hari'        => 'sometimes|required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'jam_mulai'   => 'sometimes|required|date_format:H:i',
            'jam_selesai' => 'sometimes|required|date_format:H:i',
            'ruangan'     => 'nullable|string|max:100',
            'status'      => 'in:aktif,selesai,pending',
            'catatan'     => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $jadwal->update($validator->validated());

        return response()->json([
            'status'  => true,
            'message' => 'Jadwal berhasil diperbarui',
            'data'    => $jadwal->fresh(),
        ]);
    }

    // DELETE /api/jadwal/{id}
    public function destroy($id): JsonResponse
    {
        $jadwal = Jadwal::where('user_id', Auth::id())->find($id);

        if (!$jadwal) {
            return response()->json(['status' => false, 'message' => 'Jadwal tidak ditemukan'], 404);
        }

        $jadwal->delete();

        return response()->json(['status' => true, 'message' => 'Jadwal berhasil dihapus']);
    }
}
