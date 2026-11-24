<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Support\AttendanceRules;
use App\Support\TimeShortfall;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sunday is off for everybody, even for the employees who have it sitting in
 * their weekly pattern. An absent row written for one is not an absence.
 */
class SundayAbsenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['mail.default' => 'array']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Somebody whose weekly pattern lists every day, Sunday included. */
    private function sevenDayEmployee(string $name = 'Kashif Vicky'): User
    {
        $user = User::factory()->create(['name' => $name, 'role' => 'employee', 'tracks_attendance' => true]);

        Staff::create([
            'user_id' => $user->id, 'employee_id' => 'E'.$user->id, 'department' => 'IT',
            'designation' => 'Officer', 'salary' => 60000, 'joining_date' => '2024-01-01', 'status' => 'active',
        ]);

        $shift = Shift::firstOrCreate(
            ['name' => '9 to 5'],
            ['start_time' => '09:00:00', 'end_time' => '17:00:00', 'grace_minutes' => 15]
        );

        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
            WeeklySchedule::setFor($user->id, $day, $shift->id);
        }

        return $user;
    }

    public function test_the_nightly_job_does_not_mark_absent_on_a_sunday(): void
    {
        // Sunday 22 November 2026, after the 11:45 cut-off.
        Carbon::setTestNow(Carbon::parse('2026-11-22 12:30:00', 'Asia/Karachi'));

        $user = $this->sevenDayEmployee();

        $this->artisan('attendance:mark-absent')->assertSuccessful();

        $this->assertSame(0, Attendance::where('user_id', $user->id)->count());
    }

    public function test_it_still_marks_absent_on_a_working_day(): void
    {
        // Tuesday 24 November 2026.
        Carbon::setTestNow(Carbon::parse('2026-11-24 12:30:00', 'Asia/Karachi'));

        $user = $this->sevenDayEmployee();

        $this->artisan('attendance:mark-absent')->assertSuccessful();

        $this->assertSame(1, Attendance::where('user_id', $user->id)->where('status', 'absent')->count());
    }

    public function test_a_sunday_that_was_scheduled_for_the_date_is_still_a_working_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-22 12:30:00', 'Asia/Karachi'));

        $user = $this->sevenDayEmployee();

        $shift = Shift::first();

        \App\Models\EmployeeSchedule::create([
            'user_id' => $user->id, 'date' => '2026-11-22', 'shift_id' => $shift->id,
        ]);

        $this->artisan('attendance:mark-absent')->assertSuccessful();

        // Somebody put them on that Sunday on the monthly schedule, so it is
        // theirs to be absent from.
        $this->assertSame(1, Attendance::where('user_id', $user->id)->where('status', 'absent')->count());
    }

    public function test_an_old_absent_row_on_a_sunday_is_not_drawn_as_an_absence(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 10:00:00', 'Asia/Karachi'));

        $user = $this->sevenDayEmployee();

        // Written by the job before it knew better.
        Attendance::create(['user_id' => $user->id, 'date' => '2026-11-22', 'status' => 'absent']);

        $page = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.attendance.calendar', ['month' => '2026-11']))
            ->assertOk();

        // A recorded absence is drawn with exactly this title.
        $page->assertDontSee('title="Absent">', false);
    }

    public function test_a_real_absence_on_a_working_day_is_still_drawn(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 10:00:00', 'Asia/Karachi'));

        $user = $this->sevenDayEmployee();

        Attendance::create(['user_id' => $user->id, 'date' => '2026-11-18', 'status' => 'absent']);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.attendance.calendar', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee('title="Absent">', false);
    }

    public function test_the_clean_up_deletes_only_absences_on_days_that_were_off(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 10:00:00', 'Asia/Karachi'));

        $user = $this->sevenDayEmployee();

        $sunday  = Attendance::create(['user_id' => $user->id, 'date' => '2026-11-22', 'status' => 'absent']);
        $tuesday = Attendance::create(['user_id' => $user->id, 'date' => '2026-11-17', 'status' => 'absent']);

        // Came in on a Sunday: that is work, whatever the roster says.
        $worked = Attendance::create([
            'user_id' => $user->id, 'date' => '2026-11-15',
            'clock_in' => '2026-11-15 10:00:00', 'status' => 'present',
        ]);

        $this->artisan('attendance:clean-stray-absences --from=2026-11-01')->assertSuccessful();

        $this->assertNull(Attendance::find($sunday->id));
        $this->assertNotNull(Attendance::find($tuesday->id));
        $this->assertNotNull(Attendance::find($worked->id));
    }

    public function test_the_clean_up_dry_run_deletes_nothing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 10:00:00', 'Asia/Karachi'));

        $user = $this->sevenDayEmployee();

        $sunday = Attendance::create(['user_id' => $user->id, 'date' => '2026-11-22', 'status' => 'absent']);

        $this->artisan('attendance:clean-stray-absences --from=2026-11-01 --dry-run')
            ->expectsOutputToContain('1 row(s) would be deleted')
            ->assertSuccessful();

        $this->assertNotNull(Attendance::find($sunday->id));
    }

    public function test_the_clean_up_leaves_somebody_with_no_roster_alone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 10:00:00', 'Asia/Karachi'));

        $user = User::factory()->create(['role' => 'employee']);

        $row = Attendance::create(['user_id' => $user->id, 'date' => '2026-11-22', 'status' => 'absent']);

        $this->artisan('attendance:clean-stray-absences --from=2026-11-01')->assertSuccessful();

        // Nothing says they were off, so nothing is deleted on a guess.
        $this->assertNotNull(Attendance::find($row->id));
    }

    /*
    |--------------------------------------------------------------------------
    | The clock-out reminder
    |--------------------------------------------------------------------------
    */

    private function remind(): string
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 17:12:00', 'Asia/Karachi'));

        $user = $this->sevenDayEmployee('Ali Khawaja');

        Attendance::create([
            'user_id' => $user->id, 'date' => '2026-11-24',
            'clock_in' => '2026-11-24 09:00:00', 'status' => 'present',
        ]);

        $this->artisan('attendance:clockout-reminder')->assertSuccessful();

        $mail = collect(app('mailer')->getSymfonyTransport()->messages())->first();

        $this->assertNotNull($mail, 'No reminder was sent.');
        $this->assertNotNull(Attendance::where('user_id', $user->id)->value('clock_out_reminded_at'));

        return $mail->getOriginalMessage()->getTextBody()
            ?: $mail->getOriginalMessage()->getBody()->bodyToString();
    }

    public function test_the_reminder_goes_ten_minutes_after_the_shift_and_is_recorded(): void
    {
        AppSetting::put(AttendanceRules::RULES_FROM, '2020-01-01');

        $body = $this->remind();

        $this->assertStringContainsString('Your shift ended at 05:00 PM', $body);
        $this->assertStringContainsString('half a day will be charged', $body);
    }

    public function test_under_the_time_rule_the_reminder_does_not_promise_a_half_day(): void
    {
        AppSetting::put(TimeShortfall::MODE, TimeShortfall::MODE_TIME);
        AppSetting::put(TimeShortfall::RULES_FROM, '2020-01-01');
        AppSetting::put(AttendanceRules::RULES_FROM, '2020-01-01');

        $body = $this->remind();

        $this->assertStringNotContainsString('half a day will be charged', $body);
        $this->assertStringContainsString('time short', $body);
        $this->assertStringContainsString('end of the month', $body);
    }
}
