<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessBallotSubmission;
use App\Models\BallotReceipt;
use App\Models\BallotSubmission;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\Election;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VoteController extends Controller
{
    public function index(Request $request): Response
    {
        $voter = $request->user();

        if (! $voter->is_verified) {
            return Inertia::render('VoterElections', [
                'verified' => false,
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

        $pendingSubmissions = BallotSubmission::query()
            ->where('user_id', $voter->id)
            ->whereIn('status', [
                BallotSubmission::STATUS_PENDING,
                BallotSubmission::STATUS_PROCESSING,
            ])
            ->pluck('id', 'election_id');

        $elections = Election::query()
            ->whereIn('status', [Election::STATUS_ACTIVE, Election::STATUS_SCHEDULED])
            ->with([
                'candidates' => fn ($q) => $q
                    ->with(['position:id,name', 'department:id,name,acronym,color', 'course:id,name', 'partylist:id,name,acronym'])
                    ->orderBy('position_id'),
            ])
            ->orderByDesc('voting_starts_at')
            ->get()
            ->map(function (Election $e) use ($userVotesByElection, $receiptsByElection, $pendingSubmissions) {
                $positionIds = $e->candidates->pluck('position_id')->unique()->filter()->values();
                $userVotes = ($userVotesByElection->get($e->id) ?? collect())->values();
                $hasVoted = $positionIds->isNotEmpty()
                    && $userVotes->count() >= $positionIds->count();
                $receipt = $receiptsByElection->get($e->id);
                $pendingSubmissionId = $pendingSubmissions->get($e->id);

                return [
                    'id' => $e->id,
                    'title' => $e->title,
                    'description' => $e->description,
                    'status' => $e->status,
                    'status_label' => $e->statusLabel(),
                    'voting_period' => $this->formatPeriod($e->voting_starts_at, $e->voting_ends_at),
                    'voting_starts_at_display' => $e->voting_starts_at?->format('M d, Y g:i A'),
                    'voting_ends_at_display' => $e->voting_ends_at?->format('M d, Y g:i A'),
                    'voting_phase' => $e->votingPhase(),
                    'can_vote' => $e->isVotingOpen() && ! $hasVoted && ! $pendingSubmissionId,
                    'has_voted' => $hasVoted,
                    'ballot_processing' => (bool) $pendingSubmissionId,
                    'ballot_submission_id' => $pendingSubmissionId,
                    'receipt_id' => $receipt?->id,
                    'receipt_number' => $receipt?->receipt_number,
                    'pdf_url' => $receipt?->signedPdfDownloadUrl(),
                    'pdf_filename' => $receipt
                        ? 'ballot-receipt-'.$receipt->receipt_number.'.pdf'
                        : null,
                    'user_votes' => $userVotes->map(fn (Vote $v) => [
                        'position_id' => $v->position_id,
                        'position' => $v->position?->name,
                        'candidate_id' => $v->candidate_id,
                        'candidate' => $v->candidate?->name,
                    ])->values()->all(),
                    'candidates' => $e->candidates->map(fn (Candidate $c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'position_id' => $c->position_id,
                        'position' => $c->position?->name,
                        'department' => $c->department?->name,
                        'department_acronym' => $c->department?->acronym,
                        'department_color' => $c->department?->color,
                        'department_color_hex' => Department::colorHex($c->department?->color),
                        'course' => $c->course?->name,
                        'partylist_id' => $c->partylist_id,
                        'partylist_label' => $c->partylist_id
                            ? ($c->partylist?->acronym ?: $c->partylist?->name)
                            : 'Independent',
                        'platform' => $c->platform,
                        'photo_url' => $c->photo_path
                            ? asset('storage/'.$c->photo_path)
                            : null,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('VoterElections', [
            'verified' => true,
            'elections' => $elections,
        ]);
    }

    public function store(Request $request, Election $election): RedirectResponse
    {
        $voter = $request->user();

        if ($voter->role !== 'voter') {
            abort(403);
        }

        if (! $voter->is_verified) {
            throw ValidationException::withMessages([
                'ballot' => 'Your account must be verified before you can vote.',
            ]);
        }

        if (! $election->isVotingOpen()) {
            throw ValidationException::withMessages([
                'ballot' => 'Voting is not currently open for this election.',
            ]);
        }

        $validated = $request->validate([
            'selections' => ['required', 'array', 'min:1'],
            'selections.*.position_id' => ['required', 'integer', 'exists:positions,id'],
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

        $existingSubmission = BallotSubmission::query()
            ->where('user_id', $voter->id)
            ->where('election_id', $election->id)
            ->first();

        if ($existingSubmission) {
            if ($existingSubmission->isCompleted() && $existingSubmission->ballot_receipt_id) {
                return redirect()
                    ->back()
                    ->with('success', 'Your ballot has already been submitted.')
                    ->with('ballot_receipt_id', $existingSubmission->ballot_receipt_id);
            }

            if ($existingSubmission->isPending()) {
                return redirect()
                    ->back()
                    ->with('success', 'Your ballot is already being processed.')
                    ->with('ballot_submission_id', $existingSubmission->id);
            }

            $existingSubmission->delete();
        }

        $candidates = Candidate::query()
            ->where('election_id', $election->id)
            ->whereIn('id', $selections->pluck('candidate_id'))
            ->get()
            ->keyBy('id');

        foreach ($selections as $selection) {
            $candidate = $candidates->get($selection['candidate_id']);

            if (! $candidate || $candidate->position_id !== (int) $selection['position_id']) {
                throw ValidationException::withMessages([
                    'ballot' => 'One or more selected candidates are invalid for this election.',
                ]);
            }
        }

        $normalizedSelections = $selections
            ->map(fn (array $selection) => [
                'position_id' => (int) $selection['position_id'],
                'candidate_id' => (int) $selection['candidate_id'],
            ])
            ->values()
            ->all();

        $submission = BallotSubmission::create([
            'user_id' => $voter->id,
            'election_id' => $election->id,
            'selections' => $normalizedSelections,
            'status' => BallotSubmission::STATUS_PENDING,
            'queued_at' => now(),
        ]);

        ProcessBallotSubmission::dispatch($submission->id);

        // Don't wait for the scheduler/queue:listen — drain this ballot right after the response.
        dispatch(function () {
            Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--max-jobs' => 5,
                '--max-time' => 25,
                '--tries' => 5,
                '--quiet' => true,
            ]);
        })->afterResponse();

        return redirect()
            ->back()
            ->with('success', 'Your ballot was queued and is being processed.')
            ->with('ballot_submission_id', $submission->id);
    }

    public function submissionStatus(Request $request, BallotSubmission $submission): JsonResponse
    {
        if ((int) $request->user()->id !== (int) $submission->user_id) {
            abort(403);
        }

        $submission->loadMissing('receipt:id,receipt_number');

        $inJobsQueue = $this->submissionIsInJobsTable($submission->id);
        $jobsWaiting = $this->queuedBallotJobsCount();

        $queuePhase = match (true) {
            $submission->isCompleted() => 'completed',
            $submission->isFailed() => 'failed',
            $submission->status === BallotSubmission::STATUS_PROCESSING => 'processing',
            $inJobsQueue => 'in_jobs_queue',
            default => 'pending',
        };

        $queueLabel = match ($queuePhase) {
            'completed' => 'Completed',
            'failed' => 'Failed',
            'processing' => 'Processing now',
            'in_jobs_queue' => 'In jobs queue (waiting for worker)',
            default => 'Pending (queued for processing)',
        };

        return response()->json([
            'id' => $submission->id,
            'status' => $submission->status,
            'queue_phase' => $queuePhase,
            'queue_label' => $queueLabel,
            'in_jobs_queue' => $inJobsQueue,
            'jobs_waiting' => $jobsWaiting,
            'ballot_receipt_id' => $submission->ballot_receipt_id,
            'receipt_number' => $submission->receipt?->receipt_number,
            'error_message' => $submission->error_message,
            'is_pending' => $submission->isPending(),
            'is_completed' => $submission->isCompleted(),
            'is_failed' => $submission->isFailed(),
        ]);
    }

    private function submissionIsInJobsTable(int $submissionId): bool
    {
        if (! DB::getSchemaBuilder()->hasTable('jobs')) {
            return false;
        }

        return DB::table('jobs')
            ->where('payload', 'like', '%ProcessBallotSubmission%')
            ->where(function ($query) use ($submissionId) {
                $query->where('payload', 'like', '%submissionId";i:'.$submissionId.';%')
                    ->orWhere('payload', 'like', '%"submissionId":'.$submissionId.'%');
            })
            ->exists();
    }

    private function queuedBallotJobsCount(): int
    {
        if (! DB::getSchemaBuilder()->hasTable('jobs')) {
            return 0;
        }

        return (int) DB::table('jobs')
            ->where('payload', 'like', '%ProcessBallotSubmission%')
            ->count();
    }

    private function formatPeriod(?\DateTimeInterface $start, ?\DateTimeInterface $end): string
    {
        if (! $start || ! $end) {
            return '—';
        }

        return $start->format('M d, Y g:i A').' – '.$end->format('M d, Y g:i A');
    }
}
