<?php

namespace Tests\Feature;

use App\Models\MedicalInsurance;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MedicalInsuranceSheetTest extends TestCase
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

    private function employee(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role'             => 'employee',
            'salary_category'  => 'staff',
            'employee_code'    => 'EMP001',
        ], $attrs));
    }

    public function test_medical_insurance_tab_is_listed_under_salary_management(): void
    {
        $admin = $this->admin();
        $this->employee();

        $this->actingAs($admin)
            ->get(route('admin.salary.medical'))
            ->assertOk()
            ->assertSee('Medical Insurance')
            ->assertSee('Employee Code')
            ->assertSee('LSAF Portion')
            ->assertSee('Employee Portion');
    }

    public function test_employees_cannot_open_the_medical_insurance_sheet(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee)
            ->get(route('admin.salary.medical'))
            ->assertForbidden();
    }

    public function test_saving_the_sheet_splits_the_total_in_half(): void
    {
        $admin    = $this->admin();
        $employee = $this->employee();

        $this->actingAs($admin)
            ->post(route('admin.salary.medical.store'), [
                'month'    => 8,
                'year'     => 2026,
                'category' => 'staff',
                'rows'     => [
                    [
                        'user_id'      => $employee->id,
                        'total_amount' => '2,000',
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('medical_insurances', [
            'user_id'          => $employee->id,
            'month'            => 8,
            'year'             => 2026,
            'total_amount'     => 2000,
            'lsaf_portion'     => 1000,
            'employee_portion' => 1000,
        ]);
    }

    public function test_employee_portion_is_hinted_on_the_salary_sheet(): void
    {
        $admin    = $this->admin();
        $employee = $this->employee();

        MedicalInsurance::create([
            'user_id'          => $employee->id,
            'month'            => 8,
            'year'             => 2026,
            'total_amount'     => 2000,
            'lsaf_portion'     => 1000,
            'employee_portion' => 1000,
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.salary.sheet', [
                'month'    => 8,
                'year'     => 2026,
                'category' => 'staff',
            ]))
            ->assertOk()
            ->assertSee('has-insurance', false)
            ->assertSee('data-insurance-portion="1000"', false)
            ->getContent();

        $this->assertStringContainsString('placeholder="1,000"', $html);
        $this->assertStringContainsString('will not print until you enter it', $html);
    }

    public function test_posted_insurance_shows_as_deducted_on_the_medical_tab(): void
    {
        Mail::fake();

        $admin    = $this->admin();
        $employee = $this->employee(['email' => 'staff@example.com']);

        MedicalInsurance::create([
            'user_id'          => $employee->id,
            'month'            => 8,
            'year'             => 2026,
            'total_amount'     => 2000,
            'lsaf_portion'     => 1000,
            'employee_portion' => 1000,
        ]);

        $salary = Salary::create([
            'user_id'      => $employee->id,
            'month'        => 8,
            'year'         => 2026,
            'basic_salary' => 50000,
            'insurance'    => 1000,
            'status'       => 'draft',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.salary.post', $salary->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->get(route('admin.salary.medical', [
                'month'    => 8,
                'year'     => 2026,
                'category' => 'staff',
            ]))
            ->assertOk()
            ->assertSee('1,000');
    }

    public function test_copy_last_month_copies_premiums(): void
    {
        $admin    = $this->admin();
        $employee = $this->employee();

        MedicalInsurance::create([
            'user_id'          => $employee->id,
            'month'            => 7,
            'year'             => 2026,
            'total_amount'     => 2400,
            'lsaf_portion'     => 1200,
            'employee_portion' => 1200,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.salary.medical.copy'), [
                'month'    => 8,
                'year'     => 2026,
                'category' => 'staff',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('medical_insurances', [
            'user_id'          => $employee->id,
            'month'            => 8,
            'year'             => 2026,
            'total_amount'     => 2400,
            'lsaf_portion'     => 1200,
            'employee_portion' => 1200,
        ]);
    }
}
