<?php

namespace App\Http\Controllers;

use App\Models\JamaahFasilitasModel;
use Illuminate\Http\Request;
use App\Models\MasterJamaahModel;

class JamaahFasilitasController extends Controller
{
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