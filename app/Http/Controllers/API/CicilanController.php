<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cicilan\BayarCicilanRequest;
use App\Services\CicilanService;
use App\Models\JadwalCicilan;
use App\Models\Pinjaman;
use App\Models\PembayaranCicilan;
use App\Helpers\ResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CicilanController extends Controller
{
    protected $cicilanService;

    public function __construct(CicilanService $cicilanService)
    {
        $this->cicilanService = $cicilanService;
    }

    /**
     * GET /api/v1/pinjaman/{pinjamanId}/cicilan
     * List cicilan per pinjaman (untuk user biasa)
     */
    public function index($pinjamanId, Request $request)
    {
        try {
            $currentUser = auth()->user();
            $pinjaman = Pinjaman::findOrFail($pinjamanId);

            // Authorization check
            if ($currentUser->role === 'anggota' && $pinjaman->user_id != $currentUser->id) {
                return ResponseFormatter::forbidden('Anda hanya bisa melihat cicilan sendiri');
            }

            if ($currentUser->role !== 'admin' && $pinjaman->koperasi_id != $currentUser->koperasi_id) {
                return ResponseFormatter::forbidden('Akses ditolak');
            }

            $query = JadwalCicilan::with(['pembayaranCicilan', 'dibayarOleh'])
                ->where('pinjaman_id', $pinjamanId);

            // Filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'cicilan_ke');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $cicilan = $query->get();

            // Get statistik
            $statistik = $this->cicilanService->getStatistikCicilan($pinjamanId);

            return ResponseFormatter::success([
                'pinjaman' => $pinjaman->only(['id', 'no_pinjaman', 'jumlah_pinjaman', 'status']),
                'cicilan' => $cicilan,
                'statistik' => $statistik,
            ], 'Data cicilan berhasil diambil');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseFormatter::notFound('Pinjaman tidak ditemukan');
        }
    }

    /**
     * GET /api/v1/cicilan (Admin only - global list)
     * List all cicilan dengan filter heavy
     */
    public function indexGlobal(Request $request)
    {
        $currentUser = auth()->user();

        // Authorization: Admin only
        if ($currentUser->role !== 'admin') {
            return ResponseFormatter::forbidden('Akses terbatas untuk admin');
        }

        $query = JadwalCicilan::with(['pinjaman.user', 'pinjaman.koperasi', 'dibayarOleh']);

        // Filters
        if ($request->has('koperasi_id')) {
            $query->whereHas('pinjaman', function($q) use ($request) {
                $q->where('koperasi_id', $request->koperasi_id);
            });
        }

        if ($request->has('user_id')) {
            $query->whereHas('pinjaman', function($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });
        }

        if ($request->has('pinjaman_id')) {
            $query->where('pinjaman_id', $request->pinjaman_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('tanggal_dari') && $request->has('tanggal_sampai')) {
            $query->whereBetween('tanggal_jatuh_tempo', [
                $request->tanggal_dari,
                $request->tanggal_sampai
            ]);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'tanggal_jatuh_tempo');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $cicilan = $query->paginate($perPage);

        return ResponseFormatter::success($cicilan, 'Data cicilan berhasil diambil');
    }

    /**
     * GET /api/v1/cicilan/{id}
     * Detail cicilan dengan history pembayaran
     */
    public function show($id)
    {
        try {
            $currentUser = auth()->user();
            $cicilan = JadwalCicilan::with([
                'pinjaman.user',
                'pinjaman.koperasi',
                'pembayaranCicilan.dibayarOleh',
                'dibayarOleh'
            ])->findOrFail($id);

            // Authorization check
            if ($currentUser->role === 'anggota' && $cicilan->pinjaman->user_id != $currentUser->id) {
                return ResponseFormatter::forbidden('Anda hanya bisa melihat cicilan sendiri');
            }

            if ($currentUser->role !== 'admin' && $cicilan->pinjaman->koperasi_id != $currentUser->koperasi_id) {
                return ResponseFormatter::forbidden('Akses ditolak');
            }

            // Calculate sisa
            $sisa = $cicilan->getSisaBayar();

            return ResponseFormatter::success([
                'cicilan' => $cicilan,
                'sisa_bayar' => $sisa,
                'total_dibayar' => $cicilan->getTotalDibayar(),
                'is_lunas' => $cicilan->isLunas(),
            ], 'Detail cicilan berhasil diambil');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseFormatter::notFound('Cicilan tidak ditemukan');
        }
    }

    /**
     * POST /api/v1/cicilan/{id}/preview-bayar
     * Preview pembayaran (sebelum submit)
     */
    public function previewBayar($id, Request $request)
    {
        try {
            $request->validate([
                'jumlah_bayar' => 'required|numeric|min:1000',
            ]);

            $currentUser = auth()->user();
            $cicilan = JadwalCicilan::with('pinjaman')->findOrFail($id);

            // Authorization check
            if (!in_array($currentUser->role, ['kasir', 'admin'])) {
                return ResponseFormatter::forbidden('Hanya kasir atau admin yang bisa memproses pembayaran');
            }

            if ($currentUser->role !== 'admin' && $cicilan->pinjaman->koperasi_id != $currentUser->koperasi_id) {
                return ResponseFormatter::forbidden('Akses ditolak');
            }

            $preview = $this->cicilanService->previewPembayaran($id, $request->jumlah_bayar);

            return ResponseFormatter::success($preview, 'Preview pembayaran berhasil');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseFormatter::notFound('Cicilan tidak ditemukan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseFormatter::validationError($e->errors());
        }
    }

    /**
     * POST /api/v1/cicilan/{id}/bayar
     * Proses pembayaran cicilan (support partial payment)
     */
    public function bayar($id, BayarCicilanRequest $request)
    {
        try {
            $currentUser = auth()->user();
            $cicilan = JadwalCicilan::with('pinjaman')->findOrFail($id);

            // Authorization check
            if (!in_array($currentUser->role, ['kasir', 'admin'])) {
                return ResponseFormatter::forbidden('Hanya kasir atau admin yang bisa memproses pembayaran');
            }

            if ($currentUser->role !== 'admin' && $cicilan->pinjaman->koperasi_id != $currentUser->koperasi_id) {
                return ResponseFormatter::forbidden('Akses ditolak');
            }

            $metadata = [
                'metode_bayar' => $request->metode_bayar,
                'nomor_referensi' => $request->nomor_referensi,
                'keterangan' => $request->keterangan,
            ];

            $result = $this->cicilanService->prosesBayar(
                $id,
                $request->jumlah_bayar,
                $currentUser->id,
                $metadata
            );

            if (!$result['success']) {
                return ResponseFormatter::error($result['message'], null, 400);
            }

            return ResponseFormatter::success(
                $result['data'],
                $result['message'],
                201
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseFormatter::notFound('Cicilan tidak ditemukan');
        } catch (\Exception $e) {
            Log::error('Error bayar cicilan: ' . $e->getMessage());
            return ResponseFormatter::error('Gagal memproses pembayaran', null, 500);
        }
    }

    /**
     * GET /api/v1/pinjaman/{pinjamanId}/history-pembayaran
     * History pembayaran per pinjaman
     */
    public function historyPembayaran($pinjamanId)
    {
        try {
            $currentUser = auth()->user();
            $pinjaman = Pinjaman::findOrFail($pinjamanId);

            // Authorization check
            if ($currentUser->role === 'anggota' && $pinjaman->user_id != $currentUser->id) {
                return ResponseFormatter::forbidden('Anda hanya bisa melihat history pembayaran sendiri');
            }

            if ($currentUser->role !== 'admin' && $pinjaman->koperasi_id != $currentUser->koperasi_id) {
                return ResponseFormatter::forbidden('Akses ditolak');
            }

            $history = $this->cicilanService->getHistoryPembayaran($pinjamanId);

            return ResponseFormatter::success($history, 'History pembayaran berhasil diambil');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseFormatter::notFound('Pinjaman tidak ditemukan');
        }
    }

    /**
     * GET /api/v1/cicilan/{id}/pembayaran
     * History pembayaran per cicilan
     */
    public function pembayaranCicilan($id)
    {
        try {
            $currentUser = auth()->user();
            $cicilan = JadwalCicilan::with('pinjaman')->findOrFail($id);

            // Authorization check
            if ($currentUser->role === 'anggota' && $cicilan->pinjaman->user_id != $currentUser->id) {
                return ResponseFormatter::forbidden('Akses ditolak');
            }

            if ($currentUser->role !== 'admin' && $cicilan->pinjaman->koperasi_id != $currentUser->koperasi_id) {
                return ResponseFormatter::forbidden('Akses ditolak');
            }

            $pembayaran = PembayaranCicilan::with('dibayarOleh')
                ->where('jadwal_cicilan_id', $id)
                ->orderBy('tanggal_bayar', 'desc')
                ->get();

            return ResponseFormatter::success($pembayaran, 'History pembayaran cicilan berhasil diambil');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseFormatter::notFound('Cicilan tidak ditemukan');
        }
    }
}
