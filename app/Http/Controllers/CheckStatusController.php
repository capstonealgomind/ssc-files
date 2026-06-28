<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckStatusController extends Controller
{
    public function show()
    {
        return Inertia::render('CheckStatus');
    }

    public function check(Request $request)
    {
        $validated = $request->validate([
            'voter_id' => 'required|string',
        ]);

        $user = User::where('voter_id_number', $validated['voter_id'])->first();

        if (!$user) {
            return Inertia::render('CheckStatus', [
                'errors' => ['voter_id' => 'Voter ID not found. Please check and try again.'],
            ]);
        }

        return Inertia::render('CheckStatus', [
            'status' => [
                'voterIdNumber' => $user->voter_id_number,
                'name' => $user->name,
                'emailStatus' => $user->email_status ?? 'pending',
                'ocrStatus' => $user->ocr_status ?? 'pending',
                'verificationStatus' => $user->verification_status ?? 'pending',
                'isVerified' => (bool) $user->is_verified,
            ],
        ]);
    }
}
