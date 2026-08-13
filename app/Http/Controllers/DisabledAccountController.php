<?php

namespace App\Http\Controllers;

use App\Models\SchoolYearSetting;
use App\Models\YearLevelAppeal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisabledAccountController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'voter' || ! $user->isDisabled()) {
            return redirect()->route('dashboard');
        }

        $user->loadMissing(['department:id,name', 'course:id,name', 'yearLevel:id,name']);

        return Inertia::render('DisabledAccount', [
            'account' => $this->accountPayload($user),
            'appeal' => $user->currentYearLevelAppeal()?->toPayload(),
        ]);
    }

    public function appealForm(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'voter' || ! $user->isDisabled()) {
            return redirect()->route('dashboard');
        }

        $user->loadMissing(['department:id,name', 'course:id,name', 'yearLevel:id,name']);

        return Inertia::render('DisabledAccountAppeal', [
            'account' => $this->accountPayload($user),
            'appeal' => $user->currentYearLevelAppeal()?->toPayload(),
        ]);
    }

    public function submitAppeal(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'voter' || ! $user->isDisabled()) {
            return redirect()->route('dashboard');
        }

        $existing = $user->currentYearLevelAppeal();

        if ($existing && $existing->status === YearLevelAppeal::STATUS_PENDING) {
            return redirect()
                ->route('account.disabled.appeal')
                ->with('success', 'Your appeal is already submitted and waiting for review.');
        }

        if ($existing && $existing->status === YearLevelAppeal::STATUS_APPROVED) {
            return redirect()
                ->route('account.disabled')
                ->with('success', 'Your appeal was already approved.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:20', 'max:2000'],
        ], [
            'reason.required' => 'Please explain why you did not update your year level on time.',
            'reason.min' => 'Please provide a bit more detail (at least 20 characters).',
        ]);

        YearLevelAppeal::create([
            'user_id' => $user->id,
            'school_year_start' => (int) SchoolYearSetting::current()->start_year,
            'reason' => $validated['reason'],
            'status' => YearLevelAppeal::STATUS_PENDING,
        ]);

        return redirect()
            ->route('account.disabled.appeal')
            ->with('success', 'Your appeal was submitted. An administrator will review it.');
    }

    private function accountPayload($user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'voter_id_number' => $user->voter_id_number,
            'student_id_number' => $user->student_id_number,
            'department' => $user->department?->name,
            'course' => $user->course?->name,
            'year_level' => $user->yearLevel?->name,
            'profile_photo_url' => $user->profilePhotoUrl(),
            'status_label' => 'Disabled',
        ];
    }
}
