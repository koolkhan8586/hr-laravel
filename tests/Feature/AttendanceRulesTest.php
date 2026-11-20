<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AppSetting;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\Salary;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Services\WahaService;
use App\Support\AttendanceRules;
use App\Support\HalfDayCharge;
use App\Support\WithoutPayLeave;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * What a short day costs: leaving early, never marking a clock-out, and
 * being absent with no leave behind it.
 */
class AttendanceRulesTest extends TestCase
{
    use RefreshDatabase;

    /** Tuesday 1 September 2026, a working day on the 9-to-5 shift. */
    private string $date = '2026-09-01';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['mail.default' => 'array']);

        Carbon::setTestNow(Carbon::parse($this->date.' 16:00:00', AttendanceRules::TIMEZONE));

        /*
        | The rules ship with a start date of the day they are deployed, so
        | they never reach backwards. These tests work in 2026, well before
        | that, and are about the rules themselves rather than when they were
        | switched on - so they say so plainly. The tests that are about the
        | start dates set their own.
        */
        AppSetting::put(AttendanceRules::RULES_FROM, '2020-01-01');
        AppSetting::put(AttendanceRules::ABSENCES_FROM, '2020-01');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function employee(float $balance = 0, float $salary = 60000): User
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'tracks_attendance' => true,
            'mobile' => '03001234567',
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

    private function clockedIn(User $user, ?string $date = null): Attendance
    {
        $date ??= $this->date;

        return Attendance::create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => $date.' 08:55:00',
            'status' => 'present',
        ]);
    }

    /** Catches WhatsApp instead of sending it. */
    private function fakeWhatsApp(): array
    {
        $sent = [];

        $waha = Mockery::mock(WahaService::class);
        $waha->shouldReceive('enabled')->andReturn(true);
        $waha->shouldReceive('sendToMobile')->andReturnUsing(
            function ($mobile, $text) use (&$sent) {
                $sent[] = ['mobile' => $mobile, 'text' => $text];
                return true;
            }
        );

        $this->app->instance(WahaService::class, $waha);

        return ['sent' => &$sent];
    }

    private function mails(): array
    {
        return collect(app('mailer')->getSymfonyTransport()->messages())
            ->map(fn ($m) => [
                'subject' => $m->getOriginalMessage()->getSubject(),
                'body' => $m->getOriginalMessage()->getTextBody()
                    ?: $m->getOriginalMessage()->getBody()->bodyToString(),
            ])
            ->all();
    }

    private function clockOut(User $user): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->postJson(route('attendance.clockout'), [
            'latitude' => 31.5,
            'longitude' => 74.3,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Auto clock-out
    |--------------------------------------------------------------------------
    */

    public function test_auto_clock_out_no_longer_closes_an_open_shift(): void
    {
        $user = $this->employee();
        $attendance = $this->clockedIn($user);

        Carbon::setTestNow(Carbon::parse($this->date.' 23:00:00', AttendanceRules::TIMEZONE));

        $this->artisan('attendance:auto-clockout')
            ->expectsOutputToContain('Auto clock-out is switched off')
            ->assertSuccessful();

        $this->assertNull($attendance->fresh()->clock_out);
    }

    public function test_auto_clock_out_can_still_be_switched_back_on(): void
    {
        $user = $this->employee();
        $attendance = $this->clockedIn($user);

        AppSetting::put(AttendanceRules::AUTO_CLOCK_OUT, 1);

        Carbon::setTestNow(Carbon::parse($this->date.' 23:00:00', AttendanceRules::TIMEZONE));

        $this->artisan('attendance:auto-clockout')->assertSuccessful();

        $this->assertNotNull($attendance->fresh()->clock_out);
    }

    /*
    |--------------------------------------------------------------------------
    | Leaving early
    |--------------------------------------------------------------------------
    */

    public function test_clocking_out_well_before_the_shift_ends_costs_half_a_day(): void
    {
        $user = $this->employee(balance: 5);
        $this->clockedIn($user);

        $whatsapp = $this->fakeWhatsApp();

        // Shift ends 17:00; this is 35 minutes early.
        Carbon::setTestNow(Carbon::parse($this->date.' 16:25:00', AttendanceRules::TIMEZONE));

        $this->clockOut($user)->assertOk();

        $leave = Leave::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(AttendanceRules::EARLY_CLOCK_OUT, $leave->auto_reason);
        $this->assertSame('annual', $leave->type);
        $this->assertSame('half_day', $leave->duration_type);
        $this->assertEquals(0.5, $leave->calculated_days);
        $this->assertSame('approved', $leave->status);

        // Off the balance, with a transaction behind it.
        $this->assertEquals(4.5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));
        $this->assertDatabaseHas('leave_transactions', [
            'user_id' => $user->id,
            'leave_id' => $leave->id,
            'action' => 'deducted',
        ]);

        $this->assertSame('half_day', Attendance::where('user_id', $user->id)->value('status'));

        // And the employee was told, on both channels.
        $this->assertStringContainsString('Half Day Charged', collect($this->mails())->pluck('subject')->implode(' '));
        $this->assertStringContainsString('annual leave balance', collect($whatsapp['sent'])->pluck('text')->implode("\n"));
    }

    public function test_leaving_a_few_minutes_early_costs_nothing(): void
    {
        $user = $this->employee(balance: 5);
        $this->clockedIn($user);

        // 20 minutes early, inside the 25 minute rule.
        Carbon::setTestNow(Carbon::parse($this->date.' 16:40:00', AttendanceRules::TIMEZONE));

        $this->clockOut($user)->assertOk();

        $this->assertSame(0, Leave::where('user_id', $user->id)->count());
        $this->assertEquals(5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));
    }

    public function test_with_no_balance_left_the_half_day_is_without_pay(): void
    {
        $user = $this->employee(balance: 0);
        $this->clockedIn($user);

        Carbon::setTestNow(Carbon::parse($this->date.' 16:00:00', AttendanceRules::TIMEZONE));

        $this->clockOut($user)->assertOk();

        $leave = Leave::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('without_pay', $leave->type);
        $this->assertEquals(0, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));

        // Which the salary sheet then deducts: half a day of 60,000 over 30.
        $wop = WithoutPayLeave::forMonth(collect([$user]), Carbon::parse('2026-09-01'))[$user->id];

        $this->assertSame(0.5, $wop['leave_days']);
        $this->assertEquals(1000, $wop['amount']);

        // And the employee was told the amount.
        $this->assertStringContainsString(
            'Rs 1,000 will be deducted',
            collect($this->mails())->pluck('body')->implode("\n")
        );
    }

    public function test_the_early_threshold_can_be_changed(): void
    {
        AppSetting::put(AttendanceRules::EARLY_MINUTES, 60);

        $user = $this->employee(balance: 5);
        $this->clockedIn($user);

        // 35 minutes early, which no longer trips the rule.
        Carbon::setTestNow(Carbon::parse($this->date.' 16:25:00', AttendanceRules::TIMEZONE));

        $this->clockOut($user)->assertOk();

        $this->assertSame(0, Leave::where('user_id', $user->id)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | No clock-out marked
    |--------------------------------------------------------------------------
    */

    public function test_no_clock_out_half_an_hour_after_the_shift_costs_half_a_day(): void
    {
        $user = $this->employee(balance: 1);
        $attendance = $this->clockedIn($user);

        $this->fakeWhatsApp();

        // 17:35, thirty-five minutes after the shift ended.
        Carbon::setTestNow(Carbon::parse($this->date.' 17:35:00', AttendanceRules::TIMEZONE));

        $this->artisan('attendance:charge-missing-clockout')->assertSuccessful();

        $leave = Leave::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(AttendanceRules::MISSING_CLOCK_OUT, $leave->auto_reason);
        $this->assertEquals(0.5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));

        // The day stays open: the system no longer clocks anybody out.
        $this->assertNull($attendance->fresh()->clock_out);
    }

    public function test_the_charge_waits_until_the_deadline_has_passed(): void
    {
        $user = $this->employee(balance: 1);
        $this->clockedIn($user);

        // 17:20, only twenty minutes after the shift ended.
        Carbon::setTestNow(Carbon::parse($this->date.' 17:20:00', AttendanceRules::TIMEZONE));

        $this->artisan('attendance:charge-missing-clockout')->assertSuccessful();

        $this->assertSame(0, Leave::where('user_id', $user->id)->count());
    }

    public function test_it_charges_once_however_often_it_runs(): void
    {
        $user = $this->employee(balance: 5);
        $this->clockedIn($user);

        Carbon::setTestNow(Carbon::parse($this->date.' 18:00:00', AttendanceRules::TIMEZONE));

        $this->artisan('attendance:charge-missing-clockout')->assertSuccessful();
        $this->artisan('attendance:charge-missing-clockout')->assertSuccessful();
        $this->artisan('attendance:charge-missing-clockout')->assertSuccessful();

        $this->assertSame(1, Leave::where('user_id', $user->id)->count());
        $this->assertEquals(4.5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));
    }

    public function test_clocking_out_late_takes_the_charge_back(): void
    {
        $user = $this->employee(balance: 5);
        $this->clockedIn($user);

        Carbon::setTestNow(Carbon::parse($this->date.' 17:35:00', AttendanceRules::TIMEZONE));
        $this->artisan('attendance:charge-missing-clockout')->assertSuccessful();

        $this->assertSame(1, Leave::where('user_id', $user->id)->count());
        $this->assertEquals(4.5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));

        // They remember at 18:00 and clock out after all.
        Carbon::setTestNow(Carbon::parse($this->date.' 18:00:00', AttendanceRules::TIMEZONE));
        $this->clockOut($user)->assertOk();

        $this->assertSame(0, Leave::where('user_id', $user->id)->count());
        $this->assertEquals(5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));

        // The charge and its transaction are gone, not reversed on top.
        $this->assertSame(0, \App\Models\LeaveTransaction::where('user_id', $user->id)->count());
    }

    public function test_overtime_allowed_by_an_admin_pushes_the_deadline_out(): void
    {
        $user = $this->employee(balance: 1);

        $attendance = $this->clockedIn($user);
        $attendance->overtime_allowed_until = $this->date.' 20:00:00';
        $attendance->save();

        Carbon::setTestNow(Carbon::parse($this->date.' 18:00:00', AttendanceRules::TIMEZONE));

        $this->artisan('attendance:charge-missing-clockout')->assertSuccessful();

        $this->assertSame(0, Leave::where('user_id', $user->id)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Only from the day the rules start
    |--------------------------------------------------------------------------
    */

    public function test_a_day_before_the_start_date_is_never_charged(): void
    {
        AppSetting::put(AttendanceRules::RULES_FROM, '2026-09-02');

        $user = $this->employee(balance: 5);
        $this->clockedIn($user);           // the 1st, the day before

        Carbon::setTestNow(Carbon::parse($this->date.' 16:00:00', AttendanceRules::TIMEZONE));

        $this->clockOut($user)->assertOk();

        $this->assertSame(0, Leave::where('user_id', $user->id)->count());
        $this->assertEquals(5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));
    }

    public function test_an_open_shift_from_before_the_start_date_is_left_alone(): void
    {
        AppSetting::put(AttendanceRules::RULES_FROM, '2026-09-02');

        $user = $this->employee(balance: 5);
        $this->clockedIn($user);           // open shift on the 1st

        Carbon::setTestNow(Carbon::parse('2026-09-02 09:00:00', AttendanceRules::TIMEZONE));

        $this->artisan('attendance:charge-missing-clockout')->assertSuccessful();

        $this->assertSame(0, Leave::where('user_id', $user->id)->count());
    }

    public function test_the_start_date_defaults_to_today(): void
    {
        AppSetting::where('key', AttendanceRules::RULES_FROM)->delete();

        $this->assertSame(
            Carbon::now(AttendanceRules::TIMEZONE)->toDateString(),
            AttendanceRules::rulesFrom()->toDateString()
        );

        $this->assertTrue(AttendanceRules::rulesApplyOn(Carbon::now(AttendanceRules::TIMEZONE)));
        $this->assertFalse(AttendanceRules::rulesApplyOn(Carbon::now(AttendanceRules::TIMEZONE)->subDay()));
    }

    public function test_the_start_date_is_saved_from_settings(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.settings.attendance-rules.save'), [
                'early_minutes' => 20,
                'missing_minutes' => 30,
                'rules_from' => '2026-11-20',
                'days_set' => ['saturday', 'sunday'],
            ])
            ->assertSessionHas('success');

        $this->assertSame('2026-11-20', AttendanceRules::rulesFrom()->toDateString());
        $this->assertSame(20, AttendanceRules::earlyMinutes());
        $this->assertTrue(AttendanceRules::saturdayNeedsSchedule());
        $this->assertStringContainsString('20 Nov 2026 onwards', session('success'));
    }

    /*
    |--------------------------------------------------------------------------
    | One employee's own allowance
    |--------------------------------------------------------------------------
    */

    public function test_an_employee_allowed_to_leave_earlier_is_not_charged(): void
    {
        $user = $this->employee(balance: 5);
        $user->forceFill(['early_leave_allowance_minutes' => 60])->save();

        $this->clockedIn($user);

        // 35 minutes early: past the company's 25, inside their own 60.
        Carbon::setTestNow(Carbon::parse($this->date.' 16:25:00', AttendanceRules::TIMEZONE));

        $this->clockOut($user)->assertOk();

        $this->assertSame(0, Leave::where('user_id', $user->id)->count());
        $this->assertEquals(5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));
    }

    public function test_the_allowance_still_has_an_end(): void
    {
        $user = $this->employee(balance: 5);
        $user->forceFill(['early_leave_allowance_minutes' => 60])->save();

        $this->clockedIn($user);

        // 70 minutes early, past their own allowance.
        Carbon::setTestNow(Carbon::parse($this->date.' 15:50:00', AttendanceRules::TIMEZONE));

        $this->clockOut($user)->assertOk();

        $this->assertSame(1, Leave::where('user_id', $user->id)->count());
        $this->assertEquals(4.5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));
    }

    public function test_the_allowance_is_set_on_the_staff_screen(): void
    {
        $user = $this->employee();

        $staff = \App\Models\Staff::where('user_id', $user->id)->firstOrFail();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.staff.edit', $staff->id))
            ->assertOk()
            ->assertSee('Leaving Early Costs Half a Day From');

        $this->actingAs($admin)
            ->put(route('admin.staff.update', $staff->id), [
                'name' => $user->name,
                'email' => $user->email,
                'employee_code' => $user->employee_code ?: 'E'.$user->id,
                'department' => 'IT',
                'designation' => 'Officer',
                'salary' => 60000,
                'role' => 'employee',
                'joining_date' => '2024-01-01',
                'early_leave_allowance_minutes' => 50,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(50, (int) $user->fresh()->early_leave_allowance_minutes);
        $this->assertSame(50, AttendanceRules::earlyMinutesFor($user->fresh()));
    }

    /*
    |--------------------------------------------------------------------------
    | The nudge before the charge
    |--------------------------------------------------------------------------
    */

    public function test_a_reminder_goes_out_ten_minutes_after_the_shift_ends(): void
    {
        $user = $this->employee(balance: 5);
        $attendance = $this->clockedIn($user);

        $whatsapp = $this->fakeWhatsApp();

        // Shift ends 17:00, so the reminder is due at 17:10.
        Carbon::setTestNow(Carbon::parse($this->date.' 17:05:00', AttendanceRules::TIMEZONE));
        $this->artisan('attendance:clockout-reminder')->assertSuccessful();

        $this->assertCount(0, $whatsapp['sent'], 'Too early for a reminder.');

        Carbon::setTestNow(Carbon::parse($this->date.' 17:10:00', AttendanceRules::TIMEZONE));
        $this->artisan('attendance:clockout-reminder')->assertSuccessful();

        $this->assertCount(1, $whatsapp['sent']);

        $text = $whatsapp['sent'][0]['text'];

        $this->assertStringContainsString('Your shift ended at 05:00 PM', $text);
        $this->assertStringContainsString('no clock-out has been marked', $text);

        // And it says what it will cost, and when.
        $this->assertStringContainsString('still not marked by 05:30 PM', $text);
        $this->assertStringContainsString('half a day will be charged', $text);

        $mail = $this->mails()[0] ?? null;

        $this->assertNotNull($mail);
        $this->assertSame('Please mark your clock-out', $mail['subject']);
        $this->assertStringContainsString('Your shift ended at 05:00 PM', $mail['body']);

        $this->assertNotNull($attendance->fresh()->clock_out_reminded_at);
    }

    public function test_the_reminder_is_sent_once_however_often_the_job_runs(): void
    {
        $user = $this->employee(balance: 5);
        $this->clockedIn($user);

        $whatsapp = $this->fakeWhatsApp();

        Carbon::setTestNow(Carbon::parse($this->date.' 17:10:00', AttendanceRules::TIMEZONE));

        $this->artisan('attendance:clockout-reminder')->assertSuccessful();
        $this->artisan('attendance:clockout-reminder')->assertSuccessful();
        $this->artisan('attendance:clockout-reminder')->assertSuccessful();

        $this->assertCount(1, $whatsapp['sent']);
    }

    public function test_somebody_who_clocked_out_is_not_reminded(): void
    {
        $user = $this->employee(balance: 5);

        $attendance = $this->clockedIn($user);
        $attendance->update(['clock_out' => $this->date.' 17:02:00', 'total_hours' => 8.1]);

        $whatsapp = $this->fakeWhatsApp();

        Carbon::setTestNow(Carbon::parse($this->date.' 17:10:00', AttendanceRules::TIMEZONE));
        $this->artisan('attendance:clockout-reminder')->assertSuccessful();

        $this->assertCount(0, $whatsapp['sent']);
    }

    public function test_the_reminder_lands_before_the_charge(): void
    {
        $user = $this->employee(balance: 5);
        $this->clockedIn($user);

        $whatsapp = $this->fakeWhatsApp();

        // Reminder at 17:10, charge at 17:30.
        Carbon::setTestNow(Carbon::parse($this->date.' 17:10:00', AttendanceRules::TIMEZONE));
        $this->artisan('attendance:clockout-reminder')->assertSuccessful();
        $this->artisan('attendance:charge-missing-clockout')->assertSuccessful();

        $this->assertCount(1, $whatsapp['sent']);
        $this->assertSame(0, Leave::where('user_id', $user->id)->count());

        Carbon::setTestNow(Carbon::parse($this->date.' 17:35:00', AttendanceRules::TIMEZONE));
        $this->artisan('attendance:charge-missing-clockout')->assertSuccessful();

        $this->assertSame(1, Leave::where('user_id', $user->id)->count());
    }

    public function test_a_reminder_set_later_than_the_charge_is_pulled_back(): void
    {
        AppSetting::put(AttendanceRules::REMIND_MINUTES, 90);
        AppSetting::put(AttendanceRules::MISSING_MINUTES, 30);

        $this->assertSame(29, AttendanceRules::reminderMinutes());
    }

    public function test_reminders_can_be_switched_off(): void
    {
        AppSetting::put(AttendanceRules::REMIND_CLOCK_OUT, 0);

        $user = $this->employee(balance: 5);
        $this->clockedIn($user);

        $whatsapp = $this->fakeWhatsApp();

        Carbon::setTestNow(Carbon::parse($this->date.' 17:10:00', AttendanceRules::TIMEZONE));
        $this->artisan('attendance:clockout-reminder')
            ->expectsOutputToContain('switched off')
            ->assertSuccessful();

        $this->assertCount(0, $whatsapp['sent']);
    }

    /*
    |--------------------------------------------------------------------------
    | Proving the balance moved
    |--------------------------------------------------------------------------
    */

    public function test_the_charge_is_written_to_the_transaction_report(): void
    {
        $user = $this->employee(balance: 5);
        $this->clockedIn($user);

        Carbon::setTestNow(Carbon::parse($this->date.' 16:00:00', AttendanceRules::TIMEZONE));
        $this->clockOut($user)->assertOk();

        $transaction = \App\Models\LeaveTransaction::where('user_id', $user->id)->firstOrFail();

        // The word the column refused on MySQL, which lost the row and left
        // the balance reduced with nothing to show for it.
        $this->assertSame('deducted', $transaction->action);
        $this->assertEquals(0.5, $transaction->days);
        $this->assertEquals(5, $transaction->balance_before);
        $this->assertEquals(4.5, $transaction->balance_after);
        $this->assertNull($transaction->processed_by);

        $this->assertEquals(4.5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.leave.transactions'))
            ->assertOk()
            ->assertSee('Auto - Left early')
            ->assertSee('deducted');
    }

    public function test_the_balance_is_not_touched_if_the_record_cannot_be_written(): void
    {
        $user = $this->employee(balance: 5);
        $this->clockedIn($user);

        // Whatever goes wrong writing the record, the balance must not move
        // without it: the two are one piece of work now.
        \Illuminate\Support\Facades\DB::statement(
            "CREATE TRIGGER tx_guard BEFORE INSERT ON leave_transactions
             BEGIN SELECT RAISE(ABORT, 'nope'); END"
        );

        Carbon::setTestNow(Carbon::parse($this->date.' 16:00:00', AttendanceRules::TIMEZONE));

        try {
            $this->clockOut($user);
        } catch (\Throwable $e) {
            // The failure is the point; what matters is what it left behind.
        }

        \Illuminate\Support\Facades\DB::statement('DROP TRIGGER tx_guard');

        $this->assertEquals(5, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));
        $this->assertSame(0, \App\Models\LeaveTransaction::where('user_id', $user->id)->count());
    }

    public function test_the_allowance_is_set_on_the_employee_profile(): void
    {
        $user = $this->employee();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('employees.edit', $user->id))
            ->assertOk()
            ->assertSee('Leaving Early Costs Half a Day From');

        $this->actingAs($admin)
            ->post(route('employees.update', $user->id), [
                'name' => $user->name,
                'email' => $user->email,
                'early_leave_allowance_minutes' => 45,
            ])
            ->assertRedirect(route('employees.index'));

        $this->assertSame(45, (int) $user->fresh()->early_leave_allowance_minutes);
        $this->assertSame(45, AttendanceRules::earlyMinutesFor($user->fresh()));

        // Cleared again, they are back on the company rule.
        $this->actingAs($admin)
            ->post(route('employees.update', $user->id), [
                'name' => $user->name,
                'email' => $user->email,
                'early_leave_allowance_minutes' => '',
            ]);

        $this->assertNull($user->fresh()->early_leave_allowance_minutes);
        $this->assertSame(25, AttendanceRules::earlyMinutesFor($user->fresh()));
    }

    /*
    |--------------------------------------------------------------------------
    | Saying what the system charged
    |--------------------------------------------------------------------------
    */

    public function test_a_charged_half_day_says_the_system_charged_it(): void
    {
        $user = $this->employee(balance: 5);
        $this->clockedIn($user);

        Carbon::setTestNow(Carbon::parse($this->date.' 16:00:00', AttendanceRules::TIMEZONE));
        $this->clockOut($user)->assertOk();

        $leave = Leave::where('user_id', $user->id)->firstOrFail();

        $this->assertTrue($leave->isAutoCharged());
        $this->assertSame('System', $leave->decidedByLabel());
        $this->assertSame('Left early', $leave->decidedViaLabel());
        $this->assertSame('Taken off the leave balance', $leave->chargedAgainstLabel());

        $admin = User::factory()->create(['role' => 'admin']);

        // On the leave list.
        $this->actingAs($admin)
            ->get(route('admin.leave.index'))
            ->assertOk()
            ->assertSee('Auto - Left early')
            ->assertSee('Taken off the leave balance');

        // And on the transaction report, where the balance moved.
        $this->actingAs($admin)
            ->get(route('admin.leave.transactions'))
            ->assertOk()
            ->assertSee('Auto - Left early')
            ->assertSee('System');
    }

    public function test_an_unpaid_half_day_says_where_it_went_instead(): void
    {
        $user = $this->employee(balance: 0);
        $this->clockedIn($user);

        Carbon::setTestNow(Carbon::parse($this->date.' 16:00:00', AttendanceRules::TIMEZONE));
        $this->clockOut($user)->assertOk();

        $leave = Leave::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('without_pay', $leave->type);
        $this->assertSame('No balance left, so taken without pay', $leave->chargedAgainstLabel());

        // No balance moved, so there is nothing on the transaction report -
        // the leave list is where it shows.
        $this->assertSame(0, \App\Models\LeaveTransaction::where('user_id', $user->id)->count());

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.leave.index'))
            ->assertOk()
            ->assertSee('No balance left, so taken without pay');
    }

    /*
    |--------------------------------------------------------------------------
    | What is never charged
    |--------------------------------------------------------------------------
    */

    public function test_a_day_already_covered_by_leave_is_not_charged_again(): void
    {
        $user = $this->employee(balance: 5);
        $this->clockedIn($user);

        Leave::create([
            'user_id' => $user->id,
            'type' => 'annual',
            'start_date' => $this->date,
            'end_date' => $this->date,
            'duration_type' => 'half_day',
            'half_day_type' => 'afternoon',
            'days' => 0.5,
            'calculated_days' => 0.5,
            'status' => 'approved',
            'reason' => 'Applied for',
        ]);

        Carbon::setTestNow(Carbon::parse($this->date.' 16:00:00', AttendanceRules::TIMEZONE));

        $this->clockOut($user)->assertOk();

        $this->assertSame(1, Leave::where('user_id', $user->id)->count());
        $this->assertNull(Leave::where('user_id', $user->id)->value('auto_reason'));
    }

    public function test_a_holiday_is_not_charged(): void
    {
        $user = $this->employee(balance: 5);
        $this->clockedIn($user);

        Holiday::create([
            'title' => 'Public holiday',
            'start_date' => $this->date,
            'end_date' => $this->date,
            'for_all' => 1,
        ]);

        Carbon::setTestNow(Carbon::parse($this->date.' 16:00:00', AttendanceRules::TIMEZONE));

        $this->clockOut($user)->assertOk();

        $this->assertSame(0, Leave::where('user_id', $user->id)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Absent with no leave
    |--------------------------------------------------------------------------
    */

    public function test_days_absent_with_no_leave_are_deducted(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 10:00:00', AttendanceRules::TIMEZONE));

        $user = $this->employee(salary: 60000);

        // Two working days absent, one covered by approved leave.
        foreach (['2026-09-01', '2026-09-02'] as $date) {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $date,
                'clock_in' => null,
                'status' => 'absent',
            ]);
        }

        Leave::create([
            'user_id' => $user->id,
            'type' => 'annual',
            'start_date' => '2026-09-03',
            'end_date' => '2026-09-03',
            'duration_type' => 'full_day',
            'days' => 1,
            'calculated_days' => 1,
            'status' => 'approved',
            'reason' => 'Applied for',
        ]);

        // The rest of the month was worked, so only those two days count.
        for ($day = 4; $day <= 30; $day++) {

            $date = Carbon::create(2026, 9, $day);

            if ($date->isWeekend()) {
                continue;
            }

            Attendance::create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'clock_in' => $date->toDateString().' 08:55:00',
                'clock_out' => $date->toDateString().' 17:05:00',
                'total_hours' => 8.17,
                'status' => 'present',
            ]);
        }

        $wop = WithoutPayLeave::forMonth(collect([$user]), Carbon::parse('2026-09-01'))[$user->id];

        $this->assertSame(2.0, $wop['absent_days']);
        $this->assertSame(0.0, $wop['leave_days']);
        $this->assertSame(2.0, $wop['days']);
        $this->assertEquals(4000, $wop['amount']);   // 60,000 over 30 days
    }

    public function test_absences_before_the_start_month_are_never_charged(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 10:00:00', AttendanceRules::TIMEZONE));

        AppSetting::put(AttendanceRules::ABSENCES_FROM, '2026-10');

        $user = $this->employee();

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-01',
            'clock_in' => null,
            'status' => 'absent',
        ]);

        // September is behind the line, so nothing is charged for it.
        $september = WithoutPayLeave::forMonth(collect([$user]), Carbon::parse('2026-09-01'))[$user->id];

        $this->assertSame(0.0, $september['absent_days']);
        $this->assertEquals(0, $september['amount']);

        // October is on it, and is charged.
        $october = WithoutPayLeave::forMonth(collect([$user]), Carbon::parse('2026-10-01'))[$user->id];

        $this->assertGreaterThan(0, $october['absent_days']);
    }

    public function test_switching_the_rule_on_starts_from_this_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 10:00:00', AttendanceRules::TIMEZONE));

        // Never saved before, so saving it now pins it to this month.
        AppSetting::where('key', AttendanceRules::ABSENCES_FROM)->delete();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.settings.attendance-rules.save'), [
                'early_minutes' => 25,
                'missing_minutes' => 30,
                'deduct_absences' => 1,
            ])
            ->assertSessionHas('success');

        $this->assertSame('2026-10', AttendanceRules::absencesFrom()->format('Y-m'));
        $this->assertFalse(AttendanceRules::deductsAbsencesIn(Carbon::parse('2026-09-01')));
        $this->assertTrue(AttendanceRules::deductsAbsencesIn(Carbon::parse('2026-10-01')));

        $this->assertStringContainsString('October 2026 onwards', session('success'));
    }

    public function test_absences_are_left_alone_when_the_rule_is_switched_off(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 10:00:00', AttendanceRules::TIMEZONE));

        AppSetting::put(AttendanceRules::DEDUCT_ABSENCES, 0);

        $user = $this->employee();

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-01',
            'clock_in' => null,
            'status' => 'absent',
        ]);

        $wop = WithoutPayLeave::forMonth(collect([$user]), Carbon::parse('2026-09-01'))[$user->id];

        $this->assertSame(0.0, $wop['absent_days']);
        $this->assertEquals(0, $wop['amount']);
    }

    public function test_nobody_is_absent_before_the_day_they_joined(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 10:00:00', AttendanceRules::TIMEZONE));

        $user = $this->employee();
        $user->staff->update(['joining_date' => '2026-09-21']);

        $wop = WithoutPayLeave::forMonth(collect([$user]), Carbon::parse('2026-09-01'))[$user->id];

        // Only the working days from the 21st onward can be absences.
        $this->assertSame(8.0, $wop['absent_days']);
    }

    public function test_payroll_only_staff_are_never_charged_for_absence(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 10:00:00', AttendanceRules::TIMEZONE));

        $user = $this->employee();
        $user->forceFill(['tracks_attendance' => false])->save();

        $wop = WithoutPayLeave::forMonth(collect([$user->fresh()]), Carbon::parse('2026-09-01'))[$user->id];

        $this->assertSame(0.0, $wop['absent_days']);
    }

    public function test_marking_absent_skips_leave_holidays_and_days_off(): void
    {
        $onLeave = $this->employee();
        $onHoliday = $this->employee();
        $plainlyAbsent = $this->employee();

        Leave::create([
            'user_id' => $onLeave->id,
            'type' => 'annual',
            'start_date' => $this->date,
            'end_date' => $this->date,
            'duration_type' => 'full_day',
            'days' => 1,
            'calculated_days' => 1,
            'status' => 'approved',
            'reason' => 'Applied for',
        ]);

        $holiday = Holiday::create([
            'title' => 'Department holiday',
            'start_date' => $this->date,
            'end_date' => $this->date,
            'for_all' => 0,
        ]);

        $holiday->users()->attach($onHoliday->id);

        Carbon::setTestNow(Carbon::parse($this->date.' 12:00:00', AttendanceRules::TIMEZONE));

        $this->artisan('attendance:mark-absent')->assertSuccessful();

        $this->assertSame(0, Attendance::where('user_id', $onLeave->id)->count());
        $this->assertSame(0, Attendance::where('user_id', $onHoliday->id)->count());

        $row = Attendance::where('user_id', $plainlyAbsent->id)->firstOrFail();

        $this->assertSame('absent', $row->status);

        // With the date on it, which it used to leave empty.
        $this->assertSame($this->date, Carbon::parse($row->date)->toDateString());

        // And running it again does not write a second row.
        $this->artisan('attendance:mark-absent')->assertSuccessful();

        $this->assertSame(1, Attendance::where('user_id', $plainlyAbsent->id)->count());
    }

    public function test_the_rules_can_be_saved_from_settings(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.settings.attendance-rules.save'), [
                'early_minutes' => 40,
                'missing_minutes' => 45,
                'deduct_absences' => 1,
            ])
            ->assertSessionHas('success');

        $this->assertSame(40, AttendanceRules::earlyMinutes());
        $this->assertSame(45, AttendanceRules::missingMinutes());
        $this->assertTrue(AttendanceRules::deductsAbsences());

        // The box left unticked switches auto clock-out off.
        $this->assertFalse(AttendanceRules::autoClockOutEnabled());
    }

    public function test_the_settings_screen_is_admin_only(): void
    {
        $this->actingAs($this->employee())
            ->post(route('admin.settings.attendance-rules.save'), [
                'early_minutes' => 40,
                'missing_minutes' => 45,
            ])
            ->assertForbidden();
    }
}
