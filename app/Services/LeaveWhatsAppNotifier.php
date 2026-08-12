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

    /**
     * Send leave approval WhatsApp messages to all configured numbers.
     *
     * @return array{ok: bool, sent: int, failed: int, total: int, status: string, message: string}
     */
    public function notifyApprovers(Leave $leave): array
    {
        if (!$this->waha->enabled()) {
            $result = [
                'ok' => false,
                'sent' => 0,
                'failed' => 0,
                'total' => 0,
                'status' => 'skipped',
                'message' => 'WAHA is disabled. Enable it in Settings / .env to send WhatsApp notifications.',
            ];
            $this->persistStatus($leave, $result);

            return $result;
        }

        $leave->loadMissing('user');

        $numbers = LeaveApprovalWhatsappNumber::query()
            ->orderBy('id')
            ->get();

        if ($numbers->isEmpty()) {
            $result = [
                'ok' => false,
                'sent' => 0,
                'failed' => 0,
                'total' => 0,
                'status' => 'skipped',
                'message' => 'No leave approval WhatsApp numbers are configured.',
            ];
            $this->persistStatus($leave, $result);

            return $result;
        }

        $remainingBalance = $leave->type === 'annual'
            ? (LeaveBalance::where('user_id', $leave->user_id)->value('remaining_leaves') ?? 0)
            : null;

        $sent = 0;
        $failed = 0;

        foreach ($numbers as $recipient) {
            $message = $this->buildMessage($leave, $recipient->mobile, $remainingBalance);

            $ok = $this->waha->sendToMobile($recipient->mobile, $message);

            if ($ok) {
                $sent++;
            } else {
                $failed++;
                Log::warning('Leave approval WhatsApp notify failed', [
                    'leave_id' => $leave->id,
                    'mobile' => $recipient->mobile,
                ]);
            }
        }

        $total = $numbers->count();
        $allSent = $sent > 0 && $failed === 0;

        if ($failed > 0 && $sent === 0) {
            // Helpful reason when every send failed (often WAHA session / number format)
            $connection = $this->waha->connectionStatus();
            $extra = $connection['connected']
                ? 'Check approval WhatsApp numbers and WAHA logs.'
                : 'WAHA status: '.$connection['status'].'. '.$connection['detail'];
        } else {
            $extra = '';
        }

        $result = [
            'ok' => $allSent,
            'sent' => $sent,
            'failed' => $failed,
            'total' => $total,
            'status' => $allSent ? 'sent' : ($sent > 0 ? 'partial' : 'failed'),
            'message' => $allSent
                ? "WhatsApp sent to {$sent} approver(s)."
                : ($sent > 0
                    ? "WhatsApp sent to {$sent}/{$total} approver(s); {$failed} failed."
                    : trim('WhatsApp notification failed for all approvers. '.$extra)),
        ];

        $this->persistStatus($leave, $result);

        return $result;
    }

    /**
     * @param  array{ok: bool, sent: int, failed: int, total: int, status: string, message: string}  $result
     */
    protected function persistStatus(Leave $leave, array $result): void
    {
        $leave->forceFill([
            'whatsapp_notify_status' => $result['status'],
            'whatsapp_notified_at' => $result['ok'] || ($result['sent'] ?? 0) > 0
                ? now()
                : $leave->whatsapp_notified_at,
        ])->save();
    }

    protected function buildMessage(Leave $leave, string $mobile, ?float $remainingBalance): string
    {
        $expiresAt = now()->addHours(72);

        // Links open a confirmation page (GET). Decision only happens after POST,
        // so WhatsApp link-preview crawlers cannot auto-approve leave.
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
            'New Leave Application',
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
        $lines[] = 'Approve (opens confirm page):';
        $lines[] = $approveUrl;
        $lines[] = '';
        $lines[] = 'Reject (opens confirm page):';
        $lines[] = $rejectUrl;
        $lines[] = '';
        $lines[] = 'Tap the link, then press Confirm. Links expire in 72 hours.';
        $lines[] = '- LSAF HR';

        return implode("\n", $lines);
    }
}
