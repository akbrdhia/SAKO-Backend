<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Simpanan extends Model
{
    use HasFactory;

    protected $table = 'simpanans';

    protected $fillable = [
        'koperasi_id',
        'user_id',
        'jenis',
        'jumlah',
        'tanggal',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tanggal' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | 🔗 RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Koperasi yang memiliki simpanan ini
     */
    public function koperasi()
    {
        return $this->belongsTo(Koperasi::class, 'koperasi_id');
    }

    /**
     * Anggota yang menyimpan
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Kasir yang input simpanan
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | 🧠 SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Filter by koperasi
     */
    public function scopeByKoperasi($query, $koperasiId)
    {
        return $query->where('koperasi_id', $koperasiId);
    }

    /**
     * Scope: Filter by user/anggota
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by jenis simpanan
     */
    public function scopeByJenis($query, $jenis)
    {
        return $query->where('jenis', $jenis);
    }

    /**
     * Scope: Filter by tanggal range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    /**
     * Scope: Order by tanggal descending
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('tanggal', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | 📊 STATIC METHODS (Aggregations)
    |--------------------------------------------------------------------------
    */

    /**
     * Get total simpanan by user
     */
    public static function getTotalByUser($userId, $jenis = null)
    {
        $query = self::where('user_id', $userId);

        if ($jenis) {
            $query->where('jenis', $jenis);
        }

        return $query->sum('jumlah');
    }

    /**
     * Get total simpanan by koperasi
     */
    public static function getTotalByKoperasi($koperasiId, $jenis = null)
    {
        $query = self::where('koperasi_id', $koperasiId);

        if ($jenis) {
            $query->where('jenis', $jenis);
        }

        return $query->sum('jumlah');
    }

    /**
     * Get simpanan summary by user
     */
    public static function getSummaryByUser($userId)
    {
        return [
            'total' => self::getTotalByUser($userId),
            'pokok' => self::getTotalByUser($userId, 'pokok'),
            'wajib' => self::getTotalByUser($userId, 'wajib'),
            'sukarela' => self::getTotalByUser($userId, 'sukarela'),
        ];
    }
}
