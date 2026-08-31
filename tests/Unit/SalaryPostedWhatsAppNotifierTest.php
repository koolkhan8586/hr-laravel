<?php

namespace Tests\Unit;

use App\Models\Salary;
use App\Models\User;
use App\Services\SalaryPostedWhatsAppNotifier;
use App\Services\WahaService;
use Mockery;
use Tests\TestCase;

class SalaryPostedWhatsAppNotifierTest extends TestCase
{
    public function test_skips_when_mobile_missing(): void
    {
        $user = User::factory()->make(['mobile' => null, 'name' => 'Alice']);
        $salary = new Salary([
            'month' => 8,
            'year' => 2026,
            'net_salary' => 45000,
        ]);
        $salary->setRelation('user', $user);

        $waha = Mockery::mock(WahaService::class);
        $waha->shouldReceive('enabled')->once()->andReturn(true);

        $result = (new SalaryPostedWhatsAppNotifier($waha))->notify($salary);

        $this->assertFalse($result['ok']);
        $this->assertTrue($result['skipped']);
    }

    public function test_sends_salary_posted_message(): void
    {
        $user = User::factory()->make([
            'name' => 'Alice',
            'mobile' => '03001234567',
        ]);
        $salary = new Salary([
            'month' => 8,
            'year' => 2026,
            'net_salary' => 45000.50,
        ]);
        $salary->setRelation('user', $user);

        $waha = Mockery::mock(WahaService::class);
        $waha->shouldReceive('enabled')->once()->andReturn(true);
        $waha->shouldReceive('sendToMobile')
            ->once()
            ->with('03001234567', Mockery::on(function (string $text) {
                return str_contains($text, 'August 2026')
                    && str_contains($text, '45,000.50')
                    && str_contains($text, url('/salary'))
                    && str_contains($text, 'Alice');
            }))
            ->andReturn(true);

        $result = (new SalaryPostedWhatsAppNotifier($waha))->notify($salary);

        $this->assertTrue($result['ok']);
    }
}
