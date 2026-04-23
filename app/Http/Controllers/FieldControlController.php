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
        $quotaData = $this->getQuotaData($sid);

        $directory = $sreService->resolveResultsDirectory($prj, $sid);

        $panelNames = $this->getPanelNamesMap();

        $panelValueFromDB = $panelData->panel ?? null;

        $files = $sreService->getSreFiles($directory);
        $interviews = $sreService->buildInterviewDataset($files, $panelNames, $panelValueFromDB);

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

        $utentiDisponibili = $this->getUtentiDisponibili($sid, $panelData);
        $mediaRedPanel = $this->calcolaMediaRedPanel();

        $stimaInterviste = ((int) $panelValueFromDB === 1)
            ? $this->calcolaStimaInterviste($utentiDisponibili, $redemption, $mediaRedPanel)
            : null;

        $bytes = $panelData->bytes ?? 0;

        $this->updatePanelControl($sid, $counts, $abilitati, $panelCounts, $redemption, $bytes);

        $questionMap = $this->buildQuestionMap($primis, $prj, $sid);

        $filtrateCountsByPanel = $sreService->buildFiltrateCountsFromInterviews($interviews, $questionMap);

        $hasFiltrate = false;
        foreach ($filtrateCountsByPanel as $panel => $rows) {
            if (!empty($rows) && array_sum($rows) > 0) {
                $hasFiltrate = true;
                break;
            }
        }

        $logData = $sreService->buildLogDataFromInterviews($interviews, $questionMap);
        $dataSummaryByPanel = $sreService->buildDataSummaryByDateFromInterviews($interviews);

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

    $panelNames = $this->getPanelNamesMap();
    $panelExportConfig = $this->getPanelExportConfig($panelName);

    $safePanelName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $panelName);
    $fileName = "download_{$safePanelName}_{$prj}_{$sid}.csv";

    $response = new StreamedResponse(function () use ($files, $prj, $sid, $panelName, $panelNames, $panelExportConfig, $sreService) {
        $handle = fopen('php://output', 'w');

        $extraFields = [];

        if ($panelExportConfig) {
            foreach (['red_3', 'red_4', 'red_5'] as $redField) {
                $fieldName = trim((string) ($panelExportConfig->{$redField} ?? ''));

                if ($fieldName !== '') {
                    $extraFields[] = $fieldName;
                }
            }
        }

        $csvHeaders = array_merge(['uid'], $extraFields, ['statusCode', 'Status', 'link']);
        fputcsv($handle, $csvHeaders, ';');

        foreach ($files as $file) {
            $parsed = $sreService->parseSreFile($file);

            if (empty($parsed)) {
                continue;
            }

            $panelUsed = $sreService->resolvePanelFromRawData($parsed['raw'], $panelNames, 1) ?? 'Interactive';

            if ($panelUsed !== $panelName) {
                continue;
            }

            $statusMap = $sreService->getDownloadStatusMap();
            $statusLabel = $statusMap[$parsed['status_code']] ?? 'unknown';

            $uid = $sreService->extractTaggedFieldValue($parsed['raw'], 'sysUID');
            if ($uid === 'N/A') {
                $uid = $parsed['uid'] ?? 'N/A';
            }

            $extraValues = [];

            foreach ($extraFields as $fieldName) {
                $fieldValue = $sreService->extractTaggedFieldValue($parsed['raw'], $fieldName);

                if ($fieldValue === 'N/A') {
                    $fieldValue = 'N.D.';
                }

                $extraValues[] = $fieldValue;
            }

            $panelCode = array_search($panelName, $panelNames, true);

            if ($panelName !== 'Interactive' && $panelCode === false) {
                continue;
            }

            $link = ($panelName === 'Interactive')
                ? "https://www.primisoft.com/primis/run.do?sid={$sid}&prj={$prj}&uid={$uid}"
                : "https://www.primisoft.com/primis/run.do?sid={$sid}&prj={$prj}&uid={$uid}&pan={$panelCode}";

            $row = array_merge(
                [$uid],
                $extraValues,
                [$parsed['status_code'], $statusLabel, $link]
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

        $redPanel = ($abilitati > 0) ? round($counts['contatti'] / $abilitati, 2) : 0;
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

    private function getQuotaData($sid)
    {
        return DB::table('t_quota_status')
            ->where('survey_id', $sid)
            ->orderBy('id', 'asc')
            ->select('target_name as quota', 'target_value as totale', 'current_value as entrate')
            ->get()
            ->map(function ($item) {
                $item->missing = max(0, $item->totale - $item->entrate);
                $item->quota = $this->formatQuotaName($item->quota);
                return $item;
            });
    }

    private function formatQuotaName($quotaName)
    {
        if ($quotaName === 'source_panel') {
            return 'Totale Panel Esterno';
        }

        if (strpos($quotaName, 'total_interviews') === 0) {
            return ($quotaName === 'total_interviews')
                ? 'Interviste Totali'
                : 'Totale Cella ' . str_replace('total_interviews_', '', $quotaName);
        }

        $parts = explode('_', $quotaName);

        if (count($parts) == 2) {
            return ucfirst($parts[0]) . ' - Risposta ' . ((int) $parts[1] + 1);
        }

        if (count($parts) == 3) {
            return ucfirst($parts[0]) . ' - Risposta ' . ((int) $parts[1] + 1) . ' - Cella ' . $parts[2];
        }

        return ucfirst($quotaName);
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
