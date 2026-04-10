<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function getTopbarSummary(): array
    {
        $amazonRewards = DB::table('t_user_history as h')
            ->join('t_user_info as u', 'u.user_id', '=', 'h.user_id')
            ->where('h.event_type', 'withdraw')
            ->where('h.pagato', 0)
            ->where('u.active', 1)
            ->where('h.event_info', 'like', '%Amazon%')
            ->count();

        $paypalRewards = DB::table('t_user_history as h')
            ->join('t_user_info as u', 'u.user_id', '=', 'h.user_id')
            ->where('h.event_type', 'withdraw')
            ->where('h.pagato', 0)
            ->where('u.active', 1)
            ->where('h.event_info', 'like', '%Paypal%')
            ->count();

        $openTickets = DB::table('t_user_tickets')
            ->where('status', 0)
            ->count();

        $runningSurveys = DB::table('t_panel_control')
            ->where('stato', 0)
            ->count();

        $total = $amazonRewards + $paypalRewards + $openTickets;

        return [
            'total' => $total,
            'amazon_rewards' => $amazonRewards,
            'paypal_rewards' => $paypalRewards,
            'open_tickets' => $openTickets,
            'running_surveys' => $runningSurveys,
            'items' => [
                [
                    'key' => 'amazon_rewards',
                    'label' => 'Premi Amazon',
                    'count' => $amazonRewards,
                    'url' => route('premi.panel', [
                        'type' => 'amazon',
                        'status' => 0,
                    ]),
                ],
                [
                    'key' => 'paypal_rewards',
                    'label' => 'Premi PayPal',
                    'count' => $paypalRewards,
                    'url' => route('premi.panel', [
                        'type' => 'paypal',
                        'status' => 0,
                    ]),
                ],
                [
                    'key' => 'open_tickets',
                    'label' => 'Ticket aperti',
                    'count' => $openTickets,
                    'url' => route('tickets.index'),
                ],
            ],
            'footer' => [
                'label' => 'Ricerche in corso',
                'count' => $runningSurveys,
                'url' => route('surveys.index'),
            ],
        ];
    }
}
