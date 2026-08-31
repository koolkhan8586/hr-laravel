<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use App\Services\EmployeeCredentialsWhatsAppNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class StaffWhatsAppCredentialsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function staffRow(array $userAttrs = [], array $staffAttrs = []): Staff
    {
        $user = User::factory()->create(array_merge([
            'role' => 'employee',
            'mobile' => '03001234567',
            'employee_code' => 'EMP100',
        ], $userAttrs));

        return Staff::create(array_merge([
            'user_id' => $user->id,
            'employee_id' => $user->employee_code,
            'department' => 'IT',
            'designation' => 'Developer',
            'salary' => 50000,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ], $staffAttrs));
    }

    public function test_creating_staff_sends_whatsapp_credentials(): void
    {
        Mail::fake();

        $notifier = Mockery::mock(EmployeeCredentialsWhatsAppNotifier::class);
        $notifier->shouldReceive('sendWelcome')
            ->once()
            ->with(Mockery::type(User::class), Mockery::type('string'))
            ->andReturn(['ok' => true, 'message' => 'Credentials sent via WhatsApp.']);
        $this->app->instance(EmployeeCredentialsWhatsAppNotifier::class, $notifier);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.staff.store'), [
                'name' => 'New Hire',
                'email' => 'newhire@example.com',
                'mobile' => '03009998877',
                'employee_code' => 'EMP200',
                'department' => 'HR',
                'designation' => 'Officer',
                'salary' => 40000,
                'joining_date' => '2026-08-01',
                'role' => 'employee',
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'newhire@example.com',
            'mobile' => '03009998877',
            'employee_code' => 'EMP200',
        ]);
    }

    public function test_admin_can_reset_password_and_send_whatsapp(): void
    {
        $admin = $this->admin();
        $staff = $this->staffRow();
        $oldHash = $staff->user->password;

        $notifier = Mockery::mock(EmployeeCredentialsWhatsAppNotifier::class);
        $notifier->shouldReceive('sendPasswordReset')
            ->once()
            ->with(Mockery::on(fn (User $u) => $u->id === $staff->user_id), Mockery::type('string'))
            ->andReturn(['ok' => true, 'message' => 'Credentials sent via WhatsApp.']);
        $this->app->instance(EmployeeCredentialsWhatsAppNotifier::class, $notifier);

        $this->actingAs($admin)
            ->post(route('admin.staff.reset.password', $staff->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotSame($oldHash, $staff->user->fresh()->password);
    }

    public function test_reset_password_requires_mobile(): void
    {
        $admin = $this->admin();
        $staff = $this->staffRow(['mobile' => null]);

        $this->actingAs($admin)
            ->post(route('admin.staff.reset.password', $staff->id))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_staff_index_shows_reset_whatsapp_button_when_mobile_set(): void
    {
        $admin = $this->admin();
        $this->staffRow(['name' => 'WhatsApp User']);

        $this->actingAs($admin)
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('Reset &amp; WhatsApp', false)
            ->assertSee('WhatsApp User');
    }
}
