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

    public function kasir()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | 💡 SCOPES & HELPERS
    |--------------------------------------------------------------------------
    */

    public function scopeByJenis($query, $jenis)
    {
        return $query->where('jenis', $jenis);
    }

    public function scopeByAnggota($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getFormattedJumlahAttribute()
    {
        return number_format($this->jumlah, 2, ',', '.');
    }
}
