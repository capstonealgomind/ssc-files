<?php

namespace App\Http\Controllers;

use App\Models\SchoolYearSetting;
use App\Models\User;
use App\Services\FraudDetectionService;
use App\Services\OcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VoterController extends Controller
{
    public function index(): Response
    {
        $voters = User::where('role', 'voter')
            ->with(['department:id,name', 'course:id,name', 'yearLevel:id,name,sort_order'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (User $u) => $this->summarize($u))
            ->values()
            ->all();

        return Inertia::render('Voters', [
            'voters' => $voters,
        ]);
    }

    public function show(User $voter): Response
    {
        abort_if($voter->role !== 'voter', 404);

        $voter->load(['department:id,name,color', 'course:id,name', 'yearLevel:id,name,sort_order']);

        return Inertia::render('VoterDetail', [
            'voter' => $this->detail($voter),
            'nextRiskVoter' => $this->nextHighCriticalVoter($voter),
            'riskQueueRemaining' => $this->highCriticalRemainingAfter($voter),
        ]);
    }

    public function exists(User $voter): JsonResponse
    {
        abort_if($voter->role !== 'voter', 404);

        return response()->json(['exists' => true]);
    }

    public function verify(User $voter): RedirectResponse
    {
        abort_if($voter->role !== 'voter', 404);

        $startYear = (int) SchoolYearSetting::current()->start_year;

        $voter->update([
            'is_verified' => true,
            'verified_at' => $voter->verified_at ?? now(),
            'year_level_updated_school_year_start' => $startYear > 0
                ? $startYear
                : $voter->year_level_updated_school_year_start,
        ]);
        $voter->load(['course', 'yearLevel']);
        $voter->applyCourseExpiry();

        return back()->with('success', "{$voter->name} has been verified successfully.");
    }

    public function rerunOcr(User $voter): RedirectResponse
    {
        abort_if($voter->role !== 'voter', 404);

        if (!$voter->id_image_path) {
            return back()->withErrors(['ocr' => 'No ID image on file for this voter.']);
        }

        $imagePath = Storage::disk('public')->path($voter->id_image_path);
        $ocr       = app(OcrService::class)->extract($imagePath);

        if (!$ocr->available) {
            return back()->with('error', 'OCR could not extract text from the saved ID image.');
        }

        $voter->update([
            'ocr_name'       => $ocr->name       ?? $voter->ocr_name,
            'ocr_student_id' => $ocr->studentId  ?? $voter->ocr_student_id,
            'ocr_course'     => $ocr->course      ?? $voter->ocr_course,
        ]);

        // Recalculate fraud score
        $breakdown = app(FraudDetectionService::class)->calculate($voter->fresh(), [
            'device_fingerprint' => '',
            'image_quality'      => $voter->fresh()->image_quality ?? 'good',
        ]);
        $voter->update(['fraud_score' => $breakdown->total]);

        return back()->with('success', 'OCR re-processed. Name: '.($ocr->name ?? '—').', Student ID: '.($ocr->studentId ?? '—'));
    }

    public function reject(User $voter): RedirectResponse
    {
        abort_if($voter->role !== 'voter', 404);

        $voter->update([
            'is_verified' => false,
            'verified_at' => null,
        ]);

        return back()->with('success', "{$voter->name}'s verification has been removed.");
    }

    public function destroy(Request $request, User $voter): RedirectResponse
    {
        abort_if($voter->role !== 'voter', 404);

        $this->validateDeleteConfirmation($request);

        $name = $voter->name;
        $this->purgeVoter($voter);

        return redirect()
            ->route('voters')
            ->with('success', "{$name} has been permanently deleted.");
    }

    public function destroyMany(Request $request): RedirectResponse
    {
        $this->validateDeleteConfirmation($request);

        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['integer', 'distinct'],
        ], [
            'ids.required' => 'Select at least one student to delete.',
            'ids.min'      => 'Select at least one student to delete.',
        ]);

        $ids = array_values(array_unique($validated['ids']));

        $voters = User::query()
            ->where('role', 'voter')
            ->whereIn('id', $ids)
            ->get();

        if ($voters->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'ids' => 'One or more selected students could not be deleted. Refresh and try again.',
            ]);
        }

        $count = $voters->count();

        DB::transaction(function () use ($voters) {
            foreach ($voters as $voter) {
                $this->purgeVoter($voter);
            }
        });

        return redirect()
            ->route('voters')
            ->with('success', $count === 1
                ? '1 student has been permanently deleted.'
                : "{$count} students have been permanently deleted.");
    }

    private function validateDeleteConfirmation(Request $request): void
    {
        $request->validate([
            'confirmation' => ['required', 'in:DELETE'],
        ], [
            'confirmation.required' => 'Type DELETE to confirm permanent deletion.',
            'confirmation.in'       => 'Type DELETE exactly (all caps) to confirm.',
        ]);
    }

    private function purgeVoter(User $voter): void
    {
        if ($voter->id_image_path) {
            Storage::disk('public')->delete($voter->id_image_path);
        }

        if ($voter->profile_photo_path) {
            Storage::disk('public')->delete($voter->profile_photo_path);
        }

        $voter->delete();
    }

    // ── Formatters ────────────────────────────────────────────────────────

    /**
     * HIGH = fraud_score 20–49, CRITICAL = fraud_score < 20.
     * Next button only applies while reviewing those queues.
     *
     * @return array{id: int, name: string}|null
     */
    private function nextHighCriticalVoter(User $current): ?array
    {
        if (($current->fraud_score ?? 0) >= 50) {
            return null;
        }

        $ids = $this->highCriticalVoterIds();
        $index = $ids->search($current->id);

        if ($index === false) {
            return null;
        }

        $nextId = $ids->get($index + 1);
        if (! $nextId) {
            return null;
        }

        $next = User::query()->find($nextId, ['id', 'name']);

        return $next
            ? ['id' => $next->id, 'name' => $next->name]
            : null;
    }

    private function highCriticalRemainingAfter(User $current): int
    {
        if (($current->fraud_score ?? 0) >= 50) {
            return 0;
        }

        $ids = $this->highCriticalVoterIds();
        $index = $ids->search($current->id);

        if ($index === false) {
            return 0;
        }

        return max($ids->count() - ((int) $index) - 1, 0);
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function highCriticalVoterIds()
    {
        return User::query()
            ->where('role', 'voter')
            ->where('fraud_score', '<', 50)
            ->orderBy('fraud_score')
            ->orderBy('id')
            ->pluck('id');
    }

    private function summarize(User $u): array
    {
        return [
            'id'                  => $u->id,
            'name'                => $u->name,
            'email'               => $u->email,
            'voter_id_number'     => $u->voter_id_number,
            'student_id_number'   => $u->student_id_number,
            'department_id'       => $u->department_id,
            'department'          => $u->department?->name,
            'course_id'           => $u->course_id,
            'course'              => $u->course?->name,
            'year_level_id'       => $u->year_level_id,
            'year_level'          => $u->yearLevel?->name,
            'year_level_sort'     => $u->yearLevel?->sort_order,
            'school_year_updated' => $u->hasUpdatedYearLevelThisSchoolYear(),
            'school_year_label'   => SchoolYearSetting::current()->label(),
            'fraud_score'         => FraudDetectionService::syncVerificationScore($u),
            'is_verified'         => $u->is_verified,
            'verified_at'         => $u->verified_at?->format('M j, Y g:i A'),
            'email_verified'      => (bool) $u->email_verified_at,
            'is_expired'          => $u->isExpired(),
            'is_disabled'         => $u->isDisabled(),
            'account_expires_at'  => $u->account_expires_at?->format('M d, Y'),
            'registration_status' => $u->registration_status,
            'profile_photo_url'   => $u->profilePhotoUrl(),
            'created_at'          => $u->created_at->toDateTimeString(),
        ];
    }

    private function detail(User $u): array
    {
        $base = $this->summarize($u);

        return array_merge($base, [
            'id_image_url'         => $u->id_image_path ? asset('storage/'.$u->id_image_path) : null,
            'ocr_name'             => $u->ocr_name,
            'ocr_student_id'       => $u->ocr_student_id,
            'ocr_course'           => $u->ocr_course,
            'ocr_name_match'       => FraudDetectionService::namesMatch($u->ocr_name, $u->name),
            'ocr_student_id_match' => FraudDetectionService::studentIdMatches($u->ocr_student_id, $u->student_id_number),
            'ocr_course_match'     => FraudDetectionService::courseMatches($u->ocr_course, $u->course?->name),
            'ocr_available'          => (bool) $u->ocr_name || (bool) $u->ocr_student_id,
            'image_quality'          => $u->image_quality,
            'email_name_match'       => FraudDetectionService::emailMatchesName($u->email, $u->ocr_name ?? $u->name),
            'registered_at'          => $u->created_at->format('M j, Y g:i A'),
        ]);
    }
}
