<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use App\Models\YearLevel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    public function index(Request $request): Response
    {
        $elections = Election::query()
            ->where('status', '!=', Election::STATUS_DRAFT)
            ->orderByDesc('voting_starts_at')
            ->get();

        $selectedId = (int) ($request->query('election_id') ?: 0);
        $selected = $selectedId > 0
            ? $elections->firstWhere('id', $selectedId)
            : null;

        if (!$selected) {
            $selected = $elections->first(fn (Election $election) => $election->isVotingOpen())
                ?? $elections->first();
        }

        $electionOptions = $elections->map(fn (Election $election) => [
            'value' => (string) $election->id,
            'label' => $election->title,
        ])->values()->all();

        $positions = $selected ? $this->buildPositionStandings($selected) : [];
        $participation = $selected ? $this->buildParticipation($selected) : null;
        $analytics = $selected ? $this->buildAnalytics($selected, $positions) : null;
        $finalResults = $selected ? $this->buildFinalResults($selected, $positions) : null;
        $turnoutByDepartment = $selected ? $this->buildTurnoutByDepartment($selected) : [];
        $turnoutByYearLevel = $selected ? $this->buildTurnoutByYearLevel($selected) : [];

        return Inertia::render('Monitoring', [
            'electionOptions'     => $electionOptions,
            'selectedElectionId'  => $selected?->id,
            'selectedElection'    => $selected ? $this->formatElectionSummary($selected) : null,
            'liveCountingActive'  => $selected?->isVotingOpen() ?? false,
            'statusMessage'       => $this->statusMessage($selected),
            'positions'           => $positions,
            'participation'       => $participation,
            'analytics'           => $analytics,
            'finalResults'        => $finalResults,
            'turnoutByDepartment' => $turnoutByDepartment,
            'turnoutByYearLevel'  => $turnoutByYearLevel,
        ]);
    }

    private function formatElectionSummary(Election $election): array
    {
        return [
            'id'               => $election->id,
            'title'            => $election->title,
            'status'           => $election->status,
            'status_label'     => $election->statusLabel(),
            'voting_phase'     => $election->votingPhase(),
            'voting_period'    => $this->formatPeriod($election->voting_starts_at, $election->voting_ends_at),
            'is_voting_open'   => $election->isVotingOpen(),
        ];
    }

    private function statusMessage(?Election $election): string
    {
        if (!$election) {
            return 'NO ELECTION SELECTED';
        }

        if ($election->isVotingOpen()) {
            return 'LIVE COUNTING ACTIVE';
        }

        if ($election->votingPhase() === 'upcoming') {
            return 'VOTING NOT YET OPEN';
        }

        return 'FINAL COUNT COMPLETE';
    }

    private function buildPositionStandings(Election $election): array
    {
        $voteCounts = Vote::query()
            ->where('election_id', $election->id)
            ->selectRaw('candidate_id, count(*) as total')
            ->groupBy('candidate_id')
            ->pluck('total', 'candidate_id');

        $candidates = Candidate::query()
            ->where('election_id', $election->id)
            ->with(['position:id,name,sort_order', 'partylist:id,name,acronym', 'department:id,name,color'])
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
                        'id'              => $candidate->id,
                        'name'            => $candidate->name,
                        'partylist_label' => $candidate->partylist_id
                            ? ($candidate->partylist?->acronym ?: $candidate->partylist?->name)
                            : 'Independent',
                        'partylist_name'  => $candidate->partylist_id
                            ? $candidate->partylist?->name
                            : 'Independent',
                        'photo_url'       => $candidate->photo_path
                            ? asset('storage/'.$candidate->photo_path)
                            : null,
                        'department_color'     => $candidate->department?->color,
                        'department_color_hex' => Department::colorHex($candidate->department?->color),
                        'votes'           => (int) ($voteCounts[$candidate->id] ?? 0),
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
                    $showTrophy = $isLeader && !$leaderShown;

                    if ($showTrophy) {
                        $leaderShown = true;
                    }

                    return [
                        ...$row,
                        'percentage'  => $percentage,
                        'is_leader'   => $isLeader,
                        'show_trophy' => $showTrophy,
                    ];
                });

                return [
                    'id'           => (int) $positionId,
                    'name'         => $positionName,
                    'sort_order'   => $sortOrder,
                    'total_votes'  => $totalVotes,
                    'candidates'   => $rows->values()->all(),
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    private function buildParticipation(Election $election): array
    {
        $eligibleVoters = User::query()
            ->where('role', 'voter')
            ->where('is_verified', true)
            ->count();

        $ballotsCast = (int) Vote::query()
            ->where('election_id', $election->id)
            ->distinct()
            ->count('user_id');

        $pendingVoters = max($eligibleVoters - $ballotsCast, 0);
        $turnoutRate = $eligibleVoters > 0
            ? round(($ballotsCast / $eligibleVoters) * 100, 1)
            : 0.0;

        return [
            'eligible_voters' => $eligibleVoters,
            'ballots_cast'    => $ballotsCast,
            'pending_voters'  => $pendingVoters,
            'turnout_rate'    => $turnoutRate,
        ];
    }

    private function buildAnalytics(Election $election, array $positions): array
    {
        $totalPositionVotes = collect($positions)->sum('total_votes');
        $positionCount = count($positions);
        $candidateCount = Candidate::query()->where('election_id', $election->id)->count();
        $avgVotesPerPosition = $positionCount > 0
            ? round($totalPositionVotes / $positionCount, 1)
            : 0.0;

        // Only count positions that have real votes — a 0-vote "first" candidate is not a leader.
        $leadingByParty = collect($positions)
            ->filter(fn (array $position) => ($position['total_votes'] ?? 0) > 0)
            ->map(function (array $position) {
                return collect($position['candidates'])
                    ->first(fn (array $candidate) => ($candidate['show_trophy'] ?? false)
                        || (($candidate['is_leader'] ?? false) && ($candidate['votes'] ?? 0) > 0));
            })
            ->filter()
            ->groupBy('partylist_label')
            ->map->count()
            ->sortDesc()
            ->all();

        return [
            'total_votes'           => $totalPositionVotes,
            'positions_tracked'     => $positionCount,
            'candidates_running'    => $candidateCount,
            'avg_votes_per_position'=> $avgVotesPerPosition,
            'leading_by_party'      => $leadingByParty,
        ];
    }

    private function buildTurnoutByDepartment(Election $election): array
    {
        $registeredByDepartment = User::query()
            ->where('role', 'voter')
            ->where('is_verified', true)
            ->whereNotNull('department_id')
            ->selectRaw('department_id, count(*) as total')
            ->groupBy('department_id')
            ->pluck('total', 'department_id');

        $votedUserIds = Vote::query()
            ->where('election_id', $election->id)
            ->distinct()
            ->pluck('user_id');

        $votedByDepartment = User::query()
            ->whereIn('id', $votedUserIds)
            ->where('role', 'voter')
            ->whereNotNull('department_id')
            ->selectRaw('department_id, count(*) as total')
            ->groupBy('department_id')
            ->pluck('total', 'department_id');

        return Department::query()
            ->orderBy('name')
            ->get(['id', 'name', 'acronym', 'color'])
            ->map(fn (Department $department) => [
                'label'            => $department->acronym ?: $department->name,
                'voted'            => (int) ($votedByDepartment[$department->id] ?? 0),
                'total_registered' => (int) ($registeredByDepartment[$department->id] ?? 0),
                'color'            => $department->color,
                'color_hex'        => Department::colorHex($department->color),
            ])
            ->values()
            ->all();
    }

    private function buildTurnoutByYearLevel(Election $election): array
    {
        $registeredByYearLevel = User::query()
            ->where('role', 'voter')
            ->where('is_verified', true)
            ->whereNotNull('year_level_id')
            ->selectRaw('year_level_id, count(*) as total')
            ->groupBy('year_level_id')
            ->pluck('total', 'year_level_id');

        $votedUserIds = Vote::query()
            ->where('election_id', $election->id)
            ->distinct()
            ->pluck('user_id');

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
            ->map(fn (YearLevel $yearLevel) => [
                'label'            => $yearLevel->name,
                'voted'            => (int) ($votedByYearLevel[$yearLevel->id] ?? 0),
                'total_registered' => (int) ($registeredByYearLevel[$yearLevel->id] ?? 0),
            ])
            ->values()
            ->all();
    }

    private function buildFinalResults(Election $election, array $positions): array
    {
        $winners = $election->votingPhase() === 'closed'
            ? collect($positions)
                ->filter(fn (array $position) => ($position['total_votes'] ?? 0) > 0)
                ->map(function (array $position) {
                    $winner = collect($position['candidates'])
                        ->first(fn (array $candidate) => ($candidate['show_trophy'] ?? false)
                            || (($candidate['is_leader'] ?? false) && ($candidate['votes'] ?? 0) > 0));

                    if (!$winner) {
                        return null;
                    }

                    return [
                        'position_id'          => $position['id'],
                        'position_name'        => $position['name'],
                        'candidate_name'       => $winner['name'],
                        'partylist_name'       => $winner['partylist_name'],
                        'photo_url'            => $winner['photo_url'] ?? null,
                        'department_color'     => $winner['department_color'] ?? null,
                        'department_color_hex' => $winner['department_color_hex'] ?? Department::colorHex(null),
                        'votes'                => $winner['votes'],
                    ];
                })
                ->filter()
                ->values()
                ->all()
            : [];

        $voteCounts = Vote::query()
            ->where('election_id', $election->id)
            ->selectRaw('candidate_id, count(*) as total')
            ->groupBy('candidate_id')
            ->pluck('total', 'candidate_id');

        $partylistPerformance = Candidate::query()
            ->where('election_id', $election->id)
            ->with(['position:id,name,sort_order', 'partylist:id,name'])
            ->get()
            ->map(fn (Candidate $candidate) => [
                'name'          => $candidate->name,
                'position'      => $candidate->position?->name ?? 'Unassigned',
                'position_sort' => $candidate->position?->sort_order ?? 999,
                'partylist_name'=> $candidate->partylist_id
                    ? $candidate->partylist?->name
                    : 'Independent',
                'photo_url'     => $candidate->photo_path
                    ? asset('storage/'.$candidate->photo_path)
                    : null,
                'votes'         => (int) ($voteCounts[$candidate->id] ?? 0),
            ])
            ->groupBy('partylist_name')
            ->map(function ($candidates, string $label) {
                $sorted = $candidates->sortByDesc('votes')->values();

                return [
                    'label'       => $label,
                    'total_votes' => (int) $sorted->sum('votes'),
                    'candidates'  => $sorted->map(fn (array $candidate) => [
                        'name'      => $candidate['name'],
                        'position'  => $candidate['position'],
                        'photo_url' => $candidate['photo_url'] ?? null,
                        'votes'     => $candidate['votes'],
                    ])->values()->all(),
                ];
            })
            ->sortByDesc('total_votes')
            ->values()
            ->all();

        return [
            'winners'               => $winners,
            'partylist_performance' => $partylistPerformance,
        ];
    }

    private function formatPeriod(?\DateTimeInterface $start, ?\DateTimeInterface $end): string
    {
        if (!$start || !$end) {
            return '—';
        }

        return $start->format('M d, Y g:i A').' – '.$end->format('M d, Y g:i A');
    }
}
