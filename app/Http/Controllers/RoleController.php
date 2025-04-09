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
    /**
     * @OA\Get(
     *     path="/api/role",
     *     tags={"Role"},
     *     summary="Ambil daftar role dengan permission",
     *     description="Endpoint ini digunakan untuk mengambil data role dengan relasi permission, mendukung pencarian dan paginasi.",
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
     *         description="Filter berdasarkan nama role atau permission",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mengambil daftar role",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data Roles berhasil dimuat"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name_role", type="string", example="Super Admin"),
     *                         @OA\Property(
     *                             property="permissions",
     *                             type="array",
     *                             @OA\Items(
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="name_permission", type="string", example="lihat_data_anggota")
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3)
     *             )
     *         )
     *     )
     * )
     */

    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $searchTerm = $request->input('search', '');

        $query = Role::with('permissions:id,name_permission'); // Load relasi permissions

        // Filter pencarian berdasarkan nama role
        if (!empty($searchTerm)) {
            $query->where('name_role', 'like', "%{$searchTerm}%")
                ->orWhereHas('permissions', function ($q) use ($searchTerm) {
                    $q->where('name_permission', 'like', "%{$searchTerm}%");
                });
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

    /**
     * @OA\Post(
     *     path="/api/role",
     *     tags={"Role"},
     *     summary="Tambah role baru",
     *     description="Membuat role baru beserta daftar permission-nya (opsional).",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name_role"},
     *             @OA\Property(property="name_role", type="string", example="Admin Keuangan"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Role berhasil ditambahkan",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Role berhasil ditambahkan!"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name_role", type="string", example="Admin Keuangan"),
     *                 @OA\Property(
     *                     property="permissions",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name_permission", type="string", example="lihat_iuran")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="object")
     *         )
     *     )
     * )
     */

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

    /**
     * @OA\Put(
     *     path="/api/role/{id}",
     *     tags={"Role"},
     *     summary="Ubah role",
     *     description="Memperbarui nama role dan daftar permission-nya.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID dari role yang akan diubah",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name_role"},
     *             @OA\Property(property="name_role", type="string", example="Admin Pembinaan"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role berhasil diperbarui",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Role berhasil diperbarui!"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name_role", type="string", example="Admin Pembinaan"),
     *                 @OA\Property(
     *                     property="permissions",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=3),
     *                         @OA\Property(property="name_permission", type="string", example="input_program_pembinaan")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="object")
     *         )
     *     )
     * )
     */

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
    /**
     * @OA\Delete(
     *     path="/api/role/{id}",
     *     tags={"Role"},
     *     summary="Hapus role",
     *     description="Menghapus role berdasarkan ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID dari role yang akan dihapus",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Role berhasil dihapus!")
     *         )
     *     )
     * )
     */

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(['message' => 'Role berhasil dihapus!']);
    }
}
