<?php

namespace Tests\Unit;

use App\Http\Controllers\FieldQualityController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Test per il nuovo criterio LOI normalizzato (FASE 11).
 */
class FieldQualityLoiCriterionTest extends TestCase
{
    // =========================================================================
    // HELPER: invoca metodi privati del controller
    // =========================================================================

    private function ctrl(): FieldQualityController
    {
        return new FieldQualityController();
    }

    private function call(string $method, ...$args)
    {
        $m = new ReflectionMethod(FieldQualityController::class, $method);
        $m->setAccessible(true);
        return $m->invoke($this->ctrl(), ...$args);
    }

    /** Costruisce un array di interviste minimo per applyLoiCriterion. */
    private function makeInterviews(array $specs): array
    {
        $ivs = [];
        foreach ($specs as $spec) {
            $ivs[] = [
                'iid'               => $spec['iid'] ?? 'iv' . count($ivs),
                'uid'               => 'uid',
                'panel'             => 'Norstat',
                'loiSec'            => $spec['loi'] ?? 0,
                'questionsAnswered' => $spec['q']   ?? 0,
                'pathSignature'     => '',
                'score'             => null,
                'quality_criteria'  => [],
                'quality_risks'     => [],
                'quality_weights'   => [],
                'quality_coverage'  => [],
                'quality_risk_total'=> null,
            ];
        }
        return $ivs;
    }

    /** Invoca applyLoiCriterion su un array di interviste e restituisce il risultato. */
    private function runLoi(array $specs): array
    {
        $ivs = $this->makeInterviews($specs);
        $m   = new ReflectionMethod(FieldQualityController::class, 'applyLoiCriterion');
        $m->setAccessible(true);
        $m->invokeArgs($this->ctrl(), [&$ivs]);
        return $ivs;
    }

    // =========================================================================
    // TEST 1 — Conteggio questionId unici in readFileMetrics
    // =========================================================================

    public function testReadFileMetricsCountsUniqueQuestionIds(): void
    {
        // Creo un file .sre temporaneo con un questionId duplicato
        $tmp = tempnam(sys_get_temp_dir(), 'sre_test_');
        file_put_contents($tmp, implode("\n", [
            '2.0;PRJ;SID;1;UID;01/01/2026 10:00:00 CEST;01/01/2026 10:10:00 CEST;600;3;500',
            'choice;10;3;1',
            'open;20;risposta',
            'scale;10;3;4;1;2;3', // questionId 10 già visto → non deve contare di nuovo
            'choice;30;2;0',
        ]));

        $result = $this->call('readFileMetrics', $tmp);
        unlink($tmp);

        // questionId unici: 10, 20, 30 → 3
        $this->assertSame(3, $result['questionCount']);
    }

    // =========================================================================
    // TEST 2 — Coorte 90%: referenceQuestionCount=82, ≥10 interviste con ≥74 dom.
    // =========================================================================

    public function testPrimaryCohorte90Selected(): void
    {
        // 15 interviste con q=75 (>= 82*0.90=73.8), LOI valide
        $specs = [];
        for ($i = 0; $i < 15; $i++) {
            $specs[] = ['q' => 75, 'loi' => 600];
        }
        // refQCount = P95 di [75×15] = 75
        $ivs = $this->runLoi($specs);

        $loi = $ivs[0]['quality_criteria']['loi'];
        $this->assertSame('normalized_cohort_90', $loi['reference_type']);
        $this->assertTrue($loi['available']);
    }

    // =========================================================================
    // TEST 3 — Fallback 80%: <10 casi al 90%, ≥10 casi all'80%
    // =========================================================================

    public function testFallbackCohorte80WhenPrimaryInsufficient(): void
    {
        // refQCount = P95 di [82×5 + 70×15] = 82 (P95 index 19 di 20 = valore 82)
        // Coorte 90%: q >= 82*0.90 = 73.8 → q=82, 5 interviste → < 10 → non sufficiente
        // Coorte 80%: q >= 82*0.80 = 65.6 → q=70 inclusi (15 + 5 = 20) → ≥ 10
        $specs = [];
        for ($i = 0; $i < 5; $i++)  { $specs[] = ['q' => 82, 'loi' => 700]; }
        for ($i = 0; $i < 15; $i++) { $specs[] = ['q' => 70, 'loi' => 600]; }

        $ivs = $this->runLoi($specs);
        $loi = $ivs[0]['quality_criteria']['loi'];

        $this->assertSame('normalized_cohort_80', $loi['reference_type']);
    }

    // =========================================================================
    // TEST 4 — Campione insufficiente: <10 casi anche all'80%
    // =========================================================================

    public function testInsufficientSampleWhenBothCohortesFail(): void
    {
        // 5 interviste con q=82: al 90% → 5 (<10); al 80% → 5 (<10)
        $specs = [];
        for ($i = 0; $i < 5; $i++) { $specs[] = ['q' => 82, 'loi' => 700]; }

        $ivs = $this->runLoi($specs);
        $loi = $ivs[0]['quality_criteria']['loi'];

        $this->assertFalse($loi['available']);
        $this->assertSame('insufficient_reference_sample', $loi['unavailable_reason']);
    }

    // =========================================================================
    // TEST 5 — Normalizzazione: 78 dom. / 660s su rifQCount=82
    // normalizedFullLoi = 660 × 82 / 78 ≈ 693.85
    // =========================================================================

    public function testNormalizedLoiCalculation(): void
    {
        // Coorte unica di un'intervista con q=78, loi=660, refQCount sarà P95 di [78×20] = 78
        // Ma per testare la formula usiamo calculateNormalizedReferenceLoi() direttamente
        $cohort = [
            ['iid' => 'a', 'q' => 78, 'loi' => 660.0],
        ];
        // Non sufficiente (1 < 10), ma possiamo testare la normalizzazione interno
        // Usiamo un cohort grande abbastanza per avere un riferimento valido
        $cohort15 = [];
        for ($i = 0; $i < 15; $i++) {
            $cohort15[] = ['iid' => "x{$i}", 'q' => 78, 'loi' => 660.0];
        }
        $result = $this->call('calculateNormalizedReferenceLoi', $cohort15, 82);

        // normalizedFullLoi = 660 × 82 / 78 ≈ 693.85
        $this->assertEqualsWithDelta(693.85, $result['reference_full_loi'], 0.5);
    }

    // =========================================================================
    // TEST 6 — Outlier lento: valore > 2× mediana preliminare viene escluso
    // =========================================================================

    public function testSlowOutlierExcluded(): void
    {
        // 14 interviste con normalizzato = 600, 1 con 1800 (> 2×600=1200)
        // refQCount = q = 80 per tutti → normalizedFullLoi = loi × 80 / 80 = loi
        $cohort = [];
        for ($i = 0; $i < 14; $i++) { $cohort[] = ['iid' => "n{$i}", 'q' => 80, 'loi' => 600.0]; }
        $cohort[] = ['iid' => 'slow', 'q' => 80, 'loi' => 1800.0]; // outlier

        $result = $this->call('calculateNormalizedReferenceLoi', $cohort, 80);

        $this->assertSame(1, $result['excluded']);
        // Mediana dei 14 buoni = 600
        $this->assertEqualsWithDelta(600.0, $result['reference_full_loi'], 1.0);
        $this->assertSame(14, $result['sample_size']);
    }

    // =========================================================================
    // TEST 7 — Formula proporzionale
    // refFullLoi=600, q=41, refQCount=82 → expected=300
    // =========================================================================

    public function testExpectedLoiFormula(): void
    {
        $expected = $this->call('calculateExpectedLoi', 600.0, 41, 82);
        $this->assertEqualsWithDelta(300.0, $expected, 0.001);
    }

    // =========================================================================
    // TEST 8 — Caso reale: actualLoi≈213s, q=58, refQCount≈82, refFull≈733s
    // expectedLoi ≈ 733*58/82 ≈ 518.9, ratio ≈ 213/518.9 ≈ 0.41 → Da verificare
    // =========================================================================

    public function testRealCaseVerify(): void
    {
        $expectedLoi = $this->call('calculateExpectedLoi', 733.0, 58, 82);
        $this->assertEqualsWithDelta(518.9, $expectedLoi, 1.0);

        $ratio = 213.0 / $expectedLoi;
        $this->assertEqualsWithDelta(0.41, $ratio, 0.02);

        $eval = $this->call('calculateLoiEvaluation', $ratio);
        $this->assertSame('verify', $eval['evaluation']);
        $this->assertSame('Da verificare', $eval['evaluation_label']);
    }

    // =========================================================================
    // TEST 9 — Confine 0.70: ratio esatto → OK
    // =========================================================================

    public function testRatioBoundary070IsOk(): void
    {
        $eval = $this->call('calculateLoiEvaluation', 0.70);
        $this->assertSame('ok', $eval['evaluation']);
    }

    // =========================================================================
    // TEST 10 — Confine 0.50: ratio esatto → Sospetta
    // =========================================================================

    public function testRatioBoundary050IsSuspicious(): void
    {
        $eval = $this->call('calculateLoiEvaluation', 0.50);
        $this->assertSame('suspicious', $eval['evaluation']);
    }

    // =========================================================================
    // TEST 11 — Ratio < 0.50 → Da verificare
    // =========================================================================

    public function testRatioBelowHalfIsVerify(): void
    {
        $eval = $this->call('calculateLoiEvaluation', 0.49);
        $this->assertSame('verify', $eval['evaluation']);
    }

    // =========================================================================
    // TEST 12 — LOI < 60s → risk 100, cap 35 ancora attivo
    // =========================================================================

    public function testLoiUnder60sRisk100(): void
    {
        // 20 interviste per avere una coorte valida + 1 con LOI = 30s
        $specs = [];
        for ($i = 0; $i < 20; $i++) { $specs[] = ['q' => 80, 'loi' => 700]; }
        $specs[] = ['iid' => 'under60', 'q' => 80, 'loi' => 30];

        $ivs = $this->runLoi($specs);

        $target = null;
        foreach ($ivs as $iv) {
            if ($iv['iid'] === 'under60') { $target = $iv; break; }
        }
        $this->assertNotNull($target);
        $loi = $target['quality_criteria']['loi'];
        $this->assertTrue($loi['available']);
        $this->assertTrue($loi['absolute_minimum_triggered']);
        $this->assertSame(100, $loi['risk']);
        $this->assertSame('verify', $loi['evaluation']);

        // Il cap LOI assoluto (35) deve essere attivo: testo tramite applyFinalQualityScore
        $target['quality_risks']['loi'] = 100;
        $interviews = [$target];
        $mFinal = new ReflectionMethod(FieldQualityController::class, 'applyFinalQualityScore');
        $mFinal->setAccessible(true);
        $mFinal->invokeArgs($this->ctrl(), [&$interviews]);
        $result = $interviews[0];

        // Score ordinario con solo LOI peso 30, risk 100 → totalRisk=100, score=0
        // Cap abs_min = 35; score = max(0, min(35,0)) = 0 → cap non riduce, applied=false
        $this->assertSame(0, $result['score']);
        $this->assertFalse($result['quality_score_cap']['applied']);
    }

    // =========================================================================
    // TEST 13 — LOI eccessivamente lenta (ratio > 1.67) → Non valutabile
    // =========================================================================

    public function testExcessivelySlowIsNotEvaluable(): void
    {
        // refFullLoi ≈ 700, refQCount ≈ 80 → expectedLoi per q=80 = 700
        // Un'intervista con loiSec = 2000 → ratio = 2000/700 ≈ 2.86 > 1.667
        $specs = [];
        for ($i = 0; $i < 20; $i++) { $specs[] = ['q' => 80, 'loi' => 700]; }
        $specs[] = ['iid' => 'tooslow', 'q' => 80, 'loi' => 2000];

        $ivs = $this->runLoi($specs);

        $target = null;
        foreach ($ivs as $iv) {
            if ($iv['iid'] === 'tooslow') { $target = $iv; break; }
        }
        $this->assertNotNull($target);
        $loi = $target['quality_criteria']['loi'];
        $this->assertFalse($loi['available']);
        $this->assertSame('not_evaluable', $loi['evaluation']);
        $this->assertSame('excessively_slow', $loi['unavailable_reason']);
    }

    // =========================================================================
    // TEST 14 — quality_risk_total non viene modificato direttamente da applyLoiCriterion
    // =========================================================================

    public function testQualityRiskTotalNotModifiedByLoi(): void
    {
        $specs = [];
        for ($i = 0; $i < 20; $i++) { $specs[] = ['q' => 80, 'loi' => 700]; }

        $ivs = $this->runLoi($specs);

        // Dopo applyLoiCriterion, quality_risk_total deve essere ancora null
        // (viene popolato solo da applyFinalQualityScore)
        foreach ($ivs as $iv) {
            $this->assertNull($iv['quality_risk_total']);
        }
    }
}
