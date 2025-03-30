<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
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
            ],
            'permissions' => $permissions
        ]);
    }

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
