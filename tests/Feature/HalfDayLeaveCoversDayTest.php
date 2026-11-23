<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Support\AttendanceRules;
use App\Support\LeaveEntitlement;
use App\Support\MonthlyShortfall;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Half a day's leave and leaving at one o'clock is one thing, not two.
 */
class HalfDayLeaveCoversDayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['mail.default' => 'array']);

        Carbon::setTestNow(Carbon::parse('2026-09-01 13:00:00', 'Asia/Karachi'));
        AppSetting::put(AttendanceRules::RULES_FROM, '2020-01-01');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function employee(float $balance = 5): User
    {
        $user = User::factory()->create(['role' => 'employee', 'tracks_attendance' => true]);

        Staff::create([
            'user_id' => $user->id, 'employee_id' => 'E'.$user->id, 'department' => 'IT',
            'designation' => 'Officer', 'salary' => 60000, 'joining_date' => '2024-01-01',
            'status' => 'active',
        ]);

        LeaveBalance::create([
            'user_id' => $user->id, 'opening_balance' => $balance,
            'used_leaves' => 0, 'remaining_leaves' => $balance,
        ]);

        $shift = Shift::firstOrCreate(
            ['name' => '9 to 5'],
            ['start_time' => '09:00:00', 'end_time' => '17:00:00', 'grace_minutes' => 15]
        );

        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day) {
            WeeklySchedule::setFor($user->id, $day, $shift->id);
        }

        return $user;
    }

    private function halfDayLeave(User $user, string $status): Leave
    {
        return Leave::create([
            'user_id' => $user->id, 'type' => 'annual',
            'start_date' => '2026-09-01', 'end_date' => '2026-09-01',
            'duration_type' => 'half_day', 'half_day_type' => 'afternoon',
            'days' => 0.5, 'calculated_days' => 0.5,
            'status' => $status, 'reason' => 'Appointment',
        ]);
    }

    private function clockOutAtOne(User $user): void
    {
        Attendance::create([
            'user_id' => $user->id, 'date' => '2026-09-01',
            'clock_in' => '2026-09-01 09:00:00', 'status' => 'present',
        ]);

        $this->actingAs($user)->postJson(route('attendance.clockout'), [
            'latitude' => 31.5, 'longitude' => 74.3,
        ])->assertOk();
    }

    public function test_approving_the_leave_takes_back_the_charge_made_before_it(): void
    {
        $user  = $this->employee();
        $leave = $this->halfDayLeave($user, 'pending');

        // Clocked out at one o'clock while the leave was still waiting.
        $this->clockOutAtOne($user);

        $this->assertSame(1, Leave::where('user_id', $user->id)->whereNotNull('auto_reason')->count());

        $leave->update(['status' => 'approved']);

        // Only the leave they applied for remains, and it is the only deduction.
        $this->assertSame(0, Leave::where('user_id', $user->id)->whereNotNull('auto_reason')->count());
        $this->assertEquals(0.5, 5 - LeaveEntitlement::remainingFor($user->id));
        $this->assertEquals(4.5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));
    }

    public function test_a_leave_already_approved_is_never_charged_on_top(): void
    {
        $user = $this->employee();
        $this->halfDayLeave($user, 'approved');

        $this->clockOutAtOne($user);

        $this->assertSame(0, Leave::where('user_id', $user->id)->whereNotNull('auto_reason')->count());
    }

    public function test_the_time_rule_ignores_a_day_with_half_day_leave(): void
    {
        AppSetting::put(\App\Support\TimeShortfall::MODE, \App\Support\TimeShortfall::MODE_TIME);
        AppSetting::put(\App\Support\TimeShortfall::RULES_FROM, '2020-01-01');

        $user = $this->employee();
        $this->halfDayLeave($user, 'approved');

        Attendance::create([
            'user_id' => $user->id, 'date' => '2026-09-01',
            'clock_in' => '2026-09-01 09:00:00', 'clock_out' => '2026-09-01 13:00:00',
            'status' => 'present',
        ]);

        $this->assertSame(0, MonthlyShortfall::forUser($user, Carbon::parse('2026-09-01'))['minutes']);
    }
}
