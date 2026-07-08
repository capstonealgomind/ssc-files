<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TurnstileService
{
    public function isEnabled(): bool
    {
        return filled(config('services.turnstile.secret'))
            && filled(config('services.turnstile.site_key'));
    }

    public function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }

    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        if (! $this->isEnabled() || blank($token)) {
            return false;
        }

        $payload = [
            'secret'   => config('services.turnstile.secret'),
            'response' => $token,
        ];

        if ($remoteIp) {
            $payload['remoteip'] = $remoteIp;
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $payload);

        if (! $response->successful()) {
            return false;
        }

        return (bool) $response->json('success');
    }
}
