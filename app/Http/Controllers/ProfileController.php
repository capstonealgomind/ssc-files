<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        return Inertia::render('Profile', [
            'profile' => $this->formatProfile($user),
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
            'remaining_years'      => $user->remainingCourseYears(),
            'years_until_expiry'   => $this->formatYearsUntilExpiry($user),
            'account_expires_at'   => $user->account_expires_at?->format('M d, Y'),
            'is_expired'           => $user->isExpired(),
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
}
