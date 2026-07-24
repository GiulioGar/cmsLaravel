<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PrimisApiService;
use Illuminate\Support\Facades\Log;


class FieldQualityController extends Controller
{
    private const LOI_MAX_SECONDS          = 2700;
    private const LOI_ABSOLUTE_MIN_SECONDS = 60;

    private const QUALITY_WEIGHTS = [
        'open'  => 50,
        'scale' => 30,
        'loi'   => 20,
    ];

    private const COVERAGE_THRESHOLDS = [
        'complete' => 70,
        'partial'  => 40,
    ];

    // [ratio_minimo, risk] — risk da 0 a 100; ordinato dal meno rischioso
    private const LOI_RISK_TABLE = [
        [0.80,  0],
        [0.70, 15],
        [0.60, 30],
        [0.50, 50],
        [0.40, 70],
        [0.30, 85],
        // fallback (ratio < 0.30): risk = 100
    ];

    // [percentuale_fake_massima, risk] — risk da 0 a 100; applicato solo se fakeCount > 0
    private const OPEN_RISK_TABLE = [
        [10.0,  20],
        [25.0,  40],
        [50.0,  70],
        // fallback (> 50%): risk = 100
    ];

    // [percentuale_cambi_massima, risk] — risk da 0 a 100; changes===0 gestito separatamente
    private const SCALE_RISK_TABLE = [
        [10.0, 75],
        [20.0, 50],
        [30.0, 25],
        // fallback (> 30%): risk = 0
    ];

    private const SCALE_AGGRAVATION_RISK    = 15;
    private const OPEN_SEVERE_FAKE_MIN_RISK = 50;

    public function index(Request $request, PrimisApiService $primis)
    {
        $prj = $request->query('prj');
        $sid = $request->query('sid');

        $directory = base_path("var/imr/fields/{$prj}/{$sid}/results");
        if (!is_dir($directory)) {
            $directory = "/var/imr/fields/{$prj}/{$sid}/results";
        }

        // 1) Parsing file .sre
        $parsed             = $this->parseSreFiles($directory);
        $completeInterviews = $parsed['interviews'];
        $loiData            = $parsed['loiData'];
        $openQuestionsData  = $parsed['openQuestions'];
        $scaleData          = $parsed['scaleData'];

        // 2) Singola chiamata API Primis (riusata per questionMap e questionsFromApi)
        $apiResponse      = $primis->listQuestions($prj, $sid);
        $questionsFromApi = $apiResponse['questions'] ?? [];
        $questionMap      = $this->buildQuestionMap($apiResponse);

        // 3) Arricchimento dati con codici domanda e flag fake
        $this->populateOpenQuestionsDetails($openQuestionsData, $questionMap);
        $this->populateScaleQuestionsDetails($scaleData, $questionMap);
        $this->sortOpenQuestions($openQuestionsData);

        // 4) Criteri di punteggio (popolano quality_criteria e quality_risks)
        $loiMedianSec = $this->applyLoiCriterion($completeInterviews);
        $this->applyOpenQuestionsCriterion($completeInterviews, $openQuestionsData);
        $this->applyScaleChangesCriterion($completeInterviews, $scaleData);

        // 5) Score qualità finale (0-100) basato su risk ponderati
        $this->applyFinalQualityScore($completeInterviews);

        // 6) Arricchimento presentazione: stelle, etichetta qualitativa, motivazioni
        $this->applyQualityPresentationData($completeInterviews);

        // 7) Statistiche e classificazione
        $stats          = $this->computeScoreStats($completeInterviews);
        $classification = $this->computeQualityClassification($completeInterviews);

        // 8) Dati DB (navbar e panel)
        $ricercheInCorso = DB::table('t_panel_control')
            ->where('stato', 0)
            ->orderBy('description', 'asc')
            ->get(['sur_id', 'description', 'prj']);
        $panelData = DB::table('t_panel_control')->where('sur_id', $sid)->first();

        return view('fieldQuality', array_merge([
            'prj'                => $prj,
            'sid'                => $sid,
            'panelData'          => $panelData,
            'ricercheInCorso'    => $ricercheInCorso,
            'averageScore'       => $stats['average'],
            'maxScore'           => $stats['max'],
            'minScore'           => $stats['min'],
            'loiMediaFormatted'  => $this->formatLoiSec($loiMedianSec),
            'completeInterviews' => $completeInterviews,
            'loiData'            => $loiData,
            'openQuestionsData'  => $openQuestionsData,
            'scaleData'          => $scaleData,
            'questionsFromApi'   => $questionsFromApi,
        ], $classification));
    }

    // =========================================================================
    // PARSING
    // =========================================================================

    private function parseSreFiles(string $directory): array
    {
        $completeInterviews = [];
        $loiData            = [];
        $openQuestionsData  = [];
        $scaleData          = [];

        if (!is_dir($directory)) {
            return [
                'interviews'    => $completeInterviews,
                'loiData'       => $loiData,
                'openQuestions' => $openQuestionsData,
                'scaleData'     => $scaleData,
            ];
        }

        foreach (glob($directory . "/*.sre") as $file) {
            $line = $this->readFirstLine($file);
            if (!$line) {
                continue;
            }

            $data   = explode(";", trim($line));
            $offset = (isset($data[0]) && $data[0] === '2.0') ? 0 : -1;

            $status    = isset($data[8 + $offset]) ? (int) $data[8 + $offset] : null;
            $iid       = $data[3 + $offset] ?? 'N/A';
            $uid       = $data[4 + $offset] ?? 'N/A';
            $loiSec    = isset($data[7 + $offset]) ? (int) $data[7 + $offset] : 0;
            $panelUsed = $this->detectPanel($data);

            if ($status !== 3) {
                continue;
            }

            $completeInterviews[] = [
                'iid'                => $iid,
                'uid'                => $uid,
                'panel'              => $panelUsed,
                'loiSec'             => $loiSec,
                'score'              => null,
                'quality_criteria'   => [],
                'quality_risks'      => [],
                'quality_weights'    => [],
                'quality_coverage'   => [],
                'quality_risk_total' => null,
            ];

            $loiData[] = [
                'iid' => $iid,
                'uid' => $uid,
                'loi' => $this->formatLoiSec($loiSec),
            ];

            $this->extractOpenQuestions($file, $iid, $uid, $panelUsed, $openQuestionsData);
            $this->extractScaleData($file, $iid, $uid, $panelUsed, $scaleData);
        }

        return [
            'interviews'    => $completeInterviews,
            'loiData'       => $loiData,
            'openQuestions' => $openQuestionsData,
            'scaleData'     => $scaleData,
        ];
    }

    private function formatLoiSec(float $loiSec): string
    {
        if ($loiSec <= 0) {
            return '0.00';
        }
        $minutes = (int) floor($loiSec / 60);
        $seconds = (int) $loiSec % 60;
        return $minutes . '.' . str_pad((string) $seconds, 2, '0', STR_PAD_LEFT);
    }

    private function sortOpenQuestions(array &$data): void
    {
        usort($data, function ($a, $b) {
            $aFake = $a['isFake'] ? 1 : 0;
            $bFake = $b['isFake'] ? 1 : 0;
            if ($aFake !== $bFake) {
                return $bFake - $aFake;
            }
            if ($a['uid'] !== $b['uid']) {
                return $a['uid'] <=> $b['uid'];
            }
            return $a['iid'] <=> $b['iid'];
        });
    }

    // =========================================================================
    // STATISTICHE
    // =========================================================================

    private function computeScoreStats(array $interviews): array
    {
        $validScores = array_values(
            array_filter(array_column($interviews, 'score'), fn($s) => $s !== null)
        );

        if (empty($validScores)) {
            return ['average' => null, 'max' => null, 'min' => null];
        }

        return [
            'average' => round(array_sum($validScores) / count($validScores), 1),
            'max'     => round(max($validScores), 1),
            'min'     => round(min($validScores), 1),
        ];
    }

    private function computeQualityClassification(array $interviews): array
    {
        $total     = count($interviews);
        $evaluable = array_values(
            array_filter($interviews, fn($iv) => $iv['score'] !== null)
        );
        $evaluableCount = count($evaluable);
        $notEvaluable   = $total - $evaluableCount;

        $high   = count(array_filter($evaluable, fn($iv) => $iv['score'] >= 70));
        $accept = count(array_filter($evaluable, fn($iv) => $iv['score'] >= 50 && $iv['score'] < 70));
        $low    = $evaluableCount - $high - $accept;

        if ($evaluableCount > 0) {
            $pctHigh   = round($high   / $evaluableCount * 100);
            $pctAccept = round($accept / $evaluableCount * 100);
            $pctLow    = 100 - $pctHigh - $pctAccept;
        } else {
            $pctHigh = $pctAccept = $pctLow = 0;
        }

        $pctNotEvaluable = $total > 0 ? round($notEvaluable / $total * 100) : 0;

        return [
            'totalInterviews'        => $total,
            'evaluableInterviews'    => $evaluableCount,
            'notEvaluableInterviews' => $notEvaluable,
            'pctHigh'                => $pctHigh,
            'pctAccept'              => $pctAccept,
            'pctLow'                 => $pctLow,
            'pctNotEvaluable'        => $pctNotEvaluable,
        ];
    }

    // =========================================================================
    // QUESTION MAP (Primis API)
    // =========================================================================

    private function buildQuestionMap(array $apiResponse): array
    {
        if (!isset($apiResponse['questions']) || !is_array($apiResponse['questions'])) {
            return [];
        }

        $questionMap = [];
        foreach ($apiResponse['questions'] as $q) {
            if (isset($q['id'])) {
                $id = (int) $q['id'];
                $questionMap[$id] = [
                    'code' => $q['code'] ?? 'N/A',
                    'text' => $q['text'] ?? 'No text',
                ];
            }
        }

        return $questionMap;
    }

    // =========================================================================
    // ESTRAZIONE DATI DA FILE .SRE
    // =========================================================================

    private function extractOpenQuestions(string $filePath, string $iid, string $uid, string $panelUsed, array &$openQuestionsData): void
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return;
        }

        while (($line = fgets($handle)) !== false) {
            $line   = trim($line);
            $fields = explode(";", $line);

            if (!isset($fields[0]) || $fields[0] !== 'open') {
                continue;
            }

            $questionId   = $fields[1] ?? null;
            $openResponse = $fields[2] ?? '';

            if (is_numeric($openResponse)) {
                continue;
            }

            $openQuestionsData[] = [
                'iid'          => $iid,
                'uid'          => $uid,
                'panel'        => $panelUsed,
                'questionId'   => (int) $questionId,
                'openResponse' => $openResponse,
            ];
        }

        fclose($handle);
    }

    private function extractScaleData(string $filePath, string $iid, string $uid, string $panelUsed, array &$scaleData): void
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return;
        }

        while (($line = fgets($handle)) !== false) {
            $parsed = $this->parseScaleLine(trim($line));
            if ($parsed !== null) {
                $parsed['iid']   = $iid;
                $parsed['uid']   = $uid;
                $parsed['panel'] = $panelUsed;
                $scaleData[]     = $parsed;
            }
        }

        fclose($handle);
    }

    // =========================================================================
    // ARRICCHIMENTO CON QUESTION MAP
    // =========================================================================

    private function populateOpenQuestionsDetails(array &$openData, array $questionMap): void
    {
        $whiteList = $this->loadWhiteList();
        $blackList = $this->loadBlackList();

        foreach ($openData as &$row) {
            $id = $row['questionId'];
            if (isset($questionMap[$id])) {
                $row['codice']  = $questionMap[$id]['code'];
                $row['tooltip'] = $questionMap[$id]['text'];
            } else {
                $row['codice']  = 'unknown';
                $row['tooltip'] = 'Domanda non presente';
            }

            $classification  = $this->classifyOpenResponse($row['openResponse'], $whiteList, $blackList);
            $row['isFake']   = $classification['is_fake'];
            $row['is_severe'] = $classification['is_severe'];
            $row['reason']    = $classification['reason'];
        }
        unset($row);
    }

    private function populateScaleQuestionsDetails(array &$scaleData, array $questionMap): void
    {
        foreach ($scaleData as &$row) {
            $qId = $row['questionId'] ?? null;
            if (isset($questionMap[$qId])) {
                $row['code']    = $questionMap[$qId]['code'];
                $row['tooltip'] = $questionMap[$qId]['text'];
            } else {
                $row['code']    = 'unknown';
                $row['tooltip'] = 'Domanda non presente';
            }
        }
        unset($row);
    }

    // =========================================================================
    // CRITERI DI PUNTEGGIO
    // =========================================================================

    private function applyLoiCriterion(array &$interviews): float
    {
        $loiEligible = [];
        foreach ($interviews as $iv) {
            if ($iv['loiSec'] > 0 && $iv['loiSec'] < self::LOI_MAX_SECONDS) {
                $loiEligible[] = (float) $iv['loiSec'];
            }
        }

        $medianLoi = count($loiEligible) > 0 ? $this->calculateMedian($loiEligible) : 0.0;
        $available = $medianLoi > 0.0;

        foreach ($interviews as &$iv) {
            $loiSec = (int) $iv['loiSec'];

            if (!$available) {
                $iv['quality_criteria']['loi'] = [
                    'available'                  => false,
                    'seconds'                    => $loiSec,
                    'median_seconds'             => 0,
                    'ratio'                      => null,
                    'absolute_minimum_triggered' => false,
                    'risk'                       => null,
                ];
                // chiave 'loi' assente da quality_risks (non disponibile)
                continue;
            }

            $ratio                = $loiSec / $medianLoi;
            $absoluteMinTriggered = $loiSec > 0 && $loiSec < self::LOI_ABSOLUTE_MIN_SECONDS;

            if ($absoluteMinTriggered) {
                $risk = 100;
            } else {
                $risk = 100; // fallback ratio < 0.30
                foreach (self::LOI_RISK_TABLE as [$minRatio, $tableRisk]) {
                    if ($ratio >= $minRatio) {
                        $risk = $tableRisk;
                        break;
                    }
                }
            }

            $risk = (int) min(100, max(0, $risk));

            $iv['quality_criteria']['loi'] = [
                'available'                  => true,
                'seconds'                    => $loiSec,
                'median_seconds'             => $medianLoi,
                'ratio'                      => round($ratio, 4),
                'absolute_minimum_triggered' => $absoluteMinTriggered,
                'risk'                       => $risk,
            ];
            $iv['quality_risks']['loi'] = $risk;
        }
        unset($iv);

        return $medianLoi;
    }

    private function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }
        sort($values);
        $count = count($values);
        $mid   = (int) floor($count / 2);
        if ($count % 2 === 1) {
            return (float) $values[$mid];
        }
        return ($values[$mid - 1] + $values[$mid]) / 2.0;
    }

    private function applyOpenQuestionsCriterion(array &$completeInterviews, array $openQuestionsData): void
    {
        $openByInterview = [];
        foreach ($openQuestionsData as $open) {
            $iid = $open['iid'] ?? null;
            if ($iid !== null) {
                $openByInterview[$iid][] = $open;
            }
        }

        foreach ($completeInterviews as &$iv) {
            $iid      = $iv['iid'];
            $answers  = $openByInterview[$iid] ?? [];
            $analyzed = count($answers);

            if ($analyzed === 0) {
                $iv['quality_criteria']['open'] = [
                    'available'           => false,
                    'analyzed_answers'    => 0,
                    'fake_answers'        => 0,
                    'severe_fake_answers' => 0,
                    'fake_percentage'     => 0.0,
                    'severe_fake'         => false,
                    'risk'                => null,
                ];
                // chiave 'open' assente da quality_risks (non disponibile)
                continue;
            }

            $fakeCount       = 0;
            $severeFakeCount = 0;
            foreach ($answers as $a) {
                if (!empty($a['isFake'])) {
                    $fakeCount++;
                    if (!empty($a['is_severe'])) {
                        $severeFakeCount++;
                    }
                }
            }

            $fakePct = round(($fakeCount / $analyzed) * 100, 1);

            if ($fakeCount === 0) {
                $risk = 0;
            } else {
                $risk = 100; // fallback > 50%
                foreach (self::OPEN_RISK_TABLE as [$maxPct, $tableRisk]) {
                    if ($fakePct <= $maxPct) {
                        $risk = $tableRisk;
                        break;
                    }
                }
            }

            $severeFake = $severeFakeCount > 0;
            if ($severeFake) {
                $risk = max($risk, self::OPEN_SEVERE_FAKE_MIN_RISK);
            }

            $risk = (int) min(100, max(0, $risk));

            $iv['quality_criteria']['open'] = [
                'available'           => true,
                'analyzed_answers'    => $analyzed,
                'fake_answers'        => $fakeCount,
                'severe_fake_answers' => $severeFakeCount,
                'fake_percentage'     => $fakePct,
                'severe_fake'         => $severeFake,
                'risk'                => $risk,
            ];
            $iv['quality_risks']['open'] = $risk;
        }
        unset($iv);
    }

    private function applyScaleChangesCriterion(array &$completeInterviews, array $scaleData): void
    {
        $scaleByInterview = [];
        foreach ($scaleData as $scale) {
            $iid = $scale['iid'] ?? null;
            if ($iid !== null) {
                $scaleByInterview[$iid][] = $scale;
            }
        }

        foreach ($completeInterviews as &$iv) {
            $iid    = $iv['iid'];
            $scales = $scaleByInterview[$iid] ?? [];

            if (empty($scales)) {
                $iv['quality_criteria']['scale'] = ['available' => false];
                // chiave 'scale' assente da quality_risks (non disponibile)
                continue;
            }

            $details = [];
            foreach ($scales as $scale) {
                $validAnswers    = count($scale['answers']);
                $possibleChanges = $validAnswers - 1;
                $changes         = $scale['changes'];
                $changePct       = $possibleChanges > 0
                    ? round(($changes / $possibleChanges) * 100, 1)
                    : 0.0;

                if ($changes === 0) {
                    $gRisk = 100;
                } elseif ($changePct > 30.0) {
                    $gRisk = 0;
                } else {
                    $gRisk = 25; // fallback intermedio (>20–30%)
                    foreach (self::SCALE_RISK_TABLE as [$maxPct, $tableRisk]) {
                        if ($changePct <= $maxPct) {
                            $gRisk = $tableRisk;
                            break;
                        }
                    }
                }

                $details[] = [
                    'question_id'       => $scale['questionId'],
                    'valid_answers'     => $validAnswers,
                    'possible_changes'  => $possibleChanges,
                    'changes'           => $changes,
                    'change_percentage' => $changePct,
                    'risk'              => $gRisk,
                ];
            }

            $analyzedCount  = count($details);
            $baseRisk       = array_sum(array_column($details, 'risk')) / $analyzedCount;
            $lowChangeCount = count(array_filter($details, fn($d) => $d['change_percentage'] <= 10.0));
            $aggravation    = ($lowChangeCount / $analyzedCount >= 0.5) ? self::SCALE_AGGRAVATION_RISK : 0;
            $risk           = (int) min(100, max(0, round($baseRisk + $aggravation)));

            $iv['quality_criteria']['scale'] = [
                'available'          => true,
                'analyzed_scales'    => $analyzedCount,
                'critical_scales'    => $lowChangeCount,
                'base_risk'          => round($baseRisk, 1),
                'repeat_aggravation' => $aggravation,
                'risk'               => $risk,
                'details'            => $details,
            ];
            $iv['quality_risks']['scale'] = $risk;
        }
        unset($iv);
    }

    private function applyFinalQualityScore(array &$completeInterviews): void
    {
        $criteria = ['open', 'scale', 'loi'];

        foreach ($completeInterviews as &$iv) {
            $numerator       = 0;
            $availableWeight = 0;

            foreach ($criteria as $key) {
                if (!empty($iv['quality_criteria'][$key]['available'])) {
                    $risk            = (int) min(100, max(0, (int) ($iv['quality_risks'][$key] ?? 0)));
                    $weight          = self::QUALITY_WEIGHTS[$key];
                    $numerator      += $risk * $weight;
                    $availableWeight += $weight;
                }
            }

            $iv['quality_weights'] = [
                'open'            => self::QUALITY_WEIGHTS['open'],
                'scale'           => self::QUALITY_WEIGHTS['scale'],
                'loi'             => self::QUALITY_WEIGHTS['loi'],
                'available_total' => $availableWeight,
            ];

            if ($availableWeight === 0) {
                $iv['score']              = null;
                $iv['quality_risk_total'] = null;
                $iv['quality_coverage']   = [
                    'percentage' => 0,
                    'level'      => 'none',
                    'label'      => 'Non valutabile',
                ];
            } else {
                $totalRisk                = (int) round($numerator / $availableWeight);
                $iv['score']              = max(0, min(100, 100 - $totalRisk));
                $iv['quality_risk_total'] = $totalRisk;

                $pct = $availableWeight;
                if ($pct >= self::COVERAGE_THRESHOLDS['complete']) {
                    $level = 'complete';
                    $label = 'Valutazione completa';
                } elseif ($pct >= self::COVERAGE_THRESHOLDS['partial']) {
                    $level = 'partial';
                    $label = 'Valutazione parziale';
                } else {
                    $level = 'limited';
                    $label = 'Valutazione limitata';
                }

                $iv['quality_coverage'] = [
                    'percentage' => $pct,
                    'level'      => $level,
                    'label'      => $label,
                ];
            }
        }
        unset($iv);
    }

    private function applyQualityPresentationData(array &$completeInterviews): void
    {
        foreach ($completeInterviews as &$iv) {
            if ($iv['score'] === null) {
                $iv['stars']                = null;
                $iv['rating_label']         = 'Non valutabile';
                $iv['rating_context_label'] = null;
            } else {
                $rating             = $this->convertScoreToRating((int) $iv['score']);
                $iv['stars']        = $rating['stars'];
                $iv['rating_label'] = $rating['label'];

                $coverageLevel = $iv['quality_coverage']['level'] ?? 'complete';
                if ($coverageLevel === 'partial') {
                    $iv['rating_context_label'] = $rating['label'] . ' sui criteri disponibili';
                } elseif ($coverageLevel === 'limited') {
                    $iv['rating_context_label'] = $rating['label'] . ' (valutazione limitata)';
                } else {
                    $iv['rating_context_label'] = $rating['label'];
                }
            }

            $iv['quality_reasons'] = $this->buildQualityReasons($iv);
        }
        unset($iv);
    }

    private function convertScoreToRating(int $score): array
    {
        $score = max(0, min(100, $score));

        if ($score >= 90) { return ['stars' => 5.0, 'label' => 'Ottima']; }
        if ($score >= 80) { return ['stars' => 4.5, 'label' => 'Molto buona']; }
        if ($score >= 70) { return ['stars' => 4.0, 'label' => 'Buona']; }
        if ($score >= 60) { return ['stars' => 3.5, 'label' => 'Accettabile']; }
        if ($score >= 50) { return ['stars' => 3.0, 'label' => 'Da verificare']; }
        if ($score >= 40) { return ['stars' => 2.5, 'label' => 'Sospetta']; }
        if ($score >= 30) { return ['stars' => 2.0, 'label' => 'Scarsa']; }
        if ($score >= 20) { return ['stars' => 1.5, 'label' => 'Molto scarsa']; }
        if ($score >= 10) { return ['stars' => 1.0, 'label' => 'Probabile fake']; }
        return ['stars' => 0.5, 'label' => 'Fortemente inattendibile'];
    }

    private function buildQualityReasons(array $interview): array
    {
        $open  = $interview['quality_criteria']['open']  ?? null;
        $scale = $interview['quality_criteria']['scale'] ?? null;
        $loi   = $interview['quality_criteria']['loi']   ?? null;

        $hasAnyCriterion = (!empty($open['available']))
            || (!empty($scale['available']))
            || (!empty($loi['available']));

        if (!$hasAnyCriterion) {
            return ['Nessun criterio disponibile per la valutazione'];
        }

        $reasons = [];

        // Domande aperte
        if ($open && !empty($open['available'])) {
            $fakeCount = (int) ($open['fake_answers'] ?? 0);
            if ($fakeCount > 0) {
                if (!empty($open['severe_fake'])) {
                    $reasons[] = 'Presente almeno una risposta aperta palesemente casuale';
                } else {
                    $total  = (int) ($open['analyzed_answers'] ?? 0);
                    $parola = $fakeCount === 1 ? 'risposta aperta sospetta' : 'risposte aperte sospette';
                    $reasons[] = "{$fakeCount} {$parola} su {$total} analizzate";
                }
            }
        }

        // Griglie
        if ($scale && !empty($scale['available'])) {
            $critical = (int) ($scale['critical_scales'] ?? 0);
            if ($critical > 0) {
                $analyzed = (int) ($scale['analyzed_scales'] ?? 0);
                $parola   = $critical === 1 ? 'griglia' : 'griglie';
                $reason   = "{$critical} {$parola} con risposte quasi tutte identiche su {$analyzed} analizzate";
                if (!empty($scale['repeat_aggravation'])) {
                    $reason .= ', comportamento ripetuto su almeno metà delle griglie';
                }
                $reasons[] = $reason;
            }
        }

        // LOI
        if ($loi && !empty($loi['available']) && ($loi['risk'] ?? 0) > 0) {
            if (!empty($loi['absolute_minimum_triggered'])) {
                $reasons[] = 'Intervista completata in meno di 60 secondi';
            } else {
                $pct       = (int) round(($loi['ratio'] ?? 0) * 100);
                $reasons[] = "LOI pari al {$pct}% della mediana del progetto";
            }
        }

        return empty($reasons) ? ['Nessuna anomalia rilevata'] : $reasons;
    }

    // =========================================================================
    // FAKE DETECTION
    // =========================================================================

    /**
     * Classifica una risposta aperta restituendo is_fake, is_severe e reason.
     *
     * Criteri severe (segnali forti e deterministici):
     *   blacklist, excess_repeats, random_alphanumeric, illegal_sequence
     *   (illegal_sequence: solo sequenze da tastiera riconoscibili — qwerty, asdfgh, zxcvbn, abcdef, 123456…)
     *
     * Criteri normali (segnali più deboli, potrebbero includere falsi positivi):
     *   too_short, contains_url, suspicious_consonants
     */
    private function classifyOpenResponse(string $resp, array $whiteList, array $blackList): array
    {
        $respTrim       = trim($resp);
        $respLower      = mb_strtolower($respTrim);
        $whiteListLower = array_map('mb_strtolower', $whiteList);
        $blackListLower = array_map('mb_strtolower', $blackList);

        // 1. Whitelist → valida
        if (in_array($respLower, $whiteListLower, true)) {
            return ['is_fake' => false, 'is_severe' => false, 'reason' => null];
        }

        // 2. Blacklist → fake grave (match esplicito su risposta nota come spazzatura)
        if (in_array($respLower, $blackListLower, true)) {
            return ['is_fake' => true, 'is_severe' => true, 'reason' => 'blacklist'];
        }

        // 3. Troppo corta → fake normale (potrebbe essere "ok", "sì", ecc.)
        if (mb_strlen($respTrim) < 3) {
            return ['is_fake' => true, 'is_severe' => false, 'reason' => 'too_short'];
        }

        // 4. Contiene URL → fake normale
        if (preg_match('/(http:\/\/|https:\/\/|www\.)|(\.(com|it)(\s|\/|$))/i', $respTrim)) {
            return ['is_fake' => true, 'is_severe' => false, 'reason' => 'contains_url'];
        }

        // 5. Caratteri ripetuti in eccesso → fake grave ("aaaaaaa", "!!!!!!")
        if ($this->hasExcessRepeats($respTrim)) {
            return ['is_fake' => true, 'is_severe' => true, 'reason' => 'excess_repeats'];
        }

        // 6. Tutte le parole combinano lettere e numeri casualmente → fake grave ("abc123 xyz456")
        if ($this->allWordsHaveRandomLetterNumberCombo($respTrim)) {
            return ['is_fake' => true, 'is_severe' => true, 'reason' => 'random_alphanumeric'];
        }

        // 7. Parola singola con sequenza deterministica da tastiera → fake grave
        // (qwerty, asdfgh, zxcvbn, abcdef, 123456 e sottostringhe di almeno 4 caratteri)
        if ($this->isSingleWordWithIllegalSequence($respTrim)) {
            return ['is_fake' => true, 'is_severe' => true, 'reason' => 'illegal_sequence'];
        }

        // 8. Tutte le parole hanno pattern consonantici sospetti → fake normale
        // (copertura ampia: potrebbe includere abbreviazioni o typo)
        if ($this->hasOnlySuspiciousWords($respTrim)) {
            return ['is_fake' => true, 'is_severe' => false, 'reason' => 'suspicious_consonants'];
        }

        return ['is_fake' => false, 'is_severe' => false, 'reason' => null];
    }

    private function hasExcessRepeats(string $resp, int $threshold = 5): bool
    {
        return (bool) preg_match('/(.)\1{' . ($threshold - 1) . ',}/u', $resp);
    }

    private function allWordsHaveRandomLetterNumberCombo(string $resp): bool
    {
        $words = preg_split('/\s+/', $resp);
        foreach ($words as $word) {
            if (!preg_match('/[A-Za-z]+[0-9]+|[0-9]+[A-Za-z]+/', $word)) {
                return false;
            }
        }
        return true;
    }

    private function hasOnlySuspiciousWords(string $resp): bool
    {
        $words = preg_split('/\s+/', $resp);
        foreach ($words as $word) {
            if (
                !$this->isClearlyRandomWord($word) &&
                !preg_match('/[bcdfghjklmnpqrstvwxyz]{5,}/i', $word) &&
                !$this->isShortWordWithoutVowels($word) &&
                !$this->isShortSuspiciousWord($word)
            ) {
                return false;
            }
        }
        return true;
    }

    private function isShortWordWithoutVowels(string $word): bool
    {
        $word = trim(mb_strtolower($word));
        return mb_strlen($word) >= 3 && mb_strlen($word) <= 5 && !preg_match('/[aeiou]/i', $word);
    }

    private function isShortSuspiciousWord(string $word): bool
    {
        $word = mb_strtolower(trim($word));
        return mb_strlen($word) >= 2 && mb_strlen($word) <= 3 && !preg_match('/[aeiou]/i', $word);
    }

    private function isSingleWordWithIllegalSequence(string $resp): bool
    {
        $resp  = mb_strtolower(trim($resp));
        $words = preg_split('/\s+/', $resp);

        if (count($words) !== 1) {
            return false;
        }

        // Solo sequenze deterministiche da tastiera o alfabetiche (soglia: 4 char consecutivi)
        $references = [
            'qwertyuiop', 'poiuytrewq',
            'asdfghjkl',  'lkjhgfdsa',
            'zxcvbnm',    'mnbvcxz',
            'abcdefghijklmnopqrstuvwxyz', 'zyxwvutsrqponmlkjihgfedcba',
            '0123456789', '9876543210',
        ];

        foreach ($references as $ref) {
            $refLen = mb_strlen($ref);
            for ($start = 0; $start <= $refLen - 4; $start++) {
                if (mb_strpos($resp, mb_substr($ref, $start, 4)) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isClearlyRandomWord(string $word): bool
    {
        $word = mb_strtolower(trim($word));
        if (mb_strlen($word) < 6) {
            return false;
        }
        if (preg_match('/[aeiou]{4,}/i', $word)) {
            return true;
        }
        if (preg_match('/([jkqwxy][aeiou]){3,}/i', $word)) {
            return true;
        }
        return false;
    }

    // =========================================================================
    // WHITELIST / BLACKLIST
    // =========================================================================

    private function loadWhiteList(): array
    {
        $path = public_path('json/whitelist.json');
        if (!file_exists($path)) {
            return [];
        }
        return json_decode(file_get_contents($path), true) ?? [];
    }

    private function loadBlackList(): array
    {
        $path = public_path('json/blacklist.json');
        if (!file_exists($path)) {
            return [];
        }
        return json_decode(file_get_contents($path), true) ?? [];
    }

    // =========================================================================
    // UTILITY
    // =========================================================================

    private function readFirstLine(string $filePath): ?string
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return null;
        }
        $line = fgets($handle);
        fclose($handle);
        return $line ?: null;
    }

    private function detectPanel(array $data, ?int $dbPanelValue = null): string
    {
        $panelNames = [
            1 => 'Cint',
            2 => 'Dynata',
            3 => 'Bilendi',
            4 => 'Norstat',
            5 => 'Toluna',
            6 => 'Netquest',
            7 => 'CATI',
            8 => 'Makeopinion',
            9 => 'Altro Panel',
        ];

        foreach ($data as $element) {
            if (strpos($element, 'pan=') !== false) {
                $val = (int) str_replace('pan=', '', $element);
                return $panelNames[$val] ?? 'Altro Panel';
            }
        }

        return 'Interactive';
    }

    private function parseScaleLine(string $line): ?array
    {
        $fields = explode(";", $line);

        if (empty($fields[0]) || $fields[0] !== 'scale') {
            return null;
        }
        if (!isset($fields[1], $fields[2], $fields[3])) {
            return null;
        }

        $questionId = (int) $fields[1];
        $nRows      = (int) $fields[2];
        $nCols      = (int) $fields[3];

        $lastField = end($fields);
        if (preg_match('/^[01]+$/', $lastField) && strlen($lastField) > 5) {
            return null;
        }

        $answersRaw = array_slice($fields, 4);
        if (empty($answersRaw)) {
            return null;
        }

        $allAnswers      = array_map('intval', $answersRaw);
        $filteredAnswers = array_values(array_filter($allAnswers, fn($v) => $v !== -1));
        $countValid      = count($filteredAnswers);

        if ($countValid <= 7) {
            return null;
        }

        $changes    = $this->countSequentialChanges($filteredAnswers);
        $changesPct = (int) round(($changes / $countValid) * 100);

        return [
            'questionId' => $questionId,
            'nRows'      => $nRows,
            'nCols'      => $nCols,
            'answers'    => $filteredAnswers,
            'changes'    => $changes,
            'changesPct' => $changesPct,
        ];
    }

    private function countSequentialChanges(array $answers): int
    {
        $count = count($answers);
        if ($count < 2) {
            return 0;
        }
        $changes = 0;
        for ($i = 1; $i < $count; $i++) {
            if ($answers[$i] !== $answers[$i - 1]) {
                $changes++;
            }
        }
        return $changes;
    }

    // =========================================================================
    // ENDPOINT PUBBLICI
    // =========================================================================

    public function addToWhiteList(Request $request)
    {
        $text = trim($request->input('text', ''));
        if ($text === '') {
            return response()->json(['success' => false, 'message' => 'Testo vuoto o non fornito.'], 400);
        }

        $path      = public_path('json/whitelist.json');
        $list      = file_exists($path) ? (json_decode(file_get_contents($path), true) ?? []) : [];
        $lowerList = array_map('mb_strtolower', $list);

        if (!in_array(mb_strtolower($text), $lowerList, true)) {
            $list[] = $text;
            file_put_contents($path, json_encode($list, JSON_PRETTY_PRINT));
        }

        return response()->json(['success' => true]);
    }

    public function addToBlackList(Request $request)
    {
        $text = trim($request->input('text', ''));
        if ($text === '') {
            return response()->json(['success' => false, 'message' => 'Testo vuoto o non fornito.'], 400);
        }

        $path      = public_path('json/blacklist.json');
        $list      = file_exists($path) ? (json_decode(file_get_contents($path), true) ?? []) : [];
        $lowerList = array_map('mb_strtolower', $list);

        if (!in_array(mb_strtolower($text), $lowerList, true)) {
            $list[] = $text;
            file_put_contents($path, json_encode($list, JSON_PRETTY_PRINT));
        }

        return response()->json(['success' => true]);
    }

    public function saveFilter(Request $request)
    {
        $question1       = $request->input('question1');
        $operator1       = $request->input('operator1');
        $answer1         = $request->input('answer1');
        $logicalOperator = $request->input('logicalOperator');
        $question2       = $request->input('question2');
        $operator2       = $request->input('operator2');
        $answer2         = $request->input('answer2');

        Log::info("Salvataggio Filtro: ", compact(
            'question1', 'operator1', 'answer1', 'logicalOperator', 'question2', 'operator2', 'answer2'
        ));

        return response()->json(['success' => true]);
    }
}
