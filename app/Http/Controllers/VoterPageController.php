<?php

namespace App\Http\Controllers;

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
            'announcements' => [
                [
                    'id'      => 1,
                    'title'   => 'Voting Period Now Open',
                    'date'    => now()->format('M d, Y'),
                    'excerpt' => 'The student council general election is now open. Verified voters may cast their ballots until the voting deadline.',
                ],
                [
                    'id'      => 2,
                    'title'   => 'Account Verification Reminder',
                    'date'    => now()->subDay()->format('M d, Y'),
                    'excerpt' => 'Only verified voter accounts can access the Elections page and submit ballots. Please wait for admin approval if your status is pending.',
                ],
                [
                    'id'      => 3,
                    'title'   => 'Election Guidelines',
                    'date'    => now()->subDays(3)->format('M d, Y'),
                    'excerpt' => 'Each verified voter may vote once per position. Review candidate platforms on the Elections page before submitting your ballot.',
                ],
            ],
        ]);
    }

    public function help(): Response
    {
        return Inertia::render('HelpSupport');
    }

    public function faq(): Response
    {
        return Inertia::render('Faq', [
            'faqs' => [
                [
                    'question' => 'Who can vote in the election?',
                    'answer'   => 'Only registered voters with a verified account can cast a ballot during an active election period.',
                ],
                [
                    'question' => 'How do I know if my account is verified?',
                    'answer'   => 'If your account is pending, the Elections page will show a lock screen. Once an admin approves your registration, you can access elections and vote.',
                ],
                [
                    'question' => 'Can I change my vote after submitting?',
                    'answer'   => 'No. Once your ballot is submitted, it cannot be changed. Review your choices carefully before confirming.',
                ],
                [
                    'question' => 'When will results be published?',
                    'answer'   => 'Results are published after the election voting period ends and all ballots have been counted.',
                ],
            ],
        ]);
    }
}
