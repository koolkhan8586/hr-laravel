<?php

namespace App\Services;

use App\Models\DailyReportWhatsappNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    public function enabled(): bool
    {
        return (bool) config('services.waha.enabled')
            && filled(config('services.waha.base_url'));
    }

    /**
     * Normalize a local/international phone number into WAHA chatId format.
     * Example: 03001234567 -> 923001234567@c.us
     */
    public function toChatId(?string $mobile): ?string
    {
        if (!$mobile) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $mobile);

        if (!$digits) {
            return null;
        }

        $countryCode = (string) config('services.waha.default_country_code', '92');

        // 03001234567 -> 3001234567 then prefix country code
        if (str_starts_with($digits, '0')) {
            $digits = $countryCode . substr($digits, 1);
        }

        // 3001234567 (10 digits, common PK mobile without leading 0)
        if (strlen($digits) === 10 && !str_starts_with($digits, $countryCode)) {
            $digits = $countryCode . $digits;
        }

        if (strlen($digits) < 10) {
            return null;
        }

        return $digits . '@c.us';
    }

    protected function headers(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $apiKey = config('services.waha.api_key');
        if (filled($apiKey)) {
            $headers['X-Api-Key'] = $apiKey;
        }

        return $headers;
    }

    public function sendText(string $chatId, string $text): bool
    {
        if (!$this->enabled()) {
            Log::warning('WAHA is disabled or base URL is missing.');
            return false;
        }

        $url = rtrim(config('services.waha.base_url'), '/') . '/api/sendText';

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout((int) config('services.waha.timeout', 20))
                ->post($url, [
                    'session' => config('services.waha.session', 'default'),
                    'chatId' => $chatId,
                    'text' => $text,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('WAHA sendText failed', [
                'chatId' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('WAHA sendText exception', [
                'chatId' => $chatId,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendToMobile(?string $mobile, string $text): bool
    {
        $chatId = $this->toChatId($mobile);

        if (!$chatId) {
            Log::warning('WAHA skipped: invalid mobile number', ['mobile' => $mobile]);
            return false;
        }

        return $this->sendText($chatId, $text);
    }

    /**
     * Check WAHA session status via GET /api/sessions/{session}.
     *
     * @return array{connected: bool, status: string, detail: string, me: ?string}
     */
    public function connectionStatus(): array
    {
        if (!$this->enabled()) {
            return [
                'connected' => false,
                'status' => 'DISABLED',
                'detail' => 'WAHA is disabled or WAHA_BASE_URL is missing in .env.',
                'me' => null,
            ];
        }

        $session = config('services.waha.session', 'default');
        $url = rtrim(config('services.waha.base_url'), '/') . '/api/sessions/' . rawurlencode($session);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout((int) config('services.waha.timeout', 20))
                ->get($url);

            if (!$response->successful()) {
                return [
                    'connected' => false,
                    'status' => 'ERROR',
                    'detail' => 'HTTP '.$response->status().': '.$response->body(),
                    'me' => null,
                ];
            }

            $data = $response->json() ?? [];
            $status = strtoupper((string) ($data['status'] ?? 'UNKNOWN'));
            $me = $data['me']['id'] ?? $data['me']['pushName'] ?? null;

            return [
                'connected' => $status === 'WORKING',
                'status' => $status,
                'detail' => $status === 'WORKING'
                    ? 'Session is working and ready to send messages.'
                    : 'Session status is '.$status.'. Scan QR or restart the session if needed.',
                'me' => $me,
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'status' => 'UNREACHABLE',
                'detail' => $e->getMessage(),
                'me' => null,
            ];
        }
    }

    /**
     * Parse a comma/space/newline separated list of mobiles from config or input.
     *
     * @return array<int, string>
     */
    public function parseMobileList(?string $raw): array
    {
        if (!filled($raw)) {
            return [];
        }

        $parts = preg_split('/[\s,;]+/', $raw) ?: [];

        return collect($parts)
            ->map(fn ($m) => trim($m))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Mobiles for the daily Absent/Late/Leave WhatsApp report.
     * Prefers active DB numbers from Settings; also merges any env fallback.
     *
     * @return array<int, string>
     */
    public function dailyReportMobiles(): array
    {
        $fromDb = DailyReportWhatsappNumber::query()
            ->where('is_active', true)
            ->pluck('mobile')
            ->all();

        $fromEnv = $this->parseMobileList(config('services.waha.daily_report_mobiles'));

        return collect($fromDb)
            ->merge($fromEnv)
            ->map(fn ($m) => trim((string) $m))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
