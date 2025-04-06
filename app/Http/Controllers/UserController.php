<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/user",
     *     tags={"User"},
     *     summary="Daftar user",
     *     description="Menampilkan daftar pengguna dengan filter pencarian dan paginasi.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         description="Jumlah data per halaman",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Kata kunci pencarian (nama, email, username, atau role)",
     *         required=false,
     *         @OA\Schema(type="string", example="admin")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data pengguna berhasil dimuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data Users berhasil dimuat"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Ahmad Fauzi"),
     *                     @OA\Property(property="email", type="string", example="ahmad@example.com"),
     *                     @OA\Property(property="username", type="string", example="ahmad123"),
     *                     @OA\Property(property="id_anggota", type="integer", example=12),
     *                     @OA\Property(property="role", type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name_role", type="string", example="Admin")
     *                     ),
     *                     @OA\Property(property="anggota", type="object",
     *                         @OA\Property(property="id_anggota", type="integer", example=12),
     *                         @OA\Property(property="nama_lengkap", type="string", example="Ahmad Fauzi"),
     *                         @OA\Property(property="email", type="string", example="ahmad@example.com")
     *                     )
     *                 )),
     *                 @OA\Property(property="total", type="integer", example=100),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=10)
     *             )
     *         )
     *     )
     * )
     */

    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10); // Jumlah data per halaman
        $searchTerm = $request->input('search', ''); // Kata kunci pencarian

        // Query dengan relasi role dan anggota (hanya mengambil kolom tertentu)
        $query = User::with([
            'role:id,name_role',
            'anggota:id_anggota,nama_lengkap,email'
        ]);

        // Filter pencarian berdasarkan nama, email, atau username
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('username', 'like', "%{$searchTerm}%");
            })->orWhereHas('role', function ($q) use ($searchTerm) {
                $q->where('name_role', 'like', "%{$searchTerm}%");
            });
        }

        // Paginasi data
        $paginatedData = $query->paginate($perPage);

        // Format data yang akan dikembalikan
        return response()->json([
            'success' => true,
            'message' => 'Data Users berhasil dimuat',
            'data' => [
                'data' => collect($paginatedData->items())->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'username' => $user->username,
                        'id_anggota' => $user->id_anggota,
                        'role' => $user->role ? [
                            'id' => $user->role->id,
                            'name_role' => $user->role->name_role,
                        ] : null, // Jika role null
                        'anggota' => $user->anggota ? [
                            'id_anggota' => $user->anggota->id_anggota,
                            'nama_lengkap' => $user->anggota->nama_lengkap,
                            'email' => $user->anggota->email,
                        ] : null, // Jika anggota null
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
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/user",
     *     tags={"User"},
     *     summary="Tambah user baru",
     *     description="Menambahkan user baru ke sistem, termasuk role dan opsional id anggota.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "username", "password", "role_id"},
     *             @OA\Property(property="name", type="string", example="Siti Nurhaliza"),
     *             @OA\Property(property="email", type="string", example="siti@example.com"),
     *             @OA\Property(property="username", type="string", example="sitinur"),
     *             @OA\Property(property="password", type="string", example="rahasia123"),
     *             @OA\Property(property="role_id", type="integer", example=2),
     *             @OA\Property(property="id_anggota", type="integer", nullable=true, example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User berhasil ditambahkan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User berhasil ditambahkan!"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="Siti Nurhaliza"),
     *                 @OA\Property(property="username", type="string", example="sitinur"),
     *                 @OA\Property(property="email", type="string", example="siti@example.com"),
     *                 @OA\Property(property="role", type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name_role", type="string", example="Admin")
     *                 ),
     *                 @OA\Property(property="anggota", type="object",
     *                     @OA\Property(property="id_anggota", type="integer", example=10),
     *                     @OA\Property(property="nama_lengkap", type="string", example="Siti Nurhaliza"),
     *                     @OA\Property(property="email", type="string", example="siti@example.com")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validasi gagal."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Kesalahan server",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Terjadi kesalahan saat menyimpan user."),
     *             @OA\Property(property="error", type="string", example="SQLSTATE[23000]: Integrity constraint violation: 1452 ...")
     *         )
     *     )
     * )
     */

    public function store(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required',
                'username' => 'required|string|unique:users,username|max:50',
                'password' => 'required|string|min:4',
                'role_id' => 'required|exists:roles,id', // Validasi bahwa role_id harus ada di tabel roles
                'id_anggota' => 'nullable|exists:t_anggota,id_anggota', // Validasi bahwa id_anggota harus ada di tabel anggota (jika ada)
            ]);

            // Buat user baru
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => bcrypt($request->password), // Enkripsi password
                'role_id' => $request->role_id,
                'id_anggota' => $request->id_anggota, // Boleh null jika tidak ada anggota terkait
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User berhasil ditambahkan!',
                'data' => $user->load('role:id,name_role', 'anggota:id_anggota,nama_lengkap,email')

            ], 201);
        } catch (ValidationException $e) {
            // Jika validasi gagal, kirimkan respons error
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Jika terjadi kesalahan lain, tangani di sini
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Put(
     *     path="/api/user/{id}",
     *     tags={"User"},
     *     summary="Perbarui data user",
     *     description="Memperbarui informasi user yang sudah ada, termasuk role dan id_anggota.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID user yang akan diperbarui",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "username", "role_id"},
     *             @OA\Property(property="name", type="string", example="Siti Nurhaliza"),
     *             @OA\Property(property="email", type="string", example="siti@example.com"),
     *             @OA\Property(property="username", type="string", example="sitinur"),
     *             @OA\Property(property="password", type="string", nullable=true, example="rahasiaBaru123"),
     *             @OA\Property(property="role_id", type="integer", example=2),
     *             @OA\Property(property="id_anggota", type="integer", nullable=true, example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User berhasil diperbarui",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User berhasil diperbarui!"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="Siti Nurhaliza"),
     *                 @OA\Property(property="username", type="string", example="sitinur"),
     *                 @OA\Property(property="email", type="string", example="siti@example.com"),
     *                 @OA\Property(property="role", type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name_role", type="string", example="Admin")
     *                 ),
     *                 @OA\Property(property="anggota", type="object",
     *                     @OA\Property(property="id_anggota", type="integer", example=10),
     *                     @OA\Property(property="nama_lengkap", type="string", example="Siti Nurhaliza"),
     *                     @OA\Property(property="email", type="string", example="siti@example.com")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validasi gagal."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Data user tidak ditemukan.")
     *         )
     *     )
     * )
     */

    public function update(Request $request, $id)
    {
        try {
            // Validasi input
            $request->validate([
                'name' => 'string|max:255',
                'email' => 'unique:users,email,' . $id,
                'username' => 'string|max:50|unique:users,username,' . $id,
                'password' => 'nullable|string|min:4', // Password optional
                'role_id' => 'exists:roles,id',
                'id_anggota' => 'nullable|exists:t_anggota,id_anggota',
            ]);

            // Cari user berdasarkan ID
            $user = User::findOrFail($id);

            // Update data user
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'role_id' => $request->role_id,
                'id_anggota' => $request->id_anggota,
                'password' => $request->password ? bcrypt($request->password) : $user->password, // Jika ada password baru, update
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User berhasil diperbarui!',
                'data' => $user->load('role:id,name_role', 'anggota:id_anggota,nama_lengkap,email'),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *     path="/api/user/{id}",
     *     tags={"User"},
     *     summary="Hapus user",
     *     description="Menghapus user dari sistem berdasarkan ID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID user yang akan dihapus",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User berhasil dihapus!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Data user tidak ditemukan.")
     *         )
     *     )
     * )
     */

    public function destroy($id)
    {
        try {
            // Cari user berdasarkan ID
            $user = User::findOrFail($id);

            // Hapus user
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
