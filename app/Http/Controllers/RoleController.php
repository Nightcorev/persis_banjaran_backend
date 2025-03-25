<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $searchTerm = $request->input('search', '');

        $query = Role::with('permissions:id,name_permission'); // Load relasi permissions

        // Filter pencarian berdasarkan nama role
        if (!empty($searchTerm)) {
            $query->where('name_role', 'like', "%{$searchTerm}%");
        }

        // Paginasi data
        $paginatedData = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data Roles berhasil dimuat',
            'data' => [
                'data' => collect($paginatedData->items())->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name_role' => $role->name_role,
                        'permissions' => $role->permissions->map(fn($permission) => [
                            'id' => $permission->id,
                            'name_permission' => $permission->name_permission,
                        ]),
                    ];
                }),
                'total' => $paginatedData->total(),
                'per_page' => $paginatedData->perPage(),
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
            ],
        ]);
    }


    public function store(Request $request)
    {
        try {
            $request->validate([
                'name_role' => 'required|string|unique:roles,name_role',
                'permissions' => 'array', // Pastikan permissions berupa array
                'permissions.*' => 'exists:permissions,id', // Validasi bahwa setiap ID permission harus ada di tabel permissions
            ]);

            // Buat role baru
            $role = Role::create([
                'name_role' => $request->name_role,
            ]);

            // Jika ada permissions, attach ke role
            if (!empty($request->permissions)) {
                $role->permissions()->attach($request->permissions);
            }

            return response()->json([
                'message' => 'Role berhasil ditambahkan!',
                'data' => $role->load('permissions') // Load relasi permission
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name_role' => 'required|string|unique:roles,name_role,' . $id,
                'permissions' => 'array',
                'permissions.*' => 'exists:permissions,id',
            ]);

            $role = Role::findOrFail($id);
            $role->update([
                'name_role' => $request->name_role,
            ]);

            // Sync permissions agar update tidak duplicate
            if (!empty($request->permissions)) {
                $role->permissions()->sync($request->permissions);
            } else {
                $role->permissions()->detach(); // Hapus semua permission jika kosong
            }

            return response()->json([
                'message' => 'Role berhasil diperbarui!',
                'data' => $role->load('permissions')
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(['message' => 'Role berhasil dihapus!']);
    }
}
