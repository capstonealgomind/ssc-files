<?php

namespace App\Services;

use App\Models\LocationRangeSetting;

class LocationRangeService
{
    public function settings(): LocationRangeSetting
    {
        return LocationRangeSetting::current();
    }

    public function isEnabled(): bool
    {
        $settings = $this->settings();

        return $settings->is_enabled
            && $settings->latitude !== null
            && $settings->longitude !== null
            && $settings->range_meters !== null;
    }

    public function isWithinRange(float $latitude, float $longitude): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        $settings = $this->settings();

        $distance = $this->distanceInMeters(
            (float) $settings->latitude,
            (float) $settings->longitude,
            $latitude,
            $longitude,
        );

        return $distance <= (int) $settings->range_meters;
    }

    public function distanceInMeters(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude,
    ): float {
        $earthRadius = 6371000;

        $latFrom = deg2rad($fromLatitude);
        $lonFrom = deg2rad($fromLongitude);
        $latTo = deg2rad($toLatitude);
        $lonTo = deg2rad($toLongitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
