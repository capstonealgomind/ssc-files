<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoterPresence extends Model
{
    public const DEVICE_MOBILE = 'mobile';

    public const DEVICE_DESKTOP = 'desktop';

    public const ONLINE_WINDOW_SECONDS = 120;

    protected $fillable = [
        'user_id',
        'device',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function onlineQuery()
    {
        return static::query()
            ->where('last_seen_at', '>=', now()->subSeconds(self::ONLINE_WINDOW_SECONDS));
    }
}
