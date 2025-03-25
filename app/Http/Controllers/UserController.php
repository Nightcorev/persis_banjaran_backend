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
