<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Salary;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Support\WithoutPayLeave;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Leave without pay: what it counts as, what it costs, and how it reaches
 * the salary sheet.
 */
class WithoutPayLeaveTest extends TestCase
{
    use RefreshDatabase;

    /** September 2026 has 30 days; the 1st is a Tuesday. */
    private Carbon $month;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->month = Carbon::parse('2026-09-01');
        Carbon::setTestNow(Carbon::parse('2026-10-05 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function employee(float $salary = 60000, string $category = 'staff'): User
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'salary_category' => $category,
            'tracks_attendance' => true,
        ]);

        Staff::create([
            'user_id' => $user->id,
            'employee_id' => 'E'.$user->id,
            'department' => 'IT',
            'designation' => 'Officer',
            'salary' => $salary,
            'joining_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $shift = Shift::firstOrCreate(
            ['name' => '9 to 5'],
            ['start_time' => '09:00:00', 'end_time' => '17:00:00', 'grace_minutes' => 15]
        );

        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day) {
            WeeklySchedule::setFor($user->id, $day, $shift->id);
        }

        foreach (['Saturday', 'Sunday'] as $day) {
            WeeklySchedule::setFor($user->id, $day, null);
        }

        return $user;
    }

    private function leave(User $user, string $from, string $to, array $attributes = []): Leave
    {
        $days = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;

        return Leave::create($attributes + [
            'user_id' => $user->id,
            'type' => 'without_pay',
            'start_date' => $from,
            'end_date' => $to,
            'duration_type' => 'full_day',
            'days' => $days,
            'calculated_days' => $days,
            'status' => 'approved',
            'reason' => 'Personal',
        ]);
    }

    public function test_unpaid_days_are_counted_and_priced(): void
    {
        $user = $this->employee(60000);

        // Tue 1 to Thu 3 September: three working days.
        $this->leave($user, '2026-09-01', '2026-09-03');

        $wop = WithoutPayLeave::forMonth(collect([$user]), $this->month)[$user->id];

        $this->assertSame(3.0, $wop['days']);
        $this->assertSame(30, $wop['basis_days']);
        $this->assertEquals(2000, $wop['rate']);      // 60,000 over 30 days
        $this->assertEquals(6000, $wop['amount']);    // three days
        $this->assertSame('staff record', $wop['source']);
    }

    public function test_a_weekend_inside_a_stretch_of_leave_is_not_charged_for(): void
    {
        $user = $this->employee(60000);

        // Fri 4 to Mon 7 September: the Saturday and Sunday are their weekly
        // off, so only two days' pay is lost.
        $this->leave($user, '2026-09-04', '2026-09-07');

        $wop = WithoutPayLeave::forMonth(collect([$user]), $this->month)[$user->id];

        $this->assertSame(2.0, $wop['days']);
        $this->assertEquals(4000, $wop['amount']);
    }

    public function test_a_holiday_inside_a_stretch_of_leave_is_not_charged_for(): void
    {
        $user = $this->employee(60000);

        Holiday::create([
            'title' => 'Public holiday',
            'start_date' => '2026-09-02',
            'end_date' => '2026-09-02',
            'for_all' => 1,
        ]);

        $this->leave($user, '2026-09-01', '2026-09-03');

        $wop = WithoutPayLeave::forMonth(collect([$user]), $this->month)[$user->id];

        $this->assertSame(2.0, $wop['days']);
    }

    public function test_a_half_day_without_pay_costs_half_a_day(): void
    {
        $user = $this->employee(60000);

        $this->leave($user, '2026-09-01', '2026-09-01', [
            'duration_type' => 'half_day',
            'half_day_type' => 'morning',
            'days' => 0.5,
            'calculated_days' => 0.5,
        ]);

        $wop = WithoutPayLeave::forMonth(collect([$user]), $this->month)[$user->id];

        $this->assertSame(0.5, $wop['days']);
        $this->assertEquals(1000, $wop['amount']);
    }

    public function test_paid_leave_costs_nothing(): void
    {
        $user = $this->employee(60000);

        $this->leave($user, '2026-09-01', '2026-09-03', ['type' => 'annual']);

        $wop = WithoutPayLeave::forMonth(collect([$user]), $this->month)[$user->id];

        $this->assertSame(0.0, $wop['days']);
        $this->assertEquals(0, $wop['amount']);
    }

    public function test_leave_still_waiting_for_approval_costs_nothing(): void
    {
        $user = $this->employee(60000);

        $this->leave($user, '2026-09-01', '2026-09-03', ['status' => 'pending']);

        $this->assertSame(0.0, WithoutPayLeave::forMonth(collect([$user]), $this->month)[$user->id]['days']);
    }

    public function test_only_the_part_of_the_leave_inside_the_month_is_counted(): void
    {
        $user = $this->employee(60000);

        // Mon 28 September to Fri 2 October: three days fall in September.
        $this->leave($user, '2026-09-28', '2026-10-02');

        $september = WithoutPayLeave::forMonth(collect([$user]), $this->month)[$user->id];
        $october = WithoutPayLeave::forMonth(collect([$user]), Carbon::parse('2026-10-01'))[$user->id];

        $this->assertSame(3.0, $september['days']);
        $this->assertSame(2.0, $october['days']);
    }

    public function test_the_rate_follows_the_chosen_basis(): void
    {
        $user = $this->employee(62000);

        $this->leave($user, '2026-09-01', '2026-09-01');

        $perMonthDays = WithoutPayLeave::forMonth(collect([$user]), $this->month, WithoutPayLeave::BASIS_MONTH_DAYS)[$user->id];
        $perThirty = WithoutPayLeave::forMonth(collect([$user]), $this->month, WithoutPayLeave::BASIS_THIRTY)[$user->id];

        // September has 30 days, so both agree; October has 31 and does not.
        $this->assertSame(30, $perMonthDays['basis_days']);
        $this->assertSame(30, $perThirty['basis_days']);

        $october = WithoutPayLeave::forMonth(collect([$user]), Carbon::parse('2026-10-01'), WithoutPayLeave::BASIS_MONTH_DAYS)[$user->id];

        $this->assertSame(31, $october['basis_days']);
    }

    public function test_the_months_gross_is_used_once_the_sheet_has_one(): void
    {
        $user = $this->employee(60000);

        Salary::create([
            'user_id' => $user->id,
            'month' => 9,
            'year' => 2026,
            'basic_salary' => 90000,
            'status' => 'draft',
        ]);

        $this->leave($user, '2026-09-01', '2026-09-01');

        $wop = WithoutPayLeave::forMonth(collect([$user]), $this->month)[$user->id];

        $this->assertSame('salary sheet', $wop['source']);
        $this->assertEquals(90000, $wop['monthly']);
        $this->assertEquals(3000, $wop['amount']);
    }

    public function test_the_calendar_marks_unpaid_leave_differently_and_totals_it(): void
    {
        $user = $this->employee(60000);
        $this->leave($user, '2026-09-01', '2026-09-03');

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.attendance.calendar', ['month' => '2026-09']));

        $response->assertOk();

        // The unpaid sign, not the paid palm tree.
        $response->assertSee('💸', false);
        $response->assertSee('Leave Without Pay - 1 day deducted', false);

        // And the figures at the end of the row.
        $response->assertSee('Without Pay (deducted)', false);
        $response->assertSee('6,000');

        $wop = $response->viewData('withoutPay')[$user->id];
        $this->assertSame(3.0, $wop['days']);
    }

    public function test_the_sheet_fills_extra_leaves_from_unpaid_leave(): void
    {
        $user = $this->employee(60000);
        $this->leave($user, '2026-09-01', '2026-09-03');

        $salary = Salary::create([
            'user_id' => $user->id,
            'month' => 9,
            'year' => 2026,
            'basic_salary' => 60000,
            'status' => 'draft',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.salary.sheet.pull.wop'), [
                'month' => 9,
                'year' => 2026,
                'category' => 'staff',
                'basis' => 'month_days',
            ])
            ->assertSessionHas('success');

        $this->assertEquals(6000, $salary->fresh()->extra_leaves);

        // Whole rupees, so the sheet carries a round figure.
        $this->assertSame(0.0, fmod((float) $salary->fresh()->extra_leaves, 1.0));
    }

    public function test_a_posted_salary_is_left_alone(): void
    {
        $user = $this->employee(60000);
        $this->leave($user, '2026-09-01', '2026-09-03');

        $salary = Salary::create([
            'user_id' => $user->id,
            'month' => 9,
            'year' => 2026,
            'basic_salary' => 60000,
            'extra_leaves' => 0,
            'status' => 'posted',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.salary.sheet.pull.wop'), [
                'month' => 9,
                'year' => 2026,
                'category' => 'staff',
            ]);

        $this->assertEquals(0, $salary->fresh()->extra_leaves);
    }

    public function test_a_figure_with_no_unpaid_leave_behind_it_is_reported_not_wiped(): void
    {
        // One employee with unpaid leave, so the pull has something to do.
        $withLeave = $this->employee(60000);
        $this->leave($withLeave, '2026-09-01', '2026-09-03');

        Salary::create([
            'user_id' => $withLeave->id,
            'month' => 9,
            'year' => 2026,
            'basic_salary' => 60000,
            'status' => 'draft',
        ]);

        // And one carrying a figure somebody typed by hand.
        $typedByHand = $this->employee(50000);

        $salary = Salary::create([
            'user_id' => $typedByHand->id,
            'month' => 9,
            'year' => 2026,
            'basic_salary' => 50000,
            'extra_leaves' => 4500,
            'status' => 'draft',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.salary.sheet.pull.wop'), [
                'month' => 9,
                'year' => 2026,
                'category' => 'staff',
            ])
            ->assertSessionHas('success');

        // Left alone - the column is edited by hand too, so the pull says
        // what looks stale rather than quietly zeroing it.
        $this->assertEquals(4500, $salary->fresh()->extra_leaves);

        $this->assertStringContainsString(
            'no unpaid leave',
            session('success')
        );

        $this->assertStringContainsString($typedByHand->name, session('success'));
    }

    public function test_the_sheet_hints_the_amount_without_filling_it_in(): void
    {
        $user = $this->employee(60000);
        $this->leave($user, '2026-09-01', '2026-09-03');

        Salary::create([
            'user_id' => $user->id,
            'month' => 9,
            'year' => 2026,
            'basic_salary' => 60000,
            'status' => 'draft',
        ]);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.salary.sheet', ['month' => 9, 'year' => 2026, 'category' => 'staff']));

        $response->assertOk();
        $response->assertSee('placeholder="6,000"', false);
        $response->assertSee('3 day(s) leave without pay', false);

        // Hint only: nothing has been deducted yet.
        $this->assertEquals(0, Salary::where('user_id', $user->id)->value('extra_leaves'));
    }

    public function test_the_salary_sheet_screens_need_salary_access(): void
    {
        $employee = $this->employee(60000);

        $this->actingAs($employee)
            ->post(route('admin.salary.sheet.pull.wop'), [
                'month' => 9,
                'year' => 2026,
                'category' => 'staff',
            ])
            ->assertForbidden();
    }
}
