<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PanelControl;
use App\Models\UserInfo;
use Illuminate\Support\Facades\Log;

class CampionamentoController extends Controller
{
    public function index()
    {
        // 1) Ricerche con panel=1, stato=0
        $ricerche = PanelControl::select('sur_id', 'description', 'prj')
            ->where('panel', 1)
            ->where('stato', 0)
            ->groupBy('sur_id', 'description', 'prj')
            ->get();

        // 2) Target da elencotag in ordine alfabetico
        $targets = DB::table('elencotag')
            ->select('id', 'tag')
            ->orderBy('tag', 'asc')
            ->get();

        return view('campionamento', compact('ricerche', 'targets'));
    }

    public function panelData($sur_id)
    {
        $pc = PanelControl::where('sur_id', $sur_id)
            ->where('panel', 1)
            ->where('stato', 0)
            ->firstOrFail();

        return response()->json([
            'sex_target'  => $pc->sex_target,
            'age1_target' => $pc->age1_target,
            'age2_target' => $pc->age2_target,
        ]);
    }

// ============================
//  UTENTI DISPONIBILI
// ============================
public function utentiDisponibili(Request $request)
{
    $surId        = (string) $request->input('sur_id');
    $samples      = $request->input('samples', []);
    $excludeCodes = $this->normalizeExcludeCodes($request->input('exclude_codes', ''));
    $debug        = (bool) $request->boolean('debug');

    $items = [];

    Log::info('campionamento.utentiDisponibili.start', [
        'surId' => $surId,
        'samples_count' => count($samples),
        'exclude_codes' => $excludeCodes,
    ]);

    foreach ($samples as $i => $sample) {
        $q = $this->buildSampleQuery($surId, (array) $sample, $excludeCodes);

        Log::info('campionamento.count.before', [
            'index' => $i,
            'sql' => $this->interpolateSql($q->toSql(), $q->getBindings()),
        ]);

        $count = (clone $q)->count();

        Log::info('campionamento.count.after', [
            'index' => $i,
            'count' => $count,
        ]);

        $row = [
            'index' => $i,
            'count' => $count,
        ];

        if ($debug) {
            $row['sql_full'] = $this->interpolateSql($q->toSql(), $q->getBindings());
        }

        $items[] = $row;
    }

        Log::info('campionamento.total.start');

        $total = $this->countDistinctUsersAcrossSamples($surId, $samples, $excludeCodes);

        Log::info('campionamento.total.end', [
            'total' => $total,
        ]);

    return response()->json([
        'items' => $items,
        'total' => $total,
    ]);
}


   // ============================
//  CREA CAMPIONI
// ============================
public function creaCampioni(Request $request)
{

    $surId   = (string)$request->input('sur_id');
    $samples = $request->input('samples', []);
    $excludeCodes = $this->normalizeExcludeCodes($request->input('exclude_codes', ''));

    if (!$surId || !is_array($samples) || !count($samples)) {
        return response()->json(['error'=>'Parametri mancanti'], 422);
    }

    $userKey = 'user_id';

    // === ENV ===
    $envRows = DB::table('t_surveys_env')
        ->where('sid', $surId)
        ->select('name','value')->get();

    $env = [];
    foreach ($envRows as $row) {
        $env[strtolower(trim((string)$row->name))] = trim((string)$row->value);
    }

    $getVal = fn($arr, $keys)=>collect($keys)->first(fn($k)=>!empty($arr[strtolower($k)])) ?
        $arr[strtolower(collect($keys)->first(fn($k)=>!empty($arr[strtolower($k)])))] : '';

    $prj   = $getVal($env, ['prj_name','prj','project','prjname']);
    $argo  = $getVal($env, ['survey_object','argo','argomento','object']);
    $bytes = $getVal($env, ['prize_complete','bytes','premio_complete','reward']);
    $loi   = $getVal($env, ['length_of_interview','loi','durata_intervista']);

    if ($prj === '')  $prj  = PanelControl::where('sur_id',$surId)->value('prj') ?? '';
    if ($argo === '') $argo = PanelControl::where('sur_id',$surId)->value('description') ?? '';

    // === Selezione utenti ===
    $giaSelezionatiFollowup = [];
    $giaSelezionatiStandard = [];
    $righeSelezionate = collect();

foreach ($samples as $s) {
    $followup = !empty($s['followup']);
    $invite   = max(1, (int) ($s['invite'] ?? 1));

    $excludeUids = $followup ? $giaSelezionatiFollowup : $giaSelezionatiStandard;

    $q = $this->buildSampleQuery(
        $surId,
        (array) $s,
        $excludeCodes,
        $excludeUids
    );

    $users = $q->select([
            'u.user_id as uid',
            'u.email',
            'u.first_name as firstName',
            'u.gender',
            'u.token'
        ])
        ->distinct()
        ->inRandomOrder()
        ->limit($invite)
        ->get();

    foreach ($users as $u) {
        $gs = ($u->gender == 1) ? 'o' : (($u->gender == 2) ? 'a' : '');

        $righeSelezionate->push([
            'uid' => $u->uid,
            'email' => $u->email,
            'firstName' => $u->firstName,
            'genderSuffix' => $gs,
            'sid' => $surId,
            'prj' => $prj,
            'argo' => $argo,
            'bytes' => $bytes,
            'loi' => $loi,
            'token' => $u->token ?? '',
            'followup' => $followup,
        ]);

        if ($followup) {
            $giaSelezionatiFollowup[] = (string) $u->uid;
        } else {
            $giaSelezionatiStandard[] = (string) $u->uid;
        }
    }
}

    /*
    |--------------------------------------------------------------------------
    | INSERIMENTO t_respint
    |--------------------------------------------------------------------------
    */

if ($righeSelezionate->isNotEmpty()) {
    $toInsert = $righeSelezionate
        ->filter(function ($r) {
            return empty($r['followup']);
        })
        ->map(function ($r) use ($surId, $prj) {
            return [
                'sid' => $surId,
                'uid' => $r['uid'],
                'status' => 0,
                'iid' => -1,
                'prj_name' => $prj,
            ];
        })
        ->all();

    if (!empty($toInsert)) {
        DB::transaction(function () use ($toInsert) {
            DB::table('t_respint')->insertOrIgnore($toInsert);

            $uids = collect($toInsert)
                ->pluck('uid')
                ->unique()
                ->values()
                ->all();

            $this->aggiornaInvitiUtenti($uids);
        });
    }
}

    /*
    |--------------------------------------------------------------------------
    | CSV OUTPUT
    |--------------------------------------------------------------------------
    */

    $header = 'uid;email;firstName;genderSuffix;sid;prj;argo;bytes;loi;token';

    $lines = $righeSelezionate->map(function($r){

        $vals = [
            $r['uid'],
            $r['email'],
            $r['firstName'],
            $r['genderSuffix'],
            $r['sid'],
            $r['prj'],
            $r['argo'],
            $r['bytes'],
            $r['loi'],
            $r['token']
        ];

        $escaped = array_map(fn($v)=>
            (strpos($v,';')!==false || strpos($v,'"')!==false)
                ? '"'.str_replace('"','""',$v).'"'
                : $v
        ,$vals);

        return implode(';',$escaped);

    })->all();

    $csv = $header."\n".implode("\n",$lines);

    $filename = 'campionamento_'.$surId.'_'.date('Ymd_His').'.csv';

    return response()->json([
        'enabled_count'=>$righeSelezionate->count(),
        'filename'=>$filename,
        'csv_text'=>$csv,
        'csv_base64'=>base64_encode($csv),
    ]);
}


private function aggiornaInvitiUtenti(array $uids): void
{
    $uids = array_values(array_unique(array_filter($uids)));

    if (empty($uids)) {
        return;
    }

    $now = now()->toDateTimeString();

    $values = [];
    $bindings = [];

    foreach ($uids as $uid) {
        $values[] = '(?, 1, ?, ?)';
        $bindings[] = $uid;
        $bindings[] = $now;
        $bindings[] = $now;
    }

    $sql = "
        INSERT INTO t_user_invites (user_id, invites, created_at, updated_at)
        VALUES " . implode(', ', $values) . "
        ON DUPLICATE KEY UPDATE
            invites = invites + 1,
            updated_at = VALUES(updated_at)
    ";

    DB::statement($sql, $bindings);
}


private function normalizeExcludeCodes($excludeRaw): array
{
    if (is_string($excludeRaw)) {
        return array_values(array_filter(array_map('trim', explode(';', $excludeRaw))));
    }

    if (is_array($excludeRaw)) {
        return array_values(array_filter(array_map('trim', $excludeRaw)));
    }

    return [];
}

private function buildSampleQuery(
    string $surId,
    array $sample,
    array $excludeCodes = [],
    array $excludeUids = []
) {
    $userKey  = 'user_id';
    $followup = !empty($sample['followup']);
    $sessi    = isset($sample['sesso']) ? (array) $sample['sesso'] : [];
    $etaMin   = isset($sample['eta_da']) ? (int) $sample['eta_da'] : null;
    $etaMax   = isset($sample['eta_a']) ? (int) $sample['eta_a'] : null;
    $reg      = isset($sample['regioni']) ? (array) $sample['regioni'] : [];
    $aree     = isset($sample['aree']) ? (array) $sample['aree'] : [];
    $prov     = isset($sample['province_id']) ? (array) $sample['province_id'] : [];
    $amp      = isset($sample['ampiezza']) ? (array) $sample['ampiezza'] : [];
    $targetId = isset($sample['target_id']) ? (int) $sample['target_id'] : null;
    $iscrittoDal = isset($sample['iscritto_dal']) ? (int) $sample['iscritto_dal'] : null;

    if (!empty($targetId)) {
        if ($followup) {
            $q = DB::table('utenti_target as ut')
                ->join('t_user_info as u', 'u.user_id', '=', 'ut.uid')
                ->join('t_respint as r', function ($join) use ($surId) {
                    $join->on('r.uid', '=', 'u.user_id')
                        ->where('r.sid', '=', $surId);
                })
                ->where('ut.target_id', $targetId)
                ->whereNotNull('ut.uid')
                ->whereNotIn('r.status', [3, 4, 5])
                ->select('u.user_id')
                ->distinct();
        } else {
            $q = DB::table('utenti_target as ut')
                ->join('t_user_info as u', 'u.user_id', '=', 'ut.uid')
                ->leftJoin('t_respint as r', function ($join) use ($surId) {
                    $join->on('r.uid', '=', 'u.user_id')
                        ->where('r.sid', '=', $surId);
                })
                ->where('ut.target_id', $targetId)
                ->whereNotNull('ut.uid')
                ->whereNull('r.uid')
                ->select('u.user_id')
                ->distinct();
        }
    } else {
        $q = UserInfo::query()->from('t_user_info as u');

        if ($followup) {
            $q->whereExists(function ($sub) use ($surId, $userKey) {
                $sub->select(DB::raw(1))
                    ->from('t_respint as r')
                    ->whereColumn('r.uid', "u.$userKey")
                    ->where('r.sid', $surId)
                    ->whereNotIn('r.status', [3, 4, 5]);
            });
        } else {
            $q->whereNotExists(function ($sub) use ($surId, $userKey) {
                $sub->select(DB::raw(1))
                    ->from('t_respint as r')
                    ->whereColumn('r.uid', "u.$userKey")
                    ->where('r.sid', $surId);
            });
        }
    }

    $q->where('u.confirm', 1);
    $q->where('u.active', 1);

    if (!empty($sessi)) {
        $map  = ['Uomo' => 1, 'Donna' => 2, '1' => 1, '2' => 2];
        $vals = array_values(array_unique(array_filter(array_map(function ($v) use ($map) {
            $v = trim((string) $v);
            return $map[$v] ?? null;
        }, $sessi))));

        if (!empty($vals)) {
            $q->whereIn('u.gender', $vals);
        }
    }

    $etaMin = $etaMin ?? 18;
    $etaMax = $etaMax ?? 99;

    $dataMin = now()->subYears($etaMax)->toDateString();
    $dataMax = now()->subYears($etaMin)->toDateString();

    $q->whereBetween('u.birth_date', [$dataMin, $dataMax]);

    if (!empty($reg)) {
        $q->whereIn('u.reg', $reg);
    }

    if (!empty($aree)) {
        $q->whereIn('u.area', $aree);
    }

    if (!empty($prov)) {
        $q->whereIn('u.province_id', $prov);
    }

    if (!empty($amp)) {
        $q->whereIn('u.amp', $amp);
    }

    if (!empty($iscrittoDal)) {
    $q->whereNotNull('u.reg_date')
      ->where('u.reg_date', '!=', '')
      ->whereRaw('CAST(LEFT(u.reg_date, 4) AS UNSIGNED) >= ?', [$iscrittoDal]);
}

    if (!empty($excludeCodes)) {
        $q->whereNotIn('u.user_id', function ($sub) use ($excludeCodes) {
            $sub->select('r.uid')
                ->from('t_respint as r')
                ->whereIn('r.sid', $excludeCodes);
        });
    }

    if (!empty($excludeUids)) {
        $q->whereNotIn('u.user_id', $excludeUids);
    }

    return $q;
}

private function interpolateSql(string $sql, array $bindings): string
{
    $parts = explode('?', $sql);
    $out = array_shift($parts);

    foreach ($bindings as $binding) {
        if (is_null($binding)) {
            $replacement = 'NULL';
        } elseif (is_numeric($binding)) {
            $replacement = (string) $binding;
        } else {
            $replacement = "'" . str_replace("'", "''", (string) $binding) . "'";
        }

        $out .= $replacement . (count($parts) ? array_shift($parts) : '');
    }

    if (count($parts)) {
        $out .= implode('', $parts);
    }

    return $out;
}

private function countDistinctUsersAcrossSamples(string $surId, array $samples, array $excludeCodes = []): int
{
    $union = null;

    foreach ($samples as $sample) {
        $q = $this->buildSampleQuery($surId, (array) $sample, $excludeCodes)
            ->select('u.user_id');

        if ($union === null) {
            $union = $q;
        } else {
            $union->union($q);
        }
    }

    if ($union === null) {
        return 0;
    }

    return DB::query()
        ->fromSub($union, 'sample_union')
        ->distinct('user_id')
        ->count('user_id');
}

}
