<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class EmployeeCredentialsWhatsAppNotifier
{
    public function __construct(private WahaService $waha)
    {
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function sendWelcome(User $user, string $password): array
    {
        return $this->send($user, $password, 'welcome');
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function sendPasswordReset(User $user, string $password): array
    {
        return $this->send($user, $password, 'reset');
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function send(User $user, string $password, string $type): array
    {
        if (! $this->waha->enabled()) {
            return [
                'ok' => false,
                'message' => 'WhatsApp (WAHA) is disabled. Enable it in Settings / .env.',
            ];
        }

        if (! filled($user->mobile)) {
            return [
                'ok' => false,
                'message' => 'No mobile number on file for this employee.',
            ];
        }

        $text = $this->buildMessage($user, $password, $type);
        $ok   = $this->waha->sendToMobile($user->mobile, $text);

        if (! $ok) {
            $status = $this->waha->connectionStatus();

            Log::warning('Employee credentials WhatsApp failed', [
                'user_id' => $user->id,
                'mobile'  => $user->mobile,
                'type'    => $type,
                'waha'    => $status['status'] ?? 'unknown',
            ]);

            return [
                'ok' => false,
                'message' => 'WhatsApp could not be sent. '.$status['detail'],
            ];
        }

        return [
            'ok' => true,
            'message' => 'Credentials sent via WhatsApp.',
        ];
    }

    public function buildMessage(User $user, string $password, string $type = 'welcome'): string
    {
        $loginUrl = url('/login');
        $code     = $user->employee_code ?: '-';

        $intro = $type === 'reset'
            ? 'Your HR portal password has been reset.'
            : 'Welcome to the HR portal. Your account has been created.';

        return implode("\n", [
            $intro,
            '',
            'Login URL: '.$loginUrl,
            'Employee Code: '.$code,
            'Email: '.$user->email,
            'Password: '.$password,
            '',
            'Please log in and change your password.',
        ]);
    }
}
