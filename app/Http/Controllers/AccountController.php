<?php

namespace App\Http\Controllers;

use App\Mail\AccountCredentialsMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Accounts', [
            'admins' => $this->adminAccounts(),
            'committees' => $this->committeeAccounts(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'contact_email' => $this->contactEmailRules(),
        ]);

        $loginEmail = $this->generateUniqueSystemEmail($validated['name'], 'admin');
        $plainPassword = $this->generateTemporaryPassword();

        $user = User::create([
            'name'          => $validated['name'],
            'email'         => $loginEmail,
            'contact_email' => $validated['contact_email'],
            'password'      => Hash::make($plainPassword),
            'role'          => 'admin',
        ]);

        return $this->sendCredentialsAndRedirect(
            user: $user,
            contactEmail: $validated['contact_email'],
            loginEmail: $loginEmail,
            plainPassword: $plainPassword,
            accountType: 'admin',
        );
    }

    public function storeCommittee(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'contact_email' => $this->contactEmailRules(),
        ]);

        $loginEmail = $this->generateUniqueSystemEmail($validated['name'], 'committee');
        $plainPassword = $this->generateTemporaryPassword();

        $user = User::create([
            'name'          => $validated['name'],
            'email'         => $loginEmail,
            'contact_email' => $validated['contact_email'],
            'password'      => Hash::make($plainPassword),
            'role'          => 'committee',
        ]);

        return $this->sendCredentialsAndRedirect(
            user: $user,
            contactEmail: $validated['contact_email'],
            loginEmail: $loginEmail,
            plainPassword: $plainPassword,
            accountType: 'committee',
        );
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdminAccount($user);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'contact_email' => array_merge(['nullable'], $this->contactEmailRules(required: false)),
            'password'      => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->update([
            'name'          => $validated['name'],
            'contact_email' => $validated['contact_email'] ?: $user->contact_email,
        ]);

        if (! empty($validated['password'])) {
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

    public function destroyCommittee(User $user): RedirectResponse
    {
        $this->ensureCommitteeAccount($user);

        $user->delete();

        return redirect()->route('accounts')
            ->with('success', 'Committee account deleted successfully.');
    }

    private function sendCredentialsAndRedirect(
        User $user,
        string $contactEmail,
        string $loginEmail,
        string $plainPassword,
        string $accountType,
    ): RedirectResponse {
        $label = $accountType === 'admin' ? 'Admin' : 'Committee';

        try {
            Mail::to($contactEmail)->send(new AccountCredentialsMail(
                recipientName: $user->name,
                loginEmail: $loginEmail,
                plainPassword: $plainPassword,
                loginUrl: route('login'),
                accountType: $accountType,
            ));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('accounts')
                ->with('error', "{$label} account was created, but the credentials email could not be sent. Please share the login details manually.");
        }

        return redirect()->route('accounts')
            ->with('success', "{$label} account created and credentials sent to {$contactEmail}.");
    }

    private function contactEmailRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'email',
            'max:255',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! $value) {
                    return;
                }

                if (User::isSystemEmail((string) $value)) {
                    $fail('Use a personal contact email, not an @'.User::ADMIN_EMAIL_DOMAIN.' or @'.User::COMMITTEE_EMAIL_DOMAIN.' address.');
                }
            },
        ];
    }

    private function ensureAdminAccount(User $user): void
    {
        if ($user->role !== 'admin') {
            abort(404);
        }
    }

    private function ensureCommitteeAccount(User $user): void
    {
        if ($user->role !== 'committee') {
            abort(404);
        }
    }

    private function adminAccounts(): array
    {
        return User::query()
            ->where('role', 'admin')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'contact_email', 'role', 'profile_photo_path', 'created_at'])
            ->map(fn (User $user) => [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'contact_email'     => $user->contact_email,
                'email_local'       => User::adminEmailLocalPart($user->email),
                'role'              => $user->role,
                'profile_photo_url' => $user->profilePhotoUrl(),
                'created_at'        => $user->created_at?->format('M d, Y'),
            ])
            ->values()
            ->all();
    }

    private function committeeAccounts(): array
    {
        return User::query()
            ->where('role', 'committee')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'contact_email', 'role', 'created_at'])
            ->map(fn (User $user) => [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'contact_email' => $user->contact_email,
                'role'          => $user->role,
                'created_at'    => $user->created_at?->format('M d, Y'),
            ])
            ->values()
            ->all();
    }

    private function generateUniqueSystemEmail(string $name, string $type): string
    {
        $fallback = $type === 'admin' ? 'admin' : 'committee';
        $base = Str::lower(Str::slug($name, '.'));
        $base = preg_replace('/[^a-z0-9._-]/', '', $base) ?: $fallback;
        $base = Str::limit($base, 48, '');

        $local = $base;
        $attempt = 0;

        $build = $type === 'admin'
            ? fn (string $part) => User::buildAdminEmail($part)
            : fn (string $part) => User::buildCommitteeEmail($part);

        while (User::where('email', $build($local))->exists()) {
            $attempt++;
            $suffix = $attempt > 1 ? (string) $attempt : Str::lower(Str::random(4));
            $local = Str::limit($base, 48 - strlen($suffix) - 1, '').'.'.$suffix;
        }

        return $build($local);
    }

    private function generateTemporaryPassword(): string
    {
        return Str::password(12, letters: true, numbers: true, symbols: false, spaces: false);
    }
}
