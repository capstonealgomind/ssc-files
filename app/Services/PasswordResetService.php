<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class PasswordResetService
{
    public const EXPIRY_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /**
     * @return array{hash: string, expires_at: string, attempts: int, code: string}
     */
    public function issue(): array
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return [
            'hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES)->toIso8601String(),
            'attempts' => 0,
            'code' => $code,
        ];
    }

    /**
     * @param  array{hash?: string, expires_at?: string, attempts?: int}  $state
     * @return array{success: bool, reason: string, remaining: int, state: array{hash: string, expires_at: string, attempts: int}}
     */
    public function verify(array $state, string $code): array
    {
        $attempts = (int) ($state['attempts'] ?? 0);
        $hash = (string) ($state['hash'] ?? '');
        $expiresAt = $state['expires_at'] ?? null;

        if ($attempts >= self::MAX_ATTEMPTS) {
            return $this->outcome(false, 'too_many_attempts', 0, $hash, (string) $expiresAt, $attempts);
        }

        if (! $expiresAt || now()->isAfter($expiresAt)) {
            return $this->outcome(false, 'expired', 0, $hash, (string) $expiresAt, $attempts);
        }

        if (! Hash::check($code, $hash)) {
            $attempts++;

            return $this->outcome(false, 'invalid', max(self::MAX_ATTEMPTS - $attempts, 0), $hash, (string) $expiresAt, $attempts);
        }

        return $this->outcome(true, 'ok', 0, '', '', 0);
    }

    /**
     * @return array{success: bool, reason: string, remaining: int, state: array{hash: string, expires_at: string, attempts: int}}
     */
    private function outcome(bool $success, string $reason, int $remaining, string $hash, string $expiresAt, int $attempts): array
    {
        return [
            'success' => $success,
            'reason' => $reason,
            'remaining' => $remaining,
            'state' => $this->state($hash, $expiresAt, $attempts),
        ];
    }

    /**
     * @return array{hash: string, expires_at: string, attempts: int}
     */
    private function state(string $hash, string $expiresAt, int $attempts): array
    {
        return [
            'hash' => $hash,
            'expires_at' => $expiresAt,
            'attempts' => $attempts,
        ];
    }
}
