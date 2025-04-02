<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $searchTerm = $request->input('search', '');

        $query = Permission::query();

        // Filter pencarian berdasarkan nama permission
        if (!empty($searchTerm)) {
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        // Paginasi data
        $paginatedData = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data Permissions Succses load',
            'data' => [
                'data' => $paginatedData->items(),
                'total' => $paginatedData->total(),
                'per_page' => $paginatedData->perPage(),
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
            ],
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'name_permission' => 'required|unique:permissions,name_permission',
            'jenis_permission' => 'required',
            'fitur' => 'required'
        ]);

        $permission = Permission::create($request->all());

        return response()->json(['message' => 'Permission berhasil ditambahkan', 'data' => $permission], 201);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $request->validate([
            'name_permission' => 'required|unique:permissions,name_permission,' . $id,
            'jenis_permission' => 'required',
            'fitur' => 'required'
        ]);

        $permission = Permission::findOrFail($id);
        $permission->update($request->all());

        return response()->json(['message' => 'Permission berhasil diperbarui', 'data' => $permission]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json(['message' => 'Permission berhasil dihapus']);
    }
}
