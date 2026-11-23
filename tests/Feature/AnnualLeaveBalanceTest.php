<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\User;
use App\Services\LeaveWhatsAppNotifier;
use App\Support\LeaveEntitlement;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

/**
 * Annual leave is paid out of a balance. Asking for more of it than is left
 * takes the leave without pay instead of leaving the application stuck.
 */
class AnnualLeaveBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();

        Carbon::setTestNow(Carbon::parse('2026-11-23 10:00:00', 'Asia/Karachi'));

        // Nothing leaves the machine when somebody applies.
        $notifier = Mockery::mock(LeaveWhatsAppNotifier::class);
        $notifier->shouldReceive('notifyApprovers')->andReturn([
            'ok' => true, 'sent' => 1, 'failed' => 0, 'total' => 1,
            'status' => 'sent', 'message' => 'sent',
        ]);
        $this->app->instance(LeaveWhatsAppNotifier::class, $notifier);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function employee(float $balance): User
    {
        $user = User::factory()->create(['role' => 'employee']);

        LeaveBalance::create([
            'user_id' => $user->id,
            'opening_balance' => $balance,
            'used_leaves' => 0,
            'remaining_leaves' => $balance,
        ]);

        return $user;
    }

    private function apply(User $user, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->post(route('leave.store'), $overrides + [
            'type' => 'annual',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'duration_type' => 'full_day',
            'reason' => 'Family',
        ]);
    }

    public function test_with_no_balance_the_leave_is_taken_without_pay(): void
    {
        $user = $this->employee(0);

        $this->apply($user)->assertSessionHas('warning');

        $leave = Leave::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('without_pay', $leave->type);
        $this->assertSame('pending', $leave->status);

        $this->assertStringContainsString(
            'no annual leave balance left',
            session('warning')
        );
    }

    public function test_with_too_little_balance_it_is_also_without_pay(): void
    {
        $user = $this->employee(1);

        // Two days asked for, one left.
        $this->apply($user)->assertSessionHas('warning');

        $this->assertSame('without_pay', Leave::where('user_id', $user->id)->value('type'));

        $this->assertStringContainsString('Only 1 day(s)', session('warning'));
        $this->assertStringContainsString('2 were asked for', session('warning'));
    }

    public function test_with_enough_balance_it_stays_annual(): void
    {
        $user = $this->employee(5);

        $this->apply($user)->assertSessionMissing('warning');

        $this->assertSame('annual', Leave::where('user_id', $user->id)->value('type'));
    }

    public function test_the_balance_is_what_is_left_after_leave_already_approved(): void
    {
        $user = $this->employee(3);

        // Two days already approved leaves one.
        Leave::create([
            'user_id' => $user->id,
            'type' => 'annual',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-11',
            'duration_type' => 'full_day',
            'days' => 2,
            'calculated_days' => 2,
            'status' => 'approved',
            'reason' => 'Earlier leave',
        ]);

        $this->assertEquals(1, LeaveEntitlement::remainingFor($user->id));

        $this->apply($user);

        $this->assertSame(
            'without_pay',
            Leave::where('user_id', $user->id)->whereDate('start_date', '2026-12-01')->value('type')
        );
    }

    public function test_half_a_day_needs_only_half_a_day_of_balance(): void
    {
        $user = $this->employee(0.5);

        $this->apply($user, [
            'end_date' => '2026-12-01',
            'duration_type' => 'half_day',
            'half_day_type' => 'morning',
        ]);

        $this->assertSame('annual', Leave::where('user_id', $user->id)->value('type'));
    }

    public function test_asking_for_leave_without_pay_is_left_exactly_as_asked(): void
    {
        $user = $this->employee(10);

        $this->apply($user, ['type' => 'without_pay'])->assertSessionMissing('warning');

        $this->assertSame('without_pay', Leave::where('user_id', $user->id)->value('type'));
    }

    public function test_approving_a_leave_whose_balance_went_takes_it_without_pay(): void
    {
        $user = $this->employee(2);

        // Applied while there was balance.
        $leave = Leave::create([
            'user_id' => $user->id,
            'type' => 'annual',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'duration_type' => 'full_day',
            'days' => 2,
            'calculated_days' => 2,
            'status' => 'pending',
            'reason' => 'Family',
        ]);

        // Something else was approved first and used it up.
        Leave::create([
            'user_id' => $user->id,
            'type' => 'annual',
            'start_date' => '2026-11-24',
            'end_date' => '2026-11-25',
            'duration_type' => 'full_day',
            'days' => 2,
            'calculated_days' => 2,
            'status' => 'approved',
            'reason' => 'Went first',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.leave.approve', $leave->id))
            ->assertSessionHas('success');

        $leave->refresh();

        // Approved, not stuck - and unpaid, which it always was going to be.
        $this->assertSame('approved', $leave->status);
        $this->assertSame('without_pay', $leave->type);

        $this->assertStringContainsString('Without Pay', session('success'));

        // The balance is untouched: an unpaid leave does not spend it.
        $this->assertEquals(0, LeaveEntitlement::remainingFor($user->id));
    }

    public function test_an_approval_with_balance_still_spends_it(): void
    {
        $user = $this->employee(5);

        $leave = Leave::create([
            'user_id' => $user->id,
            'type' => 'annual',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'duration_type' => 'full_day',
            'days' => 2,
            'calculated_days' => 2,
            'status' => 'pending',
            'reason' => 'Family',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.leave.approve', $leave->id));

        $this->assertSame('annual', $leave->fresh()->type);
        $this->assertEquals(3, LeaveBalance::where('user_id', $user->id)->value('remaining_leaves'));
        $this->assertDatabaseHas('leave_transactions', [
            'leave_id' => $leave->id,
            'days' => 2,
        ]);
    }

    public function test_editing_a_leave_does_not_spend_its_own_days_twice(): void
    {
        $user = $this->employee(2);

        $leave = Leave::create([
            'user_id' => $user->id,
            'type' => 'annual',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'duration_type' => 'full_day',
            'days' => 2,
            'calculated_days' => 2,
            'status' => 'approved',
            'reason' => 'Family',
        ]);

        // The whole balance is now used by this leave; editing it must not
        // read that as "nothing left" and turn it unpaid.
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put(route('admin.leave.update', $leave->id), [
                'type' => 'annual',
                'start_date' => '2026-12-03',
                'end_date' => '2026-12-04',
                'duration_type' => 'full_day',
                'reason' => 'Moved',
            ])
            ->assertSessionMissing('warning');

        $this->assertSame('annual', $leave->fresh()->type);
    }

    public function test_an_edit_beyond_the_balance_becomes_without_pay(): void
    {
        $user = $this->employee(2);

        $leave = Leave::create([
            'user_id' => $user->id,
            'type' => 'annual',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'duration_type' => 'full_day',
            'days' => 2,
            'calculated_days' => 2,
            'status' => 'approved',
            'reason' => 'Family',
        ]);

        // Stretched to four days against a balance of two.
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put(route('admin.leave.update', $leave->id), [
                'type' => 'annual',
                'start_date' => '2026-12-01',
                'end_date' => '2026-12-04',
                'duration_type' => 'full_day',
                'reason' => 'Longer',
            ])
            ->assertSessionHas('warning');

        $this->assertSame('without_pay', $leave->fresh()->type);
    }

    public function test_the_apply_form_says_what_is_left(): void
    {
        $user = $this->employee(3);

        $response = $this->actingAs($user)->get(route('leave.create'))->assertOk();

        $this->assertEquals(3, $response->viewData('balances')[$user->id]);

        $response->assertSee('balanceNote', false);
    }
}
