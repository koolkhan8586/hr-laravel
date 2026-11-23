<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveTransaction;
use App\Models\Shift;
use App\Models\ShortfallCharge;
use App\Models\Staff;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Support\MonthlyShortfall;
use App\Support\TimeShortfall;
use App\Support\WithoutPayLeave;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Time owed, counted in minutes rather than punished in half days.
 *
 * Charging half a day - four hours of somebody's leave - because they left
 * twenty-five minutes early takes more than is owed. These tests hold the
 * replacement to the arithmetic that makes it fair: what is counted is the
 * time actually missed, and it is only turned into days once the whole month
 * can be seen.
 */
class TimeShortfallTest extends TestCase
{
    use RefreshDatabase;

    /** A 9-to-5 week in November 2026. */
    private string $month = '2026-11';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['mail.default' => 'array']);

        // The first of December: November is over and can be settled.
        Carbon::setTestNow(Carbon::parse('2026-12-01 09:00:00', 'Asia/Karachi'));

        AppSetting::put(TimeShortfall::MODE, TimeShortfall::MODE_TIME);
        AppSetting::put(TimeShortfall::RULES_FROM, '2026-01-01');
        AppSetting::put(TimeShortfall::EARLY_GRACE, 15);
        AppSetting::put(TimeShortfall::HOURS_PER_HALF_DAY, 4);

        // Saturday and Sunday only count when the date itself is scheduled;
        // these tests use weekdays throughout.
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | One day's arithmetic
    |--------------------------------------------------------------------------
    */

    public function test_arriving_inside_the_grace_costs_nothing(): void
    {
        $user = $this->employee();

        // Shift starts 09:00, grace 15 minutes.
        $this->worked($user, '2026-11-02', '09:14', '17:00');

        $this->assertSame(0, $this->minutesOn($user, '2026-11-02'));
    }

    public function test_past_the_grace_the_whole_lateness_counts(): void
    {
        $user = $this->employee();

        // 9:25 is ten minutes past the grace, but twenty-five past the shift.
        $this->worked($user, '2026-11-02', '09:25', '17:00');

        $this->assertSame(25, $this->minutesOn($user, '2026-11-02'));
    }

    public function test_one_minute_past_the_grace_counts_the_whole_sixteen(): void
    {
        $user = $this->employee();

        $this->worked($user, '2026-11-02', '09:16', '17:00');

        $this->assertSame(16, $this->minutesOn($user, '2026-11-02'));
    }

    public function test_leaving_inside_the_early_grace_costs_nothing(): void
    {
        $user = $this->employee();

        // 14 minutes early, against a 15-minute grace.
        $this->worked($user, '2026-11-02', '09:00', '16:46');

        $this->assertSame(0, $this->minutesOn($user, '2026-11-02'));
    }

    public function test_past_the_early_grace_the_whole_of_it_counts(): void
    {
        $user = $this->employee();

        // The old rule charged half a day - four hours - for this.
        $this->worked($user, '2026-11-02', '09:00', '16:35');

        $this->assertSame(25, $this->minutesOn($user, '2026-11-02'));
    }

    public function test_late_and_early_on_the_same_day_add_up(): void
    {
        $user = $this->employee();

        $this->worked($user, '2026-11-02', '09:30', '16:30');

        $this->assertSame(60, $this->minutesOn($user, '2026-11-02'));
    }

    public function test_a_day_with_no_clock_out_counts_half_the_shift(): void
    {
        $user = $this->employee();

        $this->worked($user, '2026-11-02', '09:00', null);

        // An eight-hour shift, so four hours.
        $this->assertSame(240, $this->minutesOn($user, '2026-11-02'));
    }

    public function test_a_day_can_never_cost_more_than_the_shift_it_was(): void
    {
        $user = $this->employee();

        // Seven hours late and then no clock-out at all: 420 minutes late
        // plus half the shift is 660, which is more than the eight-hour day
        // ever held. A day cannot cost more than it was worth.
        //
        // With a clock-out marked this can never happen - being later takes
        // the same minutes off the early side - so a missing clock-out is
        // the one case where the cap does any work.
        $this->worked($user, '2026-11-02', '16:00', null);

        $this->assertSame(480, $this->minutesOn($user, '2026-11-02'));
    }

    public function test_an_employee_with_their_own_allowance_is_held_to_it(): void
    {
        $user = $this->employee();
        $user->forceFill(['early_leave_allowance_minutes' => 60])->save();

        // Fifty minutes early, against their own sixty-minute allowance.
        $this->worked($user, '2026-11-02', '09:00', '16:10');

        $this->assertSame(0, $this->minutesOn($user->fresh(), '2026-11-02'));
    }

    /*
    |--------------------------------------------------------------------------
    | Days that are never counted
    |--------------------------------------------------------------------------
    */

    public function test_a_day_nobody_came_in_for_is_not_counted_here(): void
    {
        $user = $this->employee();

        Attendance::create([
            'user_id' => $user->id,
            'date'    => '2026-11-02',
            'status'  => 'absent',
        ]);

        // Absence is a whole day charged by its own rule. Counting hours here
        // as well would bill the same day twice.
        $this->assertSame(0, $this->monthFor($user)['minutes']);
    }

    public function test_a_day_on_approved_leave_is_not_counted(): void
    {
        $user = $this->employee();

        Leave::create([
            'user_id' => $user->id,
            'type' => 'annual',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-02',
            'duration_type' => 'full_day',
            'days' => 1, 'calculated_days' => 1,
            'status' => 'approved',
            'reason' => 'Family',
        ]);

        $this->worked($user, '2026-11-02', '11:00', '15:00');

        $this->assertSame(0, $this->monthFor($user)['minutes']);
    }

    public function test_half_a_days_approved_leave_takes_the_whole_day_out(): void
    {
        $user = $this->employee();

        Leave::create([
            'user_id' => $user->id,
            'type' => 'annual',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-02',
            'duration_type' => 'half_day',
            'half_day_type' => 'morning',
            'days' => 0.5, 'calculated_days' => 0.5,
            'status' => 'approved',
            'reason' => 'Appointment',
        ]);

        // Coming in at one o'clock is the leave, not four hours owed.
        $this->worked($user, '2026-11-02', '13:00', '17:00');

        $this->assertSame(0, $this->monthFor($user)['minutes']);
    }

    public function test_a_holiday_is_not_counted(): void
    {
        $user = $this->employee();

        Holiday::create([
            'title' => 'Eid',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-02',
            'for_all' => 1,
        ]);

        $this->worked($user, '2026-11-02', '11:00', '15:00');

        $this->assertSame(0, $this->monthFor($user)['minutes']);
    }

    public function test_days_before_the_rule_started_are_never_counted(): void
    {
        AppSetting::put(TimeShortfall::RULES_FROM, '2026-11-10');

        $user = $this->employee();

        $this->worked($user, '2026-11-02', '10:00', '17:00');
        $this->worked($user, '2026-11-11', '10:00', '17:00');

        // Only the day on or after the start date.
        $this->assertSame(60, $this->monthFor($user)['minutes']);
    }

    /*
    |--------------------------------------------------------------------------
    | Minutes into days, once a month
    |--------------------------------------------------------------------------
    */

    public function test_the_month_is_rounded_to_the_nearest_half_day(): void
    {
        // Four hours is half a day, eight a full one.
        $this->assertSame(0.0, TimeShortfall::daysFor(0));
        $this->assertSame(0.0, TimeShortfall::daysFor(119));   // 1h59 -> nothing
        $this->assertSame(0.5, TimeShortfall::daysFor(120));   // 2h    -> half
        $this->assertSame(0.5, TimeShortfall::daysFor(330));   // 5h30  -> half
        $this->assertSame(1.0, TimeShortfall::daysFor(390));   // 6h30  -> one
        $this->assertSame(1.0, TimeShortfall::daysFor(480));   // 8h    -> one
        $this->assertSame(2.0, TimeShortfall::daysFor(960));   // 16h   -> two
        $this->assertSame(2.5, TimeShortfall::daysFor(1200));  // 20h   -> two and a half
    }

    public function test_a_few_minutes_late_now_and_then_comes_to_nothing(): void
    {
        $user = $this->employee();

        // Twenty minutes past the grace, three times in the month.
        foreach (['2026-11-02', '2026-11-03', '2026-11-04'] as $date) {
            $this->worked($user, $date, '09:20', '17:00');
        }

        $month = $this->monthFor($user);

        $this->assertSame(60, $month['minutes']);

        // An hour over a whole month is not half a day of anybody's leave.
        $this->assertSame(0.0, $month['days_charged']);
    }

    public function test_the_same_lateness_every_day_does_add_up_to_a_day(): void
    {
        $user = $this->employee();

        // Twenty-five minutes late, every working day of the month.
        foreach ($this->workingDays() as $date) {
            $this->worked($user, $date, '09:25', '17:00');
        }

        $month = $this->monthFor($user);

        // 21 working days at 25 minutes is 8 h 45 m.
        $this->assertSame(525, $month['minutes']);
        $this->assertSame(1.0, $month['days_charged']);
    }

    /*
    |--------------------------------------------------------------------------
    | Settling the month
    |--------------------------------------------------------------------------
    */

    public function test_the_month_is_charged_off_the_leave_balance(): void
    {
        $user = $this->employee(balance: 5);

        $this->shortBy($user, 480);

        $charge = MonthlyShortfall::charge($user, Carbon::parse($this->month.'-01'));

        $this->assertNotNull($charge);
        $this->assertEquals(1.0, $charge->days_charged);
        $this->assertEquals(1.0, $charge->days_from_balance);
        $this->assertEquals(0.0, $charge->days_without_pay);

        $this->assertEquals(4, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));
    }

    public function test_the_balance_move_is_on_the_transaction_report(): void
    {
        $user = $this->employee(balance: 5);

        $this->shortBy($user, 480);

        MonthlyShortfall::charge($user, Carbon::parse($this->month.'-01'));

        $transaction = LeaveTransaction::where('user_id', $user->id)->firstOrFail();

        // The balance coming down with nothing on the report to show for it is
        // exactly what made employees distrust the figure before.
        $this->assertEquals(1.0, $transaction->days);
        $this->assertSame('deducted', $transaction->action);
        $this->assertEquals(5.0, $transaction->balance_before);
        $this->assertEquals(4.0, $transaction->balance_after);

        // No leave stands behind it, so the note is what explains it.
        $this->assertNull($transaction->leave_id);
        $this->assertStringContainsString('November 2026', $transaction->note);
        $this->assertStringContainsString('8 h', $transaction->note);
    }

    public function test_with_no_balance_left_it_is_without_pay(): void
    {
        $user = $this->employee(balance: 0);

        $this->shortBy($user, 480);

        $charge = MonthlyShortfall::charge($user, Carbon::parse($this->month.'-01'));

        $this->assertEquals(0.0, $charge->days_from_balance);
        $this->assertEquals(1.0, $charge->days_without_pay);

        // Nothing was written against a balance that had nothing in it.
        $this->assertSame(0, LeaveTransaction::where('user_id', $user->id)->count());
    }

    public function test_a_part_covered_month_is_split_between_the_two(): void
    {
        $user = $this->employee(balance: 0.5);

        $this->shortBy($user, 480);

        $charge = MonthlyShortfall::charge($user, Carbon::parse($this->month.'-01'));

        $this->assertEquals(1.0, $charge->days_charged);
        $this->assertEquals(0.5, $charge->days_from_balance);
        $this->assertEquals(0.5, $charge->days_without_pay);
    }

    public function test_the_unpaid_part_reaches_the_salary_sheet(): void
    {
        // Absences are their own rule; switched off here so the figure under
        // test is the shortfall alone.
        AppSetting::put(\App\Support\AttendanceRules::DEDUCT_ABSENCES, 0);

        $user = $this->employee(balance: 0, salary: 60000);

        $this->shortBy($user, 480);

        MonthlyShortfall::charge($user, Carbon::parse($this->month.'-01'));

        $wop = WithoutPayLeave::forMonth(
            collect([$user->fresh()]),
            Carbon::parse($this->month.'-01')
        )[$user->id];

        $this->assertEquals(1.0, $wop['shortfall_days']);
        $this->assertEquals(1.0, $wop['days']);
        $this->assertGreaterThan(0, $wop['amount']);
    }

    public function test_an_absent_day_is_charged_once_not_twice(): void
    {
        AppSetting::put(\App\Support\AttendanceRules::DEDUCT_ABSENCES, 1);
        AppSetting::put(\App\Support\AttendanceRules::ABSENCES_FROM, '2026-11');

        $user = $this->employee(balance: 0, salary: 60000);

        // One day worked an hour short, and one day not worked at all.
        $this->worked($user, '2026-11-02', '09:00', '16:00');

        Attendance::create([
            'user_id' => $user->id,
            'date'    => '2026-11-03',
            'status'  => 'absent',
        ]);

        MonthlyShortfall::charge($user, Carbon::parse($this->month.'-01'));

        $wop = WithoutPayLeave::forMonth(
            collect([$user->fresh()]),
            Carbon::parse($this->month.'-01')
        )[$user->id];

        // The absent day is a whole day on the absence rule; the short hour
        // is nowhere near half a day and rounds away. The absent day is not
        // also sitting in the hours total.
        $this->assertEquals(0.0, $wop['shortfall_days']);
        $this->assertGreaterThan(0, $wop['absent_days']);
        $this->assertEquals($wop['absent_days'], $wop['days']);
    }

    public function test_a_month_already_settled_is_never_charged_twice(): void
    {
        $user = $this->employee(balance: 5);

        $this->shortBy($user, 480);

        $month = Carbon::parse($this->month.'-01');

        MonthlyShortfall::charge($user, $month->copy());
        MonthlyShortfall::charge($user, $month->copy());
        MonthlyShortfall::charge($user, $month->copy());

        $this->assertSame(1, ShortfallCharge::where('user_id', $user->id)->count());
        $this->assertSame(1, LeaveTransaction::where('user_id', $user->id)->count());
        $this->assertEquals(4, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));
    }

    public function test_a_month_still_running_is_never_charged(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 09:00:00', 'Asia/Karachi'));

        $user = $this->employee(balance: 5);
        $this->shortBy($user, 480, '2026-11-02');

        $this->assertNull(MonthlyShortfall::charge($user, Carbon::parse($this->month.'-01')));

        // Nothing has moved while the employee still has days to make it up.
        $this->assertEquals(5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));
    }

    public function test_nothing_is_charged_when_the_rule_is_switched_off(): void
    {
        AppSetting::put(TimeShortfall::MODE, TimeShortfall::MODE_HALF_DAY);

        $user = $this->employee(balance: 5);
        $this->shortBy($user, 480);

        $this->assertNull(MonthlyShortfall::charge($user, Carbon::parse($this->month.'-01')));
    }

    public function test_the_employee_is_told_what_the_month_came_to(): void
    {
        $user = $this->employee(balance: 5);
        $this->shortBy($user, 480);

        MonthlyShortfall::charge($user, Carbon::parse($this->month.'-01'));

        $mails = collect(app('mailer')->getSymfonyTransport()->messages())
            ->map(fn ($m) => $m->getOriginalMessage()->getTextBody()
                ?: $m->getOriginalMessage()->getBody()->bodyToString());

        $body = $mails->implode("\n");

        $this->assertStringContainsString('Time Short in November 2026', $body);
        $this->assertStringContainsString('8 h', $body);
        $this->assertStringContainsString('1 day(s)', $body);
    }

    /*
    |--------------------------------------------------------------------------
    | The command
    |--------------------------------------------------------------------------
    */

    public function test_the_command_settles_last_month(): void
    {
        $user = $this->employee(balance: 5);
        $this->shortBy($user, 480);

        $this->artisan('attendance:charge-shortfall')->assertSuccessful();

        $this->assertSame(1, ShortfallCharge::where('user_id', $user->id)->count());
    }

    public function test_a_dry_run_charges_nothing(): void
    {
        $user = $this->employee(balance: 5);
        $this->shortBy($user, 480);

        $this->artisan('attendance:charge-shortfall --dry-run')
            ->expectsOutputToContain('nothing was charged')
            ->assertSuccessful();

        $this->assertSame(0, ShortfallCharge::count());
        $this->assertEquals(5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));
    }

    public function test_the_command_does_nothing_under_the_old_rule(): void
    {
        AppSetting::put(TimeShortfall::MODE, TimeShortfall::MODE_HALF_DAY);

        $user = $this->employee(balance: 5);
        $this->shortBy($user, 480);

        $this->artisan('attendance:charge-shortfall')
            ->expectsOutputToContain('not time')
            ->assertSuccessful();

        $this->assertSame(0, ShortfallCharge::count());
    }

    /*
    |--------------------------------------------------------------------------
    | The old rule stands down
    |--------------------------------------------------------------------------
    */

    public function test_leaving_early_no_longer_charges_half_a_day_on_the_spot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 16:30:00', 'Asia/Karachi'));

        $user = $this->employee(balance: 5);

        Attendance::create([
            'user_id'  => $user->id,
            'date'     => '2026-11-02',
            'clock_in' => '2026-11-02 09:00:00',
            'status'   => 'present',
        ]);

        $response = $this->actingAs($user)->postJson(route('attendance.clockout'), [
            'latitude' => 31.5, 'longitude' => 74.3,
        ])->assertOk();

        // Thirty minutes early used to cost four hours of leave. Now it costs
        // thirty minutes, and the employee is told the figure.
        $this->assertSame(0, Leave::where('user_id', $user->id)->count());
        $this->assertEquals(5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));

        $this->assertStringContainsString('30 m', $response->json('message'));
        $this->assertStringContainsString('This month so far', $response->json('message'));
    }

    public function test_the_missing_clock_out_job_stands_down_too(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 18:00:00', 'Asia/Karachi'));

        $user = $this->employee(balance: 5);

        Attendance::create([
            'user_id'  => $user->id,
            'date'     => '2026-11-02',
            'clock_in' => '2026-11-02 09:00:00',
            'status'   => 'present',
        ]);

        $this->artisan('attendance:charge-missing-clockout')
            ->expectsOutputToContain('nothing is charged here')
            ->assertSuccessful();

        $this->assertSame(0, Leave::where('user_id', $user->id)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | The screens
    |--------------------------------------------------------------------------
    */

    public function test_the_employee_sees_their_own_month(): void
    {
        $user = $this->employee(balance: 5);

        $this->worked($user, '2026-11-02', '09:25', '17:00');
        $this->worked($user, '2026-11-03', '09:00', '16:00');

        $response = $this->actingAs($user)
            ->get(route('timing.my', ['month' => $this->month]))
            ->assertOk();

        $sheet = $response->viewData('sheet');

        $this->assertSame(85, $sheet['minutes']);
        $this->assertCount(2, $sheet['days']);

        $response->assertSee('Time short');
        $response->assertSee('Came 25 m late');
        $response->assertSee('Left 1 h early');
    }

    public function test_a_clean_month_reads_as_a_clean_month(): void
    {
        $user = $this->employee();

        $this->worked($user, '2026-11-02', '09:00', '17:00');

        $this->actingAs($user)
            ->get(route('timing.my', ['month' => $this->month]))
            ->assertOk()
            ->assertSee('Every day was worked in full');
    }

    public function test_the_admin_screen_lists_who_was_short(): void
    {
        $user = $this->employee(name: 'Abdul Ahid');
        $this->shortBy($user, 480);

        $clean = $this->employee(name: 'Ali Khawaja');
        $this->worked($clean, '2026-11-02', '09:00', '17:00');

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.attendance.shortfall', ['month' => $this->month]))
            ->assertOk();

        $this->assertCount(1, $response->viewData('rows'));

        $response->assertSee('Abdul Ahid');
        $response->assertDontSee('Ali Khawaja');
        $response->assertSee('Charge November 2026');
    }

    public function test_an_admin_can_settle_the_month_by_hand(): void
    {
        $user = $this->employee(balance: 5);
        $this->shortBy($user, 480);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.attendance.shortfall.charge'), ['month' => $this->month])
            ->assertSessionHas('success');

        $this->assertSame(1, ShortfallCharge::where('user_id', $user->id)->count());
        $this->assertEquals(4, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));
    }

    public function test_a_month_still_running_cannot_be_settled_by_hand(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 09:00:00', 'Asia/Karachi'));

        $user = $this->employee(balance: 5);
        $this->shortBy($user, 480, '2026-11-02');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.attendance.shortfall.charge'), ['month' => $this->month])
            ->assertSessionHas('error');

        $this->assertSame(0, ShortfallCharge::count());
    }

    public function test_the_settings_screen_offers_both_rules(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('How short time is charged')
            ->assertSee('Count the time, settle at month end')
            ->assertSee('Half a day each time');
    }

    public function test_an_admin_can_switch_to_the_time_rule(): void
    {
        AppSetting::put(TimeShortfall::MODE, TimeShortfall::MODE_HALF_DAY);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.settings.attendance-rules.save'), [
                'early_minutes'      => 25,
                'missing_minutes'    => 30,
                'charge_mode'        => 'time',
                'early_grace'        => 20,
                'hours_per_half_day' => 4,
                'time_rules_from'    => '2026-12-01',
            ])
            ->assertSessionHas('success');

        $this->assertTrue(TimeShortfall::enabled());
        $this->assertSame(20, TimeShortfall::earlyGraceMinutes());
        $this->assertSame('2026-12-01', TimeShortfall::rulesFrom()->toDateString());

        $this->assertStringContainsString('counted in minutes', session('success'));
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function employee(float $balance = 0, float $salary = 60000, string $name = 'Employee'): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => 'employee',
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

        LeaveBalance::create([
            'user_id' => $user->id,
            'opening_balance' => $balance,
            'used_leaves' => 0,
            'remaining_leaves' => $balance,
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

    private function worked(User $user, string $date, ?string $in, ?string $out): Attendance
    {
        return Attendance::create([
            'user_id'   => $user->id,
            'date'      => $date,
            'clock_in'  => $in ? $date.' '.$in.':00' : null,
            'clock_out' => $out ? $date.' '.$out.':00' : null,
            'status'    => 'present',
        ]);
    }

    /** Make this employee exactly this many minutes short, on one day. */
    private function shortBy(User $user, int $minutes, string $date = '2026-11-02'): void
    {
        // Measured from the end of the shift backwards, so it is all "left
        // early" and the arithmetic in the test reads straight off the clock.
        $out = Carbon::parse($date.' 17:00:00')->subMinutes($minutes);

        $this->worked($user, $date, '09:00', $out->format('H:i'));
    }

    private function minutesOn(User $user, string $date): int
    {
        return TimeShortfall::forDay(
            $user,
            Carbon::parse($date),
            Attendance::where('user_id', $user->id)->whereDate('date', $date)->first()
        )['minutes'];
    }

    private function monthFor(User $user): array
    {
        return MonthlyShortfall::forUser($user, Carbon::parse($this->month.'-01'));
    }

    /** Every Monday-to-Friday date in November 2026. */
    private function workingDays(): array
    {
        $days = [];

        for (
            $d = Carbon::parse($this->month.'-01');
            $d->month === 11;
            $d->addDay()
        ) {
            if (! $d->isWeekend()) {
                $days[] = $d->toDateString();
            }
        }

        return $days;
    }
}
