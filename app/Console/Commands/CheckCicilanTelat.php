<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalCicilan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckCicilanTelat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cicilan:check-telat
                            {--time=00:00 : Waktu eksekusi (format: HH:MM)}
                            {--force : Force run tanpa cek waktu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check dan update status cicilan yang sudah jatuh tempo (belum bayar → telat)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $configuredTime = $this->option('time');
        $force = $this->option('force');

        // Cek apakah waktu eksekusi sesuai (kalau tidak force)
        if (!$force && Carbon::now()->format('H:i') !== $configuredTime) {
            $this->info("Skipped. Command dijadwalkan untuk jam {$configuredTime}");
            return 0;
        }

        $this->info('===========================================');
        $this->info('Cek Cicilan Telat - ' . Carbon::now()->format('Y-m-d H:i:s'));
        $this->info('===========================================');

        try {
            // Query cicilan yang belum bayar dan sudah jatuh tempo
            $cicilanTelat = JadwalCicilan::with('pinjaman.user')
                ->where('status', 'belum_bayar')
                ->where('tanggal_jatuh_tempo', '<', Carbon::today())
                ->get();

            if ($cicilanTelat->isEmpty()) {
                $this->info('✓ Tidak ada cicilan telat.');
                return 0;
            }

            $this->info("Ditemukan {$cicilanTelat->count()} cicilan telat:");
            $this->newLine();

            $updated = 0;
            foreach ($cicilanTelat as $cicilan) {
                // Update status dan hitung denda
                $cicilan->updateStatus();

                $anggota = $cicilan->pinjaman->user->nama ?? 'Unknown';
                $noPinjaman = $cicilan->pinjaman->no_pinjaman ?? 'Unknown';
                $hariTelat = $cicilan->getHariTelat();
                $denda = $cicilan->hitungDenda();

                $this->line(sprintf(
                    "  • Cicilan ke-%d | Pinjaman: %s | Anggota: %s | Telat: %d hari | Denda: Rp %s",
                    $cicilan->cicilan_ke,
                    $noPinjaman,
                    $anggota,
                    $hariTelat,
                    number_format($denda, 0, ',', '.')
                ));

                $updated++;

                // TODO: Kirim notifikasi ke anggota (email/SMS)
                // dispatch(new SendCicilanTelatNotification($cicilan));
            }

            $this->newLine();
            $this->info("✓ Berhasil update {$updated} cicilan menjadi status 'telat'");

            // Log to file
            Log::channel('daily')->info("Cicilan telat check: {$updated} cicilan di-update", [
                'timestamp' => Carbon::now(),
                'count' => $updated,
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            Log::error('CheckCicilanTelat failed: ' . $e->getMessage());
            return 1;
        }
    }
}
