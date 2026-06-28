<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => session('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        // Skip verification checks for admin/staff roles
        if ($user->role !== 'admin' && $user->role !== 'staff') {
            if ($user->email_status !== 'verified') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Please verify your email address before logging in. Check your inbox for the verification link.',
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

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Welcome back! You have signed in successfully.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'You have been logged out successfully.');
    }
}
