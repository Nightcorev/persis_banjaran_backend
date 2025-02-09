<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnggotaModel;

class AnggotaController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('perPage', 10);
        $page = $request->query('page', 1);
        $searchTerm = $request->query('search', '');

        // Query menggunakan Eloquent ORM
        $query = AnggotaModel::with(['jamaah', 'pendidikan', 'pekerjaan'])
            ->when($searchTerm, function ($query) use ($searchTerm) {
                $query->where('nama_lengkap', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('nik', 'ILIKE', "%{$searchTerm}%");
            })
            ->orderBy('id_master_jamaah')
            ->orderBy('nama_lengkap');

        $data = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($data);
    }


    // Get single data
    public function show($id)
    {

    }

    // Create data
    public function store(Request $request)
    {

    }

    // Update data
    public function update(Request $request, $id)
    {

    }

    // Delete data
    public function destroy($id)
    {

    }
}

