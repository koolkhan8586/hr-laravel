<?php

namespace Tests\Unit;

use App\Models\MedicalInsurance;
use PHPUnit\Framework\TestCase;

class MedicalInsuranceSplitTest extends TestCase
{
    public function test_even_total_splits_in_half_and_monthly_is_employee_over_twelve(): void
    {
        $this->assertSame([
            'total_amount'     => 12000.0,
            'lsaf_portion'     => 6000.0,
            'employee_portion' => 6000.0,
            'monthly_portion'  => 500.0,
        ], MedicalInsurance::splitTotal(12000));
    }

    public function test_odd_paisa_still_adds_back_to_total(): void
    {
        $split = MedicalInsurance::splitTotal(1000.01);

        $this->assertSame(1000.01, $split['total_amount']);
        $this->assertEqualsWithDelta(
            1000.01,
            $split['lsaf_portion'] + $split['employee_portion'],
            0.001
        );
        $this->assertSame(500.01, $split['employee_portion']);
        $this->assertSame(500.0, $split['lsaf_portion']);
        $this->assertSame(
            MedicalInsurance::monthlyPortion(500.01),
            $split['monthly_portion']
        );
    }

    public function test_sheet_months_start_in_august_2026_then_full_years(): void
    {
        $this->assertSame([], MedicalInsurance::monthsForYear(2025));
        $this->assertSame([8, 9, 10, 11, 12], MedicalInsurance::monthsForYear(2026));
        $this->assertSame(range(1, 12), MedicalInsurance::monthsForYear(2027));
    }

    public function test_salary_insurance_starts_august_2026(): void
    {
        $this->assertFalse(MedicalInsurance::appliesToSalaryMonth(2025, 12));
        $this->assertFalse(MedicalInsurance::appliesToSalaryMonth(2026, 7));
        $this->assertTrue(MedicalInsurance::appliesToSalaryMonth(2026, 8));
        $this->assertTrue(MedicalInsurance::appliesToSalaryMonth(2027, 1));
    }
}
