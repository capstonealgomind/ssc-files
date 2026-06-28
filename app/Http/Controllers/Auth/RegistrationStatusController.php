<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegistrationStatusController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/RegistrationStatus', [
            'emailStatus' => $user->email_status,
            'ocrStatus' => $user->ocr_status,
            'verificationStatus' => $user->verification_status,
            'isVerified' => $user->is_verified,
            'fraudScore' => $user->fraud_score,
            'voterIdNumber' => $user->voter_id_number,
        ]);
    }
}
