<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\UserToken;

class AuthenticateWithToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken(); // Ambil token dari header Authorization

        if (!$token) {
            return response()->json(['status' => '401', 'message' => 'Unauthorized'], 401);
        }

        $userToken = UserToken::where('token', $token)->first();

        if (!$userToken) {
            return response()->json(['status' => '401', 'message' => 'Token not found'], 401);
        }

        // Cek apakah token masih valid dalam waktu 12 jam terakhir
        $expiredAt = Carbon::parse($userToken->updated_at)->addHours(12);
        if (Carbon::now()->greaterThan($expiredAt)) {
            $userToken->delete(); // Hapus token jika expired
            return response()->json(['status' => '401', 'message' => 'Session expired'], 401);
        }

        // Update waktu terakhir aktivitas
        $userToken->update(['expires_at' => Carbon::now()->addHours(12)]);

        // Set user dalam request
        $request->merge(['user' => $userToken->user]);

        return $next($request);
    }
}
