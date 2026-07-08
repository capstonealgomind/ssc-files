<?php

namespace App\Services;

use App\Models\UaManagementSetting;

class UaManagementService
{
    public function settings(): UaManagementSetting
    {
        return UaManagementSetting::current();
    }

    public function isEnabled(): bool
    {
        return $this->settings()->is_enabled;
    }

    public function adminPayload(): array
    {
        $settings = $this->settings();

        return [
            'is_enabled'        => (bool) $settings->is_enabled,
            'idle_seconds'      => (int) $settings->idle_seconds,
            'countdown_seconds' => (int) $settings->countdown_seconds,
            'sound_enabled'     => (bool) $settings->sound_enabled,
        ];
    }

    public function publicPayload(): array
    {
        return $this->adminPayload();
    }
}
