<?php

namespace App\Jobs;

use App\Models\BallotReceipt;
use App\Models\BallotSubmission;
use App\Models\Vote;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessBallotSubmission implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [2, 5, 10, 20, 40];

    public function __construct(
        public int $submissionId,
    ) {}

    public function handle(): void
    {
        $submission = BallotSubmission::query()->find($this->submissionId);

        if (! $submission) {
            Log::warning("Ballot job: submission {$this->submissionId} not found");

            return;
        }

        if ($submission->isCompleted()) {
            return;
        }

        if ($submission->isFailed() && $this->attempts() === 1) {
            return;
        }

        $submission->update([
            'status' => BallotSubmission::STATUS_PROCESSING,
            'error_message' => null,
        ]);

        try {
            $receipt = DB::transaction(function () use ($submission) {
                $locked = BallotSubmission::query()
                    ->whereKey($submission->id)
                    ->lockForUpdate()
                    ->first();

                if (! $locked || $locked->isCompleted()) {
                    return $locked?->receipt;
                }

                $alreadyVoted = Vote::query()
                    ->where('user_id', $locked->user_id)
                    ->where('election_id', $locked->election_id)
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyVoted) {
                    $existingReceipt = BallotReceipt::query()
                        ->where('user_id', $locked->user_id)
                        ->where('election_id', $locked->election_id)
                        ->first();

                    $locked->update([
                        'status' => BallotSubmission::STATUS_COMPLETED,
                        'ballot_receipt_id' => $existingReceipt?->id,
                        'processed_at' => now(),
                        'error_message' => null,
                    ]);

                    return $existingReceipt;
                }

                foreach ($locked->selections as $selection) {
                    Vote::create([
                        'user_id' => $locked->user_id,
                        'election_id' => $locked->election_id,
                        'candidate_id' => $selection['candidate_id'],
                        'position_id' => $selection['position_id'],
                    ]);
                }

                $receipt = BallotReceipt::create([
                    'user_id' => $locked->user_id,
                    'election_id' => $locked->election_id,
                    'receipt_number' => BallotReceipt::generateReceiptNumber(),
                    'submitted_at' => now(),
                ]);

                $locked->update([
                    'status' => BallotSubmission::STATUS_COMPLETED,
                    'ballot_receipt_id' => $receipt->id,
                    'processed_at' => now(),
                    'error_message' => null,
                ]);

                return $receipt;
            });

            Log::info("Ballot submission {$this->submissionId} completed", [
                'receipt_id' => $receipt?->id,
            ]);
        } catch (Throwable $e) {
            Log::error("Ballot submission {$this->submissionId} failed: ".$e->getMessage());

            if ($this->attempts() >= $this->tries) {
                $submission->update([
                    'status' => BallotSubmission::STATUS_FAILED,
                    'error_message' => 'Unable to process your ballot. Please contact support if this continues.',
                    'processed_at' => now(),
                ]);

                return;
            }

            $submission->update([
                'status' => BallotSubmission::STATUS_PENDING,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $submission = BallotSubmission::query()->find($this->submissionId);

        if (! $submission || $submission->isCompleted()) {
            return;
        }

        $submission->update([
            'status' => BallotSubmission::STATUS_FAILED,
            'error_message' => 'Unable to process your ballot. Please contact support if this continues.',
            'processed_at' => now(),
        ]);
    }
}
