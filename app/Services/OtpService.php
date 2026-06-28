<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public const EXPIRY_MINUTES = 10;
    public const MAX_ATTEMPTS   = 5;

    public function generate(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code'       => Hash::make($code),
            'otp_expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            'otp_attempts'   => 0,
        ]);

        return $code;
    }

    public function verify(User $user, string $code): OtpVerifyResult
    {
        if ($user->otp_attempts >= self::MAX_ATTEMPTS) {
            return new OtpVerifyResult(false, 'too_many_attempts');
        }

        if (!$user->otp_expires_at || now()->isAfter($user->otp_expires_at)) {
            return new OtpVerifyResult(false, 'expired');
        }

        if (!Hash::check($code, $user->otp_code ?? '')) {
            $user->increment('otp_attempts');
            $remaining = self::MAX_ATTEMPTS - $user->fresh()->otp_attempts;
            return new OtpVerifyResult(false, 'invalid', $remaining);
        }

        // Clear OTP after successful verification
        $user->update([
            'otp_code'       => null,
            'otp_expires_at' => null,
            'otp_attempts'   => 0,
        ]);

        return new OtpVerifyResult(true, 'ok');
    }

    public function isMaxAttemptsReached(User $user): bool
    {
        return $user->otp_attempts >= self::MAX_ATTEMPTS;
    }
}

class OtpVerifyResult
{
    public function __construct(
        public readonly bool   $success,
        public readonly string $reason,
        public readonly int    $remainingAttempts = 0,
    ) {}
}
