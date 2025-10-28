<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Koperasi extends Model
{
  use HasFactory;

    protected $table = 'koperasi';

    protected $fillable = [
        'kode_koperasi',
        'nama',
        'alamat',
        'kelurahan',
        'kecamatan',
        'kota',
        'provinsi',
        'kode_pos',
        'no_telp',
        'email',
        'bunga_default',
        'max_pinjaman_multiplier',
        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | 🔗 RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // satu koperasi punya banyak user
    public function users()
    {
        return $this->hasMany(User::class, 'koperasi_id');
    }

    /*
    |--------------------------------------------------------------------------
    | 🧠 SCOPES & HELPERS
    |--------------------------------------------------------------------------
    */

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
