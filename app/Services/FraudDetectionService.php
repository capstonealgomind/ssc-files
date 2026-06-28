<?php

namespace App\Services;

use App\Models\RegistrationAttempt;
use App\Models\User;

class FraudScoreBreakdown
{
    public array $items = [];
    public int   $total = 0;

    public function add(string $rule, int $points, string $description): void
    {
        $this->items[] = compact('rule', 'points', 'description');
        $this->total  += $points;
    }
}

class FraudDetectionService
{
    /**
     * Calculate fraud score for a user after registration completion.
     *
     * @param  array{device_fingerprint: string, image_quality: string} $context
     */
    public function calculate(User $user, array $context): FraudScoreBreakdown
    {
        $breakdown = new FraudScoreBreakdown();

        // ── OCR matching ──────────────────────────────────────────────────
        if ($user->ocr_name && $user->name) {
            similar_text(
                mb_strtolower($user->ocr_name),
                mb_strtolower($user->name),
                $pct,
            );
            if ($pct >= 70) {
                $breakdown->add('ocr_name_match', +20, 'OCR name matches typed name');
            }
        }

        if ($user->ocr_student_id && $user->student_id_number) {
            $ocrId   = preg_replace('/[\s\-]/', '', $user->ocr_student_id);
            $typedId = preg_replace('/[\s\-]/', '', $user->student_id_number);
            if ($ocrId === $typedId) {
                $breakdown->add('student_id_match', +20, 'Student ID matches OCR');
            }
        }

        if ($user->ocr_course && $user->course) {
            similar_text(
                mb_strtolower($user->ocr_course),
                mb_strtolower($user->course->name ?? ''),
                $pct,
            );
            if ($pct >= 60) {
                $breakdown->add('course_match', +10, 'Course matches OCR');
            }
        }

        // ── Email verified (OTP completed) ────────────────────────────────
        if ($user->email_verified_at) {
            $breakdown->add('email_verified', +20, 'Email verified');
        }

        // ── Email ↔ Name resemblance ──────────────────────────────────────
        // Strip everything except letters from the email local part, then check
        // whether any meaningful word (≥3 chars) from the OCR name (or typed name
        // as fallback) appears in it.  e.g. "johnlloydblanquera2306@gmail.com"
        // → "johnlloydblanquera" contains "john", "lloyd", "blanquera" → +20
        $nameSource = $user->ocr_name ?? $user->name ?? '';
        if ($nameSource && $user->email) {
            $emailLocal  = strtolower(preg_replace('/[^a-zA-Z]/', '', explode('@', $user->email)[0]));
            $nameParts   = array_filter(
                preg_split('/\s+/', mb_strtolower($nameSource)),
                fn ($p) => mb_strlen($p) >= 3,
            );
            $emailMatches = false;
            foreach ($nameParts as $part) {
                if (str_contains($emailLocal, $part)) {
                    $emailMatches = true;
                    break;
                }
            }
            if ($emailMatches) {
                $breakdown->add('email_name_resemblance', +20, 'Email matches name');
            }
        }

        // ── Image quality ─────────────────────────────────────────────────
        $quality = $context['image_quality'] ?? null;
        if ($quality === 'good') {
            $breakdown->add('good_image', +10, 'Good image quality');
        } elseif ($quality === 'blurry') {
            $breakdown->add('blurry_image', -20, 'Blurry ID image');
        }
        // 'warn' = readable but soft → no bonus or penalty

        // ── Duplicate detection ───────────────────────────────────────────
        $emailCount = User::where('email', $user->email)
            ->where('id', '!=', $user->id)
            ->count();
        if ($emailCount > 0) {
            $breakdown->add('duplicate_email', -50, 'Same email already registered');
        }

        $idCount = User::where('student_id_number', $user->student_id_number)
            ->where('id', '!=', $user->id)
            ->count();
        if ($idCount > 0) {
            $breakdown->add('duplicate_student_id', -100, 'Same Student ID already exists');
        }

        // ── Device fingerprint ────────────────────────────────────────────
        $deviceAttempts = RegistrationAttempt::where('device_fingerprint', $context['device_fingerprint'])
            ->where('action', 'register')
            ->where('created_at', '>=', now()->subDay())
            ->count();
        if ($deviceAttempts > 1) {
            $breakdown->add('multiple_device_attempts', -30, 'Multiple registration attempts from same device');
        }

        // ── OTP failures ──────────────────────────────────────────────────
        $otpFailures = RegistrationAttempt::where('device_fingerprint', $context['device_fingerprint'])
            ->where('action', 'otp_failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($otpFailures >= 3) {
            $breakdown->add('too_many_otp_failures', -20, 'Too many failed OTP attempts');
        }

        return $breakdown;
    }

    public static function label(int $score): string
    {
        return match (true) {
            $score >= 80 => 'High confidence',
            $score >= 50 => 'Moderate – review recommended',
            $score >= 20 => 'Low – manual review required',
            default      => 'Flagged – likely fraudulent',
        };
    }
}
