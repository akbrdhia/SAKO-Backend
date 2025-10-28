<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{

        use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | 🔗 RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // user terdaftar di satu koperasi
    public function koperasi()
    {
        return $this->belongsTo(Koperasi::class, 'koperasi_id');
    }

    // kalau dia anggota, bisa tahu siapa kasir yg daftarin
    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    // kalau dia kasir/manajer, bisa punya banyak anggota yang dia daftarin
    public function anggotaTerdaftar()
    {
        return $this->hasMany(User::class, 'registered_by');
    }

    /*
    |--------------------------------------------------------------------------
    | 🧠 SCOPES & HELPERS
    |--------------------------------------------------------------------------
    */

    // filter by role
    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    // check role
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

    // check status aktif
    public function isActive()
    {
        return $this->status === 'active';
    }
}
