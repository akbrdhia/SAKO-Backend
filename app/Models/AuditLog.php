<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';
    public $timestamps = false;

    protected $fillable = [
        'koperasi_id',
        'user_id',
        'action',
        'model',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // 🔗 Relasi
    public function koperasi()
    {
        return $this->belongsTo(Koperasi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🪄 Helper method
    public static function record(string $action, ?Model $model = null, array $changes = [])
    {
        $user = auth()->user();

        self::create([
            'koperasi_id' => $user->koperasi_id ?? null,
            'user_id'     => $user->id ?? null,
            'action'      => $action,
            'model'       => $model ? class_basename($model) : null,
            'model_id'    => $model?->id,
            'old_values'  => $changes['old'] ?? null,
            'new_values'  => $changes['new'] ?? null,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }
}
