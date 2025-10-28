<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalCicilan extends Model
{
    use HasFactory;

    protected $table = 'jadwal_cicilan';

    protected $fillable = [
        'koperasi_id',
        'pinjaman_id',
        'cicilan_ke',
        'tanggal_jatuh_tempo',
        'jumlah_cicilan',
        'pokok',
        'bunga',
        'status',
        'tanggal_bayar',
    ];

    // Relasi
    public function koperasi()
    {
        return $this->belongsTo(Koperasi::class);
    }

    public function pinjaman()
    {
        return $this->belongsTo(Pinjaman::class);
    }
}
