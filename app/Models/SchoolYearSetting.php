<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolYearSetting extends Model
{
    protected $fillable = [
        'start_year',
        'end_year',
    ];

    protected function casts(): array
    {
        return [
            'start_year' => 'integer',
            'end_year' => 'integer',
        ];
    }

    public static function current(): self
    {
        $now = now();
        $startYear = $now->month >= 6 ? $now->year : $now->year - 1;

        return static::query()->firstOrCreate([], [
            'start_year' => $startYear,
            'end_year' => $startYear + 1,
        ]);
    }

    public function label(): string
    {
        return $this->start_year.' - '.$this->end_year;
    }

    public function isConfigured(): bool
    {
        return $this->start_year > 0 && $this->end_year > $this->start_year;
    }
}
