<?php

namespace Tests\Unit;

use App\Models\Leave;
use App\Models\LeaveApprovalWhatsappNumber;
use App\Models\User;
use App\Services\LeaveWhatsAppNotifier;
use App\Services\WahaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LeaveWhatsAppNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_failed_when_waha_not_connected(): void
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

        LeaveApprovalWhatsappNumber::create([
            'name' => 'Boss',
            'mobile' => '03001234567',
        ]);

        $waha = Mockery::mock(WahaService::class);
        $waha->shouldReceive('enabled')->andReturn(true);
        $waha->shouldReceive('connectionStatus')->andReturn([
            'connected' => false,
            'status' => 'STOPPED',
            'detail' => 'Session stopped',
            'me' => null,
        ]);
        $waha->shouldNotReceive('sendToMobile');

        $result = (new LeaveWhatsAppNotifier($waha))->notifyApprovers($leave);

        $this->assertFalse($result['ok']);
        $this->assertSame('failed', $result['status']);
        $this->assertSame('failed', $leave->fresh()->whatsapp_notify_status);
    }

    public function test_marks_sent_when_all_messages_succeed(): void
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
        $waha->shouldReceive('connectionStatus')->andReturn([
            'connected' => true,
            'status' => 'WORKING',
            'detail' => 'ok',
            'me' => 'me',
        ]);
        $waha->shouldReceive('sendToMobile')->once()->andReturn(true);

        $result = (new LeaveWhatsAppNotifier($waha))->notifyApprovers($leave);

        $this->assertTrue($result['ok']);
        $this->assertSame('sent', $result['status']);
        $this->assertSame('sent', $leave->fresh()->whatsapp_notify_status);
        $this->assertNotNull($leave->fresh()->whatsapp_notified_at);
    }
}
