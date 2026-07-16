<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationAttempt extends Model
{
    /**
     * Only created_at exists on this table (no updated_at).
     * Must stay enabled so Laravel always writes created_at —
     * Hostinger/MariaDB often has no DEFAULT CURRENT_TIMESTAMP,
     * which previously left When as "—" in production.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'device_fingerprint',
        'ip_address',
        'action',
        'user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
