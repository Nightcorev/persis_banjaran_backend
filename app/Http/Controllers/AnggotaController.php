<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnggotaModel;

class AnggotaController extends Controller
{
    protected $anggotaModel;

    public function __construct(AnggotaModel $anggotaModel)
    {
        $this->anggotaModel = $anggotaModel;
    }

    // Get all data
    // public function index()
    // {
    //     $data = $this->anggotaModel->getAnggota();
    //     $response = [
    //         'data_anggota' => $data
    //     ];
    //     return response()->json($response);
    // }

    public function index(Request $request)
    {
        $perPage = $request->query('perPage', 10); // Default 10
        $page = $request->query('page', 1); // Default halaman 1
        $searchTerm = $request->query('search', ''); // Mendapatkan parameter pencarian

        $data = $this->anggotaModel->getAnggota($page, $perPage, $searchTerm);
        $total = $this->anggotaModel->getTotalAnggota(); // Hitung total data

        return response()->json([
            'data_anggota' => $data,
            'total' => $total,
            'perPage' => $perPage,
            'currentPage' => $page
        ]);
    }


    // Get single data
    public function show($id)
    {
        // $anggota = Anggota::find($id);
        // if (!$anggota) {
        //     return response()->json(['message' => 'Data not found'], 404);
        // }
        // return response()->json($anggota, 200);
    }

    // Create data
    public function store(Request $request)
    {
        // $validatedData = $request->validate([
        //     'nik' => 'required|string|max:50',
        //     'nama_lengkap' => 'required|string|max:100',
        //     'email' => 'nullable|email|max:75',
        //     // Tambahkan validasi sesuai kebutuhan
        // ]);

        // $anggota = Anggota::create($validatedData);
        // return response()->json($anggota, 201);
    }

    // Update data
    public function update(Request $request, $id)
    {
        // $anggota = Anggota::find($id);
        // if (!$anggota) {
        //     return response()->json(['message' => 'Data not found'], 404);
        // }

        // $anggota->update($request->all());
        // return response()->json($anggota, 200);
    }

    // Delete data
    public function destroy($id)
    {
        // $anggota = Anggota::find($id);
        // if (!$anggota) {
        //     return response()->json(['message' => 'Data not found'], 404);
        // }

        // $anggota->delete();
        // return response()->json(['message' => 'Data deleted successfully'], 200);
    }
}

