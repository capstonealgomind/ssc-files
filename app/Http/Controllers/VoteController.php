<?php

namespace App\Http\Controllers;

use App\Models\BallotReceipt;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\Election;
use App\Models\Vote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VoteController extends Controller
{
    public function index(Request $request): Response
    {
        $voter = $request->user();

        if (!$voter->is_verified) {
            return Inertia::render('VoterElections', [
                'verified'  => false,
                'elections' => [],
            ]);
        }

        $userVotesByElection = Vote::query()
            ->where('user_id', $voter->id)
            ->with(['candidate:id,name', 'position:id,name'])
            ->get()
            ->groupBy('election_id');

        $receiptsByElection = BallotReceipt::query()
            ->where('user_id', $voter->id)
            ->get()
            ->keyBy('election_id');

        $elections = Election::query()
            ->whereIn('status', [Election::STATUS_ACTIVE, Election::STATUS_SCHEDULED])
            ->with([
                'candidates' => fn ($q) => $q
                    ->with(['position:id,name', 'department:id,name,acronym,color'])
                    ->orderBy('position_id'),
            ])
            ->orderByDesc('voting_starts_at')
            ->get()
            ->map(function (Election $e) use ($userVotesByElection, $receiptsByElection) {
                $positionIds = $e->candidates->pluck('position_id')->unique()->filter()->values();
                $userVotes = ($userVotesByElection->get($e->id) ?? collect())->values();
                $hasVoted = $positionIds->isNotEmpty()
                    && $userVotes->count() >= $positionIds->count();
                $receipt = $receiptsByElection->get($e->id);

                return [
                    'id'                => $e->id,
                    'title'             => $e->title,
                    'description'       => $e->description,
                    'status'            => $e->status,
                    'status_label'      => $e->statusLabel(),
                    'voting_period'            => $this->formatPeriod($e->voting_starts_at, $e->voting_ends_at),
                    'voting_starts_at_display' => $e->voting_starts_at?->format('M d, Y g:i A'),
                    'voting_ends_at_display'   => $e->voting_ends_at?->format('M d, Y g:i A'),
                    'voting_phase'      => $e->votingPhase(),
                    'can_vote'          => $e->isVotingOpen() && !$hasVoted,
                    'has_voted'         => $hasVoted,
                    'receipt_id'        => $receipt?->id,
                    'receipt_number'    => $receipt?->receipt_number,
                    'pdf_url'           => $receipt?->signedPdfDownloadUrl(),
                    'pdf_filename'      => $receipt
                        ? 'ballot-receipt-' . $receipt->receipt_number . '.pdf'
                        : null,
                    'user_votes'        => $userVotes->map(fn (Vote $v) => [
                        'position_id'   => $v->position_id,
                        'position'      => $v->position?->name,
                        'candidate_id'  => $v->candidate_id,
                        'candidate'     => $v->candidate?->name,
                    ])->values()->all(),
                    'candidates'        => $e->candidates->map(fn (Candidate $c) => [
                        'id'                   => $c->id,
                        'name'                 => $c->name,
                        'position_id'          => $c->position_id,
                        'position'             => $c->position?->name,
                        'department'           => $c->department?->name,
                        'department_acronym'   => $c->department?->acronym,
                        'department_color'     => $c->department?->color,
                        'department_color_hex' => Department::colorHex($c->department?->color),
                        'platform'             => $c->platform,
                        'photo_url'            => $c->photo_path
                            ? asset('storage/' . $c->photo_path)
                            : null,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('VoterElections', [
            'verified'  => true,
            'elections' => $elections,
        ]);
    }

    public function store(Request $request, Election $election): RedirectResponse
    {
        $voter = $request->user();

        if ($voter->role !== 'voter') {
            abort(403);
        }

        if (!$voter->is_verified) {
            throw ValidationException::withMessages([
                'ballot' => 'Your account must be verified before you can vote.',
            ]);
        }

        if (!$election->isVotingOpen()) {
            throw ValidationException::withMessages([
                'ballot' => 'Voting is not currently open for this election.',
            ]);
        }

        $validated = $request->validate([
            'selections'                 => ['required', 'array', 'min:1'],
            'selections.*.position_id'   => ['required', 'integer', 'exists:positions,id'],
            'selections.*.candidate_id' => ['required', 'integer', 'exists:candidates,id'],
        ]);

        $selections = collect($validated['selections']);

        $positionIds = $election->candidates()
            ->pluck('position_id')
            ->unique()
            ->filter()
            ->values();

        if ($positionIds->isEmpty()) {
            throw ValidationException::withMessages([
                'ballot' => 'This election has no positions available for voting.',
            ]);
        }

        if ($selections->count() !== $positionIds->count()) {
            throw ValidationException::withMessages([
                'ballot' => 'Please select a candidate for every position before submitting.',
            ]);
        }

        $selectedPositionIds = $selections->pluck('position_id')->unique();
        if ($selectedPositionIds->count() !== $positionIds->count()) {
            throw ValidationException::withMessages([
                'ballot' => 'You may only vote once per position.',
            ]);
        }

        if ($positionIds->diff($selectedPositionIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'ballot' => 'Please select a candidate for every position before submitting.',
            ]);
        }

        $existingVotes = Vote::query()
            ->where('user_id', $voter->id)
            ->where('election_id', $election->id)
            ->exists();

        if ($existingVotes) {
            throw ValidationException::withMessages([
                'ballot' => 'You have already submitted your ballot for this election.',
            ]);
        }

        $candidates = Candidate::query()
            ->where('election_id', $election->id)
            ->whereIn('id', $selections->pluck('candidate_id'))
            ->get()
            ->keyBy('id');

        foreach ($selections as $selection) {
            $candidate = $candidates->get($selection['candidate_id']);

            if (!$candidate || $candidate->position_id !== (int) $selection['position_id']) {
                throw ValidationException::withMessages([
                    'ballot' => 'One or more selected candidates are invalid for this election.',
                ]);
            }
        }

        $receipt = DB::transaction(function () use ($voter, $election, $selections) {
            foreach ($selections as $selection) {
                Vote::create([
                    'user_id'      => $voter->id,
                    'election_id'  => $election->id,
                    'candidate_id' => $selection['candidate_id'],
                    'position_id'  => $selection['position_id'],
                ]);
            }

            return BallotReceipt::create([
                'user_id'         => $voter->id,
                'election_id'     => $election->id,
                'receipt_number'  => BallotReceipt::generateReceiptNumber(),
                'submitted_at'    => now(),
            ]);
        });

        return redirect()
            ->route('ballot-receipt.show', $receipt)
            ->with('success', 'Your ballot has been submitted successfully.');
    }

    private function formatPeriod(?\DateTimeInterface $start, ?\DateTimeInterface $end): string
    {
        if (!$start || !$end) {
            return '—';
        }

        return $start->format('M d, Y g:i A') . ' – ' . $end->format('M d, Y g:i A');
    }
}
