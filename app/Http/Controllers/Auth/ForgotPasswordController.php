<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $resets,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = $this->findByEmail($email);

        if (! $user) {
            return back()
                ->withErrors(['email' => 'No account was found with this email address.'])
                ->onlyInput('email');
        }

        $issued = $this->resets->issue();
        $destination = $this->deliveryAddress($user, $email);

        try {
            Mail::to($destination)->send(new PasswordResetCodeMail(
                $issued['code'],
                $user->name ?: 'there',
                PasswordResetService::EXPIRY_MINUTES,
            ));
        } catch (\Throwable $exception) {
            Log::error('Password reset email failed for user '.$user->id.': '.$exception->getMessage());

            return back()
                ->withErrors(['email' => $this->mailFailureMessage($exception)])
                ->onlyInput('email');
        }

        $request->session()->put('password_reset', [
            'user_id' => $user->id,
            'email' => $destination,
            'hash' => $issued['hash'],
            'expires_at' => $issued['expires_at'],
            'attempts' => 0,
            'verified' => false,
        ]);

        return redirect()->route('password.otp');
    }

    public function otpForm(Request $request): Response|RedirectResponse
    {
        $state = $this->state($request);

        if (! $state) {
            return redirect()->route('password.request')
                ->with('error', 'Enter your email again to get a new code.');
        }

        if ($state['verified'] === true) {
            return redirect()->route('password.reset');
        }

        return Inertia::render('Auth/ForgotPasswordOtp', [
            'maskedEmail' => $this->maskEmail($state['email']),
            'expiryMinutes' => PasswordResetService::EXPIRY_MINUTES,
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $state = $this->state($request);
        $user = $state ? User::query()->find($state['user_id']) : null;

        if (! $state || ! $user) {
            return redirect()->route('password.request')
                ->with('error', 'Enter your email again to get a new code.');
        }

        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $checked = $this->resets->verify($state, $request->input('otp'));
        $state['hash'] = $checked['state']['hash'];
        $state['expires_at'] = $checked['state']['expires_at'];
        $state['attempts'] = $checked['state']['attempts'];
        $request->session()->put('password_reset', $state);

        if (! $checked['success']) {
            $remaining = $checked['remaining'];
            $message = match ($checked['reason']) {
                'expired' => 'That code has expired. Send a new code and try again.',
                'too_many_attempts' => 'Too many incorrect codes. Send a new code and try again.',
                default => 'That code is incorrect.'.($remaining > 0 ? " {$remaining} tries left." : ''),
            };

            return back()->withErrors(['otp' => $message]);
        }

        $state['verified'] = true;
        $request->session()->put('password_reset', $state);

        return redirect()->route('password.reset');
    }

    public function resend(Request $request): RedirectResponse
    {
        $state = $this->state($request);
        $user = $state ? User::query()->find($state['user_id']) : null;

        if (! $state || ! $user) {
            return redirect()->route('password.request')
                ->with('error', 'Enter your email again to get a new code.');
        }

        $issued = $this->resets->issue();

        try {
            Mail::to($state['email'])->send(new PasswordResetCodeMail(
                $issued['code'],
                $user->name ?: 'there',
                PasswordResetService::EXPIRY_MINUTES,
            ));
        } catch (\Throwable $exception) {
            Log::error('Password reset resend failed for user '.$user->id.': '.$exception->getMessage());

            return back()->withErrors(['otp' => $this->mailFailureMessage($exception)]);
        }

        $state['hash'] = $issued['hash'];
        $state['expires_at'] = $issued['expires_at'];
        $state['attempts'] = 0;
        $state['verified'] = false;
        $request->session()->put('password_reset', $state);

        return back()->with('success', 'A new code was sent to your email.');
    }

    public function resetForm(Request $request): Response|RedirectResponse
    {
        $state = $this->state($request);

        if (! $state || $state['verified'] !== true || ! User::query()->find($state['user_id'])) {
            return redirect()->route('password.request')
                ->with('error', 'Verify the code from your email before choosing a new password.');
        }

        return Inertia::render('Auth/ResetPassword');
    }

    public function reset(Request $request): RedirectResponse
    {
        $state = $this->state($request);
        $user = $state ? User::query()->find($state['user_id']) : null;

        if (! $state || $state['verified'] !== true || ! $user) {
            return redirect()->route('password.request')
                ->with('error', 'Verify the code from your email before choosing a new password.');
        }

        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::defaults()
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ], [
            'password.mixed' => 'Password must include uppercase and lowercase letters.',
            'password.numbers' => 'Password must include at least one number.',
            'password.symbols' => 'Password must include at least one symbol.',
            'password.letters' => 'Password must include letters.',
        ]);

        $user->forceFill([
            'password' => $validated['password'],
        ])->save();

        $request->session()->forget('password_reset');

        return redirect()
            ->route('login')
            ->with('success', 'Your password was changed. You can sign in with your new password.');
    }

    /**
     * @return array{user_id: int, email: string, hash: string, expires_at: string, attempts: int, verified: bool}|null
     */
    private function state(Request $request): ?array
    {
        $state = $request->session()->get('password_reset');

        if (! is_array($state) || empty($state['user_id']) || empty($state['email'])) {
            return null;
        }

        return $state;
    }

    private function findByEmail(string $email): ?User
    {
        return User::query()
            ->where(function ($query) use ($email) {
                $query->whereRaw('LOWER(email) = ?', [$email])
                    ->orWhereRaw('LOWER(contact_email) = ?', [$email]);
            })
            ->first();
    }

    private function deliveryAddress(User $user, string $requested): string
    {
        if ($user->contact_email && strtolower($user->contact_email) === $requested) {
            return $user->contact_email;
        }

        return $user->email;
    }

    private function mailFailureMessage(\Throwable $exception): string
    {
        $detail = trim($exception->getMessage());

        if (preg_match('/identities failed the check in region ([A-Z0-9-]+):\s*(.+)$/i', $detail, $matches)) {
            $sender = trim($matches[2], " \t\n\r\0\x0B.");

            return 'The code was not sent because the sender '.$sender.' is not verified in Amazon SES (region '.$matches[1].'). Verify that address in SES, then try again.';
        }

        if ($detail === '') {
            return 'The code was not sent. The email service did not return a reason.';
        }

        return 'The code was not sent. '.$detail;
    }

    private function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, 2);

        return $visible.str_repeat('*', max(strlen($local) - 2, 2)).'@'.$domain;
    }
}
