<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LoanAccessMiddleware
{
    /**
     * Admins and managers always get in; anyone else needs the loan
     * permission granted on their staff record.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (! $user->canManageLoans()) {
            abort(403, 'You do not have access to loan management.');
        }

        return $next($request);
    }
}
