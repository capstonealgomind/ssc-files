<?php

namespace App\Http\Controllers;

use App\Models\ReactivationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReactivationController extends Controller
{
    public function create(Request $request): Response
    {
        if ($request->boolean('reset')) {
            $request->session()->forget('reactivation_validated_voter');
        }

        return Inertia::render('ReactivateAccount', [
            'validatedVoter' => $request->session()->get('reactivation_validated_voter'),
            'submitted' => null,
        ]);
    }

    public function validateVoter(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'voter_id' => ['required', 'string', 'max:50'],
        ]);

        $voter = User::query()
            ->where('role', 'voter')
            ->where('voter_id_number', $validated['voter_id'])
            ->with(['course:id,name,duration_years', 'yearLevel:id,name,sort_order'])
            ->first();

        if (! $voter) {
            return back()->withErrors([
                'voter_id' => 'Voter ID not found. Please check and try again.',
            ])->withInput();
        }

        $voter->markExpiredIfNeeded();
        $voter->refresh();

        if (! $voter->isExpired() && $voter->registration_status === User::STATUS_ACTIVE) {
            return back()->withErrors([
                'voter_id' => 'This voter account is still active and does not need reactivation.',
            ])->withInput();
        }

        $pending = ReactivationRequest::query()
            ->where('voter_id_number', $voter->voter_id_number)
            ->where('status', ReactivationRequest::STATUS_PENDING)
            ->exists();

        if ($pending) {
            return back()->withErrors([
                'voter_id' => 'A reactivation request for this voter ID is already pending. Use Reactivation Status to track it.',
            ])->withInput();
        }

        $request->session()->put('reactivation_validated_voter', [
            'id' => $voter->id,
            'voter_id_number' => $voter->voter_id_number,
            'name' => $voter->name,
            'course' => $voter->course?->name,
            'course_duration' => $voter->course?->duration_years,
            'year_level' => $voter->yearLevel?->name,
            'year_level_order' => $voter->yearLevel?->sort_order,
            'remaining_years' => $voter->remainingCourseYears(),
            'account_expires_at' => $voter->account_expires_at?->format('M d, Y'),
            'registration_status' => $voter->registration_status,
        ]);

        return redirect()->route('reactivate');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'voter_id' => ['required', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:255'],
            'year_stopped' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $voter = User::query()
            ->where('role', 'voter')
            ->where('voter_id_number', $validated['voter_id'])
            ->first();

        if (! $voter) {
            return back()->withErrors([
                'voter_id' => 'Voter ID not found.',
            ])->withInput();
        }

        $voter->markExpiredIfNeeded();

        if (! $voter->isExpired() && $voter->registration_status === User::STATUS_ACTIVE) {
            return back()->withErrors([
                'voter_id' => 'This voter account is still active.',
            ])->withInput();
        }

        $existing = ReactivationRequest::query()
            ->where('voter_id_number', $voter->voter_id_number)
            ->where('status', ReactivationRequest::STATUS_PENDING)
            ->first();

        if ($existing) {
            $request->session()->forget('reactivation_validated_voter');

            return redirect()
                ->route('reactivate')
                ->with('success', 'A pending request already exists.')
                ->with('reactivation_number', $existing->reactivation_number);
        }

        $requestModel = ReactivationRequest::create([
            'user_id' => $voter->id,
            'voter_id_number' => $voter->voter_id_number,
            'full_name' => $validated['full_name'],
            'year_stopped' => $validated['year_stopped'],
            'reason' => $validated['reason'],
            'reactivation_number' => ReactivationRequest::generateNumber(),
            'status' => ReactivationRequest::STATUS_PENDING,
        ]);

        $voter->update([
            'registration_status' => User::STATUS_PENDING_REACTIVATION,
        ]);

        $request->session()->forget('reactivation_validated_voter');

        return redirect()
            ->route('reactivate')
            ->with('success', 'Reactivation request submitted.')
            ->with('reactivation_number', $requestModel->reactivation_number);
    }

    public function statusForm(Request $request): Response
    {
        return Inertia::render('ReactivationStatus', [
            'result' => $request->session()->pull('reactivation_status_result'),
        ]);
    }

    public function statusCheck(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lookup' => ['required', 'string', 'max:50'],
        ]);

        $lookup = trim($validated['lookup']);

        $requestModel = ReactivationRequest::query()
            ->with(['user:id,name,voter_id_number,registration_status,account_expires_at,is_verified'])
            ->where(function ($query) use ($lookup) {
                $query->where('reactivation_number', $lookup)
                    ->orWhere('voter_id_number', $lookup);
            })
            ->latest()
            ->first();

        if (! $requestModel) {
            return back()->withErrors([
                'lookup' => 'No reactivation request found for that number or voter ID.',
            ])->withInput();
        }

        $user = $requestModel->user;
        $user?->markExpiredIfNeeded();
        $user?->refresh();

        $request->session()->flash('reactivation_status_result', [
            'reactivation_number' => $requestModel->reactivation_number,
            'voter_id_number' => $requestModel->voter_id_number,
            'full_name' => $requestModel->full_name,
            'year_stopped' => $requestModel->year_stopped,
            'reason' => $requestModel->reason,
            'status' => $requestModel->status,
            'duration_years_added' => $requestModel->duration_years_added,
            'admin_notes' => $requestModel->admin_notes,
            'submitted_at' => $requestModel->created_at?->format('M d, Y g:i A'),
            'processed_at' => $requestModel->processed_at?->format('M d, Y g:i A'),
            'account_status' => $user?->registration_status,
            'account_expires_at' => $user?->account_expires_at?->format('M d, Y'),
            'is_active' => $user
                && $user->registration_status === User::STATUS_ACTIVE
                && ! $user->isExpired()
                && $user->is_verified,
        ]);

        return redirect()->route('reactivation-status');
    }
}
