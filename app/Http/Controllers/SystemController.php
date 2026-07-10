<?php

namespace App\Http\Controllers;

use App\Models\BallotSubmission;
use App\Services\VoterPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    public function index(Request $request, VoterPresenceService $presence): Response
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $range = $this->normalizeRange($request->query('range'));
        $tab = $this->normalizeTab($request->query('tab'));
        $metrics = $presence->metrics();
        $chart = $presence->chartSeries($range);
        $queue = $this->queueTraffic();

        return Inertia::render('System', [
            'tab' => $tab,
            'metrics' => $metrics,
            'chart' => $chart,
            'range' => $range,
            'queue' => $queue,
            'updatedAt' => now()->format('g:i:s A'),
        ]);
    }

    public function heartbeat(Request $request, VoterPresenceService $presence): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'voter') {
            return response()->json(['ok' => false], 403);
        }

        $validated = $request->validate([
            'device' => ['nullable', 'string', 'in:mobile,desktop'],
        ]);

        $presence->heartbeat($user, $validated['device'] ?? 'desktop');

        return response()->json(['ok' => true]);
    }

    private function queueTraffic(): array
    {
        $statusCounts = BallotSubmission::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pending = (int) ($statusCounts[BallotSubmission::STATUS_PENDING] ?? 0);
        $processing = (int) ($statusCounts[BallotSubmission::STATUS_PROCESSING] ?? 0);
        $completed = (int) ($statusCounts[BallotSubmission::STATUS_COMPLETED] ?? 0);
        $failed = (int) ($statusCounts[BallotSubmission::STATUS_FAILED] ?? 0);

        $queuedJobs = 0;
        $failedJobs = 0;

        if (DB::getSchemaBuilder()->hasTable('jobs')) {
            $queuedJobs = (int) DB::table('jobs')->count();
        }

        if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
            $failedJobs = (int) DB::table('failed_jobs')->count();
        }

        $recent = BallotSubmission::query()
            ->with([
                'user:id,name,email,voter_id_number',
                'election:id,title',
                'receipt:id,receipt_number',
            ])
            ->latest('queued_at')
            ->limit(10)
            ->get()
            ->map(fn (BallotSubmission $submission) => [
                'id' => $submission->id,
                'status' => $submission->status,
                'voter_name' => $submission->user?->name,
                'voter_id_number' => $submission->user?->voter_id_number,
                'election' => $submission->election?->title,
                'receipt_number' => $submission->receipt?->receipt_number,
                'error_message' => $submission->error_message,
                'queued_at' => $submission->queued_at?->format('M d, Y g:i:s A'),
                'processed_at' => $submission->processed_at?->format('M d, Y g:i:s A'),
                'wait_seconds' => $submission->queued_at && $submission->processed_at
                    ? max(0, $submission->queued_at->diffInSeconds($submission->processed_at))
                    : ($submission->queued_at && $submission->isPending()
                        ? max(0, $submission->queued_at->diffInSeconds(now()))
                        : null),
            ])
            ->values()
            ->all();

        $traffic = collect(range(11, 0))
            ->map(function (int $minutesAgo) {
                $from = now()->subMinutes($minutesAgo + 1);
                $to = now()->subMinutes($minutesAgo);

                $queued = BallotSubmission::query()
                    ->where('queued_at', '>=', $from)
                    ->where('queued_at', '<', $to)
                    ->count();

                $completed = BallotSubmission::query()
                    ->where('status', BallotSubmission::STATUS_COMPLETED)
                    ->where('processed_at', '>=', $from)
                    ->where('processed_at', '<', $to)
                    ->count();

                $failed = BallotSubmission::query()
                    ->where('status', BallotSubmission::STATUS_FAILED)
                    ->where('processed_at', '>=', $from)
                    ->where('processed_at', '<', $to)
                    ->count();

                return [
                    'label' => $to->format('g:i A'),
                    'queued' => $queued,
                    'completed' => $completed,
                    'failed' => $failed,
                ];
            })
            ->values()
            ->all();

        return [
            'pending' => $pending,
            'processing' => $processing,
            'completed' => $completed,
            'failed' => $failed,
            'in_flight' => $pending + $processing,
            'queued_jobs' => $queuedJobs,
            'ballot_jobs_waiting' => $this->ballotJobsWaitingCount(),
            'failed_jobs' => $failedJobs,
            'recent' => $recent,
            'traffic' => $traffic,
        ];
    }

    private function ballotJobsWaitingCount(): int
    {
        if (! DB::getSchemaBuilder()->hasTable('jobs')) {
            return 0;
        }

        return (int) DB::table('jobs')
            ->where('payload', 'like', '%ProcessBallotSubmission%')
            ->count();
    }

    private function normalizeRange(mixed $range): string
    {
        return in_array($range, ['1h', '24h', '7d'], true) ? $range : '24h';
    }

    private function normalizeTab(mixed $tab): string
    {
        return in_array($tab, ['presence', 'queue'], true) ? $tab : 'presence';
    }
}
