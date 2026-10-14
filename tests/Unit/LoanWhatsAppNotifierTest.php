<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Models\LoanApprover;
use App\Models\User;
use App\Services\LoanWhatsAppNotifier;
use App\Services\WahaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LoanWhatsAppNotifierTest extends TestCase
{
    use RefreshDatabase;

    private function application(array $attributes = []): Loan
    {
        $user = User::factory()->create($attributes['user'] ?? [
            'role' => 'employee',
            'name' => 'Alice',
            'employee_code' => 'E-1',
        ]);

        unset($attributes['user']);

        return Loan::create($attributes + [
            'user_id' => $user->id,
            'amount' => 120000,
            'installments' => 12,
            'monthly_deduction' => 10000,
            'remaining_balance' => 0,
            'status' => 'pending',
            'purpose' => 'School fees',
            'requested_at' => now(),
        ]);
    }

    public function test_the_message_carries_the_figures_and_both_links(): void
    {
        $loan = $this->application();

        LoanApprover::create(['name' => 'Accounts', 'mobile' => '03001234567']);

        $sent = null;

        $waha = Mockery::mock(WahaService::class);
        $waha->shouldReceive('enabled')->andReturn(true);
        $waha->shouldReceive('sendToMobile')
            ->once()
            ->andReturnUsing(function ($mobile, $text) use (&$sent) {
                $sent = $text;
                return true;
            });

        $result = (new LoanWhatsAppNotifier($waha))->notifyApprovers($loan);

        $this->assertTrue($result['ok']);
        $this->assertSame('sent', $result['status']);
        $this->assertSame('sent', $loan->fresh()->whatsapp_notify_status);
        $this->assertNotNull($loan->fresh()->whatsapp_notified_at);

        $this->assertStringContainsString('New Loan Application', $sent);
        $this->assertStringContainsString($loan->reference(), $sent);
        $this->assertStringContainsString('Alice', $sent);
        $this->assertStringContainsString('Rs 120,000.00', $sent);
        $this->assertStringContainsString('School fees', $sent);
        $this->assertStringContainsString('/loan/whatsapp-decision/'.$loan->id.'/approve', $sent);
        $this->assertStringContainsString('/loan/whatsapp-decision/'.$loan->id.'/reject', $sent);

        // Signed links, so the decision pages cannot be reached by guessing.
        $this->assertStringContainsString('signature=', $sent);
    }

    public function test_nobody_switched_on_is_reported_rather_than_attempted(): void
    {
        $loan = $this->application();

        LoanApprover::create(['name' => 'Off', 'mobile' => '03001234567', 'is_active' => false]);

        $waha = Mockery::mock(WahaService::class);
        $waha->shouldReceive('enabled')->andReturn(true);
        $waha->shouldNotReceive('sendToMobile');

        $result = (new LoanWhatsAppNotifier($waha))->notifyApprovers($loan);

        $this->assertFalse($result['ok']);
        $this->assertSame('skipped', $result['status']);
        $this->assertStringContainsString('No loan approvers', $result['message']);
        $this->assertSame('skipped', $loan->fresh()->whatsapp_notify_status);
    }

    public function test_a_failed_send_says_why(): void
    {
        $loan = $this->application();

        LoanApprover::create(['name' => 'Accounts', 'mobile' => '03001234567']);

        $waha = Mockery::mock(WahaService::class);
        $waha->shouldReceive('enabled')->andReturn(true);
        $waha->shouldReceive('sendToMobile')->once()->andReturn(false);
        $waha->shouldReceive('connectionStatus')->once()->andReturn([
            'connected' => false,
            'status' => 'STOPPED',
            'detail' => 'Session stopped',
            'me' => null,
        ]);

        $result = (new LoanWhatsAppNotifier($waha))->notifyApprovers($loan);

        $this->assertSame('failed', $result['status']);
        $this->assertStringContainsString('STOPPED', $result['message']);
        $this->assertSame('failed', $loan->fresh()->whatsapp_notify_status);
    }

    public function test_whatsapp_switched_off_is_not_an_error_for_the_application(): void
    {
        $loan = $this->application();

        $waha = Mockery::mock(WahaService::class);
        $waha->shouldReceive('enabled')->andReturn(false);
        $waha->shouldNotReceive('sendToMobile');

        $result = (new LoanWhatsAppNotifier($waha))->notifyApprovers($loan);

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('pending', $loan->fresh()->status);
    }
}
