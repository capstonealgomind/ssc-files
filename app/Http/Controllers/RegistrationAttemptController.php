<?php

namespace App\Http\Controllers;

use App\Models\RegistrationAttempt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationAttemptController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $attempts = RegistrationAttempt::query()
            ->with('user:id,name,email,voter_id_number')
            ->latest('created_at')
            ->limit(300)
            ->get()
            ->map(fn (RegistrationAttempt $attempt) => [
                'id' => $attempt->id,
                'action' => $attempt->action,
                'ip_address' => $attempt->ip_address,
                'device_fingerprint' => $attempt->device_fingerprint,
                'user_name' => $attempt->user?->name,
                'user_email' => $attempt->user?->email,
                'voter_id_number' => $attempt->user?->voter_id_number,
                'created_at' => $attempt->created_at
                    ? $attempt->created_at->timezone(config('app.timezone'))->format('M d, Y g:i A')
                    : null,
            ])
            ->values()
            ->all();

        return Inertia::render('RegistrationAttempts', [
            'attempts' => $attempts,
        ]);
    }
}
