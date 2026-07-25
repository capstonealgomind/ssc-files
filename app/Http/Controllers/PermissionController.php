<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CommitteePageCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function index(): Response
    {
        $committees = User::query()
            ->where('role', 'committee')
            ->with('pagePermissions:id,user_id,page_key')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'contact_email' => $user->contact_email,
                'profile_photo_url' => $user->profilePhotoUrl(),
                'allowed_pages' => $user->allowedPages(),
            ]);

        return Inertia::render('Permissions', [
            'committees' => $committees,
            'pages' => CommitteePageCatalog::forFrontend(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'committee', 404);

        $validated = $request->validate([
            'pages' => ['present', 'array'],
            'pages.*' => ['string', Rule::in(CommitteePageCatalog::keys())],
        ]);

        $keys = collect($validated['pages'])->unique()->values()->all();

        $user->pagePermissions()->delete();

        foreach ($keys as $pageKey) {
            $user->pagePermissions()->create(['page_key' => $pageKey]);
        }

        return back()->with('success', 'Permissions updated for '.$user->name.'.');
    }
}
