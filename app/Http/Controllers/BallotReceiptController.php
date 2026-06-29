<?php

namespace App\Http\Controllers;

use App\Models\BallotReceipt;
use App\Models\Vote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BallotReceiptController extends Controller
{
    public function show(Request $request, BallotReceipt $receipt): \Inertia\Response
    {
        $this->authorizeReceipt($request, $receipt);

        return Inertia::render('BallotReceipt', [
            'receipt' => $this->formatReceipt($receipt),
        ]);
    }

    public function pdf(Request $request, BallotReceipt $receipt): HttpResponse
    {
        $this->authorizeReceipt($request, $receipt);

        if ($request->header('X-Inertia')) {
            return Inertia::location(route('ballot-receipt.pdf', $receipt));
        }

        $data = $this->buildPdfData($receipt);

        $pdf = Pdf::loadView('receipts.ballot', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'ballot-receipt-' . $receipt->receipt_number . '.pdf';

        return $pdf->download($filename);
    }

    private function authorizeReceipt(Request $request, BallotReceipt $receipt): void
    {
        if ($request->user()->id !== $receipt->user_id) {
            abort(403);
        }
    }

    private function formatReceipt(BallotReceipt $receipt): array
    {
        $receipt->load([
            'user.department:id,name',
            'user.course:id,name',
            'user.yearLevel:id,name',
            'election:id,title,voting_starts_at,voting_ends_at',
        ]);

        $votes = Vote::query()
            ->where('user_id', $receipt->user_id)
            ->where('election_id', $receipt->election_id)
            ->with(['candidate:id,name', 'position:id,name'])
            ->orderBy('position_id')
            ->get();

        $election = $receipt->election;
        $user = $receipt->user;

        return [
            'id'              => $receipt->id,
            'receipt_number'  => $receipt->receipt_number,
            'submitted_at'    => $receipt->submitted_at->format('M d, Y g:i A'),
            'voter'           => [
                'name'            => $user->name,
                'voter_id_number' => $user->voter_id_number,
                'department'      => $user->department?->name,
                'course'          => $user->course?->name,
                'year_level'      => $user->yearLevel?->name,
            ],
            'election'        => [
                'title'         => $election->title,
                'voting_period' => $this->formatPeriod($election->voting_starts_at, $election->voting_ends_at),
            ],
            'selections'      => $votes->map(fn (Vote $v) => [
                'position'  => $v->position?->name,
                'candidate' => $v->candidate?->name,
            ])->values()->all(),
            'pdf_url'         => url("/ballot-receipt/{$receipt->id}/pdf"),
        ];
    }

    private function buildPdfData(BallotReceipt $receipt): array
    {
        return array_merge($this->formatReceipt($receipt), [
            'generated_at' => now()->format('M d, Y g:i A'),
            'app_name'     => config('app.name', 'SSCEVS'),
            'bcc_logo'     => $this->imageDataUri('bcc.png'),
            'ssc_logo'     => $this->imageDataUri('ssc.png'),
        ]);
    }

    private function imageDataUri(string $filename): ?string
    {
        $path = public_path('images/' . $filename);

        if (!is_file($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            default       => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    private function formatPeriod(?\DateTimeInterface $start, ?\DateTimeInterface $end): string
    {
        if (!$start || !$end) {
            return '—';
        }

        return $start->format('M d, Y g:i A') . ' – ' . $end->format('M d, Y g:i A');
    }
}
