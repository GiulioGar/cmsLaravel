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
            ->orderByRaw("
                CASE
                    WHEN sid REGEXP '^R[0-9]+$' THEN 1
                    ELSE 0
                END DESC
            ")
            ->orderByRaw("
                CASE
                    WHEN sid REGEXP '^R[0-9]+$' THEN CAST(SUBSTRING(sid, 2) AS UNSIGNED)
                    ELSE NULL
                END DESC
            ")
            ->orderBy('sid', 'desc')
            ->get();

        $panels = DB::table('t_fornitoripanel')
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
            'panel_code' => 'required|integer|exists:t_fornitoripanel,panel_code',
            'num_links' => 'required|integer|min:1|max:10000',
        ]);

        $sid = $request->sid;
        $prj = $request->prj;
        $panel = DB::table('t_fornitoripanel')->where('panel_code', $request->panel_code)->first();

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
            'panel_code' => 'required|integer|unique:t_fornitoripanel,panel_code',
            'name' => 'required|string|max:100',
            'red_3' => 'nullable|url',
            'red_4' => 'nullable|url',
            'red_5' => 'nullable|url',
            'complete' => 'nullable|integer',
            'spesa' => 'nullable|numeric',
        ]);

        DB::table('t_fornitoripanel')->insert([
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
            'id' => 'required|integer|exists:t_fornitoripanel,id',
            'name' => 'required|string|max:100',
            'red_3' => 'nullable|url',
            'red_4' => 'nullable|url',
            'red_5' => 'nullable|url',
            'complete' => 'nullable|integer',
            'spesa' => 'nullable|numeric',
        ]);

        DB::table('t_fornitoripanel')->where('id', $request->id)->update([
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
        DB::table('t_fornitoripanel')->where('id', $id)->delete();
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

    // Status counts presi dai file .sre (status è in 8ª posizione -> index 7)
    $statusCounts = []; // es: [0=>123, 1=>4, ...]
    for ($i = 0; $i <= 7; $i++) $statusCounts[$i] = 0;

    if (is_dir($directory)) {
        $files = glob($directory . "/*.sre");
        $totalFiles = is_array($files) ? count($files) : 0;

        if ($totalFiles > 0) {

            // ========= (A) ULTIMO FILE: per IID numerico nel nome res12345.sre =========
            // Se tutti i nomi sono res{IID}.sre, prendiamo il max IID.
            // Se qualche file non matcha, fallback su filemtime.
            $bestByIid = null;     // ['iid'=>12345,'file'=>path]
            $bestByMtime = null;   // ['mtime'=>...,'file'=>path]

            foreach ($files as $f) {
                $base = basename($f);

                // match res12567.sre oppure res_12567.sre (se vuoi anche underscore)
                if (preg_match('/^res_?(\d+)\.sre$/i', $base, $m)) {
                    $iid = (int)$m[1];
                    if (!$bestByIid || $iid > $bestByIid['iid']) {
                        $bestByIid = ['iid' => $iid, 'file' => $f];
                    }
                } else {
                    $mt = @filemtime($f) ?: 0;
                    if (!$bestByMtime || $mt > $bestByMtime['mtime']) {
                        $bestByMtime = ['mtime' => $mt, 'file' => $f];
                    }
                }

                // ========= (B) STATUS: leggo SOLO la prima riga del file =========
                $fh = @fopen($f, 'r');
                if ($fh) {
                    $line = fgets($fh); // prima riga
                    fclose($fh);

                    if ($line !== false) {
                        $line = trim($line);
                        if ($line !== '') {
                            $parts = explode(';', $line);

                        // 9ª posizione => index 8
                        if (isset($parts[8])) {
                            $st = trim($parts[8]);

                                // conta solo 0..7, altrimenti ignoriamo
                                if ($st !== '' && ctype_digit($st)) {
                                    $stInt = (int)$st;
                                    if ($stInt >= 0 && $stInt <= 7) {
                                        $statusCounts[$stInt]++;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // scegli ultimo file
            if ($bestByIid) {
                $lastFile = basename($bestByIid['file']);
            } elseif ($bestByMtime) {
                $lastFile = basename($bestByMtime['file']);
            } else {
                // fallback estremo: primo della lista
                $lastFile = basename($files[0]);
            }
        }
    }

    return response()->json([
        'success' => true,
        'totalFiles' => $totalFiles,
        'lastFile' => $lastFile,
        'statusCounts' => $statusCounts,
    ]);
}

    /**
     * Abilita una lista di UID (inserisce in t_respint)
     */
public function enableUids(Request $request)
{
    $sid = $request->input('sid');
    $prj = $request->input('prj');
    $uidsRaw = trim((string) $request->input('uids'));

    if (!$sid || !$prj || !$uidsRaw) {
        return response()->json([
            'success' => false,
            'message' => 'Parametri mancanti.'
        ], 400);
    }

    // 1. split, trim, rimozione vuoti, deduplica
    $uids = preg_split('/\r\n|\r|\n/', $uidsRaw);
    $uids = array_map('trim', $uids);
    $uids = array_filter($uids, fn($v) => $v !== '');
    $uids = array_values(array_unique($uids));

    if (empty($uids)) {
        return response()->json([
            'success' => false,
            'message' => 'Nessun UID valido da abilitare.'
        ], 400);
    }

    // 2. leggo in una query gli UID già esistenti per quel SID
    $existing = DB::table('t_respint')
        ->where('sid', $sid)
        ->whereIn('uid', $uids)
        ->pluck('uid')
        ->all();

    $existingMap = array_flip($existing);

    // 3. preparo i nuovi record
    $toInsert = [];
    $log = [];

    foreach ($uids as $uid) {
        if (isset($existingMap[$uid])) {
            continue;
        }

        $toInsert[] = [
            'sid' => $sid,
            'uid' => $uid,
            'status' => 0,
            'iid' => -1,
            'prj_name' => $prj,
        ];

        $log[] = "UID {$uid} abilitato";
    }

    // 4. insert unica
    if (!empty($toInsert)) {
        DB::table('t_respint')->insert($toInsert);
    }

    return response()->json([
        'success' => true,
        'count' => count($toInsert),
        'actions' => array_slice($log, -5)
    ]);
}

public function resetIids(Request $request)
{
    $sid = $request->input('sid');
    $prj = $request->input('prj');
    $iidsRaw = trim((string) $request->input('iids'));

    if (!$sid || !$prj || !$iidsRaw) {
        return response()->json([
            'success' => false,
            'message' => 'Parametri mancanti.'
        ], 400);
    }

    // 1. split, trim, solo numerici, deduplica
    $iids = preg_split('/\r\n|\r|\n/', $iidsRaw);
    $iids = array_map('trim', $iids);
    $iids = array_filter($iids, fn($v) => $v !== '' && ctype_digit($v));
    $iids = array_values(array_unique($iids));

    if (empty($iids)) {
        return response()->json([
            'success' => false,
            'message' => 'Nessun IID numerico valido da resettare.'
        ], 400);
    }

    $directory = base_path("var/imr/fields/{$prj}/{$sid}/results");
    if (!is_dir($directory)) {
        $directory = "/var/imr/fields/{$prj}/{$sid}/results";
    }

    $updated = 0;
    $deleted = 0;
    $log = [];

    // 2. update unico su DB
    $updated = DB::table('t_respint')
        ->where('sid', $sid)
        ->whereIn('iid', $iids)
        ->update([
            'status' => 0,
            'iid' => -1
        ]);

    foreach ($iids as $iid) {
        $log[] = "IID {$iid} resettato";
    }

    // 3. cancellazione file come prima
    if (is_dir($directory)) {
        foreach ($iids as $iid) {
            $pattern = $directory . "/*" . $iid . "*.sre";

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
