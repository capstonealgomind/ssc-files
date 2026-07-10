<?php

namespace App\Http\Controllers;

use App\Models\ReactivationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminReactivationController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $status = $request->query('status', 'pending');
        if (! in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            $status = 'pending';
        }

        $query = ReactivationRequest::query()
            ->with([
                'user:id,name,email,voter_id_number,course_id,year_level_id,registration_status,account_expires_at,is_verified',
                'user.course:id,name,duration_years',
                'user.yearLevel:id,name,sort_order',
                'processor:id,name',
            ])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->get()->map(function (ReactivationRequest $item) {
            $user = $item->user;
            $user?->markExpiredIfNeeded();

            return [
                'id' => $item->id,
                'reactivation_number' => $item->reactivation_number,
                'voter_id_number' => $item->voter_id_number,
                'full_name' => $item->full_name,
                'year_stopped' => $item->year_stopped,
                'reason' => $item->reason,
                'status' => $item->status,
                'duration_years_added' => $item->duration_years_added,
                'admin_notes' => $item->admin_notes,
                'submitted_at' => $item->created_at?->format('M d, Y g:i A'),
                'processed_at' => $item->processed_at?->format('M d, Y g:i A'),
                'processed_by' => $item->processor?->name,
                'voter' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'course' => $user->course?->name,
                    'course_duration' => $user->course?->duration_years,
                    'year_level' => $user->yearLevel?->name,
                    'year_level_order' => $user->yearLevel?->sort_order,
                    'remaining_years' => $user->remainingCourseYears(),
                    'registration_status' => $user->registration_status,
                    'account_expires_at' => $user->account_expires_at?->format('M d, Y'),
                    'is_verified' => $user->is_verified,
                ] : null,
            ];
        })->values()->all();

        return Inertia::render('ReactivationRequests', [
            'requests' => $requests,
            'statusFilter' => $status,
            'counts' => [
                'pending' => ReactivationRequest::query()->where('status', ReactivationRequest::STATUS_PENDING)->count(),
                'approved' => ReactivationRequest::query()->where('status', ReactivationRequest::STATUS_APPROVED)->count(),
                'rejected' => ReactivationRequest::query()->where('status', ReactivationRequest::STATUS_REJECTED)->count(),
                'all' => ReactivationRequest::query()->count(),
            ],
        ]);
    }

    public function process(Request $request, ReactivationRequest $reactivationRequest): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        if ($reactivationRequest->status !== ReactivationRequest::STATUS_PENDING) {
            return back()->with('error', 'This request has already been processed.');
        }

        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'duration_years' => ['required_if:action,approve', 'nullable', 'integer', 'min:1', 'max:10'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $voter = $reactivationRequest->user
            ?? User::query()->where('voter_id_number', $reactivationRequest->voter_id_number)->first();

        if ($validated['action'] === 'reject') {
            $reactivationRequest->update([
                'status' => ReactivationRequest::STATUS_REJECTED,
                'admin_notes' => $validated['admin_notes'] ?? null,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);

            if ($voter) {
                $voter->update([
                    'registration_status' => User::STATUS_EXPIRED,
                ]);
            }

            return back()->with('success', 'Reactivation request rejected.');
        }

        if (! $voter) {
            return back()->with('error', 'Linked voter account was not found.');
        }

        $years = (int) $validated['duration_years'];
        $base = $voter->account_expires_at && $voter->account_expires_at->isFuture()
            ? $voter->account_expires_at->copy()
            : now();

        $voter->update([
            'registration_status' => User::STATUS_ACTIVE,
            'account_expires_at' => $base->addYears($years),
            'is_verified' => true,
        ]);

        $reactivationRequest->update([
            'status' => ReactivationRequest::STATUS_APPROVED,
            'duration_years_added' => $years,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
            'user_id' => $voter->id,
        ]);

        return back()->with('success', "Account reactivated and extended by {$years} year(s).");
    }
}
