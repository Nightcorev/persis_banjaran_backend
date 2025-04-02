<?php

namespace App\Http\Controllers;

use App\Models\MasterJamaahModel;
use Illuminate\Http\Request;
use App\Models\PesantrenModel;

class PesantrenController extends Controller
{
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
                return $query->whereRaw('LOWER(nama_pesantren) LIKE ?', ["%".strtolower($searchTerm)."%"]);
            })
            ->orderBy('id_pesantren', 'asc');
        
        $pesantren = $query->paginate($perPage, ['*'], 'page', $page);
    
        return response()->json(['status' => 200, 'data' => $pesantren], 200);
    }
}