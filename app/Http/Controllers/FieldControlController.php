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

/**
 * Tutto cio' che serve per "Stima interviste e disponibilita' panel" +
 * "Fattibilita' per quota": estratto da index() in modo che le route di
 * conferma/rifiuto quota demografica possano ricalcolare e restituire solo
 * questi dati senza un reload completo della pagina (stesso identico
 * calcolo, nessuna duplicazione di formula).
 */
private function buildStimaEFattibilita(string $prj, string $sid, PrimisApiService $primis, FieldControlSreService $sreService): array
{
    $panelData = PanelControl::where('sur_id', $sid)->first();

    $directory = $sreService->resolveResultsDirectory($prj, $sid);

    $questionMap = $this->buildQuestionMap($primis, $prj, $sid);
    $quotaData = $this->getQuotaData($prj, $sid, $sreService, $questionMap);

    $panelValueFromDB = $panelData->panel ?? null;

    $files = $sreService->getSreFiles($directory);
    $interviews = $sreService->buildInterviewDataset($files, $prj, $sid);

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
        ? round((($counts['complete'] + $counts['bad_quality']) / $denominator) * 100, 2)
        : 0;

    foreach ($panelCounts as $panelName => &$panel) {
        $panelDenominator = $panel['contatti'] - $panel['sospese'] - $panel['bloccate'] - $panel['over_quota'];

        $panel['redemption'] = ($panelDenominator > 0)
            ? round((($panel['complete'] + $panel['bad_quality']) / $panelDenominator) * 100, 2)
            : 0;
    }
    unset($panel);

    $panelCounts = $this->sortPanelsForDisplay($panelCounts);

    $utentiDisponibili = $this->getUtentiDisponibili($sid, $panelData);
    $mediaRedPanel = $this->calcolaMediaRedPanel();

    $bytes = $panelData->bytes ?? 0;

    // Una ricerca chiusa (stato=1) non va piu' aggiornata: il pool di persone
    // abilitate decade nel tempo (riusato da altre ricerche), quindi riaprire
    // questa pagina rovinerebbe il dato storico gia' congelato alla chiusura
    // (vedi changeSurveyStatus()). La pagina continua a mostrare i numeri live
    // calcolati dai file .sre, solo la persistenza su DB viene saltata.
    if ((int) ($panelData->stato ?? 0) !== 1) {
        $this->updatePanelControl($sid, $counts, $abilitati, $panelCounts, $redemption, $bytes);
    }

    $contattiInteractive = (int) ($panelCounts['Interactive']['contatti'] ?? 0);

    $redPanelCorrente = ($abilitati > 0 && isset($panelCounts['Interactive']))
        ? round(($contattiInteractive / $abilitati) * 100, 1)
        : 0.0;

    $panelRateInfo = ((int) $panelValueFromDB === 1)
        ? $this->getPanelRateInfo($panelData, $redPanelCorrente, (float) $mediaRedPanel, $abilitati, $contattiInteractive)
        : null;

    $irInteractive = (float) ($panelCounts['Interactive']['redemption'] ?? 0);

    $stimaInterviste = ((int) $panelValueFromDB === 1)
        ? $this->calcolaStimaInterviste(
            $utentiDisponibili,
            $irInteractive,
            $panelRateInfo ? $panelRateInfo['valore_utilizzato'] : $mediaRedPanel
          )
        : null;

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

    $fattibilitaQuote = $this->calcolaFattibilitaQuote(
        $quotaData,
        $sid,
        $panelData,
        $counts,
        $stimaInterviste,
        $panelRateInfo,
        $irInteractive,
        $utentiDisponibili
    );

    return [
        'panelData' => $panelData,
        'questionMap' => $questionMap,
        'interviews' => $interviews,
        'counts' => $counts,
        'panelCounts' => $panelCounts,
        'abilitati' => $abilitati,
        'redemption' => $redemption,
        'utentiDisponibili' => $utentiDisponibili,
        'mediaRedPanel' => $mediaRedPanel,
        'panelRateInfo' => $panelRateInfo,
        'irInteractive' => $irInteractive,
        'stimaInterviste' => $stimaInterviste,
        'ondateInfo' => $ondateInfo,
        'stimaDiagnostica' => $stimaDiagnostica,
        'quotaData' => $fattibilitaQuote['quotaData'],
        'stimaVincolataDaQuote' => $fattibilitaQuote['stima_vincolata'],
        'quotaVincolante' => $fattibilitaQuote['quota_vincolante'],
    ];
}

public function index(Request $request, PrimisApiService $primis, FieldControlSreService $sreService)
{
    ini_set('memory_limit', '512M');

    $prj = $request->query('prj');
    $sid = $request->query('sid');

    $stima = $this->buildStimaEFattibilita($prj, $sid, $primis, $sreService);

    $panelData = $stima['panelData'];
    $questionMap = $stima['questionMap'];
    $interviews = $stima['interviews'];
    $counts = $stima['counts'];
    $panelCounts = $stima['panelCounts'];
    $abilitati = $stima['abilitati'];
    $redemption = $stima['redemption'];
    $utentiDisponibili = $stima['utentiDisponibili'];
    $mediaRedPanel = $stima['mediaRedPanel'];
    $panelRateInfo = $stima['panelRateInfo'];
    $irInteractive = $stima['irInteractive'];
    $stimaInterviste = $stima['stimaInterviste'];
    $ondateInfo = $stima['ondateInfo'];
    $stimaDiagnostica = $stima['stimaDiagnostica'];
    $quotaData = $stima['quotaData'];
    $stimaVincolataDaQuote = $stima['stimaVincolataDaQuote'];
    $quotaVincolante = $stima['quotaVincolante'];

    $redirections = $sreService->getRedirectionsList($prj, $sid);
    $quotaTargetsData = $sreService->getQuotaTargetsList($prj, $sid);
    $panelNamesMap = $this->getPanelNamesMap();

    /*
    |--------------------------------------------------------------------------
    | QUESTION MAP + FILTRATE
    |--------------------------------------------------------------------------
    */
    // Icone e colori coerenti con la card "Distribuzione esiti" piu' in alto
    // nella pagina ($outcomeIcons / classi fc-dist-* in fieldControl.blade.php):
    // stessa semantica visiva per lo stesso stato intervista in tutta la pagina.
    $exitStatusTabs = [
        4 => ['key' => 'screenout', 'label' => 'Screenout', 'title' => 'Cause di screenout', 'subtitle' => 'principali domande di screenout', 'icon' => 'fa-bullseye', 'emptyTitle' => 'Nessuna intervista filtrata da analizzare', 'emptySubtitle' => "Quando ci saranno screenout, qui vedrai le domande che stanno filtrando maggiormente."],
        5 => ['key' => 'quotafull', 'label' => 'Quotafull', 'title' => 'Cause di quotafull', 'subtitle' => 'principali domande di quotafull', 'icon' => 'fa-arrow-trend-up', 'emptyTitle' => 'Nessuna intervista in quotafull da analizzare', 'emptySubtitle' => "Quando ci saranno interviste in quotafull, qui vedrai su quali domande si sono fermate."],
        7 => ['key' => 'bloccate', 'label' => 'Bloccate', 'title' => 'Cause di blocco', 'subtitle' => 'principali domande di blocco', 'icon' => 'fa-lock', 'emptyTitle' => 'Nessuna intervista bloccata da analizzare', 'emptySubtitle' => "Quando ci saranno interviste bloccate, qui vedrai su quali domande si sono fermate."],
        0 => ['key' => 'sospese', 'label' => 'Sospese', 'title' => 'Domanda sospese', 'subtitle' => 'principali domande di sospensione', 'icon' => 'fa-pause', 'emptyTitle' => 'Nessuna intervista sospesa da analizzare', 'emptySubtitle' => "Quando ci saranno interviste in corso/sospese, qui vedrai a che domanda sono ferme."],
    ];

    $exitCountsByStatus = $sreService->buildQuestionCountsByStatusFromInterviews($interviews, $questionMap, array_keys($exitStatusTabs));

    $hasExitDataByStatus = [];

    foreach ($exitStatusTabs as $statusCode => $meta) {
        $exitCountsByStatus[$statusCode] = $this->sortPanelsForDisplay($exitCountsByStatus[$statusCode] ?? []);

        $hasData = false;
        foreach ($exitCountsByStatus[$statusCode] as $rows) {
            if (!empty($rows) && array_sum($rows) > 0) {
                $hasData = true;
                break;
            }
        }
        $hasExitDataByStatus[$statusCode] = $hasData;
    }

    // Stessi dati di $exitCountsByStatus, ma chiave = $meta['key'] (es. "screenout")
    // invece dello status_code numerico: usata lato JS per costruire gli id dei
    // canvas (chart-{key}-{panel}), piu' leggibile che usare direttamente il codice.
    $exitCountsByKey = [];
    foreach ($exitStatusTabs as $statusCode => $meta) {
        $exitCountsByKey[$meta['key']] = $exitCountsByStatus[$statusCode];
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
        'irInteractive',
        'panelCounts',
        'utentiDisponibili',
        'stimaInterviste',
        'panelRateInfo',
        'ondateInfo',
        'stimaDiagnostica',
        'exitStatusTabs',
        'exitCountsByStatus',
        'exitCountsByKey',
        'hasExitDataByStatus',
        'quotaData',
        'stimaVincolataDaQuote',
        'quotaVincolante',
        'logData',
        'dataSummaryByPanel',
        'ricercheInCorso',
        'primisSurveyStatus',
        'mediaRedPanel',
        'redirections',
        'quotaTargetsData',
        'panelNamesMap',
        'questionMap'
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
    $interviews = $sreService->buildInterviewDataset($files, $prj, $sid, true);

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

public function searchLog(Request $request, PrimisApiService $primis, FieldControlSreService $sreService)
{
    ini_set('memory_limit', '512M');

    $prj = $request->query('prj');
    $sid = $request->query('sid');
    $iidFilter = trim((string) $request->query('iid', ''));
    $uidFilter = trim((string) $request->query('uid', ''));
    $panelFilter = trim((string) $request->query('panel', ''));

    $directory = $sreService->resolveResultsDirectory($prj, $sid);

    if (!$directory) {
        return response()->json(['success' => false, 'message' => 'Directory dei file .sre non trovata.'], 404);
    }

    $files = $sreService->getSreFiles($directory);
    $questionMap = $this->buildQuestionMap($primis, $prj, $sid);

    // Dataset completo (nessun cap): a differenza della vista di default
    // (ultime 1000 interviste), la ricerca deve coprire tutta la ricerca.
    $interviews = $sreService->buildInterviewDataset($files, $prj, $sid);

    if ($iidFilter !== '') {
        $interviews = array_filter($interviews, function ($interview) use ($iidFilter) {
            return stripos((string) ($interview['iid'] ?? ''), $iidFilter) !== false;
        });
    }

    if ($uidFilter !== '') {
        $interviews = array_filter($interviews, function ($interview) use ($uidFilter) {
            return stripos((string) ($interview['uid'] ?? ''), $uidFilter) !== false;
        });
    }

    if ($panelFilter !== '') {
        $interviews = array_filter($interviews, function ($interview) use ($panelFilter) {
            return (string) ($interview['panel'] ?? '') === $panelFilter;
        });
    }

    $interviews = array_values($interviews);
    $totalMatches = count($interviews);

    // Stesso tetto di sicurezza della vista di default (1000 righe renderizzate),
    // qui applicato pero' DOPO il filtro, quindi sui risultati della ricerca e
    // non sulle ultime 1000 interviste della ricerca.
    $logData = $sreService->buildLogDataFromInterviews($interviews, $questionMap, 1000);

    $html = view('partials.field-log-rows', [
        'logData' => $logData,
        'emptyTitle' => 'Nessun risultato',
        'emptySubtitle' => 'Nessuna intervista corrisponde ai filtri impostati.',
    ])->render();

    return response()->json([
        'success' => true,
        'total' => $totalMatches,
        'shown' => count($logData),
        'html' => $html,
    ]);
}

    public function closeSurvey(Request $request, PrimisApiService $primis)
    {
        return $this->changeSurveyStatus($request, $primis, 0, 4, 1, 'La ricerca è già chiusa.');
    }

    public function reopenSurvey(Request $request, PrimisApiService $primis)
    {
        return $this->changeSurveyStatus($request, $primis, 1, 2, 0, 'La ricerca non è chiusa.');
    }

    /**
     * Cambia lo status del survey su Primis e, solo se Primis conferma,
     * allinea t_panel_control.stato. Se Primis fallisce (comunicazione o
     * success:false), lo stato locale NON viene toccato.
     */
    private function changeSurveyStatus(
        Request $request,
        PrimisApiService $primis,
        int $requiredLocalStato,
        int $primisStatus,
        int $newLocalStato,
        string $alreadyMessage
    ) {
        $prj = $request->input('prj');
        $sid = $request->input('sid');

        $survey = DB::table('t_panel_control')
            ->where('prj', $prj)
            ->where('sur_id', $sid)
            ->first();

        if (!$survey) {
            return response()->json(['success' => false, 'message' => 'Ricerca non trovata.'], 404);
        }

        if ((int) $survey->stato !== $requiredLocalStato) {
            return response()->json(['success' => false, 'message' => $alreadyMessage], 400);
        }

        try {
            $primisResponse = $primis->updateSurveyStatus($prj, $sid, $primisStatus);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore di comunicazione con Primis: ' . $e->getMessage(),
            ], 502);
        }

        if (!($primisResponse['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Primis ha rifiutato la richiesta: ' . ($primisResponse['error_message'] ?? 'errore sconosciuto'),
            ], 502);
        }

        DB::table('t_panel_control')
            ->where('prj', $prj)
            ->where('sur_id', $sid)
            ->update(['stato' => $newLocalStato]);

        if ($newLocalStato === 1) {
            // Chiusura: congela i numeri di adesso, prima che il pool di
            // persone abilitate (t_respint) venga riutilizzato da altre
            // ricerche e il dato storico diventi illeggibile.
            DB::table('t_panel_control')
                ->where('prj', $prj)
                ->where('sur_id', $sid)
                ->update([
                    'frozen_at' => now(),
                    'frozen_abilitati' => $survey->abilitati,
                    'frozen_contatti_interactive' => $survey->abilitati > 0
                        ? (int) round($survey->abilitati * $survey->red_panel / 100)
                        : 0,
                    'frozen_complete_interactive' => $survey->complete_int,
                ]);
        } elseif ($newLocalStato === 0) {
            // Riapertura: la ricerca torna live, lo snapshot congelato non e' piu' valido.
            DB::table('t_panel_control')
                ->where('prj', $prj)
                ->where('sur_id', $sid)
                ->update([
                    'frozen_at' => null,
                    'frozen_abilitati' => null,
                    'frozen_contatti_interactive' => null,
                    'frozen_complete_interactive' => null,
                ]);
        }

        return response()->json(['success' => true]);
    }

    public function parseRedirectionsText(Request $request, FieldControlSreService $sreService)
    {
        $text = (string) $request->input('text', '');

        if (trim($text) === '') {
            return response()->json(['success' => false, 'message' => 'Incolla prima il testo da analizzare.'], 422);
        }

        $candidates = $sreService->parseRedirectionText($text);

        if (empty($candidates)) {
            return response()->json(['success' => false, 'message' => 'Non ho riconosciuto nessuna redirection in questo testo.'], 422);
        }

        return response()->json(['success' => true, 'candidates' => $candidates]);
    }

    public function addRedirections(Request $request, FieldControlSreService $sreService)
    {
        $prj = $request->input('prj');
        $sid = $request->input('sid');
        $entries = $request->input('entries', []);

        if (!is_array($entries)) {
            return response()->json(['success' => false, 'message' => 'Formato non valido.'], 422);
        }

        $result = $sreService->addRedirections($prj, $sid, $entries);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function deleteRedirectionsGroup(Request $request, FieldControlSreService $sreService)
    {
        $prj = $request->input('prj');
        $sid = $request->input('sid');
        $parameterName = (string) $request->input('parameter_name', 'pan');
        $parameterValue = (string) $request->input('parameter_value', '');

        if ($parameterValue === '') {
            return response()->json(['success' => false, 'message' => 'Panel non specificato.'], 422);
        }

        $result = $sreService->deleteRedirectionsGroup($prj, $sid, $parameterName, $parameterValue);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function updateRedirections(Request $request, FieldControlSreService $sreService)
    {
        $prj = $request->input('prj');
        $sid = $request->input('sid');
        $updates = $request->input('updates', []);

        if (!is_array($updates)) {
            return response()->json(['success' => false, 'message' => 'Formato aggiornamenti non valido.'], 422);
        }

        $result = $sreService->updateRedirections($prj, $sid, $updates);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function addQuotaTarget(Request $request, FieldControlSreService $sreService)
    {
        $prj = $request->input('prj');
        $sid = $request->input('sid');
        $entry = $request->input('entry', []);

        if (!is_array($entry)) {
            return response()->json(['success' => false, 'message' => 'Formato non valido.'], 422);
        }

        $result = $sreService->addQuotaTarget($prj, $sid, $entry);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function updateQuotaTarget(Request $request, FieldControlSreService $sreService)
    {
        $prj = $request->input('prj');
        $sid = $request->input('sid');
        $index = (int) $request->input('index', -1);
        $entry = $request->input('entry', []);

        if ($index < 0 || !is_array($entry)) {
            return response()->json(['success' => false, 'message' => 'Formato non valido.'], 422);
        }

        $result = $sreService->updateQuotaTarget($prj, $sid, $index, $entry);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function deleteQuotaTarget(Request $request, FieldControlSreService $sreService)
    {
        $prj = $request->input('prj');
        $sid = $request->input('sid');
        $index = (int) $request->input('index', -1);

        if ($index < 0) {
            return response()->json(['success' => false, 'message' => 'Quota non specificata.'], 422);
        }

        $result = $sreService->deleteQuotaTarget($prj, $sid, $index);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * L'operatore conferma (o corregge) il tipo demografico suggerito per una
     * quota: da questo momento viene usata per stimare quante persone
     * disponibili rientrano davvero in quella quota (vedi calcolaFattibilitaQuote()).
     */
    public function confirmQuotaDemographic(Request $request, PrimisApiService $primis, FieldControlSreService $sreService)
    {
        $validated = $request->validate([
            'prj' => 'required|string',
            'sid' => 'required|string',
            'quota_name' => 'required|string',
            'tipo' => 'required|in:sesso,eta,area',
            'filtro' => 'required|array',
        ]);

        DB::table('t_quota_demographic_mapping')->updateOrInsert(
            ['sid' => $validated['sid'], 'quota_name' => $validated['quota_name']],
            [
                'prj' => $validated['prj'],
                'tipo' => $validated['tipo'],
                'filtro' => json_encode($validated['filtro']),
                'confirmed_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return $this->rispostaFattibilitaQuote($validated['prj'], $validated['sid'], $primis, $sreService);
    }

    /**
     * L'operatore dice esplicitamente che una quota NON è demografica
     * (es. legata a una risposta del sondaggio): smette di riproporla come
     * suggerimento, passa alla stima generica basata sul ritmo di riempimento.
     */
    public function rejectQuotaDemographic(Request $request, PrimisApiService $primis, FieldControlSreService $sreService)
    {
        $validated = $request->validate([
            'prj' => 'required|string',
            'sid' => 'required|string',
            'quota_name' => 'required|string',
        ]);

        DB::table('t_quota_demographic_mapping')->updateOrInsert(
            ['sid' => $validated['sid'], 'quota_name' => $validated['quota_name']],
            [
                'prj' => $validated['prj'],
                'tipo' => 'none',
                'filtro' => null,
                'confirmed_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return $this->rispostaFattibilitaQuote($validated['prj'], $validated['sid'], $primis, $sreService);
    }

    /**
     * Conferma in un colpo solo tutti i suggerimenti sesso/età ancora in
     * attesa per questa ricerca (bottone "Conferma tutte" nella modale).
     */
    public function confirmAllQuotaDemographic(Request $request, PrimisApiService $primis, FieldControlSreService $sreService)
    {
        $validated = $request->validate([
            'prj' => 'required|string',
            'sid' => 'required|string',
        ]);

        $prj = $validated['prj'];
        $sid = $validated['sid'];

        $questionMap = $this->buildQuestionMap($primis, $prj, $sid);
        $quotaData = $this->getQuotaData($prj, $sid, $sreService, $questionMap);
        $mappingRows = DB::table('t_quota_demographic_mapping')->where('sid', $sid)->pluck('quota_name')->all();

        $now = now();
        $batch = [];

        foreach ($quotaData as $quota) {
            if ($quota->quota === 'Interviste Totali' || in_array($quota->quota_raw, $mappingRows, true)) {
                continue;
            }

            $suggestion = $this->guessQuotaDemographic($quota->quota_raw);

            if ($suggestion === null) {
                continue;
            }

            $batch[] = [
                'prj' => $prj,
                'sid' => $sid,
                'quota_name' => $quota->quota_raw,
                'tipo' => $suggestion['tipo'],
                'filtro' => json_encode($suggestion['filtro']),
                'confirmed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($batch)) {
            DB::table('t_quota_demographic_mapping')->insert($batch);
        }

        return $this->rispostaFattibilitaQuote($prj, $sid, $primis, $sreService);
    }

    /**
     * Ricalcola "Stima interviste e disponibilita' panel" + fattibilita' per
     * quota e restituisce tutto cio' che serve al JS per aggiornare la
     * pagina senza un reload completo, dopo conferma/rifiuto di un
     * suggerimento (vedi public/js/fieldControl.js::bindQuotaDemographicConfirm()).
     */
    private function rispostaFattibilitaQuote(string $prj, string $sid, PrimisApiService $primis, FieldControlSreService $sreService)
    {
        $stima = $this->buildStimaEFattibilita($prj, $sid, $primis, $sreService);

        $quotaData = $stima['quotaData'];
        $stimaAggiuntive = $stima['stimaVincolataDaQuote'] ?? $stima['stimaInterviste'] ?? 0;
        // Totale generale (tutti i panel), coerente con "Già raccolte" in
        // fieldControl.blade.php: una quota raggiunta resta raggiunta
        // indipendentemente da quale panel l'ha coperta.
        $raccolte = (int) ($stima['counts']['complete'] ?? 0);

        $rowsHtml = view('partials.field-quota-fattibilita-rows', [
            'quotaData' => $quotaData,
            'quotaVincolante' => $stima['quotaVincolante'],
        ])->render();

        $haSuggerimentiInAttesa = $quotaData->contains(function ($quota) {
            return !empty($quota->demographic_suggestion);
        });

        return response()->json([
            'success' => true,
            'rows_html' => $rowsHtml,
            'stima_aggiuntive' => $stimaAggiuntive,
            'max_raggiungibile' => $raccolte + $stimaAggiuntive,
            'quota_vincolante' => $stima['quotaVincolante'],
            'ha_suggerimenti_in_attesa' => $haSuggerimentiInAttesa,
        ]);
    }

    public function updateQuotaTotalInterviews(Request $request, FieldControlSreService $sreService)
    {
        $prj = $request->input('prj');
        $sid = $request->input('sid');
        $value = $request->input('value');

        if ($value === null || $value === '' || !is_numeric($value)) {
            return response()->json(['success' => false, 'message' => 'Valore non valido.'], 422);
        }

        $result = $sreService->updateQuotaTotalInterviews($prj, $sid, (int) $value);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Pubblica il config.json corrente (con le quote aggiornate) su Primis
     * tramite l'Upload File API, cosi' che Primis possa risincronizzare
     * t_quota_status. Il file e' gia' sul filesystem che Primis legge
     * (stesso path usato per le redirection): questa chiamata serve solo
     * a far rilevare a Primis la modifica, non a "farci arrivare" il file.
     */
    public function publishQuotaConfig(Request $request, FieldControlSreService $sreService, PrimisApiService $primis)
    {
        $prj = $request->input('prj');
        $sid = $request->input('sid');

        $content = $sreService->readConfigJsonRaw($prj, $sid);

        if ($content === null) {
            return response()->json(['success' => false, 'message' => 'File config.json non trovato o non leggibile.'], 404);
        }

        try {
            $response = $primis->uploadFile($prj, $sid, 'config.json', $content);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Errore di comunicazione con Primis: ' . $e->getMessage()], 502);
        }

        if (!($response['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Primis ha rifiutato la pubblicazione: ' . ($response['error_message'] ?? 'errore sconosciuto'),
            ], 422);
        }

        return response()->json(['success' => true, 'message' => 'config.json pubblicato su Primis.']);
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

/**
 * Come getUtentiDisponibili(), ma applicando in piu' il filtro di una
 * singola quota (sesso/eta/area) confermato dall'operatore - usato per
 * stimare quante persone disponibili rientrano in QUELLA quota specifica,
 * non nel target generico della ricerca.
 */
private function contaDisponibiliConFiltro($sid, $panelTarget, array $filtro): int
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
    }

    if (!empty($filtro['gender'])) {
        $genderFilter = array_values(array_intersect($genderFilter, (array) $filtro['gender']))
            ?: (array) $filtro['gender'];
    }

    $etaMin = (int) $panelTarget->age1_target;
    $etaMax = (int) $panelTarget->age2_target;

    if (isset($filtro['eta_min'])) {
        $etaMin = max($etaMin, (int) $filtro['eta_min']);
    }

    if (isset($filtro['eta_max'])) {
        $etaMax = min($etaMax, (int) $filtro['eta_max']);
    }

    if ($etaMin > $etaMax) {
        return 0;
    }

    $annoCorrente = date('Y');

    $query = DB::table('t_user_info')
        ->whereIn('gender', $genderFilter)
        ->whereRaw("YEAR(birth_date) BETWEEN ? AND ?", [$annoCorrente - $etaMax, $annoCorrente - $etaMin])
        ->where('active', 1)
        ->where('confirm', 1)
        ->whereNotExists(function ($query) use ($sid) {
            $query->select(DB::raw(1))
                ->from('t_respint')
                ->whereRaw('t_respint.uid = t_user_info.user_id')
                ->where('t_respint.sid', $sid);
        });

    if (!empty($filtro['area'])) {
        $query->whereIn('area', (array) $filtro['area']);
    }

    return $query->count();
}

/**
 * Conta i disponibili rimasti rispettando TUTTE le dimensioni demografiche
 * confermate insieme (sesso, eta', area), non una alla volta: se sesso ed
 * eta' hanno entrambe dei segmenti chiusi, una stessa persona puo' rientrare
 * nel segmento chiuso di entrambe le dimensioni contemporaneamente - sottrarre
 * il "chiuso" di ogni dimensione separatamente (come faceva la versione
 * precedente) la conterebbe due volte, svuotando il pool molto piu' del
 * dovuto. Qui invece si costruisce un'unica query: per ogni dimensione
 * confermata, resta valido solo chi rientra in uno dei segmenti ANCORA
 * APERTI di quella dimensione (OR tra i segmenti aperti), e le dimensioni si
 * combinano tra loro in AND.
 *
 * @param array $confirmatiPerTipo tipo => [ ['filtro'=>array, 'open'=>bool], ... ]
 */
private function contaDisponibiliCorretti(string $sid, $panelTarget, array $confirmatiPerTipo, int $utentiDisponibiliBase): int
{
    if (empty($confirmatiPerTipo) || !$panelTarget) {
        return $utentiDisponibiliBase;
    }

    // Se per una dimensione TUTTI i segmenti confermati sono chiusi, nessuno
    // puo' piu' essere accettato su quella dimensione: il pool realmente
    // disponibile e' zero, qualunque sia lo stato delle altre dimensioni.
    foreach ($confirmatiPerTipo as $righe) {
        if (empty(array_filter($righe, fn ($r) => $r['open']))) {
            return 0;
        }
    }

    $genderFilter = [1, 2];

    switch ((int) $panelTarget->sex_target) {
        case 1:
            $genderFilter = [1];
            break;
        case 2:
            $genderFilter = [2];
            break;
    }

    $etaMin = (int) $panelTarget->age1_target;
    $etaMax = (int) $panelTarget->age2_target;
    $annoCorrente = (int) date('Y');

    $query = DB::table('t_user_info')
        ->where('active', 1)
        ->where('confirm', 1)
        ->whereNotExists(function ($query) use ($sid) {
            $query->select(DB::raw(1))
                ->from('t_respint')
                ->whereRaw('t_respint.uid = t_user_info.user_id')
                ->where('t_respint.sid', $sid);
        });

    if (isset($confirmatiPerTipo['sesso'])) {
        $generiAperti = [];
        foreach ($confirmatiPerTipo['sesso'] as $riga) {
            if ($riga['open']) {
                $generiAperti = array_merge($generiAperti, (array) ($riga['filtro']['gender'] ?? []));
            }
        }
        $query->whereIn('gender', array_values(array_unique($generiAperti)) ?: $genderFilter);
    } else {
        $query->whereIn('gender', $genderFilter);
    }

    if (isset($confirmatiPerTipo['eta'])) {
        $query->where(function ($q) use ($confirmatiPerTipo, $annoCorrente) {
            foreach ($confirmatiPerTipo['eta'] as $riga) {
                if (!$riga['open']) {
                    continue;
                }

                $min = (int) ($riga['filtro']['eta_min'] ?? 0);
                $max = (int) ($riga['filtro']['eta_max'] ?? 120);
                $q->orWhereRaw('YEAR(birth_date) BETWEEN ? AND ?', [$annoCorrente - $max, $annoCorrente - $min]);
            }
        });
    } else {
        $query->whereRaw('YEAR(birth_date) BETWEEN ? AND ?', [$annoCorrente - $etaMax, $annoCorrente - $etaMin]);
    }

    if (isset($confirmatiPerTipo['area'])) {
        $areeAperte = [];
        foreach ($confirmatiPerTipo['area'] as $riga) {
            if ($riga['open']) {
                $areeAperte = array_merge($areeAperte, (array) ($riga['filtro']['area'] ?? []));
            }
        }
        if (!empty($areeAperte)) {
            $query->whereIn('area', array_values(array_unique($areeAperte)));
        }
    }

    return $query->count();
}

/**
 * Prova a riconoscere se una quota riguarda sesso o eta' dalla convenzione
 * del suo nome (es. "genere_U", "age_5565"). E' solo un suggerimento: va
 * sempre confermato dall'operatore prima di essere usato nei calcoli -
 * i nomi delle quote non seguono una convenzione garantita su tutte le
 * ricerche (altrove si sono viste quote come "gdo_muffin_0" che non
 * c'entrano nulla con l'anagrafica).
 */
private function guessQuotaDemographic(string $quotaName): ?array
{
    $parts = explode('_', $quotaName);

    if (count($parts) < 2) {
        return null;
    }

    $prefix = strtolower($parts[0]);
    $value = $parts[1];

    if (in_array($prefix, ['gener', 'genere', 'sesso'], true)) {
        $v = strtoupper($value);

        if ($v === 'U' || $v === 'M' || $v === '0') {
            return ['tipo' => 'sesso', 'filtro' => ['gender' => [1]], 'label' => 'Uomo'];
        }

        if ($v === 'D' || $v === 'F' || $v === '1') {
            return ['tipo' => 'sesso', 'filtro' => ['gender' => [2]], 'label' => 'Donna'];
        }
    }

    if (in_array($prefix, ['age', 'eta', 'età'], true) && ctype_digit($value) && strlen($value) === 4) {
        $etaMin = (int) substr($value, 0, 2);
        $etaMax = (int) substr($value, 2, 2);

        if ($etaMin > 0 && $etaMax >= $etaMin) {
            return [
                'tipo' => 'eta',
                'filtro' => ['eta_min' => $etaMin, 'eta_max' => $etaMax],
                'label' => $etaMin . '-' . $etaMax . ' anni',
            ];
        }
    }

    return null;
}

/**
 * Aggiunge, se possibile, un vincolo "generico" per una quota non
 * agganciata a un dato anagrafico: l'incidenza (entrate/contatti totali)
 * rappresenta "quante volte su un contatto qualsiasi l'intervista finisce
 * completa E in questa specifica quota" - gia' comprensiva del tasso di
 * completamento, non va quindi moltiplicata di nuovo per l'IR del
 * campione altrove (vedi calcolaFattibilitaQuote). Serve sia per il
 * numero della singola quota, sia per capire a che punto quella quota si
 * esaurirebbe (vincolo sul totale).
 */
private function aggiungiVincoloGenerico($item, int $totaleContatti, array &$vincoliGenerici): void
{
    if ($totaleContatti <= 0 || (int) $item->entrate <= 0 || (int) $item->missing <= 0) {
        return;
    }

    $incidenza = $item->entrate / $totaleContatti;

    if ($incidenza <= 0) {
        return;
    }

    $item->fattibilita_incidenza = round($incidenza * 100, 1);

    $vincoliGenerici[] = [
        'cap' => $item->missing / $incidenza,
        'label' => $item->quota,
    ];
}

/**
 * Arricchisce $quotaData con la fattibilita' per quota (demografica
 * confermata, suggerita, o generica) e ricalcola il totale di
 * "Stima aggiuntive" tenendo conto delle quote che lo vincolano:
 * - quote demografiche confermate e gia' chiuse (missing=0) escludono i
 *   loro disponibili dal conteggio usato per il totale (es. "donne" piena
 *   -&gt; il totale si basa solo sugli uomini disponibili);
 * - quote generiche proiettano il ritmo di riempimento osservato finora.
 */
private function calcolaFattibilitaQuote(
    $quotaData,
    string $sid,
    $panelTarget,
    array $counts,
    ?int $stimaInterviste,
    ?array $panelRateInfo,
    float $irInteractive,
    int $utentiDisponibili
): array {
    $result = [
        'quotaData' => $quotaData,
        'stima_vincolata' => $stimaInterviste,
        'quota_vincolante' => null,
    ];

    if ($stimaInterviste === null || $panelRateInfo === null || $quotaData->isEmpty()) {
        return $result;
    }

    $mappingRows = DB::table('t_quota_demographic_mapping')->where('sid', $sid)->get()->keyBy('quota_name');
    $valoreUtilizzato = (float) $panelRateInfo['valore_utilizzato'];
    $totaleContatti = (int) ($counts['contatti'] ?? 0);

    $confirmatiPerTipo = [];
    $vincoliGenerici = [];

    $quotaData = $quotaData->map(function ($item) use (
        $mappingRows, $sid, $panelTarget, $totaleContatti, $valoreUtilizzato, $irInteractive,
        &$confirmatiPerTipo, &$vincoliGenerici
    ) {
        $rawName = $item->quota_raw;
        $mapping = $mappingRows->get($rawName);

        $item->demographic_confirmed = null;
        $item->demographic_suggestion = null;
        $item->fattibilita = null;
        $item->fattibilita_tipo = null;

        if ($item->quota === 'Interviste Totali') {
            // E' l'obiettivo generale della ricerca (stesso concetto di
            // "Max raggiungibile" mostrato altrove in pagina), non una vera
            // quota su un sottoinsieme di persone: non deve entrare nel
            // meccanismo di vincolo sul totale, ne' avere una sua "fattibilita'".
            $item->fattibilita_tipo = 'obiettivo_generale';
        } elseif ($mapping && $mapping->tipo !== 'none') {
            $filtro = json_decode($mapping->filtro, true) ?: [];
            $disponibiliQuota = $this->contaDisponibiliConFiltro($sid, $panelTarget, $filtro);
            $stimaRiga = max(0, round($disponibiliQuota * ($valoreUtilizzato / 100) * ($irInteractive / 100)));

            $item->demographic_confirmed = ['tipo' => $mapping->tipo, 'filtro' => $filtro];
            $item->fattibilita_tipo = $mapping->tipo;
            $item->fattibilita = (int) min($stimaRiga, $item->missing);

            // Tiene traccia di OGNI segmento confermato (aperto o chiuso) per
            // poter poi contare i disponibili davvero rimasti con una query
            // unica che incrocia le dimensioni (AND tra sesso/eta', OR tra i
            // segmenti ancora aperti della stessa dimensione) - sottrarre il
            // "chiuso" di ogni dimensione in modo indipendente conterebbe più
            // volte le stesse persone quando piu' dimensioni sono confermate.
            $confirmatiPerTipo[$mapping->tipo][] = [
                'filtro' => $filtro,
                'open' => (int) $item->missing > 0,
                'missing' => (int) $item->missing,
            ];
        } elseif ($mapping && $mapping->tipo === 'none') {
            $item->fattibilita_tipo = 'generica';
            $this->aggiungiVincoloGenerico($item, $totaleContatti, $vincoliGenerici);
        } else {
            $item->demographic_suggestion = $this->guessQuotaDemographic($rawName);
            $item->fattibilita_tipo = 'generica';
            $this->aggiungiVincoloGenerico($item, $totaleContatti, $vincoliGenerici);
        }

        return $item;
    });

    $utentiDisponibiliCorretti = $this->contaDisponibiliCorretti($sid, $panelTarget, $confirmatiPerTipo, $utentiDisponibili);

    $stimaBase = $stimaInterviste;
    $quotaVincolante = null;

    if ($utentiDisponibiliCorretti !== $utentiDisponibili) {
        $stimaBase = $this->calcolaStimaInterviste($utentiDisponibiliCorretti, $irInteractive, $valoreUtilizzato);
        $quotaVincolante = 'Una o più quote demografiche sono già piene: la stima considera solo i segmenti ancora aperti.';

        // Oltre a quanti il panel puo' fisicamente fornire, c'e' un secondo
        // tetto: se una dimensione (es. eta') ha un SOLO segmento ancora
        // aperto, quel segmento non puo' comunque accettare piu' interviste
        // di quante gliene mancano (il suo "missing"). Una volta raggiunto,
        // quella dimensione si chiude anche per le combinazioni con l'altra
        // dimensione ancora aperta (es. "uomo") - che a quel punto non ha
        // piu' nessuna combinazione disponibile per completare le proprie,
        // restando scoperta. Il vero tetto e' quindi il piu' piccolo tra i
        // "missing" di ogni dimensione con un solo segmento aperto.
        foreach ($confirmatiPerTipo as $righe) {
            $aperti = array_values(array_filter($righe, fn ($r) => $r['open']));

            if (count($aperti) === 1 && $aperti[0]['missing'] < $stimaBase) {
                $stimaBase = $aperti[0]['missing'];
                $quotaVincolante = 'Una o più quote demografiche sono già piene: la stima considera solo i segmenti ancora aperti, limitati dalla dimensione più stretta.';
            }
        }
    }

    $stimaFinale = $stimaBase;

    foreach ($vincoliGenerici as $vincolo) {
        if ($vincolo['cap'] < $stimaFinale) {
            $stimaFinale = $vincolo['cap'];
            $quotaVincolante = 'Limitato dalla quota "' . $vincolo['label'] . '": al ritmo attuale si esaurirebbe prima.';
        }
    }

    // Il numero di ogni quota generica e' il suo potenziale "da sola":
    // disponibili (corretti per i segmenti demografici gia' chiusi) x IR
    // panel x incidenza propria. E' indipendente dal totale finale - puo'
    // benissimo essere piu' alto del totale mostrato, quando e' proprio
    // quella quota (o un'altra) a vincolarlo: in quel caso resta comunque
    // limitato al massimo dal suo "missing" (non ha senso promettere piu'
    // casi di quanti ne servano davvero a quella quota).
    foreach ($quotaData as $item) {
        if ($item->fattibilita_tipo === 'generica' && isset($item->fattibilita_incidenza)) {
            $stimaRiga = round($utentiDisponibiliCorretti * ($valoreUtilizzato / 100) * ($item->fattibilita_incidenza / 100));
            $item->fattibilita = (int) min($item->missing, max(0, $stimaRiga));
        }
    }

    $result['quotaData'] = $quotaData;
    $result['stima_vincolata'] = max(0, (int) round($stimaFinale));
    $result['quota_vincolante'] = $quotaVincolante;

    return $result;
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
    return Cache::remember('fieldcontrol_media_red_panel_v3', now()->addMinutes(30), function () {
        $unAnnoFa = now()->subYear();

        // Preferisce il dato congelato alla chiusura (frozen_*, affidabile)
        // quando presente; per le ricerche chiuse prima di questa modifica
        // resta il filtro 5-20% su red_panel, unica difesa disponibile contro
        // i valori azzerati dal decadimento del pool di persone abilitate.
        $rows = DB::table('t_panel_control')
            ->where('panel', 1)
            ->where('sur_date', '>=', $unAnnoFa)
            ->where(function ($query) {
                $query->whereNotNull('frozen_at')
                    ->orWhereBetween('red_panel', [5, 20]);
            })
            ->get(['red_panel', 'frozen_at', 'frozen_abilitati', 'frozen_contatti_interactive']);

        $valori = $rows->map(function ($row) {
            if ($row->frozen_at !== null && (int) $row->frozen_abilitati > 0) {
                return ($row->frozen_contatti_interactive / $row->frozen_abilitati) * 100;
            }

            return (float) $row->red_panel;
        });

        return $valori->count() > 0 ? $valori->avg() : 0;
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
                $item->quota_raw = $item->quota;
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
