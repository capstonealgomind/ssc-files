<?php

namespace App\Http\Middleware;

use App\Support\CommitteePageCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCommitteePageAccess
{
    public function handle(Request $request, Closure $next, string $pageKey): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->role === 'admin') {
            return $next($request);
        }

        if ($user->role === 'committee' && CommitteePageCatalog::isValid($pageKey) && $user->canAccessPage($pageKey)) {
            return $next($request);
        }

        return redirect()->route('dashboard')
            ->with('error', 'You do not have permission to access this page.');
    }
}
