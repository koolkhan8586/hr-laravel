<?php

namespace Tests\Unit;

use App\Models\Salary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalaryRoundingTest extends TestCase
{
    use RefreshDatabase;

    public function test_salary_totals_round_to_whole_rupees_on_save(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $salary = Salary::create([
            'user_id' => $user->id,
            'month' => 8,
            'year' => 2026,
            'basic_salary' => 26000,
            'extra_leaves' => 19933.33,
            'status' => 'draft',
        ]);

        $this->assertSame(26000.0, (float) $salary->gross_total);
        $this->assertSame(19933.0, (float) $salary->total_deductions);
        $this->assertSame(6067.0, (float) $salary->net_salary);
    }
}
