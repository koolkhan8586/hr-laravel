<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SalaryAccessMiddleware
{
    /**
     * Admins always get in; anyone else needs the salary permission
     * granted on their staff record.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->role !== 'admin' && !$user->can_manage_salary) {
            abort(403, 'You do not have access to salary management.');
        }

        return $next($request);
    }
}
