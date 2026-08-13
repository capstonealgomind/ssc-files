<?php

namespace App\Http\Controllers;

use App\Models\SchoolYearSetting;
use App\Models\User;
use App\Models\YearLevelAppeal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDisabledAccountController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $startYear = (int) SchoolYearSetting::current()->start_year;

        $baseQuery = User::query()
            ->where('role', 'voter')
            ->where('is_disabled', true);

        $accounts = (clone $baseQuery)
            ->with([
                'department:id,name',
                'course:id,name',
                'yearLevel:id,name',
                'yearLevelAppeals' => function ($query) use ($startYear) {
                    $query->where('school_year_start', $startYear)->latest('id');
                },
            ])
            ->withExists([
                'yearLevelAppeals as has_pending_appeal' => function ($query) use ($startYear) {
                    $query->where('school_year_start', $startYear)
                        ->where('status', YearLevelAppeal::STATUS_PENDING);
                },
            ])
            ->orderByDesc('has_pending_appeal')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => $this->formatDisabledAccount($user));

        return Inertia::render('DisabledAccounts', [
            'accounts' => $accounts,
            'counts' => [
                'disabled' => (clone $baseQuery)->count(),
                'appeals' => (clone $baseQuery)
                    ->whereHas('yearLevelAppeals', function ($query) use ($startYear) {
                        $query->where('school_year_start', $startYear)
                            ->where('status', YearLevelAppeal::STATUS_PENDING);
                    })
                    ->count(),
            ],
        ]);
    }

    public function show(Request $request, User $user): Response|RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        if ($user->role !== 'voter' || ! $user->isDisabled()) {
            return redirect()
                ->route('disabled-accounts')
                ->with('error', 'That account is not currently disabled.');
        }

        $user->loadMissing([
            'department:id,name',
            'course:id,name,duration_years',
            'yearLevel:id,name',
        ]);

        $appeal = $user->currentYearLevelAppeal();
        $appeal?->loadMissing('processor:id,name');

        return Inertia::render('DisabledAccountProcess', [
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'voter_id_number' => $user->voter_id_number,
                'student_id_number' => $user->student_id_number,
                'department' => $user->department?->name,
                'course' => $user->course?->name,
                'year_level' => $user->yearLevel?->name,
                'remaining_years' => $user->remainingCourseYears(),
                'account_expires_at' => $user->account_expires_at?->format('M d, Y'),
                'profile_photo_url' => $user->profilePhotoUrl(),
                'registration_status' => $user->registration_status,
                'is_verified' => (bool) $user->is_verified,
            ],
            'appeal' => $appeal?->toPayload(),
            'school_year_label' => SchoolYearSetting::current()->label(),
        ]);
    }

    public function process(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        if ($user->role !== 'voter' || ! $user->isDisabled()) {
            return redirect()
                ->route('disabled-accounts')
                ->with('error', 'That account is not currently disabled.');
        }

        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $appeal = $user->yearLevelAppeals()
            ->where('school_year_start', (int) SchoolYearSetting::current()->start_year)
            ->where('status', YearLevelAppeal::STATUS_PENDING)
            ->latest('id')
            ->first();

        if ($validated['action'] === 'reject') {
            if (! $appeal) {
                return back()->with('error', 'This voter has not submitted an appeal to reject.');
            }

            $appeal->update([
                'status' => YearLevelAppeal::STATUS_REJECTED,
                'admin_notes' => $validated['admin_notes'] ?? null,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);

            return redirect()
                ->route('disabled-accounts')
                ->with('success', 'Appeal rejected. The account stays disabled.');
        }

        if ($appeal) {
            $appeal->update([
                'status' => YearLevelAppeal::STATUS_APPROVED,
                'admin_notes' => $validated['admin_notes'] ?? null,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);
        }

        $user->forceFill([
            'is_disabled' => false,
            'registration_status' => User::STATUS_ACTIVE,
            'year_level_update_override' => true,
        ])->save();

        return redirect()
            ->route('disabled-accounts')
            ->with('success', 'Account restored. The voter can sign in and update their year level on Profile.');
    }

    private function formatDisabledAccount(User $user): array
    {
        $appeal = $user->yearLevelAppeals->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'voter_id_number' => $user->voter_id_number,
            'department' => $user->department?->name,
            'course' => $user->course?->name,
            'year_level' => $user->yearLevel?->name,
            'has_appeal' => $appeal !== null,
            'has_pending_appeal' => $appeal?->status === YearLevelAppeal::STATUS_PENDING,
            'appeal_status' => $appeal?->status,
            'appeal_submitted_at' => $appeal?->created_at?->format('M d, Y g:i A'),
        ];
    }
}
