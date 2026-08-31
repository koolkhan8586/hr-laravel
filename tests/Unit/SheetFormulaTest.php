<?php

namespace Tests\Unit;

use App\Support\SheetFormula;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SheetFormulaTest extends TestCase
{
    public function test_plus_prefixed_sum(): void
    {
        $this->assertSame(3600.0, SheetFormula::evaluate('+2600+1000'));
        $this->assertSame(102648.0, SheetFormula::evaluate('+9699+92949'));
    }

    public function test_equals_prefixed_sum(): void
    {
        $this->assertSame(3600.0, SheetFormula::evaluate('=2600+1000'));
    }

    public function test_plain_number(): void
    {
        $this->assertSame(12000.0, SheetFormula::evaluate('12,000'));
    }

    public function test_brackets_and_multiplication(): void
    {
        $this->assertSame(3000.0, SheetFormula::evaluate('=(5000+1000)/2'));
    }

    public function test_looks_like_expression(): void
    {
        $this->assertTrue(SheetFormula::looksLikeExpression('+2600+1000'));
        $this->assertTrue(SheetFormula::looksLikeExpression('=1200+300'));
        $this->assertFalse(SheetFormula::looksLikeExpression('12000'));
    }

    public function test_invalid_expression_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SheetFormula::evaluate('=abc+1');
    }
}
