<?php

use App\Models\BallotReceipt;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\LocationRangeSetting;
use App\Models\Position;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    LocationRangeSetting::current()->update([
        'is_enabled' => false,
    ]);
});

/**
 * Assert the response is not a missing page (404) or server error (5xx).
 * Redirects (3xx) and auth denials (401/403) are allowed.
 */
function assertPageHealthy(mixed $response, string $label): void
{
    $status = method_exists($response, 'status')
        ? $response->status()
        : $response->getStatusCode();

    if ($status === 404 || $status >= 500) {
        $exception = $response->exception ?? null;
        $detail = $exception
            ? $exception::class.': '.$exception->getMessage()
            : (method_exists($response, 'getContent')
                ? substr(strip_tags((string) $response->getContent()), 0, 400)
                : 'no body');

        expect(true)->toBeFalse("{$label} returned {$status}. {$detail}");

        return;
    }

    expect($status)->toBeLessThan(500);
    expect($status)->not->toBe(404);
}

function makeUser(string $role, array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'role' => $role,
        'is_verified' => true,
        'registration_status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ], $overrides));
}

function seedSmokeFixtures(User $admin): array
{
    $election = Election::create([
        'title' => 'Smoke Test Election',
        'description' => 'Used by page smoke tests',
        'event_starts_at' => now()->subDay(),
        'event_ends_at' => now()->addDays(7),
        'voting_starts_at' => now()->subHour(),
        'voting_ends_at' => now()->addDays(3),
        'status' => Election::STATUS_ACTIVE,
        'created_by' => $admin->id,
    ]);

    $position = Position::create([
        'name' => 'Smoke President',
        'sort_order' => 1,
    ]);

    $candidate = Candidate::create([
        'election_id' => $election->id,
        'name' => 'Smoke Candidate',
        'position_id' => $position->id,
        'platform' => 'Smoke platform',
    ]);

    $voter = makeUser('voter', [
        'email' => 'smoke-voter@example.com',
        'name' => 'Smoke Voter',
    ]);

    $receipt = BallotReceipt::create([
        'user_id' => $voter->id,
        'election_id' => $election->id,
        'receipt_number' => 'BR-'.now()->year.'-000001',
        'submitted_at' => now(),
    ]);

    $ticket = SupportTicket::create([
        'ticket_number' => 'TKT-SMOKE-001',
        'user_id' => $voter->id,
        'subject' => 'Smoke support ticket',
        'category' => 'other',
        'status' => SupportTicket::STATUS_PENDING,
        'last_message_at' => now(),
    ]);

    return compact('election', 'position', 'candidate', 'voter', 'receipt', 'ticket');
}

it('loads public pages without 404 or 500', function () {
    $paths = [
        '/',
        '/live-standing',
        '/check-status',
        '/reactivate',
        '/reactivation-status',
        '/location',
        '/up',
    ];

    foreach ($paths as $path) {
        assertPageHealthy($this->get($path), "GET {$path}");
    }
});

it('loads guest auth pages without 404 or 500', function () {
    $paths = [
        '/login',
        '/register',
        '/register/id-scan',
        '/register/success',
        '/register/verify-otp',
        '/email/verify/invalid-token-for-smoke-test',
    ];

    foreach ($paths as $path) {
        assertPageHealthy($this->get($path), "GET {$path}");
    }
});

it('loads admin pages without 404 or 500', function () {
    $admin = makeUser('admin', [
        'email' => 'smoke-admin@'.User::ADMIN_EMAIL_DOMAIN,
        'name' => 'Smoke Admin',
    ]);

    $fixtures = seedSmokeFixtures($admin);

    $paths = [
        '/dashboard',
        '/profile',
        '/elections',
        '/candidates',
        '/candidates/create',
        "/candidates/{$fixtures['candidate']->id}/edit",
        '/voters',
        "/voters/{$fixtures['voter']->id}",
        '/reactivation-requests',
        '/support',
        "/support/tickets/{$fixtures['ticket']->id}",
        '/announcements/manage',
        '/monitoring',
        '/reports',
        "/reports/{$fixtures['election']->id}/export/turnout/pdf",
        "/reports/{$fixtures['election']->id}/export/turnout/excel",
        '/settings',
        '/system',
        '/accounts',
        '/audit-logs',
        '/registration-attempts',
        '/my-votes',
        '/results',
        '/announcements',
        '/help',
        '/faq',
        '/registration-status',
        "/ballot-receipt/{$fixtures['receipt']->id}",
    ];

    $this->actingAs($admin);

    foreach ($paths as $path) {
        assertPageHealthy($this->get($path), "Admin GET {$path}");
    }
});

it('loads committee pages without 404 or 500', function () {
    $admin = makeUser('admin', [
        'email' => 'smoke-admin2@'.User::ADMIN_EMAIL_DOMAIN,
    ]);
    $committee = makeUser('committee', [
        'email' => 'smoke-committee@'.User::COMMITTEE_EMAIL_DOMAIN,
        'name' => 'Smoke Committee',
    ]);

    seedSmokeFixtures($admin);

    $paths = [
        '/dashboard',
        '/profile',
        '/committee',
        '/elections',
        '/voters',
    ];

    $this->actingAs($committee);

    foreach ($paths as $path) {
        assertPageHealthy($this->get($path), "Committee GET {$path}");
    }
});

it('loads voter pages without 404 or 500', function () {
    $admin = makeUser('admin', [
        'email' => 'smoke-admin3@'.User::ADMIN_EMAIL_DOMAIN,
    ]);
    $fixtures = seedSmokeFixtures($admin);
    $voter = $fixtures['voter'];

    $paths = [
        '/dashboard',
        '/profile',
        '/elections',
        '/my-votes',
        '/results',
        '/announcements',
        '/help',
        "/help/tickets/{$fixtures['ticket']->id}",
        '/faq',
        '/registration-status',
        "/ballot-receipt/{$fixtures['receipt']->id}",
    ];

    $this->actingAs($voter);

    foreach ($paths as $path) {
        assertPageHealthy($this->get($path), "Voter GET {$path}");
    }
});

it('rejects unknown pages with 404 and does not 500', function () {
    $response = $this->get('/this-route-definitely-does-not-exist-sscevs');

    expect($response->status())->toBe(404);
});
