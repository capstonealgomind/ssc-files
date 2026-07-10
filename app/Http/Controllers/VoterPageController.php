<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\BallotReceipt;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VoterPageController extends Controller
{
    public function myVotes(Request $request): Response
    {
        $receiptsByElection = BallotReceipt::query()
            ->where('user_id', $request->user()->id)
            ->get()
            ->keyBy('election_id');

        $ballots = Vote::query()
            ->where('user_id', $request->user()->id)
            ->with(['election:id,title', 'candidate:id,name', 'position:id,name'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('election_id')
            ->map(function ($votes, $electionId) use ($receiptsByElection) {
                $receipt = $receiptsByElection->get($electionId);

                return [
                    'election_id'    => (int) $electionId,
                    'election_title' => $votes->first()->election?->title ?? 'Unknown Election',
                    'submitted_at'   => $votes->max('created_at')?->format('M d, Y g:i A'),
                    'receipt_id'     => $receipt?->id,
                    'receipt_number' => $receipt?->receipt_number,
                    'pdf_url'        => $receipt?->signedPdfDownloadUrl(),
                    'pdf_filename'   => $receipt
                        ? 'ballot-receipt-' . $receipt->receipt_number . '.pdf'
                        : null,
                    'votes'          => $votes->map(fn (Vote $v) => [
                        'position_id' => $v->position_id,
                        'position'    => $v->position?->name,
                        'candidate'   => $v->candidate?->name,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('MyVotes', [
            'ballots' => $ballots,
        ]);
    }

    public function results(Request $request): Response
    {
        $elections = Election::query()
            ->where('status', '!=', Election::STATUS_DRAFT)
            ->orderByDesc('voting_ends_at')
            ->get();

        $options = $elections->map(fn (Election $election) => [
            'value' => (string) $election->id,
            'label' => $election->title,
            'voting_phase' => $election->votingPhase(),
            'status_label' => $election->statusLabel(),
            'is_published' => $election->votingPhase() === 'closed',
        ])->values()->all();

        $selectedId = (int) ($request->query('election_id') ?: 0);
        $selected = $selectedId > 0
            ? $elections->firstWhere('id', $selectedId)
            : null;

        if (! $selected) {
            $selected = $elections->first(fn (Election $election) => $election->votingPhase() === 'closed')
                ?? $elections->first();
        }

        $payload = [
            'electionOptions' => $options,
            'selectedElectionId' => $selected?->id,
            'selectedElection' => null,
            'results' => null,
        ];

        if ($selected) {
            $payload['selectedElection'] = [
                'id' => $selected->id,
                'title' => $selected->title,
                'description' => $selected->description,
                'status_label' => $selected->statusLabel(),
                'voting_phase' => $selected->votingPhase(),
                'voting_period' => $this->formatPeriod($selected->voting_starts_at, $selected->voting_ends_at),
                'is_published' => $selected->votingPhase() === 'closed',
            ];

            if ($selected->votingPhase() === 'closed') {
                $payload['results'] = $this->buildPublishedResults($selected);
            }
        }

        return Inertia::render('Results', $payload);
    }

    public function announcements(): Response
    {
        return Inertia::render('Announcements', [
            'announcements' => Announcement::publicList(),
        ]);
    }

    public function help(): Response
    {
        return Inertia::render('HelpSupport');
    }

    public function faq(): Response
    {
        return Inertia::render('Faq');
    }

    private function buildPublishedResults(Election $election): array
    {
        $positions = $this->positionStandings($election);

        $eligibleVoters = User::query()
            ->where('role', 'voter')
            ->where('is_verified', true)
            ->count();

        $ballotsCast = (int) Vote::query()
            ->where('election_id', $election->id)
            ->distinct()
            ->count('user_id');

        $turnoutRate = $eligibleVoters > 0
            ? round(($ballotsCast / $eligibleVoters) * 100, 1)
            : 0.0;

        $winners = collect($positions)
            ->filter(fn (array $position) => ($position['total_votes'] ?? 0) > 0)
            ->map(function (array $position) {
                $winner = collect($position['candidates'])
                    ->first(fn (array $candidate) => ($candidate['show_trophy'] ?? false)
                        || (($candidate['is_leader'] ?? false) && ($candidate['votes'] ?? 0) > 0));

                if (! $winner) {
                    return null;
                }

                return [
                    'position_id' => $position['id'],
                    'position_name' => $position['name'],
                    'candidate_name' => $winner['name'],
                    'partylist_name' => $winner['partylist_name'],
                    'partylist_label' => $winner['partylist_label'],
                    'photo_url' => $winner['photo_url'],
                    'department_color_hex' => $winner['department_color_hex'],
                    'votes' => $winner['votes'],
                    'percentage' => $winner['percentage'],
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'summary' => [
                'positions' => count($positions),
                'candidates' => Candidate::query()->where('election_id', $election->id)->count(),
                'eligible_voters' => $eligibleVoters,
                'ballots_cast' => $ballotsCast,
                'turnout_rate' => $turnoutRate,
                'total_votes' => (int) collect($positions)->sum('total_votes'),
            ],
            'winners' => $winners,
            'positions' => $positions,
        ];
    }

    private function positionStandings(Election $election): array
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

    private function formatPeriod(?\DateTimeInterface $start, ?\DateTimeInterface $end): string
    {
        if (! $start || ! $end) {
            return '—';
        }

        return $start->format('M d, Y g:i A').' – '.$end->format('M d, Y g:i A');
    }
}
