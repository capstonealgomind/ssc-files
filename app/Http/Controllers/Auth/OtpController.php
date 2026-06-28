<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RegistrationAttempt;
use App\Models\User;
use App\Services\FraudDetectionService;
use App\Services\OtpService;
use App\Mail\OtpMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class OtpController extends Controller
{
    public function __construct(
        private readonly OtpService            $otpService,
        private readonly FraudDetectionService $fraud,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $userId = $request->session()->get('reg_user_id');

        if (!$userId) {
            return redirect()->route('register')
                ->with('error', 'Session expired. Please start registration again.');
        }

        $user = User::find($userId);

        if (!$user || $user->registration_status !== User::STATUS_PENDING_OTP) {
            return redirect()->route('register');
        }

        return Inertia::render('Auth/VerifyOtp', [
            'email'         => $user->email,
            'maskedEmail'   => $this->maskEmail($user->email),
            'expiryMinutes' => OtpService::EXPIRY_MINUTES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('reg_user_id');

        if (!$userId) {
            return redirect()->route('register')
                ->with('error', 'Session expired. Please start registration again.');
        }

        $user = User::find($userId);

        if (!$user || $user->registration_status !== User::STATUS_PENDING_OTP) {
            return redirect()->route('register');
        }

        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $result = $this->otpService->verify($user, $request->input('otp'));

        if (!$result->success) {
            $fingerprint = $request->session()->get('reg_device_fp');

            if ($fingerprint) {
                RegistrationAttempt::create([
                    'device_fingerprint' => $fingerprint,
                    'ip_address'         => $request->ip(),
                    'action'             => 'otp_failed',
                    'user_id'            => $user->id,
                ]);
            }

            $message = match ($result->reason) {
                'expired'          => 'The verification code has expired. Please request a new one.',
                'too_many_attempts'=> 'Too many incorrect attempts. Please request a new code.',
                default            => 'Incorrect code. ' . ($result->remainingAttempts > 0 ? "{$result->remainingAttempts} attempt(s) remaining." : ''),
            };

            return back()->withErrors(['otp' => $message]);
        }

        // ── OTP verified ──────────────────────────────────────────────────
        $user->update([
            'email_verified_at'   => now(),
            'registration_status' => User::STATUS_ACTIVE,
        ]);

        // ── Compute fraud score (fresh() reloads email_verified_at from DB) ─
        $breakdown = $this->fraud->calculate($user->fresh(), [
            'device_fingerprint' => $request->session()->get('reg_device_fp', ''),
            'image_quality'      => $request->session()->get('reg_image_quality', 'good'),
        ]);

        $user->update(['fraud_score' => $breakdown->total]);

        // ── Clean session ──────────────────────────────────────────────────
        $request->session()->forget(['reg_user_id', 'reg_device_fp', 'reg_image_quality']);

        // ── Log in and redirect ────────────────────────────────────────────
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', "Registration complete! Your Voter ID is {$user->voter_id_number}. An admin will verify your account before you can vote.");
    }

    public function resend(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('reg_user_id');
        $user   = $userId ? User::find($userId) : null;

        if (!$user || $user->registration_status !== User::STATUS_PENDING_OTP) {
            return redirect()->route('register');
        }

        if ($this->otpService->isMaxAttemptsReached($user)) {
            return back()->withErrors(['otp' => 'Too many incorrect attempts. Please contact support.']);
        }

        $code = $this->otpService->generate($user);

        try {
            Mail::to($user->email)->send(new OtpMail($code, $user->name));
        } catch (\Throwable) {
            return back()->with('error', 'Failed to send OTP. Please try again.');
        }

        return back()->with('status', 'A new verification code has been sent to your email.');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);
        $masked = substr($local, 0, 2) . str_repeat('*', max(strlen($local) - 2, 2));
        return $masked . '@' . $domain;
    }
}
