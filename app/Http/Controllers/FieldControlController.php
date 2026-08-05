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
    private const PANEL_MATURITY_HOURS = 24;

public function index(Request $request, PrimisApiService $primis, FieldControlSreService $sreService)
{
    ini_set('memory_limit', '512M');

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

    $bytes = $panelData->bytes ?? 0;

    $this->updatePanelControl($sid, $counts, $abilitati, $panelCounts, $redemption, $bytes);

    $contattiInteractive = (int) ($panelCounts['Interactive']['contatti'] ?? 0);

    $redPanelCorrente = ($abilitati > 0 && isset($panelCounts['Interactive']))
        ? round(($contattiInteractive / $abilitati) * 100, 1)
        : 0.0;

    $panelRateInfo = ((int) $panelValueFromDB === 1)
        ? $this->getPanelRateInfo($panelData, $redPanelCorrente, (float) $mediaRedPanel, $abilitati, $contattiInteractive)
        : null;

    $stimaInterviste = ((int) $panelValueFromDB === 1)
        ? $this->calcolaStimaInterviste(
            $utentiDisponibili,
            $redemption,
            $panelRateInfo ? $panelRateInfo['valore_utilizzato'] : $mediaRedPanel
          )
        : null;

    $irInteractive = (float) ($panelCounts['Interactive']['redemption'] ?? 0);

    $ondateInfo = ((int) $panelValueFromDB === 1)
        ? $this->getOndate24h($sid, $prj, $utentiDisponibili)
        : null;

    $stimaDiagnostica = ($ondateInfo !== null && $panelRateInfo !== null && $irInteractive > 0)
        ? max(0, round(
            $ondateInfo['utenti_effettivi']
            * ($panelRateInfo['valore_utilizzato'] / 100)
            * ($irInteractive / 100)
          ))
        : null;

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
        'panelRateInfo',
        'ondateInfo',
        'stimaDiagnostica',
        'filtrateCountsByPanel',
        'hasFiltrate',
        'quotaData',
        'logData',
        'dataSummaryByPanel',
        'ricercheInCorso',
        'primisSurveyStatus',
        'mediaRedPanel'
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

private function correggiMediaPerEta(float $mediaRedPanel, $age1, $age2): array
{
    $riferimento = 8.0;
    $neutro = [
        'media_originale'  => $mediaRedPanel,
        'benchmark_target' => $riferimento,
        'coefficiente_eta' => 1.0,
        'media_corretta'   => $mediaRedPanel,
    ];

    if (!is_numeric($age1) || !is_numeric($age2)) {
        return $neutro;
    }

    $a1 = max(18, (int) $age1);
    $a2 = min(65, (int) $age2);

    if ($a1 > $a2) {
        return $neutro;
    }

    if ($a1 === 18 && $a2 === 65) {
        return $neutro;
    }

    $brackets = [
        ['min' => 18, 'max' => 24, 'benchmark' => 3.0],
        ['min' => 25, 'max' => 34, 'benchmark' => 4.5],
        ['min' => 35, 'max' => 44, 'benchmark' => 7.5],
        ['min' => 45, 'max' => 65, 'benchmark' => 9.5],
    ];

    $totalEta    = 0;
    $sommaPesata = 0.0;

    foreach ($brackets as $b) {
        $oMin = max($a1, $b['min']);
        $oMax = min($a2, $b['max']);
        if ($oMin <= $oMax) {
            $n             = $oMax - $oMin + 1;
            $totalEta     += $n;
            $sommaPesata  += $n * $b['benchmark'];
        }
    }

    if ($totalEta === 0) {
        return $neutro;
    }

    $benchmarkTarget = round($sommaPesata / $totalEta, 4);
    $coefficiente    = round(min(1.20, max(0.35, $benchmarkTarget / $riferimento)), 4);
    $mediaCorretta   = round($mediaRedPanel * $coefficiente, 2);

    return [
        'media_originale'  => $mediaRedPanel,
        'benchmark_target' => round($benchmarkTarget, 2),
        'coefficiente_eta' => $coefficiente,
        'media_corretta'   => $mediaCorretta,
    ];
}

private function getPanelRateInfo($panelData, float $redPanelCorrente, float $mediaRedPanel, int $abilitati, int $contattiAssoluti = 0): array
{
    $etaInfo = $this->correggiMediaPerEta(
        $mediaRedPanel,
        $panelData->age1_target ?? null,
        $panelData->age2_target ?? null
    );
    $mediaStorica = $etaInfo['media_corretta'];

    $snapshotPrecedente = ($panelData && $panelData->panel_enabled_snapshot !== null)
        ? (int) $panelData->panel_enabled_snapshot
        : null;
    $changedAt         = $panelData ? $panelData->panel_enabled_changed_at : null;
    $abilitatiCambiati = ($snapshotPrecedente === null || $snapshotPrecedente !== $abilitati);

    $secondi            = 0;
    $oreDallaVariazione = null;
    $maturo             = false;

    if ($changedAt !== null && !$abilitatiCambiati) {
        $secondi            = max(0, time() - strtotime($changedAt));
        $oreDallaVariazione = round($secondi / 3600, 1);
        $maturo             = $secondi >= (self::PANEL_MATURITY_HOURS * 3600);
    }

    // (studio + storico) / 2 — media fissa 50/50 solo con almeno 10 contatti
    if ($contattiAssoluti > 10) {
        $valoreUtilizzato = round(($redPanelCorrente + $mediaStorica) / 2, 2);
        $fonte            = $maturo ? 'studio_stabile' : 'studio';
    } else {
        $valoreUtilizzato = $mediaStorica;
        $fonte            = 'storico';
    }

    return [
        'valore_corrente'      => $redPanelCorrente,
        'valore_utilizzato'    => $valoreUtilizzato,
        'fonte'                => $fonte,
        'maturo'               => $maturo,
        'ore_dalla_variazione' => $oreDallaVariazione,
        'eta_info'             => $etaInfo,
    ];
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
    return Cache::remember('fieldcontrol_media_red_panel_v2', now()->addMinutes(30), function () {
        $unAnnoFa = now()->subYear();

        return DB::table('t_panel_control')
            ->where('panel', 1)
            ->whereBetween('red_panel', [5, 20])
            ->where('sur_date', '>=', $unAnnoFa)
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

private function getOndate24h(string $sid, string $prj, int $utentiDisponibili): array
{
    $ventiquattoreFA = now()->subHours(24);

    $waves = DB::table('t_panel_sample_waves')
        ->where('sid', $sid)
        ->where('prj_name', $prj)
        ->where('launched_at', '>=', $ventiquattoreFA)
        ->orderBy('launched_at', 'asc')
        ->get(['users_count', 'launched_at']);

    $dettaglioOndate = [];
    $utentiResiduiOndate = 0;
    $now = now();

    foreach ($waves as $wave) {
        $minutiTrascorsi = (int) $now->diffInMinutes($wave->launched_at);
        $pesoResiduo = max(1.0 - $minutiTrascorsi / 1440.0, 0.0);
        $utentiResidui = (int) round($wave->users_count * $pesoResiduo);

        $dettaglioOndate[] = [
            'launched_at'      => $wave->launched_at,
            'users_count'      => (int) $wave->users_count,
            'minuti_trascorsi' => $minutiTrascorsi,
            'peso_residuo'     => round($pesoResiduo, 4),
            'utenti_residui'   => $utentiResidui,
        ];

        $utentiResiduiOndate += $utentiResidui;
    }

    return [
        'utenti_disponibili'    => $utentiDisponibili,
        'utenti_residui_ondate' => $utentiResiduiOndate,
        'utenti_effettivi'      => $utentiDisponibili + $utentiResiduiOndate,
        'dettaglio_ondate'      => $dettaglioOndate,
    ];
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
            $baseKey = $parts[0];

            if (isset($quotaConfig['target_details'][$quotaName])) {
                return $quotaConfig['target_details'][$quotaName];
            }

            if (isset($quotaConfig['target_details'][$baseKey])) {
                $baseDetail = $quotaConfig['target_details'][$baseKey];
                $baseDetail['label'] = $this->formatSimpleQuotaLabel($parts[0], $parts[1], $quotaName);

                return $baseDetail;
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
                'label' => 'Interviste Totali',
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
                'tooltip' => $legGroups[$legIndex]['tooltip'] ?? null,
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
            $options = [];
            $questionText = '';

            if ($questionId > 0 && isset($questionMap[$questionId])) {
                $question = $questionMap[$questionId];
                $options = is_array($question['options'] ?? null) ? $question['options'] : [];
                $questionText = trim((string) ($question['text'] ?? ''));
                $optionLabels = [];

                if (is_array($optionIds)) {
                    foreach ($optionIds as $optionId) {
                        $optionIndex = (int) $optionId;

                        if (isset($options[$optionIndex])) {
                            $optionLabels[] = trim((string) $options[$optionIndex]);
                        }
                    }
                }

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

            // Per target con più option_id, genera sub-entry pers_0, pers_1 ecc.
            // così formatQuotaName le trova direttamente senza usare il tooltip aggregato
            if ($questionId > 0 && isset($questionMap[$questionId]) && is_array($optionIds) && count($optionIds) > 1) {
                foreach ($optionIds as $idx => $optionId) {
                    $optionIndex = (int) $optionId;
                    $optionText = isset($options[$optionIndex]) ? trim((string) $options[$optionIndex]) : null;

                    $subLabel = $this->formatSimpleQuotaLabel($prefix, (string) $idx, $name . '_' . $idx);

                    $subTooltip = null;
                    if ($questionText !== '' || ($optionText !== null && $optionText !== '')) {
                        $subTooltip = '<div class="quota-tooltip-card">';

                        if ($questionText !== '') {
                            $subTooltip .= '<div class="quota-tooltip-question">' . e($questionText) . '</div>';
                        }

                        if ($questionText !== '' && $optionText !== null && $optionText !== '') {
                            $subTooltip .= '<div class="quota-tooltip-divider"></div>';
                        }

                        if ($optionText !== null && $optionText !== '') {
                            $subTooltip .= '<div class="quota-tooltip-option">' . e($optionText) . '</div>';
                        }

                        $subTooltip .= '</div>';
                    }

                    $details[$name . '_' . $idx] = [
                        'label' => $subLabel,
                        'tooltip' => $subTooltip,
                    ];
                }
            }
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
