<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\Election;
use App\Models\Position;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        // Admin summary stats
        $totalVoters     = User::where('role', 'voter')->count();
        $verifiedVoters  = User::where('role', 'voter')->where('is_verified', true)->count();
        $pendingVoters   = $totalVoters - $verifiedVoters;
        $activeCount     = Election::where('status', Election::STATUS_ACTIVE)->count();
        $totalVotesCast = $this->countDistinctBallots(Vote::query());

        // Latest 10; frontend reverses so newest sits at bottom and older rows flow upward.
        $recentVotes = Vote::query()
            ->with([
                'user:id,voter_id_number,department_id',
                'user.department:id,name,acronym',
                'candidate:id,name',
                'position:id,name',
            ])
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Vote $v) => [
                'id'         => $v->id,
                'time'       => $v->created_at?->timezone(config('app.timezone'))->format('g:i:s A') ?? '—',
                'voter_id'   => $v->user?->voter_id_number ?? '—',
                'department' => $v->user?->department?->acronym
                    ?: ($v->user?->department?->name ?? '—'),
                'position'   => $v->position?->name ?? '—',
                'candidate'  => $v->candidate?->name ?? '—',
                'status'     => 'Recorded',
            ])
            ->values()
            ->all();

        $payload = [
            'stats' => [
                'total_voters'     => $totalVoters,
                'verified_voters'  => $verifiedVoters,
                'pending_voters'   => $pendingVoters,
                'active_elections' => $activeCount,
                'votes_cast'       => $totalVotesCast,
            ],
            'recent_votes' => $recentVotes,
            'is_admin'     => $user->role === 'admin',
            'is_committee' => $user->role === 'committee',
        ];

        if ($user->role === 'admin') {
            $payload = array_merge($payload, $this->adminDashboardAnalytics(
                $totalVoters,
                $verifiedVoters,
                $totalVotesCast,
            ));

            $payload['elections'] = $this->formatStandingsElections(
                Election::query()
                    ->whereIn('status', [Election::STATUS_ACTIVE, Election::STATUS_SCHEDULED])
                    ->orderByDesc('voting_starts_at')
                    ->get()
            );
        } elseif ($user->role === 'committee') {
            $candidates = Candidate::query()
                ->with([
                    'election:id,title,status',
                    'department:id,name,color',
                    'course:id,name',
                    'position:id,name,sort_order',
                    'partylist:id,name,acronym',
                ])
                ->join('positions', 'candidates.position_id', '=', 'positions.id')
                ->orderByDesc('candidates.created_at')
                ->orderBy('positions.sort_order')
                ->orderBy('candidates.name')
                ->select('candidates.*')
                ->get();

            $payload['candidate_count'] = $candidates->count();
            $payload['candidates'] = $candidates
                ->map(fn (Candidate $candidate) => [
                    'id'                   => $candidate->id,
                    'election_id'          => $candidate->election_id,
                    'position_id'          => $candidate->position_id,
                    'department_id'        => $candidate->department_id,
                    'name'                 => $candidate->name,
                    'position'             => $candidate->position?->name,
                    'election_title'       => $candidate->election?->title,
                    'election_status'      => $candidate->election?->status,
                    'department'           => $candidate->department?->name,
                    'department_name'      => $candidate->department?->name,
                    'department_acronym'   => $candidate->department?->acronym,
                    'department_color'     => $candidate->department?->color,
                    'department_color_hex' => Department::colorHex($candidate->department?->color),
                    'course'               => $candidate->course?->name,
                    'course_name'          => $candidate->course?->name,
                    'partylist_id'         => $candidate->partylist_id,
                    'partylist_label'      => $candidate->partylist_id
                        ? ($candidate->partylist?->acronym ?: $candidate->partylist?->name)
                        : 'Independent',
                    'platform'             => $candidate->platform,
                    'photo_url'            => $candidate->photo_path
                        ? asset('storage/'.$candidate->photo_path)
                        : null,
                    'created_at'           => $candidate->created_at?->format('M d, Y'),
                ])
                ->values()
                ->all();

            $payload['elections'] = Election::query()
                ->orderByDesc('voting_starts_at')
                ->get(['id', 'title', 'status', 'voting_starts_at', 'voting_ends_at'])
                ->map(fn (Election $election) => [
                    'id'           => $election->id,
                    'title'        => $election->title,
                    'status'       => $election->status,
                    'status_label' => $election->statusLabel(),
                ])
                ->values()
                ->all();

            $payload['position_options'] = Position::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Position $position) => [
                    'value' => (string) $position->id,
                    'label' => $position->name,
                ])
                ->values()
                ->all();

            $payload['departments'] = Department::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values()
                ->all();
        } else {
            $payload = array_merge($payload, $this->voterDashboardData($user));
        }

        return Inertia::render('Dashboard', $payload);
    }

    private function adminDashboardAnalytics(int $totalVoters, int $verifiedVoters, int $ballotsCast): array
    {
        $monthAgo = now()->copy()->subMonth();
        $twoMonthsAgo = now()->copy()->subMonths(2);

        $turnoutRate = $verifiedVoters > 0
            ? round(($ballotsCast / $verifiedVoters) * 100, 1)
            : 0.0;

        $totalPositionVotes = Vote::query()->count();
        $votesThisMonth = Vote::query()
            ->where('created_at', '>=', $monthAgo)
            ->count();
        $votesPrevMonth = Vote::query()
            ->whereBetween('created_at', [$twoMonthsAgo, $monthAgo])
            ->count();
        $votesMonthChange = $this->percentChange($votesThisMonth, $votesPrevMonth);

        $voterSparkline = $this->alignSparklineToValue(
            $this->weeklyCumulativeCounts(User::query()->where('role', 'voter'), 'created_at', 6),
            $totalVoters,
        );
        $ballotSparkline = $this->alignSparklineToValue(
            $this->weeklyDistinctBallotSparkline(6),
            $ballotsCast,
        );
        $turnoutSparkline = $this->alignSparklineToValue(
            $this->weeklyTurnoutSparkline(6),
            $turnoutRate,
        );

        $voterChange = $this->sparklineChange($voterSparkline);
        $ballotChange = $this->sparklineChange($ballotSparkline);
        $turnoutChange = $this->sparklineChange($turnoutSparkline);

        return [
            'admin_metrics' => [
                'total_voters' => [
                    'value'     => $totalVoters,
                    'subtitle'  => 'Registered voter accounts',
                    'change'    => $voterChange['label'],
                    'trend'     => $voterChange['trend'],
                    'sparkline' => $voterSparkline,
                ],
                'votes_cast' => [
                    'value'     => $ballotsCast,
                    'subtitle'  => 'Ballots submitted',
                    'change'    => $ballotChange['label'],
                    'trend'     => $ballotChange['trend'],
                    'sparkline' => $ballotSparkline,
                ],
                'turnout_rate' => [
                    'value'     => $turnoutRate,
                    'subtitle'  => 'Of verified voters',
                    'change'    => $turnoutChange['label'],
                    'trend'     => $turnoutChange['trend'],
                    'sparkline' => $turnoutSparkline,
                ],
            ],
            'votes_summary' => [
                'value'     => $totalPositionVotes,
                'subtitle'  => $votesMonthChange['label'].' from last month',
                'sparkline' => $this->alignSparklineToValue(
                    $this->weeklyCumulativeCounts(Vote::query(), 'created_at', 6),
                    $totalPositionVotes,
                ),
            ],
            'votes_by_department' => $this->votesByDepartment(),
            'ballots_over_time' => $this->dailyBallotSeries(7),
        ];
    }

    private function distinctBallotsCastBefore(\DateTimeInterface $before): int
    {
        return $this->countDistinctBallots(
            Vote::query()->where('created_at', '<', $before)
        );
    }

    private function distinctBallotsCastBetween(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return $this->countDistinctBallots(
            Vote::query()
                ->where('created_at', '>=', $from)
                ->where('created_at', '<', $to)
        );
    }

    /**
     * Count unique voter ballots (user_id + election_id) in a portable way.
     */
    private function countDistinctBallots($query): int
    {
        return $query
            ->clone()
            ->select('user_id', 'election_id')
            ->groupBy('user_id', 'election_id')
            ->get()
            ->count();
    }

    private function alignSparklineToValue(array $points, float|int $currentValue): array
    {
        if ($points === []) {
            return [(float) $currentValue];
        }

        $points[count($points) - 1] = (float) $currentValue;

        return $points;
    }

    private function sparklineChange(array $points): array
    {
        if (count($points) < 2) {
            return ['label' => '0%', 'trend' => 'up'];
        }

        // Compare start → end of the sparkline so the % matches the visible trend
        $current = (float) $points[count($points) - 1];
        $previous = (float) $points[0];

        return $this->percentChange($current, $previous);
    }

    private function percentChange(float|int $current, float|int $previous): array
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($current === $previous) {
            return ['label' => '0%', 'trend' => 'up'];
        }

        if ($previous === 0.0) {
            return [
                'label' => '100%',
                'trend' => $current > 0 ? 'up' : 'down',
            ];
        }

        $pct = round((($current - $previous) / abs($previous)) * 100, 1);

        return [
            'label' => abs($pct).'%',
            'trend' => $pct >= 0 ? 'up' : 'down',
        ];
    }

    private function weeklyCumulativeCounts($query, string $dateColumn, int $weeks): array
    {
        $points = [];
        $start = now()->startOfWeek()->subWeeks($weeks - 1);

        for ($i = 0; $i < $weeks; $i++) {
            $end = $start->copy()->addWeeks($i + 1);
            $points[] = (clone $query)->where($dateColumn, '<', $end)->count();
        }

        return $points;
    }

    private function weeklyDistinctBallotSparkline(int $weeks): array
    {
        $points = [];
        $start = now()->startOfWeek()->subWeeks($weeks - 1);

        for ($i = 0; $i < $weeks; $i++) {
            $end = $start->copy()->addWeeks($i + 1);
            $points[] = $this->distinctBallotsCastBefore($end);
        }

        return $points;
    }

    private function weeklyTurnoutSparkline(int $weeks): array
    {
        $points = [];
        $start = now()->startOfWeek()->subWeeks($weeks - 1);

        for ($i = 0; $i < $weeks; $i++) {
            $end = $start->copy()->addWeeks($i + 1);
            $verified = User::query()
                ->where('role', 'voter')
                ->where('is_verified', true)
                ->where('created_at', '<', $end)
                ->count();
            $ballots = $this->distinctBallotsCastBefore($end);
            $points[] = $verified > 0 ? round(($ballots / $verified) * 100, 1) : 0.0;
        }

        return $points;
    }

    private function votesByDepartment(): array
    {
        $votedByDepartment = Vote::query()
            ->join('users', 'votes.user_id', '=', 'users.id')
            ->where('users.role', 'voter')
            ->whereNotNull('users.department_id')
            ->select('users.department_id', 'votes.user_id', 'votes.election_id')
            ->groupBy('users.department_id', 'votes.user_id', 'votes.election_id')
            ->get()
            ->groupBy('department_id')
            ->map->count();

        $rows = Department::query()
            ->orderBy('name')
            ->get(['id', 'name', 'acronym'])
            ->map(fn (Department $department) => [
                'department' => $department->acronym ?: $department->name,
                'votes'      => (int) ($votedByDepartment[$department->id] ?? 0),
            ])
            ->filter(fn (array $row) => $row['votes'] > 0)
            ->values();

        if ($rows->isEmpty()) {
            return Department::query()
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name', 'acronym'])
                ->map(fn (Department $department) => [
                    'department' => $department->acronym ?: $department->name,
                    'votes'      => 0,
                ])
                ->values()
                ->all();
        }

        return $rows->all();
    }

    private function dailyBallotSeries(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $end = $day->copy()->addDay();
            $series[] = [
                'label'  => $day->format('D'),
                'voters' => $this->distinctBallotsCastBefore($end),
            ];
        }

        $todayStart = now()->startOfDay();
        $yesterdayStart = now()->subDay()->startOfDay();
        $todayCount = $this->distinctBallotsCastBetween($todayStart, now()->addSecond());
        $yesterdayCount = $this->distinctBallotsCastBetween($yesterdayStart, $todayStart);
        $change = $this->percentChange($todayCount, $yesterdayCount);

        return [
            'points'   => $series,
            'latest'   => $series[$days - 1]['voters'] ?? 0,
            'subtitle' => $change['label'].' from yesterday',
            'trend'    => $change['trend'],
        ];
    }

    private function voterDashboardData(User $user): array
    {
        $votedElectionIds = Vote::query()
            ->where('user_id', $user->id)
            ->distinct()
            ->pluck('election_id');

        $votesCast = $votedElectionIds->count();

        // Use voting dates, not just status — scheduled elections open when their period starts
        $activeElections = Election::query()
            ->votingOpen()
            ->orderBy('voting_ends_at')
            ->get();

        $upcomingElections = Election::query()
            ->eligibleForVoters()
            ->where('voting_starts_at', '>', now())
            ->orderBy('voting_starts_at')
            ->get();

        $completedElections = Election::query()
            ->where('status', '!=', Election::STATUS_DRAFT)
            ->where(function ($query) {
                $query->where('status', Election::STATUS_CLOSED)
                    ->orWhere('voting_ends_at', '<', now());
            })
            ->orderByDesc('voting_ends_at')
            ->limit(5)
            ->get();

        $activeCount    = $activeElections->count();
        $upcomingCount  = $upcomingElections->count();
        $completedCount = Election::query()
            ->where('status', '!=', Election::STATUS_DRAFT)
            ->where(function ($query) {
                $query->where('status', Election::STATUS_CLOSED)
                    ->orWhere('voting_ends_at', '<', now());
            })
            ->count();

        $activeNotVoted = $activeElections->filter(
            fn (Election $e) => !$votedElectionIds->contains($e->id)
        )->count();

        return [
            'voter_stats' => [
                'active_elections'    => $activeCount,
                'votes_cast'          => $votesCast,
                'upcoming_elections'  => $upcomingCount,
                'completed_elections' => $completedCount,
            ],
            'active_election_list' => $activeElections->map(fn (Election $e) => $this->formatElectionCard($e, $votedElectionIds))->values()->all(),
            'voting_status' => [
                ['label' => 'Voted', 'value' => $votesCast],
                ['label' => 'Not Voted', 'value' => $activeNotVoted],
                ['label' => 'Upcoming', 'value' => $upcomingCount],
            ],
            'announcements' => Announcement::publicList(5),
            'elections' => $this->formatStandingsElections(
                $activeElections->merge($upcomingElections)->sortByDesc('voting_starts_at')->values()
            ),
        ];
    }

    private function formatElectionCard(Election $election, $votedElectionIds = null): array
    {
        return [
            'id'              => $election->id,
            'title'           => $election->title,
            'description'     => $election->description,
            'status'          => $election->status,
            'status_label'    => $election->isVotingOpen() ? 'Voting Open' : $election->statusLabel(),
            'voting_phase'    => $election->votingPhase(),
            'voting_period'   => $this->formatPeriod($election->voting_starts_at, $election->voting_ends_at),
            'voting_starts_at'=> $election->voting_starts_at?->format('M d, Y g:i A'),
            'voting_ends_at'  => $election->voting_ends_at?->format('M d, Y g:i A'),
            'candidate_count' => $election->candidates()->count(),
            'has_voted'       => $votedElectionIds ? $votedElectionIds->contains($election->id) : false,
            'can_vote'        => $election->isVotingOpen() && ($votedElectionIds ? !$votedElectionIds->contains($election->id) : true),
        ];
    }

    private function formatStandingsElections($elections): array
    {
        $voteCounts = Vote::query()
            ->whereIn('election_id', $elections->pluck('id'))
            ->selectRaw('candidate_id, count(*) as total')
            ->groupBy('candidate_id')
            ->pluck('total', 'candidate_id');

        return $elections
            ->load([
                'candidates' => fn ($q) => $q
                    ->with(['position:id,name', 'department:id,name'])
                    ->orderBy('position_id'),
            ])
            ->map(fn (Election $e) => [
                'id'           => $e->id,
                'title'        => $e->title,
                'status'       => $e->status,
                'status_label' => $e->statusLabel(),
                'candidates'   => $e->candidates->map(fn (Candidate $c) => [
                    'id'         => $c->id,
                    'name'       => $c->name,
                    'position'   => $c->position?->name ?? 'Unassigned',
                    'department' => $c->department?->name,
                    'photo_url'  => $c->photo_path ? asset('storage/'.$c->photo_path) : null,
                    'votes'      => (int) ($voteCounts[$c->id] ?? 0),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function formatPeriod(?\DateTimeInterface $start, ?\DateTimeInterface $end): string
    {
        if (!$start || !$end) {
            return '—';
        }

        return $start->format('M d, Y g:i A').' – '.$end->format('M d, Y g:i A');
    }
}
