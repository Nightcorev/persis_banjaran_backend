<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/permissions",
     *     tags={"Permission"},
     *     summary="Ambil daftar data permission",
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
     *         description="Pencarian berdasarkan nama_permission, jenis_permission, atau fitur",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mengambil data permission"
     *     )
     * )
     */

    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $searchTerm = $request->input('search', '');

        $query = Permission::query();

        // Filter pencarian berdasarkan nama permission
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name_permission', 'like', "%{$searchTerm}%")
                    ->orWhere('jenis_permission', 'like', "%{$searchTerm}%")
                    ->orWhere('fitur', 'like', "%{$searchTerm}%");
            });
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
    /**
     * @OA\Post(
     *     path="/api/permissions",
     *     tags={"Permission"},
     *     summary="Tambah data permission baru",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name_permission", "jenis_permission", "fitur"},
     *             @OA\Property(property="name_permission", type="string", example="permission.view"),
     *             @OA\Property(property="jenis_permission", type="string", example="read"),
     *             @OA\Property(property="fitur", type="string", example="User Management")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permission berhasil ditambahkan"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     )
     * )
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
    /**
     * @OA\Put(
     *     path="/api/permissions/{id}",
     *     tags={"Permission"},
     *     summary="Update data permission berdasarkan ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID permission",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name_permission", "jenis_permission", "fitur"},
     *             @OA\Property(property="name_permission", type="string", example="permission.update"),
     *             @OA\Property(property="jenis_permission", type="string", example="update"),
     *             @OA\Property(property="fitur", type="string", example="User Management")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission berhasil diperbarui"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data permission tidak ditemukan"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     )
     * )
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
    /**
     * @OA\Delete(
     *     path="/api/permissions/{id}",
     *     tags={"Permission"},
     *     summary="Hapus data permission berdasarkan ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID permission",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission berhasil dihapus"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data permission tidak ditemukan"
     *     )
     * )
     */

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json(['message' => 'Permission berhasil dihapus']);
    }
}
