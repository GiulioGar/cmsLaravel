<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class PanelUsersController extends Controller
{
    public function index()
    {
        return view('panelUsers');
    }

    public function getData(Request $request)
    {
        /*
         * Subquery inviti
         */
        $invitesSub = DB::table('t_user_invites')
            ->select(
                'user_id',
                DB::raw('COALESCE(invites, 0) as invites')
            );

        /*
         * Subquery attività + partecipazione + ultima azione
         */
$historySub = DB::table('t_user_history')
    ->select(
        'user_id',
        DB::raw("
            SUM(
                CASE
                    WHEN event_type NOT IN ('subscribe', 'unsubscribe')
                    THEN 1
                    ELSE 0
                END
            ) as activity_count
        "),
        DB::raw("
            SUM(
                CASE
                    WHEN event_type IN (
                        'interview_quotafull',
                        'interview_complete',
                        'interview_screenout',
                        'interview_complete_cint'
                    )
                    THEN 1
                    ELSE 0
                END
            ) as interview_count
        "),
        DB::raw('MAX(id) as last_history_id'),
        DB::raw('MAX(event_date) as last_event_date')
    )
    ->groupBy('user_id');

        /*
         * Query principale
         */
$query = DB::table('t_user_info as u')
    ->where('u.confirm', 1)
    ->where('u.active', 1)
    ->leftJoinSub($invitesSub, 'inv', function ($join) {
        $join->on('u.user_id', '=', 'inv.user_id');
    })
    ->leftJoinSub($historySub, 'h', function ($join) {
        $join->on('u.user_id', '=', 'h.user_id');
    })
    ->select([
        'u.user_id',
        'u.email',
        'u.birth_date',
        'u.reg_date',
        DB::raw('COALESCE(inv.invites, 0) as invites'),
        DB::raw('COALESCE(h.activity_count, 0) as activity_count'),
        DB::raw('COALESCE(h.interview_count, 0) as interview_count'),
        DB::raw('h.last_event_date as last_event_date'),
    ]);

        return DataTables::of($query)
            ->editColumn('birth_date', function ($row) {
                if (empty($row->birth_date)) {
                    return 'N.D.';
                }

                try {
                    return Carbon::parse($row->birth_date)->age;
                } catch (\Exception $e) {
                    return 'N.D.';
                }
            })
            ->editColumn('activity_count', function ($row) {
                $count = (int) $row->activity_count;

                if ($count <= 0) {
                    return '<span class="pu-badge pu-badge-muted">0</span>';
                }

                if ($count < 5) {
                    return '<span class="pu-badge pu-badge-low">'.$count.'</span>';
                }

                if ($count < 20) {
                    return '<span class="pu-badge pu-badge-mid">'.$count.'</span>';
                }

                return '<span class="pu-badge pu-badge-high">'.$count.'</span>';
            })
            ->addColumn('partecipazione', function ($row) {
                $invites = (int) $row->invites;
                $interviews = (int) $row->interview_count;

                if ($invites <= 0) {
                    return '<span class="pu-badge pu-badge-muted">0%</span>';
                }

                $percentage = round(($interviews / $invites) * 100, 1);

                if ($percentage < 20) {
                    $class = 'pu-badge-danger';
                } elseif ($percentage < 50) {
                    $class = 'pu-badge-warn';
                } else {
                    $class = 'pu-badge-success';
                }

                return '<span class="pu-badge '.$class.'">'.$percentage.'%</span>';
            })
            ->editColumn('reg_date', function ($row) {
                if (empty($row->reg_date)) {
                    return 'N.D.';
                }

                try {
                    $regDate = Carbon::parse($row->reg_date);
                    $now = now();

                    $months = $regDate->diffInMonths($now);
                    $years = $regDate->diffInYears($now);

                    if ($months < 12) {
                        return $months . ' mesi';
                    }

                    return $years . ' anni';
                } catch (\Exception $e) {
                    return 'N.D.';
                }
            })
            ->editColumn('last_event_date', function ($row) {
                if (empty($row->last_event_date)) {
                    return 'N.D.';
                }

                try {
                    return Carbon::parse($row->last_event_date)->format('d/m/Y');
                } catch (\Exception $e) {
                    return 'N.D.';
                }
            })
        ->rawColumns(['activity_count', 'partecipazione'])
        ->make(true);
    }
}
