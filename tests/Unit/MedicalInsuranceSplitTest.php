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
}
