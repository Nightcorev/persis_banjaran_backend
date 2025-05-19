<?php

namespace App\Http\Controllers;

use App\Models\AnggotaModel;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;


class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="Login pengguna",
     *     description="Login user dan mengembalikan token yang bisa digunakan untuk mengakses API lainnya.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password","device_info","ip_address"},
     *             @OA\Property(property="username", type="string", example="admin"),
     *             @OA\Property(property="password", type="string", example="admin123"),
     *             @OA\Property(property="device_info", type="string", example="Chrome on Windows"),
     *             @OA\Property(property="ip_address", type="string", example="192.168.1.10")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login berhasil"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_at", type="string", format="date-time"),
     *             @OA\Property(property="role", type="string", example="Super Admin"),
     *             @OA\Property(
     *                 property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name_user", type="string"),
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="role", type="string")
     *             ),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Username atau password salah")
     * )
     */
    public function login(Request $request)
    {
        // 🔹 Validasi Input
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'device_info' => 'required|string',
            'ip_address' => 'required|string',
        ]);

        // 🔹 Cari User Berdasarkan Username
        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Username atau password salah'], 401);
        }

        $anggota = AnggotaModel::where('id_anggota', $user->id_anggota)->first();
        if ($anggota) {
            $user->id_master_jamaah = $anggota->id_master_jamaah;
        }

        // 🔹 Cek Apakah User Sudah Punya Token di Perangkat Ini
        $existingToken = UserToken::where('user_id', $user->id)
            ->where('device_info', $request->device_info)
            ->first();

        $token = Str::random(60); // Generate token unik
        $expiresAt = Carbon::now()->addHours(12); // Token aktif 12 jam

        if ($existingToken) {
            // 🔹 Perbarui Token & Expiry Jika Sudah Ada
            $existingToken->update([
                'token' => $token,
                'expires_at' => $expiresAt,
                'ip_address' => $request->ip_address,
            ]);
        } else {
            // 🔹 Buat Token Baru Jika Belum Ada
            UserToken::create([
                'user_id' => $user->id,
                'token' => $token,
                'device_info' => $request->device_info,
                'ip_address' => $request->ip_address,
                'expires_at' => $expiresAt,
            ]);
        }

        // 🔹 Ambil Role & Permissions
        $role = $user->role->name_role;
        $permissions = $user->role->permissions->pluck('name_permission');

        // 🔹 Kirim Respons ke Frontend
        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt,
            'role' => $role,
            'user' => [
                'id' => $user->id,
                'name_user' => $user->name,
                'username' => $user->username,
                'role' => $role,
                'id_master_jamaah' => $user->id_master_jamaah,

            ],
            'permissions' => $permissions
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Authentication"},
     *     summary="Logout pengguna",
     *     description="Logout pengguna dan menghapus token dari database.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logout successful")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid token or token missing")
     * )
     */
    public function logout(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token required'], 400);
        }

        $deleted = UserToken::where('token', $token)->delete();

        if ($deleted) {
            return response()->json(['message' => 'Logout successful']);
        } else {
            return response()->json(['message' => 'Invalid token'], 400);
        }
    }
}
