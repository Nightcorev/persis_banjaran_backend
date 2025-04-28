<?php

namespace App\Http\Controllers;

use App\Models\MusyawarahModel;
use App\Models\MusyawarahDetailModel;
use Illuminate\Http\Request;

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
                return $query->whereRaw('LOWER(nama_pesantren) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
            })
            ->orderBy('id_musyawarah', 'asc');

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
                return $query->whereRaw('LOWER(nama_pesantren) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
            })
            ->orderBy('id_musyawarah', 'asc');
            
        $musyawarah = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json(['status' => 200, 'data' => $musyawarah], 200);
    }
}
