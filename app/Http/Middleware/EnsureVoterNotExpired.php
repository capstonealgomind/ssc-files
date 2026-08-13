<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureVoterNotExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('logout')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || $user->skipsVoterVerification()) {
            return $next($request);
        }

        if ($user->markExpiredIfNeeded() || $user->isExpired()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your voter account has expired because the school year has ended. Please use Reactivate Account on the welcome page.',
            ]);
        }

        if ($user->markDisabledIfNeeded() || $user->isDisabled()) {
            if ($request->routeIs('account.disabled*')) {
                return $next($request);
            }

            return redirect()->route('account.disabled');
        }

        return $next($request);
    }
}
