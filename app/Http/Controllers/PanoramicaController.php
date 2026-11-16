<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PrimisApiService;
use App\Services\PanoramicaService;

class PanoramicaController extends Controller
{
    public function index(Request $request, PrimisApiService $primis, PanoramicaService $panoramica)
    {
        $prj = (string) $request->query('prj');
        $sid = (string) $request->query('sid');

        $panelData = DB::table('t_panel_control')->where('sur_id', $sid)->first();

        $ricercheInCorso = DB::table('t_panel_control')
            ->where('stato', 0)
            ->orderBy('description', 'asc')
            ->get(['sur_id', 'description', 'prj']);

        $questions = $panoramica->getQuestionCatalog($primis, $prj, $sid);
        $summary = $panoramica->getSummary($prj, $sid);
        $panels = $panoramica->getPanelMap($prj, $sid);

        return view('panoramica', [
            'prj' => $prj,
            'sid' => $sid,
            'panelData' => $panelData,
            'ricercheInCorso' => $ricercheInCorso,
            'questions' => $questions,
            'summary' => $summary,
            'variableLabels' => $panoramica->getVariableLabels($prj, $sid, $summary, $questions),
            'hasMultiplePanels' => count($panels['complete_panels']) > 1,
        ]);
    }

    public function question(Request $request, PrimisApiService $primis, PanoramicaService $panoramica)
    {
        $prj = (string) $request->query('prj');
        $sid = (string) $request->query('sid');
        $questionId = (int) $request->query('question_id');

        if ($prj === '' || $sid === '' || $questionId <= 0) {
            return response()->json(['success' => false, 'message' => 'Parametri mancanti (prj, sid o question_id).']);
        }

        $catalog = $panoramica->getQuestionCatalog($primis, $prj, $sid);
        $question = $panoramica->findQuestion($catalog, $questionId);
        if ($question === null) {
            return response()->json(['success' => false, 'message' => 'Domanda non trovata.']);
        }

        $cross = null;
        $crossKey = trim((string) $request->query('cross', ''));
        if ($crossKey !== '') {
            $cross = $panoramica->resolveCross($prj, $sid, $crossKey, $catalog, $question);
            if ($cross === null) {
                return response()->json(['success' => false, 'message' => 'Incrocio non valido per questa domanda.']);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $panoramica->analyzeQuestion($prj, $sid, $question, $cross),
        ]);
    }

    /**
     * Vista aggregata di una famiglia loop (es. Q12p per tutti i personaggi):
     * una riga per domanda del ciclo, invece di aprirle una per una.
     */
    public function family(Request $request, PrimisApiService $primis, PanoramicaService $panoramica)
    {
        $prj = (string) $request->query('prj');
        $sid = (string) $request->query('sid');
        $prefix = (string) $request->query('prefix');

        if ($prj === '' || $sid === '' || $prefix === '') {
            return response()->json(['success' => false, 'message' => 'Parametri mancanti (prj, sid o prefix).']);
        }

        $catalog = $panoramica->getQuestionCatalog($primis, $prj, $sid);
        $members = array_values(array_filter($catalog, function ($q) use ($prefix) {
            return ($q['loop'] ?? null) === $prefix;
        }));

        if (count($members) < 2) {
            return response()->json(['success' => false, 'message' => 'Famiglia non trovata.']);
        }

        return response()->json([
            'success' => true,
            'data' => $panoramica->analyzeFamily($prj, $sid, $members),
        ]);
    }
}
