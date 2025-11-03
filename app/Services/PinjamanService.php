<?php

namespace App\Services;

use App\Models\Pinjaman;
use App\Models\JadwalCicilan;
use App\Models\Simpanan;
use App\Models\User;
use App\Models\Koperasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PinjamanService
{
    /**
     * Hitung cicilan dengan bunga flat (rounded up)
     *
     * @param float $jumlahPinjaman
     * @param float $bungaPersen (misal: 1.5 untuk 1.5%)
     * @param int $tenorBulan
     * @return array
     */
    public function hitungCicilan(float $jumlahPinjaman, float $bungaPersen, int $tenorBulan): array
    {
        // Bunga total = jumlah × (persen / 100) × tenor
        $totalBunga = $jumlahPinjaman * ($bungaPersen / 100) * $tenorBulan;

        // Total yang harus dibayar
        $totalBayar = $jumlahPinjaman + $totalBunga;

        // Cicilan per bulan (ROUNDED UP)
        $cicilanPerBulan = ceil($totalBayar / $tenorBulan);

        // Pokok dan bunga per bulan
        $pokokPerBulan = $jumlahPinjaman / $tenorBulan;
        $bungaPerBulan = $totalBunga / $tenorBulan;

        // Adjustment untuk cicilan terakhir (karena pembulatan)
        $totalCicilanBulat = $cicilanPerBulan * $tenorBulan;
        $selisih = $totalCicilanBulat - $totalBayar;
        $cicilanTerakhir = $cicilanPerBulan - $selisih;

        return [
            'jumlah_pinjaman' => $jumlahPinjaman,
            'bunga_persen' => $bungaPersen,
            'tenor_bulan' => $tenorBulan,
            'total_bunga' => round($totalBunga, 2),
            'total_bayar' => round($totalBayar, 2),
            'cicilan_perbulan' => $cicilanPerBulan,
            'pokok_perbulan' => round($pokokPerBulan, 2),
            'bunga_perbulan' => round($bungaPerBulan, 2),
            'cicilan_terakhir' => $cicilanTerakhir,
        ];
    }

    /**
     * Simulasi cicilan (preview tanpa save DB)
     *
     * @param float $jumlahPinjaman
     * @param float $bungaPersen
     * @param int $tenorBulan
     * @param string|null $tanggalMulai (format: Y-m-d)
     * @return array
     */
    public function simulasiCicilan(
        float $jumlahPinjaman,
        float $bungaPersen,
        int $tenorBulan,
        ?string $tanggalMulai = null
    ): array {
        $perhitungan = $this->hitungCicilan($jumlahPinjaman, $bungaPersen, $tenorBulan);

        // Default tanggal mulai = bulan depan tanggal 15
        $startDate = $tanggalMulai
            ? Carbon::parse($tanggalMulai)
            : Carbon::today()->addMonth()->day(15);

        $jadwal = [];
        for ($i = 1; $i <= $tenorBulan; $i++) {
            $tanggalJatuhTempo = $startDate->copy()->addMonths($i - 1);

            // Cicilan terakhir pakai amount yang udah disesuaikan
            $jumlahCicilan = ($i === $tenorBulan)
                ? $perhitungan['cicilan_terakhir']
                : $perhitungan['cicilan_perbulan'];

            $jadwal[] = [
                'cicilan_ke' => $i,
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo->format('Y-m-d'),
                'jumlah_cicilan' => $jumlahCicilan,
                'pokok' => $perhitungan['pokok_perbulan'],
                'bunga' => $perhitungan['bunga_perbulan'],
            ];
        }

        return [
            'perhitungan' => $perhitungan,
            'jadwal_cicilan' => $jadwal,
        ];
    }

    /**
     * Validasi pengajuan pinjaman
     *
     * @param int $userId
     * @param float $jumlahPinjaman
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validasiPengajuan(int $userId, float $jumlahPinjaman): array
    {
        $user = User::findOrFail($userId);

        // Rule 1: Cek total simpanan
        $totalSimpanan = Simpanan::where('user_id', $userId)->sum('jumlah');
        $koperasi = $user->koperasi;
        $maxPinjaman = $totalSimpanan * $koperasi->max_pinjaman_multiplier;

        if ($jumlahPinjaman > $maxPinjaman) {
            return [
                'valid' => false,
                'message' => "Jumlah pinjaman melebihi batas maksimal. Total simpanan Anda: Rp " . number_format($totalSimpanan, 0, ',', '.') . ", maksimal pinjaman: Rp " . number_format($maxPinjaman, 0, ',', '.'),
                'data' => [
                    'total_simpanan' => $totalSimpanan,
                    'max_pinjaman' => $maxPinjaman,
                ]
            ];
        }

        // Rule 2: Cek tunggakan
        if (Pinjaman::hasTunggakan($userId)) {
            return [
                'valid' => false,
                'message' => 'Anda memiliki tunggakan cicilan. Harap selesaikan cicilan yang telat terlebih dahulu.',
            ];
        }

        // Rule 3: Cek jumlah pinjaman aktif
        $jumlahPinjamanAktif = Pinjaman::countPinjamanAktif($userId);
        if ($jumlahPinjamanAktif >= 2) {
            return [
                'valid' => false,
                'message' => 'Anda sudah memiliki 2 pinjaman aktif. Maksimal 2 pinjaman aktif per anggota.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Validasi berhasil',
        ];
    }

    /**
     * Ajukan pinjaman baru
     *
     * @param array $data
     * @return Pinjaman
     * @throws \Exception
     */
    public function ajukanPinjaman(array $data): Pinjaman
    {
        DB::beginTransaction();

        try {
            // Validasi
            $validasi = $this->validasiPengajuan($data['user_id'], $data['jumlah_pinjaman']);
            if (!$validasi['valid']) {
                throw new \Exception($validasi['message']);
            }

            // Get bunga dari koperasi (atau gunakan custom)
            $user = User::findOrFail($data['user_id']);
            $bungaPersen = $data['bunga_persen'] ?? $user->koperasi->bunga_default;

            // Hitung cicilan
            $perhitungan = $this->hitungCicilan(
                $data['jumlah_pinjaman'],
                $bungaPersen,
                $data['tenor_bulan']
            );

            // Generate no_pinjaman
            $noPinjaman = Pinjaman::generateNoPinjaman($user->koperasi_id);

            // Create pinjaman
            $pinjaman = Pinjaman::create([
                'koperasi_id' => $user->koperasi_id,
                'user_id' => $data['user_id'],
                'no_pinjaman' => $noPinjaman,
                'jumlah_pinjaman' => $data['jumlah_pinjaman'],
                'bunga_persen' => $bungaPersen,
                'tenor_bulan' => $data['tenor_bulan'],
                'tujuan_pinjaman' => $data['tujuan_pinjaman'] ?? null,
                'total_bunga' => $perhitungan['total_bunga'],
                'total_bayar' => $perhitungan['total_bayar'],
                'cicilan_perbulan' => $perhitungan['cicilan_perbulan'],
                'sisa_pokok' => $data['jumlah_pinjaman'], // Initial = full amount
                'sisa_bunga' => $perhitungan['total_bunga'],
                'status' => 'pending',
                'created_by' => $data['created_by'] ?? $data['user_id'],
            ]);

            DB::commit();
            return $pinjaman;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve pinjaman (generate jadwal cicilan)
     *
     * @param int $pinjamanId
     * @param int $approvedBy (user_id manajer)
     * @param string|null $catatanApproval
     * @param string|null $tanggalMulaiCicilan (format: Y-m-d)
     * @return Pinjaman
     * @throws \Exception
     */
    public function approvePinjaman(
        int $pinjamanId,
        int $approvedBy,
        ?string $catatanApproval = null,
        ?string $tanggalMulaiCicilan = null
    ): Pinjaman {
        DB::beginTransaction();

        try {
            $pinjaman = Pinjaman::findOrFail($pinjamanId);

            // Validasi status
            if ($pinjaman->status !== 'pending') {
                throw new \Exception('Pinjaman ini sudah di-approve/reject sebelumnya.');
            }

            // Update status pinjaman
            $pinjaman->update([
                'status' => 'approved',
                'tanggal_approval' => now(),
                'approved_by' => $approvedBy,
                'catatan_approval' => $catatanApproval,
            ]);

            // Generate jadwal cicilan
            $this->generateJadwalCicilan($pinjaman, $tanggalMulaiCicilan);

            DB::commit();

            // TODO: Dispatch email notification job (commented untuk sekarang)
            // dispatch(new SendPinjamanApprovedEmail($pinjaman));

            return $pinjaman->fresh(['jadwalCicilan']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject pinjaman
     *
     * @param int $pinjamanId
     * @param int $rejectedBy
     * @param string $catatanPenolakan
     * @return Pinjaman
     * @throws \Exception
     */
    public function rejectPinjaman(int $pinjamanId, int $rejectedBy, string $catatanPenolakan): Pinjaman
    {
        DB::beginTransaction();

        try {
            $pinjaman = Pinjaman::findOrFail($pinjamanId);

            // Validasi status
            if ($pinjaman->status !== 'pending') {
                throw new \Exception('Pinjaman ini sudah di-approve/reject sebelumnya.');
            }

            $pinjaman->update([
                'status' => 'rejected',
                'tanggal_approval' => now(),
                'approved_by' => $rejectedBy,
                'catatan_penolakan' => $catatanPenolakan,
            ]);

            DB::commit();

            // TODO: Dispatch email notification job
            // dispatch(new SendPinjamanRejectedEmail($pinjaman));

            return $pinjaman;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cairkan pinjaman (ubah status approved â†' active)
     *
     * @param int $pinjamanId
     * @param int $cairkanBy (user_id kasir)
     * @return Pinjaman
     * @throws \Exception
     */
    public function cairkanPinjaman(int $pinjamanId, int $cairkanBy): Pinjaman
    {
        DB::beginTransaction();

        try {
            $pinjaman = Pinjaman::findOrFail($pinjamanId);

            // Validasi status
            if ($pinjaman->status !== 'approved') {
                throw new \Exception('Pinjaman harus di-approve terlebih dahulu sebelum dicairkan.');
            }

            $pinjaman->update([
                'status' => 'active',
                'tanggal_pencairan' => now(),
            ]);

            DB::commit();
            return $pinjaman;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate jadwal cicilan (dipanggil saat approve)
     *
     * @param Pinjaman $pinjaman
     * @param string|null $tanggalMulai (format: Y-m-d)
     * @return void
     */
    protected function generateJadwalCicilan(Pinjaman $pinjaman, ?string $tanggalMulai = null): void
    {
        // Default: bulan depan tanggal 15
        $startDate = $tanggalMulai
            ? Carbon::parse($tanggalMulai)
            : Carbon::today()->addMonth()->day(15);

        $perhitungan = $this->hitungCicilan(
            $pinjaman->jumlah_pinjaman,
            $pinjaman->bunga_persen,
            $pinjaman->tenor_bulan
        );

        for ($i = 1; $i <= $pinjaman->tenor_bulan; $i++) {
            $tanggalJatuhTempo = $startDate->copy()->addMonths($i - 1);

            // Cicilan terakhir pakai amount yang udah disesuaikan
            $jumlahCicilan = ($i === $pinjaman->tenor_bulan)
                ? $perhitungan['cicilan_terakhir']
                : $perhitungan['cicilan_perbulan'];

            JadwalCicilan::create([
                'pinjaman_id' => $pinjaman->id,
                'cicilan_ke' => $i,
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                'jumlah_cicilan' => $jumlahCicilan,
                'pokok' => $perhitungan['pokok_perbulan'],
                'bunga' => $perhitungan['bunga_perbulan'],
                'status' => 'belum_bayar',
            ]);
        }
    }
}
