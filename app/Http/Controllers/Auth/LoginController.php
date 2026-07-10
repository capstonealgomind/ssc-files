<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TurnstileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(TurnstileService $turnstile): Response
    {
        $this->forgetUnsafeIntendedUrl();

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => session('status'),
            'turnstileSiteKey' => $turnstile->isEnabled() ? $turnstile->siteKey() : null,
        ]);
    }

    public function store(Request $request, TurnstileService $turnstile): RedirectResponse
    {
        $rules = [
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ];

        if ($turnstile->isEnabled()) {
            $rules['turnstile_token'] = 'required|string';
        }

        $request->validate($rules, [
            'turnstile_token.required' => 'Please complete the security verification.',
        ]);

        if ($turnstile->isEnabled() && ! $turnstile->verify($request->input('turnstile_token'), $request->ip())) {
            return back()->withErrors([
                'turnstile_token' => 'Security verification failed. Please try again.',
            ])->onlyInput('email');
        }

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        // Skip verification checks for admin/staff/committee roles
        if (! $user->skipsVoterVerification()) {
            if ($user->email_status !== 'verified') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Please verify your email address before logging in. Check your inbox for the verification link.',
                ])->onlyInput('email');
            }

            if ($user->markExpiredIfNeeded() || $user->isExpired()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your voter account has expired based on your course duration. Please use Reactivate Account on the welcome page.',
                ])->onlyInput('email');
            }

            if (!$user->is_verified) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account is pending verification. Please wait for admin approval or check your registration status.',
                ])->onlyInput('email');
            }
        }

        $request->session()->regenerate();

        return $this->redirectAfterLogin()
            ->with('success', 'Welcome back! You have signed in successfully.');
    }

    private function redirectAfterLogin(): RedirectResponse
    {
        $default = route('dashboard');
        $intended = session()->pull('url.intended');

        if ($intended && $this->isSafePostLoginRedirect($intended)) {
            return redirect()->to($intended);
        }

        return redirect()->to($default);
    }

    /**
     * File-download and binary routes must not be used as post-login redirects.
     * Inertia follows redirects via XHR and cannot render PDF/binary responses.
     */
    private function isSafePostLoginRedirect(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? $url;

        return ! preg_match('#/ballot-receipt/\d+/pdf$#', $path);
    }

    private function forgetUnsafeIntendedUrl(): void
    {
        $intended = session('url.intended');

        if ($intended && ! $this->isSafePostLoginRedirect($intended)) {
            session()->forget('url.intended');
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'You have been logged out successfully.');
    }
}
