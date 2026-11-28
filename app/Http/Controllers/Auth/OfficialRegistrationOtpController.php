<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\RegistrationAttempt;
use App\Models\OfficialStudentRecord;
use App\Models\SchoolYearSetting;
use App\Models\User;
use App\Services\DtsRegistrationService;
use App\Services\FraudDetectionService;
use App\Services\OfficialStudentVerification;
use App\Services\OtpService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OfficialRegistrationOtpController extends Controller
{
    public function send(Request $request, array $pending): bool
    {
        $previous = $request->session()->get('registration_pending', []);
        $pending['voter_id_number'] ??= ($previous['email'] ?? null) === $pending['email']
            && ($previous['student_id_number'] ?? null) === $pending['student_id_number']
            ? ($previous['voter_id_number'] ?? null)
            : null;

        $code = OtpService::newCode();
        $pending['code'] = Hash::make($code);
        $pending['otp_expires'] = now()->addMinutes(OtpService::EXPIRY_MINUTES)->timestamp;

        try {
            $pending['voter_id_number'] ??= User::generateVoterIdNumber();
            Mail::to($pending['email'])->send(new VerifyEmail(
                route('register.verify-otp'),
                $code,
                $pending['name'],
                $pending['voter_id_number'],
            ));
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }

        $request->session()->forget('reg_user_id');
        $request->session()->put('registration_pending', $pending);
        return true;
    }

    public function create(Request $request, OtpController $legacy)
    {
        if (! $this->pending($request)) {
            if ($request->session()->has('reg_user_id')) {
                return $legacy->create($request);
            }
            return $this->expired();
        }

        [$local, $domain] = explode('@', $request->session()->get('registration_pending.email'), 2);
        return Inertia::render('Auth/VerifyOtp', [
            'maskedEmail' => substr($local, 0, 2).str_repeat('*', max(strlen($local) - 2, 2)).'@'.$domain,
            'expiryMinutes' => OtpService::EXPIRY_MINUTES,
            'emailMismatch' => (bool) ($request->session()->get('registration_pending.email_mismatch', false)),
            'officialEmailMissing' => (bool) ($request->session()->get('registration_pending.official_email_missing', false)),
        ]);
    }

    public function updateEmail(Request $request)
    {
        if (! $pending = $this->pending($request)) {
            return $this->expired();
        }

        $validated = $request->validate(['email' => 'required|string|email|max:255|unique:users,email']);
        if (User::isSystemEmail($validated['email'])) {
            return back()->withErrors(['email' => 'Use your student email address.']);
        }

        $record = OfficialStudentRecord::query()
            ->where('student_id', $pending['student_id_number'])
            ->first();

        if (! $record || ! filled($record->email)) {
            $request->session()->put('registration_pending.official_email_missing', true);
            return back()->withErrors(['email' => 'There is no email address on your official student record. Please visit the administrator to update it.']);
        }

        if (mb_strtolower(trim($validated['email'])) !== mb_strtolower(trim($record->email))) {
            return back()->withErrors(['email' => 'Wrong email account. Enter the email listed on your official student record. If you no longer have access to it, please visit the administrator to update your record.']);
        }

        $pending['email'] = $record->email;
        $pending['attempts'] = 0;
        unset($pending['email_mismatch'], $pending['official_email_missing']);

        return $this->send($request, $pending)
            ? back()->with('status', 'A new verification code was sent to the email on your official student record.')
            : back()->withErrors(['email' => 'The verification email could not be sent. Try again.']);
    }

    public function resend(Request $request, OtpController $legacy)
    {
        if (! $pending = $this->pending($request)) {
            if ($request->session()->has('reg_user_id')) {
                return $legacy->resend($request);
            }
            return $this->expired();
        }
        if ($pending['attempts'] >= OtpService::MAX_ATTEMPTS) {
            return back()->withErrors(['otp' => 'Too many incorrect attempts. Restart registration.']);
        }

        return $this->send($request, $pending)
            ? back()
            : back()->withErrors(['otp' => 'The verification email could not be sent. Try again.']);
    }

    public function store(Request $request, DtsRegistrationService $registration, OfficialStudentVerification $official, FraudDetectionService $fraud, OtpController $legacy)
    {
        if (! $pending = $this->pending($request)) {
            if ($request->session()->has('reg_user_id')) {
                return $legacy->store($request);
            }
            return $this->expired();
        }

        $request->validate(['otp' => 'required|digits:6']);
        if ($pending['attempts'] >= OtpService::MAX_ATTEMPTS) {
            return back()->withErrors(['otp' => 'Too many incorrect attempts. Restart registration.']);
        }
        if ($pending['otp_expires'] <= now()->timestamp) {
            return back()->withErrors(['otp' => 'The code has expired. Request a new code.']);
        }
        if (! Hash::check((string) $request->input('otp'), $pending['code'])) {
            $request->session()->put('registration_pending.attempts', $pending['attempts'] + 1);
            $this->logAttempt($request, 'otp_failed');
            return back()->withErrors(['otp' => 'Incorrect code.']);
        }

        $officialRecord = OfficialStudentRecord::query()
            ->where('student_id', $pending['student_id_number'])
            ->first();
        if ($officialRecord && ! filled($officialRecord->email)) {
            $request->session()->put('registration_pending.official_email_missing', true);
            $this->logAttempt($request, 'official_email_missing');
            return back()->withErrors(['otp' => 'Your email was verified, but your official student record has no email address. Please visit the administrator to update your record before continuing.']);
        }
        if ($officialRecord && mb_strtolower(trim($pending['email'])) !== mb_strtolower(trim($officialRecord->email))) {
            $request->session()->put('registration_pending.email_mismatch', true);
            $this->logAttempt($request, 'official_email_mismatch');
            return back()->withErrors(['otp' => 'Wrong email account. Your email was verified, but it does not match the email on your official student record. Enter the official email below to receive a new code. If you no longer have access to it, please visit the administrator to update your record.']);
        }

        $scan = $request->session()->get('registration_scan');
        $student = new User([
            ...\Illuminate\Support\Arr::only($pending, ['name', 'email', 'student_id_number', 'department_id', 'course_id', 'year_level_id', 'password']),
            'role' => 'voter',
            'email_verified_at' => now(),
            'email_status' => 'verified',
            'email_send_status' => 'sent',
            'ocr_status' => 'completed',
            'ocr_name' => $scan['name'],
            'ocr_student_id' => $scan['student_id'],
            'ocr_course' => $scan['course'],
            'image_quality' => $scan['quality'],
        ]);

        $local = Storage::disk('local');
        $public = Storage::disk('public');
        $imagePath = 'id-cards/'.basename($scan['path']);
        $reviewImagePath = null;
        $flaggedForFraudReview = false;
        try {
            $reason = DB::transaction(function () use ($request, $student, $pending, $registration, $official, $fraud, $scan, $imagePath, $local, $public, &$reviewImagePath, &$flaggedForFraudReview) {
                // Serialize account decisions so each one rechecks names and allocates a fresh voter ID.
                SchoolYearSetting::query()->whereKey(SchoolYearSetting::current()->getKey())->lockForUpdate()->first();
                DB::table('official_student_sync_state')->where('id', 1)->sharedLock()->first();
                $reason = $registration->isOpen()
                    ? $official->rejectionReason($student, $pending['section'])
                    : 'registration_closed';
                if ($this->isUnlistedRecordReason($reason)) {
                    $reviewImagePath = 'unlisted-registration-ids/'.\Illuminate\Support\Str::uuid().'.'.pathinfo($scan['path'], PATHINFO_EXTENSION);
                    if (! $local->copy($scan['path'], $reviewImagePath)) {
                        throw new \RuntimeException('The registration review image could not be saved.');
                    }

                    DB::table('unlisted_registrations')->insert([
                        'student_id' => \App\Models\OfficialStudentRecord::formattedStudentId($student->student_id_number),
                        'name' => $student->name,
                        'email' => $student->email,
                        'department' => $student->department?->name,
                        'course' => $student->course?->name,
                        'year_level' => $student->yearLevel?->name,
                        'section' => $pending['section'],
                        'reason' => $this->unlistedReasonNote($reason),
                        'submitted_at' => now(),
                        'id_image_path' => $reviewImagePath,
                    ]);
                }
                if ($reason) {
                    return $reason;
                }

                $fraudBreakdown = $fraud->calculate($student, [
                    'device_fingerprint' => $this->fingerprint($request),
                    'image_quality' => $scan['quality'],
                ]);
                $student->fraud_score = $fraudBreakdown->total;
                $flaggedForFraudReview = $student->fraud_score <= 80;
                $student->fraud_review_checks = array_values(array_filter(
                    $fraudBreakdown->items,
                    fn (array $check) => in_array($check['rule'], ['multiple_device_attempts', 'too_many_otp_failures'], true)
                        || ($flaggedForFraudReview && $check['points'] < 0 && $check['rule'] !== 'blurry_image'),
                ));

                if (User::nameIsTaken($student->name)) {
                    return 'duplicate_name';
                }
                $student->forceFill([
                    'voter_id_number' => $pending['voter_id_number'] ?? User::generateVoterIdNumber(),
                    'id_image_path' => $imagePath,
                    'section' => $pending['section'],
                    'registration_status' => User::STATUS_ACTIVE,
                    'verification_status' => $flaggedForFraudReview ? 'pending_review' : 'approved',
                    'is_verified' => ! $flaggedForFraudReview,
                    'verified_at' => $flaggedForFraudReview ? null : now(),
                    'fraud_review_flagged' => $flaggedForFraudReview,
                ]);
                try {
                    DB::transaction(fn () => $student->save());
                } catch (UniqueConstraintViolationException) {
                    return 'registration_duplicate';
                }
                $student->applyCourseExpiry();
                $student->lockYearLevelForCurrentSchoolYear();
                $image = $local->readStream($scan['path']);
                if (! is_resource($image) || ! $public->put($imagePath, $image)) {
                    throw new \RuntimeException('The approved registration image could not be saved.');
                }
                $this->logAttempt($request, $flaggedForFraudReview ? 'registration_flagged_for_review' : 'registration_approved', $student->id);
                return null;
            });
        } catch (\Throwable $exception) {
            if ($reviewImagePath !== null) {
                $local->delete($reviewImagePath);
            }
            $public->delete($imagePath);
            throw $exception;
        }

        if ($reason !== null) {
            $this->logAttempt($request, $reason);
        }
        if ($this->isUnlistedRecordReason($reason)) {
            app(\App\Services\AdminSystemNotifier::class)->notify(
                'Registration needs review',
                'A student registration attempt needs administrator review.',
                '/voters/unlisted-registrations',
                'registration_review',
            );
        } elseif ($reason === null) {
            app(\App\Services\AdminSystemNotifier::class)->notify(
                $flaggedForFraudReview ? 'New registration flagged for review' : 'New student registered',
                $flaggedForFraudReview
                    ? 'A student account was created and is waiting for administrator review.'
                    : 'A student account completed registration.',
                $flaggedForFraudReview ? '/voters?status=pending_review' : '/voters',
                $flaggedForFraudReview ? 'registration_review' : 'new_registration',
            );
        }
        $local->delete($scan['path']);
        $request->session()->forget(['registration_scan', 'registration_pending']);
        $request->session()->flash('registration_decision', [
            'success' => $reason === null && ! $flaggedForFraudReview,
            'pendingReview' => $flaggedForFraudReview,
            'message' => $this->decisionMessage($reason, $flaggedForFraudReview),
            'voterIdNumber' => $reason === null && ! $flaggedForFraudReview ? $student->voter_id_number : null,
        ]);

        return redirect()->route('register.decision');
    }

    public function decision(Request $request)
    {
        $decision = $request->session()->get('registration_decision');
        return $decision ? Inertia::render('Auth/EmailVerificationResult', $decision + ['registrationDecision' => true]) : $this->expired();
    }

    public function logAttempt(Request $request, string $action, ?int $userId = null): void
    {
        RegistrationAttempt::create([
            'device_fingerprint' => $this->fingerprint($request),
            'ip_address' => $request->ip(),
            'action' => $action,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }

    private function fingerprint(Request $request): string
    {
        return hash('sha256', $request->ip().'|'.$request->userAgent());
    }

    private function decisionMessage(?string $reason, bool $flaggedForFraudReview = false): string
    {
        if ($reason === null) {
            return $flaggedForFraudReview
                ? 'Your email and student details were received, but the system detected a signal that needs administrator review. Your account is pending approval.'
                : 'Your voter account was approved. You can now log in.';
        }

        return match ($reason) {
            'registration_closed' => 'Registration is currently closed. Please contact the administrator for the registration schedule.',
            'official_record_missing' => 'The Student ID and name you entered could not be matched to a student on the official list. Check your details or contact the administrator to correct your official record.',
            'official_record_conflict_student_id' => 'Your name matches a student on the official list, but the Student ID you entered does not match that record. Check the ID or contact the administrator to correct your official record.',
            'official_record_removed' => 'Your official student record is marked as removed. Contact the administrator to confirm your current enrollment.',
            'official_record_email_missing' => 'Your official student record has no email address. Contact the administrator to update your official record before registering.',
            'official_record_conflict_email' => 'The email you entered does not match the email on the official student list. Enter the same email or contact the administrator to correct your record.',
            'official_record_conflict_year_level' => 'Your Student ID and name match the official list, but your year level does not match the official student record. Check your year level or contact the administrator to correct your record.',
            'official_record_conflict_section' => 'Your Student ID and name match the official list, but your section does not match the official student record. Check your section or contact the administrator to correct your record.',
            'official_record_conflict_year_level_and_section' => 'Your Student ID and name match the official list, but your year level and section do not match the official student record. Check both details or contact the administrator to correct your record.',
            'official_record_conflict_full_name' => 'Your Student ID matches a record on the official list, but the name you entered does not match that record. Check the spelling or contact the administrator to correct your official record.',
            'official_record_conflict_department' => 'The department you selected does not match the official student record. Select the correct department or contact the administrator to update your record.',
            'official_record_conflict_course' => 'The course you selected does not match the official student record. Select the correct course or contact the administrator to update your record.',
            'official_record_ocr_conflict_name' => 'Your typed name matches the official record, but the name read from the uploaded document does not. Check your upload or contact the administrator.',
            'official_record_ocr_conflict_student_id' => 'Your typed Student ID matches the official record, but the ID read from the uploaded document does not. Check your upload or contact the administrator.',
            'official_record_ocr_conflict_course' => 'The course read from your uploaded ID does not match the official student record. Upload a clearer image of your ID or contact the administrator.',
            'registration_duplicate' => 'An account with this Student ID or email already exists. Use the existing account or contact the administrator if you believe this is an error.',
            'duplicate_name' => 'An account with this name is already registered. Contact the administrator if this is a different student with the same name.',
            default => 'Your registration could not be approved because the submitted information could not be verified. Contact the administrator and ask them to review your enrollment and ID details.',
        };
    }

    private function unlistedReasonNote(string $reason): string
    {
        return match ($reason) {
            'official_record_conflict_student_id' => 'Student ID does not match the official record (name matched)',
            'official_record_conflict_full_name' => 'Name does not match the official record (Student ID matched)',
            'official_record_conflict_year_level' => 'Wrong year level',
            'official_record_conflict_section' => 'Wrong section',
            'official_record_conflict_year_level_and_section' => 'Wrong year level and section',
            'official_record_ocr_conflict_name' => 'Uploaded document name does not match the official record',
            'official_record_ocr_conflict_student_id' => 'Uploaded document Student ID does not match the official record',
            'official_record_conflict_department' => 'Wrong department',
            'official_record_conflict_course' => 'Wrong course',
            default => 'Student ID not found on official list',
        };
    }

    private function isUnlistedRecordReason(?string $reason): bool
    {
        return in_array($reason, [
            'official_record_missing',
            'official_record_conflict_student_id',
            'official_record_conflict_full_name',
            'official_record_conflict_department',
            'official_record_conflict_course',
            'official_record_conflict_year_level',
            'official_record_conflict_section',
            'official_record_conflict_year_level_and_section',
            'official_record_ocr_conflict_name',
            'official_record_ocr_conflict_student_id',
            'official_record_ocr_conflict_course',
        ], true);
    }

    private function pending(Request $request): ?array
    {
        $pending = $request->session()->get('registration_pending');
        return $pending && $request->session()->get('registration_scan.expires', 0) > now()->timestamp ? $pending : null;
    }

    private function expired()
    {
        return redirect()->route('register')->with('error', 'Session expired. Scan your ID to start again.');
    }
}
