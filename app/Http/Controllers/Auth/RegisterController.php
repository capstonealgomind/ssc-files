<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\OfficialStudentRecord;
use App\Models\User;
use App\Models\YearLevel;
use App\Services\DtsRegistrationService;
use App\Support\NameLetters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(Request $request, DtsRegistrationService $dtsRegistration): Response|RedirectResponse
    {
        $courses = Course::query()->orderBy('name')->get(['id', 'department_id', 'name', 'duration_years']);
        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        $scan = $this->hasScan($request) ? $request->session()->get('registration_scan') : null;
        $scannedDetails = $scan ? \Illuminate\Support\Arr::only($scan, [
            'name', 'student_id', 'course', 'year_level', 'section', 'year_course', 'name_issue',
        ]) : null;
        if ($scannedDetails) {
            $ocrCourse = (string) ($scan['course'] ?? '');
            $courseKey = $this->courseKey($ocrCourse);
            $abbreviation = preg_replace('/[^a-z0-9]/', '', strtolower($ocrCourse));
            $matches = $courses->filter(function (Course $course) use ($courseKey, $abbreviation) {
                $initials = implode('', array_map(
                    fn (string $word) => preg_match('/^(?:bs|ba|ab|bped|bsee|bsit|bsis|bscs|bsece|bpa|blis)$/', $word) ? $word : $word[0],
                    $this->courseWords($course->name),
                ));

                return $courseKey !== '' && ($this->courseKey($course->name) === $courseKey || $initials === $abbreviation);
            });
            $course = $matches->count() === 1 ? $matches->first() : null;

            $scannedDetails['department_id'] = $course?->department_id;
            $scannedDetails['course_id'] = $course?->id;

            $yearNumber = preg_match('/^([1-9])/', (string) ($scan['year_level'] ?? ''), $yearMatch)
                ? (int) $yearMatch[1]
                : null;
            $yearLevel = $yearNumber
                ? YearLevel::query()->where('sort_order', $yearNumber)->first()
                : null;
            $scannedDetails['year_level_id'] = $yearLevel?->id;

            $sectionLetter = strtoupper((string) ($scan['section'] ?? ''));
            $sectionExists = $course && $yearLevel && $sectionLetter !== ''
                ? \App\Models\Section::query()
                    ->where('course_id', $course->id)
                    ->where('year_level_id', $yearLevel->id)
                    ->where('letter', $sectionLetter)
                    ->exists()
                : false;
            $scannedDetails['section'] = $sectionExists ? $sectionLetter : '';
        }

        return Inertia::render('Auth/Register', [
            'scannedDetails' => $scannedDetails,
            'hasScannedId' => (bool) $scan,
            'scannedDocumentType' => $scan['document_type'] ?? 'school_id',
            'idPreviewUrl' => $scan
                ? route('register.id-scan.preview', ['v' => $scan['preview_key'] ?? $scan['expires']])
                : null,
            'sections' => \App\Models\Section::orderBy('letter')->get(['course_id', 'year_level_id', 'letter']),
            'departments' => $departments,
            'courses' => $courses,
            'yearLevels' => YearLevel::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'sort_order'])
                ->values()
                ->all(),
            'registrationWindow' => $dtsRegistration->publicPayload(),
        ]);
    }

    private function courseWords(string $name): array
    {
        return array_values(array_diff(
            preg_split('/[^a-z0-9]+/', strtolower($name), -1, PREG_SPLIT_NO_EMPTY),
            ['of', 'in', 'and', 'the', 'major'],
        ));
    }

    private function courseKey(string $name): string
    {
        $words = $this->courseWords($name);
        sort($words);

        return implode(' ', $words);
    }

    public function store(Request $request, DtsRegistrationService $dtsRegistration, OfficialRegistrationOtpController $otp): RedirectResponse
    {
        if (! $dtsRegistration->isOpen()) {
            return back()->with('error', $dtsRegistration->closedMessage());
        }

        if (! $this->hasScan($request)) {
            return redirect()->route('register.details')->withErrors([
                'id_image' => 'Upload and scan your school ID or enrollment form before continuing.',
            ]);
        }

        $request->merge(['student_id_number' => OfficialStudentRecord::studentId((string) $request->string('student_id_number'))]);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && User::nameIsTaken($value)) {
                        $fail('This name is already registered. Each person can have only one account.');
                    }
                },
            ],
            'email'             => 'required|string|email|max:255|unique:users',
            'student_id_number' => [
                'required', 'string', 'max:50', 'regex:'.OfficialStudentRecord::STUDENT_ID_PATTERN,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $key = OfficialStudentRecord::studentId((string) $value);
                    $alreadyRegistered = User::query()->whereNotNull('student_id_number')
                        ->get(['student_id_number'])
                        ->contains(fn (User $user) => OfficialStudentRecord::studentId((string) $user->student_id_number) === $key);

                    if ($alreadyRegistered) {
                        $fail('This Student ID is already registered.');
                    }
                },
            ],
            'section' => 'required|string|max:150',
            'department_id'     => 'required|exists:departments,id',
            'course_id'         => [
                'required',
                Rule::exists('courses', 'id')->where(fn ($q) => $q->where('department_id', $request->department_id)),
            ],
            'year_level_id' => 'required|exists:year_levels,id',
            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults()
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ], [
            'password.mixed' => 'Password must include uppercase and lowercase letters.',
            'password.numbers' => 'Password must include at least one number.',
            'password.symbols' => 'Password must include at least one symbol.',
            'password.letters' => 'Password must include letters.',
        ]);

        $course     = Course::find($validated['course_id']);
        $yearLevel  = YearLevel::find($validated['year_level_id']);

        if ($course && $yearLevel && $yearLevel->sort_order > $course->duration_years) {
            return back()->withErrors([
                'year_level_id' => 'The selected year level is not available for this course.',
            ])->withInput();
        }

        $sections = \App\Models\Section::where('course_id', $course->id)
            ->where('year_level_id', $yearLevel->id)->pluck('letter');
        if (! $sections->contains(strtoupper($validated['section']))) {
            return back()->withErrors(['section' => 'Select a section configured for your course and year level.'])->withInput();
        }

        if (User::isSystemEmail($validated['email'])) {
            return back()->withErrors([
                'email' => 'System accounts (@'.User::ADMIN_EMAIL_DOMAIN.' / @'.User::COMMITTEE_EMAIL_DOMAIN.') must be created by an administrator.',
            ])->onlyInput('name', 'student_id_number', 'department_id', 'course_id', 'year_level_id');
        }

        $officialRecord = OfficialStudentRecord::query()
            ->where('student_id', $validated['student_id_number'])
            ->first();
        if ($officialRecord && ! $officialRecord->removed_at && ! filled($officialRecord->email)) {
            return back()->withErrors([
                'email' => 'Your official student record has no email address. Please visit the administrator to update your record before continuing.',
            ]);
        }
        if ($officialRecord && ! $officialRecord->removed_at
            && mb_strtolower(trim($validated['email'])) !== mb_strtolower(trim($officialRecord->email))) {
            return back()->withErrors([
                'email' => 'Wrong email account. Enter the email listed on your official student record. If you no longer have access to it, please visit the administrator to update your record.',
            ]);
        }

        unset($validated['password_confirmation']);
        $validated['password'] = Hash::make($validated['password']);
        $validated['attempts'] = $request->session()->get('registration_pending.attempts', 0);
        $previous = $request->session()->get('registration_pending');
        if (! $otp->send($request, $validated)) {
            return back()->withErrors(['email' => 'The verification email could not be sent. Try again.']);
        }
        if (! $previous) {
            $otp->logAttempt($request, 'register');
        }

        return redirect()->route('register.verify-otp');
    }

    private function hasScan(Request $request): bool
    {
        return $request->session()->get('registration_scan.expires', 0) > now()->timestamp;
    }

    public function checkName(Request $request): JsonResponse
    {
        $name = trim((string) $request->query('name', ''));
        $key = NameLetters::key($name);

        if (strlen($key) < 2) {
            return response()->json([
                'available' => null,
                'message' => null,
            ]);
        }

        $taken = User::nameIsTaken($name);

        return response()->json([
            'available' => ! $taken,
            'message' => $taken
                ? 'This name is already registered. Each person can have only one account.'
                : 'This name is available.',
        ]);
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $request->merge([
            'student_id_number' => OfficialStudentRecord::studentId((string) $request->string('student_id_number')),
        ]);
        $validated = $request->validate([
            'student_id_number' => ['required', 'string', 'max:50', 'regex:'.OfficialStudentRecord::STUDENT_ID_PATTERN],
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);
        $studentId = OfficialStudentRecord::studentId($validated['student_id_number']);
        $record = OfficialStudentRecord::query()->where('student_id', $studentId)->first();
        if (! $record) {
            $record = OfficialStudentRecord::query()
                ->get()
                ->first(fn (OfficialStudentRecord $candidate) => OfficialStudentRecord::studentId($candidate->student_id) === $studentId);
        }

        if (! $record) {
            return response()->json(['status' => 'not_found', 'message' => '']);
        }

        if (! filled($record->email)) {
            return response()->json([
                'status' => 'email_missing',
                'message' => 'Your official student record has no email address. Please visit the administrator to update your record.',
            ]);
        }

        $matches = mb_strtolower(trim($validated['email'])) === mb_strtolower(trim($record->email));

        return response()->json([
            'status' => $matches ? 'matched' : 'mismatch',
            'message' => $matches
                ? 'This email matches your official student record.'
                : 'Wrong email account. Enter the email listed on your official student record. If you no longer have access to it, please visit the administrator to update your record.',
        ]);
    }
}
