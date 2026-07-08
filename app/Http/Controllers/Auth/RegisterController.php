<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use App\Models\YearLevel;
use App\Services\DtsRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(DtsRegistrationService $dtsRegistration): Response
    {
        return Inertia::render('Auth/Register', [
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values()
                ->all(),
            'courses' => Course::query()
                ->orderBy('name')
                ->get(['id', 'department_id', 'name', 'duration_years'])
                ->values()
                ->all(),
            'yearLevels' => YearLevel::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'sort_order'])
                ->values()
                ->all(),
            'registrationWindow' => $dtsRegistration->publicPayload(),
        ]);
    }

    public function store(Request $request, DtsRegistrationService $dtsRegistration): RedirectResponse
    {
        if (! $dtsRegistration->isOpen()) {
            return back()->with('error', $dtsRegistration->closedMessage());
        }

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|string|email|max:255|unique:users',
            'student_id_number' => 'required|string|max:50|unique:users,student_id_number',
            'department_id'     => 'required|exists:departments,id',
            'course_id'         => [
                'required',
                Rule::exists('courses', 'id')->where(fn ($q) => $q->where('department_id', $request->department_id)),
            ],
            'year_level_id' => 'required|exists:year_levels,id',
            'password'      => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $course     = Course::find($validated['course_id']);
        $yearLevel  = YearLevel::find($validated['year_level_id']);

        if ($course && $yearLevel && $yearLevel->sort_order > $course->duration_years) {
            return back()->withErrors([
                'year_level_id' => 'The selected year level is not available for this course.',
            ])->withInput();
        }

        if (User::isAdminEmail($validated['email'])) {
            return back()->withErrors([
                'email' => 'Admin accounts must use @'.User::ADMIN_EMAIL_DOMAIN.' and be created by an administrator.',
            ])->onlyInput('name', 'student_id_number', 'department_id', 'course_id', 'year_level_id');
        }

        // Store validated data in session; user account is created in Step 2
        $request->session()->put('reg_step1', $validated);

        return redirect()->route('register.id-scan');
    }
}
