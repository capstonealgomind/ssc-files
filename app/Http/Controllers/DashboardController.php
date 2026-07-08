<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Candidate;
use App\Models\Election;
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
        $totalVotesCast = (int) Vote::query()
            ->selectRaw('COUNT(DISTINCT CONCAT(user_id, "-", election_id)) as total')
            ->value('total');

        $recentVotes = Vote::query()
            ->with(['user:id,voter_id_number', 'candidate:id,name', 'position:id,name', 'user.department:id,name'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Vote $v) => [
                'time'       => $v->created_at->format('g:i:s A'),
                'voter_id'   => $v->user?->voter_id_number ?? '—',
                'department' => $v->user?->department?->name ?? '—',
                'position'   => $v->position?->name ?? '—',
                'candidate'  => $v->candidate?->name ?? '—',
                'status'     => 'recorded',
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
        ];

        if ($user->role !== 'admin') {
            $payload = array_merge($payload, $this->voterDashboardData($user));
        } else {
            // Admin: standings elections (active + scheduled)
            $payload['elections'] = $this->formatStandingsElections(
                Election::query()
                    ->whereIn('status', [Election::STATUS_ACTIVE, Election::STATUS_SCHEDULED])
                    ->orderByDesc('voting_starts_at')
                    ->get()
            );
        }

        return Inertia::render('Dashboard', $payload);
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
