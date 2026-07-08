<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\DtsRegistrationService;
use App\Services\LocationRangeService;
use App\Services\UaManagementService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'csrf_token' => csrf_token(),
            'auth' => [
                'user' => $request->user() ? [
                    'id'                => $request->user()->id,
                    'name'              => $request->user()->name,
                    'email'             => $request->user()->email,
                    'role'              => $request->user()->role,
                    'profile_photo_url' => $request->user()->profilePhotoUrl(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->pull('success'),
                'error'   => fn () => $request->session()->pull('error'),
            ],
            'adminEmailDomain' => User::ADMIN_EMAIL_DOMAIN,
            'pusher' => [
                'key'     => config('broadcasting.connections.pusher.key'),
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            ],
            'locationGate' => fn () => [
                'required' => app(LocationRangeService::class)->isEnabled(),
                'verified' => (bool) $request->session()->get('location_access.verified'),
            ],
            'registrationWindow' => fn () => app(DtsRegistrationService::class)->publicPayload(),
            'uaManagement' => fn () => app(UaManagementService::class)->publicPayload(),
        ]);
    }
}
