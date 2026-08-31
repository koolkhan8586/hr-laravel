<?php

namespace App\Services;

use App\Models\Salary;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SalaryPostedWhatsAppNotifier
{
    public function __construct(private WahaService $waha)
    {
    }

    /**
     * @return array{ok: bool, skipped: bool, message: string}
     */
    public function notify(Salary $salary): array
    {
        $salary->loadMissing('user');
        $user = $salary->user;

        if (! $user) {
            return [
                'ok' => false,
                'skipped' => true,
                'message' => 'Employee not found.',
            ];
        }

        if (! $this->waha->enabled()) {
            return [
                'ok' => false,
                'skipped' => true,
                'message' => 'WhatsApp (WAHA) is disabled.',
            ];
        }

        if (! filled($user->mobile)) {
            return [
                'ok' => false,
                'skipped' => true,
                'message' => 'No mobile number on file.',
            ];
        }

        $ok = $this->waha->sendToMobile($user->mobile, $this->buildMessage($salary));

        if (! $ok) {
            Log::warning('Salary posted WhatsApp failed', [
                'salary_id' => $salary->id,
                'user_id'   => $user->id,
                'mobile'    => $user->mobile,
            ]);

            return [
                'ok' => false,
                'skipped' => false,
                'message' => 'WhatsApp could not be sent.',
            ];
        }

        return [
            'ok' => true,
            'skipped' => false,
            'message' => 'WhatsApp sent.',
        ];
    }

    public function buildMessage(Salary $salary): string
    {
        $salary->loadMissing('user');
        $user = $salary->user;

        $monthName = Carbon::create()->month((int) $salary->month)->format('F');
        $net       = number_format((float) $salary->net_salary, 2);
        $portalUrl = url('/salary');

        return implode("\n", [
            'Salary Slip Notification',
            '',
            'Dear '.$user->name.',',
            '',
            "Your salary for {$monthName} {$salary->year} has been posted.",
            '',
            'Net Salary: Rs '.$net,
            '',
            'View your payslip: '.$portalUrl,
            '',
            'HR Department',
        ]);
    }
}
