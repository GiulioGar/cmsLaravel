<?php

namespace Tests\Unit;

use App\Http\Controllers\FieldQualityController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FieldQualityOpenRiskTest extends TestCase
{
    private function risk(float $pct): int
    {
        $method = new ReflectionMethod(FieldQualityController::class, 'calculateOpenRisk');
        $method->setAccessible(true);
        return $method->invoke(new FieldQualityController(), $pct);
    }

    // --- Boundary values ---

    public function testBoundary0(): void
    {
        $this->assertSame(0, $this->risk(0.0));
    }

    public function testBoundary0_01(): void
    {
        $this->assertSame(20, $this->risk(0.01));
    }

    public function testBoundary20(): void
    {
        $this->assertSame(20, $this->risk(20.0));
    }

    public function testBoundary20_01(): void
    {
        $this->assertSame(45, $this->risk(20.01));
    }

    public function testBoundary40(): void
    {
        $this->assertSame(45, $this->risk(40.0));
    }

    public function testBoundary40_01(): void
    {
        $this->assertSame(70, $this->risk(40.01));
    }

    public function testBoundary60(): void
    {
        $this->assertSame(70, $this->risk(60.0));
    }

    public function testBoundary60_01(): void
    {
        $this->assertSame(90, $this->risk(60.01));
    }

    public function testBoundary80(): void
    {
        $this->assertSame(90, $this->risk(80.0));
    }

    public function testBoundary80_01(): void
    {
        $this->assertSame(100, $this->risk(80.01));
    }

    public function testBoundary100(): void
    {
        $this->assertSame(100, $this->risk(100.0));
    }

    // --- Esempi reali (frazioni) ---

    // 0 fake su 5 → 0%
    public function testZeroOutOfFive(): void
    {
        $this->assertSame(0, $this->risk(0.0));
    }

    // 1 fake su 5 → 20%
    public function testOneOutOfFive(): void
    {
        $this->assertSame(20, $this->risk(round(1 / 5 * 100, 2)));
    }

    // 1 fake su 4 → 25%
    public function testOneOutOfFour(): void
    {
        $this->assertSame(45, $this->risk(round(1 / 4 * 100, 2)));
    }

    // 1 fake su 3 → 33.33%
    public function testOneOutOfThree(): void
    {
        $this->assertSame(45, $this->risk(round(1 / 3 * 100, 2)));
    }

    // 1 fake su 2 → 50%
    public function testOneOutOfTwo(): void
    {
        $this->assertSame(70, $this->risk(round(1 / 2 * 100, 2)));
    }

    // 2 fake su 3 → 66.67%
    public function testTwoOutOfThree(): void
    {
        $this->assertSame(90, $this->risk(round(2 / 3 * 100, 2)));
    }

    // 4 fake su 5 → 80%
    public function testFourOutOfFive(): void
    {
        $this->assertSame(90, $this->risk(round(4 / 5 * 100, 2)));
    }

    // 5 fake su 6 → 83.33%
    public function testFiveOutOfSix(): void
    {
        $this->assertSame(100, $this->risk(round(5 / 6 * 100, 2)));
    }

    // 3 fake su 3 → 100%
    public function testThreeOutOfThree(): void
    {
        $this->assertSame(100, $this->risk(round(3 / 3 * 100, 2)));
    }

    // --- Valori fuori range (sicurezza) ---

    public function testNegativeNormalizedToZero(): void
    {
        $this->assertSame(0, $this->risk(-10.0));
    }

    public function testAboveHundredNormalizedToHundred(): void
    {
        $this->assertSame(100, $this->risk(110.0));
    }
}
