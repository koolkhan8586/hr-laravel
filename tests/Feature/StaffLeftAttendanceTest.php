<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffLeftAttendanceTest extends TestCase
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

    private function staffMember(string $status = 'active'): Staff
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'employee_code' => 'EMP'.random_int(100, 999),
            'tracks_attendance' => true,
        ]);

        return Staff::create([
            'user_id' => $user->id,
            'employee_id' => $user->employee_code,
            'department' => 'HR',
            'designation' => 'Officer',
            'salary' => 30000,
            'joining_date' => now()->toDateString(),
            'status' => $status,
        ]);
    }

    public function test_admin_can_mark_employee_as_left(): void
    {
        $staff = $this->staffMember('active');

        $this->actingAs($this->admin())
            ->post(route('admin.staff.toggle', $staff->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('inactive', $staff->fresh()->status);
    }

    public function test_left_employee_is_excluded_from_daily_absent_list(): void
    {
        \Carbon\Carbon::setTestNow('2026-09-01'); // Tuesday

        $active = $this->staffMember('active');
        $left   = $this->staffMember('inactive');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.attendance.list', [
                'type' => 'absent',
                'date' => '2026-09-01',
            ]))
            ->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString($active->user->name, $html);
        $this->assertStringNotContainsString($left->user->name, $html);

        \Carbon\Carbon::setTestNow();
    }

    public function test_admin_can_update_employment_status_on_edit_form(): void
    {
        $staff = $this->staffMember('active');

        $this->actingAs($this->admin())
            ->put(route('admin.staff.update', $staff->id), [
                'name' => $staff->user->name,
                'email' => $staff->user->email,
                'employee_code' => $staff->employee_id,
                'department' => $staff->department,
                'designation' => $staff->designation,
                'salary' => $staff->salary,
                'joining_date' => $staff->joining_date,
                'role' => 'employee',
                'employment_status' => 'inactive',
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('success');

        $this->assertSame('inactive', $staff->fresh()->status);
    }
}
