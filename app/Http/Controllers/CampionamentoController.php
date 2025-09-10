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
}
