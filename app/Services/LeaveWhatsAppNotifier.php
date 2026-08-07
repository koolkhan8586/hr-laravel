<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\LeaveApprovalWhatsappNumber;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class LeaveWhatsAppNotifier
{
    public function __construct(protected WahaService $waha)
    {
    }

    public function notifyApprovers(Leave $leave): void
    {
        if (!$this->waha->enabled()) {
            return;
        }

        $leave->loadMissing('user');

        $numbers = LeaveApprovalWhatsappNumber::query()
            ->orderBy('id')
            ->get();

        if ($numbers->isEmpty()) {
            return;
        }

        $remainingBalance = $leave->type === 'annual'
            ? (LeaveBalance::where('user_id', $leave->user_id)->value('remaining_leaves') ?? 0)
            : null;

        foreach ($numbers as $recipient) {
            $message = $this->buildMessage($leave, $recipient->mobile, $remainingBalance);

            $ok = $this->waha->sendToMobile($recipient->mobile, $message);

            if (!$ok) {
                Log::warning('Leave approval WhatsApp notify failed', [
                    'leave_id' => $leave->id,
                    'mobile' => $recipient->mobile,
                ]);
            }
        }
    }

    protected function buildMessage(Leave $leave, string $mobile, ?float $remainingBalance): string
    {
        $expiresAt = now()->addHours(72);

        $approveUrl = URL::temporarySignedRoute('leave.email.decision', $expiresAt, [
            'id' => $leave->id,
            'decision' => 'approve',
            'email' => $mobile,
            'via' => 'whatsapp',
        ]);

        $rejectUrl = URL::temporarySignedRoute('leave.email.decision', $expiresAt, [
            'id' => $leave->id,
            'decision' => 'reject',
            'email' => $mobile,
            'via' => 'whatsapp',
        ]);

        $employee = $leave->user->name ?? 'Employee';
        $type = ucfirst(str_replace('_', ' ', $leave->type));
        $start = Carbon::parse($leave->start_date)->format('d M Y');
        $end = Carbon::parse($leave->end_date)->format('d M Y');
        $days = $leave->calculated_days;
        $reason = filled($leave->reason) ? $leave->reason : '-';

        $lines = [
            '📋 New Leave Application',
            '',
            'Employee: '.$employee,
            'Type: '.$type,
            'From: '.$start,
            'To: '.$end,
            'Days: '.$days,
            'Reason: '.$reason,
        ];

        if ($remainingBalance !== null) {
            $lines[] = 'Remaining leave balance: '.$remainingBalance.' day(s) (before this request)';
        } else {
            $lines[] = 'Remaining leave balance: N/A (without pay)';
        }

        $lines[] = '';
        $lines[] = '✅ Approve:';
        $lines[] = $approveUrl;
        $lines[] = '';
        $lines[] = '❌ Reject:';
        $lines[] = $rejectUrl;
        $lines[] = '';
        $lines[] = 'Links expire in 72 hours.';
        $lines[] = '— LSAF HR';

        return implode("\n", $lines);
    }
}
