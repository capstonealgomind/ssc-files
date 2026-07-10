<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessOcrVerification;
use App\Jobs\SendVerificationEmail;
use App\Models\RegistrationAttempt;
use App\Models\User;
use App\Services\OtpService;
use App\Support\QueueKick;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OtpController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $userId = $request->session()->get('reg_user_id');

        if (!$userId) {
            return redirect()->route('register')
                ->with('error', 'Session expired. Please start registration again.');
        }

        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('register')
                ->with('error', 'Session expired. Please start registration again.');
        }

        if ($user->email_status === 'verified') {
            return Inertia::render('Auth/EmailVerificationResult', [
                'success'       => true,
                'message'       => 'Your email is already verified.',
                'alreadyVerified' => true,
                'voterIdNumber' => $user->voter_id_number,
            ]);
        }

        if ($user->registration_status !== User::STATUS_PENDING_OTP) {
            return redirect()->route('register');
        }

        return Inertia::render('Auth/VerifyOtp', [
            'email'         => $user->email,
            'maskedEmail'   => $this->maskEmail($user->email),
            'expiryMinutes' => OtpService::EXPIRY_MINUTES,
        ]);
    }

    public function store(Request $request): Response|RedirectResponse
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
                'expired'           => 'The verification code has expired. Please request a new one.',
                'too_many_attempts' => 'Too many incorrect attempts. Please request a new code.',
                default             => 'Incorrect code. ' . ($result->remainingAttempts > 0 ? "{$result->remainingAttempts} attempt(s) remaining." : ''),
            };

            return back()->withErrors(['otp' => $message]);
        }

        $user->update([
            'email_verified_at'   => now(),
            'email_status'        => 'verified',
            'registration_status' => User::STATUS_ACTIVE,
            'ocr_status'          => 'processing',
        ]);

        $user->load(['course', 'yearLevel']);
        $user->applyCourseExpiry();

        ProcessOcrVerification::dispatch($user->id);
        QueueKick::afterResponse();

        $voterId = $user->voter_id_number;

        $request->session()->forget(['reg_user_id', 'reg_device_fp', 'reg_image_quality', 'reg_voter_id']);

        return Inertia::render('Auth/EmailVerificationResult', [
            'success'       => true,
            'message'       => 'Email verified successfully! Your account is now being processed.',
            'voterIdNumber' => $voterId,
        ]);
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

        $code      = $this->otpService->generate($user);
        $verifyUrl = route('email.verify', ['token' => $user->email_verify_token]);

        $user->update(['email_send_status' => 'pending']);

        SendVerificationEmail::dispatch(
            $user->id,
            $verifyUrl,
            $code,
            $user->voter_id_number,
        );
        QueueKick::afterResponse();

        return back()->with('status', 'A new verification code is being sent to your email.');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);
        $masked = substr($local, 0, 2) . str_repeat('*', max(strlen($local) - 2, 2));

        return $masked . '@' . $domain;
    }
}
