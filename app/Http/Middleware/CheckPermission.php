<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\UserToken;
use Illuminate\Support\Carbon;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $fitur, $jenis)
    {
        $user = $request->user;

        if (!$user) {
            return response()->json(['status' => '401', 'message' => 'Unauthorized'], 401);
        }

        // 🔹 Cek Token User & Expired atau Tidak
        $userToken = UserToken::where('user_id', $user->id)
            ->where('token', $request->bearerToken())
            ->first();

        if (!$userToken || $userToken->isExpired()) {
            return response()->json(['status' => '401', 'message' => 'Session expired. Please login again.'], 401);
        }

        // 🔹 Jika Role Super Admin, Langsung Izinkan Akses
        if ($user->role->name_role === 'Super Admin') {
            return $next($request);
        }

        // 🔹 Cek Apakah Role User Memiliki Permission
        $hasPermission = Role::where('id', $user->role_id)
            ->whereHas('permissions', function ($query) use ($fitur, $jenis) {
                $query->where('fitur', $fitur)
                    ->where('jenis_permission', $jenis);
            })->exists();

        if (!$hasPermission) {
            return response()->json(['status' => '401', 'message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
