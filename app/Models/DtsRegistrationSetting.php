<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DtsRegistrationSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'starts_at'  => 'datetime',
            'ends_at'    => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'is_enabled' => false,
            'starts_at'  => null,
            'ends_at'    => null,
        ]);
    }
}
