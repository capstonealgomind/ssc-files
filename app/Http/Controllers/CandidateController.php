<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Election;
use App\Models\Partylist;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CandidateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Candidates', [
            'departmentColors' => Department::COLORS,
            'candidates' => $this->candidateQuery()
                ->get()
                ->map(fn (Candidate $candidate) => $this->formatCandidate($candidate))
                ->values()
                ->all(),
            'elections' => $this->electionOptions(),
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values()
                ->all(),
            'positionOptions' => $this->positionOptions(),
            'partylistOptions' => $this->partylistOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('CandidateCreate', [
            'elections' => $this->electionOptions(),
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values()
                ->all(),
            'positionOptions' => $this->positionOptions(),
            'partylistOptions' => $this->partylistOptions(),
        ]);
    }

    public function edit(Candidate $candidate): Response
    {
        $candidate->load(['election:id,title,status', 'department:id,name,color', 'position:id,name', 'partylist:id,name,acronym']);

        return Inertia::render('CandidateEdit', [
            'candidate' => $this->formatCandidate($candidate),
            'elections' => $this->electionOptions(),
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values()
                ->all(),
            'positionOptions' => $this->positionOptions(),
            'partylistOptions' => $this->partylistOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeCandidateInput($request);
        $validated = $this->validateCandidate($request);
        unset($validated['photo']);
        $validated['photo_path'] = $this->storePhoto($request);

        Candidate::create($validated);

        return redirect()->route('candidates')
            ->with('success', 'Candidate added successfully.');
    }

    public function update(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->normalizeCandidateInput($request);
        $validated = $this->validateCandidate($request, $candidate);
        unset($validated['photo']);
        $validated['photo_path'] = $this->storePhoto($request, $candidate);

        $candidate->update($validated);

        return redirect()->route('candidates')
            ->with('success', 'Candidate updated successfully.');
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        $this->deletePhoto($candidate->photo_path);
        $candidate->delete();

        return redirect()->route('candidates')
            ->with('success', 'Candidate removed successfully.');
    }

    private function candidateQuery()
    {
        return Candidate::query()
            ->with(['election:id,title,status', 'department:id,name,color', 'position:id,name,sort_order', 'partylist:id,name,acronym'])
            ->join('positions', 'candidates.position_id', '=', 'positions.id')
            ->orderBy('candidates.election_id')
            ->orderBy('positions.sort_order')
            ->orderBy('positions.name')
            ->orderBy('candidates.name')
            ->select('candidates.*');
    }

    private function electionOptions(): array
    {
        return Election::query()
            ->orderByDesc('voting_starts_at')
            ->get(['id', 'title', 'status'])
            ->map(fn (Election $election) => [
                'id'           => $election->id,
                'title'        => $election->title,
                'status'       => $election->status,
                'status_label' => $election->statusLabel(),
            ])
            ->values()
            ->all();
    }

    private function positionOptions(): array
    {
        return Position::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Position $position) => [
                'value' => (string) $position->id,
                'label' => $position->name,
            ])
            ->values()
            ->all();
    }

    private function partylistOptions(): array
    {
        return [
            ['value' => '', 'label' => 'Independent'],
            ...Partylist::query()
                ->orderBy('name')
                ->get(['id', 'name', 'acronym'])
                ->map(fn (Partylist $partylist) => [
                    'value' => (string) $partylist->id,
                    'label' => $partylist->acronym
                        ? "{$partylist->acronym} — {$partylist->name}"
                        : $partylist->name,
                ])
                ->values()
                ->all(),
        ];
    }

    private function normalizeCandidateInput(Request $request): void
    {
        $request->merge([
            'department_id' => $request->input('department_id') ?: null,
            'partylist_id'  => $request->input('partylist_id') ?: null,
        ]);
    }

    private function validateCandidate(Request $request, ?Candidate $candidate = null): array
    {
        return $request->validate([
            'election_id'   => 'required|exists:elections,id',
            'name'          => 'required|string|max:255',
            'position_id'   => 'required|exists:positions,id',
            'department_id' => 'nullable|exists:departments,id',
            'partylist_id'  => 'nullable|exists:partylists,id',
            'platform'      => 'nullable|string|max:2000',
            'photo'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
    }

    private function storePhoto(Request $request, ?Candidate $candidate = null): ?string
    {
        if (!$request->hasFile('photo')) {
            return $candidate?->photo_path;
        }

        $this->deletePhoto($candidate?->photo_path);

        return $request->file('photo')->store('candidates', 'public');
    }

    private function deletePhoto(?string $photoPath): void
    {
        if ($photoPath) {
            Storage::disk('public')->delete($photoPath);
        }
    }

    private function formatCandidate(Candidate $candidate): array
    {
        return [
            'id'              => $candidate->id,
            'election_id'     => $candidate->election_id,
            'election_title'  => $candidate->election?->title,
            'election_status' => $candidate->election?->status,
            'name'            => $candidate->name,
            'position_id'     => $candidate->position_id,
            'position'        => $candidate->position?->name,
            'department_id'   => $candidate->department_id,
            'department_name' => $candidate->department?->name,
            'department_color'=> $candidate->department?->color,
            'partylist_id'    => $candidate->partylist_id,
            'partylist_name'  => $candidate->partylist?->name,
            'partylist_acronym' => $candidate->partylist?->acronym,
            'partylist_label' => $candidate->partylist_id
                ? ($candidate->partylist?->acronym ?: $candidate->partylist?->name)
                : 'Independent',
            'platform'        => $candidate->platform,
            'photo_url'       => $candidate->photo_path
                ? asset('storage/'.$candidate->photo_path)
                : null,
            'created_at'      => $candidate->created_at?->format('M d, Y'),
        ];
    }
}
