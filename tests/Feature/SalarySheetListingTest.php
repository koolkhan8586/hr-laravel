<?php

namespace Tests\Feature;

use App\Models\Salary;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Who appears on the salary sheet, and in what order.
 */
class SalarySheetListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function employee(string $name, string $code, string $status = 'active'): User
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'name' => $name,
            'employee_code' => $code,
            'salary_category' => 'staff',
        ]);

        Staff::create([
            'user_id' => $user->id,
            'employee_id' => $code,
            'department' => 'Admin',
            'designation' => 'Officer',
            'salary' => 50000,
            'joining_date' => '2024-01-01',
            'status' => $status,
        ]);

        return $user;
    }

    private function sheet(array $query = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.salary.sheet', $query + [
                'month' => 11,
                'year' => 2026,
                'category' => 'staff',
            ]));
    }

    public function test_somebody_who_has_left_is_off_the_sheet(): void
    {
        $here = $this->employee('Still Here', 'EMP001');
        $gone = $this->employee('Has Left', 'EMP002', 'inactive');

        $response = $this->sheet()->assertOk();

        $shown = $response->viewData('users')->pluck('id');

        $this->assertTrue($shown->contains($here->id));
        $this->assertFalse($shown->contains($gone->id), 'A leaver should not be on the sheet.');

        // And their absence is explained rather than silently done.
        $response->assertSee('Left / resigned');
        $response->assertSee('show employees who have left');
    }

    public function test_a_leaver_with_a_row_this_month_keeps_it(): void
    {
        $gone = $this->employee('Left Mid Month', 'EMP003', 'inactive');

        // Their final salary is already being entered.
        Salary::create([
            'user_id' => $gone->id,
            'month' => 11,
            'year' => 2026,
            'basic_salary' => 50000,
            'status' => 'draft',
        ]);

        $shown = $this->sheet()->assertOk()->viewData('users')->pluck('id');

        $this->assertTrue(
            $shown->contains($gone->id),
            'A row already entered for this month must not disappear.'
        );
    }

    public function test_leavers_can_be_put_back_on_purpose(): void
    {
        $gone = $this->employee('Has Left', 'EMP004', 'inactive');

        $response = $this->sheet(['include_left' => 1])->assertOk();

        $this->assertTrue($response->viewData('users')->pluck('id')->contains($gone->id));
        $response->assertSee('Employees who have left are being shown');
    }

    public function test_an_employee_with_no_staff_record_is_left_alone(): void
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'name' => 'No Staff Row',
            'salary_category' => 'staff',
        ]);

        $shown = $this->sheet()->assertOk()->viewData('users')->pluck('id');

        $this->assertTrue($shown->contains($user->id));
    }

    public function test_the_sheet_is_alphabetical_by_default(): void
    {
        $this->employee('Zubair Ahmad', 'EMP010');
        $this->employee('Abdul Ahid', 'EMP011');
        $this->employee('Manzoor Ahmed', 'EMP012');

        $response = $this->sheet()->assertOk();

        $this->assertSame('name', $response->viewData('sort'));

        $this->assertSame(
            ['Abdul Ahid', 'Manzoor Ahmed', 'Zubair Ahmad'],
            $response->viewData('users')->pluck('name')->all()
        );
    }

    public function test_sorting_by_code_still_works_when_asked(): void
    {
        $this->employee('Zubair Ahmad', 'EMP010');
        $this->employee('Abdul Ahid', 'EMP011');

        $response = $this->sheet(['sort' => 'code'])->assertOk();

        $this->assertSame(
            ['Zubair Ahmad', 'Abdul Ahid'],
            $response->viewData('users')->pluck('name')->all()
        );
    }

    public function test_the_staff_list_is_alphabetical_by_default(): void
    {
        $this->employee('Zubair Ahmad', 'EMP020');
        $this->employee('Abdul Ahid', 'EMP021');

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.staff.index'))
            ->assertOk();

        $this->assertSame('name', $response->viewData('sort'));

        $this->assertSame(
            ['Abdul Ahid', 'Zubair Ahmad'],
            $response->viewData('staff')->map(fn ($s) => $s->user->name)->all()
        );
    }
}
