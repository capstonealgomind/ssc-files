<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationRangeSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'latitude',
        'longitude',
        'range_meters',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled'   => 'boolean',
            'latitude'     => 'float',
            'longitude'    => 'float',
            'range_meters' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'is_enabled'   => false,
            'latitude'     => null,
            'longitude'    => null,
            'range_meters' => null,
        ]);
    }
}
