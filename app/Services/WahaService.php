<?php

namespace App\Services;

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

    public function sendText(string $chatId, string $text): bool
    {
        if (!$this->enabled()) {
            Log::warning('WAHA is disabled or base URL is missing.');
            return false;
        }

        $url = rtrim(config('services.waha.base_url'), '/') . '/api/sendText';

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $apiKey = config('services.waha.api_key');
        if (filled($apiKey)) {
            $headers['X-Api-Key'] = $apiKey;
        }

        try {
            $response = Http::withHeaders($headers)
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
     * Mobiles configured for the daily Absent/Late/Leave WhatsApp report.
     *
     * @return array<int, string>
     */
    public function dailyReportMobiles(): array
    {
        return $this->parseMobileList(config('services.waha.daily_report_mobiles'));
    }
}
