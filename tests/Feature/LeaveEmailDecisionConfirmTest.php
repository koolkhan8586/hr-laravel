<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\LeaveApprovalWhatsappNumber;
use App\Models\User;
use App\Services\LeaveWhatsAppNotifier;
use App\Services\WahaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Mockery;
use Tests\TestCase;

class LeaveEmailDecisionConfirmTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_decision_link_shows_confirm_and_does_not_approve(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $leave = Leave::create([
            'user_id' => $user->id,
            'type' => 'without_pay',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'duration_type' => 'full_day',
            'days' => 1,
            'calculated_days' => 1,
            'reason' => 'Test',
            'status' => 'pending',
        ]);

        $url = URL::temporarySignedRoute('leave.email.decision', now()->addHour(), [
            'id' => $leave->id,
            'decision' => 'approve',
            'email' => 'boss@example.com',
            'via' => 'whatsapp',
        ]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Confirm Approve');

        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_post_confirm_approves_leave(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $leave = Leave::create([
            'user_id' => $user->id,
            'type' => 'without_pay',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'duration_type' => 'full_day',
            'days' => 1,
            'calculated_days' => 1,
            'reason' => 'Test',
            'status' => 'pending',
        ]);

        $url = URL::temporarySignedRoute('leave.email.decision.submit', now()->addHour(), [
            'id' => $leave->id,
            'decision' => 'approve',
            'email' => 'boss@example.com',
            'via' => 'whatsapp',
        ]);

        $this->post($url)->assertOk()->assertSee('Leave Approved');
        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertSame('whatsapp', $leave->fresh()->decided_via);
    }

    public function test_notifier_attempts_send_without_blocking_on_session_precheck(): void
    {
        $user = User::factory()->create(['role' => 'employee', 'name' => 'Alice']);
        $leave = Leave::create([
            'user_id' => $user->id,
            'type' => 'without_pay',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'duration_type' => 'full_day',
            'days' => 1,
            'calculated_days' => 1,
            'reason' => 'Trip',
            'status' => 'pending',
        ]);

        LeaveApprovalWhatsappNumber::create([
            'name' => 'Boss',
            'mobile' => '03001234567',
        ]);

        $waha = Mockery::mock(WahaService::class);
        $waha->shouldReceive('enabled')->andReturn(true);
        $waha->shouldReceive('sendToMobile')->once()->andReturn(true);
        $waha->shouldNotReceive('connectionStatus');

        $result = (new LeaveWhatsAppNotifier($waha))->notifyApprovers($leave);

        $this->assertTrue($result['ok']);
        $this->assertSame('sent', $leave->fresh()->whatsapp_notify_status);
    }
}
