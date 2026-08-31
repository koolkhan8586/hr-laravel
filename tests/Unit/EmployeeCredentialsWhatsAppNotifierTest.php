<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\EmployeeCredentialsWhatsAppNotifier;
use App\Services\WahaService;
use Mockery;
use Tests\TestCase;

class EmployeeCredentialsWhatsAppNotifierTest extends TestCase
{
    public function test_skips_when_waha_disabled(): void
    {
        $user = User::factory()->make([
            'mobile' => '03001234567',
            'employee_code' => 'EMP001',
            'email' => 'emp@example.com',
        ]);

        $waha = Mockery::mock(WahaService::class);
        $waha->shouldReceive('enabled')->once()->andReturn(false);

        $result = (new EmployeeCredentialsWhatsAppNotifier($waha))->sendWelcome($user, 'secret12');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('disabled', strtolower($result['message']));
    }

    public function test_skips_when_mobile_missing(): void
    {
        $user = User::factory()->make([
            'mobile' => null,
            'employee_code' => 'EMP001',
        ]);

        $waha = Mockery::mock(WahaService::class);
        $waha->shouldReceive('enabled')->once()->andReturn(true);

        $result = (new EmployeeCredentialsWhatsAppNotifier($waha))->sendWelcome($user, 'secret12');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('mobile', strtolower($result['message']));
    }

    public function test_sends_welcome_message_with_credentials(): void
    {
        $user = User::factory()->make([
            'name' => 'Alice',
            'mobile' => '03001234567',
            'employee_code' => 'EMP001',
            'email' => 'alice@example.com',
        ]);

        $waha = Mockery::mock(WahaService::class);
        $waha->shouldReceive('enabled')->once()->andReturn(true);
        $waha->shouldReceive('sendToMobile')
            ->once()
            ->with('03001234567', Mockery::on(function (string $text) {
                return str_contains($text, url('/login'))
                    && str_contains($text, 'EMP001')
                    && str_contains($text, 'alice@example.com')
                    && str_contains($text, 'secret12')
                    && str_contains($text, 'Welcome');
            }))
            ->andReturn(true);

        $result = (new EmployeeCredentialsWhatsAppNotifier($waha))->sendWelcome($user, 'secret12');

        $this->assertTrue($result['ok']);
    }

    public function test_sends_reset_message(): void
    {
        $user = User::factory()->make([
            'mobile' => '03001234567',
            'employee_code' => 'EMP002',
            'email' => 'bob@example.com',
        ]);

        $waha = Mockery::mock(WahaService::class);
        $waha->shouldReceive('enabled')->once()->andReturn(true);
        $waha->shouldReceive('sendToMobile')
            ->once()
            ->with('03001234567', Mockery::on(fn (string $text) => str_contains($text, 'reset')))
            ->andReturn(true);

        $result = (new EmployeeCredentialsWhatsAppNotifier($waha))->sendPasswordReset($user, 'newpass8');

        $this->assertTrue($result['ok']);
    }
}
