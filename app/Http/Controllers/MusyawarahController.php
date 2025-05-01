<?php

namespace App\Http\Controllers;

use App\Models\MusyawarahModel;
use App\Models\MusyawarahDetailModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MusyawarahController extends Controller
{
    public function index(Request $request, $id_master_jamaah = null)
    {
        $perPage = $request->input('perPage', 10);
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
            'id_master_jamaah' => 'required|integer',
            'tgl_pelaksanaan' => 'required|date',
            'tgl_akhir_jihad' => 'required|date',
            'aktif' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->errors()], 400);
        }

        $musyawarah = MusyawarahModel::create($validator->validated());

        return response()->json(['status' => 201, 'data' => $musyawarah], 201);
    }

    public function update(Request $request, $id)
    {
        $musyawarah = MusyawarahModel::find($id);

        if (!$musyawarah) {
            return response()->json(['status' => 404, 'message' => 'Musyawarah not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_master_jamaah' => 'sometimes|required|integer',
            'tgl_pelaksanaan' => 'sometimes|required|date',
            'tgl_akhir_jihad' => 'nullable|date',
            'aktif' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->errors()], 400);
        }

        $musyawarah->update($validator->validated());

        return response()->json(['status' => 200, 'data' => $musyawarah], 200);
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
}
