<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PanelControl;
use App\Models\PanelControlQuotaTarget;
use App\Services\PrimisApiService;
use App\Services\FieldControlSreService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Cache;

class FieldControlController extends Controller
{
public function index(Request $request, PrimisApiService $primis, FieldControlSreService $sreService)
{
    ini_set('memory_limit', '512M');

    $prj = $request->query('prj');
    $sid = $request->query('sid');

    $panelData = PanelControl::where('sur_id', $sid)->first();

    $quotaTargetsAvanzati = \App\Models\PanelControlQuotaTarget::where('sur_id', $sid)
        ->where('enabled', 1)
        ->get();

    $directory = $sreService->resolveResultsDirectory($prj, $sid);

    $questionMap = $this->buildQuestionMap($primis, $prj, $sid);
    $quotaData = $this->getQuotaData($prj, $sid, $sreService, $questionMap);
    $quotaStatusOptions  = $this->getQuotaStatusOptions($sid);
    $quotaTargetsPreview = $this->prepareQuotaTargetsPreview($quotaTargetsAvanzati, $quotaStatusOptions);

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
    $disponibiliQuoteAvanzate = $this->calcolaDisponibiliPerQuoteAvanzate($sid, $quotaTargetsAvanzati);
    $disponibiliQuoteAvanzate = $this->arricchisciQuoteConQuotaStatus($sid, $disponibiliQuoteAvanzate);
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

    $irPonderatoData    = $this->calcolaIrPonderato($redemption, $dataSummaryByPanel);
    $stimaAvanzataQuote = $this->calcolaStimaAvanzata(
        $utentiDisponibili, $disponibiliQuoteAvanzate, $irPonderatoData['ir_ponderato'], $mediaRedPanel
    );
    $stimaQuoteDettaglio = $this->preparaStimaQuoteDettaglio(
        $disponibiliQuoteAvanzate, $irPonderatoData, $mediaRedPanel
    );

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
        'primisSurveyStatus',
        'quotaTargetsAvanzati',
        'stimaAvanzataQuote',
        'irPonderatoData',
        'mediaRedPanel',
        'quotaStatusOptions',
        'quotaTargetsPreview',
        'stimaQuoteDettaglio'
    ));
}

public function storeQuotaTarget(Request $request)
{
    $dimension = $request->input('quota_dimension');

    $rules = [
        'panel_control_id' => 'nullable|integer',
        'sur_id'           => 'required|string',
        'prj'              => 'required|string',
        'quota_dimension'  => 'required|in:gender,age,area',
        'quota_label'      => 'nullable|string|max:100',
        'target_percent'   => 'nullable|numeric|min:0|max:100',
        'quota_status_id'  => 'required|integer',
        'gender'           => 'nullable|integer|in:1,2',
        'age_min'          => 'nullable|integer|min:0|max:120',
        'age_max'          => 'nullable|integer|min:0|max:120',
        'area'             => 'nullable|integer',
    ];

    if ($dimension === 'gender') {
        $rules['gender'] = 'required|integer|in:1,2';
    } elseif ($dimension === 'age') {
        $rules['age_min'] = 'required|integer|min:0|max:120';
        $rules['age_max'] = 'required|integer|min:0|max:120';
    } elseif ($dimension === 'area') {
        $rules['area'] = 'required|integer';
    }

    $validated = $request->validate($rules);

    $sid = $validated['sur_id'];

    $quotaStatusExists = DB::table('t_quota_status')
        ->where('id', $validated['quota_status_id'])
        ->where('survey_id', $sid)
        ->exists();

    if (!$quotaStatusExists) {
        return back()
            ->withErrors(['quota_status_id' => 'Quota collegata non valida per questa ricerca.'])
            ->withInput();
    }

    $quotaLabel = !empty($validated['quota_label']) ? $validated['quota_label'] : null;
    if ($quotaLabel === null) {
        if ($dimension === 'gender') {
            $quotaLabel = ((int) $validated['gender'] === 1) ? 'Uomo' : 'Donna';
        } elseif ($dimension === 'age') {
            $quotaLabel = ($validated['age_min'] ?? '?') . '-' . ($validated['age_max'] ?? '?');
        } elseif ($dimension === 'area') {
            $quotaLabel = 'Area ' . ($validated['area'] ?? '?');
        }
    }

    $gender = null;
    $ageMin = null;
    $ageMax = null;
    $area   = null;

    if ($dimension === 'gender') {
        $gender = (int) $validated['gender'];
    } elseif ($dimension === 'age') {
        $ageMin = (int) $validated['age_min'];
        $ageMax = (int) $validated['age_max'];
    } elseif ($dimension === 'area') {
        $area = (int) $validated['area'];
    }

    PanelControlQuotaTarget::create([
        'panel_control_id' => !empty($validated['panel_control_id']) ? (int) $validated['panel_control_id'] : null,
        'sur_id'           => $validated['sur_id'],
        'prj'              => $validated['prj'],
        'quota_dimension'  => $dimension,
        'quota_label'      => $quotaLabel,
        'gender'           => $gender,
        'age_min'          => $ageMin,
        'age_max'          => $ageMax,
        'area'             => $area,
        'target_percent'   => isset($validated['target_percent']) ? (float) $validated['target_percent'] : null,
        'quota_status_id'  => (int) $validated['quota_status_id'],
        'enabled'          => 1,
    ]);

    return back()->with('success', 'Quota aggiunta correttamente.');
}

public function updateQuotaTarget(Request $request, $id)
{
    $quota = PanelControlQuotaTarget::findOrFail($id);
    $sid   = $request->input('sur_id');

    if ($quota->sur_id !== $sid) {
        abort(403);
    }

    $dimension = $request->input('quota_dimension');

    $rules = [
        'sur_id'          => 'required|string',
        'quota_dimension' => 'required|in:gender,age,area',
        'quota_label'     => 'nullable|string|max:100',
        'target_percent'  => 'nullable|numeric|min:0|max:100',
        'quota_status_id' => 'required|integer',
        'gender'          => 'nullable|integer|in:1,2',
        'age_min'         => 'nullable|integer|min:0|max:120',
        'age_max'         => 'nullable|integer|min:0|max:120',
        'area'            => 'nullable|integer',
    ];

    if ($dimension === 'gender') {
        $rules['gender'] = 'required|integer|in:1,2';
    } elseif ($dimension === 'age') {
        $rules['age_min'] = 'required|integer|min:0|max:120';
        $rules['age_max'] = 'required|integer|min:0|max:120';
    } elseif ($dimension === 'area') {
        $rules['area'] = 'required|integer';
    }

    $validated = $request->validate($rules);

    $quotaStatusExists = DB::table('t_quota_status')
        ->where('id', $validated['quota_status_id'])
        ->where('survey_id', $sid)
        ->exists();

    if (!$quotaStatusExists) {
        return back()
            ->withErrors(['quota_status_id' => 'Quota collegata non valida per questa ricerca.'])
            ->withInput();
    }

    $quotaLabel = !empty($validated['quota_label']) ? $validated['quota_label'] : null;
    if ($quotaLabel === null) {
        if ($dimension === 'gender') {
            $quotaLabel = ((int) $validated['gender'] === 1) ? 'Uomo' : 'Donna';
        } elseif ($dimension === 'age') {
            $quotaLabel = ($validated['age_min'] ?? '?') . '-' . ($validated['age_max'] ?? '?');
        } elseif ($dimension === 'area') {
            $quotaLabel = 'Area ' . ($validated['area'] ?? '?');
        }
    }

    $gender = null; $ageMin = null; $ageMax = null; $area = null;
    if ($dimension === 'gender') {
        $gender = (int) $validated['gender'];
    } elseif ($dimension === 'age') {
        $ageMin = (int) $validated['age_min'];
        $ageMax = (int) $validated['age_max'];
    } elseif ($dimension === 'area') {
        $area = (int) $validated['area'];
    }

    $quota->update([
        'quota_dimension' => $dimension,
        'quota_label'     => $quotaLabel,
        'gender'          => $gender,
        'age_min'         => $ageMin,
        'age_max'         => $ageMax,
        'area'            => $area,
        'target_percent'  => isset($validated['target_percent']) ? (float) $validated['target_percent'] : null,
        'quota_status_id' => (int) $validated['quota_status_id'],
    ]);

    return back()->with('success', 'Quota aggiornata correttamente.');
}

public function destroyQuotaTarget(Request $request, $id)
{
    $quota = PanelControlQuotaTarget::findOrFail($id);
    $sid   = $request->input('sur_id');

    if ($quota->sur_id !== $sid) {
        abort(403);
    }

    $quota->update(['enabled' => 0]);

    return back()->with('success', 'Quota rimossa correttamente.');
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

private function calcolaDisponibiliPerQuoteAvanzate($sid, $quotaTargetsAvanzati)
{
    if ($quotaTargetsAvanzati->isEmpty()) {
        return [];
    }

    $annoCorrente = (int) date('Y');

    $baseQuery = DB::table('t_user_info')
        ->where('active', 1)
        ->where('confirm', 1)
        ->whereNotExists(function ($query) use ($sid) {
            $query->select(DB::raw(1))
                ->from('t_respint')
                ->whereRaw('t_respint.uid = t_user_info.user_id')
                ->where('t_respint.sid', $sid);
        });

    $risultati = [];

    foreach ($quotaTargetsAvanzati as $quota) {
        $q = clone $baseQuery;

        $gender = (int) ($quota->gender ?? 0);
        if ($gender === 1) {
            $q->where('gender', 1);
        } elseif ($gender === 2) {
            $q->where('gender', 2);
        }

        if ($quota->age_min !== null && $quota->age_max !== null) {
            $q->whereRaw('YEAR(birth_date) BETWEEN ? AND ?', [
                $annoCorrente - (int) $quota->age_max,
                $annoCorrente - (int) $quota->age_min,
            ]);
        }

        if ($quota->area !== null) {
            $q->where('area', $quota->area);
        }

        $risultati[] = [
            'quota_id'           => $quota->id,
            'quota_dimension'    => $quota->quota_dimension,
            'quota_label'        => $quota->quota_label,
            'gender'             => $quota->gender,
            'age_min'            => $quota->age_min,
            'age_max'            => $quota->age_max,
            'area'               => $quota->area,
            'target_percent'     => $quota->target_percent,
            'target_value'        => $quota->target_value,
            'quota_status_id'     => $quota->quota_status_id ?? null,
            'quota_status_name'   => $quota->quota_status_name,
            'utenti_disponibili'  => $q->count(),
        ];
    }

    return $risultati;
}

private function arricchisciQuoteConQuotaStatus($sid, array $disponibiliQuoteAvanzate)
{
    if (empty($disponibiliQuoteAvanzate)) {
        return $disponibiliQuoteAvanzate;
    }

    $idsDaCercare   = array_values(array_filter(array_column($disponibiliQuoteAvanzate, 'quota_status_id')));

    $nomiDaCercare  = [];
    foreach ($disponibiliQuoteAvanzate as $q) {
        if (empty($q['quota_status_id']) && !empty($q['quota_status_name'])) {
            $nomiDaCercare[] = $q['quota_status_name'];
        }
    }
    $nomiDaCercare = array_values(array_unique($nomiDaCercare));

    if (empty($idsDaCercare) && empty($nomiDaCercare)) {
        foreach ($disponibiliQuoteAvanzate as &$quota) {
            $quota['current_value']            = null;
            $quota['quota_status_target_value'] = null;
            $quota['target_residuo']            = null;
        }
        unset($quota);
        return $disponibiliQuoteAvanzate;
    }

    $quotaStatusById = collect();
    if (!empty($idsDaCercare)) {
        $quotaStatusById = DB::table('t_quota_status')
            ->where('survey_id', $sid)
            ->whereIn('id', $idsDaCercare)
            ->select('id', 'current_value', 'target_value')
            ->get()
            ->keyBy('id');
    }

    $quotaStatusByName = collect();
    if (!empty($nomiDaCercare)) {
        $quotaStatusByName = DB::table('t_quota_status')
            ->where('survey_id', $sid)
            ->whereIn('target_name', $nomiDaCercare)
            ->select('target_name', 'current_value', 'target_value')
            ->get()
            ->keyBy('target_name');
    }

    foreach ($disponibiliQuoteAvanzate as &$quota) {
        $statusRow = null;

        if (!empty($quota['quota_status_id'])) {
            $statusRow = $quotaStatusById->get($quota['quota_status_id']);
        }

        if ($statusRow === null && !empty($quota['quota_status_name'])) {
            $statusRow = $quotaStatusByName->get($quota['quota_status_name']);
        }

        if ($statusRow === null) {
            $quota['current_value']            = null;
            $quota['quota_status_target_value'] = null;
            $quota['target_residuo']            = null;
            continue;
        }

        $currentValue = (int) $statusRow->current_value;

        $targetValueEffettivo = ($statusRow->target_value !== null)
            ? (int) $statusRow->target_value
            : ($quota['target_value'] !== null ? (int) $quota['target_value'] : null);

        $quota['current_value']            = $currentValue;
        $quota['quota_status_target_value'] = $statusRow->target_value !== null ? (int) $statusRow->target_value : null;
        $quota['target_residuo']            = $targetValueEffettivo !== null
            ? max($targetValueEffettivo - $currentValue, 0)
            : null;
    }
    unset($quota);

    return $disponibiliQuoteAvanzate;
}

private function getQuotaStatusOptions($sid)
{
    return DB::table('t_quota_status')
        ->where('survey_id', $sid)
        ->orderBy('target_name')
        ->select('id', 'target_name', 'current_value', 'target_value')
        ->get();
}

private function prepareQuotaTargetsPreview($quotaTargetsAvanzati, $quotaStatusOptions)
{
    $statusMap = $quotaStatusOptions->keyBy('id');
    $preview   = [];

    foreach ($quotaTargetsAvanzati as $qt) {
        if ($qt->quota_dimension === 'gender') {
            $tipo = 'Sesso';
        } elseif ($qt->quota_dimension === 'age') {
            $tipo = 'Età';
        } elseif ($qt->quota_dimension === 'area') {
            $tipo = 'Area';
        } else {
            $tipo = $qt->quota_dimension ?? 'N/D';
        }

        if (!empty($qt->quota_label)) {
            $valore = $qt->quota_label;
        } elseif ($qt->quota_dimension === 'gender') {
            $g = (int) ($qt->gender ?? 0);
            $valore = $g === 1 ? 'Uomo' : ($g === 2 ? 'Donna' : 'Entrambi');
        } elseif ($qt->quota_dimension === 'age') {
            $valore = ($qt->age_min ?? '?') . '–' . ($qt->age_max ?? '?');
        } elseif ($qt->quota_dimension === 'area') {
            $valore = 'Area ' . ($qt->area ?? '?');
        } else {
            $valore = '-';
        }

        $quotaCollegata = '-';
        $stato          = '-';

        if (!empty($qt->quota_status_id) && $statusMap->has($qt->quota_status_id)) {
            $statusRow      = $statusMap->get($qt->quota_status_id);
            $quotaCollegata = $statusRow->target_name;
            $stato          = $statusRow->current_value . ' / ' . $statusRow->target_value;
        } elseif (!empty($qt->quota_status_name)) {
            $quotaCollegata = $qt->quota_status_name;
        }

        $preview[] = [
            'id'              => $qt->id,
            'tipo'            => $tipo,
            'valore'          => $valore,
            'percentuale'     => $qt->target_percent,
            'quota_collegata' => $quotaCollegata,
            'stato'           => $stato,
        ];
    }

    return $preview;
}

private function preparaStimaQuoteDettaglio(array $disponibiliQuoteAvanzate, array $irPonderatoData, $mediaRedPanel)
{
    $irUsato = $irPonderatoData['ir_ponderato'] ?? 0;

    if ($irUsato <= 0 || $mediaRedPanel <= 0 || empty($disponibiliQuoteAvanzate)) {
        return null;
    }

    $fattore    = ($irUsato / 100) * ($mediaRedPanel / 100);
    $dimLabels  = ['gender' => 'Sesso', 'age' => 'Età', 'area' => 'Area'];
    $dimensioni = [];

    foreach ($disponibiliQuoteAvanzate as $quota) {
        $udisponibili = (int) ($quota['utenti_disponibili'] ?? 0);

        $dim = $quota['quota_dimension'] ?? null;
        if (empty($dim)) {
            if ($quota['gender'] !== null)   { $dim = 'gender'; }
            elseif ($quota['age_min'] !== null) { $dim = 'age'; }
            elseif ($quota['area'] !== null) { $dim = 'area'; }
            else { continue; }
        }

        $maxStimabile = (int) round($udisponibili * $fattore);

        $targetResiduo   = $quota['target_residuo'] ?? null;
        $stimabileResiduo = ($targetResiduo !== null)
            ? min($maxStimabile, $targetResiduo)
            : $maxStimabile;

        $currentValue = $quota['current_value'] ?? null;
        $targetValue  = $quota['quota_status_target_value'] ?? $quota['target_value'] ?? null;

        $label = !empty($quota['quota_label']) ? $quota['quota_label'] : null;
        if ($label === null) {
            if ($dim === 'gender') {
                $g = (int) ($quota['gender'] ?? 0);
                $label = $g === 1 ? 'Uomo' : ($g === 2 ? 'Donna' : 'N/D');
            } elseif ($dim === 'age') {
                $label = ($quota['age_min'] ?? '?') . '–' . ($quota['age_max'] ?? '?');
            } elseif ($dim === 'area') {
                $label = 'Area ' . ($quota['area'] ?? '?');
            } else {
                $label = 'N/D';
            }
        }

        if (!isset($dimensioni[$dim])) {
            $dimensioni[$dim] = [
                'label'             => $dimLabels[$dim] ?? $dim,
                'max_stimabile'     => 0,
                'stimabile_residuo' => 0,
                'tot_mancano'       => 0,
                'has_mancano'       => false,
                'quote'             => [],
            ];
        }

        $dimensioni[$dim]['max_stimabile']     += $maxStimabile;
        $dimensioni[$dim]['stimabile_residuo'] += $stimabileResiduo;
        if ($targetResiduo !== null) {
            $dimensioni[$dim]['tot_mancano'] += $targetResiduo;
            $dimensioni[$dim]['has_mancano']  = true;
        }
        $dimensioni[$dim]['quote'][] = [
            'label'              => $label,
            'target'             => $targetValue,
            'attuali'            => $currentValue,
            'mancano'            => $targetResiduo,
            'utenti_disponibili' => $udisponibili,
            'max_stimabile'      => $maxStimabile,
            'stimabile_residuo'  => $stimabileResiduo,
        ];
    }

    if (empty($dimensioni)) {
        return null;
    }

    // Calcola "tutte" per dimensione e rimuovi il flag temporaneo
    foreach ($dimensioni as $dim => &$dimData) {
        if (!$dimData['has_mancano'] || $dimData['tot_mancano'] <= 0) {
            $dimData['tot_mancano'] = null;
            $dimData['tutte']       = false;
        } else {
            $dimData['tutte'] = ($dimData['stimabile_residuo'] >= $dimData['tot_mancano']);
        }
        unset($dimData['has_mancano']);
    }
    unset($dimData);

    // Totale prudenziale = min tra dimensioni; "tutte" dal collo di bottiglia
    $totMaxStimabile     = min(array_column($dimensioni, 'max_stimabile'));
    $totStimabileResiduo = min(array_column($dimensioni, 'stimabile_residuo'));

    $bottleneck = null;
    $minVal     = PHP_INT_MAX;
    foreach ($dimensioni as $k => $d) {
        if ($d['stimabile_residuo'] < $minVal) {
            $minVal     = $d['stimabile_residuo'];
            $bottleneck = $k;
        }
    }
    $totTutte = ($bottleneck !== null) ? $dimensioni[$bottleneck]['tutte'] : false;

    return [
        'fattore'         => round($fattore * 100, 2),
        'ir_usato'        => $irUsato,
        'media_red_panel' => $mediaRedPanel,
        'totali'          => [
            'max_stimabile'     => $totMaxStimabile,
            'stimabile_residuo' => $totStimabileResiduo,
            'n_dimensioni'      => count($dimensioni),
            'tutte'             => $totTutte,
        ],
        'dimensioni'      => $dimensioni,
    ];
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

    private function calcolaIrPonderato($irTotale, $dataSummaryByPanel)
    {
        $ultimaData = null;

        foreach ($dataSummaryByPanel as $panelData) {
            foreach ($panelData as $dayKey => $dayData) {
                if ($ultimaData === null || $dayKey > $ultimaData) {
                    $ultimaData = $dayKey;
                }
            }
        }

        $irUltimoGiorno   = null;
        $pesoUltimoGiorno = 0.0;
        $baseUltimoGiorno = 0;

        if ($ultimaData !== null) {
            $completeGiorno  = 0;
            $nonTargetGiorno = 0;

            foreach ($dataSummaryByPanel as $panelData) {
                if (isset($panelData[$ultimaData])) {
                    $completeGiorno  += $panelData[$ultimaData]['complete'];
                    $nonTargetGiorno += $panelData[$ultimaData]['non_target'];
                }
            }

            // Coerente con formula globale: contatti_utili = contatti - sospese - bloccate - over_quota = complete + non_target
            $contattiUtili    = $completeGiorno + $nonTargetGiorno;
            $baseUltimoGiorno = $contattiUtili;

            if ($contattiUtili >= 10) {
                $irUltimoGiorno   = round(($completeGiorno / $contattiUtili) * 100, 2);
                $pesoUltimoGiorno = ($contattiUtili >= 30) ? 0.30 : 0.15;
            }
        }

        $irPonderato = ($irUltimoGiorno !== null)
            ? round($irTotale * (1 - $pesoUltimoGiorno) + $irUltimoGiorno * $pesoUltimoGiorno, 4)
            : $irTotale;

        return [
            'ir_totale'          => $irTotale,
            'ir_ultimo_giorno'   => $irUltimoGiorno,
            'ir_ponderato'       => $irPonderato,
            'peso_ultimo_giorno' => $pesoUltimoGiorno,
            'base_ultimo_giorno' => $baseUltimoGiorno,
        ];
    }

    private function calcolaStimaAvanzata($utentiDisponibili, $disponibiliQuoteAvanzate, $irUsato, $mediaRedPanel)
    {
        if ($irUsato <= 0 || $mediaRedPanel <= 0 || $utentiDisponibili === null) {
            return null;
        }

        $fattore = ($irUsato / 100) * ($mediaRedPanel / 100);

        $quoteValide = array_values(array_filter($disponibiliQuoteAvanzate, function ($q) {
            return $q['utenti_disponibili'] !== null;
        }));

        if (!empty($quoteValide)) {
            $quoteCalcolate = [];
            $totale         = 0;

            foreach ($quoteValide as $quota) {
                $disponibili   = $quota['utenti_disponibili'];
                $stimaLorda    = $disponibili * $fattore;
                $targetResiduo = $quota['target_residuo'] ?? null;

                $stimaFinale = ($targetResiduo !== null)
                    ? min($stimaLorda, (float) $targetResiduo)
                    : $stimaLorda;

                $quoteCalcolate[] = [
                    'quota_id'                  => $quota['quota_id'],
                    'target_value'              => $quota['target_value'],
                    'quota_status_target_value' => $quota['quota_status_target_value'] ?? null,
                    'current_value'             => $quota['current_value'] ?? null,
                    'target_residuo'            => $targetResiduo,
                    'utenti_disponibili'        => $disponibili,
                    'stima_lorda'               => round($stimaLorda, 2),
                    'stima_finale'              => round($stimaFinale, 2),
                ];

                $totale += $stimaFinale;
            }

            return [
                'totale' => (int) round($totale),
                'metodo' => 'quote',
                'quote'  => $quoteCalcolate,
            ];
        }

        return [
            'totale' => (int) round($utentiDisponibili * $fattore),
            'metodo' => 'totale',
            'quote'  => [],
        ];
    }

    private function calcolaStimaAvanzataPerQuote($disponibiliQuoteAvanzate, $redemption, $mediaRedPanel)
    {
        if (empty($disponibiliQuoteAvanzate) || $redemption <= 0 || $mediaRedPanel <= 0) {
            return null;
        }

        $fattore = ($redemption / 100) * ($mediaRedPanel / 100);
        $quoteCalcolate = [];
        $totale = 0;

        foreach ($disponibiliQuoteAvanzate as $quota) {
            $disponibili = $quota['utenti_disponibili'];

            if ($disponibili === null) {
                continue;
            }

            $stimaLorda    = $disponibili * $fattore;
            $targetResiduo = $quota['target_residuo'] ?? null;

            $stimaFinale = ($targetResiduo !== null)
                ? min($stimaLorda, (float) $targetResiduo)
                : $stimaLorda;

            $quoteCalcolate[] = [
                'quota_id'                  => $quota['quota_id'],
                'target_value'              => $quota['target_value'],
                'quota_status_target_value' => $quota['quota_status_target_value'] ?? null,
                'current_value'             => $quota['current_value'] ?? null,
                'target_residuo'            => $targetResiduo,
                'utenti_disponibili'        => $disponibili,
                'stima_lorda'               => round($stimaLorda, 2),
                'stima_finale'              => round($stimaFinale, 2),
            ];

            $totale += $stimaFinale;
        }

        if (empty($quoteCalcolate)) {
            return null;
        }

        return [
            'totale' => (int) round($totale),
            'quote'  => $quoteCalcolate,
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
