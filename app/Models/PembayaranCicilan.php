<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranCicilan extends Model
{
    use HasFactory;

    protected $table = 'pembayaran_cicilans';

    protected $fillable = [
        'jadwal_cicilan_id',
        'pinjaman_id',
        'jumlah_bayar',
        'tanggal_bayar',
        'alokasi_denda',
        'alokasi_bunga',
        'alokasi_pokok',
        'sisa_denda',
        'sisa_bunga',
        'sisa_pokok',
        'metode_bayar',
        'nomor_referensi',
        'keterangan',
        'dibayar_oleh',
    ];

    protected $casts = [
        'jumlah_bayar' => 'decimal:2',
        'tanggal_bayar' => 'date',
        'alokasi_denda' => 'decimal:2',
        'alokasi_bunga' => 'decimal:2',
        'alokasi_pokok' => 'decimal:2',
        'sisa_denda' => 'decimal:2',
        'sisa_bunga' => 'decimal:2',
        'sisa_pokok' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function jadwalCicilan(): BelongsTo
    {
        return $this->belongsTo(JadwalCicilan::class);
    }

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

    public function scopeByJadwalCicilan($query, $jadwalCicilanId)
    {
        return $query->where('jadwal_cicilan_id', $jadwalCicilanId);
    }

    public function scopeByTanggal($query, $tanggalDari, $tanggalSampai)
    {
        return $query->whereBetween('tanggal_bayar', [$tanggalDari, $tanggalSampai]);
    }

    /**
     * Helper Methods
     */

    /**
     * Check apakah pembayaran ini melunasi cicilan
     */
    public function isLunasi(): bool
    {
        return $this->sisa_denda <= 0
            && $this->sisa_bunga <= 0
            && $this->sisa_pokok <= 0;
    }

    /**
     * Get total alokasi
     */
    public function getTotalAlokasi(): float
    {
        return $this->alokasi_denda + $this->alokasi_bunga + $this->alokasi_pokok;
    }

    /**
     * Get breakdown allocation percentage
     */
    public function getBreakdownPersentase(): array
    {
        $total = $this->getTotalAlokasi();

        if ($total <= 0) {
            return [
                'denda' => 0,
                'bunga' => 0,
                'pokok' => 0,
            ];
        }

        return [
            'denda' => round(($this->alokasi_denda / $total) * 100, 2),
            'bunga' => round(($this->alokasi_bunga / $total) * 100, 2),
            'pokok' => round(($this->alokasi_pokok / $total) * 100, 2),
        ];
    }
}
