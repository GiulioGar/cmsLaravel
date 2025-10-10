<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AutotestController extends Controller
{
    public function index()
    {
        $surveys = DB::table('t_surveys')
            ->select('sid', 'prj_name')
            ->where('status', 2)
            ->orderBy('sid', 'desc')
            ->get();

        return view('autotest', compact('surveys'));
    }

    public function start(Request $request)
    {
        $sid = strtoupper(trim($request->sid));
        $prj = strtoupper(trim($request->prj));
        $num = (int) $request->num;

        $dir = base_path("var/imr/fields/$prj/$sid/results");
        $initialCount = (is_dir($dir)) ? count(glob($dir . "/*.sre")) : 0;

        // salviamo stato iniziale in storage/app/autotest/...
        $statePath = "autotest/{$prj}_{$sid}.json";
        Storage::disk('local')->put($statePath, json_encode([
            'initial' => $initialCount,
            'started_at' => now()->toDateTimeString(),
        ]));

        $link = "https://www.primisoft.com/primis/run.do?sid={$sid}&prj={$prj}&uid=GUEST&test=1&rst=1&miosid={$sid}&mioprj={$prj}&nl={$num}";

        return response()->json([
            'success' => true,
            'link'    => $link,
            'initial' => $initialCount,
            'dir'     => $dir,
            'exists'  => is_dir($dir),
        ]);
    }

public function progress(Request $request)
{
    $sid = strtoupper(trim($request->sid));
    $prj = strtoupper(trim($request->prj));
    $num = (int) $request->num;

    $dir = base_path("var/imr/fields/$prj/$sid/results");
    $currentCount = (is_dir($dir)) ? count(glob($dir . "/*.sre")) : 0;

    $statePath = "autotest/{$prj}_{$sid}.json";
    $initial = 0;
    $startTime = null;

    if (Storage::disk('local')->exists($statePath)) {
        $json = json_decode(Storage::disk('local')->get($statePath), true);
        $initial = isset($json['initial']) ? (int)$json['initial'] : 0;
        $startTime = $json['started_at'] ?? null;
    }

    // Calcolo progresso
    $fatti = max(0, $currentCount - $initial);
    $percent = ($num > 0) ? round(min(100, ($fatti / $num) * 100), 2) : 0;
    $finished = ($fatti >= $num);

    // Analizza .sre per stato interviste
    $ecodeStats = [
        'complete'  => 0,
        'screenout' => 0,
        'quotafull' => 0,
        'sospese'   => 0,
    ];

    if (is_dir($dir)) {
        $files = glob($dir . "/*.sre");
        foreach ($files as $file) {
            $line = fgets(fopen($file, "r"));
            if (!$line) continue;
            $parts = explode(";", trim($line));
            if (isset($parts[8])) {
                $ecode = (int)$parts[8];
                switch ($ecode) {
                    case 3: $ecodeStats['complete']++; break;
                    case 4: $ecodeStats['screenout']++; break;
                    case 5: $ecodeStats['quotafull']++; break;
                    case 0: $ecodeStats['sospese']++; break;
                }
            }
        }
    }

    return response()->json([
        'done'        => $fatti,
        'percent'     => $percent,
        'finished'    => $finished,
        'dir'         => $dir,
        'exists'      => is_dir($dir),
        'ecodeStats'  => $ecodeStats,
        'totFiles'    => $currentCount,
        'initial'     => $initial,
    ]);
}

public function status(Request $request)
{
    $sid = strtoupper(trim($request->sid));
    $prj = strtoupper(trim($request->prj));

    $dir = base_path("var/imr/fields/$prj/$sid/results");
    $totalFiles = 0;
    $ecodeStats = [
        'complete'  => 0,
        'screenout' => 0,
        'quotafull' => 0,
        'sospese'   => 0,
    ];

    if (is_dir($dir)) {
        $files = glob($dir . "/*.sre");
        $totalFiles = count($files);
        foreach ($files as $file) {
            $line = fgets(fopen($file, "r"));
            if (!$line) continue;
            $parts = explode(";", trim($line));
            if (isset($parts[8])) {
                $ecode = (int)$parts[8];
                switch ($ecode) {
                    case 3: $ecodeStats['complete']++; break;
                    case 4: $ecodeStats['screenout']++; break;
                    case 5: $ecodeStats['quotafull']++; break;
                    case 0: $ecodeStats['sospese']++; break;
                }
            }
        }
    }

    return response()->json([
        'exists'     => is_dir($dir),
        'dir'        => $dir,
        'totFiles'   => $totalFiles,
        'ecodeStats' => $ecodeStats,
    ]);
}


}
