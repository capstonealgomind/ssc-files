<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Accounts', [
            'admins' => $this->adminAccounts(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email_local' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'password'    => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = User::buildAdminEmail($validated['email_local']);

        if (User::where('email', $email)->exists()) {
            return back()->withErrors([
                'email_local' => 'This admin email is already taken.',
            ])->withInput();
        }

        User::create([
            'name'     => $validated['name'],
            'email'    => $email,
            'password' => Hash::make($validated['password']),
            'role'     => User::roleFromEmail($email),
        ]);

        return redirect()->route('accounts')
            ->with('success', 'Admin account created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdminAccount($user);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email_local' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'password'    => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = User::buildAdminEmail($validated['email_local']);

        if (User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
            return back()->withErrors([
                'email_local' => 'This admin email is already taken.',
            ]);
        }

        $user->update([
            'name'  => $validated['name'],
            'email' => $email,
            'role'  => User::roleFromEmail($email),
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        return redirect()->route('accounts')
            ->with('success', 'Admin account updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdminAccount($user);

        if ($request->user()->id === $user->id) {
            return redirect()->route('accounts')
                ->with('error', 'You cannot delete your own account.');
        }

        if (User::where('role', 'admin')->count() <= 1) {
            return redirect()->route('accounts')
                ->with('error', 'Cannot delete the last admin account.');
        }

        $user->delete();

        return redirect()->route('accounts')
            ->with('success', 'Admin account deleted successfully.');
    }

    private function ensureAdminAccount(User $user): void
    {
        if ($user->role !== 'admin') {
            abort(404);
        }
    }

    private function adminAccounts(): array
    {
        return User::query()
            ->where('role', 'admin')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'created_at'])
            ->map(fn (User $user) => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'email_local' => User::adminEmailLocalPart($user->email),
                'role'        => $user->role,
                'created_at'  => $user->created_at?->format('M d, Y'),
            ])
            ->values()
            ->all();
    }
}
