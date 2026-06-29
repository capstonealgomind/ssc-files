<?php

namespace App\Jobs;

use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendVerificationEmail implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [30, 120, 300];

    public function __construct(
        public int $userId,
        public string $verifyUrl,
        public string $otp,
        public string $voterIdNumber,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            Log::warning("Verification email job: User {$this->userId} not found");
            return;
        }

        $user->update(['email_send_status' => 'processing']);

        try {
            Mail::to($user->email)->send(new VerifyEmail(
                $this->verifyUrl,
                $this->otp,
                $user->name,
                $this->voterIdNumber,
            ));

            $user->update(['email_send_status' => 'sent']);
            Log::info("Verification email sent for user {$user->id}");
        } catch (\Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $user->update(['email_send_status' => 'failed']);
                Log::error("Verification email failed permanently for user {$user->id}: " . $e->getMessage());
            } else {
                throw $e;
            }
        }
    }
}
