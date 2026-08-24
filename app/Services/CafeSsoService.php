<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Build short-lived SSO URLs for cafe.khanmusa.com.
 *
 * Token format (must stay in sync with Cafe src/lib/hr-sso.ts):
 *   base64url(json).base64url(hmac-sha256)
 */
class CafeSsoService
{
    public function configured(): bool
    {
        $secret = (string) config('services.cafe.sso_secret');
        $base   = (string) config('services.cafe.base_url');

        return strlen($secret) >= 16 && filled($base);
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.cafe.base_url'), '/');
    }

    /**
     * Signed Cafe entry URL for this HR user, or null when SSO is off / no code.
     */
    public function launchUrl(User $user): ?string
    {
        if (! $this->configured()) {
            return null;
        }

        $code = strtoupper(trim((string) $user->employee_code));

        if ($code === '') {
            return null;
        }

        $now = time();

        $payload = [
            'code'  => $code,
            'name'  => $user->name,
            'iat'   => $now,
            'exp'   => $now + (int) config('services.cafe.sso_ttl', 120),
            'nonce' => Str::random(16),
        ];

        $body = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $sig  = $this->base64UrlEncode(
            hash_hmac('sha256', $body, (string) config('services.cafe.sso_secret'), true)
        );

        return $this->baseUrl().'/sso?token='.urlencode($body.'.'.$sig);
    }

    private function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
