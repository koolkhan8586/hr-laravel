<?php

namespace Tests\Unit;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEmploymentScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_employed_scope_excludes_left_staff(): void
    {
        $active = $this->employeeWithStaff('active');
        $left   = $this->employeeWithStaff('inactive');

        $ids = User::where('role', 'employee')->employed()->pluck('id');

        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($left->id));
    }

    public function test_for_attendance_roster_excludes_left_and_payroll_only(): void
    {
        $active = $this->employeeWithStaff('active');
        $left   = $this->employeeWithStaff('inactive', ['email' => 'left@example.com']);
        $payrollOnly = $this->employeeWithStaff('active', [
            'email' => 'payroll@example.com',
            'tracks_attendance' => false,
        ]);

        $ids = User::where('role', 'employee')->forAttendanceRoster()->pluck('id');

        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($left->id));
        $this->assertFalse($ids->contains($payrollOnly->id));
    }

    private function employeeWithStaff(string $status, array $attrs = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => 'employee',
            'employee_code' => 'EMP'.uniqid(),
            'tracks_attendance' => true,
        ], $attrs));

        Staff::create([
            'user_id' => $user->id,
            'employee_id' => $user->employee_code,
            'department' => 'IT',
            'designation' => 'Staff',
            'salary' => 10000,
            'joining_date' => now()->toDateString(),
            'status' => $status,
        ]);

        return $user;
    }
}
