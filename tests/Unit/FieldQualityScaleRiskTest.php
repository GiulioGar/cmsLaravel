<?php

namespace Tests\Unit;

use App\Http\Controllers\FieldQualityController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FieldQualityScaleRiskTest extends TestCase
{
    private function risk(array $qualityScores): int
    {
        $gridDetails = array_map(function (int $qs) {
            return ['quality_score' => $qs];
        }, $qualityScores);

        $method = new ReflectionMethod(FieldQualityController::class, 'calculateAggregateScaleRisk');
        $method->setAccessible(true);
        return $method->invoke(new FieldQualityController(), $gridDetails);
    }

    // --- Casi minimi richiesti dalla spec ---

    // 1. quality 100 → scaleRisk 0
    public function testSinglePerfect(): void
    {
        $this->assertSame(0, $this->risk([100]));
    }

    // 2. quality 0 → scaleRisk 100
    public function testSingleWorst(): void
    {
        $this->assertSame(100, $this->risk([0]));
    }

    // 3. quality 100, 100, 20
    //    gridRisk: 0, 0, 80 → avg 26.67, worst 80
    //    scaleRisk = 26.67×0.70 + 80×0.30 = 18.67+24 = 42.67 → 43
    public function testThreeGridsOneAnomaly(): void
    {
        $this->assertSame(43, $this->risk([100, 100, 20]));
    }

    // 4. quality 100, 20, 20
    //    gridRisk: 0, 80, 80 → avg 53.33, worst 80
    //    scaleRisk = 53.33×0.70 + 80×0.30 = 37.33+24 = 61.33 → 61
    public function testThreeGridsTwoAnomalies(): void
    {
        $this->assertSame(61, $this->risk([100, 20, 20]));
    }

    // 5. quality 50, 50, 50
    //    gridRisk: 50, 50, 50 → avg 50, worst 50
    //    scaleRisk = 50×0.70 + 50×0.30 = 50
    public function testAllMedium(): void
    {
        $this->assertSame(50, $this->risk([50, 50, 50]));
    }

    // --- Casi aggiuntivi ---

    // Singola griglia: averageRisk == worstRisk → formula coincide con gridRisk
    public function testSingleGridMid(): void
    {
        $this->assertSame(50, $this->risk([50]));
    }

    // Una griglia anomala (quality=0) tra quattro perfette
    // gridRisk: 100,0,0,0,0 → avg 20, worst 100
    // scaleRisk = 20×0.70 + 100×0.30 = 14+30 = 44
    public function testOneAnomalyAmongMany(): void
    {
        $this->assertSame(44, $this->risk([0, 100, 100, 100, 100]));
    }

    // Tutte anomale: avg 100, worst 100 → scaleRisk 100
    public function testAllWorst(): void
    {
        $this->assertSame(100, $this->risk([0, 0, 0]));
    }

    // Tutte perfette: avg 0, worst 0 → scaleRisk 0
    public function testAllPerfect(): void
    {
        $this->assertSame(0, $this->risk([100, 100, 100, 100]));
    }

    // Corrispondenza con i livelli attuali:
    // Normale=100, Sospetta=50, Da Verificare=0
    public function testLevelNormale(): void
    {
        $this->assertSame(0, $this->risk([100]));
    }

    public function testLevelSospetta(): void
    {
        $this->assertSame(50, $this->risk([50]));
    }

    public function testLevelDaVerificare(): void
    {
        $this->assertSame(100, $this->risk([0]));
    }

    // Nessuna griglia con quality_score valido → 0
    public function testNoValidGrids(): void
    {
        $method = new ReflectionMethod(FieldQualityController::class, 'calculateAggregateScaleRisk');
        $method->setAccessible(true);
        $result = $method->invoke(new FieldQualityController(), [
            ['level' => 'Normale'],           // no quality_score
            ['quality_score' => 'non-int'],   // invalid type
        ]);
        $this->assertSame(0, $result);
    }

    // quality_score fuori range: normalizzati a 0-100
    public function testOutOfRangeScoreAbove(): void
    {
        // quality_score=150 → clamped to 100 → gridRisk=0
        $method = new ReflectionMethod(FieldQualityController::class, 'calculateAggregateScaleRisk');
        $method->setAccessible(true);
        $result = $method->invoke(new FieldQualityController(), [['quality_score' => 150]]);
        $this->assertSame(0, $result);
    }

    public function testOutOfRangeScoreBelow(): void
    {
        // quality_score=-10 → clamped to 0 → gridRisk=100
        $method = new ReflectionMethod(FieldQualityController::class, 'calculateAggregateScaleRisk');
        $method->setAccessible(true);
        $result = $method->invoke(new FieldQualityController(), [['quality_score' => -10]]);
        $this->assertSame(100, $result);
    }
}
