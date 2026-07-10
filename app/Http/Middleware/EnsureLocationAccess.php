<?php

namespace App\Http\Middleware;

use App\Services\LocationRangeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLocationAccess
{
    public function __construct(
        private LocationRangeService $locationRange,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->locationRange->isEnabled()) {
            return $next($request);
        }

        if (in_array($request->user()?->role, ['admin', 'staff', 'committee'], true)) {
            return $next($request);
        }

        if ($request->routeIs('location.show', 'location.verify')) {
            return $next($request);
        }

        if ($request->routeIs('ballot-receipt.pdf')) {
            return $next($request);
        }

        if ($request->session()->get('location_access.verified') === true) {
            return $next($request);
        }

        if (! $request->routeIs('location.show')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('location.show');
    }
}
