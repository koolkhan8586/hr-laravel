<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanApprover;
use App\Models\LoanLedger;
use App\Models\User;
use App\Services\LoanWhatsAppNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * An employee applying for a loan, and the people who decide it.
 */
class LoanApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Nothing should leave the machine during a test.
        $notifier = Mockery::mock(LoanWhatsAppNotifier::class);
        $notifier->shouldReceive('notifyApprovers')->andReturn([
            'ok' => true, 'sent' => 1, 'failed' => 0, 'total' => 1,
            'status' => 'sent', 'message' => 'WhatsApp sent to 1 loan approver(s).',
        ]);
        $this->app->instance(LoanWhatsAppNotifier::class, $notifier);
    }

    private function employee(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role' => 'employee']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function application(User $employee, array $attributes = []): Loan
    {
        return Loan::create($attributes + [
            'user_id' => $employee->id,
            'amount' => 120000,
            'installments' => 12,
            'monthly_deduction' => 10000,
            'remaining_balance' => 0,
            'status' => 'pending',
            'purpose' => 'School fees',
            'requested_at' => now(),
        ]);
    }

    public function test_employee_can_submit_an_application(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee)
            ->post(route('loan.store.request'), [
                'amount' => 120000,
                'installments' => 12,
                'purpose' => 'School fees',
                'deduction_start_month' => '2026-12',
            ])
            ->assertRedirect(route('loan.apply'));

        $loan = Loan::where('user_id', $employee->id)->firstOrFail();

        $this->assertSame('pending', $loan->status);
        $this->assertEquals(10000, $loan->monthly_deduction);
        $this->assertSame('School fees', $loan->purpose);
        $this->assertSame('2026-12-01', $loan->deduction_start_month);
        $this->assertNotNull($loan->requested_at);

        // Nothing is owed and nothing is in the ledger until it is approved.
        $this->assertEquals(0, $loan->remaining_balance);
        $this->assertSame(0, LoanLedger::where('loan_id', $loan->id)->count());
    }

    public function test_the_deduction_month_is_optional(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee)
            ->post(route('loan.store.request'), [
                'amount' => 50000,
                'installments' => 5,
                'purpose' => 'Medical',
            ])
            ->assertRedirect(route('loan.apply'));

        $this->assertNull(Loan::where('user_id', $employee->id)->firstOrFail()->deduction_start_month);
    }

    public function test_purpose_is_required(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee)
            ->post(route('loan.store.request'), [
                'amount' => 50000,
                'installments' => 5,
            ])
            ->assertSessionHasErrors('purpose');

        $this->assertSame(0, Loan::count());
    }

    public function test_only_one_application_may_be_waiting_at_a_time(): void
    {
        $employee = $this->employee();
        $this->application($employee);

        $this->actingAs($employee)
            ->post(route('loan.store.request'), [
                'amount' => 20000,
                'installments' => 2,
                'purpose' => 'Something else',
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, Loan::where('user_id', $employee->id)->count());
    }

    public function test_approving_creates_the_loan_and_its_ledger_entry(): void
    {
        $employee = $this->employee();
        $loan = $this->application($employee);

        $this->actingAs($this->admin())
            ->post(route('admin.loan.approve', $loan->id), [
                'amount' => 60000,
                'installments' => 6,
                'decision_remarks' => 'Approved for less',
            ])
            ->assertSessionHas('success');

        $loan->refresh();

        $this->assertSame('approved', $loan->status);
        $this->assertEquals(60000, $loan->amount);
        $this->assertSame(6, (int) $loan->installments);
        $this->assertEquals(10000, $loan->monthly_deduction);
        $this->assertEquals(60000, $loan->remaining_balance);
        $this->assertSame('Approved for less', $loan->decision_remarks);
        $this->assertSame('dashboard', $loan->decided_via);
        $this->assertNotNull($loan->decided_at);

        $ledger = LoanLedger::where('loan_id', $loan->id)->get();

        $this->assertCount(1, $ledger);
        $this->assertSame('loan', $ledger->first()->type);
        $this->assertEquals(60000, $ledger->first()->amount);
    }

    public function test_a_decided_application_cannot_be_decided_again(): void
    {
        $employee = $this->employee();
        $loan = $this->application($employee);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.loan.approve', $loan->id));

        $this->actingAs($admin)
            ->post(route('admin.loan.reject', $loan->id))
            ->assertSessionHas('error');

        $this->assertSame('approved', $loan->fresh()->status);
    }

    public function test_rejecting_leaves_nothing_owed(): void
    {
        $employee = $this->employee();
        $loan = $this->application($employee);

        $this->actingAs($this->admin())
            ->post(route('admin.loan.reject', $loan->id), [
                'decision_remarks' => 'Not a permitted purpose',
            ])
            ->assertSessionHas('success');

        $loan->refresh();

        $this->assertSame('rejected', $loan->status);
        $this->assertEquals(0, $loan->remaining_balance);
        $this->assertSame('Not a permitted purpose', $loan->decision_remarks);
        $this->assertSame(0, LoanLedger::where('loan_id', $loan->id)->count());
    }

    public function test_an_application_waiting_for_a_decision_is_not_counted_as_a_balance(): void
    {
        $employee = $this->employee();

        $this->application($employee, ['amount' => 90000]);

        Loan::create([
            'user_id' => $employee->id,
            'amount' => 40000,
            'installments' => 4,
            'monthly_deduction' => 10000,
            'remaining_balance' => 40000,
            'status' => 'approved',
            'loan_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($employee)->get(route('loan.my'));

        $response->assertOk();

        // The 90,000 still being looked at is not part of what they owe.
        $this->assertEquals(40000, $response->viewData('loan')->remaining_balance);
        $this->assertEquals(40000, $response->viewData('loan')->amount);
        $this->assertNotNull($response->viewData('pendingApplication'));
    }

    public function test_an_employee_may_cancel_their_own_application_while_it_waits(): void
    {
        $employee = $this->employee();
        $loan = $this->application($employee);

        $this->actingAs($employee)
            ->delete(route('loan.application.cancel', $loan->id))
            ->assertSessionHas('success');

        $loan->refresh();

        // Kept, not deleted: the approvers were sent links to it.
        $this->assertSame('cancelled', $loan->status);
        $this->assertEquals(0, $loan->remaining_balance);
        $this->assertSame('employee', $loan->decided_via);
        $this->assertNotNull($loan->decided_at);
        $this->assertSame(0, LoanLedger::where('loan_id', $loan->id)->count());

        // And they are free to apply again.
        $this->actingAs($employee)
            ->post(route('loan.store.request'), [
                'amount' => 30000,
                'installments' => 3,
                'purpose' => 'A smaller amount instead',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('loan.apply'));

        $this->assertSame(1, Loan::where('user_id', $employee->id)->where('status', 'pending')->count());
    }

    public function test_an_approved_loan_cannot_be_cancelled_by_the_employee(): void
    {
        $employee = $this->employee();
        $loan = $this->application($employee);

        $this->actingAs($this->admin())->post(route('admin.loan.approve', $loan->id));

        $this->actingAs($employee)
            ->delete(route('loan.application.cancel', $loan->id))
            ->assertSessionHas('error');

        $this->assertSame('approved', $loan->fresh()->status);

        // The loan and its ledger entry are left exactly as they were.
        $this->assertEquals(120000, $loan->fresh()->remaining_balance);
        $this->assertSame(1, LoanLedger::where('loan_id', $loan->id)->count());
    }

    public function test_a_rejected_application_cannot_be_cancelled_away(): void
    {
        $employee = $this->employee();
        $loan = $this->application($employee);

        $this->actingAs($this->admin())->post(route('admin.loan.reject', $loan->id));

        $this->actingAs($employee)
            ->delete(route('loan.application.cancel', $loan->id))
            ->assertSessionHas('error');

        $this->assertSame('rejected', $loan->fresh()->status);
    }

    public function test_an_employee_cannot_cancel_somebody_elses_application(): void
    {
        $loan = $this->application($this->employee());

        $this->actingAs($this->employee(['email' => 'other@example.com']))
            ->delete(route('loan.application.cancel', $loan->id))
            ->assertForbidden();

        $this->assertSame('pending', $loan->fresh()->status);
    }

    public function test_a_cancelled_application_is_not_counted_as_a_balance(): void
    {
        $employee = $this->employee();
        $loan = $this->application($employee, ['amount' => 90000]);

        $this->actingAs($employee)->delete(route('loan.application.cancel', $loan->id));

        $response = $this->actingAs($employee)->get(route('loan.my'));

        $response->assertOk();
        $this->assertNull($response->viewData('loan'));
        $this->assertNull($response->viewData('pendingApplication'));
    }

    public function test_an_employee_may_print_only_their_own_application(): void
    {
        $employee = $this->employee();
        $loan = $this->application($employee);

        $this->actingAs($employee)
            ->get(route('loan.application.print', $loan->id))
            ->assertOk()
            ->assertSee('LOAN APPLICATION FORM')
            ->assertSee('School fees');

        $this->actingAs($this->employee(['email' => 'other@example.com']))
            ->get(route('loan.application.print', $loan->id))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('loan.application.print', $loan->id))
            ->assertOk();
    }

    public function test_the_applications_and_approvers_screens_are_for_loan_managers_only(): void
    {
        $employee = $this->employee();

        foreach ([route('admin.loan.applications'), route('admin.loan.approvers.index')] as $url) {
            $this->actingAs($employee)->get($url)->assertForbidden();
            $this->actingAs($this->admin())->get($url)->assertOk();
        }
    }

    public function test_a_number_whatsapp_could_not_use_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.loan.approvers.store'), ['name' => 'Bad', 'mobile' => '123'])
            ->assertSessionHasErrors('mobile');

        $this->assertSame(0, LoanApprover::count());

        $this->actingAs($this->admin())
            ->post(route('admin.loan.approvers.store'), ['name' => 'Accounts', 'mobile' => '03001234567'])
            ->assertSessionHas('success');

        $this->assertSame(1, LoanApprover::count());
        $this->assertTrue(LoanApprover::first()->is_active);
    }
}
