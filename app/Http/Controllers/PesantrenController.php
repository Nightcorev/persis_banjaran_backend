<?php

namespace App\Http\Controllers;

use App\Models\MasterJamaahModel;
use Illuminate\Http\Request;
use App\Models\PesantrenModel;

class PesantrenController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/pesantren/by-jamaah/{id_master_jamaah}",
     *     tags={"Pesantren"},
     *     summary="Ambil data pesantren berdasarkan ID jamaah",
     *     description="Menampilkan daftar pesantren yang terhubung dengan ID Master Jamaah tertentu, dengan pencarian dan paginasi.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id_master_jamaah",
     *         in="path",
     *         required=false,
     *         description="ID master jamaah. Jika null, akan menampilkan semua pesantren.",
     *         @OA\Schema(type="integer", nullable=true)
     *     ),
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         required=false,
     *         description="Jumlah data per halaman (default: 5)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Halaman saat ini (default: 1)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="searchTerm",
     *         in="query",
     *         required=false,
     *         description="Filter nama pesantren (case-insensitive)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mengambil data pesantren berdasarkan jamaah"
     *     )
     * )
     */

    public function indexByJamaah(Request $request, $id_master_jamaah = null)
    {
        $perPage = $request->input('perPage', 5);
        $page = $request->input('page', 1);
        $searchTerm = $request->input('searchTerm', '');

        $query = PesantrenModel::with(['master_jamaah'])
            ->when($id_master_jamaah, function ($query, $id_master_jamaah) {
                // Filter pesantren berdasarkan id_master_jamaah
                return $query->where('id_master_jamaah', $id_master_jamaah);
            })
            ->when($searchTerm, function ($query, $searchTerm) {
                // Filter berdasarkan pencarian
                return $query->whereRaw('LOWER(nama_pesantren) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
            })
            ->orderBy('id_pesantren', 'asc');

        $pesantren = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json(['status' => 200, 'data' => $pesantren], 200);
    }
}
