<?php

namespace App\Http\Controllers;

use App\Models\BallotSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $logs = BallotSubmission::query()
            ->with([
                'user:id,name,email,voter_id_number',
                'election:id,title',
                'receipt:id,receipt_number,submitted_at',
            ])
            ->latest('queued_at')
            ->limit(200)
            ->get()
            ->map(fn (BallotSubmission $submission) => [
                'id' => $submission->id,
                'voter_name' => $submission->user?->name,
                'voter_email' => $submission->user?->email,
                'voter_id_number' => $submission->user?->voter_id_number,
                'election' => $submission->election?->title,
                'status' => $submission->status,
                'receipt_number' => $submission->receipt?->receipt_number,
                'queued_at' => $submission->queued_at?->format('M d, Y g:i A'),
                'processed_at' => $submission->processed_at?->format('M d, Y g:i A'),
                'error_message' => $submission->error_message,
            ])
            ->values()
            ->all();

        return Inertia::render('AuditLogs', [
            'logs' => $logs,
        ]);
    }
}
