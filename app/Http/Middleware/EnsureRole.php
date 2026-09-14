<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Check that the authenticated user has one of the required roles.
     *
     * Usage in routes:  ->middleware('role:admin')
     *                   ->middleware('role:admin,leader')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        // Not logged in — let the 'auth' middleware handle the redirect
        if (! $user) {
            return redirect()->route('login')
                ->with('status', 'Please log in to continue.');
        }

        // Logged in but doesn't have the required role
        if (! in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to perform this action.'], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access that page.');
        }

        return $next($request);
    }
}
