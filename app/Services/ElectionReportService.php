<?php

namespace App\Services;

use App\Models\BallotReceipt;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use App\Models\YearLevel;

class ElectionReportService
{
    public const TYPES = [
        'election_results' => [
            'label' => 'Official Election Results',
            'description' => 'Winners and full vote tallies per position with percentages.',
        ],
        'vote_tally' => [
            'label' => 'Vote Tally by Position',
            'description' => 'Detailed candidate vote counts, partylist, and share per position.',
        ],
        'turnout' => [
            'label' => 'Turnout & Participation',
            'description' => 'Eligible voters, ballots cast, and turnout by department and year level.',
        ],
        'partylist_performance' => [
            'label' => 'Partylist Performance',
            'description' => 'Votes and candidates grouped by partylist.',
        ],
        'non_voters' => [
            'label' => 'Non-Voters List',
            'description' => 'Verified voters who have not cast a ballot for this election.',
        ],
        'ballot_receipts' => [
            'label' => 'Ballot Receipt Log',
            'description' => 'Submitted ballot receipts with voter and timestamp details.',
        ],
        'voter_registration' => [
            'label' => 'Voter Registration Summary',
            'description' => 'Registered voters by department, verification status, and totals.',
        ],
        'candidate_roster' => [
            'label' => 'Candidate Roster',
            'description' => 'All candidates for the selected election with position and partylist.',
        ],
    ];

    public function electionOptions(): array
    {
        return Election::query()
            ->where('status', '!=', Election::STATUS_DRAFT)
            ->orderByDesc('voting_starts_at')
            ->get(['id', 'title', 'status', 'voting_starts_at', 'voting_ends_at'])
            ->map(fn (Election $election) => [
                'value' => (string) $election->id,
                'label' => $election->title,
                'status' => $election->status,
                'status_label' => $election->statusLabel(),
                'voting_phase' => $election->votingPhase(),
                'voting_period' => $this->formatPeriod($election->voting_starts_at, $election->voting_ends_at),
            ])
            ->values()
            ->all();
    }

    public function reportCatalog(): array
    {
        return collect(self::TYPES)
            ->map(fn (array $meta, string $key) => [
                'key' => $key,
                'label' => $meta['label'],
                'description' => $meta['description'],
            ])
            ->values()
            ->all();
    }

    public function build(Election $election, string $type, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        if (! array_key_exists($type, self::TYPES)) {
            abort(404, 'Unknown report type.');
        }

        [$from, $to] = $this->normalizeDateRange($dateFrom, $dateTo);
        $meta = self::TYPES[$type];
        $payload = match ($type) {
            'election_results' => $this->electionResults($election, $from, $to),
            'vote_tally' => $this->voteTally($election, $from, $to),
            'turnout' => $this->turnout($election, $from, $to),
            'partylist_performance' => $this->partylistPerformance($election, $from, $to),
            'non_voters' => $this->nonVoters($election, $from, $to),
            'ballot_receipts' => $this->ballotReceipts($election, $from, $to),
            'voter_registration' => $this->voterRegistration($from, $to),
            'candidate_roster' => $this->candidateRoster($election),
        };

        return [
            'type' => $type,
            'label' => $meta['label'],
            'description' => $meta['description'],
            'generated_at' => now()->format('M d, Y g:i A'),
            'date_from' => $from?->toDateString(),
            'date_to' => $to?->toDateString(),
            'election' => [
                'id' => $election->id,
                'title' => $election->title,
                'status' => $election->status,
                'status_label' => $election->statusLabel(),
                'voting_phase' => $election->votingPhase(),
                'voting_period' => $this->formatPeriod($election->voting_starts_at, $election->voting_ends_at),
            ],
            ...$payload,
        ];
    }

    public function preview(Election $election): array
    {
        $positions = $this->positionStandings($election);
        $participation = $this->participation($election);
        $candidates = Candidate::query()->where('election_id', $election->id)->count();
        $receipts = BallotReceipt::query()->where('election_id', $election->id)->count();
        $nonVoters = max($participation['eligible_voters'] - $participation['ballots_cast'], 0);
        $totalVotes = (int) collect($positions)->sum('total_votes');
        $registrationTotal = User::query()->where('role', 'voter')->count();

        $availability = [
            'election_results' => $totalVotes > 0 || $candidates > 0,
            'vote_tally' => $candidates > 0,
            'turnout' => $participation['eligible_voters'] > 0 || $participation['ballots_cast'] > 0,
            'partylist_performance' => $candidates > 0,
            'non_voters' => $nonVoters > 0,
            'ballot_receipts' => $receipts > 0,
            'voter_registration' => $registrationTotal > 0,
            'candidate_roster' => $candidates > 0,
        ];

        return [
            'election' => [
                'id' => $election->id,
                'title' => $election->title,
                'status_label' => $election->statusLabel(),
                'voting_phase' => $election->votingPhase(),
                'voting_period' => $this->formatPeriod($election->voting_starts_at, $election->voting_ends_at),
                'voting_starts_at' => $election->voting_starts_at?->toDateString(),
                'voting_ends_at' => $election->voting_ends_at?->toDateString(),
            ],
            'summary' => [
                'positions' => count($positions),
                'candidates' => $candidates,
                'eligible_voters' => $participation['eligible_voters'],
                'ballots_cast' => $participation['ballots_cast'],
                'turnout_rate' => $participation['turnout_rate'],
                'total_votes' => $totalVotes,
                'receipts' => $receipts,
                'non_voters' => $nonVoters,
            ],
            'availability' => $availability,
        ];
    }

    public function excelSheets(array $report): array
    {
        return match ($report['type']) {
            'election_results' => $this->sheetsElectionResults($report),
            'vote_tally' => $this->sheetsVoteTally($report),
            'turnout' => $this->sheetsTurnout($report),
            'partylist_performance' => $this->sheetsPartylist($report),
            'non_voters' => [[
                'name' => 'Non-Voters',
                'headers' => ['Voter ID', 'Name', 'Email', 'Department', 'Course', 'Year Level'],
                'rows' => collect($report['rows'])->map(fn (array $row) => [
                    $row['voter_id_number'],
                    $row['name'],
                    $row['email'],
                    $row['department'],
                    $row['course'],
                    $row['year_level'],
                ])->all(),
            ]],
            'ballot_receipts' => [[
                'name' => 'Ballot Receipts',
                'headers' => ['Receipt Number', 'Voter ID', 'Name', 'Department', 'Submitted At'],
                'rows' => collect($report['rows'])->map(fn (array $row) => [
                    $row['receipt_number'],
                    $row['voter_id_number'],
                    $row['name'],
                    $row['department'],
                    $row['submitted_at'],
                ])->all(),
            ]],
            'voter_registration' => [
                [
                    'name' => 'Summary',
                    'headers' => ['Metric', 'Value'],
                    'rows' => [
                        ['Total voters', $report['summary']['total_voters']],
                        ['Verified', $report['summary']['verified']],
                        ['Pending', $report['summary']['pending']],
                    ],
                ],
                [
                    'name' => 'By Department',
                    'headers' => ['Department', 'Total', 'Verified', 'Pending'],
                    'rows' => collect($report['by_department'])->map(fn (array $row) => [
                        $row['label'],
                        $row['total'],
                        $row['verified'],
                        $row['pending'],
                    ])->all(),
                ],
            ],
            'candidate_roster' => [[
                'name' => 'Candidates',
                'headers' => ['Name', 'Position', 'Partylist', 'Department', 'Course'],
                'rows' => collect($report['rows'])->map(fn (array $row) => [
                    $row['name'],
                    $row['position'],
                    $row['partylist'],
                    $row['department'],
                    $row['course'],
                ])->all(),
            ]],
            default => [[
                'name' => 'Report',
                'headers' => ['Info'],
                'rows' => [['No data']],
            ]],
        };
    }

    private function electionResults(Election $election, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $positions = $this->positionStandings($election, $from, $to);
        $winners = $election->votingPhase() === 'closed'
            ? collect($positions)
                ->filter(fn (array $position) => ($position['total_votes'] ?? 0) > 0)
                ->map(function (array $position) {
                    $winner = collect($position['candidates'])
                        ->first(fn (array $candidate) => ($candidate['show_trophy'] ?? false)
                            || (($candidate['is_leader'] ?? false) && ($candidate['votes'] ?? 0) > 0));

                    if (! $winner) {
                        return null;
                    }

                    return [
                        'position' => $position['name'],
                        'candidate' => $winner['name'],
                        'partylist' => $winner['partylist_label'],
                        'votes' => $winner['votes'],
                        'percentage' => $winner['percentage'],
                    ];
                })
                ->filter()
                ->values()
                ->all()
            : [];

        return [
            'voting_closed' => $election->votingPhase() === 'closed',
            'winners' => $winners,
            'positions' => $positions,
            'participation' => $this->participation($election, $from, $to),
        ];
    }

    private function voteTally(Election $election, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $positions = $this->positionStandings($election, $from, $to);

        $rows = collect($positions)->flatMap(function (array $position) {
            return collect($position['candidates'])->map(fn (array $candidate) => [
                'position' => $position['name'],
                'candidate' => $candidate['name'],
                'partylist' => $candidate['partylist_label'],
                'votes' => $candidate['votes'],
                'percentage' => $candidate['percentage'],
                'is_leader' => $candidate['is_leader'],
            ]);
        })->values()->all();

        return [
            'positions' => $positions,
            'rows' => $rows,
        ];
    }

    private function turnout(Election $election, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        return [
            'participation' => $this->participation($election, $from, $to),
            'by_department' => $this->turnoutByDepartment($election, $from, $to),
            'by_year_level' => $this->turnoutByYearLevel($election, $from, $to),
        ];
    }

    private function partylistPerformance(Election $election, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $voteQuery = Vote::query()->where('election_id', $election->id);
        $this->applyDateRange($voteQuery, 'created_at', $from, $to);

        $voteCounts = $voteQuery
            ->selectRaw('candidate_id, count(*) as total')
            ->groupBy('candidate_id')
            ->pluck('total', 'candidate_id');

        $groups = Candidate::query()
            ->where('election_id', $election->id)
            ->with(['position:id,name,sort_order', 'partylist:id,name,acronym'])
            ->get()
            ->map(fn (Candidate $candidate) => [
                'name' => $candidate->name,
                'position' => $candidate->position?->name ?? 'Unassigned',
                'partylist' => $candidate->partylist_id
                    ? ($candidate->partylist?->acronym ?: $candidate->partylist?->name)
                    : 'Independent',
                'votes' => (int) ($voteCounts[$candidate->id] ?? 0),
            ])
            ->groupBy('partylist')
            ->map(function ($candidates, string $label) {
                $sorted = $candidates->sortByDesc('votes')->values();

                return [
                    'label' => $label,
                    'total_votes' => (int) $sorted->sum('votes'),
                    'candidate_count' => $sorted->count(),
                    'candidates' => $sorted->values()->all(),
                ];
            })
            ->sortByDesc('total_votes')
            ->values()
            ->all();

        return ['groups' => $groups];
    }

    private function nonVoters(Election $election, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $votedQuery = Vote::query()->where('election_id', $election->id);
        $this->applyDateRange($votedQuery, 'created_at', $from, $to);
        $votedUserIds = $votedQuery->distinct()->pluck('user_id');

        $rows = User::query()
            ->where('role', 'voter')
            ->where('is_verified', true)
            ->whereNotIn('id', $votedUserIds)
            ->with(['department:id,name,acronym', 'course:id,name', 'yearLevel:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'voter_id_number' => $user->voter_id_number ?? '—',
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department?->acronym ?: ($user->department?->name ?? '—'),
                'course' => $user->course?->name ?? '—',
                'year_level' => $user->yearLevel?->name ?? '—',
            ])
            ->values()
            ->all();

        return [
            'count' => count($rows),
            'rows' => $rows,
        ];
    }

    private function ballotReceipts(Election $election, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $query = BallotReceipt::query()->where('election_id', $election->id);
        $this->applyDateRange($query, 'submitted_at', $from, $to);

        $rows = $query
            ->with(['user:id,name,voter_id_number,department_id', 'user.department:id,name,acronym'])
            ->orderByDesc('submitted_at')
            ->get()
            ->map(fn (BallotReceipt $receipt) => [
                'receipt_number' => $receipt->receipt_number,
                'voter_id_number' => $receipt->user?->voter_id_number ?? '—',
                'name' => $receipt->user?->name ?? '—',
                'department' => $receipt->user?->department?->acronym
                    ?: ($receipt->user?->department?->name ?? '—'),
                'submitted_at' => $receipt->submitted_at?->format('M d, Y g:i A') ?? '—',
            ])
            ->values()
            ->all();

        return [
            'count' => count($rows),
            'rows' => $rows,
        ];
    }

    private function voterRegistration(?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $query = User::query()->where('role', 'voter');
        $this->applyDateRange($query, 'created_at', $from, $to);

        $voters = $query
            ->with('department:id,name,acronym')
            ->get(['id', 'department_id', 'is_verified']);

        $total = $voters->count();
        $verified = $voters->where('is_verified', true)->count();

        $byDepartment = Department::query()
            ->orderBy('name')
            ->get(['id', 'name', 'acronym'])
            ->map(function (Department $department) use ($voters) {
                $deptVoters = $voters->where('department_id', $department->id);
                $deptVerified = $deptVoters->where('is_verified', true)->count();
                $deptTotal = $deptVoters->count();

                return [
                    'label' => $department->acronym ?: $department->name,
                    'total' => $deptTotal,
                    'verified' => $deptVerified,
                    'pending' => max($deptTotal - $deptVerified, 0),
                ];
            })
            ->filter(fn (array $row) => $row['total'] > 0)
            ->values()
            ->all();

        return [
            'summary' => [
                'total_voters' => $total,
                'verified' => $verified,
                'pending' => max($total - $verified, 0),
            ],
            'by_department' => $byDepartment,
        ];
    }

    private function candidateRoster(Election $election): array
    {
        $rows = Candidate::query()
            ->where('election_id', $election->id)
            ->with([
                'position:id,name,sort_order',
                'partylist:id,name,acronym',
                'department:id,name,acronym',
                'course:id,name',
            ])
            ->get()
            ->sortBy([
                fn (Candidate $c) => $c->position?->sort_order ?? 999,
                fn (Candidate $c) => $c->name,
            ])
            ->values()
            ->map(fn (Candidate $candidate) => [
                'name' => $candidate->name,
                'position' => $candidate->position?->name ?? 'Unassigned',
                'partylist' => $candidate->partylist_id
                    ? ($candidate->partylist?->acronym ?: $candidate->partylist?->name)
                    : 'Independent',
                'department' => $candidate->department?->acronym
                    ?: ($candidate->department?->name ?? '—'),
                'course' => $candidate->course?->name ?? '—',
            ])
            ->all();

        return [
            'count' => count($rows),
            'rows' => $rows,
        ];
    }

    private function positionStandings(Election $election, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $voteQuery = Vote::query()->where('election_id', $election->id);
        $this->applyDateRange($voteQuery, 'created_at', $from, $to);

        $voteCounts = $voteQuery
            ->selectRaw('candidate_id, count(*) as total')
            ->groupBy('candidate_id')
            ->pluck('total', 'candidate_id');

        $candidates = Candidate::query()
            ->where('election_id', $election->id)
            ->with(['position:id,name,sort_order', 'partylist:id,name,acronym'])
            ->get()
            ->groupBy('position_id');

        if ($candidates->isEmpty()) {
            return [];
        }

        return $candidates
            ->map(function ($positionCandidates, $positionId) use ($voteCounts) {
                $position = $positionCandidates->first()->position;
                $positionName = $position?->name ?? 'Unassigned';
                $sortOrder = $position?->sort_order ?? 999;

                $rows = $positionCandidates
                    ->map(fn (Candidate $candidate) => [
                        'id' => $candidate->id,
                        'name' => $candidate->name,
                        'partylist_label' => $candidate->partylist_id
                            ? ($candidate->partylist?->acronym ?: $candidate->partylist?->name)
                            : 'Independent',
                        'votes' => (int) ($voteCounts[$candidate->id] ?? 0),
                    ])
                    ->sortByDesc('votes')
                    ->values();

                $totalVotes = (int) $rows->sum('votes');
                $maxVotes = (int) ($rows->max('votes') ?? 0);
                $leaderShown = false;

                $rows = $rows->map(function (array $row) use ($totalVotes, $maxVotes, &$leaderShown) {
                    $percentage = $totalVotes > 0
                        ? round(($row['votes'] / $totalVotes) * 100, 1)
                        : 0.0;

                    $isLeader = $maxVotes > 0 && $row['votes'] === $maxVotes;
                    $showTrophy = $isLeader && ! $leaderShown;

                    if ($showTrophy) {
                        $leaderShown = true;
                    }

                    return [
                        ...$row,
                        'percentage' => $percentage,
                        'is_leader' => $isLeader,
                        'show_trophy' => $showTrophy,
                    ];
                });

                return [
                    'id' => (int) $positionId,
                    'name' => $positionName,
                    'sort_order' => $sortOrder,
                    'total_votes' => $totalVotes,
                    'candidates' => $rows->values()->all(),
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    private function participation(Election $election, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $eligibleVoters = User::query()
            ->where('role', 'voter')
            ->where('is_verified', true)
            ->count();

        $ballotsQuery = Vote::query()->where('election_id', $election->id);
        $this->applyDateRange($ballotsQuery, 'created_at', $from, $to);
        $ballotsCast = (int) $ballotsQuery->distinct()->count('user_id');

        $pendingVoters = max($eligibleVoters - $ballotsCast, 0);
        $turnoutRate = $eligibleVoters > 0
            ? round(($ballotsCast / $eligibleVoters) * 100, 1)
            : 0.0;

        return [
            'eligible_voters' => $eligibleVoters,
            'ballots_cast' => $ballotsCast,
            'pending_voters' => $pendingVoters,
            'turnout_rate' => $turnoutRate,
        ];
    }

    private function turnoutByDepartment(Election $election, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $registeredByDepartment = User::query()
            ->where('role', 'voter')
            ->where('is_verified', true)
            ->whereNotNull('department_id')
            ->selectRaw('department_id, count(*) as total')
            ->groupBy('department_id')
            ->pluck('total', 'department_id');

        $votedQuery = Vote::query()->where('election_id', $election->id);
        $this->applyDateRange($votedQuery, 'created_at', $from, $to);
        $votedUserIds = $votedQuery->distinct()->pluck('user_id');

        $votedByDepartment = User::query()
            ->whereIn('id', $votedUserIds)
            ->where('role', 'voter')
            ->whereNotNull('department_id')
            ->selectRaw('department_id, count(*) as total')
            ->groupBy('department_id')
            ->pluck('total', 'department_id');

        return Department::query()
            ->orderBy('name')
            ->get(['id', 'name', 'acronym'])
            ->map(function (Department $department) use ($registeredByDepartment, $votedByDepartment) {
                $registered = (int) ($registeredByDepartment[$department->id] ?? 0);
                $voted = (int) ($votedByDepartment[$department->id] ?? 0);

                return [
                    'label' => $department->acronym ?: $department->name,
                    'voted' => $voted,
                    'total_registered' => $registered,
                    'turnout_rate' => $registered > 0
                        ? round(($voted / $registered) * 100, 1)
                        : 0.0,
                ];
            })
            ->filter(fn (array $row) => $row['total_registered'] > 0 || $row['voted'] > 0)
            ->values()
            ->all();
    }

    private function turnoutByYearLevel(Election $election, ?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        $registeredByYearLevel = User::query()
            ->where('role', 'voter')
            ->where('is_verified', true)
            ->whereNotNull('year_level_id')
            ->selectRaw('year_level_id, count(*) as total')
            ->groupBy('year_level_id')
            ->pluck('total', 'year_level_id');

        $votedQuery = Vote::query()->where('election_id', $election->id);
        $this->applyDateRange($votedQuery, 'created_at', $from, $to);
        $votedUserIds = $votedQuery->distinct()->pluck('user_id');

        $votedByYearLevel = User::query()
            ->whereIn('id', $votedUserIds)
            ->where('role', 'voter')
            ->whereNotNull('year_level_id')
            ->selectRaw('year_level_id, count(*) as total')
            ->groupBy('year_level_id')
            ->pluck('total', 'year_level_id');

        return YearLevel::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (YearLevel $yearLevel) use ($registeredByYearLevel, $votedByYearLevel) {
                $registered = (int) ($registeredByYearLevel[$yearLevel->id] ?? 0);
                $voted = (int) ($votedByYearLevel[$yearLevel->id] ?? 0);

                return [
                    'label' => $yearLevel->name,
                    'voted' => $voted,
                    'total_registered' => $registered,
                    'turnout_rate' => $registered > 0
                        ? round(($voted / $registered) * 100, 1)
                        : 0.0,
                ];
            })
            ->values()
            ->all();
    }

    private function sheetsElectionResults(array $report): array
    {
        $winnerRows = collect($report['winners'])->map(fn (array $row) => [
            $row['position'],
            $row['candidate'],
            $row['partylist'],
            $row['votes'],
            $row['percentage'].'%',
        ])->all();

        $tallyRows = collect($report['positions'])->flatMap(function (array $position) {
            return collect($position['candidates'])->map(fn (array $candidate) => [
                $position['name'],
                $candidate['name'],
                $candidate['partylist_label'],
                $candidate['votes'],
                $candidate['percentage'].'%',
            ]);
        })->all();

        return [
            [
                'name' => 'Winners',
                'headers' => ['Position', 'Winner', 'Partylist', 'Votes', 'Share'],
                'rows' => $winnerRows ?: [['—', 'Voting not closed / no winners', '—', 0, '0%']],
            ],
            [
                'name' => 'Full Tally',
                'headers' => ['Position', 'Candidate', 'Partylist', 'Votes', 'Share'],
                'rows' => $tallyRows,
            ],
            [
                'name' => 'Participation',
                'headers' => ['Metric', 'Value'],
                'rows' => [
                    ['Eligible voters', $report['participation']['eligible_voters']],
                    ['Ballots cast', $report['participation']['ballots_cast']],
                    ['Pending voters', $report['participation']['pending_voters']],
                    ['Turnout rate', $report['participation']['turnout_rate'].'%'],
                ],
            ],
        ];
    }

    private function sheetsVoteTally(array $report): array
    {
        return [[
            'name' => 'Vote Tally',
            'headers' => ['Position', 'Candidate', 'Partylist', 'Votes', 'Share', 'Leader'],
            'rows' => collect($report['rows'])->map(fn (array $row) => [
                $row['position'],
                $row['candidate'],
                $row['partylist'],
                $row['votes'],
                $row['percentage'].'%',
                $row['is_leader'] ? 'Yes' : 'No',
            ])->all(),
        ]];
    }

    private function sheetsTurnout(array $report): array
    {
        return [
            [
                'name' => 'Summary',
                'headers' => ['Metric', 'Value'],
                'rows' => [
                    ['Eligible voters', $report['participation']['eligible_voters']],
                    ['Ballots cast', $report['participation']['ballots_cast']],
                    ['Pending voters', $report['participation']['pending_voters']],
                    ['Turnout rate', $report['participation']['turnout_rate'].'%'],
                ],
            ],
            [
                'name' => 'By Department',
                'headers' => ['Department', 'Voted', 'Registered', 'Turnout'],
                'rows' => collect($report['by_department'])->map(fn (array $row) => [
                    $row['label'],
                    $row['voted'],
                    $row['total_registered'],
                    $row['turnout_rate'].'%',
                ])->all(),
            ],
            [
                'name' => 'By Year Level',
                'headers' => ['Year Level', 'Voted', 'Registered', 'Turnout'],
                'rows' => collect($report['by_year_level'])->map(fn (array $row) => [
                    $row['label'],
                    $row['voted'],
                    $row['total_registered'],
                    $row['turnout_rate'].'%',
                ])->all(),
            ],
        ];
    }

    private function sheetsPartylist(array $report): array
    {
        $summary = collect($report['groups'])->map(fn (array $group) => [
            $group['label'],
            $group['total_votes'],
            $group['candidate_count'],
        ])->all();

        $detail = collect($report['groups'])->flatMap(function (array $group) {
            return collect($group['candidates'])->map(fn (array $candidate) => [
                $group['label'],
                $candidate['name'],
                $candidate['position'],
                $candidate['votes'],
            ]);
        })->all();

        return [
            [
                'name' => 'Summary',
                'headers' => ['Partylist', 'Total Votes', 'Candidates'],
                'rows' => $summary,
            ],
            [
                'name' => 'Candidates',
                'headers' => ['Partylist', 'Candidate', 'Position', 'Votes'],
                'rows' => $detail,
            ],
        ];
    }

    private function normalizeDateRange(?string $dateFrom, ?string $dateTo): array
    {
        $from = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->startOfDay() : null;
        $to = $dateTo ? \Carbon\Carbon::parse($dateTo)->endOfDay() : null;

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function applyDateRange($query, string $column, ?\Carbon\Carbon $from, ?\Carbon\Carbon $to): void
    {
        if ($from) {
            $query->where($column, '>=', $from);
        }

        if ($to) {
            $query->where($column, '<=', $to);
        }
    }

    private function formatPeriod(?\DateTimeInterface $start, ?\DateTimeInterface $end): string
    {
        if (! $start || ! $end) {
            return '—';
        }

        return $start->format('M d, Y g:i A').' – '.$end->format('M d, Y g:i A');
    }
}
