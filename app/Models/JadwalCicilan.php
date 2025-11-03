<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class JadwalCicilan extends Model
{
    use HasFactory;

    protected $table = 'jadwal_cicilans';

    protected $fillable = [
        'pinjaman_id',
        'cicilan_ke',
        'tanggal_jatuh_tempo',
        'jumlah_cicilan',
        'pokok',
        'bunga',
        'jumlah_dibayar',
        'tanggal_bayar',
        'denda',
        'hari_telat',
        'status',
        'dibayar_oleh',
        'keterangan',
    ];

    protected $casts = [
        'cicilan_ke' => 'integer',
        'tanggal_jatuh_tempo' => 'date',
        'jumlah_cicilan' => 'decimal:2',
        'pokok' => 'decimal:2',
        'bunga' => 'decimal:2',
        'jumlah_dibayar' => 'decimal:2',
        'tanggal_bayar' => 'date',
        'denda' => 'decimal:2',
        'hari_telat' => 'integer',
    ];

    /**
     * Relationships
     */
    public function pinjaman(): BelongsTo
    {
        return $this->belongsTo(Pinjaman::class);
    }

    public function dibayarOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibayar_oleh')->withDefault([
            'nama' => 'System',
        ]);
    }

    /**
     * Query Scopes
     */
    public function scopeByPinjaman($query, $pinjamanId)
    {
        return $query->where('pinjaman_id', $pinjamanId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBelumBayar($query)
    {
        return $query->where('status', 'belum_bayar');
    }

    public function scopeSudahBayar($query)
    {
        return $query->where('status', 'sudah_bayar');
    }

    public function scopeTelat($query)
    {
        return $query->where('status', 'telat');
    }

    public function scopeJatuhTempo($query, $date = null)
    {
        $date = $date ?? Carbon::today();
        return $query->where('tanggal_jatuh_tempo', '<=', $date);
    }

    /**
     * Helper Methods
     */

    /**
     * Cek apakah cicilan sudah jatuh tempo
     */
    public function isJatuhTempo(): bool
    {
        return Carbon::parse($this->tanggal_jatuh_tempo)->isPast();
    }

    /**
     * Hitung hari keterlambatan
     */
    public function getHariTelat(): int
    {
        if (!$this->isJatuhTempo() || $this->status === 'sudah_bayar') {
            return 0;
        }

        return Carbon::parse($this->tanggal_jatuh_tempo)->diffInDays(Carbon::today());
    }

    /**
     * Hitung denda keterlambatan
     * Formula: jumlah_cicilan × 0.1% × hari_telat (future feature)
     */
    public function hitungDenda(): float
    {
        $hariTelat = $this->getHariTelat();

        if ($hariTelat <= 0) {
            return 0;
        }

        // 0.1% per hari
        $dendaPersen = 0.001;
        $denda = $this->jumlah_cicilan * $dendaPersen * $hariTelat;

        return round($denda, 2);
    }

    /**
     * Update status cicilan (cek telat otomatis)
     */
    public function updateStatus(): void
    {
        if ($this->status === 'sudah_bayar') {
            return; // Skip kalau sudah bayar
        }

        if ($this->isJatuhTempo() && $this->status !== 'telat') {
            $this->update([
                'status' => 'telat',
                'hari_telat' => $this->getHariTelat(),
                'denda' => $this->hitungDenda(),
            ]);
        }
    }

    /**
     * Proses pembayaran cicilan
     */
    public function prosesBayar(float $jumlahBayar, int $userId, ?string $keterangan = null): bool
    {
        // Hitung denda kalau ada
        $denda = $this->hitungDenda();
        $totalYangHarusDibayar = $this->jumlah_cicilan + $denda;

        // Validasi jumlah bayar
        if ($jumlahBayar < $totalYangHarusDibayar) {
            return false; // Pembayaran kurang
        }

        // Update cicilan
        $this->update([
            'jumlah_dibayar' => $jumlahBayar,
            'tanggal_bayar' => Carbon::today(),
            'denda' => $denda,
            'hari_telat' => $this->getHariTelat(),
            'status' => 'sudah_bayar',
            'dibayar_oleh' => $userId,
            'keterangan' => $keterangan,
        ]);

        // Update saldo pinjaman
        $this->pinjaman->update([
            'sisa_pokok' => max(0, $this->pinjaman->sisa_pokok - $this->pokok),
            'sisa_bunga' => max(0, $this->pinjaman->sisa_bunga - $this->bunga),
        ]);

        // Cek lunas
        if ($this->pinjaman->isLunas()) {
            $this->pinjaman->update(['status' => 'lunas']);
        }

        return true;
    }

    /**
     * Static method: Cek dan update cicilan telat (untuk cron job)
     */
    public static function cekCicilanTelat(): int
    {
        $cicilan = static::belumBayar()
            ->jatuhTempo()
            ->get();

        $updated = 0;
        foreach ($cicilan as $item) {
            $item->updateStatus();
            $updated++;
        }

        return $updated;
    }
}
