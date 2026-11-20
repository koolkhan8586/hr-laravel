<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Support\MonthlyAttendanceSheet;
use App\Support\WithoutPayLeave;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The calendar has to show the day that costs the money.
 *
 * A day only counts against an employee if their own roster says they were
 * due in on it, and the grid has to paint that same answer - otherwise a
 * deduction appears beside a row with nothing wrong in it.
 */
class CalendarRosterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Friday 20 November 2026. The 7th and 14th are Saturdays.
        Carbon::setTestNow(Carbon::parse('2026-11-20 12:00:00', 'Asia/Karachi'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @param array<int, string> $workingDays */
    private function employee(array $workingDays, float $salary = 80000): User
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'tracks_attendance' => true,
        ]);

        Staff::create([
            'user_id' => $user->id,
            'employee_id' => 'E'.$user->id,
            'department' => 'Admin',
            'designation' => 'Officer',
            'salary' => $salary,
            'joining_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $shift = Shift::firstOrCreate(
            ['name' => '9 to 5'],
            ['start_time' => '09:00:00', 'end_time' => '17:00:00', 'grace_minutes' => 15]
        );

        foreach ($workingDays as $day) {
            WeeklySchedule::setFor($user->id, $day, $shift->id);
        }

        $week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach (array_diff($week, $workingDays) as $day) {
            WeeklySchedule::setFor($user->id, $day, null);
        }

        return $user;
    }

    /** Present on every rostered day of the month so far, bar the ones skipped. */
    private function worked(User $user, array $skip = []): void
    {
        foreach (range(1, 20) as $d) {

            $day = Carbon::create(2026, 11, $d);

            if ($day->format('l') === 'Sunday' || in_array($d, $skip, true)) {
                continue;
            }

            Attendance::create([
                'user_id' => $user->id,
                'date' => $day->toDateString(),
                'clock_in' => $day->toDateString().' 08:55:00',
                'clock_out' => $day->toDateString().' 17:05:00',
                'total_hours' => 8.17,
                'status' => 'present',
            ]);
        }
    }

    /** The calendar cell for one employee on one day of November. */
    private function cell(User $user, int $day): string
    {
        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.attendance.calendar', ['month' => '2026-11']))
            ->assertOk()
            ->getContent();

        preg_match_all('~<tr class="employee-row" data-id="'.$user->id.'">(.*?)</tr>~s', $html, $rows);

        foreach ($rows[1] as $row) {

            preg_match_all('~<td[^>]*>(.*?)</td>~s', $row, $cells);

            if (!empty($cells[1])) {
                return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($cells[1][$day] ?? ''))));
            }
        }

        return '(row not found)';
    }

    public function test_a_rostered_saturday_nobody_came_in_for_shows_as_absent(): void
    {
        // Six day week, missing Saturday the 14th.
        $user = $this->employee(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']);
        $this->worked($user, skip: [14]);

        $wop = WithoutPayLeave::forMonth(collect([$user]), Carbon::parse('2026-11-01'))[$user->id];

        $this->assertSame(1.0, $wop['absent_days']);
        $this->assertEquals(2667, $wop['amount']);   // 80,000 over 30 days

        // The day the money comes off is marked on the grid, not greyed out
        // as though it were a weekend.
        $this->assertSame('✖', $this->cell($user, 14));
        $this->assertSame('✔', $this->cell($user, 7));

        // Sunday is still their day off.
        $this->assertSame('-', $this->cell($user, 15));
    }

    public function test_a_saturday_taken_off_on_the_monthly_schedule_costs_nothing(): void
    {
        $user = $this->employee(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']);
        $this->worked($user, skip: [14]);

        // The alternating Saturday: an admin takes them off that one date.
        EmployeeSchedule::setFor($user->id, '2026-11-14', null);

        $sheet = MonthlyAttendanceSheet::build($user, Carbon::parse('2026-11-01'));

        $fourteenth = collect($sheet['days'])->firstWhere(fn ($d) => $d['date']->day === 14);

        $this->assertSame('off', $fourteenth['bucket']);
        $this->assertSame(0, $sheet['totals']['absent']);

        $wop = WithoutPayLeave::forMonth(collect([$user]), Carbon::parse('2026-11-01'))[$user->id];

        $this->assertSame(0.0, $wop['absent_days']);
        $this->assertEquals(0, $wop['amount']);

        $this->assertSame('-', $this->cell($user, 14));
    }

    public function test_a_weekday_off_is_not_an_absence(): void
    {
        // Rostered Sunday to Thursday: Friday and Saturday are their days off.
        $user = $this->employee(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']);

        foreach (range(1, 20) as $d) {

            $day = Carbon::create(2026, 11, $d);

            if (in_array($day->format('l'), ['Friday', 'Saturday'], true)) {
                continue;
            }

            Attendance::create([
                'user_id' => $user->id,
                'date' => $day->toDateString(),
                'clock_in' => $day->toDateString().' 08:55:00',
                'clock_out' => $day->toDateString().' 17:05:00',
                'total_hours' => 8.17,
                'status' => 'present',
            ]);
        }

        $wop = WithoutPayLeave::forMonth(collect([$user]), Carbon::parse('2026-11-01'))[$user->id];

        $this->assertSame(0.0, $wop['absent_days']);

        // Friday the 20th is their day off, and Sunday the 15th a working day.
        $this->assertSame('-', $this->cell($user, 20));
        $this->assertSame('✔', $this->cell($user, 15));
    }

    public function test_nobody_is_charged_when_no_schedule_has_been_set(): void
    {
        $user = User::factory()->create(['role' => 'employee', 'tracks_attendance' => true]);

        Staff::create([
            'user_id' => $user->id,
            'employee_id' => 'E'.$user->id,
            'department' => 'Admin',
            'designation' => 'Officer',
            'salary' => 80000,
            'joining_date' => '2024-01-01',
            'status' => 'active',
        ]);

        // Worked every weekday, never a Saturday, and nobody ever set a roster.
        foreach (range(1, 20) as $d) {

            $day = Carbon::create(2026, 11, $d);

            if ($day->isWeekend()) {
                continue;
            }

            Attendance::create([
                'user_id' => $user->id,
                'date' => $day->toDateString(),
                'clock_in' => $day->toDateString().' 08:55:00',
                'clock_out' => $day->toDateString().' 17:05:00',
                'total_hours' => 8.17,
                'status' => 'present',
            ]);
        }

        $wop = WithoutPayLeave::forMonth(collect([$user]), Carbon::parse('2026-11-01'))[$user->id];

        // Without a roster there is nothing to say they were due in, so the
        // Saturdays are not absences and nothing comes off the pay.
        $this->assertSame(0.0, $wop['absent_days']);
        $this->assertEquals(0, $wop['amount']);

        $this->assertSame('?', $this->cell($user, 14));

        // And the screen says who needs a schedule setting.
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.attendance.calendar', ['month' => '2026-11']))
            ->assertSee('No weekly schedule set for')
            ->assertSee($user->name);
    }
}
