<?php

namespace Tests\Feature;

use App\Models\Salary;
use App\Models\User;
use App\Services\SalaryPostedWhatsAppNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class SalaryPostedWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_posting_salary_sends_whatsapp_notification(): void
    {
        Mail::fake();

        $employee = User::factory()->create([
            'role' => 'employee',
            'mobile' => '03001234567',
            'employee_code' => 'EMP001',
            'salary_category' => 'staff',
        ]);

        $salary = Salary::create([
            'user_id' => $employee->id,
            'month' => 8,
            'year' => 2026,
            'basic_salary' => 50000,
            'status' => 'draft',
        ]);

        $notifier = Mockery::mock(SalaryPostedWhatsAppNotifier::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->with(Mockery::on(fn (Salary $s) => $s->id === $salary->id))
            ->andReturn(['ok' => true, 'skipped' => false, 'message' => 'WhatsApp sent.']);
        $this->app->instance(SalaryPostedWhatsAppNotifier::class, $notifier);

        $this->actingAs($this->admin())
            ->post(route('admin.salary.post', $salary->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($salary->fresh()->isPosted());
    }
}
