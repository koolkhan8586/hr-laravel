<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeaveAdminApproveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function pendingLeave(): Leave
    {
        $employee = User::factory()->create([
            'role'  => 'employee',
            'email' => 'staff@example.com',
        ]);

        return Leave::create([
            'user_id'          => $employee->id,
            'type'             => 'without_pay',
            'start_date'       => now()->toDateString(),
            'end_date'         => now()->toDateString(),
            'duration_type'    => 'full_day',
            'days'             => 1,
            'calculated_days'  => 1,
            'reason'           => 'Test',
            'status'           => 'pending',
        ]);
    }

    public function test_admin_can_approve_leave_with_post(): void
    {
        $admin = $this->admin();
        $leave = $this->pendingLeave();

        $this->actingAs($admin)
            ->post(route('admin.leave.approve', $leave->id))
            ->assertRedirect(route('admin.leave.index'))
            ->assertSessionHas('success');

        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertSame('dashboard', $leave->fresh()->decided_via);
    }

    public function test_admin_can_approve_leave_with_get_after_relogin_redirect(): void
    {
        // After session expiry Laravel resumes the intended URL with GET.
        // That used to 405 because approve only accepted POST.
        $admin = $this->admin();
        $leave = $this->pendingLeave();

        $this->actingAs($admin)
            ->get(route('admin.leave.approve', $leave->id))
            ->assertRedirect(route('admin.leave.index'))
            ->assertSessionHas('success');

        $this->assertSame('approved', $leave->fresh()->status);
    }

    public function test_admin_can_reject_leave_with_get(): void
    {
        $admin = $this->admin();
        $leave = $this->pendingLeave();

        $this->actingAs($admin)
            ->get(route('admin.leave.reject', $leave->id))
            ->assertRedirect(route('admin.leave.index'))
            ->assertSessionHas('success');

        $this->assertSame('rejected', $leave->fresh()->status);
    }

    public function test_guests_cannot_approve_via_get(): void
    {
        $leave = $this->pendingLeave();

        $this->get(route('admin.leave.approve', $leave->id))
            ->assertRedirect();

        $this->assertSame('pending', $leave->fresh()->status);
    }
}
