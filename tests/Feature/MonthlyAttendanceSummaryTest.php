<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Support\MonthlyAttendanceSheet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin Monthly Attendance Summary has to agree with the monthly report
 * each employee can print for themselves.
 */
class MonthlyAttendanceSummaryTest extends TestCase
{
    use RefreshDatabase;

    private string $month = '2026-09';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // The summary only reaches days that have happened.
        Carbon::setTestNow(Carbon::parse('2026-10-05 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function employee(): User
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'name' => 'Half Day Person',
            'tracks_attendance' => true,
        ]);

        Staff::create([
            'user_id' => $user->id,
            'employee_id' => 'HDP',
            'department' => 'IT',
            'designation' => 'Officer',
            'salary' => 100000,
            'joining_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $shift = Shift::create([
            'name' => '9 to 5',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'grace_minutes' => 15,
        ]);

        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day) {
            WeeklySchedule::setFor($user->id, $day, $shift->id);
        }

        foreach (['Saturday', 'Sunday'] as $day) {
            WeeklySchedule::setFor($user->id, $day, null);
        }

        return $user;
    }

    public function test_a_half_day_taken_as_leave_is_counted_as_a_half_day(): void
    {
        $user = $this->employee();

        // Tuesday the 1st: half day leave in the morning, came in after lunch.
        // The attendance row on its own says Late, which is what used to be
        // counted.
        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-01',
            'clock_in' => '2026-09-01 13:30:00',
            'clock_out' => '2026-09-01 17:30:00',
            'total_hours' => 4,
            'status' => 'late',
        ]);

        Leave::create([
            'user_id' => $user->id,
            'type' => 'annual',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-01',
            'duration_type' => 'half_day',
            'half_day_type' => 'morning',
            'days' => 0.5,
            'calculated_days' => 0.5,
            'status' => 'approved',
            'reason' => 'Doctor',
        ]);

        // A short day marked half day outright, with no leave behind it.
        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-02',
            'clock_in' => '2026-09-02 09:00:00',
            'clock_out' => '2026-09-02 11:30:00',
            'total_hours' => 2.5,
            'status' => 'half_day',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-03',
            'clock_in' => '2026-09-03 08:55:00',
            'clock_out' => '2026-09-03 17:05:00',
            'total_hours' => 8.17,
            'status' => 'present',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.summary', ['month' => $this->month]));

        $response->assertOk();

        $row = collect($response->viewData('data'))
            ->firstWhere(fn ($r) => $r['user']->id === $user->id);

        $this->assertNotNull($row, 'The employee should be listed on the summary.');

        $sheet = MonthlyAttendanceSheet::build($user, Carbon::parse($this->month.'-01'));

        // Both half days are counted, and neither is filed under Leave.
        $this->assertSame(2, $row['halfday']);
        $this->assertSame(0, $row['leave']);
        $this->assertSame(1, $row['present']);
        $this->assertSame(0, $row['late']);

        // And the figures are the employee's own report's figures.
        $this->assertSame($sheet['totals']['half_day'], $row['halfday']);
        $this->assertSame($sheet['totals']['present'], $row['present']);
        $this->assertSame($sheet['totals']['late'], $row['late']);
        $this->assertSame($sheet['totals']['absent'], $row['absent']);
        $this->assertSame($sheet['totals']['off'], $row['off']);
    }

    public function test_weekly_offs_are_not_counted_as_absences(): void
    {
        $user = $this->employee();

        $admin = User::factory()->create(['role' => 'admin']);

        $row = collect(
            $this->actingAs($admin)
                ->get(route('admin.attendance.summary', ['month' => $this->month]))
                ->viewData('data')
        )->firstWhere(fn ($r) => $r['user']->id === $user->id);

        // September 2026 has 8 weekend days; they are off days, not absences,
        // and they are not working days either.
        $this->assertSame(8, $row['off']);
        $this->assertSame(22, $row['absent']);
        $this->assertSame(22, $row['working_days']);
    }
}
