<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanApprover;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Tells the configured loan approvers, on WhatsApp, that somebody has applied
 * for a loan - and gives them a link to approve or reject it.
 *
 * Deliberately the same shape as LeaveWhatsAppNotifier: the numbers are
 * entered in the panel, the links are signed and expire, and the outcome is
 * written back onto the row so the panel can show whether the message got out
 * and offer to send it again.
 */
class LoanWhatsAppNotifier
{
    public function __construct(protected WahaService $waha)
    {
    }

    /**
     * @return array{ok: bool, sent: int, failed: int, total: int, status: string, message: string}
     */
    public function notifyApprovers(Loan $loan): array
    {
        if (!$this->waha->enabled()) {
            return $this->finish($loan, [
                'ok' => false,
                'sent' => 0,
                'failed' => 0,
                'total' => 0,
                'status' => 'skipped',
                'message' => 'WAHA is disabled. Enable it in Settings / .env to send WhatsApp notifications.',
            ]);
        }

        $loan->loadMissing('user');

        $approvers = LoanApprover::active();

        if ($approvers->isEmpty()) {
            return $this->finish($loan, [
                'ok' => false,
                'sent' => 0,
                'failed' => 0,
                'total' => 0,
                'status' => 'skipped',
                'message' => 'No loan approvers are configured. Add them under Loan Management / Loan Approvers.',
            ]);
        }

        $sent = 0;
        $failed = 0;

        foreach ($approvers as $approver) {

            $ok = $this->waha->sendToMobile(
                $approver->mobile,
                $this->buildMessage($loan, $approver->mobile)
            );

            if ($ok) {
                $sent++;
            } else {
                $failed++;
                Log::warning('Loan application WhatsApp notify failed', [
                    'loan_id' => $loan->id,
                    'mobile' => $approver->mobile,
                ]);
            }
        }

        $total = $approvers->count();
        $allSent = $sent > 0 && $failed === 0;

        if ($failed > 0 && $sent === 0) {
            // Say why, when nothing got out at all: it is nearly always the
            // WAHA session rather than the numbers.
            $connection = $this->waha->connectionStatus();

            $extra = $connection['connected']
                ? 'Check the approver numbers and the WAHA logs.'
                : 'WAHA status: '.$connection['status'].'. '.$connection['detail'];
        } else {
            $extra = '';
        }

        return $this->finish($loan, [
            'ok' => $allSent,
            'sent' => $sent,
            'failed' => $failed,
            'total' => $total,
            'status' => $allSent ? 'sent' : ($sent > 0 ? 'partial' : 'failed'),
            'message' => $allSent
                ? "WhatsApp sent to {$sent} loan approver(s)."
                : ($sent > 0
                    ? "WhatsApp sent to {$sent}/{$total} loan approver(s); {$failed} failed."
                    : trim('WhatsApp notification failed for all loan approvers. '.$extra)),
        ]);
    }

    /**
     * @param  array{ok: bool, sent: int, failed: int, total: int, status: string, message: string}  $result
     * @return array{ok: bool, sent: int, failed: int, total: int, status: string, message: string}
     */
    protected function finish(Loan $loan, array $result): array
    {
        $loan->forceFill([
            'whatsapp_notify_status' => $result['status'],
            'whatsapp_notified_at' => ($result['sent'] ?? 0) > 0
                ? now()
                : $loan->whatsapp_notified_at,
        ])->save();

        return $result;
    }

    protected function buildMessage(Loan $loan, string $mobile): string
    {
        $expiresAt = now()->addHours(72);

        // Both links open a confirmation page over GET and only decide on the
        // POST behind it, so a WhatsApp link preview crawler cannot approve a
        // loan by fetching the URL.
        $approveUrl = URL::temporarySignedRoute('loan.whatsapp.decision', $expiresAt, [
            'id' => $loan->id,
            'decision' => 'approve',
            'mobile' => $mobile,
        ]);

        $rejectUrl = URL::temporarySignedRoute('loan.whatsapp.decision', $expiresAt, [
            'id' => $loan->id,
            'decision' => 'reject',
            'mobile' => $mobile,
        ]);

        $employee = $loan->user->name ?? 'Employee';
        $monthly = $loan->monthly_deduction > 0
            ? 'Rs '.number_format((float) $loan->monthly_deduction, 2)
            : 'To be worked out';

        $lines = [
            'New Loan Application',
            '',
            'Reference: '.$loan->reference(),
            'Employee: '.$employee,
        ];

        if (filled($loan->user->employee_code ?? null)) {
            $lines[] = 'Employee code: '.$loan->user->employee_code;
        }

        $lines[] = 'Amount: Rs '.number_format((float) $loan->amount, 2);
        $lines[] = 'Installments: '.($loan->installments ?: '-');
        $lines[] = 'Monthly deduction: '.$monthly;

        if ($loan->deduction_start_month) {
            $lines[] = 'Deductions from: '
                .Carbon::parse($loan->deduction_start_month)->format('F Y');
        }

        $lines[] = 'Purpose: '.(filled($loan->purpose) ? $loan->purpose : '-');

        if ($outstanding = $this->outstandingBefore($loan)) {
            $lines[] = 'Already outstanding: Rs '.number_format($outstanding, 2);
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

    /**
     * What the employee still owes on loans already approved, so an approver
     * can see the whole picture before adding to it.
     */
    protected function outstandingBefore(Loan $loan): float
    {
        return (float) Loan::where('user_id', $loan->user_id)
            ->where('id', '!=', $loan->id)
            ->whereIn('status', ['approved', 'completed'])
            ->sum('remaining_balance');
    }
}
