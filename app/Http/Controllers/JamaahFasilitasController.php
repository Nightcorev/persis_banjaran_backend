<?php

namespace App\Http\Controllers;

use App\Models\JamaahFasilitasModel;
use Illuminate\Http\Request;
use App\Models\MasterJamaahModel;

class JamaahFasilitasController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/fasilitas/by-jamaah/{id_master_jamaah}",
     *     tags={"Jamaah"},
     *     summary="Ambil data fasilitas berdasarkan ID Jamaah",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id_master_jamaah",
     *         in="path",
     *         required=false,
     *         description="ID master jamaah (opsional)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         required=false,
     *         description="Jumlah data per halaman (default: 10)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Halaman keberapa (default: 1)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="searchTerm",
     *         in="query",
     *         required=false,
     *         description="Kata kunci pencarian",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Data fasilitas berhasil diambil")
     * )
     */

    public function indexByJamaah(Request $request, $id_master_jamaah = null)
    {
        $perPage = $request->input('perPage', 10);
        $page = $request->input('page', 1);
        $searchTerm = $request->input('searchTerm', '');

        $query = JamaahFasilitasModel::with(['master_jamaah'])
            ->when($id_master_jamaah, function ($query, $id_master_jamaah) {
                // Filter pesantren berdasarkan id_master_jamaah
                return $query->where('id_master_jamaah', $id_master_jamaah);
            })
            ->orderBy('id_fasilitas', 'asc');

        $fasilitas = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json(['status' => 200, 'data' => $fasilitas], 200);
    }
}
