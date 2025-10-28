<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKoperasiAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Admin Kemenkop bisa akses semua koperasi
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Untuk role lain, cek koperasi_id
        $koperasiId = $request->route('koperasiId')
            ?? $request->input('koperasi_id')
            ?? $request->koperasi_id;

        if ($koperasiId && $user->koperasi_id !== (int)$koperasiId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to different koperasi'
            ], 403);
        }

        return $next($request);
    }
}
