<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\OcrService;
use App\Services\FraudDetectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessOcrVerification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [60, 300, 900];

    public function __construct(
        public int $userId,
    ) {}

    public function handle(OcrService $ocr, FraudDetectionService $fraud): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            Log::warning("OCR job: User {$this->userId} not found");
            return;
        }

        $user->update(['ocr_status' => 'processing']);

        try {
            $imagePath = Storage::disk('public')->path($user->id_image_path);
            $result = $ocr->extract($imagePath);

            if (!$result->available) {
                Log::warning("OCR unavailable for user {$user->id}, will retry");
                $user->update(['ocr_status' => 'processing']);
                throw new \Exception('OCR service unavailable');
            }

            $user->update([
                'ocr_name' => $result->name,
                'ocr_student_id' => $result->studentId,
                'ocr_course' => $result->course,
                'ocr_status' => 'completed',
            ]);

            $breakdown = $fraud->calculate($user->fresh(), [
                'device_fingerprint' => '',
                'image_quality' => $user->image_quality ?? 'good',
            ]);

            $user->update(['fraud_score' => $breakdown->total]);

            if ($breakdown->total >= 80) {
                $user->update([
                    'verification_status' => 'approved',
                    'is_verified' => true,
                ]);
                Log::info("User {$user->id} auto-verified (score: {$breakdown->total})");
            } else {
                $user->update(['verification_status' => 'pending']);
                Log::info("User {$user->id} pending admin review (score: {$breakdown->total})");
            }
        } catch (\Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $user->update(['ocr_status' => 'failed']);
                Log::error("OCR failed permanently for user {$user->id}: " . $e->getMessage());
            } else {
                throw $e;
            }
        }
    }
}
