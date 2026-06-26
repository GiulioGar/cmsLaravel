<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PanelControl;
use App\Services\PrimisApiService;
use App\Services\FieldControlSreService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Cache;

class FieldControlController extends Controller
{
public function index(Request $request, PrimisApiService $primis, FieldControlSreService $sreService)
{
    ini_set('memory_limit', '256M');

    $prj = $request->query('prj');
    $sid = $request->query('sid');

    $panelData = PanelControl::where('sur_id', $sid)->first();

    $directory = $sreService->resolveResultsDirectory($prj, $sid);

    $questionMap = $this->buildQuestionMap($primis, $prj, $sid);
    $quotaData = $this->getQuotaData($prj, $sid, $sreService, $questionMap);

    // 👇 NON SERVE PIÙ PER LA CLASSIFICAZIONE
    // $panelNames = $this->getPanelNamesMap();

    $panelValueFromDB = $panelData->panel ?? null;

    $files = $sreService->getSreFiles($directory);

    /*
    |--------------------------------------------------------------------------
    | NUOVA LOGICA SRE (SUPER IMPORTANTE)
    |--------------------------------------------------------------------------
    */
    $interviews = $sreService->buildInterviewDataset($files, $prj, $sid);

    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */
    $summary = $sreService->summarizeInterviews($interviews, count($files));
    $counts = $summary['counts'];
    $panelCounts = $summary['panelCounts'];

    $abilitati = DB::table('t_respint as r')
        ->join('t_user_info as u', 'u.user_id', '=', 'r.uid')
        ->where('r.sid', $sid)
        ->where('r.status', '!=', 6)
        ->count();

    $denominator = $counts['contatti'] - $counts['sospese'] - $counts['bloccate'] - $counts['over_quota'];

    $redemption = ($denominator > 0)
        ? round(($counts['complete'] / $denominator) * 100, 2)
        : 0;

    foreach ($panelCounts as $panelName => &$panel) {
        $panelDenominator = $panel['contatti'] - $panel['sospese'] - $panel['bloccate'] - $panel['over_quota'];

        $panel['redemption'] = ($panelDenominator > 0)
            ? round(($panel['complete'] / $panelDenominator) * 100, 2)
            : 0;
    }
    unset($panel);

    $panelCounts = $this->sortPanelsForDisplay($panelCounts);


    /*
    |--------------------------------------------------------------------------
    | UTENTI / STIME
    |--------------------------------------------------------------------------
    */
    $utentiDisponibili = $this->getUtentiDisponibili($sid, $panelData);
    $mediaRedPanel = $this->calcolaMediaRedPanel();

    $stimaInterviste = ((int) $panelValueFromDB === 1)
        ? $this->calcolaStimaInterviste($utentiDisponibili, $redemption, $mediaRedPanel)
        : null;

    $bytes = $panelData->bytes ?? 0;

    $this->updatePanelControl($sid, $counts, $abilitati, $panelCounts, $redemption, $bytes);

    /*
    |--------------------------------------------------------------------------
    | QUESTION MAP + FILTRATE
    |--------------------------------------------------------------------------
    */
    $filtrateCountsByPanel = $sreService->buildFiltrateCountsFromInterviews($interviews, $questionMap);
    $filtrateCountsByPanel = $this->sortPanelsForDisplay($filtrateCountsByPanel);

    $hasFiltrate = false;
    foreach ($filtrateCountsByPanel as $panel => $rows) {
        if (!empty($rows) && array_sum($rows) > 0) {
            $hasFiltrate = true;
            break;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LOG & TIME SERIES
    |--------------------------------------------------------------------------
    */
    $logData = $sreService->buildLogDataFromInterviews($interviews, $questionMap);
    $dataSummaryByPanel = $sreService->buildDataSummaryByDateFromInterviews($interviews);
    $dataSummaryByPanel = $this->sortPanelsForDisplay($dataSummaryByPanel);

    /*
    |--------------------------------------------------------------------------
    | CACHE RICERCHE IN CORSO
    |--------------------------------------------------------------------------
    */
    $cacheKey = "fieldcontrol_ricerche_in_corso_{$prj}_{$sid}";

    $ricercheInCorso = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($prj, $sid) {
        return DB::table('t_panel_control')
            ->where('stato', 0)
            ->where(function ($query) use ($prj, $sid) {
                $query->where('sur_id', '!=', $sid)
                      ->orWhere('prj', '!=', $prj);
            })
            ->orderBy('description', 'asc')
            ->get(['sur_id', 'description', 'prj']);
    });

    $primisSurveyStatus = DB::table('t_surveys')
        ->where('sid', $sid)
        ->where('prj_name', $prj)
        ->value('status');

    return view('fieldControl', compact(
        'prj',
        'sid',
        'panelData',
        'counts',
        'abilitati',
        'redemption',
        'panelCounts',
        'utentiDisponibili',
        'stimaInterviste',
        'filtrateCountsByPanel',
        'hasFiltrate',
        'quotaData',
        'logData',
        'dataSummaryByPanel',
        'ricercheInCorso',
        'primisSurveyStatus'
    ));
}

public function downloadCSV(Request $request, FieldControlSreService $sreService)
{
    $prj = $request->query('prj');
    $sid = $request->query('sid');
    $panelName = $request->query('panel');

    if (!$panelName) {
        return redirect()->back()->with('error', 'Seleziona un panel per scaricare il file.');
    }

    $directory = $sreService->resolveResultsDirectory($prj, $sid);

    if (!$directory) {
        return redirect()->back()->with('error', 'Directory dei file .sre non trovata.');
    }

    $files = $sreService->getSreFiles($directory);

    if (empty($files)) {
        return redirect()->back()->with('error', 'Nessun file .sre trovato.');
    }

    /*
    |--------------------------------------------------------------------------
    | DATASET UNICO (NUOVA LOGICA)
    |--------------------------------------------------------------------------
    */
    $interviews = $sreService->buildInterviewDataset($files, $prj, $sid);

    $panelExportConfig = $this->getPanelExportConfig($panelName);
    $configVariables = $sreService->getConfigRedirectVariables(
        $prj,
        $sid,
        $panelExportConfig->panel_code ?? null
    );

    $safePanelName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $panelName);
    $fileName = "download_{$safePanelName}_{$prj}_{$sid}.csv";

    $response = new StreamedResponse(function () use ($interviews, $panelName, $panelExportConfig, $configVariables, $prj, $sid, $sreService) {

        $handle = fopen('php://output', 'w');

        /*
        |--------------------------------------------------------------------------
        | HEADER DINAMICO
        |--------------------------------------------------------------------------
        */
        $extraFields = [];

        if ($panelExportConfig) {
            foreach (['red_3', 'red_4', 'red_5'] as $redField) {
                $fieldName = trim((string) ($panelExportConfig->{$redField} ?? ''));

                if ($fieldName !== '') {
                    $extraFields[] = $fieldName;
                }
            }
        }

        foreach ($configVariables as $variableName) {
            if (!in_array($variableName, $extraFields, true)) {
                $extraFields[] = $variableName;
            }
        }

        $csvHeaders = array_merge(['uid'], $extraFields, ['statusCode', 'Status', 'link']);
        fputcsv($handle, $csvHeaders, ';');

        /*
        |--------------------------------------------------------------------------
        | LOOP SU DATASET (NON FILE!)
        |--------------------------------------------------------------------------
        */
        foreach ($interviews as $interview) {

            if ($interview['panel'] !== $panelName) {
                continue;
            }

            $raw = $interview['raw'];

            $statusMap = $sreService->getDownloadStatusMap();
            $statusLabel = $statusMap[$interview['status_code']] ?? 'unknown';

            $uid = $interview['uid'] ?? 'N/A';

            /*
            |--------------------------------------------------------------------------
            | EXTRA FIELDS
            |--------------------------------------------------------------------------
            */
            $extraValues = [];

            foreach ($extraFields as $fieldName) {
                $fieldValue = $sreService->resolveDownloadFieldValue($interview, $fieldName, $prj, $sid);

                if ($fieldValue === 'N/A') {
                    $fieldValue = 'N.D.';
                }

                $extraValues[] = $fieldValue;
            }

            /*
            |--------------------------------------------------------------------------
            | LINK
            |--------------------------------------------------------------------------
            */
            if ($panelName === 'Interactive') {
                $link = "https://www.primisoft.com/primis/run.do?sid={$sid}&prj={$prj}&uid={$uid}";
            } else {
                // recuperiamo panel_code da config
                $panelCode = $panelExportConfig->panel_code ?? null;

                $link = $panelCode
                    ? "https://www.primisoft.com/primis/run.do?sid={$sid}&prj={$prj}&uid={$uid}&pan={$panelCode}"
                    : "https://www.primisoft.com/primis/run.do?sid={$sid}&prj={$prj}&uid={$uid}";
            }

            /*
            |--------------------------------------------------------------------------
            | ROW
            |--------------------------------------------------------------------------
            */
            $row = array_merge(
                [$uid],
                $extraValues,
                [$interview['status_code'], $statusLabel, $link]
            );

            fputcsv($handle, $row, ';');
        }

        fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

    return $response;
}

    public function closeSurvey(Request $request)
    {
        $prj = $request->input('prj');
        $sid = $request->input('sid');

        $survey = DB::table('t_panel_control')
            ->where('prj', $prj)
            ->where('sur_id', $sid)
            ->first();

        if (!$survey) {
            return response()->json(['success' => false, 'message' => 'Ricerca non trovata.'], 404);
        }

        if ((int) $survey->stato === 1) {
            return response()->json(['success' => false, 'message' => 'La ricerca è già chiusa.'], 400);
        }

        DB::table('t_panel_control')
            ->where('prj', $prj)
            ->where('sur_id', $sid)
            ->update(['stato' => 1]);

        return response()->json(['success' => true]);
    }

    public function resetBloccate(Request $request, FieldControlSreService $sreService)
    {
        $prj = $request->input('prj');
        $sid = $request->input('sid');

        $directory = $sreService->resolveResultsDirectory($prj, $sid);

        if (!$directory) {
            return response()->json(['success' => false, 'message' => 'Directory non trovata.'], 404);
        }

        $files = $sreService->getSreFiles($directory);

        if (empty($files)) {
            return response()->json(['success' => false, 'message' => 'Nessun file .sre trovato.'], 404);
        }

        $resetCount = 0;

        foreach ($files as $file) {
            $parsed = $sreService->parseSreFile($file);

            if (empty($parsed)) {
                continue;
            }

            if ((int) $parsed['status_code'] === 7) {
                $uid = $sreService->extractTaggedFieldValue($parsed['raw'], 'sysUID');
                if ($uid === 'N/A') {
                    $uid = $parsed['uid'] ?? 'N/A';
                }

                unlink($file);
                $resetCount++;

                DB::table('t_respint')
                    ->where('sid', $sid)
                    ->where('uid', $uid)
                    ->update([
                        'status' => 0,
                        'iid' => -1
                    ]);
            }
        }

        return response()->json([
            'success' => true,
            'resetCount' => $resetCount
        ]);
    }

    private function updatePanelControl($sid, $counts, $abilitati, $panelCounts, $redemption, $bytes)
    {
        $panelInteractiveComplete = $panelCounts['Interactive']['complete'] ?? 0;
        $panelExternalComplete = array_sum(array_column(array_diff_key($panelCounts, ['Interactive' => '']), 'complete'));

        $panelContatti = 0;

            foreach ($panelCounts as $panelName => $panel) {
                if ($panelName !== 'Da lista') {
                    $panelContatti += $panel['contatti'];
                }
            }

$panelInteractiveContatti = $panelCounts['Interactive']['contatti'] ?? 0;

$redPanel = ($abilitati > 0)
    ? round($panelInteractiveContatti / $abilitati, 2)
    : 0;

        $costo = ($bytes / 1000) * $panelInteractiveComplete;

        DB::table('t_panel_control')->where('sur_id', $sid)->update([
            'abilitati' => $abilitati,
            'contatti' => $counts['contatti'],
            'red_panel' => $redPanel * 100,
            'complete_int' => $panelInteractiveComplete,
            'complete_ext' => $panelExternalComplete,
            'complete' => $counts['complete'],
            'red_surv' => $redemption,
            'last_update' => now(),
            'costo' => $costo
        ]);
    }

private function getUtentiDisponibili($sid, $panelTarget)
{
    if (!$panelTarget) {
        return 0;
    }

    $genderFilter = [1, 2];

    switch ((int) $panelTarget->sex_target) {
        case 1:
            $genderFilter = [1];
            break;
        case 2:
            $genderFilter = [2];
            break;
        case 3:
            $genderFilter = [1, 2];
            break;
    }

    $etaMin = (int) $panelTarget->age1_target;
    $etaMax = (int) $panelTarget->age2_target;
    $annoCorrente = date('Y');

    return DB::table('t_user_info')
        ->whereIn('gender', $genderFilter)
        ->whereRaw("YEAR(birth_date) BETWEEN ? AND ?", [$annoCorrente - $etaMax, $annoCorrente - $etaMin])
        ->where('active', 1)
        ->where('confirm', 1)
        ->whereNotExists(function ($query) use ($sid) {
            $query->select(DB::raw(1))
                ->from('t_respint')
                ->whereRaw('t_respint.uid = t_user_info.user_id')
                ->where('t_respint.sid', $sid);
        })
        ->count();
}

private function sortPanelsForDisplay(array $panels): array
{
    if (empty($panels)) {
        return $panels;
    }

    $sortedPanels = [];

    if (array_key_exists('Interactive', $panels)) {
        $sortedPanels['Interactive'] = $panels['Interactive'];
        unset($panels['Interactive']);
    }

    $daLista = null;

    if (array_key_exists('Da lista', $panels)) {
        $daLista = $panels['Da lista'];
        unset($panels['Da lista']);
    }

    foreach ($panels as $panelName => $panelData) {
        $sortedPanels[$panelName] = $panelData;
    }

    if ($daLista !== null) {
        $sortedPanels['Da lista'] = $daLista;
    }

    return $sortedPanels;
}

private function calcolaMediaRedPanel()
{
    return Cache::remember('fieldcontrol_media_red_panel', now()->addMinutes(30), function () {
        $dueAnniFa = now()->subYears(2);

        return DB::table('t_panel_control')
            ->where('panel', 1)
            ->whereBetween('red_panel', [7, 29])
            ->where('sur_date', '>=', $dueAnniFa)
            ->avg('red_panel') ?? 0;
    });
}

    private function calcolaStimaInterviste($utentiDisponibili, $redSurv, $mediaRedPanel)
    {
        $percentualeRedSurv = $redSurv / 100;
        $percentualeMediaRedPanel = $mediaRedPanel / 100;

        $step1 = $utentiDisponibili * $percentualeRedSurv;
        $stimaInterviste = $step1 * $percentualeMediaRedPanel;

        return max(0, round($stimaInterviste));
    }

    private function getQuotaData($prj, $sid, FieldControlSreService $sreService, array $questionMap = [])
    {
        $quotaConfig = $this->getQuotaConfig($prj, $sid, $sreService, $questionMap);

        return DB::table('t_quota_status')
            ->where('survey_id', $sid)
            ->orderBy('id', 'asc')
            ->select('target_name as quota', 'target_value as totale', 'current_value as entrate')
            ->get()
            ->map(function ($item) use ($quotaConfig, $questionMap) {
                $item->missing = max(0, $item->totale - $item->entrate);
                $quotaMeta = $this->formatQuotaName($item->quota, $quotaConfig, $questionMap);
                $item->quota = $quotaMeta['label'];
                $item->quota_tooltip = $quotaMeta['tooltip'];
                return $item;
            });
    }

    private function formatQuotaName($quotaName, array $quotaConfig = [], array $questionMap = [])
    {
        if ($quotaName === 'source_panel') {
            return [
                'label' => 'Totale panel esterno',
                'tooltip' => null,
            ];
        }

        if (strpos($quotaName, 'total_interviews') === 0) {
            return $this->resolveTotalInterviewsQuotaMeta($quotaName, $quotaConfig, $questionMap);
        }

        $parts = explode('_', $quotaName);

        if (count($parts) === 2) {
            if (isset($quotaConfig['target_details'][$quotaName])) {
                return $quotaConfig['target_details'][$quotaName];
            }

            return [
                'label' => $this->formatSimpleQuotaLabel($parts[0], $parts[1], $quotaName),
                'tooltip' => null,
            ];
        }

        if (count($parts) === 3) {
            $targetKey = $parts[0] . '_' . $parts[1];

            if (isset($quotaConfig['target_details'][$targetKey])) {
                return $quotaConfig['target_details'][$targetKey];
            }

            return [
                'label' => $this->formatThreePartQuotaLabel($parts, $quotaName),
                'tooltip' => null,
            ];
        }

        return [
            'label' => $this->humanizeQuotaToken($quotaName),
            'tooltip' => null,
        ];
    }

    private function formatSimpleQuotaLabel($prefix, $rawValue, $fallbackName)
    {
        $label = $this->getQuotaPrefixLabel($prefix);

        if (is_numeric($rawValue)) {
            return $label . ' - Risposta ' . ((int) $rawValue + 1);
        }

        return $label . ' - ' . $this->humanizeQuotaToken($rawValue);
    }

    private function formatThreePartQuotaLabel(array $parts, $fallbackName)
    {
        $prefix = $parts[0];
        $middle = $parts[1];
        $last = $parts[2];
        $label = $this->getQuotaPrefixLabel($prefix);

        if (is_numeric($middle) && is_numeric($last)) {
            return $label
                . ' - Risposta ' . ((int) $middle + 1)
                . ' - Cella ' . $last;
        }

        if (!is_numeric($middle) && is_numeric($last)) {
            return $label
                . ' - ' . $this->humanizeQuotaToken($middle)
                . ' - Risposta ' . ((int) $last + 1);
        }

        return $label
            . ' - ' . $this->humanizeQuotaToken($middle)
            . ' - ' . $this->humanizeQuotaToken($last);
    }

    private function getQuotaPrefixLabel($prefix)
    {
        $map = [
            'sesso' => 'Sesso',
            'eta' => 'Età',
            'pers' => 'Personaggio',
            'reg' => 'Regione',
            'gdo' => 'Target GDO',
            'auto' => 'Target Autogrill',
            'bar' => 'Target Bar',
        ];

        $normalized = strtolower((string) $prefix);

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        return $this->humanizeQuotaToken($prefix);
    }

    private function humanizeQuotaToken($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = str_replace(['_', '-'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return ucwords(strtolower($value));
    }

    private function getQuotaConfig($prj, $sid, FieldControlSreService $sreService, array $questionMap = []): array
    {
        if (empty($prj) || empty($sid)) {
            return [];
        }

        $resourcesDirectory = $sreService->resolveResourcesDirectory($prj, $sid);

        if (!$resourcesDirectory) {
            return [];
        }

        $configPath = $resourcesDirectory . DIRECTORY_SEPARATOR . 'config.json';

        if (!is_file($configPath) || !is_readable($configPath)) {
            return [];
        }

        $content = @file_get_contents($configPath);

        if ($content === false || trim($content) === '') {
            return [];
        }

        $config = json_decode($content, true);

        if (!is_array($config)) {
            return [];
        }

        $variableName = trim((string) data_get($config, 'quota.total_by_leg.variable_name', ''));
        $legGroups = $this->buildQuotaLegGroupsFromConfig(data_get($config, 'quota.targets', []));
        $targetDetails = $this->buildQuotaTargetDetailsFromConfig(data_get($config, 'quota.targets', []), $questionMap);

        return [
            'total_by_leg_variable_name' => $variableName !== '' ? $variableName : null,
            'total_by_leg_groups' => $legGroups,
            'target_details' => $targetDetails,
        ];
    }

    private function resolveTotalInterviewsQuotaMeta($quotaName, array $quotaConfig, array $questionMap): array
    {
        if ($quotaName === 'total_interviews') {
            return [
                'label' => 'Interviste totali',
                'tooltip' => null,
            ];
        }

        $suffix = str_replace('total_interviews_', '', $quotaName);
        $legVariableName = $quotaConfig['total_by_leg_variable_name'] ?? null;
        $legGroups = $quotaConfig['total_by_leg_groups'] ?? [];
        $fallbackLabel = 'Interviste totali - ' . ($legVariableName ? $legVariableName . ' ' : 'Cella ') . $suffix;

        if (!is_numeric($suffix)) {
            return [
                'label' => $fallbackLabel,
                'tooltip' => null,
            ];
        }

        $legIndex = (string) $suffix;

        if (isset($legGroups[$legIndex])) {
            return [
                'label' => 'Interviste totali - ' . $legGroups[$legIndex]['label'],
                'tooltip' => null,
            ];
        }

        return [
            'label' => $fallbackLabel,
            'tooltip' => null,
        ];
    }

    private function buildQuotaLegGroupsFromConfig($targets): array
    {
        if (!is_array($targets) || empty($targets)) {
            return [];
        }

        $grouped = [];

        foreach ($targets as $target) {
            if (!is_array($target)) {
                continue;
            }

            $name = trim((string) ($target['name'] ?? ''));
            $description = trim((string) ($target['description'] ?? ''));

            if ($name === '') {
                continue;
            }

            $nameParts = explode('_', $name);
            $groupKey = strtolower(trim((string) ($nameParts[0] ?? '')));

            if ($groupKey === '') {
                continue;
            }

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'label' => $this->getQuotaPrefixLabel($groupKey),
                    'descriptions' => [],
                ];
            }

            if ($description !== '') {
                $grouped[$groupKey]['descriptions'][] = $description;
            }
        }

        $legs = [];
        $index = 1;

        foreach ($grouped as $group) {
            $legs[(string) $index] = [
                'label' => $group['label'],
                'tooltip' => !empty($group['descriptions'])
                    ? implode(' | ', $group['descriptions'])
                    : null,
            ];
            $index++;
        }

        return $legs;
    }

    private function buildQuotaTargetDetailsFromConfig($targets, array $questionMap): array
    {
        if (!is_array($targets) || empty($targets)) {
            return [];
        }

        $details = [];

        foreach ($targets as $target) {
            if (!is_array($target)) {
                continue;
            }

            $name = trim((string) ($target['name'] ?? ''));
            $description = trim((string) ($target['description'] ?? ''));
            $questionId = isset($target['question_id']) ? (int) $target['question_id'] : 0;
            $optionIds = $target['option_id'] ?? [];

            if ($name === '') {
                continue;
            }

            $parts = explode('_', $name);
            $prefix = $parts[0] ?? '';
            $suffix = isset($parts[1]) ? $this->humanizeQuotaToken($parts[1]) : '';

            $label = $this->getQuotaPrefixLabel($prefix);
            if ($suffix !== '') {
                $label .= ' - ' . $suffix;
            }

            $tooltip = null;

            if ($questionId > 0 && isset($questionMap[$questionId])) {
                $question = $questionMap[$questionId];
                $options = is_array($question['options'] ?? null) ? $question['options'] : [];
                $optionLabels = [];

                if (is_array($optionIds)) {
                    foreach ($optionIds as $optionId) {
                        $optionIndex = (int) $optionId;

                        if (isset($options[$optionIndex])) {
                            $optionLabels[] = trim((string) $options[$optionIndex]);
                        }
                    }
                }

                $questionText = trim((string) ($question['text'] ?? ''));

                if ($questionText !== '' || !empty($optionLabels)) {
                    $tooltip = '<div class="quota-tooltip-card">';

                    if ($questionText !== '') {
                        $tooltip .= '<div class="quota-tooltip-question">' . e($questionText) . '</div>';
                    }

                    if ($questionText !== '' && !empty($optionLabels)) {
                        $tooltip .= '<div class="quota-tooltip-divider"></div>';
                    }

                    if (!empty($optionLabels)) {
                        $tooltip .= '<div class="quota-tooltip-option">' . e(implode(' | ', $optionLabels)) . '</div>';
                    }

                    $tooltip .= '</div>';
                }
            }

            if ($tooltip === null && $description !== '') {
                $tooltip = $description;
            }

            $details[$name] = [
                'label' => $label,
                'tooltip' => $tooltip,
            ];
        }

        return $details;
    }

    private function buildQuestionMap(PrimisApiService $primis, $prj, $sid): array
    {
        $cacheKey = "fieldcontrol_question_map_{$prj}_{$sid}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($primis, $prj, $sid) {
            $response = $primis->listQuestions($prj, $sid);

            if (!isset($response['questions']) || !is_array($response['questions'])) {
                return [];
            }

            $questionMap = [];

            foreach ($response['questions'] as $question) {
                if (!isset($question['id'])) {
                    continue;
                }

                $questionMap[$question['id']] = [
                    'code' => $question['code'] ?? 'Codice Sconosciuto',
                    'text' => $question['text'] ?? 'Testo non disponibile',
                    'options' => $question['options'] ?? [],
                ];
            }

            return $questionMap;
        });
    }

private function getPanelNamesMap(): array
{
    return Cache::remember('fieldcontrol_panel_names_map', now()->addMinutes(30), function () {
        return DB::table('t_fornitoripanel')
            ->orderBy('panel_code')
            ->pluck('name', 'panel_code')
            ->map(function ($name) {
                return trim((string) $name);
            })
            ->toArray();
    });
}

private function getPanelExportConfig(string $panelName): ?object
{
    return DB::table('t_fornitoripanel')
        ->where('name', $panelName)
        ->select('panel_code', 'name', 'red_3', 'red_4', 'red_5')
        ->first();
}

}
