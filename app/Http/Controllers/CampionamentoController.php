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

    public function utentiDisponibili(Request $request)
    {
        $surId      = (string) $request->input('sur_id');
        $samples    = $request->input('samples', []);
        $excludeRaw = $request->input('exclude_codes', '');
        $debug      = (bool) $request->boolean('debug');

        // normalizza codici esclusione (separati da ;)
        if (is_string($excludeRaw)) {
            $excludeCodes = array_values(array_filter(array_map('trim', explode(';', $excludeRaw))));
        } elseif (is_array($excludeRaw)) {
            $excludeCodes = array_values(array_filter(array_map('trim', $excludeRaw)));
        } else {
            $excludeCodes = [];
        }

        $userKey = 'user_id'; // cambia in 'uid' se la colonna è differente
        $items   = [];

        // Helper per interpolare la SQL completa (senza preg_replace)
        $interpolateSql = function (string $sql, array $bindings) {
            $parts = explode('?', $sql);
            $out   = array_shift($parts);
            foreach ($bindings as $b) {
                if (is_null($b)) {
                    $rep = 'NULL';
                } elseif (is_numeric($b)) {
                    $rep = (string) $b;
                } else {
                    $rep = "'" . str_replace("'", "''", (string) $b) . "'";
                }
                $out .= $rep . (count($parts) ? array_shift($parts) : '');
            }
            if (count($parts)) { $out .= implode('', $parts); }
            return $out;
        };

        // ========= Conteggi per sottocampione =========
        foreach ($samples as $i => $s) {
            $sessi    = isset($s['sesso']) ? (array) $s['sesso'] : [];
            $etaMin   = isset($s['eta_da']) ? (int) $s['eta_da'] : null;
            $etaMax   = isset($s['eta_a'])  ? (int) $s['eta_a']  : null;
            $reg      = isset($s['regioni']) ? (array) $s['regioni'] : [];
            $aree     = isset($s['aree']) ? (array) $s['aree'] : [];
            $prov     = isset($s['province_id']) ? (array) $s['province_id'] : [];
            $targetId = isset($s['target_id']) ? (int) $s['target_id'] : null;

            $q = UserInfo::query()
                ->from('t_user_info') // per whereColumn nei subquery
                ->attiviEConfermati()
                ->Sesso($sessi)
                ->Eta($etaMin, $etaMax)      // default 18-99 inside
                ->Regioni($reg)
                ->Aree($aree)
                ->Province($prov)
                ->Target($targetId, $userKey)
                ->NonGiaRespintiPerRicerca($surId, $userKey)
                ->EscludiRicerche($excludeCodes, $userKey);

            $count = (clone $q)->count();

            $row = ['index' => $i, 'count' => $count];

            if ($debug) {
                $sql      = (string) $q->toSql();
                $bindings = $q->getBindings();
                $row['sql_full'] = $interpolateSql($sql, $bindings);
            }

            $items[] = $row;
        }

        // ========= Totale UNICO (OR tra sottocampioni) =========
        $totalQ = UserInfo::query()
            ->from('t_user_info as u')
            ->where('u.confirm', 1)
            ->where('u.active', 1)
            // escludo chi è già in t_respint per la ricerca corrente
            ->whereNotExists(function ($sub) use ($surId, $userKey) {
                $sub->select(DB::raw(1))
                    ->from('t_respint as r')
                    ->whereColumn('r.uid', "u.$userKey")
                    ->where('r.sid', $surId);
            });

        if (!empty($excludeCodes)) {
            // escludo chi ha partecipazioni pregresse (status <> 0) nei sid indicati
            $totalQ->whereNotExists(function ($sub) use ($excludeCodes, $userKey) {
                $sub->select(DB::raw(1))
                    ->from('t_respint as r2')
                    ->whereColumn('r2.uid', "u.$userKey")
                    ->whereIn('r2.sid', $excludeCodes)
                    ->where('r2.status', '<>', 0);
            });
        }

        if (!empty($samples)) {
            $totalQ->where(function ($OR) use ($samples) {
                foreach ($samples as $s) {
                    $OR->orWhere(function ($q) use ($s) {
                        $sessi = isset($s['sesso']) ? (array) $s['sesso'] : [];
                        if (!empty($sessi)) {
                            $map  = ['Uomo' => 1, 'Donna' => 2, '1' => 1, '2' => 2];
                            $vals = array_values(array_unique(array_filter(array_map(function ($v) use ($map) {
                                $v = trim((string) $v);
                                return $map[$v] ?? null;
                            }, $sessi))));
                            if (!empty($vals)) $q->whereIn('u.gender', $vals);
                        }

                        $etaMin = isset($s['eta_da']) ? (int) $s['eta_da'] : 18;
                        $etaMax = isset($s['eta_a'])  ? (int) $s['eta_a']  : 99;
                        $expr   = "TIMESTAMPDIFF(YEAR, STR_TO_DATE(u.birth_date, '%Y-%m-%d'), CURDATE())";
                        $q->whereRaw("$expr BETWEEN ? AND ?", [$etaMin, $etaMax]);

                        if (!empty($s['regioni']))     $q->whereIn('u.reg', (array) $s['regioni']);
                        if (!empty($s['aree']))        $q->whereIn('u.area', (array) $s['aree']);
                        if (!empty($s['province_id'])) $q->whereIn('u.province_id', (array) $s['province_id']);
                        if (!empty($s['ampiezza']))    $q->whereIn('u.amp', (array) $s['ampiezza']);

                        if (!empty($s['target_id'])) {
                            $q->whereExists(function ($sub) use ($s) {
                                $sub->select(DB::raw(1))
                                    ->from('utenti_target as ut')
                                    ->whereColumn('ut.uid', 'u.user_id')
                                    ->where('ut.target_id', (int) $s['target_id']);
                            });
                        }
                    });
                }
            });
        }

        $total = (clone $totalQ)->count();

        $totalSqlFull = null;
        if ($debug) {
            $totalSqlFull  = $interpolateSql((string) $totalQ->toSql(), $totalQ->getBindings());
        }

        return response()
            ->json([
                'items'          => $items,
                'total'          => $total,
                'total_sql_full' => $totalSqlFull, // utile se vuoi tenerlo visibile in futuro
            ], 200, ['Content-Type' => 'application/json'])
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

public function creaCampioni(Request $request)
{
    // === Input ===
    $surId   = (string) $request->input('sur_id');
    $samples = $request->input('samples', []); // [{... , invite:int}, ...]

    if (!$surId || !is_array($samples) || !count($samples)) {
        return response()->json(['error' => 'Parametri mancanti'], 422);
    }

    $userKey = 'user_id'; // cambia se la colonna è diversa

    // === Recupero ENV dalla t_surveys_env ===
    // Prelevo in forma name => value (così copre anche prj_name, survey_object, ecc.)
        // === Recupero ENV dalla t_surveys_env (robusto, case-insensitive, alias) ===
        $envRows = DB::table('t_surveys_env')
            ->where('sid', $surId)
            ->select('name', 'value')
            ->get();

        $env = [];
        foreach ($envRows as $row) {
            $k = strtolower(trim((string)$row->name));
            $env[$k] = is_string($row->value) ? trim($row->value) : $row->value;
        }

        // helper: prima chiave non vuota
        $firstNonEmpty = function(array $map, array $keys) {
            foreach ($keys as $k) {
                $kk = strtolower($k);
                if (isset($map[$kk]) && $map[$kk] !== '' && $map[$kk] !== null) {
                    return $map[$kk];
                }
            }
            return '';
        };

        // alias per ciascun campo
        $prj   = $firstNonEmpty($env, ['prj_name','prj','project','prjname']);
        $argo  = $firstNonEmpty($env, ['survey_object','argo','argomento','object']);
        $bytes = $firstNonEmpty($env, ['prize_complete','bytes','premio_complete','premio','reward']);
        $loi   = $firstNonEmpty($env, ['length_of_interview','loi','length','durata_intervista']);

        // Fallback da t_panel_control se prj è vuoto
        if ($prj === '' || $prj === null) {
            $prj = (string) (PanelControl::where('sur_id', $surId)->value('prj') ?? '');
        }

        // (opzionale) fallback su description come argo se argo vuoto
        if ($argo === '' || $argo === null) {
            $argo = (string) (PanelControl::where('sur_id', $surId)->value('description') ?? '');
        }


    // Per sicurezza: se alcune chiavi sono altrove, aggiungi eventuali fallback qui.

    // === Selezione utenti per ciascun sottocampione (evitiamo duplicati cross-sample) ===
    $giaSelezionati = [];          // set di UID già presi
    $righeSelezionate = collect(); // rows finali per CSV

    foreach ($samples as $s) {
        // inviti richiesti (min 1)
        $invite = isset($s['invite']) ? (int)$s['invite'] : 1;
        if ($invite < 1) $invite = 1;

        // normalizza filtri
        $sessi    = isset($s['sesso']) ? (array)$s['sesso'] : [];
        $etaMin   = isset($s['eta_da']) ? (int)$s['eta_da'] : null;
        $etaMax   = isset($s['eta_a'])  ? (int)$s['eta_a']  : null;
        $reg      = isset($s['regioni']) ? (array)$s['regioni'] : [];
        $aree     = isset($s['aree']) ? (array)$s['aree'] : [];
        $prov     = isset($s['province_id']) ? (array)$s['province_id'] : [];
        $amp      = isset($s['ampiezza']) ? (array)$s['ampiezza'] : [];
        $targetId = isset($s['target_id']) ? (int)$s['target_id'] : null;

        // eventuale lista di codici ricerche da escludere (status <> 0)
        $excludeRaw = $request->input('exclude_codes', '');
        if (is_string($excludeRaw)) {
            $excludeCodes = array_values(array_filter(array_map('trim', explode(';', $excludeRaw))));
        } elseif (is_array($excludeRaw)) {
            $excludeCodes = array_values(array_filter(array_map('trim', $excludeRaw)));
        } else {
            $excludeCodes = [];
        }

        // Query base (stesse regole dei conteggi)
        $q = \App\Models\UserInfo::query()
            ->from('t_user_info as u')
            ->where('u.confirm', 1)
            ->where('u.active', 1)
            // non già in t_respint per la ricerca corrente
            ->whereNotExists(function($sub) use ($surId, $userKey) {
                $sub->select(DB::raw(1))
                    ->from('t_respint as r')
                    ->whereColumn('r.uid', "u.$userKey")
                    ->where('r.sid', $surId);
            });

        if (!empty($excludeCodes)) {
            $q->whereNotExists(function($sub) use ($excludeCodes, $userKey) {
                $sub->select(DB::raw(1))
                    ->from('t_respint as r2')
                    ->whereColumn('r2.uid', "u.$userKey")
                    ->whereIn('r2.sid', $excludeCodes)
                    ->where('r2.status', '<>', 0);
            });
        }

        // Filtri specifici del sottocampione
        if (!empty($sessi)) {
            $map  = ['Uomo'=>1,'Donna'=>2,'1'=>1,'2'=>2];
            $vals = array_values(array_unique(array_filter(array_map(function($v) use ($map){
                $v = trim((string)$v);
                return $map[$v] ?? null;
            }, $sessi))));
            if (!empty($vals)) $q->whereIn('u.gender', $vals);
        }

        $etaMin = $etaMin ?? 18;
        $etaMax = $etaMax ?? 99;
        $q->whereRaw("TIMESTAMPDIFF(YEAR, STR_TO_DATE(u.birth_date, '%Y-%m-%d'), CURDATE()) BETWEEN ? AND ?", [$etaMin, $etaMax]);

        if (!empty($reg))  $q->whereIn('u.reg', (array)$reg);
        if (!empty($aree)) $q->whereIn('u.area', (array)$aree);
        if (!empty($prov)) $q->whereIn('u.province_id', (array)$prov);
        if (!empty($amp))  $q->whereIn('u.amp', (array)$amp);

        if (!empty($targetId)) {
            $q->whereExists(function($sub) use ($targetId, $userKey) {
                $sub->select(DB::raw(1))
                    ->from('utenti_target as ut')
                    ->whereColumn('ut.uid', "u.$userKey")
                    ->where('ut.target_id', (int)$targetId);
            });
        }

        if (!empty($giaSelezionati)) {
            $q->whereNotIn("u.$userKey", $giaSelezionati);
        }

        // Selezione utenti (puoi cambiare ordinamento a tuo gusto; RAND() è costoso)
        $users = $q->select([
                "u.$userKey as uid",
                'u.email',
                'u.first_name as firstName',
                'u.gender',
                'u.token'
            ])
            ->orderBy('u.' . $userKey)
            ->limit($invite)
            ->get();

        foreach ($users as $u) {
            $gs = ($u->gender == 1) ? 'o' : (($u->gender == 2) ? 'a' : ''); // 1->o, 2->a, altro vuoto
            $righeSelezionate->push([
                'uid'          => (string)$u->uid,
                'email'        => (string)$u->email,
                'firstName'    => (string)$u->firstName,
                'genderSuffix' => $gs,
                'sid'          => (string)$surId,
                'prj'          => (string)$prj,
                'argo'         => (string)$argo,
                'bytes'        => (string)$bytes,
                'loi'          => (string)$loi,
                'token'        => (string)($u->token ?? ''),
            ]);
            $giaSelezionati[] = (string)$u->uid;
        }
    }

    // === Insert in t_respint (status=0, iid=-1, prj_name=$prj) ===
    if ($righeSelezionate->isNotEmpty()) {
        $now = now();
        $toInsert = $righeSelezionate->map(function($r) use ($surId, $prj, $userKey) {
            return [
                'sid'      => $surId,
                'uid'      => $r['uid'],
                'status'   => 0,
                'iid'      => -1,
                'prj_name' => $prj,
                // opzionale: 'created_at' => now(), 'updated_at' => now()
            ];
        })->all();

        // Evita duplicati se esiste unique key; altrimenti usa insert semplice
        DB::table('t_respint')->insertOrIgnore($toInsert);
    }

    // === Costruzione CSV ===
    $header = 'uid;email;firstName;genderSuffix;sid;prj;argo;bytes;loi;token';
    $lines = $righeSelezionate->map(function($r) {
        // attento ai ; nei campi: se necessario wrappare con doppi apici e rimpiazzare apici
        $vals = [
            $r['uid'], $r['email'], $r['firstName'], $r['genderSuffix'],
            $r['sid'], $r['prj'], $r['argo'], $r['bytes'], $r['loi'], $r['token']
        ];
        $escaped = array_map(function($v){
            $v = (string)$v;
            return (strpos($v, ';') !== false || strpos($v, '"') !== false)
                ? '"'.str_replace('"', '""', $v).'"'
                : $v;
        }, $vals);
        return implode(';', $escaped);
    })->all();

    $csv = $header . "\n" . implode("\n", $lines);
    $filename = 'campionamento_' . $surId . '_' . date('Ymd_His') . '.csv';

    // Puoi anche salvarlo su storage se preferisci:
    // Storage::disk('public')->put($filename, $csv);

    return response()->json([
        'enabled_count' => $righeSelezionate->count(),
        'filename'      => $filename,
        'csv_text'      => $csv,         // testo grezzo per bottone "Copia"
        'csv_base64'    => base64_encode($csv), // per download client-side
    ]);
}


}
