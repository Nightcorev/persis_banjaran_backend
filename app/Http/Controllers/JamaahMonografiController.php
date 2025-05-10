<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterJamaahModel;
use App\Models\JamaahMonografiModel;
use App\Models\AnggotaModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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
        $perPage = $request->input('perPage');
        $page = $request->input('page', 1);
        $searchTerm = $request->input('searchTerm', '');
    
        $query = MasterJamaahModel::with([
            'musyawarah' => function ($query) {
                $query->orderBy('id_musyawarah', 'desc')->where('aktif', 1);
            },
            'musyawarah.musyawarah_detail' => function ($query) {
                $query->where('jabatan', 'Ketua');
            },
            'musyawarah.musyawarah_detail.anggota',
            'monografi'
        ])->orderBy('id_master_jamaah', 'asc'); // Add this line for sorting
    
        // Filter pencarian berdasarkan nama jamaah
        if (!empty($searchTerm)) {
            $query->whereRaw('LOWER(nama_jamaah) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
        }
    
        // Jika perPage tidak diset, ambil semua data
        if ($perPage) {
            $paginatedData = $query->paginate($perPage);
        } else {
            $paginatedData = $query->paginate($query->count());
        }
    
        // Transform data untuk menambahkan jumlah_persis ke dalam setiap item
        $transformedData = $paginatedData->through(function ($item) {
            $item->jumlah_persis = $item->jumlahPersis();
            return $item;
        });
    
        return response()->json([
            'success' => true,
            'message' => 'Data Jamaah Monografi',
            'data' => $transformedData
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

    /**
     * @OA\Post(
     *     path="/api/jamaah/store",
     *     tags={"Jamaah"},
     *     summary="Tambah data master jamaah dan monografi",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"master_jamaah", "monografi"},
     *             @OA\Property(
     *                 property="master_jamaah",
     *                 type="object",
     *                 @OA\Property(property="nama_jamaah", type="string"),
     *                 @OA\Property(property="tgl_pelaksanaan", type="string", format="date"),
     *                 @OA\Property(property="tgl_akhir_jihad", type="string", format="date"),
     *                 @OA\Property(property="alamat", type="string")
     *             ),
     *             @OA\Property(
     *                 property="monografi",
     *                 type="object",
     *                 @OA\Property(property="jum_persistri", type="integer"),
     *                 @OA\Property(property="jum_pemuda", type="integer"),
     *                 @OA\Property(property="jum_pemudi", type="integer"),
     *                 @OA\Property(property="jum_mubaligh", type="integer"),
     *                 @OA\Property(property="jum_asatidz", type="integer"),
     *                 @OA\Property(property="jum_santri_ra", type="integer"),
     *                 @OA\Property(property="jum_santri_md", type="integer"),
     *                 @OA\Property(property="jum_santri_mi", type="integer"),
     *                 @OA\Property(property="jum_santri_tsn", type="integer"),
     *                 @OA\Property(property="jum_santri_smp", type="integer"),
     *                 @OA\Property(property="jum_santri_ma", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Data berhasil ditambahkan"),
     *     @OA\Response(response=400, description="Validasi gagal")
     * )
     */
    private function extractCoordinates($mapUrl)
    {
        try {
            // Make HTTP request to follow the shortened URL
            $response = Http::get($mapUrl);
            $finalUrl = $response->effectiveUri()->__toString();

            // Handle different Google Maps URL formats
            $patterns = [
                '/[@?](-?\d+\.\d+),(-?\d+\.\d+)/', // Matches ?lat,lng or @lat,lng
                '/maps\/place\/[^\/]+\/@(-?\d+\.\d+),(-?\d+\.\d+)/', // Matches maps/place/.../@lat,lng
                '/maps\/preview\/@(-?\d+\.\d+),(-?\d+\.\d+)/', // Matches maps/preview/@lat,lng
                '/maps\/([^\/]+)\/([^\/]+)\/@(-?\d+\.\d+),(-?\d+\.\d+)/' // Matches other formats with coordinates
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $finalUrl, $matches)) {
                    // If it's the last pattern (with 4 matches), coordinates are in index 3 and 4
                    if (count($matches) > 3) {
                        return [
                            'lat' => (float)$matches[3],
                            'long' => (float)$matches[4]
                        ];
                    }
                    // For other patterns, coordinates are in index 1 and 2
                    return [
                        'lat' => (float)$matches[1],
                        'long' => (float)$matches[2]
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Error extracting coordinates: ' . $e->getMessage());
            return null;
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'master_jamaah.nama_jamaah' => 'required|string|max:255',
            'master_jamaah.alamat' => 'required|string',
            'master_jamaah.aktif' => 'required|boolean',
            'master_jamaah.lokasi_map' => 'required|url',
            'monografi.jum_persistri' => 'nullable|integer',
            'monografi.jum_pemuda' => 'nullable|integer',
            'monografi.jum_pemudi' => 'nullable|integer',
            'monografi.jum_mubaligh' => 'nullable|integer',
            'monografi.jum_asatidz' => 'nullable|integer',
            'monografi.jum_santri_ra' => 'nullable|integer',
            'monografi.jum_santri_md' => 'nullable|integer',
            'monografi.jum_santri_mi' => 'nullable|integer',
            'monografi.jum_santri_tsn' => 'nullable|integer',
            'monografi.jum_santri_smp' => 'nullable|integer',
            'monografi.jum_santri_ma' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Extract coordinates from Google Maps URL
            $coordinates = $this->extractCoordinates($request->master_jamaah['lokasi_map']);
            
            if (!$coordinates) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format URL Google Maps tidak valid'
                ], 400);
            }

            // Merge coordinates and id_otonom with master_jamaah data
            $masterJamaahData = array_merge(
                $request->master_jamaah,
                [
                    'lokasi_lat' => $coordinates['lat'],
                    'lokasi_long' => $coordinates['long'],
                    'id_otonom' => 1  // Auto set id_otonom to 1
                ]
            );

            // Create master jamaah
            $masterJamaah = MasterJamaahModel::create($masterJamaahData);
                
            // Create monografi with same ID
            $monografiData = array_merge(
                $request->monografi,
                ['id_jamaah' => $masterJamaah->id_master_jamaah]
            );
                
            $monografi = JamaahMonografiModel::create($monografiData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data jamaah dan monografi berhasil ditambahkan',
                'data' => [
                    'master_jamaah' => $masterJamaah,
                    'monografi' => $monografi
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/jamaah/{id_master_jamaah}",
     *     tags={"Jamaah"},
     *     summary="Update data jamaah dan monografi",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id_master_jamaah",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="master_jamaah",
     *                 type="object",
     *                 @OA\Property(property="nama_jamaah", type="string"),
     *                 @OA\Property(property="tgl_pelaksanaan", type="string", format="date"),
     *                 @OA\Property(property="tgl_akhir_jihad", type="string", format="date"),
     *                 @OA\Property(property="alamat", type="string")
     *             ),
     *             @OA\Property(
     *                 property="monografi",
     *                 type="object",
     *                 @OA\Property(property="jum_persistri", type="integer"),
     *                 @OA\Property(property="jum_pemuda", type="integer"),
     *                 @OA\Property(property="jum_pemudi", type="integer"),
     *                 @OA\Property(property="jum_mubaligh", type="integer"),
     *                 @OA\Property(property="jum_asatidz", type="integer"),
     *                 @OA\Property(property="jum_santri_ra", type="integer"),
     *                 @OA\Property(property="jum_santri_md", type="integer"),
     *                 @OA\Property(property="jum_santri_mi", type="integer"),
     *                 @OA\Property(property="jum_santri_tsn", type="integer"),
     *                 @OA\Property(property="jum_santri_smp", type="integer"),
     *                 @OA\Property(property="jum_santri_ma", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Data berhasil diupdate"),
     *     @OA\Response(response=404, description="Data tidak ditemukan")
     * )
     */
    public function update(Request $request, $id_master_jamaah)
    {
        $masterJamaah = MasterJamaahModel::with('monografi')->find($id_master_jamaah);

        if (!$masterJamaah) {
            return response()->json([
                'success' => false,
                'message' => 'Data jamaah tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'master_jamaah.nama_jamaah' => 'required|string|max:255',
            'master_jamaah.alamat' => 'required|string',
            'master_jamaah.aktif' => 'required|boolean',
            'master_jamaah.lokasi_map' => 'required|url',
            'monografi.jum_persistri' => 'nullable|integer',
            'monografi.jum_pemuda' => 'nullable|integer',
            'monografi.jum_pemudi' => 'nullable|integer',
            'monografi.jum_mubaligh' => 'nullable|integer',
            'monografi.jum_asatidz' => 'nullable|integer',
            'monografi.jum_santri_ra' => 'nullable|integer',
            'monografi.jum_santri_md' => 'nullable|integer',
            'monografi.jum_santri_mi' => 'nullable|integer',
            'monografi.jum_santri_tsn' => 'nullable|integer',
            'monografi.jum_santri_smp' => 'nullable|integer',
            'monografi.jum_santri_ma' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Extract coordinates from Google Maps URL
            $coordinates = $this->extractCoordinates($request->master_jamaah['lokasi_map']);
            
            if (!$coordinates) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format URL Google Maps tidak valid'
                ], 400);
            }

            // Merge coordinates and id_otonom with master_jamaah data
            $masterJamaahData = array_merge(
                $request->master_jamaah,
                [
                    'lokasi_lat' => $coordinates['lat'],
                    'lokasi_long' => $coordinates['long'],
                    'id_otonom' => 1  // Maintain id_otonom as 1
                ]
            );

            // Update master jamaah
            $masterJamaah->update($masterJamaahData);

            // Update or create monografi
            if ($masterJamaah->monografi) {
                $masterJamaah->monografi->update($request->monografi);
                $monografi = $masterJamaah->monografi;
            } else {
                $monografiData = array_merge(
                    $request->monografi,
                    ['id_jamaah' => $masterJamaah->id_master_jamaah]
                );
                $monografi = JamaahMonografiModel::create($monografiData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data jamaah dan monografi berhasil diupdate',
                'data' => [
                    'master_jamaah' => $masterJamaah,
                    'monografi' => $monografi
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/jamaah/{id_master_jamaah}",
     *     tags={"Jamaah"},
     *     summary="Hapus data jamaah dan monografi",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id_master_jamaah",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Data berhasil dihapus"),
     *     @OA\Response(response=404, description="Data tidak ditemukan")
     * )
     */
    public function destroy($id_master_jamaah)
    {
        $masterJamaah = MasterJamaahModel::with('monografi')->find($id_master_jamaah);

        if (!$masterJamaah) {
            return response()->json([
                'success' => false,
                'message' => 'Data jamaah tidak ditemukan'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Delete monografi data first
            if ($masterJamaah->monografi) {
                $masterJamaah->monografi->delete();
            }
            
            // Then delete master jamaah
            $masterJamaah->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data jamaah dan monografi berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
