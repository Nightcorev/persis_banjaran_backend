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
                $query->where('aktif', 1);
            },
            'musyawarah.musyawarahDetail' => function ($query) {
                $query->where('jabatan', 'Ketua');
            },
            'musyawarah.musyawarahDetail.anggota',
            'monografi'
        ]);

        // Filter pencarian berdasarkan nama jamaah
        if (!empty($searchTerm)) {
            $query->whereRaw('LOWER(nama_jamaah) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
        }

        // Paginasi data
        $paginatedData = $query->paginate($perPage);

        // Modifikasi data sebelum dikirim ke frontend
        $dataMonografi = $paginatedData->map(function ($jamaah) {
            return [
                'id_master_jamaah' => $jamaah->id_master_jamaah,
                'nama_jamaah' => $jamaah->nama_jamaah,
                'nama_lengkap' => optional($jamaah->musyawarah->musyawarahDetail->first()->anggota)->nama_lengkap ?? 'Tidak Ada Ketua',
                'tgl_pelaksanaan' => optional($jamaah->musyawarah)->tgl_pelaksanaan,
                'tgl_akhir_jihad' => optional($jamaah->musyawarah)->tgl_akhir_jihad,
                'jml_persis' => $jamaah->jumlahPersis() ?? 0,
                'jml_persistri' => $jamaah->monografi->jum_persistri ?? 0,
                'jml_pemuda' => $jamaah->monografi->jum_pemuda ?? 0,
                'jml_pemudi' => $jamaah->monografi->jum_pemudi ?? 0,
                'jml_mubaligh' => $jamaah->monografi->jum_mubaligh ?? 0,
                'jml_asatidz' => $jamaah->monografi->jum_asatidz ?? 0,
                'jml_ra' => $jamaah->monografi->jum_santri_ra ?? 0,
                'jml_md' => $jamaah->monografi->jum_santri_md ?? 0,
                'jml_mi' => $jamaah->monografi->jum_santri_mi ?? 0,
                'jml_tsn' => $jamaah->monografi->jum_santri_tsn ?? 0,
                'jml_smp' => $jamaah->monografi->jum_santri_smp ?? 0,
                'jml_ma' => $jamaah->monografi->jum_santri_ma ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Data Jamaah Monografi',
            'data' => [
                'data' => $dataMonografi,
                'total' => $paginatedData->total(),
                'per_page' => $paginatedData->perPage(),
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/data_choice_jamaah",
     *     tags={"Jamaah"},
     *     summary="Ambil pilihan data jamaah",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Data jamaah berhasil diambil")
     * )
     */

    public function getChoiceDataJamaah(Request $request)
    {
        $datajamaah = MasterJamaahModel::select(
            'id_master_jamaah',
            'nama_jamaah'
        )->get();

        return response()->json([
            'data' => $datajamaah,
        ], 200);
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
        $searchTerm = request('searchTerm');
        $perPage = request('perPage', 5); // Default 5 jika tidak dikirim dari frontend

        // Cari data jamaah berdasarkan id_master_jamaah
        $jamaah = MasterJamaahModel::with([
            'musyawarah' => function ($query) {
                $query->where('aktif', 1);
            },
            'musyawarah.musyawarahDetail' => function ($query) {
                $query->where('jabatan', 'Ketua');
            },
            'musyawarah.musyawarahDetail.anggota',
            'monografi'
        ])->where('id_master_jamaah', $id_master_jamaah)->first();

        // Jika data tidak ditemukan, kembalikan response error
        if (!$jamaah) {
            return response()->json([
                'success' => false,
                'message' => 'Data Jamaah tidak ditemukan',
                'data' => null,
            ], 404);
        }

        // Ambil data musyawarah
        $musyawarahData = [
            'id_master_jamaah' => $jamaah->id_master_jamaah,
            'nama_jamaah' => $jamaah->nama_jamaah,
            'nama_lengkap' => optional($jamaah->musyawarah->musyawarahDetail->first()->anggota)->nama_lengkap ?? 'Tidak Ada Ketua',
            'alamat' => $jamaah->alamat,
            'jml_persis' => $jamaah->jumlahPersis() ?? 0,
            'jml_persistri' => optional($jamaah->monografi)->jum_persistri ?? 0,
            'jml_pemuda' => optional($jamaah->monografi)->jum_pemuda ?? 0,
            'jml_pemudi' => optional($jamaah->monografi)->jum_pemudi ?? 0,
            'jml_mubaligh' => optional($jamaah->monografi)->jum_mubaligh ?? 0,
            'jml_asatidz' => optional($jamaah->monografi)->jum_asatidz ?? 0,
            'jml_ra' => optional($jamaah->monografi)->jum_santri_ra ?? 0,
            'jml_md' => optional($jamaah->monografi)->jum_santri_md ?? 0,
            'jml_mi' => optional($jamaah->monografi)->jum_santri_mi ?? 0,
            'jml_tsn' => optional($jamaah->monografi)->jum_santri_tsn ?? 0,
            'jml_smp' => optional($jamaah->monografi)->jum_santri_smp ?? 0,
            'jml_ma' => optional($jamaah->monografi)->jum_santri_ma ?? 0,
            'tgl_pelaksanaan' => $jamaah->musyawarah->tgl_pelaksanaan ?? 0,
            'tgl_akhir_jihad' => $jamaah->musyawarah->tgl_akhir_jihad ?? 0
        ];

        // **Filter berdasarkan searchTerm**
        if ($searchTerm) {
            if (stripos($musyawarahData['nama_lengkap'], $searchTerm) === false) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada data yang cocok dengan pencarian',
                    'data' => null
                ]);
            }
        }

        // **Pagination (batasi jumlah data)**
        $paginatedData = collect([$musyawarahData])->take($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Detail Data Jamaah Monografi',
            'data' => $paginatedData->first(),
        ]);
    }
}
