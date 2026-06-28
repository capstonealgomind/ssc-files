<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, string $token): Response|RedirectResponse
    {
        $user = User::where('email_verify_token', $token)->first();

        if (!$user) {
            return Inertia::render('Auth/EmailVerificationResult', [
                'success' => false,
                'message' => 'Invalid or expired verification link.',
            ]);
        }

        if ($user->email_status === 'verified') {
            return Inertia::render('Auth/EmailVerificationResult', [
                'success'       => true,
                'message'       => 'Your email is already verified.',
                'alreadyVerified' => true,
                'voterIdNumber' => $user->voter_id_number,
            ]);
        }

        $request->session()->put('reg_user_id', $user->id);
        $request->session()->put('reg_voter_id', $user->voter_id_number);

        return redirect()->route('register.verify-otp');
    }
}
