<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Helpers\ResponseFormatter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            return ResponseFormatter::unauthorized('Email atau password salah');
        }

        // Check if user is active
        if ($user->status !== 'active') {
            return ResponseFormatter::forbidden('Akun Anda tidak aktif. Hubungi administrator.');
        }

        // Create access token
        $token = $user->createToken('auth_token')->accessToken;

        // Return success response with user data and token
        return ResponseFormatter::success([
            'user' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->email,
                'role' => $user->role,
                'no_anggota' => $user->no_anggota,
                'koperasi' => $user->koperasi ? [
                    'id' => $user->koperasi->id,
                    'nama' => $user->koperasi->nama,
                    'kode' => $user->koperasi->kode_koperasi,
                ] : null,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login berhasil');
    }

    /**
     * Get authenticated user info
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('koperasi');

        return ResponseFormatter::success([
            'id' => $user->id,
            'nama' => $user->nama,
            'email' => $user->email,
            'no_anggota' => $user->no_anggota,
            'nik' => $user->nik,
            'alamat' => $user->alamat,
            'no_hp' => $user->no_hp,
            'role' => $user->role,
            'status' => $user->status,
            'foto_profile' => $user->foto_profile,
            'koperasi' => $user->koperasi ? [
                'id' => $user->koperasi->id,
                'nama' => $user->koperasi->nama,
                'kode' => $user->koperasi->kode_koperasi,
                'alamat' => $user->koperasi->alamat,
                'email' => $user->koperasi->email,
                'no_telp' => $user->koperasi->no_telp,
            ] : null,
        ], 'Data user berhasil diambil');
    }

    /**
     * Logout (revoke token)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->token()->revoke();

        return ResponseFormatter::success(null, 'Logout berhasil');
    }

    /**
     * Change password
     *
     * @param ChangePasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return ResponseFormatter::error('Password lama salah', null, 400);
        }

        // Update password (auto-hashed by model cast)
        $user->password = $request->new_password;
        $user->save();

        return ResponseFormatter::success(null, 'Password berhasil diubah');
    }

    /**
     * Refresh token (optional - untuk refresh access token)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        // Revoke old token
        $request->user()->token()->revoke();

        // Create new token
        $token = $user->createToken('auth_token')->accessToken;

        return ResponseFormatter::success([
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Token berhasil di-refresh');
    }
}
