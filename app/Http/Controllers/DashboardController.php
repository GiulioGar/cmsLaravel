<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        /**
         * 1) Query per i record di t_panel_control
         */
        $records = DB::select("
            SELECT sur_id, description, red_surv, durata, goal, complete, end_field
            FROM t_panel_control
            WHERE stato = 0
            ORDER BY stato, giorni_rimanenti ASC, id DESC
        ");
        // Con DB::select(), $records è un array di oggetti stdClass.

        /**
         * 2) Data odierna con Carbon (invece di new DateTime())
         */
        $oggi = Carbon::now();

        /**
         * 3) Totale utenti iscritti
         */
        $resultTotalUsers = DB::select("
            SELECT COUNT(*) AS total_users
            FROM t_user_info
            WHERE active = 1
              AND confirm = 1
        ");
        // Essendo un array di un solo elemento, recuperiamo la proprietà total_users
        $totalUsers = $resultTotalUsers[0]->total_users ?? 0;

        /**
         * 4) Utenti attivi (actions > 1)
         */
        $resultActiveUsers = DB::select("
            SELECT COUNT(*) AS active_users
            FROM t_user_info
            WHERE active = 1
              AND confirm = 1
              AND actions > 1
        ");
        $activeUsers = $resultActiveUsers[0]->active_users ?? 0;

        // Calcolo percentuale attivi
        $activePercentage = ($totalUsers > 0)
            ? round(($activeUsers / $totalUsers) * 100, 2)
            : 0;

        /**
         * 5) Distribuzione per genere (uomini/donne totali)
         */
        $totalMen = 0;
        $totalWomen = 0;

        $genderResults = DB::select("
            SELECT gender, COUNT(*) AS total
            FROM t_user_info
            WHERE active = 1
              AND confirm = 1
            GROUP BY gender
        ");

        foreach ($genderResults as $row) {
            if ($row->gender == 1) {
                $totalMen = $row->total;
            } elseif ($row->gender == 2) {
                $totalWomen = $row->total;
            }
        }

        /**
         * 6) Distribuzione per genere (uomini/donne attivi)
         */
        $activeMen = 0;
        $activeWomen = 0;

        $activeGenderResults = DB::select("
            SELECT gender, COUNT(*) AS active_total
            FROM t_user_info
            WHERE active = 1
              AND confirm = 1
              AND actions > 1
            GROUP BY gender
        ");

        foreach ($activeGenderResults as $row) {
            if ($row->gender == 1) {
                $activeMen = $row->active_total;
            } elseif ($row->gender == 2) {
                $activeWomen = $row->active_total;
            }
        }

        // Calcolo percentuali di uomini/donne attivi sul totale di uomini/donne
        // Attenzione: $totalUsers era la somma di tutti, ma qui usiamo i totali separati
        $totalUsers = $totalMen + $totalWomen;  // Se vogliamo riconciliare questo valore
        $activeMenPercentage = ($totalMen > 0)
            ? round(($activeMen / $totalMen) * 100, 2)
            : 0;
        $activeWomenPercentage = ($totalWomen > 0)
            ? round(($activeWomen / $totalWomen) * 100, 2)
            : 0;

        /**
         * 7) Distribuzione età
         */
        // Pre-inizializzazione
        $ageGroups = [
            "Under 18" => 0,
            "18-24" => 0,
            "25-34" => 0,
            "35-44" => 0,
            "45-54" => 0,
            "55-65" => 0,
            "Over 65" => 0
        ];

        $ageResults = DB::select("
            SELECT
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(birth_date, '%Y-%m-%d'), CURDATE()) < 18 THEN 'Under 18'
                    WHEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(birth_date, '%Y-%m-%d'), CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
                    WHEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(birth_date, '%Y-%m-%d'), CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
                    WHEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(birth_date, '%Y-%m-%d'), CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
                    WHEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(birth_date, '%Y-%m-%d'), CURDATE()) BETWEEN 45 AND 54 THEN '45-54'
                    WHEN TIMESTAMPDIFF(YEAR, STR_TO_DATE(birth_date, '%Y-%m-%d'), CURDATE()) BETWEEN 55 AND 65 THEN '55-65'
                    ELSE 'Over 65'
                END AS age_group,
                COUNT(*) AS total
            FROM t_user_info
            WHERE active = 1
            GROUP BY age_group
        ");

        // Aggiorna le fasce di età con i risultati
        foreach ($ageResults as $row) {
            $group = $row->age_group;
            $ageGroups[$group] = $row->total;
        }

        /**
         * 8) Distribuzione per area
         */
        $areaGroups = [
            "Nord Ovest" => 0,
            "Nord Est" => 0,
            "Centro" => 0,
            "Sud e Isole" => 0
        ];

        $areaResults = DB::select("
            SELECT area, COUNT(*) AS total
            FROM t_user_info
            WHERE active = 1
            GROUP BY area
        ");

        foreach ($areaResults as $row) {
            switch ($row->area) {
                case 1:
                    $areaGroups["Nord Ovest"] = $row->total;
                    break;
                case 2:
                    $areaGroups["Nord Est"] = $row->total;
                    break;
                case 3:
                    $areaGroups["Centro"] = $row->total;
                    break;
                case 4:
                    $areaGroups["Sud e Isole"] = $row->total;
                    break;
            }
        }

        /**
         * 9) Registrazioni mensili anno corrente
         */
        // Inizializziamo array [1..12] a zero
        $monthlyRegistrations = array_fill(1, 12, 0);
        $monthlyActiveRegistrations = array_fill(1, 12, 0);

        // Anno corrente
        $currentYear = date("Y");

        // Query per contare i registrati mese per mese
        $registrations = DB::select("
            SELECT MONTH(STR_TO_DATE(reg_date, '%Y-%m-%d %H:%i:%s')) AS month, COUNT(*) AS total
            FROM t_user_info
            WHERE YEAR(STR_TO_DATE(reg_date, '%Y-%m-%d %H:%i:%s')) = :currentYear
            GROUP BY month
        ", ['currentYear' => $currentYear]);

        foreach ($registrations as $row) {
            $m = $row->month;  // mese
            $monthlyRegistrations[$m] = $row->total;
        }

        // Query per contare i registrati attivi mese per mese
        $activeRegistrations = DB::select("
            SELECT MONTH(STR_TO_DATE(reg_date, '%Y-%m-%d %H:%i:%s')) AS month, COUNT(*) AS total
            FROM t_user_info
            WHERE YEAR(STR_TO_DATE(reg_date, '%Y-%m-%d %H:%i:%s')) = :currentYear
              AND actions > 0
            GROUP BY month
        ", ['currentYear' => $currentYear]);

        foreach ($activeRegistrations as $row) {
            $m = $row->month;
            $monthlyActiveRegistrations[$m] = $row->total;
        }

        /**
         * 10) Utenti attivi ultimi 5 anni
         */
        $years = range($currentYear - 4, $currentYear); // es: [2019,2020,2021,2022,2023]
        $activeUsersPerYear = array_fill_keys($years, 0);

        $activeResults = DB::select("
            SELECT COUNT(DISTINCT story.user_id) AS total, YEAR(event_date) AS year
            FROM t_user_history AS story
            WHERE story.event_type NOT IN ('subscribe', 'unsubscribe')
              AND YEAR(event_date) BETWEEN :startYear AND :currentYear
            GROUP BY year
        ", ['startYear' => $currentYear - 4, 'currentYear' => $currentYear]);

        foreach ($activeResults as $row) {
            $yr = $row->year;
            $activeUsersPerYear[$yr] = $row->total;
        }

         /**
         * SEZIONE DASHBOARD RICERCHE (Sostituzione Registrazioni con Interviste Complete)
         */

        // Inizializziamo array con i mesi dell'anno
        $monthlyCompleteMillebytes = array_fill(1, 12, 0);
        $monthlyCompleteCint = array_fill(1, 12, 0);

        // Anno corrente
        $currentYear = date("Y");

        // Query per contare le interviste complete per mese
        $completes = DB::select("
            SELECT MONTH(event_date) AS month, event_type, COUNT(*) AS total
            FROM t_user_history
            WHERE YEAR(event_date) = :currentYear
              AND event_type IN ('interview_complete', 'interview_complete_cint')
            GROUP BY month, event_type
        ", ['currentYear' => $currentYear]);

        foreach ($completes as $row) {
            $m = $row->month;
            if ($row->event_type == 'interview_complete') {
                $monthlyCompleteMillebytes[$m] = $row->total;
            } elseif ($row->event_type == 'interview_complete_cint') {
                $monthlyCompleteCint[$m] = $row->total;
            }
        }

        /**
 *  Numero di progetti aperti nel 2025 (suddivisi per mese)
 */
$monthlyOpenProjects = array_fill(1, 12, 0);

$openProjects = DB::select("
    SELECT MONTH(sur_date) AS month, COUNT(*) AS total
    FROM t_panel_control
    WHERE YEAR(sur_date) = 2025
    GROUP BY month
");

foreach ($openProjects as $row) {
    $m = $row->month;
    $monthlyOpenProjects[$m] = $row->total;
}


/**
 * 11) Numero totale di contatti nel 2025
 */
$resultTotalContacts = DB::select("
    SELECT SUM(contatti) AS total_contacts
    FROM t_panel_control
    WHERE YEAR(sur_date) = 2025
");

$totalContacts = $resultTotalContacts[0]->total_contacts ?? 0;

$totalContacts = $resultTotalContacts[0]->total_contacts ?? 0;

/**
 * 12) Media di red_panel e media di red_surv
 */
$resultAvgRed = DB::select("
    SELECT AVG(red_panel) AS avg_red_panel, AVG(red_surv) AS avg_red_surv
    FROM t_panel_control
    WHERE YEAR(sur_date) = 2025
");

$avgRedPanel = round($resultAvgRed[0]->avg_red_panel ?? 0, 2);
$avgRedSurv = round($resultAvgRed[0]->avg_red_surv ?? 0, 2);

/**
 * 13) Ultime 20 attività (log attività da t_user_history)
 */
$activityLog = DB::select("
    SELECT event_date, event_info
    FROM t_user_history
    ORDER BY event_date DESC
    LIMIT 20
");

$formattedActivities = [];
foreach ($activityLog as $row) {
    // Formattiamo la data come "gg-mm hh:mm"
    $formattedDate = Carbon::parse($row->event_date)->format('d-m H:i');

    // Mapping delle descrizioni personalizzate
    $eventInfo = $row->event_info;

    if ($eventInfo == 'Interview Complete Cint') {
        $eventInfo = "Completa per Cint";
    } elseif (preg_match('/\((\d+),([A-Z0-9]+),([A-Z]+)\)/', $eventInfo, $matches)) {
        $eventInfo = "Completa per {$matches[2]}-{$matches[3]}";
    } elseif (str_contains($eventInfo, 'Buono Amazon')) {
        $eventInfo = "Richiesta Buono " . preg_replace('/[^0-9]/', '', $eventInfo) . " €";
    } elseif (str_contains($eventInfo, 'Ricarica Paypal')) {
        $eventInfo = "Richiesta Paypal " . preg_replace('/[^0-9]/', '', $eventInfo) . " €";
    } elseif ($eventInfo == 'New user has been created') {
        $eventInfo = "Utente registrato";
    } elseif ($eventInfo == 'User has been canceled') {
        $eventInfo = "Utente cancellato";
    } elseif (preg_match('/1, ([A-Z0-9]+), ([A-Z]+)/', $eventInfo, $matches)) {
        $eventInfo = "{$matches[1]}-{$matches[2]}";
    }

    // Aggiungiamo l'evento formattato
    $formattedActivities[] = [
        'date' => $formattedDate,
        'info' => $eventInfo
    ];
}

/**
 * 14) Divisione per cliente dei progetti eseguiti nel 2025
 */
$projectsByClient = DB::select("
    SELECT cliente, COUNT(*) AS total
    FROM t_panel_control
    WHERE YEAR(sur_date) = 2025
    GROUP BY cliente
");

$clientStats = [];
foreach ($projectsByClient as $row) {
    $clientStats[$row->cliente] = $row->total;
}


        // A questo punto abbiamo TUTTI i dati pronti come nel codice "vecchio".
        // Passiamo i dati alla vista index.blade.php tramite compact().
        return view('index', compact(
            'records',
            'oggi',
            'totalUsers',
            'activeUsers',
            'activePercentage',
            'totalMen',
            'totalWomen',
            'activeMen',
            'activeWomen',
            'activeMenPercentage',
            'activeWomenPercentage',
            'ageGroups',
            'areaGroups',
            'monthlyRegistrations',
            'monthlyActiveRegistrations',
            'monthlyCompleteMillebytes',
            'monthlyCompleteCint',
            'totalContacts',
            'avgRedPanel',
            'avgRedSurv',
            'formattedActivities',
            'clientStats',
            'monthlyOpenProjects',
            'currentYear',
            'activeUsersPerYear'
        ));
    }
}
