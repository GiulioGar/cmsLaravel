<?php

namespace Tests\Unit;

use App\Http\Controllers\FieldQualityController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FieldQualityLoiCapTest extends TestCase
{
    private function makeInterview(int $loiRisk, bool $absMinTriggered, bool $loiAvailable = true): array
    {
        return [
            'iid'                => 'test',
            'score'              => null,
            'quality_risk_total' => null,
            'quality_weights'    => [],
            'quality_coverage'   => [],
            'quality_risks'      => $loiAvailable ? ['loi' => $loiRisk] : [],
            'quality_criteria'   => [
                'open'  => ['available' => false],
                'scale' => ['available' => false],
                'loi'   => $loiAvailable ? [
                    'available'                  => true,
                    'absolute_minimum_triggered' => $absMinTriggered,
                    'risk'                       => $loiRisk,
                ] : ['available' => false],
            ],
        ];
    }

    private function runScoring(array $interview): array
    {
        $interviews = [$interview];
        $method = new ReflectionMethod(FieldQualityController::class, 'applyFinalQualityScore');
        $method->setAccessible(true);
        $method->invokeArgs(new FieldQualityController(), [&$interviews]);
        return $interviews[0];
    }

    // Caso 1: score ordinario 70, LOI < 60s → score finale 35, cap applied
    // Solo LOI (peso 30, risk 30): totalRisk=30, score=70 → capped a 35
    public function testCapAppliedScoreAboveCap(): void
    {
        $result = $this->runScoring($this->makeInterview(30, true));

        $this->assertSame(35, $result['score']);
        $this->assertSame(30, $result['quality_risk_total']); // invariato
        $this->assertTrue($result['quality_score_cap']['applied']);
        $this->assertSame(35, $result['quality_score_cap']['maximum_score']);
        $this->assertSame('loi_below_absolute_minimum', $result['quality_score_cap']['reason']);
    }

    // Caso 2: score ordinario 20, LOI < 60s → score finale 20, cap NON riduce (score già sotto)
    // Solo LOI (peso 30, risk 80): totalRisk=80, score=20 → già ≤ 35, applied=false
    public function testCapAppliedScoreBelowCap(): void
    {
        $result = $this->runScoring($this->makeInterview(80, true));

        $this->assertSame(20, $result['score']);
        $this->assertSame(80, $result['quality_risk_total']); // invariato
        $this->assertFalse($result['quality_score_cap']['applied']);
    }

    // Caso 3: score ordinario 70, LOI ≥ 60s → score finale 70, no cap
    public function testNoCap(): void
    {
        $result = $this->runScoring($this->makeInterview(30, false));

        $this->assertSame(70, $result['score']);
        $this->assertSame(30, $result['quality_risk_total']);
        $this->assertFalse($result['quality_score_cap']['applied']);
        $this->assertNull($result['quality_score_cap']['maximum_score']);
        $this->assertNull($result['quality_score_cap']['reason']);
    }

    // Caso 4: LOI non disponibile → no cap, score null
    public function testLoiUnavailable(): void
    {
        $result = $this->runScoring($this->makeInterview(0, false, false));

        $this->assertNull($result['score']);
        $this->assertNull($result['quality_risk_total']);
        $this->assertFalse($result['quality_score_cap']['applied']);
        $this->assertNull($result['quality_score_cap']['maximum_score']);
        $this->assertNull($result['quality_score_cap']['reason']);
    }

    // Extra: score esattamente 35 con cap 35 attivo → rimane 35, applied=false (nessuna riduzione)
    public function testCapAppliedScoreExactlyCap(): void
    {
        // risk=65, score=35 (solo LOI peso 30): totalRisk=65, score=35 = cap → nessuna riduzione
        $result = $this->runScoring($this->makeInterview(65, true));

        $this->assertSame(35, $result['score']);
        $this->assertFalse($result['quality_score_cap']['applied']);
        $this->assertSame(65, $result['quality_risk_total']); // invariato
    }
}
