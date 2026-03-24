<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecruitmentController extends Controller
{
    public function index()
    {
        $currentYear = now()->year;
        $currentMonth = now()->format('m');

        return view('recruitment.index', compact('currentYear', 'currentMonth'));
    }

public function daily(Request $request)
{
    $year = (int) $request->get('year', now()->year);
    $month = (int) $request->get('month', now()->month);

    $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
    $end = $start->copy()->endOfMonth();

    $monthLabel = ucfirst($start->locale('it')->translatedFormat('F Y'));

    $referrals = DB::table('t_recruitment_referrals')
        ->select(
            'code',
            'title',
            'source_codes',
            'group_type',
            'sort_order'
        )
        ->where('is_active', 1)
        ->orderBy('sort_order')
        ->get();

    $rows = DB::table('t_user_info')
        ->select(
            DB::raw('DATE(reg_date) as reg_day'),
            'provenienza',
            DB::raw('COUNT(*) as total')
        )
        ->whereDate('reg_date', '>=', $start->format('Y-m-d'))
        ->whereDate('reg_date', '<=', $end->format('Y-m-d'))
        ->where('email', 'not like', '%.top')
        ->groupBy(DB::raw('DATE(reg_date)'), 'provenienza')
        ->orderBy('reg_day')
        ->get();

    $sourceStatsByDay = [];
    foreach ($rows as $row) {
        $day = $row->reg_day;
        $source = $row->provenienza;

        if (!isset($sourceStatsByDay[$day])) {
            $sourceStatsByDay[$day] = [];
        }

        $sourceStatsByDay[$day][$source] = (int) $row->total;
    }

    $mappedSources = [];
    $fallbackReferral = null;
    $referralTotals = [];
    $dailyTotals = [];

    $cursor = $start->copy();
    while ($cursor->lte($end)) {
        $dayKey = $cursor->format('Y-m-d');
        $dailyTotals[$dayKey] = 0;
        $cursor->addDay();
    }

    foreach ($referrals as $referral) {
        if ($referral->group_type === 'fallback') {
            $fallbackReferral = $referral;
            continue;
        }

        $sources = $this->parseSourceCodes($referral->source_codes);
        $total = 0;

        foreach ($sources as $source) {
            $mappedSources[$source] = true;
        }

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $dayKey = $cursor->format('Y-m-d');

            foreach ($sources as $source) {
                $count = $sourceStatsByDay[$dayKey][$source] ?? 0;
                $total += $count;
                $dailyTotals[$dayKey] += $count;
            }

            $cursor->addDay();
        }

        $referralTotals[] = [
            'code' => $referral->code,
            'title' => $referral->title,
            'total' => $total,
        ];
    }

    if ($fallbackReferral) {
        $total = 0;

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $dayKey = $cursor->format('Y-m-d');

            if (isset($sourceStatsByDay[$dayKey])) {
                foreach ($sourceStatsByDay[$dayKey] as $source => $count) {
                    if (!isset($mappedSources[$source])) {
                        $total += $count;
                        $dailyTotals[$dayKey] += $count;
                    }
                }
            }

            $cursor->addDay();
        }

        $referralTotals[] = [
            'code' => $fallbackReferral->code,
            'title' => $fallbackReferral->title,
            'total' => $total,
        ];
    }

    $referralTotals = array_values(array_filter($referralTotals, function ($item) {
        return $item['total'] > 1;
    }));

    $calendarRows = [];
    $week = array_fill(0, 7, null);

    $cursor = $start->copy();

    while ($cursor->lte($end)) {
        $position = $cursor->dayOfWeekIso - 1; // 0=lun ... 6=dom
        $dayKey = $cursor->format('Y-m-d');

        $week[$position] = [
            'day' => (int) $cursor->format('j'),
            'date' => $dayKey,
            'total' => $dailyTotals[$dayKey] ?? 0,
        ];

        if ($position === 6) {
            $calendarRows[] = $week;
            $week = array_fill(0, 7, null);
        }

        $cursor->addDay();
    }

    $hasContent = false;
    foreach ($week as $cell) {
        if ($cell !== null) {
            $hasContent = true;
            break;
        }
    }

    if ($hasContent) {
        $calendarRows[] = $week;
    }

    $formattedRows = [];
    foreach ($calendarRows as $week) {
        $weekTotal = 0;

        foreach ($week as $cell) {
            if ($cell !== null) {
                $weekTotal += (int) $cell['total'];
            }
        }

        $formattedRows[] = [
            'days' => $week,
            'week_total' => $weekTotal,
        ];
    }

    return response()->json([
        'success' => true,
        'month_label' => $monthLabel,
        'total_registered' => array_sum(array_column($referralTotals, 'total')),
        'referrals' => $referralTotals,
        'calendar' => $formattedRows,
    ]);
}

    public function costs(Request $request)
    {
        $year = (int) $request->get('year', now()->year);

        $referrals = DB::table('t_recruitment_referrals')
            ->select(
                'id',
                'legacy_id',
                'code',
                'title',
                'icon',
                'source_codes',
                'annual_cost',
                'group_type',
                'sort_order'
            )
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();

        $rows = DB::table('t_user_info')
            ->select(
                'provenienza',
                DB::raw('COUNT(*) as registered'),
                DB::raw('SUM(CASE WHEN actions > 0 THEN 1 ELSE 0 END) as active')
            )
            ->whereYear('reg_date', $year)
            ->where('email', 'not like', '%.top')
            ->groupBy('provenienza')
            ->get();

        $sourceStats = [];
        foreach ($rows as $row) {
            $sourceStats[$row->provenienza] = [
                'registered' => (int) $row->registered,
                'active' => (int) $row->active,
            ];
        }

        $mappedSources = [];
        $fallbackReferral = null;
        $table = [];

        foreach ($referrals as $referral) {
            if ($referral->group_type === 'fallback') {
                $fallbackReferral = $referral;
                continue;
            }

            $sources = $this->parseSourceCodes($referral->source_codes);

            $registered = 0;
            $active = 0;

            foreach ($sources as $source) {
                $mappedSources[$source] = true;

                if (isset($sourceStats[$source])) {
                    $registered += $sourceStats[$source]['registered'];
                    $active += $sourceStats[$source]['active'];
                }
            }

            $cost = (float) $referral->annual_cost;
            $activeRate = $registered > 0 ? round(($active / $registered) * 100, 2) : 0;
            $cpi = $registered > 0 ? round($cost / $registered, 2) : 0;
            $cpa = $active > 0 ? round($cost / $active, 2) : 0;

            $table[] = [
                'code' => $referral->code,
                'title' => $referral->title,
                'icon' => $referral->icon,
                'registered' => $registered,
                'active' => $active,
                'active_rate' => $activeRate,
                'cost' => round($cost, 2),
                'cpi' => $cpi,
                'cpa' => $cpa,
            ];
        }

        if ($fallbackReferral) {
            $registered = 0;
            $active = 0;

            foreach ($sourceStats as $source => $stats) {
                if (!isset($mappedSources[$source])) {
                    $registered += $stats['registered'];
                    $active += $stats['active'];
                }
            }

            $cost = (float) $fallbackReferral->annual_cost;
            $activeRate = $registered > 0 ? round(($active / $registered) * 100, 2) : 0;
            $cpi = $registered > 0 ? round($cost / $registered, 2) : 0;
            $cpa = $active > 0 ? round($cost / $active, 2) : 0;

            $table[] = [
                'code' => $fallbackReferral->code,
                'title' => $fallbackReferral->title,
                'icon' => $fallbackReferral->icon,
                'registered' => $registered,
                'active' => $active,
                'active_rate' => $activeRate,
                'cost' => round($cost, 2),
                'cpi' => $cpi,
                'cpa' => $cpa,
            ];
        }

        $totalRegistered = array_sum(array_column($table, 'registered'));
        $totalActive = array_sum(array_column($table, 'active'));
        $totalCost = round(array_sum(array_column($table, 'cost')), 2);

        $kpi = [
            'registered' => $totalRegistered,
            'active' => $totalActive,
            'active_rate' => $totalRegistered > 0 ? round(($totalActive / $totalRegistered) * 100, 2) : 0,
            'cost' => $totalCost,
            'cpi' => $totalRegistered > 0 ? round($totalCost / $totalRegistered, 2) : 0,
            'cpa' => $totalActive > 0 ? round($totalCost / $totalActive, 2) : 0,
        ];

        return response()->json([
            'success' => true,
            'kpi' => $kpi,
            'rows' => $table,
        ]);
    }

public function activity(Request $request)
{
    $year = (int) $request->get('year', now()->year);

    $referrals = DB::table('t_recruitment_referrals')
        ->select(
            'id',
            'legacy_id',
            'code',
            'title',
            'icon',
            'source_codes',
            'annual_cost',
            'group_type',
            'sort_order'
        )
        ->where('is_active', 1)
        ->orderBy('sort_order')
        ->get();

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
            'title' => $referral->title,
            'icon' => $referral->icon,
            'total_registered' => 0,
            'act_0' => 0,
            'act_1_2' => 0,
            'act_3_5' => 0,
            'act_6_9' => 0,
            'act_10_plus' => 0,
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
        }

        $item['perc_0'] = $item['total_registered'] > 0 ? round(($item['act_0'] / $item['total_registered']) * 100, 2) : 0;
        $item['perc_1_2'] = $item['total_registered'] > 0 ? round(($item['act_1_2'] / $item['total_registered']) * 100, 2) : 0;
        $item['perc_3_5'] = $item['total_registered'] > 0 ? round(($item['act_3_5'] / $item['total_registered']) * 100, 2) : 0;
        $item['perc_6_9'] = $item['total_registered'] > 0 ? round(($item['act_6_9'] / $item['total_registered']) * 100, 2) : 0;
        $item['perc_10_plus'] = $item['total_registered'] > 0 ? round(($item['act_10_plus'] / $item['total_registered']) * 100, 2) : 0;

        if ($item['total_registered'] > 0) {
            $result[] = $item;
        }
    }

    if ($fallbackReferral) {
        $item = [
            'code' => $fallbackReferral->code,
            'title' => $fallbackReferral->title,
            'icon' => $fallbackReferral->icon,
            'total_registered' => 0,
            'act_0' => 0,
            'act_1_2' => 0,
            'act_3_5' => 0,
            'act_6_9' => 0,
            'act_10_plus' => 0,
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
        }

        $item['perc_0'] = $item['total_registered'] > 0 ? round(($item['act_0'] / $item['total_registered']) * 100, 2) : 0;
        $item['perc_1_2'] = $item['total_registered'] > 0 ? round(($item['act_1_2'] / $item['total_registered']) * 100, 2) : 0;
        $item['perc_3_5'] = $item['total_registered'] > 0 ? round(($item['act_3_5'] / $item['total_registered']) * 100, 2) : 0;
        $item['perc_6_9'] = $item['total_registered'] > 0 ? round(($item['act_6_9'] / $item['total_registered']) * 100, 2) : 0;
        $item['perc_10_plus'] = $item['total_registered'] > 0 ? round(($item['act_10_plus'] / $item['total_registered']) * 100, 2) : 0;

        if ($item['total_registered'] > 0) {
            $result[] = $item;
        }
    }

    return response()->json([
        'success' => true,
        'rows' => $result,
    ]);
}

public function stats(Request $request)
{
    $year = (int) $request->get('year', now()->year);

    $rows = DB::table('t_user_info')
        ->select(
            'gender',
            'birth_date',
            'area'
        )
        ->whereYear('reg_date', $year)
        ->where('email', 'not like', '%.top')
        ->get();

    $gender = [
        'male' => 0,
        'female' => 0,
        'unknown' => 0,
    ];

    $ages = [
        'under_18' => 0,
        '18_24' => 0,
        '25_34' => 0,
        '35_44' => 0,
        '45_54' => 0,
        '55_64' => 0,
        '65_plus' => 0,
        'unknown' => 0,
    ];

    $areas = [
        'nord_ovest' => 0,
        'nord_est' => 0,
        'centro' => 0,
        'sud' => 0,
        'unknown' => 0,
    ];

    foreach ($rows as $row) {
        if ((int) $row->gender === 1) {
            $gender['male']++;
        } elseif ((int) $row->gender === 2) {
            $gender['female']++;
        } else {
            $gender['unknown']++;
        }

        $age = $this->calculateAgeFromBirthDate($row->birth_date);

        if ($age === null) {
            $ages['unknown']++;
        } elseif ($age < 18) {
            $ages['under_18']++;
        } elseif ($age <= 24) {
            $ages['18_24']++;
        } elseif ($age <= 34) {
            $ages['25_34']++;
        } elseif ($age <= 44) {
            $ages['35_44']++;
        } elseif ($age <= 54) {
            $ages['45_54']++;
        } elseif ($age <= 64) {
            $ages['55_64']++;
        } else {
            $ages['65_plus']++;
        }

        $areaValue = strtolower(trim($row->area ?? ''));

        if ($areaValue === 'nord ovest') {
            $areas['nord_ovest']++;
        } elseif ($areaValue === 'nord est') {
            $areas['nord_est']++;
        } elseif ($areaValue === 'centro') {
            $areas['centro']++;
        } elseif ($areaValue === 'sud') {
            $areas['sud']++;
        } else {
            $areas['unknown']++;
        }
    }

    return response()->json([
        'success' => true,
        'gender' => [
            'labels' => ['Uomini', 'Donne', 'N.D.'],
            'data' => [
                $gender['male'],
                $gender['female'],
                $gender['unknown'],
            ],
        ],
        'ages' => [
            'labels' => ['<18', '18-24', '25-34', '35-44', '45-54', '55-64', '65+', 'N.D.'],
            'data' => [
                $ages['under_18'],
                $ages['18_24'],
                $ages['25_34'],
                $ages['35_44'],
                $ages['45_54'],
                $ages['55_64'],
                $ages['65_plus'],
                $ages['unknown'],
            ],
        ],
        'areas' => [
            'labels' => ['Nord Ovest', 'Nord Est', 'Centro', 'Sud', 'N.D.'],
            'data' => [
                $areas['nord_ovest'],
                $areas['nord_est'],
                $areas['centro'],
                $areas['sud'],
                $areas['unknown'],
            ],
        ],
    ]);
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

   private function calculateAgeFromBirthDate($birthDate)
{
    if (empty($birthDate) || $birthDate === '0000-00-00') {
        return null;
    }

    try {
        return \Carbon\Carbon::parse($birthDate)->age;
    } catch (\Exception $e) {
        return null;
    }
}


}
