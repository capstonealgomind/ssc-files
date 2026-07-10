<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommitteeController extends CandidateController
{
    public function index(): Response
    {
        return Inertia::render('Committee', $this->candidateFormProps());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeCandidateInput($request);
        $validated = $this->validateCandidate($request);
        unset($validated['photo']);
        $validated['photo_path'] = $this->storePhoto($request);

        Candidate::create($validated);

        return redirect()->route('committee')
            ->with('success', 'Candidate added successfully.');
    }
}
