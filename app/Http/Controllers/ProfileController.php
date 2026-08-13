<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\SchoolYearSetting;
use App\Models\YearLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user()->load([
            'department:id,name,acronym,color',
            'course:id,name,duration_years',
            'yearLevel:id,name,sort_order',
        ]);

        if ($user->role === 'voter') {
            $user->markExpiredIfNeeded();
            $user->refresh();
        }

        $profile = $this->formatProfile($user);

        if ($user->role === 'voter') {
            $profile = array_merge($profile, $this->yearLevelEditPayload($user));
        }

        return Inertia::render('Profile', [
            'profile' => $profile,
        ]);
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'profile_photo' => 'required|file|mimetypes:image/*|max:5120',
        ]);

        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $request->file('profile_photo')->store('profile-photos', 'public');

        $user->update(['profile_photo_path' => $path]);

        return back()->with('success', 'Profile photo updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $request->user()->update([
            'password' => $request->input('password'),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function updateName(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! in_array($user->role, ['admin', 'committee'], true)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user->update([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Full name updated successfully.');
    }

    public function updateYearLevel(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->role === 'voter', 403);

        $user->markExpiredIfNeeded();

        if ($user->isExpired()) {
            return back()->with('error', 'Expired accounts cannot update year level.');
        }

        if ($user->markDisabledIfNeeded() || $user->isDisabled()) {
            return redirect()->route('account.disabled');
        }

        $user->loadMissing(['course', 'yearLevel']);
        $schoolYear = SchoolYearSetting::current();

        if (! $schoolYear->isYearLevelEditWindowOpen() && ! $user->year_level_update_override) {
            return back()->with('error', 'Year level updates are not open at this time.');
        }

        if ($user->hasUpdatedYearLevelThisSchoolYear()) {
            return back()->with('error', 'You can update your year level only once per school year.');
        }

        $duration = (int) ($user->course?->duration_years ?? 0);

        $validated = $request->validate([
            'year_level_id' => ['required', 'integer', Rule::exists('year_levels', 'id')],
            'accepted_terms' => ['accepted'],
        ], [
            'accepted_terms.accepted' => 'You must agree to the terms before updating your year level.',
        ]);

        $yearLevel = YearLevel::query()->find($validated['year_level_id']);

        if (! $yearLevel) {
            return back()->withErrors([
                'year_level_id' => 'Please choose a valid year level.',
            ]);
        }

        if ($duration > 0 && (int) $yearLevel->sort_order > $duration) {
            return back()->withErrors([
                'year_level_id' => 'The selected year level is not available for your course.',
            ]);
        }

        $changed = (int) $yearLevel->id !== (int) $user->year_level_id;

        $user->forceFill([
            'year_level_id' => $yearLevel->id,
            'year_level_updated_school_year_start' => $schoolYear->start_year,
            'year_level_update_override' => false,
        ])->save();
        $user->unsetRelation('yearLevel');
        $user->load('yearLevel');

        if ($changed) {
            $user->applyCourseExpiry($user->created_at ?? now());
        }

        return back()->with('success', $changed
            ? 'Year level updated successfully.'
            : 'Year level confirmed for this school year.');
    }

    private function formatProfile(User $user): array
    {
        $base = [
            'name'              => $user->name,
            'email'             => $user->email,
            'contact_email'     => $user->contact_email,
            'role'              => $user->role,
            'registered_at'     => $user->created_at?->format('M d, Y g:i A'),
            'profile_photo_url' => $user->profilePhotoUrl(),
        ];

        if ($user->role !== 'voter') {
            return $base;
        }

        return array_merge($base, [
            'voter_id_number'      => $user->voter_id_number,
            'student_id_number'    => $user->student_id_number,
            'department'           => $user->department?->name,
            'department_acronym'   => $user->department?->acronym,
            'department_color_hex' => $user->department
                ? Department::colorHex($user->department->color)
                : null,
            'course'               => $user->course?->name,
            'course_duration_years'=> $user->course?->duration_years,
            'account_duration'     => $this->formatAccountDuration($user->course?->duration_years),
            'year_level'           => $user->yearLevel?->name,
            'year_level_id'        => $user->year_level_id,
            'remaining_years'      => $user->remainingCourseYears(),
            'years_until_expiry'   => $this->formatYearsUntilExpiry($user),
            'account_expires_at'   => $user->account_expires_at?->format('M d, Y'),
            'is_expired'           => $user->isExpired(),
            'is_disabled'          => $user->isDisabled(),
            'is_verified'          => $user->is_verified,
            'email_verified'       => (bool) $user->email_verified_at,
            'email_status'         => $user->email_status,
            'ocr_status'           => $user->ocr_status,
            'verification_status'  => $user->verification_status,
            'registration_status'  => $user->registration_status,
            'id_image_url'         => $user->id_image_path ? asset('storage/' . $user->id_image_path) : null,
        ]);
    }

    private function formatYearsUntilExpiry(User $user): ?string
    {
        if ($user->isExpired()) {
            return 'Expired';
        }

        if ($user->account_expires_at) {
            if ($user->account_expires_at->isPast()) {
                return 'Expired';
            }

            // Round so e.g. 1y 11m after adding 2 years still shows "2 Years"
            // (floor() was truncating that to "1 Year").
            $yearsFloat = max(0.0, now()->floatDiffInYears($user->account_expires_at, false));
            $years = (int) round($yearsFloat);

            if ($years >= 1) {
                return $years.' '.($years === 1 ? 'Year' : 'Years');
            }

            $months = max(0, (int) round(now()->floatDiffInMonths($user->account_expires_at, false)));

            if ($months <= 0) {
                return 'Less than 1 month';
            }

            return $months.' '.($months === 1 ? 'Month' : 'Months');
        }

        $remaining = $user->remainingCourseYears();

        return $this->formatAccountDuration($remaining > 0 ? $remaining : null) ?? 'Expired';
    }

    private function formatAccountDuration(?int $years): ?string
    {
        if (!$years) {
            return null;
        }

        return $years . ' ' . ($years === 1 ? 'Year' : 'Years');
    }

    private function yearLevelEditPayload(User $user): array
    {
        $schoolYear = SchoolYearSetting::current();
        $duration = (int) ($user->course?->duration_years ?? 0);
        $canEdit = $user->canEditYearLevel();

        $options = YearLevel::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'sort_order'])
            ->filter(fn (YearLevel $yearLevel) => $duration <= 0 || (int) $yearLevel->sort_order <= $duration)
            ->map(fn (YearLevel $yearLevel) => [
                'value' => (string) $yearLevel->id,
                'label' => $yearLevel->name,
            ])
            ->values()
            ->all();

        return [
            'can_edit_year_level' => $canEdit,
            'year_level_edit_locked' => $schoolYear->allow_year_level_edit && $user->hasUpdatedYearLevelThisSchoolYear(),
            'year_level_options' => $options,
            'school_year_label' => $schoolYear->label(),
            'year_level_edit_starts_at' => $schoolYear->year_level_edit_starts_at?->format('M d, Y g:i A'),
            'year_level_edit_ends_at' => $schoolYear->year_level_edit_ends_at?->format('M d, Y g:i A'),
            'year_level_edit_window_open' => $schoolYear->isYearLevelEditWindowOpen(),
        ];
    }
}
