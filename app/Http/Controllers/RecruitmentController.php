<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecruitmentController extends Controller
{
    public function index()
    {
        $currentYear = now()->year;
        $currentMonth = now()->format('m');

        $years = [];
        for ($year = $currentYear; $year >= 2021; $year--) {
            $years[] = $year;
        }

        $months = [
            '01' => 'Gennaio',
            '02' => 'Febbraio',
            '03' => 'Marzo',
            '04' => 'Aprile',
            '05' => 'Maggio',
            '06' => 'Giugno',
            '07' => 'Luglio',
            '08' => 'Agosto',
            '09' => 'Settembre',
            '10' => 'Ottobre',
            '11' => 'Novembre',
            '12' => 'Dicembre',
        ];

        $referrals = $this->getActiveReferrals();

        return view('recruitment.index', compact(
            'currentYear',
            'currentMonth',
            'years',
            'months',
            'referrals'
        ));
    }

    public function daily(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        if ($month < 1 || $month > 12) {
            $month = (int) now()->month;
        }

        if ($year < 2021 || $year > ((int) now()->year + 1)) {
            $year = (int) now()->year;
        }

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $referenceDate = $startDate->format('Y-m-d');

        $referrals = $this->getActiveReferrals();

        // CPI per il mese selezionato
        $cpiRows = DB::table('t_recruitment_referral_costs')
            ->select('referral_id', 'start_date', 'end_date', 'cpi', 'age_min', 'age_max')
            ->where('is_active', 1)
            ->whereDate('start_date', '<=', $endDate->format('Y-m-d'))
            ->where(function ($q) use ($referenceDate) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $referenceDate);
            })
            ->orderBy('start_date')
            ->get();

        $cpiByReferral = [];
        foreach ($cpiRows as $row) {
            $cpiByReferral[$row->referral_id][] = [
                'start_date' => $row->start_date,
                'end_date'   => $row->end_date,
                'cpi'        => (float) $row->cpi,
                'age_min'    => $row->age_min !== null ? (int) $row->age_min : null,
                'age_max'    => $row->age_max !== null ? (int) $row->age_max : null,
            ];
        }

        $ageBreakpoints = [];
        foreach ($cpiByReferral as $_periods) {
            foreach ($_periods as $_p) {
                if ($_p['age_max'] !== null) {
                    $bp = (int) $_p['age_max'];
                    if (!in_array($bp, $ageBreakpoints, true)) $ageBreakpoints[] = $bp;
                }
                if ($_p['age_min'] !== null) {
                    $bp = (int) $_p['age_min'] - 1;
                    if ($bp >= 0 && !in_array($bp, $ageBreakpoints, true)) $ageBreakpoints[] = $bp;
                }
            }
        }
        sort($ageBreakpoints);

        $selectClauses = [
            'provenienza',
            DB::raw('COUNT(*) as registered'),
            DB::raw("SUM(CASE WHEN birth_date IS NOT NULL AND birth_date <> '0000-00-00' THEN 1 ELSE 0 END) as has_birth_date"),
        ];
        foreach ($ageBreakpoints as $bp) {
            $selectClauses[] = DB::raw(
                "SUM(CASE WHEN birth_date IS NOT NULL AND birth_date <> '0000-00-00'"
                . " AND TIMESTAMPDIFF(YEAR, birth_date, reg_date) <= {$bp}"
                . " THEN 1 ELSE 0 END) as age_under_{$bp}"
            );
        }

        $rows = DB::table('t_user_info')
            ->select($selectClauses)
            ->where('reg_date', '>=', $startDate->format('Y-m-d'))
            ->where('reg_date', '<', $startDate->copy()->addMonth()->format('Y-m-d'))
            ->where('email', 'not like', '%.top')
            ->whereNotNull('provenienza')
            ->where('provenienza', '<>', '')
            ->groupBy('provenienza')
            ->get();

        $sourceStats = [];
        foreach ($rows as $row) {
            $entry = [
                'registered'     => (int) $row->registered,
                'has_birth_date' => isset($row->has_birth_date) ? (int) $row->has_birth_date : 0,
            ];
            foreach ($ageBreakpoints as $bp) {
                $key = 'age_under_' . $bp;
                $entry[$key] = isset($row->$key) ? (int) $row->$key : 0;
            }
            $sourceStats[$row->provenienza] = $entry;
        }

        $mappedSources = [];
        $fallbackReferral = null;
        $groupedReferrals = [];

        foreach ($referrals as $referral) {
            if ($referral->group_type === 'fallback') {
                $fallbackReferral = $referral;
                continue;
            }

            $sources = $this->parseSourceCodes($referral->source_codes);
            $monthSourceData = ['registered' => 0, 'has_birth_date' => 0];
            foreach ($ageBreakpoints as $bp) $monthSourceData['age_under_' . $bp] = 0;
            $matchedSources = [];

            foreach ($sources as $source) {
                $mappedSources[$source] = true;
                if (!isset($sourceStats[$source])) continue;
                $s = $sourceStats[$source];
                $monthSourceData['registered']     += $s['registered'];
                $monthSourceData['has_birth_date'] += $s['has_birth_date'] ?? 0;
                foreach ($ageBreakpoints as $bp) {
                    $monthSourceData['age_under_' . $bp] += $s['age_under_' . $bp] ?? 0;
                }
                $matchedSources[] = $source;
            }

            if ($monthSourceData['registered'] <= 0) continue;

            $cost = round($this->computeSegmentedCost($referral->id, $referenceDate, $monthSourceData, $cpiByReferral, $ageBreakpoints), 2);

            $groupedReferrals[] = [
                'code'    => $referral->code,
                'label'   => $referral->title,
                'icon'    => $referral->icon,
                'total'   => $monthSourceData['registered'],
                'cost'    => $cost,
                'sources' => $matchedSources,
                'sort_order' => (int) $referral->sort_order,
            ];
        }

        if ($fallbackReferral) {
            $monthSourceData = ['registered' => 0, 'has_birth_date' => 0];
            foreach ($ageBreakpoints as $bp) $monthSourceData['age_under_' . $bp] = 0;
            $fallbackSources = [];

            foreach ($sourceStats as $source => $s) {
                if (isset($mappedSources[$source])) continue;
                $monthSourceData['registered']     += $s['registered'];
                $monthSourceData['has_birth_date'] += $s['has_birth_date'] ?? 0;
                foreach ($ageBreakpoints as $bp) {
                    $monthSourceData['age_under_' . $bp] += $s['age_under_' . $bp] ?? 0;
                }
                $fallbackSources[] = $source;
            }

            if ($monthSourceData['registered'] > 0) {
                $cost = round($this->computeSegmentedCost($fallbackReferral->id, $referenceDate, $monthSourceData, $cpiByReferral, $ageBreakpoints), 2);
                $groupedReferrals[] = [
                    'code'    => $fallbackReferral->code,
                    'label'   => $fallbackReferral->title,
                    'icon'    => $fallbackReferral->icon,
                    'total'   => $monthSourceData['registered'],
                    'cost'    => $cost,
                    'sources' => $fallbackSources,
                    'sort_order' => (int) $fallbackReferral->sort_order,
                ];
            }
        }

        usort($groupedReferrals, function ($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });

        $totalRegistered = array_sum(array_column($groupedReferrals, 'total'));
        $totalCost = round(array_sum(array_column($groupedReferrals, 'cost')), 2);

        $monthLabel = ucfirst($startDate->locale('it')->translatedFormat('F Y'));

        return response()->json([
            'success'          => true,
            'month'            => str_pad($month, 2, '0', STR_PAD_LEFT),
            'year'             => $year,
            'month_label'      => $monthLabel,
            'total_registered' => $totalRegistered,
            'total_cost'       => $totalCost,
            'referrals'        => array_map(function ($item) {
                unset($item['sort_order']);
                return $item;
            }, $groupedReferrals),
        ]);
    }

public function costs(Request $request)
{
    $year = (int) $request->get('year', now()->year);

    if ($year < 2021 || $year > ((int) now()->year + 1)) {
        $year = (int) now()->year;
    }

    $referrals = $this->getActiveReferrals();

    $cpiRows = DB::table('t_recruitment_referral_costs')
        ->select('referral_id', 'start_date', 'end_date', 'cpi', 'age_min', 'age_max')
        ->where('is_active', 1)
        ->whereDate('start_date', '<=', $year . '-12-31')
        ->where(function ($query) use ($year) {
            $query->whereNull('end_date')
                ->orWhereDate('end_date', '>=', $year . '-01-01');
        })
        ->orderBy('start_date')
        ->get();

    $cpiByReferral = [];

    foreach ($cpiRows as $row) {
        if (!isset($cpiByReferral[$row->referral_id])) {
            $cpiByReferral[$row->referral_id] = [];
        }

        $cpiByReferral[$row->referral_id][] = [
            'start_date' => $row->start_date,
            'end_date'   => $row->end_date,
            'cpi'        => (float) $row->cpi,
            'age_min'    => $row->age_min !== null ? (int) $row->age_min : null,
            'age_max'    => $row->age_max !== null ? (int) $row->age_max : null,
        ];
    }

    $ageBreakpoints = [];
    foreach ($cpiByReferral as $_periods) {
        foreach ($_periods as $_period) {
            if ($_period['age_max'] !== null) {
                $bp = (int) $_period['age_max'];
                if (!in_array($bp, $ageBreakpoints, true)) {
                    $ageBreakpoints[] = $bp;
                }
            }
            if ($_period['age_min'] !== null) {
                $bp = (int) $_period['age_min'] - 1;
                if ($bp >= 0 && !in_array($bp, $ageBreakpoints, true)) {
                    $ageBreakpoints[] = $bp;
                }
            }
        }
    }
    sort($ageBreakpoints);

    $selectClauses = [
        DB::raw('MONTH(reg_date) as month_num'),
        'provenienza',
        DB::raw('COUNT(*) as registered'),
        DB::raw('SUM(CASE WHEN COALESCE(actions, 0) > 0 THEN 1 ELSE 0 END) as active'),
        DB::raw("SUM(CASE WHEN birth_date IS NOT NULL AND birth_date <> '0000-00-00' THEN 1 ELSE 0 END) as has_birth_date"),
    ];

    foreach ($ageBreakpoints as $bp) {
        $selectClauses[] = DB::raw(
            "SUM(CASE WHEN birth_date IS NOT NULL AND birth_date <> '0000-00-00'"
            . " AND TIMESTAMPDIFF(YEAR, birth_date, reg_date) <= {$bp}"
            . " THEN 1 ELSE 0 END) as age_under_{$bp}"
        );
    }

    $rows = DB::table('t_user_info')
        ->select($selectClauses)
        ->whereYear('reg_date', $year)
        ->where('email', 'not like', '%.top')
        ->whereNotNull('provenienza')
        ->where('provenienza', '<>', '')
        ->groupBy(DB::raw('MONTH(reg_date)'), 'provenienza')
        ->get();

    $sourceStatsByMonth = [];

    foreach ($rows as $row) {
        $monthNum = (int) $row->month_num;
        $source = $row->provenienza;

        if (!isset($sourceStatsByMonth[$monthNum])) {
            $sourceStatsByMonth[$monthNum] = [];
        }

        $entry = [
            'registered'     => (int) $row->registered,
            'active'         => (int) $row->active,
            'has_birth_date' => isset($row->has_birth_date) ? (int) $row->has_birth_date : 0,
        ];

        foreach ($ageBreakpoints as $bp) {
            $key = 'age_under_' . $bp;
            $entry[$key] = isset($row->$key) ? (int) $row->$key : 0;
        }

        $sourceStatsByMonth[$monthNum][$source] = $entry;
    }

    $mappedSources = [];
    $fallbackReferral = null;
    $tableRows = [];
    $today = now()->format('Y-m-d');

    foreach ($referrals as $referral) {
        if ($referral->group_type === 'fallback') {
            $fallbackReferral = $referral;
            continue;
        }

        $sources = $this->parseSourceCodes($referral->source_codes);

        $registered = 0;
        $active = 0;
        $cost = 0;
        $matchedSources = [];

        foreach ($sources as $source) {
            $mappedSources[$source] = true;
        }

        for ($month = 1; $month <= 12; $month++) {
            $monthRegistered = 0;
            $monthActive = 0;
            $monthAgeData = [];

            foreach ($sources as $source) {
                if (!isset($sourceStatsByMonth[$month][$source])) {
                    continue;
                }

                $monthRegistered += $sourceStatsByMonth[$month][$source]['registered'];
                $monthActive += $sourceStatsByMonth[$month][$source]['active'];

                $monthAgeData['has_birth_date'] = ($monthAgeData['has_birth_date'] ?? 0) + ($sourceStatsByMonth[$month][$source]['has_birth_date'] ?? 0);
                foreach ($ageBreakpoints as $bp) {
                    $key = 'age_under_' . $bp;
                    $monthAgeData[$key] = ($monthAgeData[$key] ?? 0) + ($sourceStatsByMonth[$month][$source][$key] ?? 0);
                }

                if (!in_array($source, $matchedSources, true)) {
                    $matchedSources[] = $source;
                }
            }

            if ($monthRegistered <= 0) {
                continue;
            }

            $registered += $monthRegistered;
            $active += $monthActive;

            $referenceDate = sprintf('%04d-%02d-01', $year, $month);
            $monthSourceData = array_merge(['registered' => $monthRegistered], $monthAgeData);
            $cost += $this->computeSegmentedCost($referral->id, $referenceDate, $monthSourceData, $cpiByReferral, $ageBreakpoints);
        }

        if ($registered <= 0) {
            continue;
        }

        $cost = round($cost, 2);
        $activeRate = $registered > 0 ? round(($active / $registered) * 100, 2) : 0;
        $avgCpi = $registered > 0 ? round($cost / $registered, 4) : 0;
        $cpa = $active > 0 ? round($cost / $active, 2) : 0;

        $hasCurrentCampaign = false;
        if (isset($cpiByReferral[$referral->id])) {
            foreach ($cpiByReferral[$referral->id] as $_cp) {
                $_pEnd = $_cp['end_date'] !== null ? substr($_cp['end_date'], 0, 10) : null;
                if (substr($_cp['start_date'], 0, 10) <= $today && ($_pEnd === null || $_pEnd >= $today)) {
                    $hasCurrentCampaign = true;
                    break;
                }
            }
        }

        $_bAcc = [];
        for ($_m = 1; $_m <= 12; $_m++) {
            $_mReg = 0;
            $_mAgeData = ['has_birth_date' => 0];
            foreach ($sources as $_src) {
                if (!isset($sourceStatsByMonth[$_m][$_src])) continue;
                $_mReg += $sourceStatsByMonth[$_m][$_src]['registered'];
                $_mAgeData['has_birth_date'] += ($sourceStatsByMonth[$_m][$_src]['has_birth_date'] ?? 0);
                foreach ($ageBreakpoints as $_bp) {
                    $_k = 'age_under_' . $_bp;
                    $_mAgeData[$_k] = ($_mAgeData[$_k] ?? 0) + ($sourceStatsByMonth[$_m][$_src][$_k] ?? 0);
                }
            }
            if ($_mReg <= 0) continue;
            $_ref = sprintf('%04d-%02d-01', $year, $_m);
            foreach ($this->computeMonthSegmentBreakdown($referral->id, $_ref, array_merge(['registered' => $_mReg], $_mAgeData), $cpiByReferral) as $_seg) {
                $_bk = ($_seg['age_min'] ?? '') . '_' . ($_seg['age_max'] ?? '');
                if (!isset($_bAcc[$_bk])) {
                    $_bAcc[$_bk] = ['age_min' => $_seg['age_min'], 'age_max' => $_seg['age_max'], 'count' => 0, 'cost' => 0.0];
                }
                $_bAcc[$_bk]['count'] += $_seg['count'];
                $_bAcc[$_bk]['cost']  += $_seg['cost'];
            }
        }
        $breakdown = count($_bAcc) > 1
            ? array_map(function ($b) { $b['cost'] = round($b['cost'], 2); return $b; }, array_values($_bAcc))
            : [];

        $tableRows[] = [
            'code' => $referral->code,
            'label' => $referral->title,
            'icon' => $referral->icon,
            'registered' => $registered,
            'active' => $active,
            'active_rate' => $activeRate,
            'cost' => $cost,
            'cpi' => $avgCpi,
            'cpa' => $cpa,
            'sources' => $matchedSources,
            'sort_order' => (int) $referral->sort_order,
            'has_current_campaign' => $hasCurrentCampaign,
            'breakdown' => $breakdown,
        ];
    }

    if ($fallbackReferral) {
        $registered = 0;
        $active = 0;
        $cost = 0;
        $fallbackSources = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthRegistered = 0;
            $monthActive = 0;
            $monthAgeData = [];

            if (isset($sourceStatsByMonth[$month])) {
                foreach ($sourceStatsByMonth[$month] as $source => $stats) {
                    if (isset($mappedSources[$source])) {
                        continue;
                    }

                    $monthRegistered += $stats['registered'];
                    $monthActive += $stats['active'];

                    $monthAgeData['has_birth_date'] = ($monthAgeData['has_birth_date'] ?? 0) + ($stats['has_birth_date'] ?? 0);
                    foreach ($ageBreakpoints as $bp) {
                        $key = 'age_under_' . $bp;
                        $monthAgeData[$key] = ($monthAgeData[$key] ?? 0) + ($stats[$key] ?? 0);
                    }

                    if (!in_array($source, $fallbackSources, true)) {
                        $fallbackSources[] = $source;
                    }
                }
            }

            if ($monthRegistered <= 0) {
                continue;
            }

            $registered += $monthRegistered;
            $active += $monthActive;

            $referenceDate = sprintf('%04d-%02d-01', $year, $month);
            $monthSourceData = array_merge(['registered' => $monthRegistered], $monthAgeData);
            $cost += $this->computeSegmentedCost($fallbackReferral->id, $referenceDate, $monthSourceData, $cpiByReferral, $ageBreakpoints);
        }

        if ($registered > 0) {
            $cost = round($cost, 2);
            $activeRate = $registered > 0 ? round(($active / $registered) * 100, 2) : 0;
            $avgCpi = $registered > 0 ? round($cost / $registered, 4) : 0;
            $cpa = $active > 0 ? round($cost / $active, 2) : 0;

            $hasCurrentCampaign = false;
            if (isset($cpiByReferral[$fallbackReferral->id])) {
                foreach ($cpiByReferral[$fallbackReferral->id] as $_cp) {
                    $_pEnd = $_cp['end_date'] !== null ? substr($_cp['end_date'], 0, 10) : null;
                    if (substr($_cp['start_date'], 0, 10) <= $today && ($_pEnd === null || $_pEnd >= $today)) {
                        $hasCurrentCampaign = true;
                        break;
                    }
                }
            }

            $_bAcc = [];
            for ($_m = 1; $_m <= 12; $_m++) {
                $_mReg = 0;
                $_mAgeData = ['has_birth_date' => 0];
                if (isset($sourceStatsByMonth[$_m])) {
                    foreach ($sourceStatsByMonth[$_m] as $_src => $_stats) {
                        if (isset($mappedSources[$_src])) continue;
                        $_mReg += $_stats['registered'];
                        $_mAgeData['has_birth_date'] += ($_stats['has_birth_date'] ?? 0);
                        foreach ($ageBreakpoints as $_bp) {
                            $_k = 'age_under_' . $_bp;
                            $_mAgeData[$_k] = ($_mAgeData[$_k] ?? 0) + ($_stats[$_k] ?? 0);
                        }
                    }
                }
                if ($_mReg <= 0) continue;
                $_ref = sprintf('%04d-%02d-01', $year, $_m);
                foreach ($this->computeMonthSegmentBreakdown($fallbackReferral->id, $_ref, array_merge(['registered' => $_mReg], $_mAgeData), $cpiByReferral) as $_seg) {
                    $_bk = ($_seg['age_min'] ?? '') . '_' . ($_seg['age_max'] ?? '');
                    if (!isset($_bAcc[$_bk])) {
                        $_bAcc[$_bk] = ['age_min' => $_seg['age_min'], 'age_max' => $_seg['age_max'], 'count' => 0, 'cost' => 0.0];
                    }
                    $_bAcc[$_bk]['count'] += $_seg['count'];
                    $_bAcc[$_bk]['cost']  += $_seg['cost'];
                }
            }
            $breakdown = count($_bAcc) > 1
                ? array_map(function ($b) { $b['cost'] = round($b['cost'], 2); return $b; }, array_values($_bAcc))
                : [];

            $tableRows[] = [
                'code' => $fallbackReferral->code,
                'label' => $fallbackReferral->title,
                'icon' => $fallbackReferral->icon,
                'registered' => $registered,
                'active' => $active,
                'active_rate' => $activeRate,
                'cost' => $cost,
                'cpi' => $avgCpi,
                'cpa' => $cpa,
                'sources' => $fallbackSources,
                'sort_order' => (int) $fallbackReferral->sort_order,
                'has_current_campaign' => $hasCurrentCampaign,
                'breakdown' => $breakdown,
            ];
        }
    }

    usort($tableRows, function ($a, $b) {
        return $a['sort_order'] <=> $b['sort_order'];
    });

    $totalRegistered = array_sum(array_column($tableRows, 'registered'));
    $totalActive = array_sum(array_column($tableRows, 'active'));
    $totalCost = round(array_sum(array_column($tableRows, 'cost')), 2);

    return response()->json([
        'success' => true,
        'year' => $year,
        'kpi' => [
            'registered' => $totalRegistered,
            'active' => $totalActive,
            'active_rate' => $totalRegistered > 0 ? round(($totalActive / $totalRegistered) * 100, 2) : 0,
            'cost' => $totalCost,
            'cpi' => $totalRegistered > 0 ? round($totalCost / $totalRegistered, 4) : 0,
            'cpa' => $totalActive > 0 ? round($totalCost / $totalActive, 2) : 0,
        ],
        'rows' => array_map(function ($item) {
            unset($item['sort_order']);
            return $item;
        }, $tableRows),
    ]);
}

public function activity(Request $request)
{
    $year = (int) $request->get('year', now()->year);

    if ($year < 2021 || $year > ((int) now()->year + 1)) {
        $year = (int) now()->year;
    }

    $referrals = $this->getActiveReferrals();

    $today = now()->format('Y-m-d');
    $activeCampaignReferralIds = DB::table('t_recruitment_referral_costs')
        ->where('start_date', '<=', $today)
        ->where(function ($q) use ($today) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
        })
        ->pluck('referral_id')
        ->unique()
        ->flip()
        ->toArray();

    // Bucket di attivita' basati direttamente su u.actions (sincronizzato da t_user_history
    // tramite il bottone "Aggiorna Attivita'" in PanelUsers): nessun arco temporale qui,
    // quindi non serve piu' interrogare t_user_history (733K righe senza indice su user_id).
    $rows = DB::table('t_user_info as u')
        ->select(
            'u.provenienza',
            DB::raw('COUNT(*) as total_registered'),
            DB::raw('SUM(CASE WHEN COALESCE(u.actions, 0) = 0 THEN 1 ELSE 0 END) as act_0'),
            DB::raw('SUM(CASE WHEN COALESCE(u.actions, 0) BETWEEN 1 AND 2 THEN 1 ELSE 0 END) as act_1_2'),
            DB::raw('SUM(CASE WHEN COALESCE(u.actions, 0) BETWEEN 3 AND 5 THEN 1 ELSE 0 END) as act_3_5'),
            DB::raw('SUM(CASE WHEN COALESCE(u.actions, 0) BETWEEN 6 AND 9 THEN 1 ELSE 0 END) as act_6_9'),
            DB::raw('SUM(CASE WHEN COALESCE(u.actions, 0) >= 10 THEN 1 ELSE 0 END) as act_10_plus')
        )
        ->whereYear('u.reg_date', $year)
        ->where('u.email', 'not like', '%.top')
        ->whereNotNull('u.provenienza')
        ->where('u.provenienza', '<>', '')
        ->groupBy('u.provenienza')
        ->get();

    $sourceStats = [];

    foreach ($rows as $row) {
        $sourceStats[$row->provenienza] = [
            'total_registered' => (int) $row->total_registered,
            'act_0' => (int) $row->act_0,
            'act_1_2' => (int) $row->act_1_2,
            'act_3_5' => (int) $row->act_3_5,
            'act_6_9' => (int) $row->act_6_9,
            'act_10_plus' => (int) $row->act_10_plus,
        ];
    }

    $mappedSources = [];
    $fallbackReferral = null;
    $result = [];

    foreach ($referrals as $referral) {
        if ($referral->group_type === 'fallback') {
            $fallbackReferral = $referral;
            continue;
        }

        $sources = $this->parseSourceCodes($referral->source_codes);

        $item = [
            'code' => $referral->code,
            'label' => $referral->title,
            'icon' => $referral->icon,
            'total_registered' => 0,
            'act_0' => 0,
            'act_1_2' => 0,
            'act_3_5' => 0,
            'act_6_9' => 0,
            'act_10_plus' => 0,
            'sources' => [],
            'sort_order' => (int) $referral->sort_order,
        ];

        foreach ($sources as $source) {
            $mappedSources[$source] = true;

            if (!isset($sourceStats[$source])) {
                continue;
            }

            $item['total_registered'] += $sourceStats[$source]['total_registered'];
            $item['act_0'] += $sourceStats[$source]['act_0'];
            $item['act_1_2'] += $sourceStats[$source]['act_1_2'];
            $item['act_3_5'] += $sourceStats[$source]['act_3_5'];
            $item['act_6_9'] += $sourceStats[$source]['act_6_9'];
            $item['act_10_plus'] += $sourceStats[$source]['act_10_plus'];
            $item['sources'][] = $source;
        }

        if ($item['total_registered'] <= 0) {
            continue;
        }

        $item['perc_0'] = round(($item['act_0'] / $item['total_registered']) * 100, 2);
        $item['perc_1_2'] = round(($item['act_1_2'] / $item['total_registered']) * 100, 2);
        $item['perc_3_5'] = round(($item['act_3_5'] / $item['total_registered']) * 100, 2);
        $item['perc_6_9'] = round(($item['act_6_9'] / $item['total_registered']) * 100, 2);
        $item['perc_10_plus'] = round(($item['act_10_plus'] / $item['total_registered']) * 100, 2);
        $item['has_current_campaign'] = isset($activeCampaignReferralIds[$referral->id]);

        $result[] = $item;
    }

    if ($fallbackReferral) {
        $item = [
            'code' => $fallbackReferral->code,
            'label' => $fallbackReferral->title,
            'icon' => $fallbackReferral->icon,
            'total_registered' => 0,
            'act_0' => 0,
            'act_1_2' => 0,
            'act_3_5' => 0,
            'act_6_9' => 0,
            'act_10_plus' => 0,
            'sources' => [],
            'sort_order' => (int) $fallbackReferral->sort_order,
        ];

        foreach ($sourceStats as $source => $stats) {
            if (isset($mappedSources[$source])) {
                continue;
            }

            $item['total_registered'] += $stats['total_registered'];
            $item['act_0'] += $stats['act_0'];
            $item['act_1_2'] += $stats['act_1_2'];
            $item['act_3_5'] += $stats['act_3_5'];
            $item['act_6_9'] += $stats['act_6_9'];
            $item['act_10_plus'] += $stats['act_10_plus'];
            $item['sources'][] = $source;
        }

        if ($item['total_registered'] > 0) {
            $item['perc_0'] = round(($item['act_0'] / $item['total_registered']) * 100, 2);
            $item['perc_1_2'] = round(($item['act_1_2'] / $item['total_registered']) * 100, 2);
            $item['perc_3_5'] = round(($item['act_3_5'] / $item['total_registered']) * 100, 2);
            $item['perc_6_9'] = round(($item['act_6_9'] / $item['total_registered']) * 100, 2);
            $item['perc_10_plus'] = round(($item['act_10_plus'] / $item['total_registered']) * 100, 2);
            $item['has_current_campaign'] = isset($activeCampaignReferralIds[$fallbackReferral->id]);

            $result[] = $item;
        }
    }

    usort($result, function ($a, $b) {
        return $a['sort_order'] <=> $b['sort_order'];
    });

    return response()->json([
        'success' => true,
        'year' => $year,
        'rows' => array_map(function ($item) {
            unset($item['sort_order']);
            return $item;
        }, $result),
    ]);
}

public function stats(Request $request)
{
    $year = (int) $request->get('year', now()->year);

    if ($year < 2021 || $year > ((int) now()->year + 1)) {
        $year = (int) now()->year;
    }

    $referrals = $this->getActiveReferrals();
    $currentYear = (int) now()->year;

    $todayStats = now()->format('Y-m-d');
    $activeCampaignReferralIdsStats = DB::table('t_recruitment_referral_costs')
        ->where('start_date', '<=', $todayStats)
        ->where(function ($q) use ($todayStats) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', $todayStats);
        })
        ->pluck('referral_id')
        ->unique()
        ->flip()
        ->toArray();

    $rows = DB::table('t_user_info')
        ->select(
            'provenienza',
            DB::raw('COUNT(*) as total_registered'),

            DB::raw('SUM(CASE WHEN gender = 1 THEN 1 ELSE 0 END) as gender_male'),
            DB::raw('SUM(CASE WHEN gender = 2 THEN 1 ELSE 0 END) as gender_female'),
            DB::raw('SUM(CASE WHEN gender IS NULL OR gender NOT IN (1,2) THEN 1 ELSE 0 END) as gender_unknown'),

            DB::raw("SUM(CASE
                WHEN birth_date IS NOT NULL
                     AND birth_date <> '0000-00-00'
                     AND TIMESTAMPDIFF(YEAR, birth_date, '{$year}-12-31') < 18
                THEN 1 ELSE 0 END) as age_under_18"),

            DB::raw("SUM(CASE
                WHEN birth_date IS NOT NULL
                     AND birth_date <> '0000-00-00'
                     AND TIMESTAMPDIFF(YEAR, birth_date, '{$year}-12-31') BETWEEN 18 AND 24
                THEN 1 ELSE 0 END) as age_18_24"),

            DB::raw("SUM(CASE
                WHEN birth_date IS NOT NULL
                     AND birth_date <> '0000-00-00'
                     AND TIMESTAMPDIFF(YEAR, birth_date, '{$year}-12-31') BETWEEN 25 AND 34
                THEN 1 ELSE 0 END) as age_25_34"),

            DB::raw("SUM(CASE
                WHEN birth_date IS NOT NULL
                     AND birth_date <> '0000-00-00'
                     AND TIMESTAMPDIFF(YEAR, birth_date, '{$year}-12-31') BETWEEN 35 AND 44
                THEN 1 ELSE 0 END) as age_35_44"),

            DB::raw("SUM(CASE
                WHEN birth_date IS NOT NULL
                     AND birth_date <> '0000-00-00'
                     AND TIMESTAMPDIFF(YEAR, birth_date, '{$year}-12-31') BETWEEN 45 AND 54
                THEN 1 ELSE 0 END) as age_45_54"),

            DB::raw("SUM(CASE
                WHEN birth_date IS NOT NULL
                     AND birth_date <> '0000-00-00'
                     AND TIMESTAMPDIFF(YEAR, birth_date, '{$year}-12-31') BETWEEN 55 AND 64
                THEN 1 ELSE 0 END) as age_55_64"),

            DB::raw("SUM(CASE
                WHEN birth_date IS NOT NULL
                     AND birth_date <> '0000-00-00'
                     AND TIMESTAMPDIFF(YEAR, birth_date, '{$year}-12-31') >= 65
                THEN 1 ELSE 0 END) as age_65_plus"),

            DB::raw("SUM(CASE
                WHEN birth_date IS NULL
                     OR birth_date = '0000-00-00'
                THEN 1 ELSE 0 END) as age_unknown"),

        DB::raw("SUM(CASE WHEN area = 1 THEN 1 ELSE 0 END) as area_nord_ovest"),
        DB::raw("SUM(CASE WHEN area = 2 THEN 1 ELSE 0 END) as area_nord_est"),
        DB::raw("SUM(CASE WHEN area = 3 THEN 1 ELSE 0 END) as area_centro"),
        DB::raw("SUM(CASE WHEN area = 4 THEN 1 ELSE 0 END) as area_sud"),
        DB::raw("SUM(CASE
            WHEN area IS NULL
                OR area NOT IN (1,2,3,4)
            THEN 1 ELSE 0 END) as area_unknown")
        )
        ->whereYear('reg_date', $year)
        ->where('email', 'not like', '%.top')
        ->whereNotNull('provenienza')
        ->where('provenienza', '<>', '')
        ->groupBy('provenienza')
        ->get();

    $sourceStats = [];

    foreach ($rows as $row) {
        $sourceStats[$row->provenienza] = [
            'total_registered' => (int) $row->total_registered,

            'gender_male' => (int) $row->gender_male,
            'gender_female' => (int) $row->gender_female,
            'gender_unknown' => (int) $row->gender_unknown,

            'age_under_18' => (int) $row->age_under_18,
            'age_18_24' => (int) $row->age_18_24,
            'age_25_34' => (int) $row->age_25_34,
            'age_35_44' => (int) $row->age_35_44,
            'age_45_54' => (int) $row->age_45_54,
            'age_55_64' => (int) $row->age_55_64,
            'age_65_plus' => (int) $row->age_65_plus,
            'age_unknown' => (int) $row->age_unknown,

            'area_nord_ovest' => (int) $row->area_nord_ovest,
            'area_nord_est' => (int) $row->area_nord_est,
            'area_centro' => (int) $row->area_centro,
            'area_sud' => (int) $row->area_sud,
            'area_unknown' => (int) $row->area_unknown,
        ];
    }

    $mappedSources = [];
    $fallbackReferral = null;
    $result = [];

    foreach ($referrals as $referral) {
        if ($referral->group_type === 'fallback') {
            $fallbackReferral = $referral;
            continue;
        }

        $sources = $this->parseSourceCodes($referral->source_codes);

        $item = [
            'code' => $referral->code,
            'label' => $referral->title,
            'icon' => $referral->icon,
            'sources' => [],
            'total_registered' => 0,

            'gender_male' => 0,
            'gender_female' => 0,
            'gender_unknown' => 0,

            'age_under_18' => 0,
            'age_18_24' => 0,
            'age_25_34' => 0,
            'age_35_44' => 0,
            'age_45_54' => 0,
            'age_55_64' => 0,
            'age_65_plus' => 0,
            'age_unknown' => 0,

            'area_nord_ovest' => 0,
            'area_nord_est' => 0,
            'area_centro' => 0,
            'area_sud' => 0,
            'area_unknown' => 0,

            'sort_order' => (int) $referral->sort_order,
        ];

        foreach ($sources as $source) {
            $mappedSources[$source] = true;

            if (!isset($sourceStats[$source])) {
                continue;
            }

            $stats = $sourceStats[$source];

            $item['sources'][] = $source;
            $item['total_registered'] += $stats['total_registered'];

            $item['gender_male'] += $stats['gender_male'];
            $item['gender_female'] += $stats['gender_female'];
            $item['gender_unknown'] += $stats['gender_unknown'];

            $item['age_under_18'] += $stats['age_under_18'];
            $item['age_18_24'] += $stats['age_18_24'];
            $item['age_25_34'] += $stats['age_25_34'];
            $item['age_35_44'] += $stats['age_35_44'];
            $item['age_45_54'] += $stats['age_45_54'];
            $item['age_55_64'] += $stats['age_55_64'];
            $item['age_65_plus'] += $stats['age_65_plus'];
            $item['age_unknown'] += $stats['age_unknown'];

            $item['area_nord_ovest'] += $stats['area_nord_ovest'];
            $item['area_nord_est'] += $stats['area_nord_est'];
            $item['area_centro'] += $stats['area_centro'];
            $item['area_sud'] += $stats['area_sud'];
            $item['area_unknown'] += $stats['area_unknown'];
        }

        if ($item['total_registered'] > 0) {
            $item['has_current_campaign'] = isset($activeCampaignReferralIdsStats[$referral->id]);
            $result[] = $item;
        }
    }

    if ($fallbackReferral) {
        $item = [
            'code' => $fallbackReferral->code,
            'label' => $fallbackReferral->title,
            'icon' => $fallbackReferral->icon,
            'sources' => [],
            'total_registered' => 0,

            'gender_male' => 0,
            'gender_female' => 0,
            'gender_unknown' => 0,

            'age_under_18' => 0,
            'age_18_24' => 0,
            'age_25_34' => 0,
            'age_35_44' => 0,
            'age_45_54' => 0,
            'age_55_64' => 0,
            'age_65_plus' => 0,
            'age_unknown' => 0,

            'area_nord_ovest' => 0,
            'area_nord_est' => 0,
            'area_centro' => 0,
            'area_sud' => 0,
            'area_unknown' => 0,

            'sort_order' => (int) $fallbackReferral->sort_order,
        ];

        foreach ($sourceStats as $source => $stats) {
            if (isset($mappedSources[$source])) {
                continue;
            }

            $item['sources'][] = $source;
            $item['total_registered'] += $stats['total_registered'];

            $item['gender_male'] += $stats['gender_male'];
            $item['gender_female'] += $stats['gender_female'];
            $item['gender_unknown'] += $stats['gender_unknown'];

            $item['age_under_18'] += $stats['age_under_18'];
            $item['age_18_24'] += $stats['age_18_24'];
            $item['age_25_34'] += $stats['age_25_34'];
            $item['age_35_44'] += $stats['age_35_44'];
            $item['age_45_54'] += $stats['age_45_54'];
            $item['age_55_64'] += $stats['age_55_64'];
            $item['age_65_plus'] += $stats['age_65_plus'];
            $item['age_unknown'] += $stats['age_unknown'];

            $item['area_nord_ovest'] += $stats['area_nord_ovest'];
            $item['area_nord_est'] += $stats['area_nord_est'];
            $item['area_centro'] += $stats['area_centro'];
            $item['area_sud'] += $stats['area_sud'];
            $item['area_unknown'] += $stats['area_unknown'];
        }

        if ($item['total_registered'] > 0) {
            $item['has_current_campaign'] = isset($activeCampaignReferralIdsStats[$fallbackReferral->id]);
            $result[] = $item;
        }
    }

    usort($result, function ($a, $b) {
        return $a['sort_order'] <=> $b['sort_order'];
    });

    return response()->json([
        'success' => true,
        'year' => $year,
        'rows' => array_map(function ($item) {
            unset($item['sort_order']);
            return $item;
        }, $result),
    ]);
}


 private function getActiveReferrals()
    {
        return DB::table('t_recruitment_referrals')
            ->select(
                'id',
                'legacy_id',
                'code',
                'title',
                'icon',
                'source_codes',
                'group_type',
                'sort_order'
            )
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();
    }

    private function parseSourceCodes($sourceCodes)
    {
        if (empty($sourceCodes)) {
            return [];
        }

        $parts = explode(',', $sourceCodes);
        $clean = [];

        foreach ($parts as $part) {
            $value = trim($part);

            if ($value !== '') {
                $clean[] = $value;
            }
        }

        return array_values(array_unique($clean));
    }

    private function resolveReferralCpiForDate($referralId, $referenceDate, array $cpiByReferral)
{
    if (!isset($cpiByReferral[$referralId])) {
        return 0;
    }

    // Ordine per start_date DESC: in caso di periodi sovrapposti vince il più recente
    $periods = $cpiByReferral[$referralId];
    usort($periods, fn($a, $b) => strcmp($b['start_date'], $a['start_date']));

    foreach ($periods as $period) {
        // Normalizza a YYYY-MM-DD: il DB può restituire 'YYYY-MM-DD HH:MM:SS'
        // e il confronto PHP su stringhe di lunghezza diversa dà risultati errati
        $startDate = substr($period['start_date'], 0, 10);
        $endDate = $period['end_date'] !== null ? substr($period['end_date'], 0, 10) : null;

        if ($referenceDate < $startDate) {
            continue;
        }

        if ($endDate !== null && $referenceDate > $endDate) {
            continue;
        }

        return (float) $period['cpi'];
    }

    return 0;
}

private function computeMonthSegmentBreakdown(
    $referralId,
    string $referenceDate,
    array $monthSourceData,
    array $cpiByReferral
): array {
    if (!isset($cpiByReferral[$referralId])) {
        return [];
    }

    $validPeriods = array_values(array_filter($cpiByReferral[$referralId], function ($p) use ($referenceDate) {
        $start = substr($p['start_date'], 0, 10);
        $end   = $p['end_date'] !== null ? substr($p['end_date'], 0, 10) : null;
        return $referenceDate >= $start && ($end === null || $referenceDate <= $end);
    }));

    if (empty($validPeriods)) {
        return [];
    }

    $totalRegistered = (int) ($monthSourceData['registered'] ?? 0);
    $hasBirthDate    = (int) ($monthSourceData['has_birth_date'] ?? 0);
    $result = [];

    foreach ($validPeriods as $segment) {
        $ageMin = $segment['age_min'];
        $ageMax = $segment['age_max'];

        if ($ageMin === null && $ageMax === null) {
            $count = $totalRegistered;
        } else {
            $upperCount = $ageMax !== null
                ? (int) ($monthSourceData['age_under_' . $ageMax] ?? 0)
                : $hasBirthDate;
            $lowerCount = $ageMin !== null
                ? (int) ($monthSourceData['age_under_' . ($ageMin - 1)] ?? 0)
                : 0;
            $count = max(0, $upperCount - $lowerCount);
        }

        $result[] = [
            'age_min' => $ageMin,
            'age_max' => $ageMax,
            'count'   => $count,
            'cost'    => $count * $segment['cpi'],
        ];
    }

    return $result;
}

private function computeSegmentedCost(
    $referralId,
    string $referenceDate,
    array $monthSourceData,
    array $cpiByReferral,
    array $ageBreakpoints
): float {
    if (!isset($cpiByReferral[$referralId])) {
        return 0.0;
    }

    $validPeriods = array_values(array_filter($cpiByReferral[$referralId], function ($p) use ($referenceDate) {
        $start = substr($p['start_date'], 0, 10);
        $end   = $p['end_date'] !== null ? substr($p['end_date'], 0, 10) : null;
        return $referenceDate >= $start && ($end === null || $referenceDate <= $end);
    }));

    if (empty($validPeriods)) {
        return 0.0;
    }

    $totalRegistered = (int) ($monthSourceData['registered'] ?? 0);
    $hasBirthDate    = (int) ($monthSourceData['has_birth_date'] ?? 0);
    $cost = 0.0;

    foreach ($validPeriods as $segment) {
        $ageMin = $segment['age_min'];
        $ageMax = $segment['age_max'];

        if ($ageMin === null && $ageMax === null) {
            // No age filter: applies to all registered users (including unknown birth_date)
            $cost += $totalRegistered * $segment['cpi'];
            continue;
        }

        // Upper bound: age_under_{age_max}, or all users with known birth_date if no upper limit
        $upperCount = $ageMax !== null
            ? (int) ($monthSourceData['age_under_' . $ageMax] ?? 0)
            : $hasBirthDate;

        // Lower bound: count of users strictly below age_min = age_under_{age_min - 1}
        $lowerCount = $ageMin !== null
            ? (int) ($monthSourceData['age_under_' . ($ageMin - 1)] ?? 0)
            : 0;

        $cost += max(0, $upperCount - $lowerCount) * $segment['cpi'];
    }

    return $cost;
}

public function latestRegistrations()
{
    $referrals = $this->getActiveReferrals();

    // Pre-costruisce mappa source_code → referral per evitare O(n×m) nel loop
    $sourceMap = [];
    $fallbackReferral = null;
    foreach ($referrals as $referral) {
        if ($referral->group_type === 'fallback') {
            $fallbackReferral = $referral;
            continue;
        }
        foreach ($this->parseSourceCodes($referral->source_codes) as $code) {
            $sourceMap[$code] = $referral;
        }
    }

    $rows = DB::table('t_user_info')
        ->select('reg_date', 'email', 'provenienza')
        ->where('email', 'not like', '%.top')
        ->whereNotNull('reg_date')
        ->whereNotNull('email')
        ->where('email', '<>', '')
        ->orderByDesc('reg_date')
        ->limit(100)
        ->get();

    $mappedRows = [];

    foreach ($rows as $row) {
        $source = $row->provenienza ?: '';
        $referral = $sourceMap[$source] ?? $fallbackReferral;

        $mappedRows[] = [
            'reg_date' => $row->reg_date
                ? Carbon::parse($row->reg_date)->format('d/m/Y H:i')
                : '-',
            'email' => $row->email ?: '-',
            'source' => $source ?: '-',
            'referral_code' => $referral->code ?? '-',
            'referral_label' => $referral->title ?? ($source ?: '-'),
            'referral_icon' => $referral->icon ?? null,
        ];
    }

    return response()->json([
        'success' => true,
        'rows' => $mappedRows,
    ]);
}

private function mapSourceToReferral($source, $referrals)
{
    $fallbackReferral = null;

    foreach ($referrals as $referral) {
        if ($referral->group_type === 'fallback') {
            $fallbackReferral = $referral;
            continue;
        }

        $sources = $this->parseSourceCodes($referral->source_codes);

        if (in_array($source, $sources, true)) {
            return [
                'code' => $referral->code,
                'label' => $referral->title,
                'icon' => $referral->icon,
            ];
        }
    }

    if ($fallbackReferral) {
        return [
            'code' => $fallbackReferral->code,
            'label' => $fallbackReferral->title,
            'icon' => $fallbackReferral->icon,
        ];
    }

    return [
        'code' => null,
        'label' => $source ?: '-',
        'icon' => null,
    ];
}

public function summaryYear(Request $request)
{
    $year = (int) $request->get('year', now()->year);

    if ($year < 2021 || $year > ((int) now()->year + 1)) {
        $year = (int) now()->year;
    }

    $budget = (int) config('recruitment.annual_budget.' . $year, config('recruitment.annual_budget.default', 15000));

    $referrals = $this->getActiveReferrals();

    $cpiRows = DB::table('t_recruitment_referral_costs')
        ->select('referral_id', 'start_date', 'end_date', 'cpi', 'age_min', 'age_max')
        ->where('is_active', 1)
        ->whereDate('start_date', '<=', $year . '-12-31')
        ->where(function ($query) use ($year) {
            $query->whereNull('end_date')
                ->orWhereDate('end_date', '>=', $year . '-01-01');
        })
        ->orderBy('start_date')
        ->get();

    $cpiByReferral = [];

    foreach ($cpiRows as $row) {
        if (!isset($cpiByReferral[$row->referral_id])) {
            $cpiByReferral[$row->referral_id] = [];
        }

        $cpiByReferral[$row->referral_id][] = [
            'start_date' => $row->start_date,
            'end_date'   => $row->end_date,
            'cpi'        => (float) $row->cpi,
            'age_min'    => $row->age_min !== null ? (int) $row->age_min : null,
            'age_max'    => $row->age_max !== null ? (int) $row->age_max : null,
        ];
    }

    $ageBreakpoints = [];
    foreach ($cpiByReferral as $_periods) {
        foreach ($_periods as $_period) {
            if ($_period['age_max'] !== null) {
                $bp = (int) $_period['age_max'];
                if (!in_array($bp, $ageBreakpoints, true)) {
                    $ageBreakpoints[] = $bp;
                }
            }
            if ($_period['age_min'] !== null) {
                $bp = (int) $_period['age_min'] - 1;
                if ($bp >= 0 && !in_array($bp, $ageBreakpoints, true)) {
                    $ageBreakpoints[] = $bp;
                }
            }
        }
    }
    sort($ageBreakpoints);

    $selectClauses = [
        DB::raw('MONTH(reg_date) as month_num'),
        'provenienza',
        DB::raw('COUNT(*) as registered'),
        DB::raw('SUM(CASE WHEN COALESCE(actions, 0) > 0 THEN 1 ELSE 0 END) as active'),
        DB::raw("SUM(CASE WHEN birth_date IS NOT NULL AND birth_date <> '0000-00-00' THEN 1 ELSE 0 END) as has_birth_date"),
    ];

    foreach ($ageBreakpoints as $bp) {
        $selectClauses[] = DB::raw(
            "SUM(CASE WHEN birth_date IS NOT NULL AND birth_date <> '0000-00-00'"
            . " AND TIMESTAMPDIFF(YEAR, birth_date, reg_date) <= {$bp}"
            . " THEN 1 ELSE 0 END) as age_under_{$bp}"
        );
    }

    $rows = DB::table('t_user_info')
        ->select($selectClauses)
        ->whereYear('reg_date', $year)
        ->where('email', 'not like', '%.top')
        ->whereNotNull('provenienza')
        ->where('provenienza', '<>', '')
        ->groupBy(DB::raw('MONTH(reg_date)'), 'provenienza')
        ->get();

    $sourceStatsByMonth = [];

    foreach ($rows as $row) {
        $monthNum = (int) $row->month_num;
        $source = $row->provenienza;

        if (!isset($sourceStatsByMonth[$monthNum])) {
            $sourceStatsByMonth[$monthNum] = [];
        }

        $entry = [
            'registered'     => (int) $row->registered,
            'active'         => (int) $row->active,
            'has_birth_date' => isset($row->has_birth_date) ? (int) $row->has_birth_date : 0,
        ];

        foreach ($ageBreakpoints as $bp) {
            $key = 'age_under_' . $bp;
            $entry[$key] = isset($row->$key) ? (int) $row->$key : 0;
        }

        $sourceStatsByMonth[$monthNum][$source] = $entry;
    }

    $mappedSources = [];
    $fallbackReferral = null;
    $summaryRows = [];

    foreach ($referrals as $referral) {
        if ($referral->group_type === 'fallback') {
            $fallbackReferral = $referral;
            continue;
        }

        $sources = $this->parseSourceCodes($referral->source_codes);

        $registered = 0;
        $active = 0;
        $cost = 0;
        $matchedSources = [];

        foreach ($sources as $source) {
            $mappedSources[$source] = true;
        }

        for ($month = 1; $month <= 12; $month++) {
            $monthRegistered = 0;
            $monthActive = 0;
            $monthAgeData = [];

            foreach ($sources as $source) {
                if (!isset($sourceStatsByMonth[$month][$source])) {
                    continue;
                }

                $monthRegistered += $sourceStatsByMonth[$month][$source]['registered'];
                $monthActive += $sourceStatsByMonth[$month][$source]['active'];

                $monthAgeData['has_birth_date'] = ($monthAgeData['has_birth_date'] ?? 0) + ($sourceStatsByMonth[$month][$source]['has_birth_date'] ?? 0);
                foreach ($ageBreakpoints as $bp) {
                    $key = 'age_under_' . $bp;
                    $monthAgeData[$key] = ($monthAgeData[$key] ?? 0) + ($sourceStatsByMonth[$month][$source][$key] ?? 0);
                }

                if (!in_array($source, $matchedSources, true)) {
                    $matchedSources[] = $source;
                }
            }

            if ($monthRegistered <= 0) {
                continue;
            }

            $registered += $monthRegistered;
            $active += $monthActive;

            $referenceDate = sprintf('%04d-%02d-01', $year, $month);
            $monthSourceData = array_merge(['registered' => $monthRegistered], $monthAgeData);
            $cost += $this->computeSegmentedCost($referral->id, $referenceDate, $monthSourceData, $cpiByReferral, $ageBreakpoints);
        }

        if ($registered <= 0) {
            continue;
        }

        $cost = round($cost, 2);
        $activeRate = $registered > 0 ? round(($active / $registered) * 100, 2) : 0;
        $avgCpi = $registered > 0 ? round($cost / $registered, 4) : 0;
        $cpa = $active > 0 ? round($cost / $active, 2) : 0;

        $summaryRows[] = [
            'code' => $referral->code,
            'label' => $referral->title,
            'icon' => $referral->icon,
            'registered' => $registered,
            'active' => $active,
            'active_rate' => $activeRate,
            'cost' => $cost,
            'cpi' => $avgCpi,
            'cpa' => $cpa,
            'sources' => $matchedSources,
            'sort_order' => (int) $referral->sort_order,
        ];
    }

    if ($fallbackReferral) {
        $registered = 0;
        $active = 0;
        $cost = 0;
        $fallbackSources = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthRegistered = 0;
            $monthActive = 0;
            $monthAgeData = [];

            if (isset($sourceStatsByMonth[$month])) {
                foreach ($sourceStatsByMonth[$month] as $source => $stats) {
                    if (isset($mappedSources[$source])) {
                        continue;
                    }

                    $monthRegistered += $stats['registered'];
                    $monthActive += $stats['active'];

                    $monthAgeData['has_birth_date'] = ($monthAgeData['has_birth_date'] ?? 0) + ($stats['has_birth_date'] ?? 0);
                    foreach ($ageBreakpoints as $bp) {
                        $key = 'age_under_' . $bp;
                        $monthAgeData[$key] = ($monthAgeData[$key] ?? 0) + ($stats[$key] ?? 0);
                    }

                    if (!in_array($source, $fallbackSources, true)) {
                        $fallbackSources[] = $source;
                    }
                }
            }

            if ($monthRegistered <= 0) {
                continue;
            }

            $registered += $monthRegistered;
            $active += $monthActive;

            $referenceDate = sprintf('%04d-%02d-01', $year, $month);
            $monthSourceData = array_merge(['registered' => $monthRegistered], $monthAgeData);
            $cost += $this->computeSegmentedCost($fallbackReferral->id, $referenceDate, $monthSourceData, $cpiByReferral, $ageBreakpoints);
        }

        if ($registered > 0) {
            $cost = round($cost, 2);
            $activeRate = $registered > 0 ? round(($active / $registered) * 100, 2) : 0;
            $avgCpi = $registered > 0 ? round($cost / $registered, 4) : 0;
            $cpa = $active > 0 ? round($cost / $active, 2) : 0;

            $summaryRows[] = [
                'code' => $fallbackReferral->code,
                'label' => $fallbackReferral->title,
                'icon' => $fallbackReferral->icon,
                'registered' => $registered,
                'active' => $active,
                'active_rate' => $activeRate,
                'cost' => $cost,
                'cpi' => $avgCpi,
                'cpa' => $cpa,
                'sources' => $fallbackSources,
                'sort_order' => (int) $fallbackReferral->sort_order,
            ];
        }
    }

    usort($summaryRows, function ($a, $b) {
        return $a['sort_order'] <=> $b['sort_order'];
    });

    $totalRegistered = array_sum(array_column($summaryRows, 'registered'));
    $totalActive = array_sum(array_column($summaryRows, 'active'));
    $totalCost = round(array_sum(array_column($summaryRows, 'cost')), 2);
    $rest = round($budget - $totalCost, 2);

    $topByRegistered = null;
    $topByActive = null;

    if (!empty($summaryRows)) {
        $rowsByRegistered = $summaryRows;
        usort($rowsByRegistered, function ($a, $b) {
            return $b['registered'] <=> $a['registered'];
        });
        $topByRegistered = $rowsByRegistered[0];

        $rowsByActive = $summaryRows;
        usort($rowsByActive, function ($a, $b) {
            return $b['active'] <=> $a['active'];
        });
        $topByActive = $rowsByActive[0];
    }

    $activeReferralCount = count($summaryRows);
    $budgetUsedPercent = $budget > 0 ? round(($totalCost / $budget) * 100, 2) : 0;

    return response()->json([
        'success' => true,
        'year' => $year,
        'kpi' => [
            'budget' => $budget,
            'spent' => $totalCost,
            'rest' => $rest,
            'registered' => $totalRegistered,
            'cpi' => $totalRegistered > 0 ? round($totalCost / $totalRegistered, 2) : 0,
            'active' => $totalActive,
            'active_rate' => $totalRegistered > 0 ? round(($totalActive / $totalRegistered) * 100, 2) : 0,
            'cpa' => $totalActive > 0 ? round($totalCost / $totalActive, 2) : 0,
            'budget_used_percent' => $budgetUsedPercent,
            'active_referral_count' => $activeReferralCount,
        ],
        'highlights' => [
            'top_registered' => $topByRegistered ? [
                'label' => $topByRegistered['label'],
                'icon' => $topByRegistered['icon'],
                'value' => $topByRegistered['registered'],
            ] : null,
            'top_active' => $topByActive ? [
                'label' => $topByActive['label'],
                'icon' => $topByActive['icon'],
                'value' => $topByActive['active'],
            ] : null,
        ],
    ]);
}

public function storeCampaign(Request $request)
{
    $validated = $request->validate([
        'referral_mode' => 'required|in:existing,new',

        'existing_referral_id' => 'nullable|required_if:referral_mode,existing|integer',
        'new_referral_code' => 'nullable|required_if:referral_mode,new|string|max:50',
        'new_referral_title' => 'nullable|required_if:referral_mode,new|string|max:255',
        'new_referral_icon' => 'nullable|string|max:150',

        'start_date'          => 'required|date',
        'end_date'            => 'nullable|date|after_or_equal:start_date',
        'segments'            => 'required|array|min:1|max:10',
        'segments.*.cpi'      => 'required|numeric|min:0',
        'segments.*.age_min'  => 'nullable|integer|min:1|max:119',
        'segments.*.age_max'  => 'nullable|integer|min:1|max:120',
        'is_active'           => 'nullable|boolean',
    ]);

    DB::beginTransaction();

    try {
        $referralId = null;

        if ($validated['referral_mode'] === 'existing') {
            $referralId = (int) $validated['existing_referral_id'];

            $referral = DB::table('t_recruitment_referrals')
                ->select('id', 'group_type', 'is_active')
                ->where('id', $referralId)
                ->first();

            if (!$referral || (int) $referral->is_active !== 1) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Referral non valido o non attivo.'
                ], 422);
            }

            if ($referral->group_type === 'fallback') {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Non è possibile creare una campagna su un referral di tipo fallback.'
                ], 422);
            }
        } else {
            $newCode = trim($validated['new_referral_code']);
            $newTitle = trim($validated['new_referral_title']);
            $newIcon = !empty($validated['new_referral_icon'])
                ? trim($validated['new_referral_icon'])
                : null;

            $maxSortOrder = DB::table('t_recruitment_referrals')->max('sort_order');
            $nextSortOrder = ((int) $maxSortOrder) + 1;

            try {
                $referralId = DB::table('t_recruitment_referrals')->insertGetId([
                    'legacy_id' => null,
                    'code' => $newCode,
                    'title' => $newTitle,
                    'icon' => $newIcon,
                    'source_codes' => $newCode,
                    'group_type' => 'standard',
                    'sort_order' => $nextSortOrder,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                // Duplicate entry (codice già esistente, anche in caso di race condition)
                if ($e->errorInfo[1] === 1062) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Il codice referral esiste già.'
                    ], 422);
                }
                throw $e;
            }
        }

        $startDate = $validated['start_date'];
        $endDate = !empty($validated['end_date']) ? $validated['end_date'] : null;
        $isActive = isset($validated['is_active']) ? (int) $validated['is_active'] : 1;

        foreach ($validated['segments'] as $segment) {
            $segmentCpi    = (float) $segment['cpi'];
            $segmentAgeMin = isset($segment['age_min']) && $segment['age_min'] !== '' && $segment['age_min'] !== null
                ? (int) $segment['age_min']
                : null;
            $segmentAgeMax = isset($segment['age_max']) && $segment['age_max'] !== '' && $segment['age_max'] !== null
                ? (int) $segment['age_max']
                : null;

            if ($segmentAgeMin !== null && $segmentAgeMax !== null && $segmentAgeMin >= $segmentAgeMax) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => "Eta' min deve essere inferiore a eta' max."], 422);
            }

            $overlapQuery = DB::table('t_recruitment_referral_costs')
                ->where('referral_id', $referralId)
                ->where('is_active', 1)
                ->where(function ($q) use ($segmentAgeMin, $segmentAgeMax) {
                    // Ranges overlap if: existMin <= newMax AND newMin <= existMax
                    if ($segmentAgeMax !== null) {
                        $q->where(function ($sub) use ($segmentAgeMax) {
                            $sub->whereNull('age_min')->orWhere('age_min', '<=', $segmentAgeMax);
                        });
                    }
                    if ($segmentAgeMin !== null) {
                        $q->where(function ($sub) use ($segmentAgeMin) {
                            $sub->whereNull('age_max')->orWhere('age_max', '>=', $segmentAgeMin);
                        });
                    }
                })
                ->where(function ($query) use ($startDate, $endDate) {
                    if ($endDate) {
                        $query->whereDate('start_date', '<=', $endDate)
                            ->where(function ($sub) use ($startDate) {
                                $sub->whereNull('end_date')
                                    ->orWhereDate('end_date', '>=', $startDate);
                            });
                    } else {
                        $query->where(function ($sub) use ($startDate) {
                            $sub->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $startDate);
                        });
                    }
                });

            if ($overlapQuery->exists()) {
                DB::rollBack();
                if ($segmentAgeMin !== null && $segmentAgeMax !== null) {
                    $label = " (eta' {$segmentAgeMin}-{$segmentAgeMax})";
                } elseif ($segmentAgeMin !== null) {
                    $label = " (eta' >={$segmentAgeMin})";
                } elseif ($segmentAgeMax !== null) {
                    $label = " (eta' <={$segmentAgeMax})";
                } else {
                    $label = ' (fascia default)';
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Esiste gia\' una campagna attiva o sovrapposta per questo referral nel periodo selezionato' . $label . '.'
                ], 422);
            }

            DB::table('t_recruitment_referral_costs')->insert([
                'referral_id' => $referralId,
                'start_date'  => $startDate,
                'end_date'    => $endDate,
                'cpi'         => $segmentCpi,
                'age_min'     => $segmentAgeMin,
                'age_max'     => $segmentAgeMax,
                'is_active'   => $isActive,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        DB::commit();

        $successMessage = $isActive
            ? 'Campagna inserita correttamente.'
            : 'Campagna inserita come inattiva: non verrà mostrata nei report finché non viene attivata.';

        return response()->json([
            'success' => true,
            'message' => $successMessage
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();

        Log::error('[storeCampaign] Errore: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Errore durante il salvataggio della campagna.'
        ], 500);
    }
}

public function campaignsList(Request $request)
{
    $referralId = (int) $request->get('referral_id', 0);

    $query = DB::table('t_recruitment_referral_costs as c')
        ->join('t_recruitment_referrals as r', 'r.id', '=', 'c.referral_id')
        ->where('r.is_active', 1)
        ->where('r.group_type', '<>', 'fallback')
        ->orderBy('r.sort_order')
        ->orderByRaw("SUBSTR(c.start_date, 1, 10) DESC")
        ->orderByRaw('c.age_min IS NULL DESC')
        ->orderBy('c.age_min')
        ->orderByRaw('c.age_max IS NULL ASC')
        ->orderBy('c.age_max')
        ->select([
            'r.id as referral_id',
            'r.code as referral_code',
            'r.title as referral_title',
            'r.icon as referral_icon',
            'c.id as cost_id',
            'c.start_date',
            'c.end_date',
            'c.cpi',
            'c.age_min',
            'c.age_max',
            'c.is_active',
        ]);

    if ($referralId > 0) {
        $query->where('c.referral_id', $referralId);
    }

    $rows = $query->get();

    $groups = [];

    foreach ($rows as $row) {
        $rid = $row->referral_id;

        if (!isset($groups[$rid])) {
            $groups[$rid] = [
                'referral_id'    => $rid,
                'referral_code'  => $row->referral_code,
                'referral_title' => $row->referral_title,
                'referral_icon'  => $row->referral_icon,
                'periods'        => [],
            ];
        }

        $pkey = substr($row->start_date, 0, 10) . '|' . ($row->end_date !== null ? substr($row->end_date, 0, 10) : '');

        if (!isset($groups[$rid]['periods'][$pkey])) {
            $groups[$rid]['periods'][$pkey] = [
                'start_date' => substr($row->start_date, 0, 10),
                'end_date'   => $row->end_date !== null ? substr($row->end_date, 0, 10) : null,
                'is_active'  => (int) $row->is_active,
                'segments'   => [],
            ];
        }

        $groups[$rid]['periods'][$pkey]['segments'][] = [
            'id'      => $row->cost_id,
            'cpi'     => (float) $row->cpi,
            'age_min' => $row->age_min !== null ? (int) $row->age_min : null,
            'age_max' => $row->age_max !== null ? (int) $row->age_max : null,
        ];
    }

    foreach ($groups as &$group) {
        $group['periods'] = array_values($group['periods']);
    }
    unset($group);

    return response()->json([
        'success' => true,
        'groups'  => array_values($groups),
    ]);
}

public function updateCampaign(Request $request)
{
    $validated = $request->validate([
        'referral_id'          => 'required|integer',
        'original_start_date'  => 'required|date',
        'original_end_date'    => 'nullable|date',
        'start_date'           => 'required|date',
        'end_date'             => 'nullable|date|after_or_equal:start_date',
        'segments'             => 'required|array|min:1|max:10',
        'segments.*.cpi'       => 'required|numeric|min:0',
        'segments.*.age_min'   => 'nullable|integer|min:1|max:119',
        'segments.*.age_max'   => 'nullable|integer|min:1|max:120',
        'is_active'            => 'nullable|boolean',
    ]);

    $referralId    = (int) $validated['referral_id'];
    $originalStart = substr($validated['original_start_date'], 0, 10);
    $originalEnd   = !empty($validated['original_end_date']) ? substr($validated['original_end_date'], 0, 10) : null;
    $startDate     = $validated['start_date'];
    $endDate       = !empty($validated['end_date']) ? $validated['end_date'] : null;
    $isActive      = isset($validated['is_active']) ? (int) $validated['is_active'] : 1;

    DB::beginTransaction();

    try {
        $referral = DB::table('t_recruitment_referrals')
            ->where('id', $referralId)
            ->where('is_active', 1)
            ->first();

        if (!$referral) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Referral non valido.'], 422);
        }

        // Elimina tutti i segmenti del periodo originale
        $deleteQuery = DB::table('t_recruitment_referral_costs')
            ->where('referral_id', $referralId)
            ->whereDate('start_date', $originalStart);

        if ($originalEnd === null) {
            $deleteQuery->whereNull('end_date');
        } else {
            $deleteQuery->whereDate('end_date', $originalEnd);
        }

        $deleteQuery->delete();

        // Inserisce i nuovi segmenti (con overlap check sugli altri periodi)
        foreach ($validated['segments'] as $segment) {
            $segmentCpi    = (float) $segment['cpi'];
            $segmentAgeMin = isset($segment['age_min']) && $segment['age_min'] !== '' && $segment['age_min'] !== null
                ? (int) $segment['age_min']
                : null;
            $segmentAgeMax = isset($segment['age_max']) && $segment['age_max'] !== '' && $segment['age_max'] !== null
                ? (int) $segment['age_max']
                : null;

            if ($segmentAgeMin !== null && $segmentAgeMax !== null && $segmentAgeMin >= $segmentAgeMax) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => "Eta' min deve essere inferiore a eta' max."], 422);
            }

            $overlapQuery = DB::table('t_recruitment_referral_costs')
                ->where('referral_id', $referralId)
                ->where('is_active', 1)
                ->where(function ($q) use ($segmentAgeMin, $segmentAgeMax) {
                    if ($segmentAgeMax !== null) {
                        $q->where(function ($sub) use ($segmentAgeMax) {
                            $sub->whereNull('age_min')->orWhere('age_min', '<=', $segmentAgeMax);
                        });
                    }
                    if ($segmentAgeMin !== null) {
                        $q->where(function ($sub) use ($segmentAgeMin) {
                            $sub->whereNull('age_max')->orWhere('age_max', '>=', $segmentAgeMin);
                        });
                    }
                })
                ->where(function ($query) use ($startDate, $endDate) {
                    if ($endDate) {
                        $query->whereDate('start_date', '<=', $endDate)
                            ->where(function ($sub) use ($startDate) {
                                $sub->whereNull('end_date')
                                    ->orWhereDate('end_date', '>=', $startDate);
                            });
                    } else {
                        $query->where(function ($sub) use ($startDate) {
                            $sub->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $startDate);
                        });
                    }
                });

            if ($overlapQuery->exists()) {
                DB::rollBack();
                if ($segmentAgeMin !== null && $segmentAgeMax !== null) {
                    $label = " (eta' {$segmentAgeMin}-{$segmentAgeMax})";
                } elseif ($segmentAgeMin !== null) {
                    $label = " (eta' >={$segmentAgeMin})";
                } elseif ($segmentAgeMax !== null) {
                    $label = " (eta' <={$segmentAgeMax})";
                } else {
                    $label = ' (fascia default)';
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Esiste gia\' un periodo sovrapposto per questo referral' . $label . '.'
                ], 422);
            }

            DB::table('t_recruitment_referral_costs')->insert([
                'referral_id' => $referralId,
                'start_date'  => $startDate,
                'end_date'    => $endDate,
                'cpi'         => $segmentCpi,
                'age_min'     => $segmentAgeMin,
                'age_max'     => $segmentAgeMax,
                'is_active'   => $isActive,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        DB::commit();

        return response()->json(['success' => true, 'message' => 'Campagna aggiornata correttamente.']);
    } catch (\Throwable $e) {
        DB::rollBack();

        Log::error('[updateCampaign] Errore: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Errore durante il salvataggio della campagna.'
        ], 500);
    }
}

public function reportReferrals(Request $request)
{
    $year = (int) $request->get('year', now()->year);

    if ($year < 2021 || $year > ((int) now()->year + 1)) {
        $year = (int) now()->year;
    }

    $activeReferralIds = DB::table('t_recruitment_referral_costs')
        ->where('is_active', 1)
        ->whereDate('start_date', '<=', $year . '-12-31')
        ->where(function ($q) use ($year) {
            $q->whereNull('end_date')->orWhereDate('end_date', '>=', $year . '-01-01');
        })
        ->pluck('referral_id')
        ->unique()
        ->values();

    $referrals = DB::table('t_recruitment_referrals')
        ->select('id', 'code', 'title')
        ->where('is_active', 1)
        ->whereIn('id', $activeReferralIds)
        ->orderBy('sort_order')
        ->get();

    return response()->json([
        'success' => true,
        'referrals' => $referrals,
    ]);
}

public function exportReport(Request $request)
{
    $year = (int) $request->get('year');
    $monthFrom = (int) $request->get('month_from');
    $monthTo = (int) $request->get('month_to');
    $referralIds = $request->get('referral_ids', []);
    if (!is_array($referralIds)) {
        $referralIds = [];
    }
    $referralIds = array_values(array_filter(array_map('intval', $referralIds)));

    if ($year < 2021 || $year > ((int) now()->year + 1)) {
        $year = (int) now()->year;
    }

    if ($monthFrom < 1 || $monthFrom > 12) {
        $monthFrom = (int) now()->month;
    }

    if ($monthTo < 1 || $monthTo > 12) {
        $monthTo = $monthFrom;
    }

    if ($monthTo < $monthFrom) {
        $monthTo = $monthFrom;
    }

    $startDate = Carbon::createFromDate($year, $monthFrom, 1)->startOfMonth();
    $endDate = Carbon::createFromDate($year, $monthTo, 1)->endOfMonth();

    $query = DB::table('t_user_info as u')
        ->leftJoin('t_user_invites as ui', 'ui.user_id', '=', 'u.user_id')
        ->select(
            'u.user_id',
            'u.email',
            'u.gender',
            'u.birth_date',
            'u.reg_date',
            'u.provenienza',
            'u.actions',
            DB::raw('COALESCE(ui.invites, 0) as invites')
        )
        ->where('u.reg_date', '>=', $startDate->format('Y-m-d'))
        ->where('u.reg_date', '<', $endDate->copy()->addDay()->format('Y-m-d'))
        ->where('u.email', 'not like', '%.top')
        ->whereNotNull('u.email')
        ->where('u.email', '<>', '');

    $fileLabel = 'tutte';

    if (!empty($referralIds)) {
        $referrals = DB::table('t_recruitment_referrals')
            ->select('id', 'source_codes', 'title')
            ->whereIn('id', $referralIds)
            ->where('is_active', 1)
            ->get();

        if ($referrals->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Nessun referral valido selezionato.'
            ], 422);
        }

        $allSources = [];
        $titles = [];

        foreach ($referrals as $referral) {
            $sources = $this->parseSourceCodes($referral->source_codes);

            if (!empty($sources)) {
                $allSources = array_merge($allSources, $sources);
            }

            $titles[] = $referral->title;
        }

        $allSources = array_values(array_unique($allSources));

        if (empty($allSources)) {
            return response()->json([
                'success' => false,
                'message' => 'I referral selezionati non hanno source_codes configurati.'
            ], 422);
        }

        $query->whereIn('u.provenienza', $allSources);

        $fileLabel = count($titles) === 1
            ? preg_replace('/[^A-Za-z0-9_-]/', '_', $titles[0])
            : 'multi_referral';
    }

    $rows = $query->orderBy('u.reg_date', 'desc')->get();

    // Attivo/Inattivo basato direttamente su u.actions (sincronizzato da t_user_history
    // tramite il bottone "Aggiorna Attivita'" in PanelUsers): nessun arco temporale qui,
    // quindi non serve piu' interrogare t_user_history per ogni export.
    $records = [];
    foreach ($rows as $row) {
        $age = $this->calculateAge($row->birth_date, $row->reg_date);
        $actions = (int) ($row->actions ?? 0);
        $invites = (int) ($row->invites ?? 0);

        $records[] = [
            'user_id' => $row->user_id,
            'email' => $row->email,
            'gender' => $this->mapGenderLabel($row->gender),
            'age' => $age,
            'age_bucket' => $this->mapAge45Bucket($age),
            'provenienza' => $row->provenienza,
            'reg_date' => $row->reg_date,
            'invites' => $invites,
            'actions' => $actions,
            'active' => $actions > 0 ? 'Attivo' : 'Inattivo',
        ];
    }

    $periodLabel = $monthFrom === $monthTo
        ? $this->monthLabel($monthFrom) . ' ' . $year
        : $this->monthLabel($monthFrom) . ' - ' . $this->monthLabel($monthTo) . ' ' . $year;

    $fileName = 'recruitment_report_' . $year . '_' . str_pad($monthFrom, 2, '0', STR_PAD_LEFT)
        . '-' . str_pad($monthTo, 2, '0', STR_PAD_LEFT) . '_' . $fileLabel . '.csv';

    $headers = [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
    ];

    return response()->streamDownload(function () use ($records, $periodLabel, $startDate, $endDate) {
        $handle = fopen('php://output', 'w');

        // BOM UTF-8 per Excel
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $this->writeReportDataCsv($handle, $records, $periodLabel);
        fputcsv($handle, [], ';');
        fputcsv($handle, [], ';');

        $this->writeReportStatsCsv($handle, $records, $periodLabel);
        fputcsv($handle, [], ';');
        fputcsv($handle, [], ';');

        $this->writeReportCostsCsv($handle, $records, $periodLabel, $startDate, $endDate);

        fclose($handle);
    }, $fileName, $headers);
}

private function writeReportDataCsv($handle, array $records, string $periodLabel): void
{
    fputcsv($handle, ['Dati - ' . $periodLabel], ';');
    fputcsv($handle, ['User ID', 'Email', 'Sesso', 'Età', 'Fascia Età', 'Provenienza', 'Inviti', 'Azioni', 'Stato'], ';');

    foreach ($records as $record) {
        fputcsv($handle, [
            $record['user_id'],
            $record['email'],
            $record['gender'],
            $record['age'],
            $record['age_bucket'],
            $record['provenienza'],
            $record['invites'],
            $record['actions'],
            $record['active'],
        ], ';');
    }
}

private function writeReportStatsCsv($handle, array $records, string $periodLabel): void
{
    $total = count($records);

    $under45 = 0;
    $over45 = 0;
    $ageUnknown = 0;
    $male = 0;
    $female = 0;
    $genderUnknown = 0;
    $active = 0;
    $inactive = 0;
    $invitesActive = 0;
    $invitesInactive = 0;
    $totalInvites = 0;
    $totalActions = 0;

    foreach ($records as $record) {
        if ($record['age_bucket'] === 'under') {
            $under45++;
        } elseif ($record['age_bucket'] === 'over') {
            $over45++;
        } else {
            $ageUnknown++;
        }

        if ($record['gender'] === 'M') {
            $male++;
        } elseif ($record['gender'] === 'F') {
            $female++;
        } else {
            $genderUnknown++;
        }

        $totalInvites += $record['invites'];
        $totalActions += $record['actions'];

        if ($record['active'] === 'Attivo') {
            $active++;
            $invitesActive += $record['invites'];
        } else {
            $inactive++;
            $invitesInactive += $record['invites'];
        }
    }

    $pct = function ($value, $total) {
        return $total > 0 ? round(($value / $total) * 100, 1) : 0.0;
    };

    fputcsv($handle, ['Statistiche - ' . $periodLabel], ';');
    fputcsv($handle, ['Totale utenti: ' . $total . ' | Totale inviti: ' . $totalInvites . ' | Totale azioni (actions): ' . $totalActions], ';');
    fputcsv($handle, [], ';');

    $this->writeStatsSectionCsv($handle, 'Fascia Età', [
        ['Under 45', $under45, $pct($under45, $total)],
        ['Over 45', $over45, $pct($over45, $total)],
        ['Non disponibile', $ageUnknown, $pct($ageUnknown, $total)],
    ]);
    fputcsv($handle, [], ';');

    $this->writeStatsSectionCsv($handle, 'Genere', [
        ['Uomo', $male, $pct($male, $total)],
        ['Donna', $female, $pct($female, $total)],
        ['Non disponibile', $genderUnknown, $pct($genderUnknown, $total)],
    ]);
    fputcsv($handle, [], ';');

    $this->writeStatsSectionCsv($handle, 'Attivi / Inattivi (basato su u.actions)', [
        ['Attivi', $active, $pct($active, $total)],
        ['Inattivi', $inactive, $pct($inactive, $total)],
    ]);
    fputcsv($handle, [], ';');

    fputcsv($handle, ['Inviti ricevuti dagli Attivi', $invitesActive], ';');
    fputcsv($handle, ['Inviti ricevuti dagli Inattivi', $invitesInactive], ';');
    fputcsv($handle, ['Azioni per invito (media globale)', $totalInvites > 0 ? round($totalActions / $totalInvites, 2) : 0], ';');
}

private function writeStatsSectionCsv($handle, string $title, array $rowsData): void
{
    fputcsv($handle, [$title], ';');
    fputcsv($handle, ['Categoria', 'Totale', '%'], ';');

    foreach ($rowsData as $data) {
        fputcsv($handle, [$data[0], $data[1], $data[2] . '%'], ';');
    }
}

private function writeReportCostsCsv($handle, array $records, string $periodLabel, Carbon $startDate, Carbon $endDate): void
{
    $referrals = $this->getActiveReferrals();

    $sourceMap = [];
    $fallbackReferral = null;
    foreach ($referrals as $referral) {
        if ($referral->group_type === 'fallback') {
            $fallbackReferral = $referral;
            continue;
        }
        foreach ($this->parseSourceCodes($referral->source_codes) as $code) {
            $sourceMap[$code] = $referral;
        }
    }

    $cpiRows = DB::table('t_recruitment_referral_costs')
        ->select('referral_id', 'start_date', 'end_date', 'cpi', 'age_min', 'age_max')
        ->where('is_active', 1)
        ->whereDate('start_date', '<=', $endDate->format('Y-m-d'))
        ->where(function ($q) use ($startDate) {
            $q->whereNull('end_date')->orWhereDate('end_date', '>=', $startDate->format('Y-m-d'));
        })
        ->orderBy('start_date')
        ->get();

    $cpiByReferral = [];
    foreach ($cpiRows as $row) {
        $cpiByReferral[$row->referral_id][] = [
            'start_date' => $row->start_date,
            'end_date'   => $row->end_date,
            'cpi'        => (float) $row->cpi,
            'age_min'    => $row->age_min !== null ? (int) $row->age_min : null,
            'age_max'    => $row->age_max !== null ? (int) $row->age_max : null,
        ];
    }

    $groups = [];
    foreach ($records as $record) {
        $source = $record['provenienza'];
        $referral = $sourceMap[$source] ?? $fallbackReferral;
        $key = $referral->id ?? 0;

        if (!isset($groups[$key])) {
            $groups[$key] = [
                'label' => $referral->title ?? ($source ?: 'Sconosciuta'),
                'registered' => 0,
                'active' => 0,
                'inactive' => 0,
                'cost_total' => 0.0,
                'cost_active' => 0.0,
                'cost_inactive' => 0.0,
                'segments' => [],
            ];
        }

        $segment = $referral ? $this->findUserSegment($referral->id, $record['reg_date'], $record['age'], $cpiByReferral) : null;
        $cost = $segment ? (float) $segment['cpi'] : 0.0;
        $segmentLabel = $segment
            ? $this->segmentAgeLabel($segment['age_min'], $segment['age_max'])
            : 'Nessuna campagna';

        if (!isset($groups[$key]['segments'][$segmentLabel])) {
            $groups[$key]['segments'][$segmentLabel] = [
                'registered' => 0,
                'active' => 0,
                'inactive' => 0,
                'cost_total' => 0.0,
                'cost_active' => 0.0,
            ];
        }

        $groups[$key]['registered']++;
        $groups[$key]['cost_total'] += $cost;
        $groups[$key]['segments'][$segmentLabel]['registered']++;
        $groups[$key]['segments'][$segmentLabel]['cost_total'] += $cost;

        if ($record['active'] === 'Attivo') {
            $groups[$key]['active']++;
            $groups[$key]['cost_active'] += $cost;
            $groups[$key]['segments'][$segmentLabel]['active']++;
            $groups[$key]['segments'][$segmentLabel]['cost_active'] += $cost;
        } else {
            $groups[$key]['inactive']++;
            $groups[$key]['cost_inactive'] += $cost;
            $groups[$key]['segments'][$segmentLabel]['inactive']++;
        }
    }

    uasort($groups, function ($a, $b) {
        return $b['cost_total'] <=> $a['cost_total'];
    });

    fputcsv($handle, ['Costi - ' . $periodLabel], ';');
    fputcsv($handle, ['Costo calcolato su base CPI per fascia età/periodo campagna, allocato su utenti Attivi/Inattivi (criterio u.actions).'], ';');
    fputcsv($handle, [], ';');
    fputcsv($handle, ['Referral', 'Registrati', 'Attivi', 'Inattivi', '% Attivi', 'Costo Totale', 'Costo su Attivi', 'CPA (Costo/Attivo)'], ';');

    $totals = ['registered' => 0, 'active' => 0, 'inactive' => 0, 'cost_total' => 0.0, 'cost_active' => 0.0];

    foreach ($groups as $group) {
        $activeRate = $group['registered'] > 0 ? round($group['active'] / $group['registered'] * 100, 1) : 0;
        $cpa = $group['active'] > 0 ? $group['cost_total'] / $group['active'] : 0;

        fputcsv($handle, [
            $group['label'],
            $group['registered'],
            $group['active'],
            $group['inactive'],
            $activeRate . '%',
            round($group['cost_total'], 2),
            round($group['cost_active'], 2),
            round($cpa, 2),
        ], ';');

        $totals['registered'] += $group['registered'];
        $totals['active'] += $group['active'];
        $totals['inactive'] += $group['inactive'];
        $totals['cost_total'] += $group['cost_total'];
        $totals['cost_active'] += $group['cost_active'];

        // Breakdown per fascia età (solo se la campagna ha più di 1 segmento), come in Spese Referral
        if (count($group['segments']) > 1) {
            foreach ($group['segments'] as $segmentLabel => $segmentData) {
                $segActiveRate = $segmentData['registered'] > 0 ? round($segmentData['active'] / $segmentData['registered'] * 100, 1) : 0;
                $segCpa = $segmentData['active'] > 0 ? $segmentData['cost_total'] / $segmentData['active'] : 0;

                fputcsv($handle, [
                    '    ↳ ' . $segmentLabel,
                    $segmentData['registered'],
                    $segmentData['active'],
                    $segmentData['inactive'],
                    $segActiveRate . '%',
                    round($segmentData['cost_total'], 2),
                    round($segmentData['cost_active'], 2),
                    round($segCpa, 2),
                ], ';');
            }
        }
    }

    fputcsv($handle, [
        'TOTALE',
        $totals['registered'],
        $totals['active'],
        $totals['inactive'],
        ($totals['registered'] > 0 ? round($totals['active'] / $totals['registered'] * 100, 1) : 0) . '%',
        round($totals['cost_total'], 2),
        round($totals['cost_active'], 2),
        round($totals['active'] > 0 ? $totals['cost_total'] / $totals['active'] : 0, 2),
    ], ';');
}

private function findUserSegment($referralId, $regDate, $age, array $cpiByReferral): ?array
{
    if (!isset($cpiByReferral[$referralId]) || empty($regDate)) {
        return null;
    }

    $regDateStr = substr((string) $regDate, 0, 10);

    $validPeriods = array_values(array_filter($cpiByReferral[$referralId], function ($p) use ($regDateStr) {
        $start = substr($p['start_date'], 0, 10);
        $end   = $p['end_date'] !== null ? substr($p['end_date'], 0, 10) : null;
        return $regDateStr >= $start && ($end === null || $regDateStr <= $end);
    }));

    if (empty($validPeriods)) {
        return null;
    }

    foreach ($validPeriods as $segment) {
        $ageMin = $segment['age_min'];
        $ageMax = $segment['age_max'];

        if ($ageMin === null && $ageMax === null) {
            return $segment;
        }

        if ($age === '' || $age === null) {
            continue;
        }

        if ($ageMin !== null && $age < $ageMin) {
            continue;
        }

        if ($ageMax !== null && $age > $ageMax) {
            continue;
        }

        return $segment;
    }

    return null;
}

private function segmentAgeLabel($ageMin, $ageMax): string
{
    if ($ageMin !== null && $ageMax !== null) {
        return $ageMin . '-' . $ageMax . 'a';
    }

    if ($ageMin !== null) {
        return '≥' . $ageMin . 'a';
    }

    if ($ageMax !== null) {
        return '≤' . $ageMax . 'a';
    }

    return 'tutti';
}

private function monthLabel(int $month): string
{
    $labels = [
        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
        5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
        9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre',
    ];

    return $labels[$month] ?? (string) $month;
}

private function mapGenderLabel($gender)
{
    if ((int) $gender === 1) {
        return 'M';
    }

    if ((int) $gender === 2) {
        return 'F';
    }

    return 'N.D.';
}

private function calculateAge($birthDate, $referenceDate = null)
{
    if (empty($birthDate) || $birthDate === '0000-00-00') {
        return '';
    }

    try {
        $birth = Carbon::parse($birthDate);
        $reference = $referenceDate ? Carbon::parse($referenceDate) : now();

        return $birth->diffInYears($reference);
    } catch (\Throwable $e) {
        return '';
    }
}

private function mapAge45Bucket($age)
{
    if ($age === '' || $age === null) {
        return '';
    }

    return ((int) $age >= 45) ? 'over' : 'under';
}

}
