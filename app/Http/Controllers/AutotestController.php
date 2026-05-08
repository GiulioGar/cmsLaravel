<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\FieldControlSreService;

class AutotestController extends Controller
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

        return view('autotest', compact('surveys'));
    }

 public function start(Request $request, FieldControlSreService $sreService)
{
    $sid = strtoupper(trim($request->sid));
    $prj = strtoupper(trim($request->prj));
    $num = (int) $request->num;

    $dir = $sreService->resolveResultsDirectory($prj, $sid);
    $files = $sreService->getSreFiles($dir);

    $initialCount = count($files);

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
        'exists'  => $dir !== null,
    ]);
}

public function progress(Request $request, FieldControlSreService $sreService)
{
    $sid = strtoupper(trim($request->sid));
    $prj = strtoupper(trim($request->prj));
    $num = (int) $request->num;

    $dir = $sreService->resolveResultsDirectory($prj, $sid);
    $files = $sreService->getSreFiles($dir);
    $currentCount = count($files);

    $statePath = "autotest/{$prj}_{$sid}.json";
    $initial = 0;

    if (Storage::disk('local')->exists($statePath)) {
        $json = json_decode(Storage::disk('local')->get($statePath), true);
        $initial = isset($json['initial']) ? (int) $json['initial'] : 0;
    }

    $fatti = max(0, $currentCount - $initial);
    $percent = ($num > 0) ? round(min(100, ($fatti / $num) * 100), 2) : 0;
    $finished = ($fatti >= $num);

    $ecodeStats = $this->buildAutotestEcodeStats($files, $sreService);

    return response()->json([
        'done'        => $fatti,
        'percent'     => $percent,
        'finished'    => $finished,
        'dir'         => $dir,
        'exists'      => $dir !== null,
        'ecodeStats'  => $ecodeStats,
        'totFiles'    => $currentCount,
        'initial'     => $initial,
    ]);
}

public function status(Request $request, FieldControlSreService $sreService)
{
    $sid = strtoupper(trim($request->sid));
    $prj = strtoupper(trim($request->prj));

    $dir = $sreService->resolveResultsDirectory($prj, $sid);
    $files = $sreService->getSreFiles($dir);

    return response()->json([
        'exists'     => $dir !== null,
        'dir'        => $dir,
        'totFiles'   => count($files),
        'ecodeStats' => $this->buildAutotestEcodeStats($files, $sreService),
    ]);
}


private function buildAutotestEcodeStats(array $files, FieldControlSreService $sreService): array
{
    $ecodeStats = [
        'complete'  => 0,
        'screenout' => 0,
        'quotafull' => 0,
        'sospese'   => 0,
    ];

    foreach ($files as $file) {
        $parsed = $sreService->parseSreFile($file);

        if (empty($parsed)) {
            continue;
        }

        switch ((int) $parsed['status_code']) {
            case 3:
                $ecodeStats['complete']++;
                break;

            case 4:
                $ecodeStats['screenout']++;
                break;

            case 5:
                $ecodeStats['quotafull']++;
                break;

            case 0:
                $ecodeStats['sospese']++;
                break;
        }
    }

    return $ecodeStats;
}


}
