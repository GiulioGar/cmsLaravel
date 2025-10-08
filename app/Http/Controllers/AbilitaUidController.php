<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\UidGeneratorService;
use Illuminate\Support\Facades\File;

class AbilitaUidController extends Controller
{
    public function index()
    {
        $surveys = DB::table('t_surveys')
            ->select('sid', 'prj_name')
            ->where('status', 2)
            ->orderBy('sid', 'desc')
            ->get();

        $panels = DB::table('t_fornitoriPanel')
            ->select('id', 'panel_code', 'name', 'red_3', 'red_4', 'red_5', 'complete', 'spesa')
            ->orderBy('name', 'asc')
            ->get();

        return view('abilitaUid', compact('surveys', 'panels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sid' => 'required|string|exists:t_surveys,sid',
            'prj' => 'required|string',
            'panel_code' => 'required|integer|exists:t_fornitoriPanel,panel_code',
            'num_links' => 'required|integer|min:1|max:10000',
        ]);

        $sid = $request->sid;
        $prj = $request->prj;
        $panel = DB::table('t_fornitoriPanel')->where('panel_code', $request->panel_code)->first();

        if (!$panel) {
            return back()->withErrors(['panel_code' => 'Panel non trovato']);
        }

        $generator = new UidGeneratorService();
        $uids = $generator->generateBatch($panel->name, $request->num_links);

        $links = [];
        foreach ($uids as $uid) {
            $links[] = [
                'link' => "https://www.primisoft.com/primis/run.do?sid={$sid}&prj={$prj}&uid={$uid}&pan={$panel->panel_code}",
                'uid' => $uid,
            ];
        }

        foreach ($uids as $uid) {
            DB::table('t_respint')->insert([
                'sid' => $sid,
                'uid' => $uid,
                'status' => 0,
                'iid' => -1,
                'prj_name' => $prj,
            ]);
        }

        return redirect()->back()->with([
            'links' => $links,
            'success' => count($uids) . ' UID generati e salvati correttamente.'
        ]);
    }

    // === GESTIONE PANEL (AJAX) ===

    public function storePanel(Request $request)
    {
        $request->validate([
            'panel_code' => 'required|integer|unique:t_fornitoriPanel,panel_code',
            'name' => 'required|string|max:100',
            'red_3' => 'nullable|url',
            'red_4' => 'nullable|url',
            'red_5' => 'nullable|url',
            'complete' => 'nullable|integer',
            'spesa' => 'nullable|numeric',
        ]);

        DB::table('t_fornitoriPanel')->insert([
            'panel_code' => $request->panel_code,
            'name' => $request->name,
            'red_3' => $request->red_3,
            'red_4' => $request->red_4,
            'red_5' => $request->red_5,
            'complete' => $request->complete ?? 0,
            'spesa' => $request->spesa ?? 0.00,
        ]);

        return response()->json(['success' => true]);
    }

    public function updatePanel(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:t_fornitoriPanel,id',
            'name' => 'required|string|max:100',
            'red_3' => 'nullable|url',
            'red_4' => 'nullable|url',
            'red_5' => 'nullable|url',
            'complete' => 'nullable|integer',
            'spesa' => 'nullable|numeric',
        ]);

        DB::table('t_fornitoriPanel')->where('id', $request->id)->update([
            'name' => $request->name,
            'red_3' => $request->red_3,
            'red_4' => $request->red_4,
            'red_5' => $request->red_5,
            'complete' => $request->complete ?? 0,
            'spesa' => $request->spesa ?? 0.00,
        ]);

        return response()->json(['success' => true]);
    }

    public function deletePanel($id)
    {
        DB::table('t_fornitoriPanel')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }


    /**
     * AJAX: restituisce conteggi file .sre e status t_respint
     */
    public function showRightPanelData(Request $request)
    {
        $sid = $request->input('sid');
        $prj = $request->input('prj');

        if (!$sid || !$prj) {
            return response()->json(['success' => false, 'message' => 'Parametri mancanti.'], 400);
        }

        $directory = base_path("var/imr/fields/$prj/$sid/results/");
        $totalFiles = 0;
        $lastFile = '—';

        if (is_dir($directory)) {
            $files = glob($directory . "/*.sre");
            $totalFiles = count($files);
            if ($totalFiles > 0) {
                rsort($files);
                $lastFile = basename($files[0]);
            }
        }

        // Conteggi per status nella tabella t_respint
        $statusCounts = DB::table('t_respint')
            ->select('status', DB::raw('COUNT(*) as totale'))
            ->where('sid', $sid)
            ->groupBy('status')
            ->pluck('totale', 'status');

        return response()->json([
            'success' => true,
            'totalFiles' => $totalFiles,
            'lastFile' => $lastFile,
            'statusCounts' => $statusCounts
        ]);
    }

    /**
     * Abilita una lista di UID (inserisce in t_respint)
     */
    public function enableUids(Request $request)
{
    $sid = $request->input('sid');
    $prj = $request->input('prj');
    $uidsRaw = trim($request->input('uids'));

    if (!$sid || !$prj || !$uidsRaw) {
        return response()->json(['success' => false, 'message' => 'Parametri mancanti.'], 400);
    }

    $uids = preg_split('/\r\n|\r|\n/', $uidsRaw);
    $inserted = 0;
    $log = [];

    foreach ($uids as $uid) {
        $uid = trim($uid);
        if ($uid === '') continue;

        $exists = DB::table('t_respint')->where('sid', $sid)->where('uid', $uid)->exists();
        if ($exists) continue;

        DB::table('t_respint')->insert([
            'sid' => $sid,
            'uid' => $uid,
            'status' => 0,
            'iid' => -1,
            'prj_name' => $prj,
        ]);
        $inserted++;
        $log[] = "UID {$uid} abilitato";
    }

    return response()->json([
        'success' => true,
        'count' => $inserted,
        'actions' => array_slice($log, -5)
    ]);
}

public function resetIids(Request $request)
{
    $sid = $request->input('sid');
    $prj = $request->input('prj');
    $iidsRaw = trim($request->input('iids'));

    if (!$sid || !$prj || !$iidsRaw) {
        return response()->json(['success' => false, 'message' => 'Parametri mancanti.'], 400);
    }

    $iids = preg_split('/\r\n|\r|\n/', $iidsRaw);
    $directory = base_path("var/imr/fields/$prj/$sid/results/");
    $updated = 0;
    $deleted = 0;
    $log = [];

    foreach ($iids as $iid) {
        $iid = trim($iid);
        if ($iid === '') continue;

        $affected = DB::table('t_respint')
            ->where('sid', $sid)
            ->where('iid', $iid)
            ->update(['status' => 0, 'iid' => -1]);

        if ($affected > 0) {
            $updated += $affected;
            $log[] = "IID {$iid} resettato";
        }

        if (is_dir($directory)) {
            $pattern = "$directory/*$iid*.sre";
            foreach (glob($pattern) as $file) {
                if (File::exists($file)) {
                    File::delete($file);
                    $deleted++;
                    $log[] = "File eliminato: " . basename($file);
                }
            }
        }
    }

    return response()->json([
        'success' => true,
        'updated' => $updated,
        'deleted' => $deleted,
        'actions' => array_slice($log, -5)
    ]);
}



}
