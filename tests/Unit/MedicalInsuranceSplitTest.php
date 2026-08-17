<?php

namespace Tests\Unit;

use App\Models\MedicalInsurance;
use PHPUnit\Framework\TestCase;

class MedicalInsuranceSplitTest extends TestCase
{
    public function test_even_total_splits_in_half(): void
    {
        $this->assertSame([
            'total_amount'     => 2000.0,
            'lsaf_portion'     => 1000.0,
            'employee_portion' => 1000.0,
        ], MedicalInsurance::splitTotal(2000));
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
    }
}
