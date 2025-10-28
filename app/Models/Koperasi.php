<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Koperasi extends Model
{
    use HasFactory;

    protected $table = 'koperasis';

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

    // ← TAMBAH CASTS untuk type casting otomatis
    protected $casts = [
        'bunga_default' => 'decimal:2',
        'max_pinjaman_multiplier' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | 🔗 RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // Semua user di koperasi ini
    public function users()
    {
        return $this->hasMany(User::class, 'koperasi_id');
    }

    // Hanya anggota
    public function anggota()
    {
        return $this->hasMany(User::class, 'koperasi_id')->where('role', 'anggota');
    }

    // Hanya kasir
    public function kasir()
    {
        return $this->hasMany(User::class, 'koperasi_id')->where('role', 'kasir');
    }

    // Hanya manajer
    public function manajer()
    {
        return $this->hasMany(User::class, 'koperasi_id')->where('role', 'manajer');
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

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}
