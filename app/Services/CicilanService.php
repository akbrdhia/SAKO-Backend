<?php

namespace App\Services;

use App\Models\JadwalCicilan;
use App\Models\PembayaranCicilan;
use App\Models\Pinjaman;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class CicilanService
{
    /**
     * Proses pembayaran cicilan (support partial payment)
     * Strategy: Denda First → Bunga First → Pokok Last
     *
     * @param int $jadwalCicilanId
     * @param float $jumlahBayar
     * @param int $userId (kasir yang input)
     * @param array $metadata (metode_bayar, nomor_referensi, keterangan)
     * @return array ['success' => bool, 'message' => string, 'data' => PembayaranCicilan|null]
     */
    public function prosesBayar(
        int $jadwalCicilanId,
        float $jumlahBayar,
        int $userId,
        array $metadata = []
    ): array {
        DB::beginTransaction();

        try {
            $cicilan = JadwalCicilan::with('pinjaman')->findOrFail($jadwalCicilanId);

            // Validasi: cicilan sudah lunas?
            if ($cicilan->status === 'sudah_bayar') {
                return [
                    'success' => false,
                    'message' => 'Cicilan ini sudah lunas',
                    'data' => null,
                ];
            }

            // Validasi: pinjaman masih active?
            if ($cicilan->pinjaman->status !== 'active') {
                return [
                    'success' => false,
                    'message' => 'Pinjaman tidak dalam status aktif',
                    'data' => null,
                ];
            }

            // Validasi: jumlah bayar > 0
            if ($jumlahBayar <= 0) {
                return [
                    'success' => false,
                    'message' => 'Jumlah pembayaran harus lebih dari 0',
                    'data' => null,
                ];
            }

            // Update status cicilan jika telat (auto-detect)
            $cicilan->updateStatus();
            $cicilan->refresh();

            // Hitung denda (jika enabled)
            $dendaEnabled = Config::get('cicilan.denda_enabled', true);
            $denda = $dendaEnabled ? $cicilan->hitungDenda() : 0;

            // Update denda di cicilan
            $cicilan->update(['denda' => $denda]);

            // Get sisa yang belum dibayar
            $totalDibayarSebelumnya = $cicilan->getTotalDibayar();
            $sisaDenda = max(0, $denda - ($cicilan->jumlah_dibayar_denda ?? 0));
            $sisaBunga = max(0, $cicilan->bunga - ($cicilan->jumlah_dibayar_bunga ?? 0));
            $sisaPokok = max(0, $cicilan->pokok - ($cicilan->jumlah_dibayar_pokok ?? 0));

            // Allocation strategy: Denda → Bunga → Pokok
            $sisa = $jumlahBayar;
            $alokasiDenda = 0;
            $alokasiBunga = 0;
            $alokasiPokok = 0;

            // 1. Bayar denda dulu
            if ($sisa > 0 && $sisaDenda > 0) {
                $alokasiDenda = min($sisa, $sisaDenda);
                $sisa -= $alokasiDenda;
            }

            // 2. Bayar bunga
            if ($sisa > 0 && $sisaBunga > 0) {
                $alokasiBunga = min($sisa, $sisaBunga);
                $sisa -= $alokasiBunga;
            }

            // 3. Bayar pokok
            if ($sisa > 0 && $sisaPokok > 0) {
                $alokasiPokok = min($sisa, $sisaPokok);
                $sisa -= $alokasiPokok;
            }

            // Hitung sisa setelah pembayaran ini
            $sisaDendaAfter = max(0, $sisaDenda - $alokasiDenda);
            $sisaBungaAfter = max(0, $sisaBunga - $alokasiBunga);
            $sisaPokokAfter = max(0, $sisaPokok - $alokasiPokok);

            // Create payment record
            $pembayaran = PembayaranCicilan::create([
                'jadwal_cicilan_id' => $jadwalCicilanId,
                'pinjaman_id' => $cicilan->pinjaman_id,
                'jumlah_bayar' => $jumlahBayar,
                'tanggal_bayar' => Carbon::today(),
                'alokasi_denda' => $alokasiDenda,
                'alokasi_bunga' => $alokasiBunga,
                'alokasi_pokok' => $alokasiPokok,
                'sisa_denda' => $sisaDendaAfter,
                'sisa_bunga' => $sisaBungaAfter,
                'sisa_pokok' => $sisaPokokAfter,
                'metode_bayar' => $metadata['metode_bayar'] ?? 'tunai',
                'nomor_referensi' => $metadata['nomor_referensi'] ?? null,
                'keterangan' => $metadata['keterangan'] ?? null,
                'dibayar_oleh' => $userId,
            ]);

            // Update cicilan (cumulative)
            $totalDibayar = $totalDibayarSebelumnya + $jumlahBayar;
            $isLunas = ($sisaDendaAfter <= 0 && $sisaBungaAfter <= 0 && $sisaPokokAfter <= 0);

            $cicilan->update([
                'jumlah_dibayar' => $totalDibayar,
                'jumlah_dibayar_denda' => ($cicilan->jumlah_dibayar_denda ?? 0) + $alokasiDenda,
                'jumlah_dibayar_bunga' => ($cicilan->jumlah_dibayar_bunga ?? 0) + $alokasiBunga,
                'jumlah_dibayar_pokok' => ($cicilan->jumlah_dibayar_pokok ?? 0) + $alokasiPokok,
                'tanggal_bayar' => $isLunas ? Carbon::today() : $cicilan->tanggal_bayar,
                'status' => $isLunas ? 'sudah_bayar' : $cicilan->status,
                'dibayar_oleh' => $userId,
            ]);

            // Update saldo pinjaman
            $cicilan->pinjaman->update([
                'sisa_pokok' => max(0, $cicilan->pinjaman->sisa_pokok - $alokasiPokok),
                'sisa_bunga' => max(0, $cicilan->pinjaman->sisa_bunga - $alokasiBunga),
            ]);

            // Check apakah pinjaman sudah lunas
            if ($cicilan->pinjaman->isLunas()) {
                $cicilan->pinjaman->update(['status' => 'lunas']);
            }

            DB::commit();

            $message = $isLunas
                ? 'Pembayaran berhasil. Cicilan sudah lunas.'
                : 'Pembayaran sebagian berhasil. Sisa: Rp ' . number_format($sisaDendaAfter + $sisaBungaAfter + $sisaPokokAfter, 0, ',', '.');

            return [
                'success' => true,
                'message' => $message,
                'data' => $pembayaran->load(['jadwalCicilan', 'dibayarOleh']),
                'is_lunas' => $isLunas,
                'sisa' => [
                    'denda' => $sisaDendaAfter,
                    'bunga' => $sisaBungaAfter,
                    'pokok' => $sisaPokokAfter,
                    'total' => $sisaDendaAfter + $sisaBungaAfter + $sisaPokokAfter,
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Gagal memproses pembayaran: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Get ringkasan pembayaran cicilan (untuk preview sebelum bayar)
     *
     * @param int $jadwalCicilanId
     * @param float $jumlahBayar
     * @return array
     */
    public function previewPembayaran(int $jadwalCicilanId, float $jumlahBayar): array
    {
        $cicilan = JadwalCicilan::findOrFail($jadwalCicilanId);

        // Hitung denda
        $dendaEnabled = Config::get('cicilan.denda_enabled', true);
        $denda = $dendaEnabled ? $cicilan->hitungDenda() : 0;

        // Get sisa
        $totalDibayar = $cicilan->getTotalDibayar();
        $sisaDenda = max(0, $denda - ($cicilan->jumlah_dibayar_denda ?? 0));
        $sisaBunga = max(0, $cicilan->bunga - ($cicilan->jumlah_dibayar_bunga ?? 0));
        $sisaPokok = max(0, $cicilan->pokok - ($cicilan->jumlah_dibayar_pokok ?? 0));
        $totalSisa = $sisaDenda + $sisaBunga + $sisaPokok;

        // Simulate allocation
        $sisa = $jumlahBayar;
        $alokasiDenda = min($sisa, $sisaDenda);
        $sisa -= $alokasiDenda;

        $alokasiBunga = min($sisa, $sisaBunga);
        $sisa -= $alokasiBunga;

        $alokasiPokok = min($sisa, $sisaPokok);
        $sisa -= $alokasiPokok;

        $sisaSetelahBayar = max(0, $totalSisa - $jumlahBayar);
        $isLunas = ($sisaSetelahBayar <= 0);

        return [
            'cicilan_ke' => $cicilan->cicilan_ke,
            'total_cicilan' => $cicilan->jumlah_cicilan,
            'sudah_dibayar' => $totalDibayar,
            'sisa_sebelum' => [
                'denda' => $sisaDenda,
                'bunga' => $sisaBunga,
                'pokok' => $sisaPokok,
                'total' => $totalSisa,
            ],
            'jumlah_bayar' => $jumlahBayar,
            'alokasi' => [
                'denda' => $alokasiDenda,
                'bunga' => $alokasiBunga,
                'pokok' => $alokasiPokok,
            ],
            'sisa_setelah' => [
                'denda' => max(0, $sisaDenda - $alokasiDenda),
                'bunga' => max(0, $sisaBunga - $alokasiBunga),
                'pokok' => max(0, $sisaPokok - $alokasiPokok),
                'total' => $sisaSetelahBayar,
            ],
            'is_lunas' => $isLunas,
            'keterangan' => $isLunas
                ? 'Pembayaran akan melunasi cicilan ini'
                : 'Pembayaran sebagian, masih ada sisa Rp ' . number_format($sisaSetelahBayar, 0, ',', '.'),
        ];
    }

    /**
     * Get history pembayaran per pinjaman
     *
     * @param int $pinjamanId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHistoryPembayaran(int $pinjamanId)
    {
        return PembayaranCicilan::with(['jadwalCicilan', 'dibayarOleh'])
            ->where('pinjaman_id', $pinjamanId)
            ->orderBy('tanggal_bayar', 'desc')
            ->get();
    }

    /**
     * Get statistik cicilan per pinjaman
     *
     * @param int $pinjamanId
     * @return array
     */
    public function getStatistikCicilan(int $pinjamanId): array
    {
        $pinjaman = Pinjaman::with('jadwalCicilan')->findOrFail($pinjamanId);

        $totalCicilan = $pinjaman->jadwalCicilan->count();
        $sudahBayar = $pinjaman->jadwalCicilan->where('status', 'sudah_bayar')->count();
        $belumBayar = $pinjaman->jadwalCicilan->where('status', 'belum_bayar')->count();
        $telat = $pinjaman->jadwalCicilan->where('status', 'telat')->count();

        $totalDibayar = PembayaranCicilan::where('pinjaman_id', $pinjamanId)->sum('jumlah_bayar');
        $totalDenda = PembayaranCicilan::where('pinjaman_id', $pinjamanId)->sum('alokasi_denda');

        return [
            'total_cicilan' => $totalCicilan,
            'sudah_bayar' => $sudahBayar,
            'belum_bayar' => $belumBayar,
            'telat' => $telat,
            'persentase_lunas' => $totalCicilan > 0 ? round(($sudahBayar / $totalCicilan) * 100, 2) : 0,
            'total_sudah_dibayar' => $totalDibayar,
            'total_denda' => $totalDenda,
            'sisa_pokok' => $pinjaman->sisa_pokok,
            'sisa_bunga' => $pinjaman->sisa_bunga,
            'total_sisa' => $pinjaman->sisa_pokok + $pinjaman->sisa_bunga,
        ];
    }
}
