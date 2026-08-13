<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisabledAccountController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'voter' || ! $user->isDisabled()) {
            return redirect()->route('dashboard');
        }

        $user->loadMissing(['department:id,name', 'course:id,name', 'yearLevel:id,name']);

        return Inertia::render('DisabledAccount', [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'voter_id_number' => $user->voter_id_number,
                'student_id_number' => $user->student_id_number,
                'department' => $user->department?->name,
                'course' => $user->course?->name,
                'year_level' => $user->yearLevel?->name,
                'profile_photo_url' => $user->profilePhotoUrl(),
                'status_label' => 'Disabled',
            ],
        ]);
    }
}
