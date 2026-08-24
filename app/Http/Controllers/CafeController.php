<?php

namespace App\Http\Controllers;

use App\Services\CafeSsoService;
use Illuminate\Http\RedirectResponse;

class CafeController extends Controller
{
    /**
     * Open Cafe LSAF. When SSO is configured and the employee has a code,
     * land them signed-in; otherwise send them to the Cafe login page.
     */
    public function launch(CafeSsoService $sso): RedirectResponse
    {
        $user = auth()->user();
        $code = trim((string) ($user->employee_code ?? ''));

        if ($code === '') {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Your profile has no employee code, so Cafe SSO cannot sign you in. Ask HR to set your employee code, then try again.');
        }

        if ($sso->configured()) {
            $url = $sso->launchUrl($user);

            if ($url) {
                return redirect()->away($url);
            }
        }

        // SSO not configured yet — still open Cafe so staff can PIN-login.
        $base = $sso->baseUrl() ?: 'https://cafe.khanmusa.com';

        return redirect()->away(rtrim($base, '/').'/login');
    }
}
