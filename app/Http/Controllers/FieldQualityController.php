<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PrimisApiService;
use Illuminate\Support\Facades\Log;
use App\Models\UserQuality;


class FieldQualityController extends Controller
{
    private const LOI_MAX_SECONDS           = 2700;
    private const LOI_ABSOLUTE_MIN_SECONDS          = 60;
    private const LOI_SHORT_SURVEY_THRESHOLD_SECONDS = 180; // solo sondaggi con refLOI > 3 min attivano il floor assoluto
    private const LOI_CRITICAL_SCORE_CAP   = 35;
    private const LOI_UPPER_MULTIPLIER     = 20 / 12;  // > 1.667× mediana → non-valutabile
    private const LOI_MIN_REFERENCE_GROUP           = 10;   // usato da calculateReferenceMedian (legacy, non attivo nel calcolo)
    private const LOI_REFERENCE_PRIMARY_COVERAGE    = 0.90; // soglia minima domande per coorte primaria
    private const LOI_REFERENCE_FALLBACK_COVERAGE   = 0.80; // soglia minima domande per coorte fallback
    private const LOI_REFERENCE_MIN_SAMPLE          = 10;   // campione minimo per considerare la coorte valida
    private const LOI_REFERENCE_SLOW_OUTLIER_FACTOR = 2.0;  // moltiplicatore per escludere outlier lenti

    private const QUALITY_WEIGHTS = [
        'open'  => 60,
        'scale' => 10,
        'loi'   => 30,
    ];

    private const COVERAGE_THRESHOLDS = [
        'complete' => 70,
        'partial'  => 40,
    ];

    // [ratio_minimo, risk] — risk da 0 a 100; ordinato dal meno rischioso
    private const LOI_RISK_TABLE = [
        [0.80,  0],
        [0.70, 10],
        [0.60, 20],
        [0.50, 35],
        [0.40, 50],
        [0.30, 65],
        // fallback (ratio < 0.30, LOI >= 60s): risk = 80
    ];

    // Confidence: [n_min, n_max, factor] — modula il peso effettivo del criterio Open
    private const OPEN_CONFIDENCE_TABLE = [
        [0, 0,           0.00],
        [1, 2,           0.30],
        [3, 4,           0.55],
        [5, 8,           0.80],
        [9, PHP_INT_MAX, 1.00],
    ];

    // [percentuale_cambi_massima, risk] — risk da 0 a 100; changes===0 gestito separatamente
    private const SCALE_RISK_TABLE = [
        [10.0, 75],
        [20.0, 50],
        [30.0, 25],
        // fallback (> 30%): risk = 0
    ];

    private const SCALE_AGGRAVATION_RISK = 15;

    public function index(Request $request, PrimisApiService $primis)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $prj = $request->query('prj');
        $sid = $request->query('sid');

        $directory = base_path("var/imr/fields/{$prj}/{$sid}/results");
        if (!is_dir($directory)) {
            $directory = "/var/imr/fields/{$prj}/{$sid}/results";
        }

        // 1) Parsing file .sre — singolo pass per file
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

        // Pre-calcola open rows per la view: fake prima (per IID), poi non-fake (per IID).
        // Cap a 1500 per evitare HTML/browser OOM quando ci sono molte interviste.
        $openTotalCount = count($openQuestionsData);
        $sortedOpen = $openQuestionsData;
        usort($sortedOpen, function ($a, $b) {
            $fa = !empty($a['isFake']) ? 0 : 1;
            $fb = !empty($b['isFake']) ? 0 : 1;
            return $fa !== $fb ? $fa - $fb : ((int) $a['iid'] <=> (int) $b['iid']);
        });
        $allOpenRows  = array_slice($sortedOpen, 0, 1500);
        $fakeOpenRows = array_values(array_filter($allOpenRows, fn ($r) => !empty($r['isFake'])));
        unset($sortedOpen);

        // 4) Criteri di punteggio (popolano quality_criteria e quality_risks)
        $loiMedianSec = $this->applyLoiCriterion($completeInterviews);
        $this->applyOpenQuestionsCriterion($completeInterviews, $openQuestionsData);
        $this->applyScaleChangesCriterion($completeInterviews, $scaleData);

        // 5) Score qualità finale (0-100) basato su risk ponderati
        $this->applyFinalQualityScore($completeInterviews);

        // 6) Arricchimento presentazione: stelle, etichetta qualitativa, motivazioni
        $this->applyQualityPresentationData($completeInterviews);

        // 7a) Persistenza score in t_user_quality (insert-only, skip se già presente)
        $this->persistQualityScores($completeInterviews, $prj, $sid);

        // 7) Statistiche e classificazione
        $stats          = $this->computeScoreStats($completeInterviews);
        $classification = $this->computeQualityClassification($completeInterviews);

        // 8) Estrae lookup per la view ed elimina i campi annidati pesanti da $completeInterviews
        //    (quality_criteria / quality_risks / quality_weights non servono al rendering HTML)
        $loiCriteriaByIid = [];
        $loiSecByIid      = [];
        $scaleQsByIidQid  = [];
        $loiSurveyMeta    = [
            'refQCount'  => null, 'refType'    => null, 'refFullSec' => null,
            'sampleSize' => null, 'excluded'   => null, 'coverage'   => null,
        ];

        foreach ($completeInterviews as &$iv) {
            $loi = $iv['quality_criteria']['loi'] ?? [];
            $loiCriteriaByIid[$iv['iid']] = $loi;
            $loiSecByIid[$iv['iid']]      = $iv['loiSec'];

            foreach ($iv['quality_criteria']['scale']['details'] ?? [] as $d) {
                $scaleQsByIidQid[$iv['iid']][$d['question_id']] = $d;
            }

            if ($loiSurveyMeta['refQCount'] === null && isset($loi['reference_question_count'])) {
                $loiSurveyMeta = [
                    'refQCount'  => (int)    $loi['reference_question_count'],
                    'refType'    =>          $loi['reference_type']               ?? null,
                    'refFullSec' =>          $loi['reference_full_seconds']       ?? null,
                    'sampleSize' => (int)   ($loi['reference_sample_size']        ?? 0),
                    'excluded'   => (int)   ($loi['excluded_slow_reference_cases'] ?? 0),
                    'coverage'   =>          $loi['reference_coverage']           ?? null,
                ];
            }

            unset($iv['quality_criteria'], $iv['quality_risks'], $iv['quality_weights']);
        }
        unset($iv);

        // 9) Dati DB (navbar e panel)
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
            'allOpenRows'        => $allOpenRows,
            'fakeOpenRows'       => $fakeOpenRows,
            'openTotalCount'     => $openTotalCount,
            'loiCriteriaByIid'   => $loiCriteriaByIid,
            'loiSecByIid'        => $loiSecByIid,
            'scaleQsByIidQid'    => $scaleQsByIidQid,
            'loiSurveyMeta'      => $loiSurveyMeta,
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
            $handle = fopen($file, 'r');
            if (!$handle) {
                continue;
            }

            // Prima riga: header — status, iid, uid, loiSec, panel
            $firstRaw = fgets($handle);
            if (!$firstRaw) {
                fclose($handle);
                continue;
            }

            $data   = explode(";", trim($firstRaw));
            $offset = (isset($data[0]) && $data[0] === '2.0') ? 0 : -1;

            $status = isset($data[8 + $offset]) ? (int) $data[8 + $offset] : null;
            if ($status !== 3) {
                fclose($handle);
                continue;
            }

            $iid       = $data[3 + $offset] ?? 'N/A';
            $uid       = $data[4 + $offset] ?? 'N/A';
            $loiSec    = isset($data[7 + $offset]) ? (int) $data[7 + $offset] : 0;
            $panelUsed = $this->detectPanel($data);

            // Lettura singola di tutte le righe rimanenti
            $questionIds = [];

            while (($line = fgets($handle)) !== false) {
                $trimmed = rtrim($line);
                if ($trimmed === '') {
                    continue;
                }

                $fields = explode(';', $trimmed);
                $type   = $fields[0] ?? '';

                // Raccoglie question IDs per questionsAnswered / pathSignature
                $qid = isset($fields[1]) ? trim($fields[1]) : '';
                if ($qid !== '' && ctype_digit($qid) && (int) $qid > 0) {
                    $questionIds[] = (int) $qid;
                }

                if ($type === 'open') {
                    $openResponse = $fields[2] ?? '';
                    if (!is_numeric($openResponse)) {
                        $openQuestionsData[] = [
                            'iid'          => $iid,
                            'uid'          => $uid,
                            'panel'        => $panelUsed,
                            'questionId'   => (int) $qid,
                            'openResponse' => $openResponse,
                        ];
                    }
                } elseif ($type === 'choice') {
                    $questionId = (int) ($fields[1] ?? 0);
                    if ($questionId > 0) {
                        for ($i = 5; $i < count($fields); $i++) {
                            $part = trim($fields[$i]);
                            if ($part === '') {
                                continue;
                            }
                            if (!preg_match('/^(comp(\d+)):(.+)$/i', $part, $m)) {
                                continue;
                            }
                            $answer = trim($m[3]);
                            if ($answer === '' || is_numeric($answer)) {
                                continue;
                            }
                            $openQuestionsData[] = [
                                'iid'             => $iid,
                                'uid'             => $uid,
                                'panel'           => $panelUsed,
                                'questionId'      => $questionId,
                                'openResponse'    => $answer,
                                'question_type'   => 'choice_open',
                                'component'       => $m[1],
                                'component_index' => (int) $m[2],
                            ];
                        }
                    }
                } elseif ($type === 'scale') {
                    $parsed = $this->parseScaleLine($trimmed);
                    if ($parsed !== null) {
                        $parsed['iid']   = $iid;
                        $parsed['uid']   = $uid;
                        $parsed['panel'] = $panelUsed;
                        $scaleData[]     = $parsed;
                    }
                }
            }

            fclose($handle);

            sort($questionIds);
            $uniqueIds         = array_unique($questionIds);
            $questionsAnswered = max(1, count($uniqueIds));
            $pathSignature     = implode(',', $questionIds);

            $completeInterviews[] = [
                'iid'                => $iid,
                'uid'                => $uid,
                'panel'              => $panelUsed,
                'loiSec'             => $loiSec,
                'questionsAnswered'  => $questionsAnswered,
                'pathSignature'      => $pathSignature,
                'score'              => null,
                'quality_criteria'   => [],
                'quality_risks'      => [],
                'quality_weights'    => [],
                'quality_coverage'   => [],
                'quality_risk_total' => null,
            ];

            $loiData[] = [
                'iid'               => $iid,
                'uid'               => $uid,
                'loi'               => $this->formatLoiSec($loiSec),
                'questionsAnswered' => $questionsAnswered,
            ];
        }

        return [
            'interviews'    => $completeInterviews,
            'loiData'       => $loiData,
            'openQuestions' => $openQuestionsData,
            'scaleData'     => $scaleData,
        ];
    }

    private function formatSeconds(int $seconds): string
    {
        if ($seconds <= 0) {
            return '00:00';
        }
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        if ($h > 0) {
            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        }
        return sprintf('%02d:%02d', $m, $s);
    }

    private function formatLoiSec(float $loiSec): string
    {
        return $this->formatSeconds((int) $loiSec);
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

    private function extractChoiceOpenComponents(string $filePath, string $iid, string $uid, string $panelUsed, array &$openQuestionsData): void
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return;
        }

        while (($line = fgets($handle)) !== false) {
            $line   = trim($line);
            $fields = explode(';', $line);

            if (($fields[0] ?? '') !== 'choice') {
                continue;
            }

            $questionId = isset($fields[1]) ? (int) $fields[1] : null;
            if ($questionId === null) {
                continue;
            }

            // fields[0..4]: type, questionId, ?, ?, ?
            // fields[5..]: compX:testo (posizione e numerazione non garantite)
            for ($i = 5; $i < count($fields); $i++) {
                $part = trim($fields[$i]);
                if ($part === '') {
                    continue;
                }

                if (!preg_match('/^(comp(\d+)):(.+)$/i', $part, $m)) {
                    continue;
                }

                $component      = $m[1];        // "comp0"
                $componentIndex = (int) $m[2];  // 0
                $answer         = trim($m[3]);   // "Palette occhi"

                if ($answer === '' || is_numeric($answer)) {
                    continue;
                }

                $openQuestionsData[] = [
                    'iid'             => $iid,
                    'uid'             => $uid,
                    'panel'           => $panelUsed,
                    'questionId'      => $questionId,
                    'openResponse'    => $answer,
                    'question_type'   => 'choice_open',
                    'component'       => $component,
                    'component_index' => $componentIndex,
                ];
            }
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
            $id             = $row['questionId'];
            $isChoiceOpen   = ($row['question_type'] ?? '') === 'choice_open';
            $qCode          = $questionMap[$id]['code'] ?? null;
            $qText          = $questionMap[$id]['text'] ?? 'Domanda non presente';

            if ($isChoiceOpen) {
                $row['tipologia']  = 'Specifica';
                $row['codice']     = $qCode ?? "Domanda {$id}";
                $row['tooltip']    = $qText;
            } else {
                $row['tipologia']  = 'Open';
                $row['codice']     = $qCode ?? 'unknown';
                $row['tooltip']    = $qText;
            }

            $classification   = $this->classifyOpenResponse($row['openResponse'], $whiteList, $blackList);
            $row['isFake']    = $classification['is_fake'];
            $row['is_severe'] = $classification['is_severe'];
            $row['reason']    = $classification['reason'];
            $row['category']  = $classification['category'];
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
        // Pass 1: raccogli conteggi domande e interviste eligible per la coorte normalizzata.
        $allQCounts     = [];
        $eligibleForRef = []; // 60s <= LOI < 2700s e q > 0

        foreach ($interviews as $iv) {
            $q      = (int) ($iv['questionsAnswered'] ?? 0);
            $loiSec = (int) $iv['loiSec'];
            if ($q > 0) {
                $allQCounts[] = $q;
            }
            if ($q > 0 && $loiSec >= self::LOI_ABSOLUTE_MIN_SECONDS && $loiSec < self::LOI_MAX_SECONDS) {
                $eligibleForRef[] = ['iid' => $iv['iid'], 'q' => $q, 'loi' => (float) $loiSec];
            }
        }

        $refQCount           = $this->calculateReferenceQuestionCount($allQCounts);
        $medianQuestionCount = count($allQCounts) > 0
            ? $this->calculateMedian(array_map('floatval', $allQCounts))
            : 1.0;
        $cohortResult        = $this->buildAndCalculateNormalizedReference($eligibleForRef, $refQCount);
        $refFullLoi          = $cohortResult['reference_full_loi'];

        // Pass 2: score ogni intervista.
        foreach ($interviews as &$iv) {
            $loiSec = (int) $iv['loiSec'];
            $q      = (int) ($iv['questionsAnswered'] ?? 0);
            $absMin = $loiSec > 0
                && $loiSec < self::LOI_ABSOLUTE_MIN_SECONDS
                && $refFullLoi > self::LOI_SHORT_SURVEY_THRESHOLD_SECONDS;

            $baseLoi = [
                'seconds'                            => $loiSec,
                'question_count'                     => $q,
                'reference_question_count'           => $refQCount,
                'reference_type'                     => $cohortResult['reference_type'],
                'reference_coverage'                 => $cohortResult['coverage'],
                'reference_min_questions'            => $cohortResult['min_questions'],
                'reference_sample_size'              => $cohortResult['sample_size'],
                'preliminary_reference_full_seconds' => $cohortResult['preliminary_median'],
                'reference_full_seconds'             => $refFullLoi,
                'excluded_slow_reference_cases'      => $cohortResult['excluded'],
                'absolute_minimum_triggered'         => $absMin,
                // Alias legacy — non usati per il calcolo, mantenuti per backward compat
                'questions_answered'                 => $q,
                'max_q'                              => $refQCount,
                'display_max_q'                      => $refQCount,
                'median_question_count'              => (int) round($medianQuestionCount),
                'global_median_seconds'              => (int) round($refFullLoi),
                'ref_source'                         => $cohortResult['reference_type'] ?? 'proportional',
            ];

            // Domande non disponibili
            if ($q <= 0) {
                $iv['quality_criteria']['loi'] = array_merge($baseLoi, [
                    'available'          => false,
                    'expected_seconds'   => null,
                    'reference_median'   => null,
                    'ratio'              => null,
                    'risk'               => null,
                    'evaluation'         => 'not_evaluable',
                    'evaluation_label'   => 'Non valutabile',
                    'unavailable_reason' => 'invalid_question_count',
                ]);
                continue;
            }

            // LOI non valida
            if ($loiSec <= 0) {
                $iv['quality_criteria']['loi'] = array_merge($baseLoi, [
                    'available'          => false,
                    'expected_seconds'   => null,
                    'reference_median'   => null,
                    'ratio'              => null,
                    'risk'               => null,
                    'evaluation'         => 'not_evaluable',
                    'evaluation_label'   => 'Non valutabile',
                    'unavailable_reason' => 'invalid_loi',
                ]);
                continue;
            }

            // Campione di riferimento insufficiente
            if ($cohortResult['reference_type'] === 'insufficient_reference_sample' || $refFullLoi <= 0.0) {
                $iv['quality_criteria']['loi'] = array_merge($baseLoi, [
                    'available'          => false,
                    'expected_seconds'   => null,
                    'reference_median'   => null,
                    'ratio'              => null,
                    'risk'               => null,
                    'evaluation'         => 'not_evaluable',
                    'evaluation_label'   => 'Non valutabile',
                    'unavailable_reason' => 'insufficient_reference_sample',
                ]);
                continue;
            }

            $expectedLoi = $this->calculateExpectedLoi($refFullLoi, $q, $refQCount);

            if ($expectedLoi <= 0.0) {
                $iv['quality_criteria']['loi'] = array_merge($baseLoi, [
                    'available'          => false,
                    'expected_seconds'   => null,
                    'reference_median'   => null,
                    'ratio'              => null,
                    'risk'               => null,
                    'evaluation'         => 'not_evaluable',
                    'evaluation_label'   => 'Non valutabile',
                    'unavailable_reason' => 'invalid_reference',
                ]);
                continue;
            }

            // LOI sotto il minimo assoluto (< 60s): disponibile ma risk = 100
            if ($absMin) {
                $ratio = $loiSec / $expectedLoi;
                $iv['quality_criteria']['loi'] = array_merge($baseLoi, [
                    'available'          => true,
                    'expected_seconds'   => round($expectedLoi, 1),
                    'reference_median'   => (int) round($expectedLoi),
                    'ratio'              => round($ratio, 4),
                    'risk'               => 100,
                    'evaluation'         => 'verify',
                    'evaluation_label'   => 'Da verificare',
                    'unavailable_reason' => null,
                ]);
                $iv['quality_risks']['loi'] = 100;
                continue;
            }

            $ratio = $loiSec / $expectedLoi;

            // LOI eccessivamente lenta (ratio > 1.667): non valutabile
            if ($ratio > self::LOI_UPPER_MULTIPLIER) {
                $iv['quality_criteria']['loi'] = array_merge($baseLoi, [
                    'available'          => false,
                    'too_slow'           => true,
                    'expected_seconds'   => round($expectedLoi, 1),
                    'reference_median'   => (int) round($expectedLoi),
                    'ratio'              => round($ratio, 4),
                    'risk'               => null,
                    'evaluation'         => 'not_evaluable',
                    'evaluation_label'   => 'Non valutabile',
                    'unavailable_reason' => 'excessively_slow',
                ]);
                continue;
            }

            $risk = 80;
            foreach (self::LOI_RISK_TABLE as [$minRatio, $tableRisk]) {
                if ($ratio >= $minRatio) {
                    $risk = $tableRisk;
                    break;
                }
            }
            $risk = (int) min(100, max(0, $risk));
            $eval = $this->calculateLoiEvaluation($ratio);

            $iv['quality_criteria']['loi'] = array_merge($baseLoi, [
                'available'          => true,
                'expected_seconds'   => round($expectedLoi, 1),
                'reference_median'   => (int) round($expectedLoi),
                'ratio'              => round($ratio, 4),
                'risk'               => $risk,
                'evaluation'         => $eval['evaluation'],
                'evaluation_label'   => $eval['evaluation_label'],
                'unavailable_reason' => null,
            ]);
            $iv['quality_risks']['loi'] = $risk;
        }
        unset($iv);

        return $refFullLoi;
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

    // =========================================================================
    // LOI — FUNZIONI DI RIFERIMENTO NORMALIZZATO
    // =========================================================================

    // P95 dei questionCount validi → usato come referenceQuestionCount.
    private function calculateReferenceQuestionCount(array $qCounts): int
    {
        if (empty($qCounts)) {
            return 1;
        }
        $sorted = $qCounts;
        sort($sorted);
        $p95idx = max(0, (int) ceil(count($sorted) * 0.95) - 1);
        return max(1, (int) $sorted[$p95idx]);
    }

    // Seleziona le interviste della coorte con questionCount >= referenceQuestionCount × coverage.
    private function buildNormalizedLoiCohort(array $eligible, int $refQCount, float $coverage): array
    {
        $minQ     = max(1, (int) floor($refQCount * $coverage));
        $filtered = [];
        foreach ($eligible as $e) {
            if ($e['q'] >= $minQ) {
                $filtered[] = $e;
            }
        }
        return $filtered;
    }

    // Calcola la mediana LOI normalizzata della coorte con pulizia outlier lenti.
    // normalizedFullLoi = actualLoi × refQCount / questionCount
    private function calculateNormalizedReferenceLoi(array $cohort, int $refQCount): array
    {
        if (empty($cohort) || $refQCount <= 0) {
            return ['reference_full_loi' => 0.0, 'preliminary_median' => 0.0, 'excluded' => 0, 'sample_size' => 0];
        }

        $normalized = [];
        foreach ($cohort as $e) {
            if ($e['q'] > 0) {
                $normalized[] = $e['loi'] * $refQCount / $e['q'];
            }
        }

        if (empty($normalized)) {
            return ['reference_full_loi' => 0.0, 'preliminary_median' => 0.0, 'excluded' => 0, 'sample_size' => 0];
        }

        $prelimMedian = $this->calculateMedian($normalized);
        $threshold    = $prelimMedian * self::LOI_REFERENCE_SLOW_OUTLIER_FACTOR;
        $cleaned      = [];
        $excluded     = 0;
        foreach ($normalized as $v) {
            if ($v <= $threshold) {
                $cleaned[] = $v;
            } else {
                $excluded++;
            }
        }

        if (empty($cleaned)) {
            return ['reference_full_loi' => 0.0, 'preliminary_median' => $prelimMedian, 'excluded' => $excluded, 'sample_size' => 0];
        }

        return [
            'reference_full_loi' => $this->calculateMedian($cleaned),
            'preliminary_median' => $prelimMedian,
            'excluded'           => $excluded,
            'sample_size'        => count($cleaned),
        ];
    }

    // Tenta la coorte al 90%, poi all'80%, poi dichiara campione insufficiente.
    private function buildAndCalculateNormalizedReference(array $eligible, int $refQCount): array
    {
        $attempts = [
            ['coverage' => self::LOI_REFERENCE_PRIMARY_COVERAGE,  'type' => 'normalized_cohort_90'],
            ['coverage' => self::LOI_REFERENCE_FALLBACK_COVERAGE, 'type' => 'normalized_cohort_80'],
        ];

        foreach ($attempts as $attempt) {
            $cohort = $this->buildNormalizedLoiCohort($eligible, $refQCount, $attempt['coverage']);
            $calc   = $this->calculateNormalizedReferenceLoi($cohort, $refQCount);
            if ($calc['sample_size'] >= self::LOI_REFERENCE_MIN_SAMPLE) {
                $minQ = max(1, (int) floor($refQCount * $attempt['coverage']));
                return array_merge($calc, [
                    'reference_type' => $attempt['type'],
                    'coverage'       => $attempt['coverage'],
                    'min_questions'  => $minQ,
                ]);
            }
        }

        return [
            'reference_full_loi' => 0.0,
            'preliminary_median' => 0.0,
            'excluded'           => 0,
            'sample_size'        => 0,
            'reference_type'     => 'insufficient_reference_sample',
            'coverage'           => null,
            'min_questions'      => null,
        ];
    }

    // LOI attesa proporzionale al numero di domande.
    private function calculateExpectedLoi(float $refFullLoi, int $qCount, int $refQCount): float
    {
        if ($refQCount <= 0 || $qCount <= 0 || $refFullLoi <= 0.0) {
            return 0.0;
        }
        return $refFullLoi * $qCount / $refQCount;
    }

    // Valutazione sintetica basata sul ratio (non arrotondato).
    private function calculateLoiEvaluation(float $ratio): array
    {
        if ($ratio >= 0.70) {
            return ['evaluation' => 'ok',         'evaluation_label' => 'OK'];
        }
        if ($ratio >= 0.50) {
            return ['evaluation' => 'suspicious', 'evaluation_label' => 'Sospetta'];
        }
        return ['evaluation' => 'verify',     'evaluation_label' => 'Da verificare'];
    }

    private function calculateOpenRisk(float $fakePercentage): int
    {
        $pct = max(0.0, min(100.0, $fakePercentage));

        if ($pct <= 0.0)  { return 0; }
        if ($pct <= 20.0) { return 20; }
        if ($pct <= 40.0) { return 45; }
        if ($pct <= 60.0) { return 70; }
        if ($pct <= 80.0) { return 90; }
        return 100;
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
            $iid     = $iv['iid'];
            $answers = $openByInterview[$iid] ?? [];

            if (count($answers) === 0) {
                $iv['quality_criteria']['open'] = ['available' => false];
                continue;
            }

            $fakeCount    = 0;
            $correctCount = 0;
            $hasSevere    = false;

            foreach ($answers as $a) {
                if (!empty($a['isFake'])) {
                    $fakeCount++;
                    if (!empty($a['is_severe'])) {
                        $hasSevere = true;
                    }
                } else {
                    $correctCount++;
                }
            }

            $totalAnswers   = $correctCount + $fakeCount;
            $fakePercentage = $totalAnswers > 0
                ? round(($fakeCount / $totalAnswers) * 100, 2)
                : 0.0;
            $openRisk = $this->calculateOpenRisk($fakePercentage);

            // Confidence: modula il peso del criterio Open nel punteggio totale
            $confidenceFactor = 0.0;
            $confidenceLevel  = 'nessuna';
            foreach (self::OPEN_CONFIDENCE_TABLE as [$nMin, $nMax, $factor]) {
                if ($totalAnswers >= $nMin && $totalAnswers <= $nMax) {
                    $confidenceFactor = $factor;
                    $confidenceLevel  = $factor >= 1.0 ? 'alta'
                        : ($factor >= 0.80 ? 'sufficiente'
                        : ($factor >= 0.55 ? 'bassa'
                        : ($factor >= 0.30 ? 'limitata' : 'nessuna')));
                    break;
                }
            }
            $effectiveWeight = (int) round(self::QUALITY_WEIGHTS['open'] * $confidenceFactor);

            $iv['quality_criteria']['open'] = [
                'available'         => true,
                'analyzed_answers'  => $totalAnswers,
                'correct_count'     => $correctCount,
                'fake_answers'      => $fakeCount,
                'fake_percentage'   => $fakePercentage,
                'severe_fake'       => $hasSevere,
                'risk'              => $openRisk,
                'confidence_level'  => $confidenceLevel,
                'confidence_factor' => $confidenceFactor,
                'effective_weight'  => $effectiveWeight,
            ];
            $iv['quality_risks']['open'] = $openRisk;
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

                // Vecchio risk (mantenuto per compatibilità con view e punteggio totale)
                if ($changes === 0) {
                    $gRisk = 100;
                } elseif ($changePct > 30.0) {
                    $gRisk = 0;
                } else {
                    $gRisk = 25;
                    foreach (self::SCALE_RISK_TABLE as [$maxPct, $tableRisk]) {
                        if ($changePct <= $maxPct) {
                            $gRisk = $tableRisk;
                            break;
                        }
                    }
                }

                $gridScore = $this->scoreScaleGrid($scale['answers']);

                $details[] = [
                    'question_id'       => $scale['questionId'],
                    'question_code'     => $scale['code'] ?? null,
                    'valid_answers'     => $validAnswers,
                    'possible_changes'  => $possibleChanges,
                    'changes'           => $changes,
                    'change_percentage' => $changePct,
                    'risk'              => $gRisk,
                    'level'             => $gridScore['level'],
                    'quality_score'     => $gridScore['quality_score'],
                    'reasons'           => $gridScore['reasons'],
                ];
            }

            $analyzedCount  = count($details);
            $baseRisk       = array_sum(array_column($details, 'risk')) / $analyzedCount;
            $lowChangeCount = count(array_filter($details, fn($d) => $d['change_percentage'] <= 10.0));
            $aggravation    = ($lowChangeCount / $analyzedCount >= 0.5) ? self::SCALE_AGGRAVATION_RISK : 0;

            // Nuovo risk aggregato Scale
            $gridRisksArr = [];
            foreach ($details as $d) {
                if (isset($d['quality_score']) && is_int($d['quality_score'])) {
                    $gridRisksArr[] = 100 - max(0, min(100, $d['quality_score']));
                }
            }
            $nValid      = count($gridRisksArr);
            $averageRisk = $nValid > 0 ? round(array_sum($gridRisksArr) / $nValid, 2) : 0.0;
            $worstRisk   = $nValid > 0 ? max($gridRisksArr) : 0;
            $newScaleRisk = $this->calculateAggregateScaleRisk($details);

            // aggregate_level mantenuto per compatibilità view (non usato nel punteggio)
            $levelPriority  = ['Normale' => 0, 'Sospetta' => 1, 'Da Verificare' => 2];
            $aggregateLevel = 'Normale';
            foreach (array_column($details, 'level') as $lvl) {
                if ($levelPriority[$lvl] > $levelPriority[$aggregateLevel]) {
                    $aggregateLevel = $lvl;
                }
            }
            $allGridReasons = array_values(array_unique(array_merge(...array_column($details, 'reasons'))));

            $iv['quality_criteria']['scale'] = [
                'available'       => true,
                'analyzed_scales' => $analyzedCount,
                'average_risk'    => $averageRisk,
                'worst_risk'      => $worstRisk,
                'risk'            => $newScaleRisk,
                // legacy — letti da buildQualityReasons():
                'critical_scales'    => $lowChangeCount,
                'repeat_aggravation' => $aggravation,
                'base_risk'          => round($baseRisk, 1),
                // legacy — compatibilità view:
                'aggregate_level'    => $aggregateLevel,
                'aggregate_reasons'  => $allGridReasons,
                'details'            => $details,
            ];
            $iv['quality_risks']['scale'] = $newScaleRisk;
        }
        unset($iv);
    }

    private function calculateAggregateScaleRisk(array $gridDetails): int
    {
        $gridRisks = [];
        foreach ($gridDetails as $d) {
            if (!isset($d['quality_score']) || !is_int($d['quality_score'])) {
                continue;
            }
            $gridRisks[] = 100 - max(0, min(100, $d['quality_score']));
        }

        if (empty($gridRisks)) {
            return 0;
        }

        $n           = count($gridRisks);
        $averageRisk = array_sum($gridRisks) / $n;
        $worstRisk   = max($gridRisks);
        $scaleRisk   = $averageRisk * 0.70 + $worstRisk * 0.30;

        return (int) min(100, max(0, round($scaleRisk)));
    }

    private function scoreScaleGrid(array $answers): array
    {
        $n = count($answers);

        $valueCounts  = array_count_values($answers);
        $dominantFrac = max($valueCounts) / $n;

        if ($dominantFrac >= 1.0) {
            return ['level' => 'Da Verificare', 'quality_score' => 0, 'reasons' => ['Risposte tutte uguali']];
        }

        // Pattern ciclico: blocco len 2/3/4, minimo 4 ripetizioni consecutive
        foreach ([2, 3, 4] as $len) {
            if ($n < $len * 4) {
                continue;
            }
            $block = array_slice($answers, 0, $len);
            $reps  = 0;
            for ($i = 0; $i + $len <= $n; $i += $len) {
                if (array_slice($answers, $i, $len) === $block) {
                    $reps++;
                } else {
                    break;
                }
            }
            $coverage = ($reps * $len) / $n;
            if ($reps >= 4 && $coverage >= 0.75) {
                return ['level' => 'Sospetta', 'quality_score' => 50, 'reasons' => ['Pattern ciclico ripetuto']];
            }
        }

        return ['level' => 'Normale', 'quality_score' => 100, 'reasons' => []];
    }

    private function applyFinalQualityScore(array &$completeInterviews): void
    {
        $criteria = ['open', 'scale', 'loi'];

        foreach ($completeInterviews as &$iv) {
            $numerator       = 0;
            $availableWeight = 0;

            foreach ($criteria as $key) {
                if (!empty($iv['quality_criteria'][$key]['available'])) {
                    $risk   = min(100.0, max(0.0, (float) ($iv['quality_risks'][$key] ?? 0)));
                    // Il criterio Open usa il peso effettivo modulato dalla Confidence
                    $weight = ($key === 'open')
                        ? (int) ($iv['quality_criteria']['open']['effective_weight'] ?? self::QUALITY_WEIGHTS['open'])
                        : self::QUALITY_WEIGHTS[$key];
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
                $iv['quality_score_caps'] = [
                    'applied'                 => false,
                    'base_score'              => null,
                    'final_score'             => null,
                    'effective_maximum_score' => null,
                    'rules'                   => [],
                ];
                $iv['quality_score_cap'] = [
                    'applied'       => false,
                    'maximum_score' => null,
                    'reason'        => null,
                ];
            } else {
                $totalRisk                = (int) round($numerator / $availableWeight);
                $baseScore                = max(0, min(100, 100 - $totalRisk));
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

                $caps                     = $this->calculateQualityScoreCaps($iv, $baseScore);
                $iv['score']              = $caps['final_score'];
                $iv['quality_score_caps'] = $caps;

                // Alias backward-compatible — punta al cap più restrittivo
                $effectiveRule = null;
                foreach ($caps['rules'] as $r) {
                    if ($effectiveRule === null || $r['maximum_score'] < $effectiveRule['maximum_score']) {
                        $effectiveRule = $r;
                    }
                }
                $iv['quality_score_cap'] = [
                    'applied'       => $caps['applied'],
                    'maximum_score' => $caps['effective_maximum_score'],
                    'reason'        => $effectiveRule !== null ? $effectiveRule['reason'] : null,
                ];
            }
        }
        unset($iv);
    }

    private function calculateQualityScoreCaps(array $interview, int $baseScore): array
    {
        $open  = $interview['quality_criteria']['open']  ?? null;
        $scale = $interview['quality_criteria']['scale'] ?? null;
        $loi   = $interview['quality_criteria']['loi']   ?? null;

        $openAvail  = !empty($open['available']);
        $scaleAvail = !empty($scale['available']);
        $loiAvail   = !empty($loi['available']);

        $rules = [];

        // 1. Cap Open per percentuale di fake estrema
        // Modello bayesiano (FPR=0.20, P(bad)=0.15):
        //   n=1 → P(bad|all_fake)=47% → cap 30 (incertezza alta)
        //   n=2 → P(bad|all_fake)=82% → cap 15 (buona evidenza)
        //   n=3 → P(bad|all_fake)=96% → cap  5 (quasi certo)
        //   n≥4 → P(bad|all_fake)>99% → cap  0 (certo)
        if ($openAvail) {
            $analyzedAnswers = (int) ($open['analyzed_answers'] ?? 0);
            $fakeAnswers     = (int) ($open['fake_answers']     ?? 0);
            $fakePct         = (float) ($open['fake_percentage'] ?? 0.0);

            if ($analyzedAnswers >= 1 && $fakeAnswers >= $analyzedAnswers) {
                // Tutte fake: cap graduato per numero di risposte analizzate
                if ($analyzedAnswers >= 4) {
                    $rules[] = ['reason' => 'open_all_fake',         'maximum_score' =>  0];
                } elseif ($analyzedAnswers === 3) {
                    $rules[] = ['reason' => 'open_all_fake_few',     'maximum_score' =>  5];
                } elseif ($analyzedAnswers === 2) {
                    $rules[] = ['reason' => 'open_all_fake_minimal', 'maximum_score' => 15];
                } else {
                    $rules[] = ['reason' => 'open_all_fake_single',  'maximum_score' => 30];
                }
            } elseif ($analyzedAnswers >= 5) {
                // Cap per percentuale alta di fake (solo con campione sufficiente)
                if ($fakePct >= 80.0) {
                    $rules[] = ['reason' => 'open_fake_percentage_80', 'maximum_score' => 15];
                } elseif ($fakePct >= 60.0) {
                    $rules[] = ['reason' => 'open_fake_percentage_60', 'maximum_score' => 30];
                } elseif ($fakePct >= 40.0) {
                    $rules[] = ['reason' => 'open_fake_percentage_40', 'maximum_score' => 45];
                }
            }
        }

        // 2. Cap Scale per griglie con quality_score = 0
        if ($scaleAvail) {
            $details       = $scale['details'] ?? [];
            $zeroQualCount = 0;
            foreach ($details as $d) {
                $qs = isset($d['quality_score']) ? (int) $d['quality_score'] : -1;
                if ($qs === 0) {
                    $zeroQualCount++;
                }
            }

            if ($zeroQualCount >= 3) {
                $rules[] = ['reason' => 'three_or_more_zero_quality_scales', 'maximum_score' => 40];
            } elseif ($zeroQualCount >= 2) {
                $rules[] = ['reason' => 'multiple_zero_quality_scales', 'maximum_score' => 55];
            }
        }

        // 3. Cap progressivo LOI ratio + LOI assoluto
        if ($loiAvail) {
            $ratio  = isset($loi['ratio']) ? (float) $loi['ratio'] : null;
            $absMin = !empty($loi['absolute_minimum_triggered']);

            if ($ratio !== null) {
                if ($ratio <= 0.40) {
                    $rules[] = ['reason' => 'loi_ratio_below_040', 'maximum_score' => 45];
                } elseif ($ratio <= 0.50) {
                    $rules[] = ['reason' => 'loi_ratio_below_050', 'maximum_score' => 60];
                } elseif ($ratio <= 0.60) {
                    $rules[] = ['reason' => 'loi_ratio_below_060', 'maximum_score' => 75];
                }
            }

            if ($absMin) {
                $rules[] = ['reason' => 'loi_below_absolute_minimum', 'maximum_score' => self::LOI_CRITICAL_SCORE_CAP];
            }
        }

        // 4. Attivazione multi-criterio
        $openActive  = false;
        $loiActive   = false;
        $scaleActive = false;

        if ($openAvail) {
            $analyzedAnswers = (int) ($open['analyzed_answers'] ?? 0);
            $fakeAnswers     = (int) ($open['fake_answers']     ?? 0);
            $openRisk        = (int) ($open['risk']             ?? 0);

            $openActive = ($analyzedAnswers >= 5)
                ? $fakeAnswers >= 1
                : $openRisk >= 70;
        }

        if ($loiAvail) {
            $loiActive = (int) ($loi['risk'] ?? 0) >= 20;
        }

        if ($scaleAvail) {
            $details = $scale['details'] ?? [];
            foreach ($details as $d) {
                $level = $d['level'] ?? '';
                if ($level === 'Sospetta' || $level === 'Da Verificare') {
                    $scaleActive = true;
                    break;
                }
            }
        }

        $activeCriteria = ($openActive ? 1 : 0) + ($loiActive ? 1 : 0) + ($scaleActive ? 1 : 0);

        if ($activeCriteria >= 3) {
            $rules[] = ['reason' => 'all_anomalous_criteria', 'maximum_score' => 39];
        } elseif ($activeCriteria >= 2) {
            $rules[] = ['reason' => 'multiple_anomalous_criteria', 'maximum_score' => 59];
        }

        // 5. Combinazione grave Open + LOI
        if ($openAvail && $loiAvail) {
            $openRisk = (int) ($open['risk'] ?? 0);
            $loiRisk  = (int) ($loi['risk']  ?? 0);
            if ($openRisk >= 70 && $loiRisk >= 65) {
                $rules[] = ['reason' => 'severe_open_and_loi', 'maximum_score' => 25];
            }
        }

        // Calcola cap effettivo (il più restrittivo)
        if (empty($rules)) {
            return [
                'applied'                 => false,
                'base_score'              => $baseScore,
                'final_score'             => $baseScore,
                'effective_maximum_score' => null,
                'rules'                   => [],
            ];
        }

        $effectiveMax = PHP_INT_MAX;
        foreach ($rules as $r) {
            if ($r['maximum_score'] < $effectiveMax) {
                $effectiveMax = $r['maximum_score'];
            }
        }

        $finalScore = min($baseScore, $effectiveMax);

        return [
            'applied'                 => $finalScore < $baseScore,  // true solo se il cap ha davvero ridotto lo score
            'base_score'              => $baseScore,
            'final_score'             => $finalScore,
            'effective_maximum_score' => $effectiveMax,
            'rules'                   => $rules,
        ];
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

            $iv['quality_reasons']    = $this->buildQualityReasons($iv);
            $iv['quality_motivation'] = $this->buildMotivationLines($iv);
        }
        unset($iv);
    }

    private function buildMotivationLines(array $interview): array
    {
        $open  = $interview['quality_criteria']['open']  ?? null;
        $scale = $interview['quality_criteria']['scale'] ?? null;
        $loi   = $interview['quality_criteria']['loi']   ?? null;

        $lines = ['open' => null, 'scale' => null, 'loi' => null];

        // ---- Domande aperte ----
        if (!empty($open['available'])) {
            $fakeCount  = (int)   ($open['fake_answers']      ?? 0);
            $total      = (int)   ($open['analyzed_answers']  ?? 0);
            $risk       = (int)   ($open['risk']              ?? 0);
            $confFactor = (float) ($open['confidence_factor'] ?? 1.0);
            $lowConf    = $confFactor < 0.55;
            $rFake      = "{$fakeCount}/{$total}";

            if ($risk === 0) {
                $lines['open'] = $lowConf
                    ? 'Nessuna anomalia nelle aperte (campione ridotto)'
                    : 'Risposte aperte nella norma';
            } elseif ($risk <= 20) {
                $parola = $fakeCount === 1 ? 'risposta aperta sospetta' : 'risposte aperte sospette';
                $lines['open'] = $lowConf
                    ? 'Un lieve segnale nelle risposte aperte (pochi dati disponibili)'
                    : "{$fakeCount} {$parola} su {$total} — segnale lieve";
            } elseif ($risk <= 45) {
                $parola = $fakeCount === 1 ? 'risposta aperta poco attendibile' : 'risposte aperte poco attendibili';
                $lines['open'] = $lowConf
                    ? 'Alcune risposte aperte sospette, ma dati limitati'
                    : "{$fakeCount} {$parola} su {$total}";
            } elseif ($risk <= 70) {
                $lines['open'] = "Le risposte aperte risultano spesso inattendibili ({$rFake} sospette)";
            } elseif ($risk <= 90) {
                $lines['open'] = "La maggior parte delle risposte aperte è inattendibile ({$rFake})";
            } else {
                $lines['open'] = $fakeCount >= $total
                    ? "Tutte le {$total} risposte aperte risultano false"
                    : "Quasi tutte le risposte aperte sono inattendibili ({$rFake})";
            }
        }

        // ---- Griglie ----
        if (!empty($scale['available'])) {
            $details  = $scale['details'] ?? [];
            $analyzed = (int) ($scale['analyzed_scales'] ?? count($details));
            $countDv  = 0;
            $countSo  = 0;
            foreach ($details as $d) {
                $level = $d['level'] ?? '';
                if ($level === 'Da Verificare') {
                    $countDv++;
                } elseif ($level === 'Sospetta') {
                    $countSo++;
                }
            }

            if ($countDv === 0 && $countSo === 0) {
                $lines['scale'] = 'Le griglie non presentano anomalie';
            } elseif ($countDv > 0 && $countSo === 0) {
                $parola = $countDv === 1 ? 'griglia con risposte tutte uguali' : 'griglie con risposte tutte uguali';
                $nota   = $countDv >= 3 ? ' — comportamento meccanico' : ' — sospetto';
                $lines['scale'] = "{$countDv} {$parola} su {$analyzed}{$nota}";
            } elseif ($countSo > 0 && $countDv === 0) {
                $parola = $countSo === 1 ? 'griglia con pattern ciclico' : 'griglie con pattern ciclico ripetuto';
                $lines['scale'] = "{$countSo} {$parola} su {$analyzed}";
            } else {
                $totAnom = $countDv + $countSo;
                $lines['scale'] = "{$totAnom} griglie anomale su {$analyzed}: {$countDv} uniformi, {$countSo} con pattern ciclico";
            }
        }

        // ---- LOI ----
        if (!empty($loi['available'])) {
            $risk   = (int)   ($loi['risk']  ?? 0);
            $ratio  = (float) ($loi['ratio'] ?? 1.0);
            $absMin = !empty($loi['absolute_minimum_triggered']);
            $pct    = (int) round($ratio * 100);
            $q      = (int) ($loi['questions_answered'] ?? 0);
            $suffix = $q > 0 ? " ({$q} dom.)" : '';

            if ($absMin) {
                $lines['loi'] = "Intervista completata in meno di 60 secondi — altamente sospetto";
            } elseif ($risk === 0) {
                $lines['loi'] = "Durata nella norma rispetto al riferimento normalizzato{$suffix}";
            } elseif ($risk <= 10) {
                $lines['loi'] = "Leggermente rapida rispetto al riferimento normalizzato{$suffix} — {$pct}% dell'atteso";
            } elseif ($risk <= 20) {
                $lines['loi'] = "Un po' rapida rispetto al riferimento normalizzato{$suffix} — {$pct}% dell'atteso";
            } elseif ($risk <= 35) {
                $lines['loi'] = "LOI inferiore all'atteso{$suffix} — {$pct}%";
            } elseif ($risk <= 50) {
                $lines['loi'] = "LOI piuttosto bassa rispetto all'atteso{$suffix} — {$pct}% dell'atteso";
            } elseif ($risk <= 65) {
                $lines['loi'] = "LOI decisamente bassa rispetto all'atteso{$suffix} — al {$pct}% dell'atteso";
            } else {
                $lines['loi'] = "LOI estremamente bassa rispetto all'atteso{$suffix} — al {$pct}% dell'atteso";
            }
        }

        return $lines;
    }

    private function persistQualityScores(array $interviews, string $prj, string $sid): void
    {
        if (empty($interviews) || empty($prj) || empty($sid)) {
            return;
        }

        $now  = now()->toDateTimeString();
        $rows = [];

        foreach ($interviews as $iv) {
            $iid   = (string) ($iv['iid'] ?? '');
            $score = $iv['score'] ?? null;

            if ($iid === '' || $score === null) {
                continue;
            }

            if (($iv['panel'] ?? '') !== 'Interactive') {
                continue;
            }

            $tier = $score >= 70 ? 'alta' : ($score >= 50 ? 'accettabile' : 'bassa');

            $rows[] = [
                'prj'                => $prj,
                'sid'                => $sid,
                'iid'                => $iid,
                'uid'                => (string) ($iv['uid'] ?? ''),
                'panel'              => $iv['panel'] ?? null,
                'quality_score'      => (int) $score,
                'quality_tier'       => $tier,
                'quality_risk_total' => $iv['quality_risk_total'] ?? null,
                'cap_applied'        => !empty($iv['quality_score_caps']['applied']) ? 1 : 0,
                'computed_at'        => $now,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            UserQuality::upsert(
                $chunk,
                ['prj', 'sid', 'iid'],
                ['quality_score', 'quality_tier', 'quality_risk_total', 'cap_applied', 'updated_at']
            );
        }
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
            $fakeCount = (int) ($open['fake_answers']    ?? 0);
            $total     = (int) ($open['analyzed_answers'] ?? 0);
            if ($fakeCount > 0) {
                $parola    = $fakeCount === 1 ? 'risposta aperta sospetta' : 'risposte aperte sospette';
                $reasons[] = "{$fakeCount} {$parola} su {$total} analizzate";
            }
        }

        // Griglie
        if ($scale && !empty($scale['available'])) {
            $analyzed = (int) ($scale['analyzed_scales'] ?? 0);
            $details  = $scale['details'] ?? [];

            $countDv = count(array_filter($details, fn($d) => ($d['level'] ?? '') === 'Da Verificare'));
            $countSo = count(array_filter($details, fn($d) => ($d['level'] ?? '') === 'Sospetta'));

            if ($countDv > 0) {
                $parola    = $countDv === 1 ? 'griglia' : 'griglie';
                $reasons[] = "{$countDv} {$parola} da verificare su {$analyzed} (risposte tutte uguali)";
            }
            if ($countSo > 0) {
                $parola    = $countSo === 1 ? 'griglia sospetta' : 'griglie sospette';
                $reasons[] = "{$countSo} {$parola} su {$analyzed} (pattern ciclico)";
            }
        }

        // LOI
        if ($loi && !empty($loi['available']) && ($loi['risk'] ?? 0) > 0) {
            if (!empty($loi['absolute_minimum_triggered'])) {
                $reasons[] = 'Intervista completata in meno di 60 secondi';
            } else {
                $pct       = (int) round(($loi['ratio'] ?? 0) * 100);
                $q         = (int) ($loi['questions_answered'] ?? 0);
                $evalLabel = $loi['evaluation_label'] ?? null;
                $loiMsg    = "LOI al {$pct}% dell'atteso" . ($q > 0 ? " ({$q} dom.)" : '');
                if ($evalLabel !== null && $evalLabel !== 'OK') {
                    $loiMsg .= " — {$evalLabel}";
                }
                $reasons[] = $loiMsg;
            }
        }

        if (!empty($interview['quality_score_cap']['applied'])) {
            $cap       = $interview['quality_score_cap']['maximum_score'];
            $reasons[] = "Score limitato a {$cap}/100 (LOI inferiore al minuto)";
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
            return ['is_fake' => false, 'is_severe' => false, 'reason' => null, 'category' => null];
        }

        // 2. Blacklist → fake grave (match esplicito su risposta nota come spazzatura)
        if (in_array($respLower, $blackListLower, true)) {
            return ['is_fake' => true, 'is_severe' => true, 'reason' => 'blacklist', 'category' => 'strong'];
        }

        // 3. Troppo corta → fake debole (potrebbe essere "ok", "sì", ecc.)
        if (mb_strlen($respTrim) < 3) {
            return ['is_fake' => true, 'is_severe' => false, 'reason' => 'too_short', 'category' => 'weak'];
        }

        // 4. Contiene URL o dominio reale → fake debole
        // Richiede un prefisso alfanumerico prima del TLD per evitare FP su testo inglese
        // ("I did it. It was" non ha .[tld] preceduto da chars di dominio validi)
        if (preg_match('/https?:\/\/|www\.|\b[a-zA-Z0-9][a-zA-Z0-9-]*\.(com|it)(\/|\s|$)/i', $respTrim)) {
            return ['is_fake' => true, 'is_severe' => false, 'reason' => 'contains_url', 'category' => 'weak'];
        }

        // 5. Caratteri ripetuti in eccesso → fake forte ("aaaaaaa", "!!!!!!")
        if ($this->hasExcessRepeats($respTrim)) {
            return ['is_fake' => true, 'is_severe' => true, 'reason' => 'excess_repeats', 'category' => 'strong'];
        }

        // 6. Tutte le parole combinano lettere e numeri casualmente → fake forte ("abc123 xyz456")
        if ($this->allWordsHaveRandomLetterNumberCombo($respTrim)) {
            return ['is_fake' => true, 'is_severe' => true, 'reason' => 'random_alphanumeric', 'category' => 'strong'];
        }

        // 7. Parola singola con sequenza deterministica da tastiera → fake forte
        // (qwerty, asdfgh, zxcvbn, abcdef, 123456 e sottostringhe di almeno 4 caratteri)
        if ($this->isSingleWordWithIllegalSequence($respTrim)) {
            return ['is_fake' => true, 'is_severe' => true, 'reason' => 'illegal_sequence', 'category' => 'strong'];
        }

        // 8. Tutte le parole hanno pattern consonantici sospetti → fake medio
        // (copertura ampia: potrebbe includere abbreviazioni o typo)
        if ($this->hasOnlySuspiciousWords($respTrim)) {
            return ['is_fake' => true, 'is_severe' => false, 'reason' => 'suspicious_consonants', 'category' => 'medium'];
        }

        return ['is_fake' => false, 'is_severe' => false, 'reason' => null, 'category' => null];
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

    // Legge il file in un unico pass: conta le righe dati e costruisce la firma del percorso.
    private function readFileMetrics(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['questionCount' => 0, 'pathSignature' => ''];
        }

        $lineCount   = 0;
        $questionIds = [];
        $firstLine   = true;

        while (($line = fgets($handle)) !== false) {
            $trimmed = rtrim($line);
            if ($trimmed === '') {
                continue;
            }
            $lineCount++;
            if ($firstLine) {
                $firstLine = false;
                continue; // salta header
            }
            $fields = explode(';', $trimmed);
            $qid    = isset($fields[1]) ? trim($fields[1]) : '';
            if ($qid !== '' && ctype_digit($qid) && (int) $qid > 0) {
                $questionIds[] = (int) $qid;
            }
        }

        fclose($handle);
        sort($questionIds);

        return [
            'questionCount' => max(1, count(array_unique($questionIds))),
            'pathSignature' => implode(',', $questionIds),
        ];
    }

    // Costruisce il riferimento LOI per una singola intervista.
    // Priorità: stesso percorso → bucket ±2/5/10 → proporzionale.
    // Invariante: il riferimento peer non può MAI scendere sotto il proporzionale
    // (peer può solo rendere il controllo più severo, mai più permissivo).
    private function calculateReferenceMedian(
        string $currentIid,
        int    $currentQ,
        string $currentSignature,
        array  $signatureGroups,
        array  $allEligible,
        float  $globalMedian,
        int    $maxQ
    ): array {
        $propRef     = $this->calculateProportionalReference($currentQ, $globalMedian, $maxQ);
        $propRefInt  = (int) round($propRef);

        // Helper: applica max(peerMedian, propRef) e costruisce il risultato completo.
        $buildResult = function (
            string $peerType,
            int    $peerMedian,
            int    $sampleSize,
            int    $prelimMedian,
            int    $excluded
        ) use ($propRefInt): array {
            $finalRef = max($peerMedian, $propRefInt);
            return [
                'reference_type'                => $peerType,
                'peer_reference_type'           => $peerType,
                'peer_reference_median'         => $peerMedian,
                'proportional_reference'        => $propRefInt,
                'reference_median'              => $finalRef,
                'final_reference_source'        => $peerMedian >= $propRefInt ? 'peer' : 'proportional_floor',
                'reference_sample_size'         => $sampleSize,
                'preliminary_reference_median'  => $prelimMedian,
                'excluded_slow_reference_cases' => $excluded,
            ];
        };

        // 1. Stesso percorso (firma identica)
        if ($currentSignature !== '') {
            $candidates = $signatureGroups[$currentSignature] ?? [];
            $loiValues  = [];
            foreach ($candidates as $c) {
                if ($c['iid'] !== $currentIid) {
                    $loiValues[] = $c['loi'];
                }
            }
            $cleaned = $this->cleanReferenceLoiGroup($loiValues);
            if (count($cleaned['values']) >= self::LOI_MIN_REFERENCE_GROUP) {
                $peerMedian = (int) round($this->calculateMedian($cleaned['values']));
                return $buildResult('same_path', $peerMedian,
                    count($cleaned['values']), (int) round($cleaned['preliminary_median']), $cleaned['excluded']);
            }
        }

        // 2. Bucket adattivo ±2, ±5, ±10
        foreach ([2, 5, 10] as $delta) {
            $loiValues = [];
            foreach ($allEligible as $e) {
                if ($e['iid'] === $currentIid) {
                    continue;
                }
                if (abs($e['q'] - $currentQ) <= $delta) {
                    $loiValues[] = $e['loi'];
                }
            }
            $cleaned = $this->cleanReferenceLoiGroup($loiValues);
            if (count($cleaned['values']) >= self::LOI_MIN_REFERENCE_GROUP) {
                $peerMedian = (int) round($this->calculateMedian($cleaned['values']));
                return $buildResult("question_bucket_{$delta}", $peerMedian,
                    count($cleaned['values']), (int) round($cleaned['preliminary_median']), $cleaned['excluded']);
            }
        }

        // 3. Fallback proporzionale (nessun peer disponibile)
        return [
            'reference_type'                => 'proportional_fallback',
            'peer_reference_type'           => null,
            'peer_reference_median'         => null,
            'proportional_reference'        => $propRefInt,
            'reference_median'              => $propRefInt > 0 ? $propRefInt : (int) round($globalMedian),
            'final_reference_source'        => 'proportional_floor',
            'reference_sample_size'         => 0,
            'preliminary_reference_median'  => 0,
            'excluded_slow_reference_cases' => 0,
        ];
    }

    // Filtra e pulisce un gruppo LOI: esclude LOI > 2× mediana preliminare.
    // Le escluse non sono penalizzate, solo rimosse dalla costruzione del riferimento.
    private function cleanReferenceLoiGroup(array $loiValues): array
    {
        $valid = array_values(array_filter($loiValues, fn($v) => $v > 0 && $v < self::LOI_MAX_SECONDS));

        if (empty($valid)) {
            return ['values' => [], 'preliminary_median' => 0.0, 'excluded' => 0];
        }

        $prelimMedian = $this->calculateMedian($valid);
        $threshold    = $prelimMedian * 2.0;
        $cleaned      = [];
        $excluded     = 0;

        foreach ($valid as $v) {
            if ($v <= $threshold) {
                $cleaned[] = $v;
            } else {
                $excluded++;
            }
        }

        return ['values' => $cleaned, 'preliminary_median' => $prelimMedian, 'excluded' => $excluded];
    }

    private function calculateProportionalReference(int $q, float $globalMedian, int $maxQ): float
    {
        if ($maxQ <= 0 || $q <= 0 || $globalMedian <= 0) {
            return $globalMedian > 0 ? $globalMedian : 0.0;
        }
        return ($q / $maxQ) * $globalMedian;
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

        if ($countValid < 10) {
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
