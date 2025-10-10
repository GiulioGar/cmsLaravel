<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PanelControl;
use App\Models\UserInfo;

class CampionamentoController extends Controller
{
    public function index()
    {
        // 1) Ricerche con panel=1, stato=0
        $ricerche = PanelControl::select('sur_id', 'description')
            ->where('panel', 1)
            ->where('stato', 0)
            ->groupBy('sur_id', 'description')
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
    $surId      = (string) $request->input('sur_id');
    $samples    = $request->input('samples', []);
    $excludeRaw = $request->input('exclude_codes', '');
    $debug      = (bool) $request->boolean('debug');

    // normalizza codici esclusione
    if (is_string($excludeRaw)) {
        $excludeCodes = array_values(array_filter(array_map('trim', explode(';', $excludeRaw))));
    } elseif (is_array($excludeRaw)) {
        $excludeCodes = array_values(array_filter(array_map('trim', $excludeRaw)));
    } else {
        $excludeCodes = [];
    }

    $userKey = 'user_id';
    $items   = [];

    // Helper per interpolare SQL
    $interpolateSql = function (string $sql, array $bindings) {
        $parts = explode('?', $sql);
        $out   = array_shift($parts);
        foreach ($bindings as $b) {
            if (is_null($b))      $rep = 'NULL';
            elseif (is_numeric($b)) $rep = (string) $b;
            else $rep = "'" . str_replace("'", "''", (string) $b) . "'";
            $out .= $rep . (count($parts) ? array_shift($parts) : '');
        }
        if (count($parts)) $out .= implode('', $parts);
        return $out;
    };

    // ========= Conteggi per sottocampione =========
    foreach ($samples as $i => $s) {
        $followup = isset($s['followup']) ? (bool)$s['followup'] : false;
        $sessi    = isset($s['sesso']) ? (array) $s['sesso'] : [];
        $etaMin   = isset($s['eta_da']) ? (int) $s['eta_da'] : null;
        $etaMax   = isset($s['eta_a'])  ? (int) $s['eta_a']  : null;
        $reg      = isset($s['regioni']) ? (array) $s['regioni'] : [];
        $aree     = isset($s['aree']) ? (array) $s['aree'] : [];
        $prov     = isset($s['province_id']) ? (array) $s['province_id'] : [];
        $targetId = isset($s['target_id']) ? (int) $s['target_id'] : null;

        $q = UserInfo::query()
            ->from('t_user_info as u')
            ->where('u.confirm', 1)
            ->where('u.active', 1);

        // 🔸 Logica follow-up / standard
        if ($followup) {
            // Solo utenti già presenti con status != 3,4,5
            $q->whereExists(function ($sub) use ($surId, $userKey) {
                $sub->select(DB::raw(1))
                    ->from('t_respint as r')
                    ->whereColumn('r.uid', "u.$userKey")
                    ->where('r.sid', $surId)
                    ->whereNotIn('r.status', [3,4,5]);
            });
        } else {
            // Escludo utenti già presenti
            $q->whereNotExists(function ($sub) use ($surId, $userKey) {
                $sub->select(DB::raw(1))
                    ->from('t_respint as r')
                    ->whereColumn('r.uid', "u.$userKey")
                    ->where('r.sid', $surId);
            });
        }

        // altri filtri
        if (!empty($sessi)) {
            $map  = ['Uomo'=>1,'Donna'=>2,'1'=>1,'2'=>2];
            $vals = array_values(array_unique(array_filter(array_map(fn($v)=>$map[trim($v)]??null,$sessi))));
            if (!empty($vals)) $q->whereIn('u.gender', $vals);
        }
        $etaMin = $etaMin ?? 18;
        $etaMax = $etaMax ?? 99;
        $q->whereRaw("TIMESTAMPDIFF(YEAR, STR_TO_DATE(u.birth_date, '%Y-%m-%d'), CURDATE()) BETWEEN ? AND ?", [$etaMin, $etaMax]);

        if (!empty($reg))  $q->whereIn('u.reg', (array)$reg);
        if (!empty($aree)) $q->whereIn('u.area', (array)$aree);
        if (!empty($prov)) $q->whereIn('u.province_id', (array)$prov);
        if (!empty($targetId)) {
            $q->whereExists(function ($sub) use ($targetId, $userKey) {
                $sub->select(DB::raw(1))
                    ->from('utenti_target as ut')
                    ->whereColumn('ut.uid', "u.$userKey")
                    ->where('ut.target_id', $targetId);
            });
        }

        $count = (clone $q)->count();
        $row = ['index'=>$i,'count'=>$count];
        if ($debug) $row['sql_full'] = $interpolateSql($q->toSql(), $q->getBindings());
        $items[] = $row;
    }

    // ========= Totale unico corretto =========
    $uidUnici = collect();

    foreach ($samples as $s) {
        $followup = isset($s['followup']) ? (bool)$s['followup'] : false;
        $sessi    = isset($s['sesso']) ? (array) $s['sesso'] : [];
        $etaMin   = isset($s['eta_da']) ? (int) $s['eta_da'] : null;
        $etaMax   = isset($s['eta_a'])  ? (int) $s['eta_a']  : null;
        $reg      = isset($s['regioni']) ? (array) $s['regioni'] : [];
        $aree     = isset($s['aree']) ? (array) $s['aree'] : [];
        $prov     = isset($s['province_id']) ? (array) $s['province_id'] : [];
        $targetId = isset($s['target_id']) ? (int) $s['target_id'] : null;

        $q = UserInfo::query()
            ->from('t_user_info as u')
            ->where('u.confirm', 1)
            ->where('u.active', 1);

        if ($followup) {
            $q->whereExists(function ($sub) use ($surId, $userKey) {
                $sub->select(DB::raw(1))
                    ->from('t_respint as r')
                    ->whereColumn('r.uid', "u.$userKey")
                    ->where('r.sid', $surId)
                    ->whereNotIn('r.status', [3,4,5]);
            });
        } else {
            $q->whereNotExists(function ($sub) use ($surId, $userKey) {
                $sub->select(DB::raw(1))
                    ->from('t_respint as r')
                    ->whereColumn('r.uid', "u.$userKey")
                    ->where('r.sid', $surId);
            });
        }

        // Applichiamo i filtri demografici
        if (!empty($sessi)) {
            $map  = ['Uomo'=>1,'Donna'=>2,'1'=>1,'2'=>2];
            $vals = array_values(array_unique(array_filter(array_map(fn($v)=>$map[trim($v)]??null,$sessi))));
            if (!empty($vals)) $q->whereIn('u.gender', $vals);
        }
        $etaMin = $etaMin ?? 18;
        $etaMax = $etaMax ?? 99;
        $q->whereRaw("TIMESTAMPDIFF(YEAR, STR_TO_DATE(u.birth_date, '%Y-%m-%d'), CURDATE()) BETWEEN ? AND ?", [$etaMin, $etaMax]);
        if (!empty($reg))  $q->whereIn('u.reg', $reg);
        if (!empty($aree)) $q->whereIn('u.area', $aree);
        if (!empty($prov)) $q->whereIn('u.province_id', $prov);
        if (!empty($targetId)) {
            $q->whereExists(function ($sub) use ($targetId, $userKey) {
                $sub->select(DB::raw(1))
                    ->from('utenti_target as ut')
                    ->whereColumn('ut.uid', "u.$userKey")
                    ->where('ut.target_id', $targetId);
            });
        }

        $uids = $q->pluck("u.$userKey")->all();
        $uidUnici = $uidUnici->merge($uids);
    }

    $total = $uidUnici->unique()->count();

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
            $followup = isset($s['followup']) ? (bool)$s['followup'] : false;
            $invite = max(1, (int)($s['invite'] ?? 1));

            $sessi    = (array)($s['sesso'] ?? []);
            $etaMin   = $s['eta_da'] ?? 18;
            $etaMax   = $s['eta_a'] ?? 99;
            $reg      = (array)($s['regioni'] ?? []);
            $aree     = (array)($s['aree'] ?? []);
            $prov     = (array)($s['province_id'] ?? []);
            $amp      = (array)($s['ampiezza'] ?? []);
            $targetId = $s['target_id'] ?? null;

            $q = UserInfo::query()
                ->from('t_user_info as u')
                ->where('u.confirm',1)
                ->where('u.active',1);

            if ($followup) {
                $q->whereExists(function($sub) use ($surId, $userKey) {
                    $sub->select(DB::raw(1))
                        ->from('t_respint as r')
                        ->whereColumn('r.uid',"u.$userKey")
                        ->where('r.sid',$surId)
                        ->whereNotIn('r.status',[3,4,5]);
                });
            } else {
                $q->whereNotExists(function($sub) use ($surId, $userKey) {
                    $sub->select(DB::raw(1))
                        ->from('t_respint as r')
                        ->whereColumn('r.uid',"u.$userKey")
                        ->where('r.sid',$surId);
                });
            }

            if (!empty($sessi)) {
                $map = ['Uomo'=>1,'Donna'=>2,'1'=>1,'2'=>2];
                $vals = array_values(array_unique(array_filter(array_map(fn($v)=>$map[trim($v)]??null,$sessi))));
                if (!empty($vals)) $q->whereIn('u.gender',$vals);
            }

            $q->whereRaw("TIMESTAMPDIFF(YEAR, STR_TO_DATE(u.birth_date, '%Y-%m-%d'), CURDATE()) BETWEEN ? AND ?", [$etaMin, $etaMax]);
            if (!empty($reg))  $q->whereIn('u.reg', $reg);
            if (!empty($aree)) $q->whereIn('u.area', $aree);
            if (!empty($prov)) $q->whereIn('u.province_id', $prov);
            if (!empty($amp))  $q->whereIn('u.amp', $amp);

            if (!empty($targetId)) {
                $q->whereExists(function($sub) use ($targetId, $userKey) {
                    $sub->select(DB::raw(1))
                        ->from('utenti_target as ut')
                        ->whereColumn('ut.uid',"u.$userKey")
                        ->where('ut.target_id',(int)$targetId);
                });
            }

            if ($followup && !empty($giaSelezionatiFollowup)) {
                $q->whereNotIn("u.$userKey", $giaSelezionatiFollowup);
            }
            if (!$followup && !empty($giaSelezionatiStandard)) {
                $q->whereNotIn("u.$userKey", $giaSelezionatiStandard);
            }

            $users = $q->select([
                    "u.$userKey as uid",
                    'u.email',
                    'u.first_name as firstName',
                    'u.gender',
                    'u.token'
                ])
                ->orderBy('u.'.$userKey)
                ->limit($invite)
                ->get();

            foreach ($users as $u) {
                $gs = ($u->gender==1)?'o':(($u->gender==2)?'a':'');
                $righeSelezionate->push([
                    'uid'=>$u->uid,
                    'email'=>$u->email,
                    'firstName'=>$u->firstName,
                    'genderSuffix'=>$gs,
                    'sid'=>$surId,
                    'prj'=>$prj,
                    'argo'=>$argo,
                    'bytes'=>$bytes,
                    'loi'=>$loi,
                    'token'=>$u->token??'',
                ]);
                if ($followup)
                    $giaSelezionatiFollowup[] = (string)$u->uid;
                else
                    $giaSelezionatiStandard[] = (string)$u->uid;
            }
        }

        // 🔸 Inserimento solo se NON follow-up
        if ($righeSelezionate->isNotEmpty()) {
            $toInsert = $righeSelezionate->filter(fn($r) => !in_array($r['uid'], $giaSelezionatiFollowup))
                ->map(fn($r)=>[
                    'sid'=>$surId,
                    'uid'=>$r['uid'],
                    'status'=>0,
                    'iid'=>-1,
                    'prj_name'=>$prj,
                ])->all();

            if (!empty($toInsert)) {
                DB::table('t_respint')->insertOrIgnore($toInsert);
            }
        }

        // === CSV ===
        $header = 'uid;email;firstName;genderSuffix;sid;prj;argo;bytes;loi;token';
        $lines = $righeSelezionate->map(function($r){
            $vals = [$r['uid'],$r['email'],$r['firstName'],$r['genderSuffix'],$r['sid'],$r['prj'],$r['argo'],$r['bytes'],$r['loi'],$r['token']];
            $escaped = array_map(fn($v)=>(strpos($v,';')!==false||strpos($v,'"')!==false)?'"'.str_replace('"','""',$v).'"':$v,$vals);
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
}
