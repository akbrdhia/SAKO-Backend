<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'koperasi_id',
        'no_anggota',
        'nik',
        'nama',
        'alamat',
        'no_hp',
        'email',
        'password',
        'role',
        'status',
        'registered_by',
        'foto_profile',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // ← TAMBAH INI (Laravel 11 feature)
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

    // Kasir yang mendaftarkan user
    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    // Daftar anggota yang didaftarkan kasir ini
    public function registeredMembers()
    {
        return $this->hasMany(User::class, 'registered_by');
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

    public function isAnggota()
    {
        return $this->role === 'anggota';
    }

    public function isKasir()
    {
        return $this->role === 'kasir';
    }

    public function isManajer()
    {
        return $this->role === 'manajer';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeAnggota($query)
    {
        return $query->where('role', 'anggota');
    }

    public function scopeKasir($query)
    {
        return $query->where('role', 'kasir');
    }

    public function scopeManajer($query)
    {
        return $query->where('role', 'manajer');
    }

    public function scopeAdmin($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeByKoperasi($query, $koperasiId)
    {
        return $query->where('koperasi_id', $koperasiId);
    }
}
