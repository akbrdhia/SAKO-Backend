<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasFactory;

    protected $table = 'email_logs';
    public $timestamps = false;

    protected $fillable = [
        'koperasi_id',
        'user_id',
        'email_to',
        'subject',
        'body',
        'status',
        'error_message',
        'sent_at',
    ];

    public function koperasi()
    {
        return $this->belongsTo(Koperasi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $error)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }
}
