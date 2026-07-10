<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LiveStandingController extends Controller
{
    public function index(Request $request): Response
    {
        $elections = Election::query()
            ->whereIn('status', [Election::STATUS_ACTIVE, Election::STATUS_SCHEDULED, Election::STATUS_CLOSED])
            ->orderByDesc('voting_starts_at')
            ->get();

        $activeElections = $elections->filter(fn (Election $election) => $election->isVotingOpen())->values();
        $hasActiveElections = $activeElections->isNotEmpty();

        $selectedId = (int) ($request->query('election_id') ?: 0);
        $selected = null;

        if ($hasActiveElections) {
            $selected = $selectedId > 0
                ? $activeElections->firstWhere('id', $selectedId)
                : null;

            if (! $selected) {
                $selected = $activeElections->first();
            }
        }

        $electionOptions = $elections->map(function (Election $election) {
            $phase = $election->votingPhase();
            $ended = $phase === 'closed';
            $upcoming = $phase === 'upcoming';
            $disabled = $ended || $upcoming;

            $label = $election->title;
            if ($ended) {
                $label .= ' (ended)';
            } elseif ($upcoming) {
                $label .= ' (not started)';
            }

            return [
                'value' => (string) $election->id,
                'label' => $label,
                'disabled' => $disabled,
                'ended' => $ended,
            ];
        })->values()->all();

        $positions = $selected ? $this->buildPositionStandings($selected) : [];
        $participation = $selected ? $this->buildParticipation($selected) : null;

        return Inertia::render('LiveStanding', [
            'electionOptions' => $electionOptions,
            'hasActiveElections' => $hasActiveElections,
            'selectedElectionId' => $selected?->id,
            'selectedElection' => $selected ? [
                'id' => $selected->id,
                'title' => $selected->title,
                'status' => $selected->status,
                'status_label' => $selected->statusLabel(),
                'voting_phase' => $selected->votingPhase(),
                'voting_period' => $this->formatPeriod($selected->voting_starts_at, $selected->voting_ends_at),
                'is_voting_open' => $selected->isVotingOpen(),
            ] : null,
            'liveCountingActive' => $selected?->isVotingOpen() ?? false,
            'statusMessage' => $this->statusMessage($selected, $hasActiveElections),
            'positions' => $positions,
            'participation' => $participation,
            'updatedAt' => now()->format('g:i:s A'),
        ]);
    }

    private function statusMessage(?Election $election, bool $hasActiveElections): string
    {
        if (! $hasActiveElections) {
            return 'NO ACTIVE ELECTIONS';
        }

        if (! $election) {
            return 'NO ELECTION AVAILABLE';
        }

        if ($election->isVotingOpen()) {
            return 'LIVE STANDINGS';
        }

        if ($election->votingPhase() === 'upcoming') {
            return 'VOTING NOT YET OPEN';
        }

        return 'FINAL STANDINGS';
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
                        'id' => $candidate->id,
                        'name' => $candidate->name,
                        'partylist_label' => $candidate->partylist_id
                            ? ($candidate->partylist?->acronym ?: $candidate->partylist?->name)
                            : 'Independent',
                        'partylist_name' => $candidate->partylist_id
                            ? $candidate->partylist?->name
                            : 'Independent',
                        'photo_url' => $candidate->photo_path
                            ? asset('storage/'.$candidate->photo_path)
                            : null,
                        'department_color' => $candidate->department?->color,
                        'department_color_hex' => Department::colorHex($candidate->department?->color),
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
            'ballots_cast' => $ballotsCast,
            'pending_voters' => $pendingVoters,
            'turnout_rate' => $turnoutRate,
        ];
    }

    private function formatPeriod(?\DateTimeInterface $start, ?\DateTimeInterface $end): string
    {
        if (! $start || ! $end) {
            return '—';
        }

        return $start->format('M d, Y g:i A').' – '.$end->format('M d, Y g:i A');
    }
}
