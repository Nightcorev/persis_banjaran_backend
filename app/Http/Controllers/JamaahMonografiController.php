<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterJamaahModel;
use App\Models\JamaahMonografiModel;
use App\Models\AnggotaModel;

class JamaahMonografiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/data_jamaah",
     *     tags={"Jamaah"},
     *     summary="Ambil daftar data jamaah monografi",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         required=false,
     *         description="Jumlah data per halaman (default: 10)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Pencarian berdasarkan nama jamaah",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mengambil data jamaah monografi"
     *     )
     * )
     */

    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $searchTerm = $request->input('search', '');

        $query = MasterJamaahModel::with([
            'musyawarah' => function ($query) {
                $query->orderBy('id_musyawarah', 'desc')->where('aktif', 1);
            },
            'musyawarah.musyawarah_detail' => function ($query) {
                $query->where('jabatan', 'Ketua');
            },
            'musyawarah.musyawarah_detail.anggota',
            'monografi'
        ]);

        // Filter pencarian berdasarkan nama jamaah
        if (!empty($searchTerm)) {
            $query->whereRaw('LOWER(nama_jamaah) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
        }

        // Paginasi data
        $paginatedData = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data Jamaah Monografi',
            'data' => $paginatedData,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/jamaah-monografi/{id_master_jamaah}",
     *     tags={"Jamaah"},
     *     summary="Ambil detail data jamaah monografi berdasarkan ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id_master_jamaah",
     *         in="path",
     *         required=true,
     *         description="ID Master Jamaah",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="searchTerm",
     *         in="query",
     *         required=false,
     *         description="Filter nama lengkap ketua jamaah",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         required=false,
     *         description="Jumlah data yang diambil (default: 5)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail data jamaah monografi ditemukan"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data Jamaah tidak ditemukan"
     *     )
     * )
     */

    public function show($id_master_jamaah)
    {
        $jamaah = MasterJamaahModel::with([
            'musyawarah.musyawarah_detail.anggota',
            'monografi'
        ])->find($id_master_jamaah);

        if (!$jamaah) {
            return response()->json([
                'success' => false,
                'message' => 'Data Jamaah tidak ditemukan',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail Data Jamaah Monografi',
            'data' => $jamaah,
        ]);
    }
}
