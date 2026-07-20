<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GallerySetting extends Model
{
    public const STYLE_DOME = 'dome';

    public const STYLE_CIRCULAR = 'circular';

    public const STYLES = [
        self::STYLE_DOME,
        self::STYLE_CIRCULAR,
    ];

    protected $fillable = [
        'style',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'style' => self::STYLE_DOME,
        ]);
    }

    public function isDome(): bool
    {
        return $this->style === self::STYLE_DOME;
    }

    public function isCircular(): bool
    {
        return $this->style === self::STYLE_CIRCULAR;
    }
}
