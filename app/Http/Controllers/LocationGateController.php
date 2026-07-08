<?php

namespace App\Http\Controllers;

use App\Services\LocationRangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LocationGateController extends Controller
{
    public function show(Request $request, LocationRangeService $locationRange): Response|RedirectResponse
    {
        if (! $locationRange->isEnabled()) {
            return redirect()->route('home');
        }

        if (in_array($request->user()?->role, ['admin', 'staff'], true)) {
            return redirect()->route('home');
        }

        if ($request->session()->get('location_access.verified') === true) {
            return redirect()->intended(route('home'));
        }

        $settings = $locationRange->settings();

        return Inertia::render('LocationGate', [
            'rangeMeters' => $settings->range_meters,
        ]);
    }

    public function verify(Request $request, LocationRangeService $locationRange): RedirectResponse
    {
        if (! $locationRange->isEnabled()) {
            return redirect()->route('home');
        }

        if (in_array($request->user()?->role, ['admin', 'staff'], true)) {
            return redirect()->route('home');
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];

        if (! $locationRange->isWithinRange($latitude, $longitude)) {
            return redirect()
                ->route('location.show')
                ->withErrors([
                    'location' => 'Outside campus area.',
                ]);
        }

        $request->session()->put('location_access', [
            'verified' => true,
            'verified_at' => now()->toIso8601String(),
        ]);

        return redirect()->intended(route('home'));
    }
}
