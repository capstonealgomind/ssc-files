<?php

namespace App\Http\Controllers;

use App\Models\Election;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ElectionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Elections', [
            'elections' => Election::query()
                ->with('creator:id,name')
                ->orderByDesc('voting_starts_at')
                ->get()
                ->map(fn (Election $election) => $this->formatElection($election))
                ->values()
                ->all(),
            'statusOptions' => collect(Election::STATUS_LABELS)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeElectionInput($request);
        $validated = $this->validateElection($request);

        Election::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('elections')
            ->with('success', 'Election created successfully.');
    }

    public function update(Request $request, Election $election): RedirectResponse
    {
        $this->normalizeElectionInput($request);
        $election->update($this->validateElection($request, $election));

        return redirect()->route('elections')
            ->with('success', 'Election updated successfully.');
    }

    public function destroy(Election $election): RedirectResponse
    {
        $election->delete();

        return redirect()->route('elections')
            ->with('success', 'Election deleted successfully.');
    }

    private function normalizeElectionInput(Request $request): void
    {
        $request->merge([
            'event_starts_at' => $request->input('event_starts_at') ?: null,
            'event_ends_at'   => $request->input('event_ends_at') ?: null,
        ]);
    }

    private function validateElection(Request $request, ?Election $election = null): array
    {
        return $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:2000',
            'event_starts_at'  => 'nullable|date',
            'event_ends_at'    => 'nullable|date|after_or_equal:event_starts_at',
            'voting_starts_at' => 'required|date',
            'voting_ends_at'   => 'required|date|after:voting_starts_at',
            'status'           => ['required', 'string', Rule::in(Election::STATUSES)],
        ]);
    }

    private function formatElection(Election $election): array
    {
        return [
            'id'                 => $election->id,
            'title'              => $election->title,
            'description'        => $election->description,
            'event_starts_at'    => $election->event_starts_at?->format('Y-m-d\TH:i'),
            'event_ends_at'      => $election->event_ends_at?->format('Y-m-d\TH:i'),
            'voting_starts_at'   => $election->voting_starts_at->format('Y-m-d\TH:i'),
            'voting_ends_at'     => $election->voting_ends_at->format('Y-m-d\TH:i'),
            'status'             => $election->status,
            'status_label'       => $election->statusLabel(),
            'event_schedule'     => $this->formatPeriod($election->event_starts_at, $election->event_ends_at, 'Not set'),
            'voting_period'      => $this->formatPeriod($election->voting_starts_at, $election->voting_ends_at),
            'created_by_name'    => $election->creator?->name,
            'created_at'         => $election->created_at?->format('M d, Y'),
        ];
    }

    private function formatPeriod(?\DateTimeInterface $startsAt, ?\DateTimeInterface $endsAt, ?string $fallback = null): string
    {
        if (!$startsAt || !$endsAt) {
            return $fallback ?? '—';
        }

        return $startsAt->format('M d, Y g:i A').' – '.$endsAt->format('M d, Y g:i A');
    }
}
