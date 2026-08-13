<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\BallotReceipt;
use App\Models\Candidate;
use App\Models\Course;
use App\Models\Department;
use App\Models\Election;
use App\Models\Partylist;
use App\Models\Position;
use App\Models\ReactivationRequest;
use App\Models\RegistrationAttempt;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\YearLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(in_array($user?->role, ['admin', 'committee'], true), 403);

        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 1) {
            return response()->json(['results' => []]);
        }

        $like = $this->like($query);
        $results = collect();

        $results = $results->merge($this->searchPages($user, $query));

        if ($user->role === 'admin') {
            $results = $results
                ->merge($this->searchVoters($like))
                ->merge($this->searchStaffAccounts($like))
                ->merge($this->searchCandidates($like, admin: true))
                ->merge($this->searchElections($like, $query))
                ->merge($this->searchTickets($like))
                ->merge($this->searchTicketMessages($like))
                ->merge($this->searchAnnouncements($like))
                ->merge($this->searchReactivations($like))
                ->merge($this->searchRegistrationAttempts($like))
                ->merge($this->searchBallotReceipts($like))
                ->merge($this->searchDepartments($like))
                ->merge($this->searchCourses($like))
                ->merge($this->searchYearLevels($like))
                ->merge($this->searchPositions($like))
                ->merge($this->searchPartylists($like));
        } else {
            $allowed = $user->allowedPages();

            if (in_array('voters', $allowed, true)) {
                $results = $results->merge($this->searchVoters($like));
            }

            if (in_array('candidates', $allowed, true)) {
                $results = $results->merge($this->searchCandidates($like, admin: false));
            }

            if (in_array('elections', $allowed, true)) {
                $results = $results->merge($this->searchElections($like, $query));
            }

            if (in_array('support', $allowed, true)) {
                $results = $results
                    ->merge($this->searchTickets($like))
                    ->merge($this->searchTicketMessages($like));
            }

            if (in_array('announcements', $allowed, true)) {
                $results = $results->merge($this->searchAnnouncements($like));
            }

            if (in_array('reactivation', $allowed, true)) {
                $results = $results->merge($this->searchReactivations($like));
            }

            if (in_array('candidates', $allowed, true) || in_array('elections', $allowed, true)) {
                $results = $results
                    ->merge($this->searchDepartments($like))
                    ->merge($this->searchCourses($like))
                    ->merge($this->searchPositions($like))
                    ->merge($this->searchPartylists($like));
            }
        }

        return response()->json([
            'results' => $results
                ->unique(fn (array $item) => $item['type'].'|'.$item['href'].'|'.$item['title'])
                ->take(25)
                ->values()
                ->all(),
        ]);
    }

    private function like(string $query): string
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);

        return "%{$escaped}%";
    }

    private function matches(string $needle, string ...$parts): bool
    {
        $haystack = mb_strtolower(implode(' ', $parts));

        return str_contains($haystack, mb_strtolower($needle));
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchPages(User $user, string $query): array
    {
        $pages = [
            // Core pages
            [
                'title' => 'Dashboard',
                'subtitle' => 'Overview, live activity, turnout, votes cast',
                'href' => '/dashboard',
                'keywords' => ['home', 'overview', 'stats', 'analytics', 'metrics', 'turnout', 'votes cast', 'active elections', 'live vote'],
                'roles' => ['admin', 'committee'],
                'page_key' => 'dashboard',
            ],
            [
                'title' => 'Candidate',
                'subtitle' => 'Add and manage election candidates',
                'href' => '/candidates',
                'keywords' => ['candidates', 'nominee', 'partylist', 'position', 'department', 'course', 'platform', 'photo', 'dropdown'],
                'roles' => ['committee'],
                'page_key' => 'candidates',
            ],
            [
                'title' => 'Elections',
                'subtitle' => 'Create and manage elections · Draft / Scheduled / Active / Closed',
                'href' => '/elections',
                'keywords' => ['vote', 'voting', 'ballot', 'schedule', 'draft', 'scheduled', 'active', 'closed', 'status dropdown'],
                'roles' => ['admin', 'committee'],
                'page_key' => 'elections',
            ],
            [
                'title' => 'Candidates',
                'subtitle' => 'Candidate records, positions, partylists',
                'href' => '/candidates',
                'keywords' => ['nominee', 'partylist', 'position', 'platform', 'department', 'course', 'filter'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'Add Candidate',
                'subtitle' => 'Create a new candidate record',
                'href' => '/candidates/create',
                'keywords' => ['new candidate', 'nominate', 'add nominee'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'Voters',
                'subtitle' => 'Voter accounts · All / Pending / Verified / Flagged',
                'href' => '/voters',
                'keywords' => ['students', 'accounts', 'verify', 'ocr', 'fraud', 'risk', 'pending', 'verified', 'flagged', 'low', 'moderate', 'high', 'critical', 'tab'],
                'roles' => ['admin', 'committee'],
                'page_key' => 'voters',
            ],
            [
                'title' => 'Reactivation Requests',
                'subtitle' => 'Pending / Approved / Rejected reactivation reviews',
                'href' => '/reactivation-requests',
                'keywords' => ['reactivate', 'expired', 'renew', 'pending', 'approved', 'rejected', 'status filter'],
                'roles' => ['admin', 'committee'],
                'page_key' => 'reactivation',
            ],
            [
                'title' => 'Pending Reactivations',
                'subtitle' => 'Reactivation requests awaiting review',
                'href' => '/reactivation-requests?status=pending',
                'keywords' => ['pending reactivation', 'awaiting review'],
                'roles' => ['admin', 'committee'],
                'page_key' => 'reactivation',
            ],
            [
                'title' => 'Support',
                'subtitle' => 'Help desk tickets · Pending / Approved / Closed / Rejected',
                'href' => '/support',
                'keywords' => ['tickets', 'help', 'issues', 'messages', 'pending', 'approved', 'closed', 'rejected', 'registration', 'voting', 'account', 'technical', 'other', 'category'],
                'roles' => ['admin', 'committee'],
                'page_key' => 'support',
            ],
            [
                'title' => 'Pending Support Tickets',
                'subtitle' => 'Tickets waiting for admin action',
                'href' => '/support?status=pending',
                'keywords' => ['pending tickets', 'open tickets'],
                'roles' => ['admin', 'committee'],
                'page_key' => 'support',
            ],
            [
                'title' => 'Announcements',
                'subtitle' => 'Post and manage announcements',
                'href' => '/announcements/manage',
                'keywords' => ['news', 'notice', 'megaphone', 'links', 'images'],
                'roles' => ['admin', 'committee'],
                'page_key' => 'announcements',
            ],
            [
                'title' => 'Monitoring',
                'subtitle' => 'Live monitoring, analytics, results, participation',
                'href' => '/monitoring',
                'keywords' => ['live', 'presence', 'activity', 'analytics', 'final results', 'participation', 'turnout', 'leaders', 'winners', 'tab'],
                'roles' => ['admin', 'committee'],
                'page_key' => 'monitoring',
            ],
            [
                'title' => 'Reports',
                'subtitle' => 'Exports: results, tally, turnout, partylist, non-voters, receipts',
                'href' => '/reports',
                'keywords' => [
                    'export', 'pdf', 'excel', 'results', 'vote tally', 'turnout', 'participation',
                    'partylist performance', 'non-voters', 'students who voted', 'voters list',
                    'ballot receipt log', 'voter registration summary', 'candidate roster',
                    'official election results', 'department', 'year level', 'course', 'section',
                ],
                'roles' => ['admin', 'committee'],
                'page_key' => 'reports',
            ],
            [
                'title' => 'Disabled Accounts',
                'subtitle' => 'Voters disabled for missing the year level update deadline',
                'href' => '/disabled-accounts',
                'keywords' => ['disabled', 'year level appeal', 'missed deadline', 'restore account'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'Accounts',
                'subtitle' => 'Admin and committee accounts',
                'href' => '/accounts',
                'keywords' => ['users', 'committee', 'staff', 'administrators', 'admins tab', 'committee tab'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'Admin Accounts',
                'subtitle' => 'Manage administrator accounts',
                'href' => '/accounts',
                'keywords' => ['admins', 'administrator list'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'Committee Accounts',
                'subtitle' => 'Manage election committee accounts',
                'href' => '/accounts',
                'keywords' => ['committee members', 'committee list'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'Permissions',
                'subtitle' => 'Assign operational page access per committee account',
                'href' => '/permissions',
                'keywords' => ['access', 'committee pages', 'allowed pages', 'page permissions'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'Settings',
                'subtitle' => 'Departments, courses, year levels, positions, partylists',
                'href' => '/settings',
                'keywords' => ['config', 'preferences', 'academic', 'departments', 'courses', 'year levels', 'positions', 'partylists', 'dropdown lists'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'Range Limit Settings',
                'subtitle' => 'Campus geofence / location range',
                'href' => '/settings?advanced=rangeLimit',
                'keywords' => ['geofence', 'location', 'meters', 'range limit', 'map', 'latitude', 'longitude'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'D&TS Registration Settings',
                'subtitle' => 'Registration open/close window',
                'href' => '/settings?advanced=dtsRegistration',
                'keywords' => ['dts', 'registration window', 'registration schedule', 'open registration', 'close registration'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'School Year Settings',
                'subtitle' => 'School year start and end',
                'href' => '/settings?advanced=schoolYear',
                'keywords' => ['school year', 'sy', 'academic year'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'UA Management Settings',
                'subtitle' => 'Idle timeout, countdown, logout sound',
                'href' => '/settings?advanced=uaManagement',
                'keywords' => ['ua', 'idle', 'inactivity', 'countdown', 'auto logout', 'sound'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'SSC Members Settings',
                'subtitle' => 'SSC officer / member images',
                'href' => '/settings?advanced=sscMembers',
                'keywords' => ['ssc members', 'officers', 'member photos'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'Gallery Settings',
                'subtitle' => 'Welcome gallery · Dome / Circular',
                'href' => '/settings?advanced=gallery',
                'keywords' => ['gallery', 'dome', 'circular', 'carousel', 'images'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'System',
                'subtitle' => 'Presence and voting queue tools',
                'href' => '/system',
                'keywords' => ['maintenance', 'health', 'tools', 'online voters', 'jobs'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'System · Presence',
                'subtitle' => 'Online voter presence monitoring',
                'href' => '/system?tab=presence',
                'keywords' => ['presence tab', 'online', 'heartbeat', 'active sessions'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'System · Voting Queue',
                'subtitle' => 'Ballot submission queue and failures',
                'href' => '/system?tab=queue',
                'keywords' => ['queue tab', 'ballot queue', 'processing', 'failed ballots', 'jobs'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'Audit Logs',
                'subtitle' => 'Ballot receipts and activity history',
                'href' => '/audit-logs',
                'keywords' => ['history', 'security', 'trail', 'receipt', 'br-', 'ballot receipt'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'Registration Attempts',
                'subtitle' => 'Signup and OTP failure attempts',
                'href' => '/registration-attempts',
                'keywords' => ['signup', 'fraud', 'blocked', 'otp failed', 'ip address', 'device fingerprint'],
                'roles' => ['admin'],
            ],
            [
                'title' => 'Profile',
                'subtitle' => 'Your account profile, password, and photo',
                'href' => '/profile',
                'keywords' => ['account', 'password', 'photo', 'name', 'change password'],
                'roles' => ['admin', 'committee'],
            ],
        ];

        return collect($pages)
            ->filter(fn (array $page) => in_array($user->role, $page['roles'], true))
            ->filter(function (array $page) use ($user) {
                if ($user->role !== 'committee') {
                    return true;
                }

                $pageKey = $page['page_key'] ?? null;

                return $pageKey === null || $user->canAccessPage($pageKey);
            })
            ->filter(fn (array $page) => $this->matches(
                $query,
                $page['title'],
                $page['subtitle'],
                ...$page['keywords'],
            ))
            ->map(fn (array $page) => [
                'type' => 'page',
                'title' => $page['title'],
                'subtitle' => $page['subtitle'],
                'href' => $page['href'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchVoters(string $like): array
    {
        return User::query()
            ->where('role', 'voter')
            ->where(function ($builder) use ($like) {
                $builder->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('contact_email', 'like', $like)
                    ->orWhere('student_id_number', 'like', $like)
                    ->orWhere('voter_id_number', 'like', $like)
                    ->orWhere('ocr_name', 'like', $like)
                    ->orWhere('ocr_student_id', 'like', $like)
                    ->orWhere('ocr_course', 'like', $like)
                    ->orWhere('registration_status', 'like', $like)
                    ->orWhere('verification_status', 'like', $like);
            })
            ->with(['department:id,name', 'course:id,name'])
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (User $voter) => [
                'type' => 'voter',
                'title' => $voter->name,
                'subtitle' => collect([
                    $voter->voter_id_number,
                    $voter->student_id_number,
                    $voter->email,
                    $voter->department?->name,
                    $voter->course?->name,
                    $voter->registration_status,
                ])->filter()->implode(' · '),
                'href' => "/voters/{$voter->id}",
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchStaffAccounts(string $like): array
    {
        return User::query()
            ->whereIn('role', ['admin', 'committee'])
            ->where(function ($builder) use ($like) {
                $builder->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('contact_email', 'like', $like);
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'email', 'role'])
            ->map(fn (User $account) => [
                'type' => 'account',
                'title' => $account->name,
                'subtitle' => collect([
                    ucfirst($account->role),
                    $account->email,
                ])->filter()->implode(' · '),
                'href' => '/accounts',
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchCandidates(string $like, bool $admin): array
    {
        return Candidate::query()
            ->with([
                'election:id,title',
                'position:id,name',
                'department:id,name,acronym',
                'course:id,name',
                'partylist:id,name,acronym',
            ])
            ->where(function ($builder) use ($like) {
                $builder->where('name', 'like', $like)
                    ->orWhere('platform', 'like', $like)
                    ->orWhereHas('position', fn ($q) => $q->where('name', 'like', $like))
                    ->orWhereHas('partylist', function ($q) use ($like) {
                        $q->where('name', 'like', $like)->orWhere('acronym', 'like', $like);
                    })
                    ->orWhereHas('department', function ($q) use ($like) {
                        $q->where('name', 'like', $like)->orWhere('acronym', 'like', $like);
                    })
                    ->orWhereHas('course', fn ($q) => $q->where('name', 'like', $like))
                    ->orWhereHas('election', fn ($q) => $q->where('title', 'like', $like));
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Candidate $candidate) => [
                'type' => 'candidate',
                'title' => $candidate->name,
                'subtitle' => collect([
                    $candidate->position?->name,
                    $candidate->partylist?->acronym ?: $candidate->partylist?->name,
                    $candidate->department?->acronym ?: $candidate->department?->name,
                    $candidate->course?->name,
                    $candidate->election?->title,
                ])->filter()->implode(' · '),
                'href' => "/candidates/{$candidate->id}/edit",
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchElections(string $like, string $query): array
    {
        return Election::query()
            ->where(function ($builder) use ($like, $query) {
                $builder->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('status', 'like', $like);

                foreach (Election::STATUS_LABELS as $status => $label) {
                    if ($this->matches($query, $status, $label)) {
                        $builder->orWhere('status', $status);
                    }
                }
            })
            ->orderByDesc('created_at')
            ->limit(6)
            ->get(['id', 'title', 'status', 'description'])
            ->map(fn (Election $election) => [
                'type' => 'election',
                'title' => $election->title,
                'subtitle' => collect([
                    'Election',
                    Election::STATUS_LABELS[$election->status] ?? ucfirst($election->status),
                ])->implode(' · '),
                'href' => '/elections',
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchTickets(string $like): array
    {
        return SupportTicket::query()
            ->where(function ($builder) use ($like) {
                $builder->where('ticket_number', 'like', $like)
                    ->orWhere('subject', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhere('status', 'like', $like);
            })
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get(['id', 'ticket_number', 'subject', 'status', 'category'])
            ->map(fn (SupportTicket $ticket) => [
                'type' => 'ticket',
                'title' => $ticket->subject,
                'subtitle' => collect([
                    $ticket->ticket_number,
                    SupportTicket::CATEGORIES[$ticket->category] ?? $ticket->category,
                    ucfirst($ticket->status),
                ])->filter()->implode(' · '),
                'href' => "/support/tickets/{$ticket->id}",
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchTicketMessages(string $like): array
    {
        return SupportMessage::query()
            ->with('ticket:id,ticket_number,subject')
            ->where('body', 'like', $like)
            ->orderByDesc('created_at')
            ->limit(4)
            ->get()
            ->map(function (SupportMessage $message) {
                $ticket = $message->ticket;
                if (! $ticket) {
                    return null;
                }

                return [
                    'type' => 'ticket',
                    'title' => $ticket->subject,
                    'subtitle' => collect([
                        $ticket->ticket_number,
                        'Message match',
                    ])->implode(' · '),
                    'href' => "/support/tickets/{$ticket->id}",
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchAnnouncements(string $like): array
    {
        return Announcement::query()
            ->where(function ($builder) use ($like) {
                $builder->where('title', 'like', $like)
                    ->orWhere('body', 'like', $like);
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'title'])
            ->map(fn (Announcement $announcement) => [
                'type' => 'announcement',
                'title' => $announcement->title,
                'subtitle' => 'Announcement',
                'href' => '/announcements/manage',
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchReactivations(string $like): array
    {
        return ReactivationRequest::query()
            ->where(function ($builder) use ($like) {
                $builder->where('full_name', 'like', $like)
                    ->orWhere('voter_id_number', 'like', $like)
                    ->orWhere('reactivation_number', 'like', $like)
                    ->orWhere('reason', 'like', $like)
                    ->orWhere('year_stopped', 'like', $like)
                    ->orWhere('admin_notes', 'like', $like)
                    ->orWhere('status', 'like', $like);
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'full_name', 'voter_id_number', 'reactivation_number', 'status'])
            ->map(fn (ReactivationRequest $request) => [
                'type' => 'reactivation',
                'title' => $request->full_name,
                'subtitle' => collect([
                    $request->reactivation_number,
                    $request->voter_id_number,
                    ucfirst($request->status),
                ])->filter()->implode(' · '),
                'href' => '/reactivation-requests?status='.$request->status,
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchRegistrationAttempts(string $like): array
    {
        return RegistrationAttempt::query()
            ->with('user:id,name,email')
            ->where(function ($builder) use ($like) {
                $builder->where('ip_address', 'like', $like)
                    ->orWhere('device_fingerprint', 'like', $like)
                    ->orWhere('action', 'like', $like)
                    ->orWhereHas('user', function ($q) use ($like) {
                        $q->where('name', 'like', $like)->orWhere('email', 'like', $like);
                    });
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (RegistrationAttempt $attempt) => [
                'type' => 'registration',
                'title' => $attempt->user?->name ?: 'Registration attempt',
                'subtitle' => collect([
                    $attempt->action,
                    $attempt->ip_address,
                    $attempt->user?->email,
                ])->filter()->implode(' · '),
                'href' => '/registration-attempts',
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchBallotReceipts(string $like): array
    {
        return BallotReceipt::query()
            ->with(['user:id,name,voter_id_number', 'election:id,title'])
            ->where(function ($builder) use ($like) {
                $builder->where('receipt_number', 'like', $like)
                    ->orWhereHas('user', function ($q) use ($like) {
                        $q->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('voter_id_number', 'like', $like);
                    })
                    ->orWhereHas('election', fn ($q) => $q->where('title', 'like', $like));
            })
            ->orderByDesc('submitted_at')
            ->limit(5)
            ->get()
            ->map(fn (BallotReceipt $receipt) => [
                'type' => 'receipt',
                'title' => $receipt->receipt_number,
                'subtitle' => collect([
                    $receipt->user?->name,
                    $receipt->election?->title,
                ])->filter()->implode(' · '),
                'href' => '/audit-logs',
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchDepartments(string $like): array
    {
        return Department::query()
            ->where(function ($builder) use ($like) {
                $builder->where('name', 'like', $like)
                    ->orWhere('acronym', 'like', $like);
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'acronym'])
            ->map(fn (Department $department) => [
                'type' => 'setting',
                'title' => $department->name,
                'subtitle' => collect(['Department', $department->acronym])->filter()->implode(' · '),
                'href' => '/settings',
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchCourses(string $like): array
    {
        return Course::query()
            ->with('department:id,name,acronym')
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (Course $course) => [
                'type' => 'setting',
                'title' => $course->name,
                'subtitle' => collect([
                    'Course',
                    $course->department?->acronym ?: $course->department?->name,
                ])->filter()->implode(' · '),
                'href' => '/settings',
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchYearLevels(string $like): array
    {
        return YearLevel::query()
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name'])
            ->map(fn (YearLevel $yearLevel) => [
                'type' => 'setting',
                'title' => $yearLevel->name,
                'subtitle' => 'Year level · Settings',
                'href' => '/settings',
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchPositions(string $like): array
    {
        return Position::query()
            ->where('name', 'like', $like)
            ->orderBy('sort_order')
            ->limit(5)
            ->get(['id', 'name'])
            ->map(fn (Position $position) => [
                'type' => 'setting',
                'title' => $position->name,
                'subtitle' => 'Position · Settings / Candidates',
                'href' => '/settings',
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, title: string, subtitle: string, href: string}>
     */
    private function searchPartylists(string $like): array
    {
        return Partylist::query()
            ->where(function ($builder) use ($like) {
                $builder->where('name', 'like', $like)
                    ->orWhere('acronym', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'acronym'])
            ->map(fn (Partylist $partylist) => [
                'type' => 'setting',
                'title' => $partylist->name,
                'subtitle' => collect([
                    'Partylist',
                    $partylist->acronym,
                ])->filter()->implode(' · '),
                'href' => '/settings',
            ])
            ->all();
    }
}
