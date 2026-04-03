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

        $referrals = $this->getActiveReferrals();

        $rows = DB::table('t_user_info')
            ->select(
                'provenienza',
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('reg_date', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
            ->where('email', 'not like', '%.top')
            ->whereNotNull('provenienza')
            ->where('provenienza', '<>', '')
            ->groupBy('provenienza')
            ->get();

        $sourceStats = [];
        foreach ($rows as $row) {
            $sourceStats[$row->provenienza] = (int) $row->total;
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
            $total = 0;
            $matchedSources = [];

            foreach ($sources as $source) {
                $mappedSources[$source] = true;

                if (isset($sourceStats[$source])) {
                    $total += $sourceStats[$source];
                    $matchedSources[] = $source;
                }
            }

            if ($total > 0) {
                $groupedReferrals[] = [
                    'code' => $referral->code,
                    'label' => $referral->title,
                    'icon' => $referral->icon,
                    'total' => $total,
                    'sources' => $matchedSources,
                    'sort_order' => (int) $referral->sort_order,
                ];
            }
        }

        if ($fallbackReferral) {
            $fallbackTotal = 0;
            $fallbackSources = [];

            foreach ($sourceStats as $source => $count) {
                if (isset($mappedSources[$source])) {
                    continue;
                }

                $fallbackTotal += $count;
                $fallbackSources[] = $source;
            }

            if ($fallbackTotal > 0) {
                $groupedReferrals[] = [
                    'code' => $fallbackReferral->code,
                    'label' => $fallbackReferral->title,
                    'icon' => $fallbackReferral->icon,
                    'total' => $fallbackTotal,
                    'sources' => $fallbackSources,
                    'sort_order' => (int) $fallbackReferral->sort_order,
                ];
            }
        }

        usort($groupedReferrals, function ($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });

        $totalRegistered = array_sum(array_column($groupedReferrals, 'total'));

        $monthLabel = ucfirst($startDate->locale('it')->translatedFormat('F Y'));

        return response()->json([
            'success' => true,
            'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
            'year' => $year,
            'month_label' => $monthLabel,
            'total_registered' => $totalRegistered,
            'referrals' => array_map(function ($item) {
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

    $rows = DB::table('t_user_info')
        ->select(
            DB::raw('MONTH(reg_date) as month_num'),
            'provenienza',
            DB::raw('COUNT(*) as registered'),
            DB::raw('SUM(CASE WHEN COALESCE(actions, 0) > 0 THEN 1 ELSE 0 END) as active')
        )
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

        $sourceStatsByMonth[$monthNum][$source] = [
            'registered' => (int) $row->registered,
            'active' => (int) $row->active,
        ];
    }

    $cpiRows = DB::table('t_recruitment_referral_costs')
        ->select('referral_id', 'start_date', 'end_date', 'cpi')
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
            'end_date' => $row->end_date,
            'cpi' => (float) $row->cpi,
        ];
    }

    $mappedSources = [];
    $fallbackReferral = null;
    $tableRows = [];

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

            foreach ($sources as $source) {
                if (!isset($sourceStatsByMonth[$month][$source])) {
                    continue;
                }

                $monthRegistered += $sourceStatsByMonth[$month][$source]['registered'];
                $monthActive += $sourceStatsByMonth[$month][$source]['active'];

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
            $monthCpi = $this->resolveReferralCpiForDate($referral->id, $referenceDate, $cpiByReferral);

            $cost += ($monthRegistered * $monthCpi);
        }

        if ($registered <= 0) {
            continue;
        }

        $cost = round($cost, 2);
        $activeRate = $registered > 0 ? round(($active / $registered) * 100, 2) : 0;
        $avgCpi = $registered > 0 ? round($cost / $registered, 4) : 0;
        $cpa = $active > 0 ? round($cost / $active, 2) : 0;

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

            if (isset($sourceStatsByMonth[$month])) {
                foreach ($sourceStatsByMonth[$month] as $source => $stats) {
                    if (isset($mappedSources[$source])) {
                        continue;
                    }

                    $monthRegistered += $stats['registered'];
                    $monthActive += $stats['active'];

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
            $monthCpi = $this->resolveReferralCpiForDate($fallbackReferral->id, $referenceDate, $cpiByReferral);

            $cost += ($monthRegistered * $monthCpi);
        }

        if ($registered > 0) {
            $cost = round($cost, 2);
            $activeRate = $registered > 0 ? round(($active / $registered) * 100, 2) : 0;
            $avgCpi = $registered > 0 ? round($cost / $registered, 4) : 0;
            $cpa = $active > 0 ? round($cost / $active, 2) : 0;

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

    $rows = DB::table('t_user_info')
        ->select(
            'provenienza',
            DB::raw('COUNT(*) as total_registered'),
            DB::raw('SUM(CASE WHEN COALESCE(actions, 0) = 0 THEN 1 ELSE 0 END) as act_0'),
            DB::raw('SUM(CASE WHEN actions BETWEEN 1 AND 2 THEN 1 ELSE 0 END) as act_1_2'),
            DB::raw('SUM(CASE WHEN actions BETWEEN 3 AND 5 THEN 1 ELSE 0 END) as act_3_5'),
            DB::raw('SUM(CASE WHEN actions BETWEEN 6 AND 9 THEN 1 ELSE 0 END) as act_6_9'),
            DB::raw('SUM(CASE WHEN actions >= 10 THEN 1 ELSE 0 END) as act_10_plus')
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
                     AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 18
                THEN 1 ELSE 0 END) as age_under_18"),

            DB::raw("SUM(CASE
                WHEN birth_date IS NOT NULL
                     AND birth_date <> '0000-00-00'
                     AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 18 AND 24
                THEN 1 ELSE 0 END) as age_18_24"),

            DB::raw("SUM(CASE
                WHEN birth_date IS NOT NULL
                     AND birth_date <> '0000-00-00'
                     AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 25 AND 34
                THEN 1 ELSE 0 END) as age_25_34"),

            DB::raw("SUM(CASE
                WHEN birth_date IS NOT NULL
                     AND birth_date <> '0000-00-00'
                     AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 35 AND 44
                THEN 1 ELSE 0 END) as age_35_44"),

            DB::raw("SUM(CASE
                WHEN birth_date IS NOT NULL
                     AND birth_date <> '0000-00-00'
                     AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 45 AND 54
                THEN 1 ELSE 0 END) as age_45_54"),

            DB::raw("SUM(CASE
                WHEN birth_date IS NOT NULL
                     AND birth_date <> '0000-00-00'
                     AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 55 AND 64
                THEN 1 ELSE 0 END) as age_55_64"),

            DB::raw("SUM(CASE
                WHEN birth_date IS NOT NULL
                     AND birth_date <> '0000-00-00'
                     AND TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= 65
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

    foreach ($cpiByReferral[$referralId] as $period) {
        $startDate = $period['start_date'];
        $endDate = $period['end_date'];

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

public function latestRegistrations()
{
    $referrals = $this->getActiveReferrals();

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
        $mappedReferral = $this->mapSourceToReferral($row->provenienza, $referrals);

        $mappedRows[] = [
            'reg_date' => $row->reg_date
                ? Carbon::parse($row->reg_date)->format('d/m/Y H:i')
                : '-',
            'email' => $row->email ?: '-',
            'source' => $row->provenienza ?: '-',
            'referral_code' => $mappedReferral['code'],
            'referral_label' => $mappedReferral['label'],
            'referral_icon' => $mappedReferral['icon'],
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

    $budget = 15000;

    $referrals = $this->getActiveReferrals();

    $rows = DB::table('t_user_info')
        ->select(
            DB::raw('MONTH(reg_date) as month_num'),
            'provenienza',
            DB::raw('COUNT(*) as registered'),
            DB::raw('SUM(CASE WHEN COALESCE(actions, 0) > 0 THEN 1 ELSE 0 END) as active')
        )
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

        $sourceStatsByMonth[$monthNum][$source] = [
            'registered' => (int) $row->registered,
            'active' => (int) $row->active,
        ];
    }

    $cpiRows = DB::table('t_recruitment_referral_costs')
        ->select('referral_id', 'start_date', 'end_date', 'cpi')
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
            'end_date' => $row->end_date,
            'cpi' => (float) $row->cpi,
        ];
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

            foreach ($sources as $source) {
                if (!isset($sourceStatsByMonth[$month][$source])) {
                    continue;
                }

                $monthRegistered += $sourceStatsByMonth[$month][$source]['registered'];
                $monthActive += $sourceStatsByMonth[$month][$source]['active'];

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
            $monthCpi = $this->resolveReferralCpiForDate($referral->id, $referenceDate, $cpiByReferral);

            $cost += ($monthRegistered * $monthCpi);
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

            if (isset($sourceStatsByMonth[$month])) {
                foreach ($sourceStatsByMonth[$month] as $source => $stats) {
                    if (isset($mappedSources[$source])) {
                        continue;
                    }

                    $monthRegistered += $stats['registered'];
                    $monthActive += $stats['active'];

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
            $monthCpi = $this->resolveReferralCpiForDate($fallbackReferral->id, $referenceDate, $cpiByReferral);

            $cost += ($monthRegistered * $monthCpi);
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

        'start_date' => 'required|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'cpi' => 'required|numeric|min:0',
        'is_active' => 'nullable|boolean',
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

            $codeExists = DB::table('t_recruitment_referrals')
                ->where('code', $newCode)
                ->exists();

            if ($codeExists) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Il codice referral esiste già.'
                ], 422);
            }

            $maxSortOrder = DB::table('t_recruitment_referrals')->max('sort_order');
            $nextSortOrder = ((int) $maxSortOrder) + 1;

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
        }

        $startDate = $validated['start_date'];
        $endDate = !empty($validated['end_date']) ? $validated['end_date'] : null;
        $isActive = isset($validated['is_active']) ? (int) $validated['is_active'] : 1;

        $overlapQuery = DB::table('t_recruitment_referral_costs')
            ->where('referral_id', $referralId)
            ->where('is_active', 1)
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

            return response()->json([
                'success' => false,
                'message' => 'Esiste già una campagna attiva o sovrapposta per questo referral nel periodo selezionato.'
            ], 422);
        }

        DB::table('t_recruitment_referral_costs')->insert([
            'referral_id' => $referralId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'cpi' => $validated['cpi'],
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Campagna inserita correttamente.'
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

public function exportReport(Request $request)
{
    $year = (int) $request->get('year');
    $month = (int) $request->get('month');
    $referralIds = $request->get('referral_ids', []);
    if (!is_array($referralIds)) {
        $referralIds = [];
    }
    $referralIds = array_values(array_filter(array_map('intval', $referralIds)));

    if ($year < 2021 || $year > ((int) now()->year + 1)) {
        $year = (int) now()->year;
    }

    if ($month < 1 || $month > 12) {
        $month = (int) now()->month;
    }

    $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $endDate = $startDate->copy()->endOfMonth();

$query = DB::table('t_user_info as u')
    ->leftJoin('t_user_invites as ui', 'ui.user_id', '=', 'u.user_id')
    ->select(
        'u.user_id',
        'u.email',
        'u.gender',
        'u.birth_date',
        'u.provenienza',
        'u.actions',
        DB::raw('COALESCE(ui.invites, 0) as invites')
    )
    ->whereBetween('u.reg_date', [
        $startDate->format('Y-m-d 00:00:00'),
        $endDate->format('Y-m-d 23:59:59'),
    ])
    ->where('u.email', 'not like', '%.top')
    ->whereNotNull('u.email')
    ->where('u.email', '<>', '');

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
} else {
    $fileLabel = 'tutte';
}
    $rows = $query
        ->orderBy('reg_date', 'desc')
        ->get();

    $fileName = 'recruitment_report_' . $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '_' . $fileLabel . '.csv';

    $headers = [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
    ];

    $callback = function () use ($rows) {
        $handle = fopen('php://output', 'w');

        // BOM UTF-8 per Excel
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($handle, [
    'user_id',
    'email',
    'sesso',
    'eta',
    'eta_45',
    'provenienza',
    'actions',
    'invites'
], ';');

    foreach ($rows as $row) {
        $age = $this->calculateAgeForCsv($row->birth_date);

        fputcsv($handle, [
            $row->user_id,
            $row->email,
            $this->mapGenderLabel($row->gender),
            $age,
            $this->mapAge45Bucket($age),
            $row->provenienza,
            (int) ($row->actions ?? 0),
            (int) ($row->invites ?? 0),
        ], ';');
    }

        fclose($handle);
    };

    return response()->streamDownload($callback, $fileName, $headers);
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

private function calculateAgeForCsv($birthDate)
{
    if (empty($birthDate) || $birthDate === '0000-00-00') {
        return '';
    }

    try {
        return Carbon::parse($birthDate)->age;
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
