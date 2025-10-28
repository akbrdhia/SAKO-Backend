<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pinjaman extends Model
{
    use HasFactory;

    protected $table = 'pinjaman';

    protected $fillable = [
        'koperasi_id',
        'no_pinjaman',
        'user_id',
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

    /*
    |--------------------------------------------------------------------------
    | 🔗 RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function koperasi()
    {
        return $this->belongsTo(Koperasi::class, 'koperasi_id');
    }

    public function anggota()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | 🧠 SCOPES & HELPERS
    |--------------------------------------------------------------------------
    */

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function isLunas()
    {
        return $this->status === 'lunas';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function getFormattedCicilanAttribute()
    {
        return number_format($this->cicilan_perbulan, 2, ',', '.');
    }

    public function getFormattedJumlahAttribute()
    {
        return number_format($this->jumlah_pinjaman, 2, ',', '.');
    }
}
