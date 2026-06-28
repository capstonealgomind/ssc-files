<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    public const COLORS = [
        'Blue'   => '#2563eb',
        'Indigo' => '#6366f1',
        'Cyan'   => '#0891b2',
        'Green'  => '#059669',
        'Orange' => '#d97706',
        'Red'    => '#dc2626',
        'Purple' => '#9333ea',
        'Pink'   => '#db2777',
    ];

    public const DEFAULT_COLOR = 'Blue';

    protected $fillable = [
        'name',
        'acronym',
        'color',
    ];

    protected function acronym(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? strtoupper(trim($value)) : null,
        );
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public static function colorHex(?string $colorName): string
    {
        return self::COLORS[$colorName] ?? self::COLORS[self::DEFAULT_COLOR];
    }
}
