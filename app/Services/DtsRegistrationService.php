<?php

namespace App\Services;

use App\Models\DtsRegistrationSetting;

class DtsRegistrationService
{
    public function settings(): DtsRegistrationSetting
    {
        return DtsRegistrationSetting::current();
    }

    public function isEnabled(): bool
    {
        return $this->settings()->is_enabled;
    }

    public function isOpen(): bool
    {
        $settings = $this->settings();

        if (! $settings->is_enabled) {
            return true;
        }

        if (! $settings->starts_at || ! $settings->ends_at) {
            return false;
        }

        return now()->between($settings->starts_at, $settings->ends_at);
    }

    public function status(): string
    {
        $settings = $this->settings();

        if (! $settings->is_enabled) {
            return 'unrestricted';
        }

        if (! $settings->starts_at || ! $settings->ends_at) {
            return 'not_configured';
        }

        if (now()->lt($settings->starts_at)) {
            return 'upcoming';
        }

        if (now()->gt($settings->ends_at)) {
            return 'closed';
        }

        return 'open';
    }

    public function closedMessage(): string
    {
        $settings = $this->settings();

        return match ($this->status()) {
            'upcoming' => 'Registration opens on '.$settings->starts_at->format('M d, Y g:i A').'.',
            'closed'   => 'Registration closed on '.$settings->ends_at->format('M d, Y g:i A').'.',
            'not_configured' => 'Registration is not open at this time.',
            default => 'Registration is not open at this time.',
        };
    }

    public function adminPayload(): array
    {
        $settings = $this->settings();

        return [
            'is_enabled' => $settings->is_enabled,
            'starts_at'  => $settings->starts_at?->format('Y-m-d\TH:i'),
            'ends_at'    => $settings->ends_at?->format('Y-m-d\TH:i'),
            'status'     => $this->status(),
            'status_label' => $this->statusLabel(),
        ];
    }

    public function publicPayload(): array
    {
        $settings = $this->settings();
        $status = $this->status();
        $countdownTarget = $this->countdownTarget();

        return [
            'is_scheduled'     => $settings->is_enabled,
            'is_open'          => $this->isOpen(),
            'status'           => $status,
            'starts_at'        => $settings->starts_at?->format('M d, Y g:i A'),
            'ends_at'          => $settings->ends_at?->format('M d, Y g:i A'),
            'starts_at_iso'    => $settings->starts_at?->toIso8601String(),
            'ends_at_iso'      => $settings->ends_at?->toIso8601String(),
            'show_countdown'   => $countdownTarget !== null,
            'countdown_label'  => $this->countdownLabel(),
            'countdown_to_iso' => $countdownTarget?->toIso8601String(),
            'message'          => $this->isOpen() ? null : $this->closedMessage(),
        ];
    }

    private function countdownTarget(): ?\Illuminate\Support\Carbon
    {
        $settings = $this->settings();

        return match ($this->status()) {
            'upcoming' => $settings->starts_at,
            'open'     => $settings->ends_at,
            default    => null,
        };
    }

    private function countdownLabel(): ?string
    {
        return match ($this->status()) {
            'upcoming' => 'Registration opens in',
            'open'     => 'Registration closes in',
            default    => null,
        };
    }

    private function statusLabel(): string
    {
        return match ($this->status()) {
            'unrestricted'     => 'Not scheduled',
            'not_configured'   => 'Schedule incomplete',
            'upcoming'         => 'Upcoming',
            'open'             => 'Open now',
            'closed'           => 'Closed',
            default            => 'Unknown',
        };
    }
}
