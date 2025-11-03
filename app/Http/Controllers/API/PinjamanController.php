<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pinjaman\StorePinjamanRequest;
use App\Http\Requests\Pinjaman\ApprovePinjamanRequest;
use App\Http\Requests\Pinjaman\RejectPinjamanRequest;
use App\Services\PinjamanService;
use App\Models\Pinjaman;
use App\Models\User;
use App\Helpers\ResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PinjamanController extends Controller
{
    protected $pinjamanService;

    public function __construct(PinjamanService $pinjamanService)
    {
        $this->pinjamanService = $pinjamanService;
    }

    /**
     * GET /api/v1/pinjaman
     * List pinjaman (filtered by role & koperasi)
     */
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $query = Pinjaman::with(['user', 'koperasi', 'approvedBy']);

        // Data isolation
        if ($currentUser->role !== 'admin') {
            $query->where('koperasi_id', $currentUser->koperasi_id);
        } elseif ($request->has('koperasi_id')) {
            $query->where('koperasi_id', $request->koperasi_id);
        }

        // Anggota hanya bisa lihat pinjaman sendiri
        if ($currentUser->role === 'anggota') {
            $query->where('user_id', $currentUser->id);
        }

        // Filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('tanggal_dari') && $request->has('tanggal_sampai')) {
            $query->whereBetween('tanggal_pengajuan', [
                $request->tanggal_dari,
                $request->tanggal_sampai
            ]);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'tanggal_pengajuan');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $pinjaman = $query->paginate($perPage);

        return ResponseFormatter::success($pinjaman, 'Data pinjaman berhasil diambil');
    }

    /**
     * POST /api/v1/pinjaman/simulasi
     * Simulasi cicilan (preview tanpa save DB)
     */
    public function simulasi(Request $request)
    {
        $request->validate([
            'jumlah_pinjaman' => 'required|numeric|min:100000',
            'tenor_bulan' => 'required|integer|in:6,12,24',
            'bunga_persen' => 'nullable|numeric|min:0|max:10',
        ]);

        $currentUser = auth()->user();

        // Gunakan bunga dari koperasi kalau tidak diisi
        $bungaPersen = $request->bunga_persen ?? $currentUser->koperasi->bunga_default;

        $simulasi = $this->pinjamanService->simulasiCicilan(
            $request->jumlah_pinjaman,
            $bungaPersen,
            $request->tenor_bulan,
            $request->tanggal_mulai
        );

        return ResponseFormatter::success($simulasi, 'Simulasi cicilan berhasil');
    }

    /**
     * POST /api/v1/pinjaman
     * Ajukan pinjaman baru
     */
    public function store(StorePinjamanRequest $request)
    {
        try {
            $currentUser = auth()->user();

            // Authorization check
            if ($currentUser->role === 'anggota') {
                // Anggota hanya bisa ajukan untuk diri sendiri
                if ($request->user_id != $currentUser->id) {
                    return ResponseFormatter::forbidden('Anda hanya bisa mengajukan pinjaman untuk diri sendiri');
                }
            } elseif ($currentUser->role === 'kasir') {
                // Kasir bisa ajukan atas nama anggota (tapi harus se-koperasi)
                $targetUser = User::findOrFail($request->user_id);
                if ($targetUser->koperasi_id != $currentUser->koperasi_id) {
                    return ResponseFormatter::forbidden('Tidak dapat mengajukan pinjaman untuk anggota koperasi lain');
                }
            } elseif ($currentUser->role !== 'admin') {
                return ResponseFormatter::forbidden('Anda tidak memiliki akses untuk mengajukan pinjaman');
            }

            $data = $request->validated();
            $data['created_by'] = $currentUser->id;

            $pinjaman = $this->pinjamanService->ajukanPinjaman($data);

            return ResponseFormatter::success(
                $pinjaman->load(['user', 'koperasi', 'createdBy']),
                'Pinjaman berhasil diajukan',
                201
            );

        } catch (\Exception $e) {
            Log::error('Error ajukan pinjaman: ' . $e->getMessage());
            return ResponseFormatter::error('Gagal mengajukan pinjaman: ' . $e->getMessage(), null, 400);
        }
    }

    /**
     * GET /api/v1/pinjaman/{id}
     * Get detail pinjaman
     */
    public function show($id)
    {
        try {
            $currentUser = auth()->user();
            $pinjaman = Pinjaman::with([
                'user',
                'koperasi',
                'approvedBy',
                'createdBy',
                'jadwalCicilan'
            ])->findOrFail($id);

            // Authorization check
            if ($currentUser->role === 'anggota' && $pinjaman->user_id != $currentUser->id) {
                return ResponseFormatter::forbidden('Anda hanya bisa melihat pinjaman sendiri');
            }

            if ($currentUser->role !== 'admin' && $pinjaman->koperasi_id != $currentUser->koperasi_id) {
                return ResponseFormatter::forbidden('Akses ditolak');
            }

            return ResponseFormatter::success($pinjaman, 'Detail pinjaman berhasil diambil');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseFormatter::notFound('Pinjaman tidak ditemukan');
        }
    }

    /**
     * PUT /api/v1/pinjaman/{id}/approve
     * Approve pinjaman (manajer/admin only)
     */
    public function approve($id, ApprovePinjamanRequest $request)
    {
        try {
            $currentUser = auth()->user();

            // Authorization check
            if (!in_array($currentUser->role, ['manajer', 'admin'])) {
                return ResponseFormatter::forbidden('Hanya manajer atau admin yang bisa approve pinjaman');
            }

            $pinjaman = Pinjaman::findOrFail($id);

            // Check koperasi access
            if ($currentUser->role !== 'admin' && $pinjaman->koperasi_id != $currentUser->koperasi_id) {
                return ResponseFormatter::forbidden('Akses ditolak');
            }

            $pinjaman = $this->pinjamanService->approvePinjaman(
                $id,
                $currentUser->id,
                $request->catatan_approval,
                $request->tanggal_mulai_cicilan
            );

            return ResponseFormatter::success(
                $pinjaman,
                'Pinjaman berhasil di-approve'
            );

        } catch (\Exception $e) {
            Log::error('Error approve pinjaman: ' . $e->getMessage());
            return ResponseFormatter::error('Gagal approve pinjaman: ' . $e->getMessage(), null, 400);
        }
    }

    /**
     * PUT /api/v1/pinjaman/{id}/reject
     * Reject pinjaman (manajer/admin only)
     */
    public function reject($id, RejectPinjamanRequest $request)
    {
        try {
            $currentUser = auth()->user();

            // Authorization check
            if (!in_array($currentUser->role, ['manajer', 'admin'])) {
                return ResponseFormatter::forbidden('Hanya manajer atau admin yang bisa reject pinjaman');
            }

            $pinjaman = Pinjaman::findOrFail($id);

            // Check koperasi access
            if ($currentUser->role !== 'admin' && $pinjaman->koperasi_id != $currentUser->koperasi_id) {
                return ResponseFormatter::forbidden('Akses ditolak');
            }

            $pinjaman = $this->pinjamanService->rejectPinjaman(
                $id,
                $currentUser->id,
                $request->catatan_penolakan
            );

            return ResponseFormatter::success(
                $pinjaman,
                'Pinjaman berhasil ditolak'
            );

        } catch (\Exception $e) {
            Log::error('Error reject pinjaman: ' . $e->getMessage());
            return ResponseFormatter::error('Gagal reject pinjaman: ' . $e->getMessage(), null, 400);
        }
    }

    /**
     * PUT /api/v1/pinjaman/{id}/cairkan
     * Cairkan pinjaman (kasir/admin only)
     */
    public function cairkan($id)
    {
        try {
            $currentUser = auth()->user();

            // Authorization check
            if (!in_array($currentUser->role, ['kasir', 'admin'])) {
                return ResponseFormatter::forbidden('Hanya kasir atau admin yang bisa cairkan pinjaman');
            }

            $pinjaman = Pinjaman::findOrFail($id);

            // Check koperasi access
            if ($currentUser->role !== 'admin' && $pinjaman->koperasi_id != $currentUser->koperasi_id) {
                return ResponseFormatter::forbidden('Akses ditolak');
            }

            $pinjaman = $this->pinjamanService->cairkanPinjaman($id, $currentUser->id);

            return ResponseFormatter::success(
                $pinjaman->load(['user', 'jadwalCicilan']),
                'Pinjaman berhasil dicairkan. Cicilan mulai berjalan.'
            );

        } catch (\Exception $e) {
            Log::error('Error cairkan pinjaman: ' . $e->getMessage());
            return ResponseFormatter::error('Gagal cairkan pinjaman: ' . $e->getMessage(), null, 400);
        }
    }

    /**
     * GET /api/v1/pinjaman/{id}/jadwal-cicilan
     * Get jadwal cicilan pinjaman
     */
    public function jadwalCicilan($id)
    {
        try {
            $currentUser = auth()->user();
            $pinjaman = Pinjaman::with(['jadwalCicilan' => function($query) {
                $query->orderBy('cicilan_ke', 'asc');
            }])->findOrFail($id);

            // Authorization check
            if ($currentUser->role === 'anggota' && $pinjaman->user_id != $currentUser->id) {
                return ResponseFormatter::forbidden('Anda hanya bisa melihat jadwal cicilan sendiri');
            }

            if ($currentUser->role !== 'admin' && $pinjaman->koperasi_id != $currentUser->koperasi_id) {
                return ResponseFormatter::forbidden('Akses ditolak');
            }

            $data = [
                'pinjaman' => [
                    'id' => $pinjaman->id,
                    'no_pinjaman' => $pinjaman->no_pinjaman,
                    'jumlah_pinjaman' => $pinjaman->jumlah_pinjaman,
                    'tenor_bulan' => $pinjaman->tenor_bulan,
                    'status' => $pinjaman->status,
                ],
                'jadwal_cicilan' => $pinjaman->jadwalCicilan,
                'summary' => [
                    'total_cicilan' => $pinjaman->jadwalCicilan->count(),
                    'sudah_bayar' => $pinjaman->jadwalCicilan->where('status', 'sudah_bayar')->count(),
                    'belum_bayar' => $pinjaman->jadwalCicilan->where('status', 'belum_bayar')->count(),
                    'telat' => $pinjaman->jadwalCicilan->where('status', 'telat')->count(),
                ]
            ];

            return ResponseFormatter::success($data, 'Jadwal cicilan berhasil diambil');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseFormatter::notFound('Pinjaman tidak ditemukan');
        }
    }

    /**
     * DELETE /api/v1/pinjaman/{id}
     * Hapus pinjaman (admin only, dan hanya yang status pending/rejected)
     */
    public function destroy($id)
    {
        try {
            $currentUser = auth()->user();

            if ($currentUser->role !== 'admin') {
                return ResponseFormatter::forbidden('Hanya admin yang bisa menghapus pinjaman');
            }

            $pinjaman = Pinjaman::findOrFail($id);

            // Hanya bisa hapus pending/rejected
            if (!in_array($pinjaman->status, ['pending', 'rejected'])) {
                return ResponseFormatter::error('Tidak dapat menghapus pinjaman yang sudah approved/active/lunas', null, 400);
            }

            $pinjaman->delete();

            return ResponseFormatter::success(null, 'Pinjaman berhasil dihapus');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseFormatter::notFound('Pinjaman tidak ditemukan');
        } catch (\Exception $e) {
            Log::error('Error hapus pinjaman: ' . $e->getMessage());
            return ResponseFormatter::error('Gagal menghapus pinjaman', null, 500);
        }
    }
}
