<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FraudDetectionService;
use App\Services\OcrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class VoterController extends Controller
{
    public function index(): Response
    {
        $voters = User::where('role', 'voter')
            ->with(['department:id,name', 'course:id,name', 'yearLevel:id,name'])
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

        $voter->load(['department:id,name,color', 'course:id,name', 'yearLevel:id,name']);

        return Inertia::render('VoterDetail', [
            'voter' => $this->detail($voter),
        ]);
    }

    public function verify(User $voter): RedirectResponse
    {
        abort_if($voter->role !== 'voter', 404);

        $voter->update(['is_verified' => true]);
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

        $voter->update(['is_verified' => false]);

        return back()->with('success', "{$voter->name}'s verification has been removed.");
    }

    // ── Formatters ────────────────────────────────────────────────────────

    private function summarize(User $u): array
    {
        return [
            'id'                  => $u->id,
            'name'                => $u->name,
            'email'               => $u->email,
            'voter_id_number'     => $u->voter_id_number,
            'student_id_number'   => $u->student_id_number,
            'department'          => $u->department?->name,
            'course'              => $u->course?->name,
            'year_level'          => $u->yearLevel?->name,
            'fraud_score'         => $u->fraud_score ?? 0,
            'is_verified'         => $u->is_verified,
            'email_verified'      => (bool) $u->email_verified_at,
            'registration_status' => $u->registration_status,
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
            'ocr_name_match'       => $this->nameMatches($u->ocr_name, $u->name),
            'ocr_student_id_match' => $this->idMatches($u->ocr_student_id, $u->student_id_number),
            'ocr_course_match'     => $this->courseMatches($u->ocr_course, $u->course?->name),
            'ocr_available'          => (bool) $u->ocr_name || (bool) $u->ocr_student_id,
            'image_quality'          => $u->image_quality,
            'email_name_match'       => $this->emailMatchesName($u->email, $u->ocr_name ?? $u->name),
            'registered_at'          => $u->created_at->format('M j, Y g:i A'),
        ]);
    }

    private function emailMatchesName(?string $email, ?string $name): bool
    {
        if (!$email || !$name) {
            return false;
        }

        $emailLocal = strtolower(preg_replace('/[^a-zA-Z]/', '', explode('@', $email)[0]));
        $parts      = array_filter(
            preg_split('/\s+/', mb_strtolower($name)),
            fn ($p) => mb_strlen($p) >= 3,
        );

        foreach ($parts as $part) {
            if (str_contains($emailLocal, $part)) {
                return true;
            }
        }

        return false;
    }

    private function nameMatches(?string $ocr, ?string $typed): bool
    {
        if (!$ocr || !$typed) {
            return false;
        }
        similar_text(mb_strtolower($ocr), mb_strtolower($typed), $pct);
        return $pct >= 70;
    }

    private function idMatches(?string $ocr, ?string $typed): bool
    {
        if (!$ocr || !$typed) {
            return false;
        }
        return preg_replace('/[\s\-]/', '', $ocr) === preg_replace('/[\s\-]/', '', $typed);
    }

    private function courseMatches(?string $ocr, ?string $typed): bool
    {
        if (!$ocr || !$typed) {
            return false;
        }
        similar_text(mb_strtolower($ocr), mb_strtolower($typed), $pct);
        return $pct >= 55;
    }
}
