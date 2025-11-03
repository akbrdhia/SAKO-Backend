<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pinjaman extends Model
{
    use HasFactory;

    protected $table = 'pinjamans';

    protected $fillable = [
        'koperasi_id',
        'user_id',
        'no_pinjaman',
        'jumlah_pinjaman',
        'bunga_persen',
        'tenor_bulan',
        'tujuan_pinjaman',
        'total_bunga',
        'total_bayar',
        'cicilan_perbulan',
        'sisa_pokok',
        'sisa_bunga',
        'status',
        'tanggal_pengajuan',
        'tanggal_approval',
        'tanggal_pencairan',
        'approved_by',
        'catatan_approval',
        'catatan_penolakan',
        'created_by',
    ];

    protected $casts = [
        'jumlah_pinjaman' => 'decimal:2',
        'bunga_persen' => 'decimal:2',
        'tenor_bulan' => 'integer',
        'total_bunga' => 'decimal:2',
        'total_bayar' => 'decimal:2',
        'cicilan_perbulan' => 'decimal:2',
        'sisa_pokok' => 'decimal:2',
        'sisa_bunga' => 'decimal:2',
        'tanggal_pengajuan' => 'datetime',
        'tanggal_approval' => 'datetime',
        'tanggal_pencairan' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function koperasi(): BelongsTo
    {
        return $this->belongsTo(Koperasi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault([
            'nama' => 'Unknown User',
        ]);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withDefault([
            'nama' => 'System',
        ]);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault([
            'nama' => 'System',
        ]);
    }

    public function jadwalCicilan(): HasMany
    {
        return $this->hasMany(JadwalCicilan::class);
    }

    /**
     * Query Scopes
     */
    public function scopeByKoperasi($query, $koperasiId)
    {
        return $query->where('koperasi_id', $koperasiId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLunas($query)
    {
        return $query->where('status', 'lunas');
    }

    /**
     * Helper Methods
     */

    /**
     * Cek apakah pinjaman sudah lunas
     */
    public function isLunas(): bool
    {
        return $this->sisa_pokok <= 0 && $this->sisa_bunga <= 0;
    }

    /**
     * Cek apakah pinjaman masih bisa dibayar cicilan
     */
    public function isBayarAble(): bool
    {
        return in_array($this->status, ['active']);
    }

    /**
     * Get persentase pembayaran
     */
    public function getPersentaseBayar(): float
    {
        if ($this->total_bayar <= 0) {
            return 0;
        }

        $sudahDibayar = $this->total_bayar - ($this->sisa_pokok + $this->sisa_bunga);
        return round(($sudahDibayar / $this->total_bayar) * 100, 2);
    }

    /**
     * Get sisa cicilan yang belum dibayar
     */
    public function getSisaCicilan(): int
    {
        return $this->jadwalCicilan()
            ->whereIn('status', ['belum_bayar', 'telat'])
            ->count();
    }

    /**
     * Get total cicilan telat
     */
    public function getCicilanTelat(): int
    {
        return $this->jadwalCicilan()
            ->where('status', 'telat')
            ->count();
    }

    /**
     * Static method: Generate no_pinjaman
     * Format: PNJ-{KODE_KOPERASI}-{TAHUN}{BULAN}-{URUTAN}
     * Example: PNJ-JKT001-202501-0001
     */
    public static function generateNoPinjaman($koperasiId): string
    {
        $koperasi = Koperasi::findOrFail($koperasiId);
        $prefix = 'PNJ-' . $koperasi->kode_koperasi . '-' . date('Ym') . '-';

        // Get last pinjaman bulan ini dengan prefix yang sama
        $lastPinjaman = static::where('no_pinjaman', 'like', $prefix . '%')
            ->orderBy('no_pinjaman', 'desc')
            ->first();

        if ($lastPinjaman) {
            // Extract urutan dari no_pinjaman terakhir
            $lastNumber = (int) substr($lastPinjaman->no_pinjaman, -4);
            $newNumber = $lastNumber + 1;
        } else {
            // Bulan ini belum ada pinjaman, mulai dari 1
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Static method: Hitung jumlah pinjaman aktif user
     */
    public static function countPinjamanAktif($userId): int
    {
        return static::where('user_id', $userId)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Static method: Cek apakah user punya tunggakan
     */
    public static function hasTunggakan($userId): bool
    {
        return JadwalCicilan::whereHas('pinjaman', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->where('status', 'active');
        })
        ->where('status', 'telat')
        ->exists();
    }
}
