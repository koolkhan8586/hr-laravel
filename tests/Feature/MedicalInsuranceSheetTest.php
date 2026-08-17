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
            'role'            => 'employee',
            'salary_category' => 'staff',
            'employee_code'   => 'EMP001',
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
            ->assertSee('Employee Portion')
            ->assertSee('Monthly');
    }

    public function test_employees_cannot_open_the_medical_insurance_sheet(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee)
            ->get(route('admin.salary.medical'))
            ->assertForbidden();
    }

    public function test_saving_the_sheet_stores_a_yearly_record_for_every_employee(): void
    {
        $admin     = $this->admin();
        $employee  = $this->employee();
        $other     = $this->employee([
            'employee_code' => 'EMP002',
            'email'         => 'other@example.com',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.salary.medical.store'), [
                'year'     => 2026,
                'category' => 'staff',
                'rows'     => [
                    [
                        'user_id'      => $employee->id,
                        'total_amount' => '12,000',
                    ],
                    [
                        'user_id'      => $other->id,
                        'total_amount' => '',
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('medical_insurances', [
            'user_id'          => $employee->id,
            'year'             => 2026,
            'total_amount'     => 12000,
            'lsaf_portion'     => 6000,
            'employee_portion' => 6000,
        ]);

        $this->assertDatabaseHas('medical_insurances', [
            'user_id'      => $other->id,
            'year'         => 2026,
            'total_amount' => 0,
        ]);

        $this->assertDatabaseCount('medical_insurances', 2);
    }

    public function test_monthly_employee_portion_is_hinted_on_the_salary_sheet(): void
    {
        $admin    = $this->admin();
        $employee = $this->employee();

        MedicalInsurance::create([
            'user_id'          => $employee->id,
            'year'             => 2026,
            'total_amount'     => 12000,
            'lsaf_portion'     => 6000,
            'employee_portion' => 6000,
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.salary.sheet', [
                'month'    => 8,
                'year'     => 2026,
                'category' => 'staff',
            ]))
            ->assertOk()
            ->assertSee('has-insurance', false)
            ->assertSee('data-insurance-portion="500"', false)
            ->getContent();

        $this->assertStringContainsString('placeholder="500"', $html);
        $this->assertStringContainsString('will not print until you enter it', $html);
    }

    public function test_posted_insurance_shows_in_that_month_on_the_yearly_sheet(): void
    {
        Mail::fake();

        $admin    = $this->admin();
        $employee = $this->employee(['email' => 'staff@example.com']);

        MedicalInsurance::create([
            'user_id'          => $employee->id,
            'year'             => 2026,
            'total_amount'     => 12000,
            'lsaf_portion'     => 6000,
            'employee_portion' => 6000,
        ]);

        $salary = Salary::create([
            'user_id'      => $employee->id,
            'month'        => 8,
            'year'         => 2026,
            'basic_salary' => 50000,
            'insurance'    => 500,
            'status'       => 'draft',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.salary.post', $salary->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->get(route('admin.salary.medical', [
                'year'     => 2026,
                'category' => 'staff',
            ]))
            ->assertOk()
            ->assertSee('500');
    }

    public function test_copy_previous_year_copies_premiums(): void
    {
        $admin    = $this->admin();
        $employee = $this->employee();

        MedicalInsurance::create([
            'user_id'          => $employee->id,
            'year'             => 2025,
            'total_amount'     => 12000,
            'lsaf_portion'     => 6000,
            'employee_portion' => 6000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.salary.medical.copy'), [
                'year'     => 2026,
                'category' => 'staff',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('medical_insurances', [
            'user_id'          => $employee->id,
            'year'             => 2026,
            'total_amount'     => 12000,
            'lsaf_portion'     => 6000,
            'employee_portion' => 6000,
        ]);
    }
}
