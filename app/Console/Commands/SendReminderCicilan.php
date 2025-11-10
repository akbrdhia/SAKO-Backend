<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalCicilan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendReminderCicilan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cicilan:send-reminder
                            {--days=3 : Berapa hari sebelum jatuh tempo}
                            {--time=08:00 : Waktu eksekusi (format: HH:MM)}
                            {--force : Force run tanpa cek waktu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim reminder cicilan yang akan jatuh tempo (default: H-3)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $configuredTime = $this->option('time');
        $force = $this->option('force');

        // Cek apakah waktu eksekusi sesuai (kalau tidak force)
        if (!$force && Carbon::now()->format('H:i') !== $configuredTime) {
            $this->info("Skipped. Command dijadwalkan untuk jam {$configuredTime}");
            return 0;
        }

        $this->info('===========================================');
        $this->info("Kirim Reminder Cicilan (H-{$days}) - " . Carbon::now()->format('Y-m-d H:i:s'));
        $this->info('===========================================');

        try {
            // Tanggal target: hari ini + X hari
            $targetDate = Carbon::today()->addDays($days);

            // Query cicilan yang belum bayar dan jatuh tempo di tanggal target
            $cicilanReminder = JadwalCicilan::with('pinjaman.user')
                ->where('status', 'belum_bayar')
                ->whereDate('tanggal_jatuh_tempo', $targetDate)
                ->get();

            if ($cicilanReminder->isEmpty()) {
                $this->info("✓ Tidak ada cicilan yang jatuh tempo pada {$targetDate->format('Y-m-d')}");
                return 0;
            }

            $this->info("Ditemukan {$cicilanReminder->count()} cicilan yang akan jatuh tempo:");
            $this->newLine();

            $sent = 0;
            foreach ($cicilanReminder as $cicilan) {
                $anggota = $cicilan->pinjaman->user;
                $noPinjaman = $cicilan->pinjaman->no_pinjaman ?? 'Unknown';

                if (!$anggota || !$anggota->email) {
                    $this->warn("  ✗ Cicilan ke-{$cicilan->cicilan_ke} | {$noPinjaman} | Anggota tidak punya email");
                    continue;
                }

                $this->line(sprintf(
                    "  • Cicilan ke-%d | Pinjaman: %s | Anggota: %s | Email: %s | Jatuh Tempo: %s | Jumlah: Rp %s",
                    $cicilan->cicilan_ke,
                    $noPinjaman,
                    $anggota->nama,
                    $anggota->email,
                    $cicilan->tanggal_jatuh_tempo->format('d-m-Y'),
                    number_format($cicilan->jumlah_cicilan, 0, ',', '.')
                ));

                // TODO: Dispatch email job (commented untuk sekarang)
                // dispatch(new SendReminderCicilanEmail($cicilan));

                $sent++;
            }

            $this->newLine();
            $this->info("✓ Reminder akan dikirim ke {$sent} anggota");
            $this->comment("  (Email notification masih di-comment, uncomment di production)");

            // Log to file
            Log::channel('daily')->info("Reminder cicilan sent: {$sent} emails", [
                'timestamp' => Carbon::now(),
                'target_date' => $targetDate->format('Y-m-d'),
                'count' => $sent,
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            Log::error('SendReminderCicilan failed: ' . $e->getMessage());
            return 1;
        }
    }
}
