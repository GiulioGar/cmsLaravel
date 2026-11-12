<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Panoramica field domanda per domanda.
 *
 * Tutte le analisi leggono i file .sre in un solo passaggio e accumulano solo
 * contatori (mai le interviste intere), per restare leggeri anche con
 * migliaia di file. Attribuzione dell'uscita: ultima domanda nel File Dati
 * (ultima riga utile del .sre), coerente con fieldControl.
 */
class PanoramicaService
{
    const STATUS_COMPLETE  = 3;
    const STATUS_SCREENOUT = 4;
    const STATUS_QUOTAFULL = 5;

    // Tipi di domanda mostrati nel menu laterale
    const SUPPORTED_TYPES = ['choice', 'scale', 'open'];

    // Oltre questo numero di valori distinti una variabile non e' incrociabile
    const MAX_CROSS_VALUES = 30;

    /** @var FieldControlSreService */
    private $sre;

    /** @var PanoramicaSdlService */
    private $sdl;

    public function __construct(FieldControlSreService $sre, PanoramicaSdlService $sdl)
    {
        $this->sre = $sre;
        $this->sdl = $sdl;
    }

    /*
    |--------------------------------------------------------------------------
    | CATALOGO DOMANDE (Primis)
    |--------------------------------------------------------------------------
    */

    public function getQuestionCatalog(PrimisApiService $primis, string $prj, string $sid): array
    {
        $cacheKey = "panoramica_catalog_v4_{$prj}_{$sid}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($primis, $prj, $sid) {
            try {
                $response = $primis->listQuestions($prj, $sid);
            } catch (\Exception $e) {
                return [];
            }

            if (empty($response['questions']) || !is_array($response['questions'])) {
                return [];
            }

            $catalog = [];
            foreach ($response['questions'] as $q) {
                $type = $q['type'] ?? '';
                if (!isset($q['id']) || !in_array($type, self::SUPPORTED_TYPES, true)) {
                    continue;
                }

                $catalog[] = [
                    'id'        => (int) $q['id'],
                    'code'      => (string) ($q['code'] ?? $q['id']),
                    'text'      => $this->cleanLabel($q['text'] ?? ''),
                    'type'      => $type,
                    'selection' => $q['selection'] ?? null,
                    'options'   => array_map([$this, 'cleanLabel'], $q['options'] ?? []),
                    'rows'      => array_map([$this, 'cleanLabel'], $q['rows'] ?? []),
                    'cols'      => array_map([$this, 'cleanLabel'], $q['cols'] ?? []),
                    'context'   => null,
                    'loop'      => null,
                ];
            }

            return $this->applySdlContext($prj, $sid, $catalog);
        });
    }

    /**
     * Arricchisce il catalogo con quanto ricavato dall'SDL: etichetta del
     * ciclo (es. Q12p3 -> Tananai) e righe reali al posto dei segnaposto.
     */
    private function applySdlContext(string $prj, string $sid, array $catalog): array
    {
        $codes = [];
        foreach ($catalog as $q) {
            $codes[$q['code']] = true;
        }

        try {
            $contexts = $this->sdl->getQuestionContext($prj, $sid, $codes);
        } catch (\Exception $e) {
            return $catalog;
        }

        foreach ($catalog as &$q) {
            $ctx = $contexts[$q['code']] ?? null;
            if ($ctx === null) {
                continue;
            }
            $q['context'] = $ctx['context'];
            $q['loop'] = $ctx['loop'];
            // Righe sostituite solo se la forma coincide con quella di Primis
            if (!empty($ctx['rows']) && count($ctx['rows']) === count($q['rows'])) {
                $q['rows'] = array_values($ctx['rows']);
            }
        }
        unset($q);

        return $catalog;
    }

    public function findQuestion(array $catalog, int $questionId): ?array
    {
        foreach ($catalog as $q) {
            if ($q['id'] === $questionId) {
                return $q;
            }
        }
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | RIEPILOGO GENERALE (menu laterale + KPI)
    |--------------------------------------------------------------------------
    */

    /**
     * Totali per stato + per ogni domanda: quante interviste ci sono arrivate
     * e quante sono uscite (screenout/quotafull) proprio li'.
     */
    public function getSummary(string $prj, string $sid): array
    {
        $cacheKey = "panoramica_summary_v3_{$prj}_{$sid}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($prj, $sid) {
            $files = $this->sre->getSreFiles($this->sre->resolveResultsDirectory($prj, $sid));

            $totals = [
                self::STATUS_COMPLETE  => 0,
                self::STATUS_SCREENOUT => 0,
                self::STATUS_QUOTAFULL => 0,
            ];
            $perQuestion = [];
            $variableValues = [];
            $variablePresence = [];
            // [variabile][qid][valore][opzione] => conteggio, per associare etichette
            $variablePairs = [];

            foreach ($files as $file) {
                $interview = $this->readInterview($file);
                if ($interview === null) {
                    continue;
                }

                $status = $interview['status'];
                if (isset($totals[$status])) {
                    $totals[$status]++;
                }

                $crossTags = [];
                foreach ($interview['tags'] as $key => $value) {
                    if (!$this->isCrossableVariable($key)) {
                        continue;
                    }
                    if (!isset($variableValues[$key])) {
                        $variableValues[$key] = [];
                    }
                    // Oltre la soglia la variabile non e' incrociabile: smettiamo di raccogliere
                    if (count($variableValues[$key]) <= self::MAX_CROSS_VALUES) {
                        $variableValues[$key][$value] = true;
                        $crossTags[$key] = $value;
                    }
                }

                if (!empty($crossTags)) {
                    foreach ($crossTags as $key => $value) {
                        $variablePresence[$key] = ($variablePresence[$key] ?? 0) + 1;
                    }
                    // Solo choice singole (4 campi): candidate a contenere l'etichetta della variabile
                    foreach ($interview['rows'] as $qid => $parts) {
                        if (($parts[0] ?? '') !== 'choice' || count($parts) !== 4 || (int) $parts[3] < 0) {
                            continue;
                        }
                        $opt = (int) $parts[3];
                        foreach ($crossTags as $key => $value) {
                            $variablePairs[$key][$qid][$value][$opt] = ($variablePairs[$key][$qid][$value][$opt] ?? 0) + 1;
                        }
                    }
                }

                foreach ($interview['rows'] as $qid => $parts) {
                    if (!isset($perQuestion[$qid])) {
                        $perQuestion[$qid] = ['reached' => 0, 'complete' => 0, 'screenout' => 0, 'quotafull' => 0];
                    }
                    $perQuestion[$qid]['reached']++;
                    if ($status === self::STATUS_COMPLETE) {
                        $perQuestion[$qid]['complete']++;
                    }
                }

                $exitQid = $interview['exit_qid'];
                if ($exitQid > 0 && isset($perQuestion[$exitQid])) {
                    if ($status === self::STATUS_SCREENOUT) {
                        $perQuestion[$exitQid]['screenout']++;
                    } elseif ($status === self::STATUS_QUOTAFULL) {
                        $perQuestion[$exitQid]['quotafull']++;
                    }
                }
            }

            // Variabili incrociabili: almeno 2 valori distinti e non troppi
            $variables = [];
            foreach ($variableValues as $key => $values) {
                $count = count($values);
                if ($count < 2 || $count > self::MAX_CROSS_VALUES) {
                    continue;
                }
                $list = array_map('strval', array_keys($values));
                natsort($list);
                $variables[$key] = array_values($list);
            }
            ksort($variables);

            return [
                'files'        => count($files),
                'complete'     => $totals[self::STATUS_COMPLETE],
                'screenout'    => $totals[self::STATUS_SCREENOUT],
                'quotafull'    => $totals[self::STATUS_QUOTAFULL],
                'per_question' => $perQuestion,
                'variables'    => $variables,
                'variable_links' => $this->findVariableLinks($variables, $variablePairs, $variablePresence),
            ];
        });
    }

    /**
     * Cerca, per ogni variabile, una choice singola in cui ogni valore della
     * variabile corrisponde sempre alla stessa opzione (es. cella -> persSelez):
     * quell'opzione diventa l'etichetta del valore.
     */
    private function findVariableLinks(array $variables, array $pairs, array $presence): array
    {
        $links = [];

        foreach ($variables as $key => $values) {
            $best = null;

            foreach ($pairs[$key] ?? [] as $qid => $byValue) {
                $total = 0;
                $pure = 0;
                $map = [];
                $usedOptions = [];

                foreach ($byValue as $value => $options) {
                    $max = max($options);
                    $map[(string) $value] = (int) array_search($max, $options, true);
                    $usedOptions[$map[(string) $value]] = true;
                    $total += array_sum($options);
                    $pure += $max;
                }

                $isComplete = count($map) === count($values);
                $isInjective = count($usedOptions) === count($map);
                $coverage = ($presence[$key] ?? 0) > 0 ? $total / $presence[$key] : 0;
                $purity = $total > 0 ? $pure / $total : 0;

                if (!$isComplete || !$isInjective || $coverage < 0.8 || $purity < 0.95) {
                    continue;
                }
                if ($best === null || $total > $best['total'] || ($total === $best['total'] && $purity > $best['purity'])) {
                    $best = ['question_id' => (int) $qid, 'map' => $map, 'total' => $total, 'purity' => $purity];
                }
            }

            if ($best !== null) {
                $links[$key] = ['question_id' => $best['question_id'], 'map' => $best['map']];
            }
        }

        return $links;
    }

    /**
     * Etichette leggibili per i valori delle variabili incrociabili.
     * 1) dai dati: opzione della domanda associata (findVariableLinks)
     * 2) dall'SDL: vector X = new vector {...} usato come X.get(var),
     *    anche con scostamento lineare (es. leg=cella+1)
     * Restituisce [var => ['source' => string, 'labels' => [valore => etichetta]]].
     */
    public function getVariableLabels(string $prj, string $sid, array $summary, array $catalog): array
    {
        $variables = $summary['variables'] ?? [];
        $result = [];

        foreach ($summary['variable_links'] ?? [] as $key => $link) {
            $question = $this->findQuestion($catalog, $link['question_id']);
            if ($question === null) {
                continue;
            }
            $labels = [];
            foreach ($link['map'] as $value => $optIdx) {
                $labels[(string) $value] = $question['options'][$optIdx] ?? (string) $value;
            }
            $result[$key] = ['source' => $question['code'], 'labels' => $labels];
        }

        $missing = array_diff(array_keys($variables), array_keys($result));
        if (!empty($missing)) {
            foreach ($this->sdl->getVariableVectors($prj, $sid, $missing) as $key => $vector) {
                $labels = [];
                foreach ($variables[$key] as $value) {
                    $idx = is_numeric($value) ? (int) $value - $vector['offset'] : -1;
                    if (isset($vector['items'][$idx]) && $vector['items'][$idx] !== '') {
                        $labels[(string) $value] = $vector['items'][$idx];
                    }
                }
                if (!empty($labels)) {
                    $result[$key] = ['source' => 'SDL', 'labels' => $labels];
                }
            }
        }

        return $result;
    }


    /**
     * Mappa nome file .sre -> panel, con la stessa classificazione di
     * fieldControl (pan=, poi UID Interactive, altrimenti "Da lista"),
     * piu' l'elenco dei panel presenti tra le complete.
     */
    public function getPanelMap(string $prj, string $sid): array
    {
        $cacheKey = "panoramica_panel_map_v2_{$prj}_{$sid}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($prj, $sid) {
            $files = $this->sre->getSreFiles($this->sre->resolveResultsDirectory($prj, $sid));
            $map = [];
            $completePanels = [];
            foreach ($this->sre->buildInterviewDataset($files, $prj, $sid) as $interview) {
                $panel = (string) $interview['panel'];
                $map[basename($interview['file'])] = $panel;
                if ((int) $interview['status_code'] === self::STATUS_COMPLETE) {
                    $completePanels[$panel] = true;
                }
            }
            return ['map' => $map, 'complete_panels' => array_keys($completePanels)];
        });
    }

    /**
     * Costruisce la definizione di incrocio a partire dal valore della select
     * ("panel", "var:leg", "q:30"). Restituisce null se non valido.
     */
    public function resolveCross(string $prj, string $sid, string $crossKey, array $catalog, array $question): ?array
    {
        if ($crossKey === 'panel') {
            $panels = $this->getPanelMap($prj, $sid);
            if (count($panels['complete_panels']) < 2) {
                return null;
            }
            return ['type' => 'panel', 'label' => 'Panel', 'map' => $panels['map']];
        }

        if (strpos($crossKey, 'var:') === 0) {
            $key = substr($crossKey, 4);
            $summary = $this->getSummary($prj, $sid);
            $variables = $summary['variables'] ?? [];
            if (!isset($variables[$key])) {
                return null;
            }
            $known = $this->getVariableLabels($prj, $sid, $summary, $catalog)[$key] ?? null;
            $labels = [];
            foreach ($variables[$key] as $value) {
                $labels[$value] = $known['labels'][$value] ?? $value;
            }
            return [
                'type'   => 'var',
                'key'    => $key,
                'label'  => 'Variabile ' . $key . ($known ? ' (etichette da ' . $known['source'] . ')' : ''),
                'labels' => $labels,
                'raw_values' => $known !== null,
            ];
        }

        if (strpos($crossKey, 'q:') === 0) {
            $crossQuestion = $this->findQuestion($catalog, (int) substr($crossKey, 2));
            if ($crossQuestion === null || $crossQuestion['type'] !== 'choice' || $crossQuestion['id'] === $question['id']) {
                return null;
            }
            $labels = [];
            foreach ($crossQuestion['options'] as $idx => $option) {
                $labels[(string) $idx] = $option !== '' ? $option : 'Opzione ' . ($idx + 1);
            }
            return [
                'type'   => 'question',
                'id'     => $crossQuestion['id'],
                'label'  => $crossQuestion['code'] . ' - ' . $crossQuestion['text'],
                'labels' => $labels,
            ];
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | ANALISI SINGOLA DOMANDA
    |--------------------------------------------------------------------------
    */

    public function analyzeQuestion(string $prj, string $sid, array $question, ?array $cross = null): array
    {
        $files = $this->sre->getSreFiles($this->sre->resolveResultsDirectory($prj, $sid));
        $qid = $question['id'];
        $type = $question['type'];

        $complete = $this->emptyDistribution($question);
        $exits = [
            'screenout' => $this->emptyDistribution($question),
            'quotafull' => $this->emptyDistribution($question),
        ];
        $reached = ['total' => 0, 'complete' => 0, 'screenout' => 0, 'quotafull' => 0];
        $totals = ['complete' => 0, 'screenout' => 0, 'quotafull' => 0];
        $exitCounts = ['screenout' => 0, 'quotafull' => 0];
        $daily = [];
        $isMultiple = false;
        $crossDist = [];
        $crossMissing = 0;

        foreach ($files as $file) {
            $interview = $this->readInterview($file);
            if ($interview === null) {
                continue;
            }

            $statusKey = $this->statusKey($interview['status']);
            if ($statusKey !== null) {
                $totals[$statusKey]++;
            }

            if (!isset($interview['rows'][$qid])) {
                continue;
            }

            $parts = $interview['rows'][$qid];
            $reached['total']++;
            if ($statusKey === null) {
                continue;
            }
            $reached[$statusKey]++;

            if ($type === 'choice' && count($parts) >= 5) {
                $isMultiple = true;
            }

            $day = $interview['day'];
            if ($day !== null && !isset($daily[$day])) {
                $daily[$day] = [
                    'complete' => 0, 'screenout' => 0, 'quotafull' => 0,
                    'answered' => 0, 'options' => [], 'num_sum' => 0, 'num_n' => 0,
                ];
            }

            if ($statusKey === 'complete') {
                $baseBefore = $complete['base'];
                $answer = $this->accumulate($complete, $question, $parts);
                $answered = $complete['base'] > $baseBefore;

                if ($day !== null) {
                    $daily[$day]['complete']++;
                    if ($answered) {
                        $daily[$day]['answered']++;
                    }
                    if ($type === 'choice') {
                        foreach ($answer as $idx) {
                            $daily[$day]['options'][$idx] = ($daily[$day]['options'][$idx] ?? 0) + 1;
                        }
                    } elseif ($type === 'open' && $answered) {
                        $number = $this->toNumber($parts);
                        if ($number !== null) {
                            $daily[$day]['num_sum'] += $number;
                            $daily[$day]['num_n']++;
                        }
                    }
                }

                if ($cross !== null && $answered) {
                    $categories = $this->crossCategories($interview, $file, $cross);
                    if (empty($categories)) {
                        $crossMissing++;
                    }
                    foreach ($categories as $category) {
                        if (!isset($crossDist[$category])) {
                            $crossDist[$category] = $this->emptyDistribution($question);
                        }
                        $this->accumulate($crossDist[$category], $question, $parts);
                    }
                }
            } elseif ($interview['exit_qid'] === $qid) {
                // Uscita (screenout/quotafull) avvenuta proprio su questa domanda
                $exitCounts[$statusKey]++;
                $this->accumulate($exits[$statusKey], $question, $parts);
                if ($day !== null) {
                    $daily[$day][$statusKey]++;
                }
            }
        }

        ksort($daily);

        return [
            'question'    => $question,
            'is_multiple' => $isMultiple,
            'totals'      => $totals,
            'reached'     => $reached,
            'exit_counts' => $exitCounts,
            'complete'    => $this->finalizeDistribution($complete, $type),
            'screenout'   => $this->finalizeDistribution($exits['screenout'], $type),
            'quotafull'   => $this->finalizeDistribution($exits['quotafull'], $type),
            'daily'       => $daily,
            'cross'       => $cross !== null ? $this->finalizeCross($cross, $crossDist, $crossMissing, $type) : null,
        ];
    }

    /**
     * Categorie dell'incrocio a cui appartiene l'intervista (piu' di una
     * se la domanda di incrocio e' a risposta multipla).
     */
    private function crossCategories(array $interview, string $file, array $cross): array
    {
        if ($cross['type'] === 'panel') {
            return [$cross['map'][basename($file)] ?? 'Sconosciuto'];
        }

        if ($cross['type'] === 'var') {
            return isset($interview['tags'][$cross['key']]) ? [(string) $interview['tags'][$cross['key']]] : [];
        }

        $parts = $interview['rows'][$cross['id']] ?? null;
        if ($parts === null || ($parts[0] ?? '') !== 'choice') {
            return [];
        }
        return array_map('strval', $this->parseChoice($parts));
    }

    private function finalizeCross(array $cross, array $crossDist, int $missing, string $type): array
    {
        // Ordine colonne: quello delle opzioni/valori; per il panel per numerosita'
        if ($cross['type'] === 'panel') {
            uasort($crossDist, function ($a, $b) {
                return $b['base'] - $a['base'];
            });
            $order = array_keys($crossDist);
        } else {
            $order = array_map('strval', array_keys($cross['labels']));
        }

        $columns = [];
        foreach ($order as $key) {
            $key = (string) $key;
            if (!isset($crossDist[$key]) || $crossDist[$key]['base'] === 0) {
                continue;
            }
            $columns[] = [
                'key'   => $key,
                'label' => $cross['labels'][$key] ?? $key,
                'dist'  => $this->finalizeDistribution($crossDist[$key], $type),
            ];
        }

        return [
            'type'    => $cross['type'],
            'label'   => $cross['label'],
            'columns' => $columns,
            'missing' => $missing,
            // Con etichette risolte mostriamo anche il valore grezzo (es. leg 3)
            'show_keys' => !empty($cross['raw_values']) ? ($cross['key'] ?? '') : '',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | LETTURA .SRE
    |--------------------------------------------------------------------------
    */

    /**
     * Legge un .sre e restituisce solo cio' che serve: stato, giorno,
     * righe risposta indicizzate per question id, domanda di uscita.
     */
    private function readInterview(string $file): ?array
    {
        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines) || empty($lines)) {
            return null;
        }

        $header = explode(';', trim((string) $lines[0]));
        $columns = $this->sre->getSreColumnMap($header);

        $rows = [];
        $exitQid = 0;
        $count = count($lines);
        for ($i = 1; $i < $count; $i++) {
            $parts = explode(';', trim((string) $lines[$i]));
            if (!isset($parts[1]) || !is_numeric($parts[1])) {
                continue;
            }
            $qid = (int) $parts[1];
            $rows[$qid] = $parts;
            $exitQid = $qid;
        }

        $rawDate = $header[$columns['end_date']] ?? '';
        if ($rawDate === '' || $rawDate === 'N/A') {
            $rawDate = $header[$columns['start_date']] ?? '';
        }
        $date = $this->sre->parseDateToCarbon($rawDate);

        // Variabili chiave=valore della prima riga (dopo i campi fissi)
        $tags = [];
        $headerCount = count($header);
        for ($i = $columns['last_question_code'] + 1; $i < $headerCount; $i++) {
            $eq = strpos($header[$i], '=');
            if ($eq > 0) {
                $tags[substr($header[$i], 0, $eq)] = substr($header[$i], $eq + 1);
            }
        }

        return [
            'status'   => (int) ($header[$columns['status']] ?? -1),
            'day'      => $date ? $date->format('Y-m-d') : null,
            'rows'     => $rows,
            'exit_qid' => $exitQid,
            'tags'     => $tags,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DISTRIBUZIONI
    |--------------------------------------------------------------------------
    */

    private function emptyDistribution(array $question): array
    {
        if ($question['type'] === 'choice') {
            return ['base' => 0, 'counts' => array_fill(0, count($question['options']), 0)];
        }

        if ($question['type'] === 'scale') {
            $rowCount = max(1, count($question['rows']));
            return [
                'base'      => 0,
                'row_bases' => array_fill(0, $rowCount, 0),
                'counts'    => array_fill(0, $rowCount, array_fill(0, count($question['cols']), 0)),
            ];
        }

        return ['base' => 0, 'values' => [], 'non_numeric' => 0];
    }

    /**
     * Aggiunge una riga risposta alla distribuzione. Per le choice restituisce
     * gli indici selezionati (riusati per la serie giornaliera).
     */
    private function accumulate(array &$dist, array $question, array $parts): array
    {
        $type = $question['type'];

        if ($type === 'choice') {
            $selected = $this->parseChoice($parts);
            if (empty($selected)) {
                return [];
            }
            $dist['base']++;
            foreach ($selected as $idx) {
                if (isset($dist['counts'][$idx])) {
                    $dist['counts'][$idx]++;
                }
            }
            return $selected;
        }

        if ($type === 'scale') {
            // scale;id;nRows;nCols;colRow0;colRow1;... (-1 = riga non mostrata)
            $nRows = (int) ($parts[2] ?? 0);
            $answered = false;
            for ($r = 0; $r < $nRows; $r++) {
                $col = isset($parts[4 + $r]) ? (int) $parts[4 + $r] : -1;
                if ($col < 0 || !isset($dist['counts'][$r][$col])) {
                    continue;
                }
                $dist['counts'][$r][$col]++;
                $dist['row_bases'][$r]++;
                $answered = true;
            }
            if ($answered) {
                $dist['base']++;
            }
            return [];
        }

        // open: open;id;valore (il valore puo' contenere ';')
        if ($this->openValue($parts) === '') {
            return [];
        }
        $dist['base']++;
        $number = $this->toNumber($parts);
        if ($number !== null) {
            $dist['values'][] = $number;
        } else {
            $dist['non_numeric']++;
        }
        return [];
    }

    private function openValue(array $parts): string
    {
        return trim(implode(';', array_slice($parts, 2)));
    }

    private function toNumber(array $parts): ?float
    {
        $value = str_replace(',', '.', $this->openValue($parts));
        return ($value !== '' && is_numeric($value)) ? (float) $value : null;
    }

    /**
     * Esclude variabili tecniche: sys*, pan (gestito come "Panel"), lang,
     * randomizzazioni tipo r12 / Svar3.
     */
    private function isCrossableVariable(string $key): bool
    {
        if ($key === '' || $key === 'pan' || $key === 'lang' || stripos($key, 'sys') === 0) {
            return false;
        }
        return !preg_match('/^(r|Svar)\d+$/', $key);
    }

    /**
     * choice;id;nOpt;indice          -> singola
     * choice;id;nOpt;stringa01;extra -> multipla
     */
    private function parseChoice(array $parts): array
    {
        $nOpt = (int) ($parts[2] ?? 0);
        $field = isset($parts[3]) ? trim($parts[3]) : '';
        if ($field === '') {
            return [];
        }

        if (count($parts) >= 5 && strlen($field) === $nOpt && preg_match('/^[01]+$/', $field)) {
            $selected = [];
            for ($i = 0; $i < $nOpt; $i++) {
                if ($field[$i] === '1') {
                    $selected[] = $i;
                }
            }
            return $selected;
        }

        $idx = (int) $field;
        return $idx >= 0 ? [$idx] : [];
    }

    private function finalizeDistribution(array $dist, string $type): array
    {
        if ($type !== 'open') {
            return $dist;
        }

        // Per le open numeriche restituiamo solo statistiche, non i valori grezzi
        $values = $dist['values'];
        sort($values);
        $n = count($values);
        $stats = null;
        if ($n > 0) {
            $mid = intdiv($n, 2);
            $stats = [
                'count'  => $n,
                'min'    => $values[0],
                'max'    => $values[$n - 1],
                'mean'   => round(array_sum($values) / $n, 1),
                'median' => $n % 2 ? $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2,
            ];
        }

        return [
            'base'        => $dist['base'],
            'non_numeric' => $dist['non_numeric'],
            'stats'       => $stats,
        ];
    }

    private function statusKey(int $status): ?string
    {
        if ($status === self::STATUS_COMPLETE) {
            return 'complete';
        }
        if ($status === self::STATUS_SCREENOUT) {
            return 'screenout';
        }
        if ($status === self::STATUS_QUOTAFULL) {
            return 'quotafull';
        }
        return null;
    }

    /**
     * Testo leggibile da HTML Primis: via blocchi <script>/<style>, tag e
     * snippet jQuery incollati nel testo (es. $(document).ready(function(){...});).
     */
    private function cleanLabel($label): string
    {
        $text = (string) $label;
        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $text);
        // Spazio prima di ogni tag: evita parole incollate quando il tag viene rimosso
        $text = html_entity_decode(strip_tags(str_replace('<', ' <', $text)), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\$\(\s*(?:document|window|this|[\'"][^\'"]*[\'"])\s*\)\s*\.[\s\S]*?\}\s*\)\s*;?/u', ' ', $text);
        $text = preg_replace('/\bfunction\s*\([^)]*\)\s*\{[^{}]*\}\s*;?/u', ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
