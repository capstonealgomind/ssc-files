<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\BallotReceipt;
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

    public function results(): Response
    {
        return Inertia::render('Results');
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
}
