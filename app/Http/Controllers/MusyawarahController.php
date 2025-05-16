<?php

namespace App\Http\Controllers;

use App\Models\MusyawarahModel;
use App\Models\MusyawarahDetailModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MusyawarahController extends Controller
{
    public function index(Request $request, $id_master_jamaah = null)
    {
        $perPage = $request->input('perPage', 33);
        $page = $request->input('page', 1);
        $searchTerm = $request->input('searchTerm', '');
        
        $query = MusyawarahModel::with(['master_jamaah'])
            ->when($searchTerm, function ($query, $searchTerm) {
                // Filter berdasarkan pencarian
                return $query->whereRaw('LOWER(nama_jamaah) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
            })
            ->orderBy('id_musyawarah', 'desc');

        $musyawarah = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json(['status' => 200, 'data' => $musyawarah], 200);
    }

    public function view(Request $request, $id_musyawarah = null)
    {
        $perPage = $request->input('perPage', 10);
        $page = $request->input('page', 1);
        $searchTerm = $request->input('searchTerm', '');
        
        $query = MusyawarahModel::with(['master_jamaah', 'musyawarah_detail.anggota'])
            ->when($id_musyawarah, function ($query, $id_musyawarah) {
                return $query->where('id_musyawarah', $id_musyawarah);
            })
            ->when($searchTerm, function ($query, $searchTerm) {
                // Filter berdasarkan pencarian
                return $query->whereRaw('LOWER(nama_lengkap) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
            });
            
        $musyawarah = $query->paginate($perPage, ['*'], 'page', $page);
        
        $musyawarah->getCollection()->transform(function ($item) {
            // Sort the musyawarah_detail relationship
            $sortedDetails = $item->musyawarah_detail->sortBy(function ($detail) {
                // Define jabatan priority order
                $jabatanPriority = [
                    'ketua' => 1,
                    'sekretaris' => 2,
                    'bendahara' => 3,
                ];
                
                $jabatan = strtolower($detail->jabatan ?? '');
                return $jabatanPriority[$jabatan] ?? 999;
            })->values();
            
            $item->setRelation('musyawarah_detail', $sortedDetails);
            
            return $item;
        });

        return response()->json(['status' => 200, 'data' => $musyawarah], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_master_jamaah' => 'nullable|integer',
            'tgl_pelaksanaan' => 'required|date',
            'tgl_akhir_jihad' => 'required|date',
            'aktif' => 'required|boolean',
            'tingkat_musyawarah' => 'nullable|string',
            'no_sk' => 'nullable|string|max:100',
            'id_anggota' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400, 
                'errors' => $validator->errors()
            ], 400);
        }

        DB::beginTransaction();
        try {
            // If new musyawarah is active, deactivate others
            if ($request->aktif) {
                MusyawarahModel::where('id_master_jamaah', $request->id_master_jamaah)
                    ->where('aktif', true)
                    ->update(['aktif' => false]);
            }

            // Create musyawarah with default tingkat_musyawarah if not provided
            $musyawarah = MusyawarahModel::create([
                'id_master_jamaah' => $request->id_master_jamaah,
                'tgl_pelaksanaan' => $request->tgl_pelaksanaan,
                'tgl_akhir_jihad' => $request->tgl_akhir_jihad,
                'aktif' => $request->aktif,
                'tingkat_musyawarah' => $request->tingkat_musyawarah ?? 'jamaah',
                'no_sk' => $request->no_sk
            ]);

            // Create musyawarah detail for ketua
            MusyawarahDetailModel::create([
                'id_musyawarah' => $musyawarah->id_musyawarah,
                'id_anggota' => $request->id_anggota,
                'jabatan' => 'Ketua',
                'aktif' => true
            ]);

            DB::commit();

            $musyawarah->load(['musyawarah_detail.anggota', 'master_jamaah']);

            return response()->json([
                'status' => 201,
                'message' => 'Musyawarah created successfully',
                'data' => $musyawarah
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 500,
                'message' => 'Failed to create musyawarah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $musyawarah = MusyawarahModel::find($id);

        if (!$musyawarah) {
            return response()->json([
                'status' => 404, 
                'message' => 'Musyawarah not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_master_jamaah' => 'nullable|integer',
            'tgl_pelaksanaan' => 'sometimes|required|date',
            'tgl_akhir_jihad' => 'sometimes|required|date',
            'aktif' => 'sometimes|required|boolean',
            'tingkat_musyawarah' => 'nullable|string',
            'no_sk' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400, 
                'errors' => $validator->errors()
            ], 400);
        }

        DB::beginTransaction();
        try {
            if ($request->has('aktif') && $request->aktif && !$musyawarah->aktif) {
                MusyawarahModel::where('id_master_jamaah', $musyawarah->id_master_jamaah)
                    ->where('id_musyawarah', '!=', $id)
                    ->where('aktif', true)
                    ->update(['aktif' => false]);
            }

            $musyawarah->update($validator->validated());

            DB::commit();

            $musyawarah->load(['musyawarah_detail.anggota', 'master_jamaah']);

            return response()->json([
                'status' => 200,
                'message' => 'Musyawarah updated successfully',
                'data' => $musyawarah
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update musyawarah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $musyawarah = MusyawarahModel::find($id);

        if (!$musyawarah) {
            return response()->json(['status' => 404, 'message' => 'Musyawarah not found'], 404);
        }

        $musyawarah->delete();

        return response()->json(['status' => 200, 'message' => 'Musyawarah deleted successfully'], 200);
    }
        public function showDetail($id_musyawarah, $id_detail)
    {
        $detail = MusyawarahDetailModel::where('id_musyawarah', $id_musyawarah)
            ->where('id_musyawarah_detail', $id_detail)
            ->with(['anggota']) // Load anggota relation
            ->first();
    
        if (!$detail) {
            return response()->json([
                'status' => 404, 
                'message' => 'Musyawarah detail not found'
            ], 404);
        }
    
        return response()->json([
            'status' => 200,
            'data' => $detail
        ], 200);
    }
    
    public function addDetail(Request $request, $id_musyawarah)
    {
        $musyawarah = MusyawarahModel::find($id_musyawarah);
    
        if (!$musyawarah) {
            return response()->json(['status' => 404, 'message' => 'Musyawarah not found'], 404);
        }
    
        $validator = Validator::make($request->all(), [
            'id_anggota' => 'required|integer|exists:t_anggota,id_anggota',
            'jabatan' => 'required|string|max:50', // Remove the 'in' validation
            'aktif' => 'required|boolean',
            'no_sk' => 'nullable|string|max:100'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->errors()], 400);
        }
    
        // Special validation for Ketua position
        if (strtolower($request->jabatan) === 'ketua') {
            $existingKetua = MusyawarahDetailModel::where('id_musyawarah', $id_musyawarah)
                ->where('jabatan', 'Ketua')
                ->where('aktif', true)
                ->first();
    
            if ($existingKetua) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Jabatan Ketua sudah ada dan masih aktif'
                ], 400);
            }
        }
    
        $detail = MusyawarahDetailModel::create([
            'id_musyawarah' => $id_musyawarah,
            'id_anggota' => $request->id_anggota,
            'jabatan' => $request->jabatan,
            'aktif' => $request->aktif,
            'no_sk' => $request->no_sk
        ]);
    
        return response()->json([
            'status' => 201,
            'message' => 'Musyawarah detail added successfully',
            'data' => $detail->load('anggota')
        ], 201);
    }
    
    public function updateDetail(Request $request, $id_musyawarah, $id_detail)
    {
        $detail = MusyawarahDetailModel::where('id_musyawarah', $id_musyawarah)
            ->where('id_musyawarah_detail', $id_detail)
            ->first();
    
        if (!$detail) {
            return response()->json(['status' => 404, 'message' => 'Musyawarah detail not found'], 404);
        }
    
        $validator = Validator::make($request->all(), [
            'id_anggota' => 'nullable|integer|exists:t_anggota,id_anggota',
            'jabatan' => 'nullable|string|max:50', // Remove the 'in' validation
            'aktif' => 'nullable|boolean',
            'no_sk' => 'nullable|string|max:100'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->errors()], 400);
        }
    
        // Special validation for Ketua position when changing jabatan
        if ($request->has('jabatan') && strtolower($request->jabatan) === 'ketua') {
            $existingKetua = MusyawarahDetailModel::where('id_musyawarah', $id_musyawarah)
                ->where('jabatan', 'Ketua')
                ->where('id_musyawarah_detail', '!=', $id_detail)
                ->where('aktif', true)
                ->first();
    
            if ($existingKetua) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Jabatan Ketua sudah ada dan masih aktif'
                ], 400);
            }
        }
    
        // Check if jabatan already exists (excluding current detail)
        if ($request->has('jabatan')) {
            $existingJabatan = MusyawarahDetailModel::where('id_musyawarah', $id_musyawarah)
                ->where('jabatan', $request->jabatan)
                ->where('id_musyawarah_detail', '!=', $id_detail)
                ->where('aktif', true)
                ->first();
    
            if ($existingJabatan) {
                return response()->json([
                    'status' => 400,
                    'message' => "Jabatan {$request->jabatan} sudah ada dan masih aktif"
                ], 400);
            }
        }
    
        $detail->update($validator->validated());
    
        return response()->json([
            'status' => 200,
            'message' => 'Musyawarah detail updated successfully',
            'data' => $detail->load('anggota')
        ], 200);
    }
    
    public function destroyDetail($id_musyawarah, $id_detail)
    {
        $detail = MusyawarahDetailModel::where('id_musyawarah', $id_musyawarah)
            ->where('id_musyawarah_detail', $id_detail)
            ->first();
    
        if (!$detail) {
            return response()->json(['status' => 404, 'message' => 'Musyawarah detail not found'], 404);
        }
    
        $detail->delete();
    
        return response()->json([
            'status' => 200,
            'message' => 'Musyawarah detail deleted successfully'
        ], 200);
    }

    public function indexPimpinanCabang(Request $request)
    {
        $perPage = $request->input('perPage', 33);
        $page = $request->input('page', 1);
        $searchTerm = $request->input('searchTerm', '');
        
        $query = MusyawarahModel::with(['master_jamaah', 'musyawarah_detail.anggota'])
            ->where('tingkat_musyawarah', 'pimpinan_cabang')
            ->when($searchTerm, function ($query, $searchTerm) {
                return $query->whereHas('master_jamaah', function($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(nama_jamaah) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
                });
            })
            ->orderBy('id_musyawarah', 'desc');
    
        $musyawarah = $query->paginate($perPage, ['*'], 'page', $page);
    
        // Transform the result to include only the ketua information
        $musyawarah->getCollection()->transform(function ($item) {
            // Sort the musyawarah_detail relationship to prioritize key positions
            $sortedDetails = $item->musyawarah_detail->sortBy(function ($detail) {
                // Define jabatan priority order
                $jabatanPriority = [
                    'ketua' => 1,
                    'sekretaris' => 2,
                    'bendahara' => 3,
                ];
                
                $jabatan = strtolower($detail->jabatan ?? '');
                return $jabatanPriority[$jabatan] ?? 999;
            })->values();
            
            $item->setRelation('musyawarah_detail', $sortedDetails);
            
            // Find the ketua position
            $ketua = $sortedDetails->firstWhere(function($detail) {
                return strtolower($detail->jabatan) === 'ketua';
            });
            
            // Add only ketua information
            $item->pimpinan = $ketua ? [
                'jabatan' => $ketua->jabatan,
                'nama' => $ketua->anggota->nama_lengkap ?? 'N/A'
            ] : null;
            
            return $item;
        });
    
        return response()->json([
            'status' => 200, 
            'message' => 'Daftar Musyawarah Pimpinan Cabang',
            'data' => $musyawarah
        ], 200);
    }
}
