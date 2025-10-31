<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Simpanan\StoreSimpananRequest;
use App\Helpers\ResponseFormatter;
use App\Models\Simpanan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SimpananController extends Controller
{
    /**
     * Display a listing of simpanan
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $query = Simpanan::with(['user', 'koperasi', 'createdBy']);

        // Filter by koperasi (admin bisa lihat semua, role lain cuma koperasi sendiri)
        if ($currentUser->role !== 'admin') {
            $query->where('koperasi_id', $currentUser->koperasi_id);
        } elseif ($request->has('koperasi_id')) {
            $query->where('koperasi_id', $request->koperasi_id);
        }

        // Filter by user/anggota (anggota cuma bisa lihat simpanan sendiri)
        if ($currentUser->role === 'anggota') {
            $query->where('user_id', $currentUser->id);
        } elseif ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by jenis
        if ($request->has('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        // Filter by tanggal range
        if ($request->has('tanggal_dari')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_dari);
        }
        if ($request->has('tanggal_sampai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_sampai);
        }

        // Search by anggota name
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('no_anggota', 'like', "%{$search}%");
            });
        }

        // Order by
        $query->orderBy('tanggal', 'desc');

        // Paginate
        $perPage = $request->get('per_page', 15);
        $simpanan = $query->paginate($perPage);

        return ResponseFormatter::success($simpanan, 'Data simpanan berhasil diambil');
    }

    /**
     * Store a newly created simpanan
     *
     * @param StoreSimpananRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreSimpananRequest $request)
    {
        try {
            DB::beginTransaction();

            $currentUser = auth()->user();

            // Cek apakah user_id adalah anggota dari koperasi yang sama
            $anggota = User::where('id', $request->user_id)
                ->where('koperasi_id', $currentUser->koperasi_id)
                ->where('role', 'anggota')
                ->first();

            if (!$anggota) {
                return ResponseFormatter::error(
                    'Anggota tidak ditemukan atau bukan anggota koperasi Anda',
                    null,
                    404
                );
            }

            // Create simpanan
            $simpanan = Simpanan::create([
                'koperasi_id' => $currentUser->koperasi_id,
                'user_id' => $request->user_id,
                'jenis' => $request->jenis,
                'jumlah' => $request->jumlah,
                'tanggal' => $request->tanggal,
                'keterangan' => $request->keterangan,
                'created_by' => $currentUser->id,
            ]);

            DB::commit();

            return ResponseFormatter::success(
                $simpanan->load(['user', 'koperasi', 'createdBy']),
                'Simpanan berhasil ditambahkan',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(
                'Gagal menambahkan simpanan: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Display the specified simpanan
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $currentUser = auth()->user();
        $simpanan = Simpanan::with(['user', 'koperasi', 'createdBy'])->find($id);

        if (!$simpanan) {
            return ResponseFormatter::notFound('Simpanan tidak ditemukan');
        }

        // Authorization check
        if ($currentUser->role === 'anggota' && $currentUser->id != $simpanan->user_id) {
            return ResponseFormatter::forbidden('Anda tidak memiliki akses untuk melihat simpanan ini');
        }

        if ($currentUser->role !== 'admin' && $currentUser->koperasi_id !== $simpanan->koperasi_id) {
            return ResponseFormatter::forbidden('Anda tidak memiliki akses untuk melihat simpanan dari koperasi lain');
        }

        return ResponseFormatter::success([
            'id' => $simpanan->id,
            'anggota' => [
                'id' => $simpanan->user->id,
                'no_anggota' => $simpanan->user->no_anggota,
                'nama' => $simpanan->user->nama,
            ],
            'koperasi' => [
                'id' => $simpanan->koperasi->id,
                'nama' => $simpanan->koperasi->nama,
            ],
            'jenis' => $simpanan->jenis,
            'jumlah' => $simpanan->jumlah,
            'tanggal' => $simpanan->tanggal->format('Y-m-d'),
            'keterangan' => $simpanan->keterangan,
            'created_by' => $simpanan->createdBy ? [
                'id' => $simpanan->createdBy->id,
                'nama' => $simpanan->createdBy->nama,
            ] : null,
            'created_at' => $simpanan->created_at,
        ], 'Detail simpanan berhasil diambil');
    }

    /**
     * Get total simpanan by user
     *
     * @param Request $request
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTotalByUser(Request $request, $userId)
    {
        $currentUser = auth()->user();
        $user = User::find($userId);

        if (!$user) {
            return ResponseFormatter::notFound('User tidak ditemukan');
        }

        // Authorization check
        if ($currentUser->role === 'anggota' && $currentUser->id != $userId) {
            return ResponseFormatter::forbidden('Anda tidak memiliki akses untuk melihat data ini');
        }

        if ($currentUser->role !== 'admin' && $currentUser->koperasi_id !== $user->koperasi_id) {
            return ResponseFormatter::forbidden('Anda tidak memiliki akses untuk melihat data dari koperasi lain');
        }

        $summary = Simpanan::getSummaryByUser($userId);

        return ResponseFormatter::success([
            'user' => [
                'id' => $user->id,
                'no_anggota' => $user->no_anggota,
                'nama' => $user->nama,
            ],
            'total_simpanan' => $summary['total'],
            'simpanan_pokok' => $summary['pokok'],
            'simpanan_wajib' => $summary['wajib'],
            'simpanan_sukarela' => $summary['sukarela'],
        ], 'Total simpanan berhasil diambil');
    }

    /**
     * Get simpanan history by user
     *
     * @param Request $request
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHistoryByUser(Request $request, $userId)
    {
        $currentUser = auth()->user();
        $user = User::find($userId);

        if (!$user) {
            return ResponseFormatter::notFound('User tidak ditemukan');
        }

        // Authorization check
        if ($currentUser->role === 'anggota' && $currentUser->id != $userId) {
            return ResponseFormatter::forbidden('Anda tidak memiliki akses untuk melihat data ini');
        }

        if ($currentUser->role !== 'admin' && $currentUser->koperasi_id !== $user->koperasi_id) {
            return ResponseFormatter::forbidden('Anda tidak memiliki akses untuk melihat data dari koperasi lain');
        }

        $query = Simpanan::with(['createdBy'])
            ->where('user_id', $userId);

        // Filter by jenis
        if ($request->has('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        // Filter by tanggal range
        if ($request->has('tanggal_dari') && $request->has('tanggal_sampai')) {
            $query->betweenDates($request->tanggal_dari, $request->tanggal_sampai);
        }

        $simpanan = $query->latest()->paginate($request->get('per_page', 20));

        return ResponseFormatter::success($simpanan, 'History simpanan berhasil diambil');
    }

    /**
     * Delete simpanan (soft delete atau hard delete - sesuaikan kebutuhan)
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $simpanan = Simpanan::find($id);

            if (!$simpanan) {
                return ResponseFormatter::notFound('Simpanan tidak ditemukan');
            }

            DB::beginTransaction();

            $simpanan->delete();

            DB::commit();

            return ResponseFormatter::success(null, 'Simpanan berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(
                'Gagal menghapus simpanan: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}
