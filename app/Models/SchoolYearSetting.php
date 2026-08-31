<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolYearSetting extends Model
{
    protected $fillable = [
        'start_year',
        'end_year',
        'allow_year_level_edit',
        'year_level_edit_starts_at',
        'year_level_edit_ends_at',
    ];

    private static ?self $cachedCurrent = null;

    protected function casts(): array
    {
        return [
            'start_year' => 'integer',
            'end_year' => 'integer',
            'allow_year_level_edit' => 'boolean',
            'year_level_edit_starts_at' => 'datetime',
            'year_level_edit_ends_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        if (static::$cachedCurrent) {
            return static::$cachedCurrent;
        }

        $now = now();
        $startYear = $now->month >= 6 ? $now->year : $now->year - 1;

        return static::$cachedCurrent = static::query()->firstOrCreate([], [
            'start_year' => $startYear,
            'end_year' => $startYear + 1,
            'allow_year_level_edit' => false,
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

    public function yearsElapsedSince(?int $fromStartYear): int
    {
        if (! $this->isConfigured() || ! $fromStartYear) {
            return 0;
        }

        return (int) $this->start_year - (int) $fromStartYear;
    }

    /**
     * Expire year is the school-year end year plus remaining course years.
     * 4th year / 4-year course, 2026-2027 → 0 years left → 2027.
     * 2nd year / 4-year course, 2026-2027 → 2 years left → 2029.
     */
    public function expireYearForRemainingYears(int $remainingYears): int
    {
        return (int) $this->end_year + max(0, $remainingYears);
    }

    public function yearLevelEditWindowStatus(): string
    {
        if (! $this->allow_year_level_edit) {
            return 'off';
        }

        if (! $this->year_level_edit_starts_at || ! $this->year_level_edit_ends_at) {
            return 'not_configured';
        }

        $endsAt = $this->yearLevelEditEndsAtInclusive();

        if (now()->lt($this->year_level_edit_starts_at)) {
            return 'upcoming';
        }

        if ($endsAt && now()->gt($endsAt)) {
            return 'ended';
        }

        return 'open';
    }

    public function yearLevelEditEndsAtInclusive(): ?\Carbon\Carbon
    {
        if (! $this->year_level_edit_ends_at) {
            return null;
        }

        $end = $this->year_level_edit_ends_at->copy();

        // A midnight deadline is treated as the whole calendar day.
        if ($end->hour === 0 && $end->minute === 0 && $end->second === 0) {
            return $end->endOfDay();
        }

        return $end;
    }

    public function yearLevelEditWindowContains(?\DateTimeInterface $at): bool
    {
        if (! $at || ! $this->year_level_edit_starts_at) {
            return false;
        }

        $endsAt = $this->yearLevelEditEndsAtInclusive();
        if (! $endsAt) {
            return false;
        }

        $moment = \Carbon\Carbon::parse($at);

        return $moment->gte($this->year_level_edit_starts_at)
            && $moment->lte($endsAt);
    }

    public function yearLevelEditWindowStatusLabel(): string
    {
        return match ($this->yearLevelEditWindowStatus()) {
            'off' => 'Year level updates off',
            'not_configured' => 'Set the update window',
            'upcoming' => 'Update window upcoming',
            'open' => 'Update window open',
            'ended' => 'Update window ended',
            default => 'Not scheduled',
        };
    }

    public function isYearLevelEditWindowOpen(): bool
    {
        return $this->yearLevelEditWindowStatus() === 'open';
    }

    public function isYearLevelEditWindowEnded(): bool
    {
        return $this->yearLevelEditWindowStatus() === 'ended';
    }

    public static function forgetCurrent(): void
    {
        static::$cachedCurrent = null;
    }
}
