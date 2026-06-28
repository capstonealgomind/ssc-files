<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessOcrVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, string $token)
    {
        $user = User::where('email_verify_token', $token)->first();

        if (!$user) {
            return Inertia::render('Auth/EmailVerificationResult', [
                'success' => false,
                'message' => 'Invalid verification link.',
            ]);
        }

        if ($user->email_status === 'verified') {
            return Inertia::render('Auth/EmailVerificationResult', [
                'success' => true,
                'message' => 'Your email is already verified.',
            ]);
        }

        $user->update([
            'email_status' => 'verified',
            'email_verified_at' => now(),
            'ocr_status' => 'processing',
        ]);

        ProcessOcrVerification::dispatch($user->id);

        return Inertia::render('Auth/EmailVerificationResult', [
            'success' => true,
            'message' => 'Email verified successfully! Your account is now being processed.',
        ]);
    }
}
