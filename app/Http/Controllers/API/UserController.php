<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Helpers\ResponseFormatter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of users
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = User::with('koperasi');

        // Filter by koperasi (admin bisa lihat semua, role lain cuma koperasi sendiri)
        if ($user->role !== 'admin') {
            $query->where('koperasi_id', $user->koperasi_id);
        } elseif ($request->has('koperasi_id')) {
            $query->where('koperasi_id', $request->koperasi_id);
        }

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('no_anggota', 'like', "%{$search}%");
            });
        }

        // Order by
        $query->orderBy('created_at', 'desc');

        // Paginate
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return ResponseFormatter::success($users, 'Data user berhasil diambil');
    }

    /**
     * Store a newly created user (Daftar anggota baru)
     *
     * @param StoreUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreUserRequest $request)
    {
        try {
            DB::beginTransaction();

            $currentUser = auth()->user();

            // Generate no_anggota untuk role anggota
            $noAnggota = null;
            if ($request->role === 'anggota') {
                $noAnggota = $this->generateNoAnggota($currentUser->koperasi_id);
            }

            // Create user
            $user = User::create([
                'koperasi_id' => $currentUser->koperasi_id,
                'no_anggota' => $noAnggota,
                'nik' => $request->nik,
                'nama' => $request->nama,
                'alamat' => $request->alamat,
                'no_hp' => $request->no_hp,
                'email' => $request->email,
                'password' => $request->password, // Auto-hashed by model
                'role' => $request->role,
                'status' => 'active',
                'registered_by' => $currentUser->id,
            ]);

            DB::commit();

            return ResponseFormatter::success(
                $user->load('koperasi'),
                'User berhasil didaftarkan',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(
                'Gagal mendaftarkan user: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Display the specified user
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $currentUser = auth()->user();
        $user = User::with(['koperasi', 'registeredBy'])->find($id);

        if (!$user) {
            return ResponseFormatter::notFound('User tidak ditemukan');
        }

        // Authorization check
        if ($currentUser->role === 'anggota' && $currentUser->id != $user->id) {
            return ResponseFormatter::forbidden('Anda tidak memiliki akses untuk melihat data user ini');
        }

        if ($currentUser->role !== 'admin' && $currentUser->koperasi_id !== $user->koperasi_id) {
            return ResponseFormatter::forbidden('Anda tidak memiliki akses untuk melihat data user dari koperasi lain');
        }

        return ResponseFormatter::success([
            'id' => $user->id,
            'koperasi' => $user->koperasi ? [
                'id' => $user->koperasi->id,
                'nama' => $user->koperasi->nama,
                'kode' => $user->koperasi->kode_koperasi,
            ] : null,
            'no_anggota' => $user->no_anggota,
            'nik' => $user->nik,
            'nama' => $user->nama,
            'alamat' => $user->alamat,
            'no_hp' => $user->no_hp,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'foto_profile' => $user->foto_profile,
            'registered_by' => $user->registeredBy ? [
                'id' => $user->registeredBy->id,
                'nama' => $user->registeredBy->nama,
            ] : null,
            'created_at' => $user->created_at,
        ], 'Detail user berhasil diambil');
    }

    /**
     * Update the specified user
     *
     * @param UpdateUserRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateUserRequest $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return ResponseFormatter::notFound('User tidak ditemukan');
            }

            DB::beginTransaction();

            // Update only filled fields
            $user->update($request->only([
                'nik',
                'nama',
                'alamat',
                'no_hp',
                'email',
                'password', // Auto-hashed by model
                'status',
            ]));

            DB::commit();

            return ResponseFormatter::success(
                $user->load('koperasi'),
                'Data user berhasil diupdate'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(
                'Gagal mengupdate user: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Remove the specified user
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return ResponseFormatter::notFound('User tidak ditemukan');
            }

            // Tidak bisa hapus diri sendiri
            if (auth()->id() == $user->id) {
                return ResponseFormatter::error('Anda tidak dapat menghapus akun sendiri');
            }

            DB::beginTransaction();

            $user->delete();

            DB::commit();

            return ResponseFormatter::success(null, 'User berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(
                'Gagal menghapus user: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Suspend user
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function suspend(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return ResponseFormatter::notFound('User tidak ditemukan');
            }

            // Tidak bisa suspend diri sendiri
            if (auth()->id() == $user->id) {
                return ResponseFormatter::error('Anda tidak dapat suspend akun sendiri');
            }

            DB::beginTransaction();

            $user->update(['status' => 'suspended']);

            DB::commit();

            return ResponseFormatter::success(
                $user->load('koperasi'),
                'User berhasil di-suspend'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(
                'Gagal suspend user: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Activate user
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return ResponseFormatter::notFound('User tidak ditemukan');
            }

            DB::beginTransaction();

            $user->update(['status' => 'active']);

            DB::commit();

            return ResponseFormatter::success(
                $user->load('koperasi'),
                'User berhasil diaktifkan'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(
                'Gagal mengaktifkan user: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Generate nomor anggota
     * Format: {KODE_KOPERASI}-{URUTAN}
     * Contoh: JKT001-0042
     *
     * @param int $koperasiId
     * @return string
     */
    private function generateNoAnggota($koperasiId)
    {
        $koperasi = \App\Models\Koperasi::find($koperasiId);
        $kodeKoperasi = $koperasi->kode_koperasi;

        // Get last anggota number
        $lastAnggota = User::where('koperasi_id', $koperasiId)
            ->where('role', 'anggota')
            ->where('no_anggota', 'like', "{$kodeKoperasi}-%")
            ->orderBy('id', 'desc')
            ->first();

        $urutan = 1;
        if ($lastAnggota) {
            $lastNumber = (int) substr($lastAnggota->no_anggota, -4);
            $urutan = $lastNumber + 1;
        }

        return $kodeKoperasi . '-' . str_pad($urutan, 4, '0', STR_PAD_LEFT);
    }
}
