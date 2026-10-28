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

    /*
    |--------------------------------------------------------------------------
    | Telling the employee what an approved unpaid leave will cost them
    |--------------------------------------------------------------------------
    */

    /** The bodies of the mails sent during this test. */
    private function sentMails(): array
    {
        return collect(app('mailer')->getSymfonyTransport()->messages())
            ->map(fn ($message) => [
                'subject' => $message->getOriginalMessage()->getSubject(),
                'body' => $message->getOriginalMessage()->getTextBody()
                    ?? $message->getOriginalMessage()->getBody()->bodyToString(),
            ])
            ->all();
    }

    /** Catches whatever is sent on WhatsApp, instead of sending it. */
    private function fakeWhatsApp(): array
    {
        $sent = [];

        $waha = \Mockery::mock(\App\Services\WahaService::class);
        $waha->shouldReceive('enabled')->andReturn(true);
        $waha->shouldReceive('sendToMobile')
            ->andReturnUsing(function ($mobile, $text) use (&$sent) {
                $sent[] = ['mobile' => $mobile, 'text' => $text];
                return true;
            });

        $this->app->instance(\App\Services\WahaService::class, $waha);

        return ['sent' => &$sent];
    }

    public function test_approving_unpaid_leave_tells_the_employee_what_it_costs(): void
    {
        config(['mail.default' => 'array']);

        $user = $this->employee(60000);
        $user->forceFill(['mobile' => '03001234567'])->save();

        $whatsapp = $this->fakeWhatsApp();

        $leave = $this->leave($user, '2026-09-01', '2026-09-03');
        $leave->update(['status' => 'pending']);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.leave.approve', $leave->id))
            ->assertRedirect(route('admin.leave.index'));

        $this->assertSame('approved', $leave->fresh()->status);

        $mail = $this->sentMails()[0] ?? null;

        $this->assertNotNull($mail, 'The employee should have been emailed.');
        $this->assertSame('Leave Approved (Without Pay)', $mail['subject']);
        $this->assertStringContainsString('This leave is without pay.', $mail['body']);
        $this->assertStringContainsString('Rs 6,000 will be deducted from your September 2026 salary', $mail['body']);
        $this->assertStringContainsString('3 day(s) at Rs 2,000.00 a day', $mail['body']);

        // No salary sheet for September yet, so the figure came from the
        // staff record - and says so, rather than reading as final.
        $this->assertStringContainsString('salary on record', $mail['body']);

        $this->assertCount(1, $whatsapp['sent']);
        $this->assertSame('03001234567', $whatsapp['sent'][0]['mobile']);
        $this->assertStringContainsString('Leave Approved (Without Pay)', $whatsapp['sent'][0]['text']);
        $this->assertStringContainsString('Rs 6,000 will be deducted', $whatsapp['sent'][0]['text']);
    }

    public function test_approving_paid_leave_says_nothing_about_deductions(): void
    {
        config(['mail.default' => 'array']);

        $user = $this->employee(60000);
        $leave = $this->leave($user, '2026-09-01', '2026-09-03', ['type' => 'annual', 'status' => 'pending']);

        \App\Models\LeaveBalance::create([
            'user_id' => $user->id,
            'opening_balance' => 20,
            'used_leaves' => 0,
            'remaining_leaves' => 20,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.leave.approve', $leave->id));

        $mail = $this->sentMails()[0];

        $this->assertSame('Leave Approved', $mail['subject']);
        $this->assertStringNotContainsString('without pay', $mail['body']);
        $this->assertStringNotContainsString('deducted', $mail['body']);
    }

    public function test_the_figure_reads_as_final_once_the_month_has_a_salary_sheet(): void
    {
        $user = $this->employee(60000);

        Salary::create([
            'user_id' => $user->id,
            'month' => 9,
            'year' => 2026,
            'basic_salary' => 90000,
            'status' => 'draft',
        ]);

        $leave = $this->leave($user, '2026-09-01', '2026-09-01');

        $lines = implode("\n", WithoutPayLeave::noticeLines($leave));

        $this->assertStringContainsString('Rs 3,000 will be deducted', $lines);
        $this->assertStringNotContainsString('salary on record', $lines);
    }

    public function test_a_leave_running_into_the_next_month_is_priced_month_by_month(): void
    {
        $user = $this->employee(62000);

        // Mon 28 September to Fri 2 October: three days in September at
        // 62,000/30, two in October at 62,000/31.
        $leave = $this->leave($user, '2026-09-28', '2026-10-02');

        $cost = WithoutPayLeave::forLeave($leave);

        $this->assertSame(5.0, $cost['days']);
        $this->assertCount(2, $cost['months']);

        $this->assertSame('September 2026', $cost['months'][0]['label']);
        $this->assertSame(3.0, $cost['months'][0]['days']);
        $this->assertEquals(6200, $cost['months'][0]['amount']);

        $this->assertSame('October 2026', $cost['months'][1]['label']);
        $this->assertSame(2.0, $cost['months'][1]['days']);
        $this->assertEquals(4000, $cost['months'][1]['amount']);

        $this->assertEquals(10200, $cost['amount']);

        $lines = implode("\n", WithoutPayLeave::noticeLines($leave));

        $this->assertStringContainsString('September 2026 salary', $lines);
        $this->assertStringContainsString('October 2026 salary', $lines);
        $this->assertStringContainsString('Total to be deducted: Rs 10,200', $lines);
    }

    public function test_an_unpaid_leave_with_no_salary_on_file_still_says_how_many_days(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $leave = $this->leave($user, '2026-09-01', '2026-09-02');

        $lines = implode("\n", WithoutPayLeave::noticeLines($leave));

        $this->assertStringContainsString('2 day(s) will be deducted', $lines);
        $this->assertStringContainsString('worked out from your salary', $lines);
        $this->assertStringNotContainsString('Rs 0', $lines);
    }

    public function test_an_unpaid_leave_falling_only_on_days_off_says_nothing(): void
    {
        $user = $this->employee(60000);

        // Saturday and Sunday: not days they were due in.
        $leave = $this->leave($user, '2026-09-05', '2026-09-06');

        $this->assertNull(WithoutPayLeave::noticeLines($leave));
    }

    public function test_a_failing_notification_does_not_undo_the_approval(): void
    {
        $user = $this->employee(60000);
        $user->forceFill(['mobile' => '03001234567'])->save();

        $leave = $this->leave($user, '2026-09-01', '2026-09-03');
        $leave->update(['status' => 'pending']);

        // Both channels broken at once.
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'localhost', 'mail.mailers.smtp.port' => 1]);

        $waha = \Mockery::mock(\App\Services\WahaService::class);
        $waha->shouldReceive('sendToMobile')->andThrow(new \RuntimeException('WAHA is down'));
        $this->app->instance(\App\Services\WahaService::class, $waha);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.leave.approve', $leave->id))
            ->assertRedirect(route('admin.leave.index'))
            ->assertSessionHas('success');

        $this->assertSame('approved', $leave->fresh()->status);
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
