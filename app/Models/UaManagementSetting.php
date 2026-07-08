<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UaManagementSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'idle_seconds',
        'countdown_seconds',
        'sound_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled'        => 'boolean',
            'idle_seconds'      => 'integer',
            'countdown_seconds' => 'integer',
            'sound_enabled'     => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'is_enabled'        => true,
            'idle_seconds'      => 60,
            'countdown_seconds' => 10,
            'sound_enabled'     => true,
        ]);
    }
}
