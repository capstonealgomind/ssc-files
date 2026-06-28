<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegistrationSuccessController extends Controller
{
    public function show(Request $request)
    {
        $voterId = $request->session()->get('reg_voter_id');

        if (!$voterId) {
            return redirect()->route('register');
        }

        return Inertia::render('Auth/RegistrationSuccess', [
            'voterIdNumber' => $voterId,
        ]);
    }
}
