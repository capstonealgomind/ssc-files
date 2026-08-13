<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\SchoolYearSetting;
use App\Services\DtsRegistrationService;
use App\Services\LocationRangeService;
use App\Services\UaManagementService;
use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Support\Header;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Ensure browser document navigations never receive Inertia JSON,
     * and prevent CDNs/browsers from caching JSON page responses as HTML.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Full page loads (leave site / return / refresh / address bar) must get HTML.
        // Some mobile browsers and CDNs can replay a prior Inertia JSON response otherwise.
        if ($this->isBrowserDocumentRequest($request)) {
            $request->headers->remove(Header::INERTIA);
        }

        /** @var Response $response */
        $response = parent::handle($request, $next);

        $response->headers->set(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, max-age=0, private'
        );
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Vary', Header::INERTIA.', Accept');

        return $response;
    }

    protected function isBrowserDocumentRequest(Request $request): bool
    {
        $dest = strtolower((string) $request->headers->get('Sec-Fetch-Dest', ''));
        $mode = strtolower((string) $request->headers->get('Sec-Fetch-Mode', ''));

        if ($dest === 'document' || $mode === 'navigate') {
            return true;
        }

        return false;
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
                    'allowed_pages'     => $request->user()->role === 'committee'
                        ? $request->user()->allowedPages()
                        : ($request->user()->role === 'admin'
                            ? \App\Support\CommitteePageCatalog::keys()
                            : []),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->pull('success'),
                'error'   => fn () => $request->session()->pull('error'),
                'ballot_receipt_id' => fn () => $request->session()->pull('ballot_receipt_id'),
                'ballot_submission_id' => fn () => $request->session()->pull('ballot_submission_id'),
                'reactivation_number' => fn () => $request->session()->pull('reactivation_number'),
            ],
            'adminEmailDomain' => User::ADMIN_EMAIL_DOMAIN,
            'committeeEmailDomain' => User::COMMITTEE_EMAIL_DOMAIN,
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
            'yearLevelUpdateNotice' => function () use ($request) {
                $user = $request->user();

                if (! $user || $user->role !== 'voter' || $user->isExpired() || $user->isDisabled()) {
                    return null;
                }

                if ($user->hasUpdatedYearLevelThisSchoolYear()) {
                    return null;
                }

                $settings = SchoolYearSetting::current();
                $override = (bool) $user->year_level_update_override;

                if (! $override && ! $settings->isYearLevelEditWindowOpen()) {
                    return null;
                }

                $endsAt = $settings->year_level_edit_ends_at;
                $endsAtLabel = $endsAt?->format('M d, Y g:i A');

                return [
                    'message' => $override
                        ? 'Please update your year level on your Profile before you can vote. Your appeal was approved, but your year level still needs to be current.'
                        : ($endsAtLabel
                            ? "Please update your year level before {$endsAtLabel}. Accounts that miss this deadline will be disabled."
                            : 'Please update your year level on your Profile before the deadline ends.'),
                    'ends_at_label' => $override ? null : $endsAtLabel,
                    'profile_url' => route('profile'),
                ];
            },
        ]);
    }
}
