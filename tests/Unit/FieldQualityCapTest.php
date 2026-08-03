<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\FieldQualityController;

class FieldQualityCapTest extends TestCase
{
    private function callCaps(array $interview, int $baseScore): array
    {
        $controller = new FieldQualityController();
        $method     = new \ReflectionMethod(FieldQualityController::class, 'calculateQualityScoreCaps');
        $method->setAccessible(true);
        return $method->invoke($controller, $interview, $baseScore);
    }

    private function makeInterview(
        array $open  = null,
        array $scale = null,
        array $loi   = null
    ): array {
        $qualityCriteria = [];

        $qualityCriteria['open'] = $open !== null
            ? array_merge([
                'available'         => true,
                'analyzed_answers'  => 9,
                'fake_answers'      => 0,
                'fake_percentage'   => 0.0,
                'risk'              => 0,
                'confidence_factor' => 1.0,
                'effective_weight'  => 60,
            ], $open)
            : ['available' => false];

        $qualityCriteria['scale'] = $scale !== null
            ? array_merge([
                'available'       => true,
                'analyzed_scales' => 0,
                'details'         => [],
            ], $scale)
            : ['available' => false];

        $qualityCriteria['loi'] = $loi !== null
            ? array_merge([
                'available'                  => true,
                'ratio'                      => 1.0,
                'risk'                       => 0,
                'absolute_minimum_triggered' => false,
            ], $loi)
            : ['available' => false];

        return ['quality_criteria' => $qualityCriteria];
    }

    private function normalScaleDetails(int $count): array
    {
        $details = [];
        for ($i = 0; $i < $count; $i++) {
            $details[] = [
                'question_id'   => $i + 1,
                'level'         => 'Normale',
                'quality_score' => 100,
                'reasons'       => [],
            ];
        }
        return $details;
    }

    private function zeroScaleDetails(int $count): array
    {
        $details = [];
        for ($i = 0; $i < $count; $i++) {
            $details[] = [
                'question_id'   => $i + 1,
                'level'         => 'Da Verificare',
                'quality_score' => 0,
                'reasons'       => ['Risposte tutte uguali'],
            ];
        }
        return $details;
    }

    // -------------------------------------------------------------------------
    // Test 1: 9/9 fake → score ordinario 40, score finale 0, reason open_all_fake
    // -------------------------------------------------------------------------
    public function test_open_all_fake_caps_to_zero(): void
    {
        $interview = $this->makeInterview(
            ['analyzed_answers' => 9, 'fake_answers' => 9, 'fake_percentage' => 100.0, 'risk' => 100],
            ['details' => $this->normalScaleDetails(2), 'analyzed_scales' => 2],
            ['ratio' => 0.9, 'risk' => 0]
        );

        $result = $this->callCaps($interview, 40);

        $this->assertTrue($result['applied']);
        $this->assertSame(40, $result['base_score']);
        $this->assertSame(0, $result['final_score']);
        $this->assertSame(0, $result['effective_maximum_score']);
        $this->assertContains('open_all_fake', array_column($result['rules'], 'reason'));
    }

    // -------------------------------------------------------------------------
    // Test 2: 5/9 fake (risk=70) + loi_risk=80 → score ordinario 34, finale 25
    // -------------------------------------------------------------------------
    public function test_severe_open_and_loi_caps_to_25(): void
    {
        $interview = $this->makeInterview(
            ['analyzed_answers' => 9, 'fake_answers' => 5, 'fake_percentage' => 55.56, 'risk' => 70],
            ['details' => $this->normalScaleDetails(2), 'analyzed_scales' => 2],
            ['ratio' => 0.28, 'risk' => 80, 'absolute_minimum_triggered' => false]
        );

        $result = $this->callCaps($interview, 34);

        $this->assertTrue($result['applied']);
        $this->assertSame(34, $result['base_score']);
        $this->assertSame(25, $result['final_score']);
        $this->assertContains('severe_open_and_loi', array_column($result['rules'], 'reason'));
    }

    // -------------------------------------------------------------------------
    // Test 3: 2/9 fake + loi_risk=35 → score ordinario 62, finale 59
    // -------------------------------------------------------------------------
    public function test_two_active_criteria_caps_to_59(): void
    {
        $interview = $this->makeInterview(
            ['analyzed_answers' => 9, 'fake_answers' => 2, 'fake_percentage' => 22.22, 'risk' => 45],
            ['details' => $this->normalScaleDetails(2), 'analyzed_scales' => 2],
            ['ratio' => 0.52, 'risk' => 35, 'absolute_minimum_triggered' => false]
        );

        $result = $this->callCaps($interview, 62);

        $this->assertTrue($result['applied']);
        $this->assertSame(62, $result['base_score']);
        $this->assertSame(59, $result['final_score']);
        $this->assertContains('multiple_anomalous_criteria', array_column($result['rules'], 'reason'));
    }

    // -------------------------------------------------------------------------
    // Test 4: 2 scale quality_score=0 → score ordinario 90, finale 55
    // -------------------------------------------------------------------------
    public function test_two_zero_quality_scales_caps_to_55(): void
    {
        $interview = $this->makeInterview(
            ['analyzed_answers' => 9, 'fake_answers' => 0, 'fake_percentage' => 0.0, 'risk' => 0],
            ['details' => $this->zeroScaleDetails(2), 'analyzed_scales' => 2],
            ['ratio' => 0.9, 'risk' => 0]
        );

        $result = $this->callCaps($interview, 90);

        $this->assertTrue($result['applied']);
        $this->assertSame(55, $result['final_score']);
        $this->assertContains('multiple_zero_quality_scales', array_column($result['rules'], 'reason'));
    }

    // -------------------------------------------------------------------------
    // Test 5: 3 scale quality_score=0 → score ordinario 90, finale 40
    // -------------------------------------------------------------------------
    public function test_three_zero_quality_scales_caps_to_40(): void
    {
        $interview = $this->makeInterview(
            ['analyzed_answers' => 9, 'fake_answers' => 0, 'fake_percentage' => 0.0, 'risk' => 0],
            ['details' => $this->zeroScaleDetails(3), 'analyzed_scales' => 3],
            ['ratio' => 0.9, 'risk' => 0]
        );

        $result = $this->callCaps($interview, 90);

        $this->assertTrue($result['applied']);
        $this->assertSame(40, $result['final_score']);
        $this->assertContains('three_or_more_zero_quality_scales', array_column($result['rules'], 'reason'));
    }

    // -------------------------------------------------------------------------
    // Test 6: 2/10 fake + loi_risk=50 → score ordinario 73, finale 59
    // -------------------------------------------------------------------------
    public function test_open_and_loi_active_caps_to_59(): void
    {
        $interview = $this->makeInterview(
            ['analyzed_answers' => 10, 'fake_answers' => 2, 'fake_percentage' => 20.0, 'risk' => 20],
            ['details' => $this->normalScaleDetails(2), 'analyzed_scales' => 2],
            ['ratio' => 0.45, 'risk' => 50, 'absolute_minimum_triggered' => false]
        );

        $result = $this->callCaps($interview, 73);

        $this->assertTrue($result['applied']);
        $this->assertSame(73, $result['base_score']);
        $this->assertSame(59, $result['final_score']);
        $this->assertContains('multiple_anomalous_criteria', array_column($result['rules'], 'reason'));
    }

    // -------------------------------------------------------------------------
    // Test 7: LOI ratio=0.50 → score ordinario 85, finale 60
    // -------------------------------------------------------------------------
    public function test_loi_ratio_050_caps_to_60(): void
    {
        $interview = $this->makeInterview(
            ['analyzed_answers' => 9, 'fake_answers' => 0, 'fake_percentage' => 0.0, 'risk' => 0],
            ['details' => $this->normalScaleDetails(2), 'analyzed_scales' => 2],
            ['ratio' => 0.50, 'risk' => 35, 'absolute_minimum_triggered' => false]
        );

        $result = $this->callCaps($interview, 85);

        $this->assertTrue($result['applied']);
        $this->assertSame(85, $result['base_score']);
        $this->assertSame(60, $result['final_score']);
        $this->assertContains('loi_ratio_below_050', array_column($result['rules'], 'reason'));
    }

    // -------------------------------------------------------------------------
    // Test 8: LOI ratio=0.60 → score ordinario 85, finale 75
    // -------------------------------------------------------------------------
    public function test_loi_ratio_060_caps_to_75(): void
    {
        $interview = $this->makeInterview(
            ['analyzed_answers' => 9, 'fake_answers' => 0, 'fake_percentage' => 0.0, 'risk' => 0],
            ['details' => $this->normalScaleDetails(2), 'analyzed_scales' => 2],
            ['ratio' => 0.60, 'risk' => 20, 'absolute_minimum_triggered' => false]
        );

        $result = $this->callCaps($interview, 85);

        $this->assertTrue($result['applied']);
        $this->assertSame(85, $result['base_score']);
        $this->assertSame(75, $result['final_score']);
        $this->assertContains('loi_ratio_below_060', array_column($result['rules'], 'reason'));
    }

    // -------------------------------------------------------------------------
    // Test 9: LOI < 60s → cap 35 applicato
    // -------------------------------------------------------------------------
    public function test_loi_absolute_minimum_cap_still_applied(): void
    {
        $interview = $this->makeInterview(
            ['analyzed_answers' => 9, 'fake_answers' => 0, 'fake_percentage' => 0.0, 'risk' => 0],
            ['details' => $this->normalScaleDetails(2), 'analyzed_scales' => 2],
            ['ratio' => 0.15, 'risk' => 100, 'absolute_minimum_triggered' => true]
        );

        $result = $this->callCaps($interview, 70);

        $this->assertTrue($result['applied']);
        $this->assertLessThanOrEqual(35, $result['final_score']);
        $this->assertContains('loi_below_absolute_minimum', array_column($result['rules'], 'reason'));
    }

    // -------------------------------------------------------------------------
    // Test 10a: 1/1 fake → cap 30 (open_all_fake_single, incertezza ~53%)
    // -------------------------------------------------------------------------
    public function test_open_all_fake_single_answer_caps_to_30(): void
    {
        $interview = $this->makeInterview(
            ['analyzed_answers' => 1, 'fake_answers' => 1, 'fake_percentage' => 100.0, 'risk' => 100],
            ['details' => $this->normalScaleDetails(2), 'analyzed_scales' => 2],
            ['ratio' => 0.9, 'risk' => 0]
        );

        $result = $this->callCaps($interview, 60);

        $this->assertTrue($result['applied']);
        $this->assertSame(30, $result['final_score']);
        $this->assertContains('open_all_fake_single', array_column($result['rules'], 'reason'));
    }

    // -------------------------------------------------------------------------
    // Test 10b: 2/2 fake → cap 15 (open_all_fake_minimal, confidenza ~82%)
    // -------------------------------------------------------------------------
    public function test_open_all_fake_two_answers_caps_to_15(): void
    {
        $interview = $this->makeInterview(
            ['analyzed_answers' => 2, 'fake_answers' => 2, 'fake_percentage' => 100.0, 'risk' => 100],
            ['details' => $this->normalScaleDetails(2), 'analyzed_scales' => 2],
            ['ratio' => 0.9, 'risk' => 0]
        );

        $result = $this->callCaps($interview, 31);

        $this->assertTrue($result['applied']);
        $this->assertSame(15, $result['final_score']);
        $this->assertContains('open_all_fake_minimal', array_column($result['rules'], 'reason'));
    }

    // -------------------------------------------------------------------------
    // Test 10c: 3/3 fake → cap 5 (open_all_fake_few, confidenza ~96%)
    // -------------------------------------------------------------------------
    public function test_open_all_fake_three_answers_caps_to_5(): void
    {
        $interview = $this->makeInterview(
            ['analyzed_answers' => 3, 'fake_answers' => 3, 'fake_percentage' => 100.0, 'risk' => 100],
            ['details' => $this->normalScaleDetails(2), 'analyzed_scales' => 2],
            ['ratio' => 0.9, 'risk' => 0]
        );

        $result = $this->callCaps($interview, 40);

        $this->assertTrue($result['applied']);
        $this->assertSame(5, $result['final_score']);
        $this->assertContains('open_all_fake_few', array_column($result['rules'], 'reason'));
    }

    // -------------------------------------------------------------------------
    // Test 10d: 4/4 fake → cap 0 (open_all_fake, confidenza >99%)
    // -------------------------------------------------------------------------
    public function test_open_all_fake_four_answers_caps_to_zero(): void
    {
        $interview = $this->makeInterview(
            ['analyzed_answers' => 4, 'fake_answers' => 4, 'fake_percentage' => 100.0, 'risk' => 100],
            ['details' => $this->normalScaleDetails(2), 'analyzed_scales' => 2],
            ['ratio' => 0.9, 'risk' => 0]
        );

        $result = $this->callCaps($interview, 40);

        $this->assertTrue($result['applied']);
        $this->assertSame(0, $result['final_score']);
        $this->assertContains('open_all_fake', array_column($result['rules'], 'reason'));
    }

    // -------------------------------------------------------------------------
    // Test 10: nessuna anomalia → nessun cap, score finale = score ordinario
    // -------------------------------------------------------------------------
    public function test_no_cap_when_all_clean(): void
    {
        $interview = $this->makeInterview(
            ['analyzed_answers' => 9, 'fake_answers' => 0, 'fake_percentage' => 0.0, 'risk' => 0],
            ['details' => $this->normalScaleDetails(3), 'analyzed_scales' => 3],
            ['ratio' => 0.95, 'risk' => 0]
        );

        $result = $this->callCaps($interview, 100);

        $this->assertFalse($result['applied']);
        $this->assertSame(100, $result['base_score']);
        $this->assertSame(100, $result['final_score']);
        $this->assertNull($result['effective_maximum_score']);
        $this->assertEmpty($result['rules']);
    }
}
