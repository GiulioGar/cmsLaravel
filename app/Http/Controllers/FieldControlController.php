<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PanelControl;
use Illuminate\Support\Facades\DB;
use App\Services\PrimisApiService; // ✅ Importa PrimisApiService


class FieldControlController extends Controller
{
    public function index(Request $request, PrimisApiService $primis)
    {
        $prj = $request->query('prj');
        $sid = $request->query('sid');

        // Recupera i dati del progetto
        $panelData = PanelControl::where('sur_id', $sid)->first();
        $quotaData = $this->getQuotaData($sid);

        // Percorso della directory dei file .sre
        $directory = base_path("var/imr/fields/$prj/$sid/results/");

        // Definizione dei panel
        $panelNames = [
            1 => 'Cint',
            2 => 'Dynata',
            3 => 'Bilendi',
            4 => 'Norstat',
            5 => 'Toluna',
            6 => 'Netquest',
            7 => 'CATI',
            8 => 'Makeopinion',
            9 => 'Altro Panel'
        ];

        // Inizializziamo le variabili per i conteggi totali
        $counts = [
            'complete' => 0,
            'non_target' => 0,
            'over_quota' => 0,
            'sospese' => 0,
            'bloccate' => 0,
            'contatti' => 0
        ];

        // Array per tenere traccia dei panel utilizzati
        $panelCounts = [];

        // Recupera il valore del campo "panel" da t_panel_control
        $panelValueFromDB = DB::table('t_panel_control')->where('sur_id', $sid)->value('panel');

        // Verifica se la directory esiste e cerca i file .sre
        if (is_dir($directory)) {
            $files = glob($directory . "/*.sre");

            if (!empty($files)) {
                $counts['contatti'] = count($files);

                foreach ($files as $file) {
                    $handle = fopen($file, "r");
                    if ($handle) {
                        $line = fgets($handle);
                        fclose($handle);

                        if ($line) {
                            $data = explode(";", trim($line));

                            // Determina la posizione della colonna "status"
                            $statusIndex = (isset($data[0]) && $data[0] == "2.0") ? 8 : 7;

                            // Default: Nessun panel assegnato
                            $panelUsed = null;

                            // Se "pan=" è presente nella riga del file .sre, assegna il panel corretto
                            foreach ($data as $element) {
                                if (strpos($element, "pan=") !== false) {
                                    $panelValue = (int) str_replace("pan=", "", $element);
                                    $panelUsed = $panelNames[$panelValue] ?? null;
                                    break;
                                }
                            }

                            // Se non troviamo "pan=" e panel != 1 nel database, NON conteggiamo questa intervista
                            if (!$panelUsed && $panelValueFromDB != 1) {
                                continue;
                            }

                            // Se il panel nel DB è 1 e non abbiamo trovato "pan=", assegniamo "Interactive"
                            if (!$panelUsed && $panelValueFromDB == 1) {
                                $panelUsed = 'Interactive';
                            }

                            // Se il panel è ancora NULL, saltiamo questa intervista
                            if (!$panelUsed) {
                                continue;
                            }

                            // Se il panel non esiste ancora nell'array, inizializzalo
                            if (!isset($panelCounts[$panelUsed])) {
                                $panelCounts[$panelUsed] = [
                                    'complete' => 0,
                                    'non_target' => 0,
                                    'over_quota' => 0,
                                    'sospese' => 0,
                                    'bloccate' => 0,
                                    'contatti' => 0,
                                    'redemption' => 0.0
                                ];
                            }

                            // Incrementa il numero di contatti per il panel
                            $panelCounts[$panelUsed]['contatti']++;


                            if (isset($data[$statusIndex])) {
                                $status = (int) $data[$statusIndex];

                                // Conta le interviste per status (totali e per panel)
                                switch ($status) {
                                    case 3:
                                        $counts['complete']++;
                                        $panelCounts[$panelUsed]['complete']++;
                                        break;
                                    case 4:
                                        $counts['non_target']++;
                                        $panelCounts[$panelUsed]['non_target']++;
                                        break;
                                    case 5:
                                        $counts['over_quota']++;
                                        $panelCounts[$panelUsed]['over_quota']++;
                                        break;
                                    case 0:
                                        $counts['sospese']++;
                                        $panelCounts[$panelUsed]['sospese']++;
                                        break;
                                    case 7:
                                        $counts['bloccate']++;
                                        $panelCounts[$panelUsed]['bloccate']++;
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Recupera gli abilitati solo se presenti in t_user_info
        $abilitati = DB::table('t_respint')
            ->where('sid', $sid)
            ->where('status', '!=', 6)
            ->whereIn('uid', function ($query) {
                $query->select('user_id')->from('t_user_info');
            })
            ->count();

        // Calcolo della Redemption (IR) totale
        $denominator = $counts['contatti'] - $counts['sospese'] - $counts['bloccate'] - $counts['over_quota'];
        $redemption = ($denominator > 0) ? round(($counts['complete'] / $denominator) * 100, 2) : 0;

        // Calcolo della Redemption (IR) per ogni Panel
        foreach ($panelCounts as $panelName => &$panel) {
            $panelDenominator = $panel['contatti'] - $panel['sospese'] - $panel['bloccate'] - $panel['over_quota'];

            if ($panelDenominator > 0) {
                $panel['redemption'] = round(($panel['complete'] / $panelDenominator) * 100, 2);
            } else {
                $panel['redemption'] = 0;
            }
        }
        unset($panel);

        // Calcoliamo gli utenti disponibili SOLO per il panel Interactive
        $utentiDisponibili = $this->getUtentiDisponibili($sid);

        // Calcoliamo la mediaRedPanel
        $mediaRedPanel = $this->calcolaMediaRedPanel();

        // Calcoliamo la stima interviste solo se il panel è Interactive
        $stimaInterviste = ($panelValueFromDB == 1)
            ? $this->calcolaStimaInterviste($utentiDisponibili, $redemption, $mediaRedPanel)
            : null;


        // Recupera il valore di bytes da t_panel_control
        $bytes = DB::table('t_panel_control')->where('sur_id', $sid)->value('bytes') ?? 0;

        // Aggiorna il database con i nuovi dati
        $this->updatePanelControl($sid, $counts, $abilitati, $panelCounts, $redemption, $bytes);

        //Conta filtrate per panel
        $filtrateCountsByPanel = $this->contaFiltrate($directory, $panelNames, $prj, $sid, $primis);


        //FUNZIONE LOG DATA
        $logData = $this->getLogData($directory, $primis, $prj, $sid);

        //dati per data
        $dataSummaryByPanel = $this->getDataSummaryByDate($directory, $panelNames);


        return view('fieldControl', compact('prj', 'sid', 'panelData', 'counts', 'abilitati', 'redemption', 'panelCounts', 'utentiDisponibili',
        'stimaInterviste', 'filtrateCountsByPanel', 'quotaData', 'logData', 'dataSummaryByPanel' ));
    }

    private function updatePanelControl($sid, $counts, $abilitati, $panelCounts, $redemption, $bytes)
    {
        $panelInteractiveComplete = $panelCounts['Interactive']['complete'] ?? 0;
        $panelExternalComplete = array_sum(array_column(array_diff_key($panelCounts, ['Interactive' => '']), 'complete'));
        $panelInteractiveContacts = $panelCounts['Interactive']['contatti'] ?? 0;
        $panelExternalContacts = array_sum(array_column(array_diff_key($panelCounts, ['Interactive' => '']), 'contatti'));

        $redPanel = ($abilitati > 0) ? round($counts['contatti'] / $abilitati, 2) : 0;
        $costo = ($bytes / 1000) * $panelInteractiveComplete;

        DB::table('t_panel_control')->where('sur_id', $sid)->update([
            'abilitati' => $abilitati,
            'contatti' => $counts['contatti'],
            'red_panel' => $redPanel * 100,
            'complete_int' => $panelInteractiveComplete,
            'complete_ext' => $panelExternalComplete,
            'complete' => $counts['complete'],
            'red_surv' => $redemption,
            'last_update' => now(),
            'costo' => $costo
        ]);
    }


//UTENTI DISPONIBILI
    private function getUtentiDisponibili($sid)
    {
        // Recupera il target del panel
        $panelTarget = DB::table('t_panel_control')->where('sur_id', $sid)->first();

        if (!$panelTarget) {
            return 0; // Nessun dato trovato
        }

        // Determina il filtro per il sesso
        $genderFilter = [1, 2]; // Default: entrambi i sessi
        switch ($panelTarget->sex_target) {
            case 1:
                $genderFilter = [1]; // Solo uomini
                break;
            case 2:
                $genderFilter = [2]; // Solo donne
                break;
            case 3:
                $genderFilter = [1, 2]; // Entrambi
                break;
        }

        // Determina il range di età
        $etaMin = $panelTarget->age1_target;
        $etaMax = $panelTarget->age2_target;
        $annoCorrente = date('Y');

        // Query per contare gli utenti disponibili
        $utentiDisponibili = DB::table('t_user_info')
            ->whereIn('gender', $genderFilter)
            ->whereRaw("YEAR(birth_date) BETWEEN ? AND ?", [$annoCorrente - $etaMax, $annoCorrente - $etaMin])
            ->where('active', 1)
            ->where('confirm', 1)
            ->whereNotExists(function ($query) use ($sid) {
                $query->select(DB::raw(1))
                      ->from('t_respint')
                      ->whereRaw('t_respint.uid = t_user_info.user_id')
                      ->where('t_respint.sid', $sid);
            })
            ->count();

        return $utentiDisponibili;
    }


// CALCOLO MEDIA REDEMPTION PANEL
private function calcolaMediaRedPanel()
{
    $dueAnniFa = now()->subYears(2);

    // Query base per ottenere il dataset
    $query = DB::table('t_panel_control')
        ->where('panel', 1)
        ->whereBetween('red_panel', [7, 29])
        ->where('sur_date', '>=', $dueAnniFa);

    // Conta il numero di record che rispettano i criteri
    $countRecords = $query->count();

    // Media di red_panel rispettando i filtri
    $mediaRedPanel = $query->avg('red_panel');


    return $mediaRedPanel ?? 0; // Se non ci sono dati restituisce 0
}


//CALCOLO STIMA INTERVISTE POSSIBILI
private function calcolaStimaInterviste($utentiDisponibili, $redSurv, $mediaRedPanel)
{
    $percentualeRedSurv = $redSurv / 100;
    $percentualeMediaRedPanel = $mediaRedPanel / 100;

    // Prima riduzione: Applichiamo RedSurv sugli utenti disponibili
    $step1 = $utentiDisponibili * $percentualeRedSurv;

    // Seconda riduzione: Applichiamo MediaRedPanel sul valore ottenuto
    $stimaInterviste = $step1 * $percentualeMediaRedPanel;

    // Log per debugging

    return max(0, round($stimaInterviste)); // Assicuriamoci che non sia negativo
}


//  FUNZIONE PER CONTEGGIO FILTRATE
private function contaFiltrate($directory, $panelNames, $prj, $sid, PrimisApiService $primis)
{
    $panelFiltrateCounts = [];

    if (is_dir($directory)) {
        $files = glob($directory . "/*.sre");

        // ✅ Otteniamo tutte le domande in un'unica chiamata API
        $response = $primis->listQuestions($prj, $sid);


        // ✅ Verifica se la risposta contiene la chiave "questions"
        if (!isset($response['questions']) || !is_array($response['questions'])) {
            return [];
        }

        // ✅ Recuperiamo solo la lista di domande
        $allQuestions = $response['questions'];

        // ✅ Creiamo una mappa delle domande per accesso rapido
        $questionMap = [];
        foreach ($allQuestions as $question) {
            if (isset($question['id'])) {
                $questionMap[$question['id']] = [
                    'code' => $question['code'] ?? "Codice Sconosciuto",
                    'text' => $question['text'] ?? "Testo non disponibile"
                ];
            }
        }

        foreach ($files as $file) {
            $handle = fopen($file, "r");
            if ($handle) {
                $line = fgets($handle);
                fclose($handle);

                if ($line) {
                    $data = explode(";", trim($line));

                    // Determina la posizione della colonna "status" e della domanda filtrata
                    $statusIndex = (isset($data[0]) && $data[0] == "2.0") ? 8 : 7;
                    $lastCodeIndex = $statusIndex + 1;

                    // ✅ Verifica se lo status è "4" (Non in target)
                    if (!isset($data[$statusIndex]) || (int)$data[$statusIndex] !== 4) {
                        continue;
                    }

                    // ✅ Determina il panel
                    $panelUsed = 'Interactive';
                    foreach ($data as $element) {
                        if (strpos($element, "pan=") !== false) {
                            $panelValue = (int) str_replace("pan=", "", $element);
                            $panelUsed = $panelNames[$panelValue] ?? 'Altro Panel';
                            break;
                        }
                    }

                    if (!isset($panelFiltrateCounts[$panelUsed])) {
                        $panelFiltrateCounts[$panelUsed] = [];
                    }

                    if (isset($data[$lastCodeIndex])) {
                        $questionId = (int) $data[$lastCodeIndex];

                        // ✅ Verifica se la domanda è presente nella mappa
                        $questionDetails = $questionMap[$questionId] ?? ['code' => "N/A", 'text' => "Domanda non trovata"];

                        // ✅ Creiamo l'etichetta della domanda
                        $questionLabel = "{$questionDetails['code']} - {$questionDetails['text']}";

                        // ✅ Aggiungiamo l'occorrenza
                        if (!isset($panelFiltrateCounts[$panelUsed][$questionLabel])) {
                            $panelFiltrateCounts[$panelUsed][$questionLabel] = 1;
                        } else {
                            $panelFiltrateCounts[$panelUsed][$questionLabel]++;
                        }
                    }
                }
            }
        }
    }

    // ✅ Ordinamento decrescente delle occorrenze
    foreach ($panelFiltrateCounts as &$filtrateCounts) {
        arsort($filtrateCounts);
    }

    return $panelFiltrateCounts;
}





private function getQuestionDetails(PrimisApiService $primis, $prj, $sid, $questionId)
{
    try {
        // Otteniamo la lista di domande da Primis
        $questions = $primis->listQuestions($prj, $sid);

        // Cerchiamo la domanda corrispondente
        foreach ($questions as $question) {
            if ($question['id'] == $questionId) {
                return [
                    'code' => $question['code'] ?? "Codice Sconosciuto",
                    'text' => $question['text'] ?? "Testo non disponibile"
                ];
            }
        }
    } catch (\Exception $e) {
        return [
            'code' => "Errore",
            'text' => "Errore nel recupero della domanda"
        ];
    }

    // Se non troviamo nulla, restituiamo valori di default
    return [
        'code' => "N/A",
        'text' => "Domanda non trovata"
    ];
}




// FUNZIONE PER CONTROLLARE LA TABELLA QUOTE

private function getQuotaData($sid)
{
    return DB::table('t_quota_status')
        ->where('survey_id', $sid)
        ->orderBy('id', 'asc') // Ordina per ID crescente
        ->select('target_name as quota', 'target_value as totale', 'current_value as entrate')
        ->get()
        ->map(function ($item) {
            $item->missing = max(0, $item->totale - $item->entrate);
            $item->quota = $this->formatQuotaName($item->quota);
            return $item;
        });
}

private function formatQuotaName($quotaName)
{
    if ($quotaName === 'source_panel') {
        return 'Totale Panel Esterno';
    }

    if (strpos($quotaName, 'total_interviews') === 0) {
        return ($quotaName === 'total_interviews') ? 'Interviste Totali' : 'Totale Cella ' . str_replace('total_interviews_', '', $quotaName);
    }

    $parts = explode('_', $quotaName);

    if (count($parts) == 2) {
        return ucfirst($parts[0]) . ' - Risposta ' . ((int)$parts[1] + 1);
    } elseif (count($parts) == 3) {
        return ucfirst($parts[0]) . ' - Risposta ' . ((int)$parts[1] + 1) . ' - Cella ' . $parts[2];
    }

    return ucfirst($quotaName);
}


//LOG DATA

private function getLogData($directory, PrimisApiService $primis, $projectName, $surveyId)
{
    $logData = [];

    if (is_dir($directory)) {
        $files = glob($directory . "/*.sre");

        // Ordiniamo i file in ordine decrescente (dal più recente al più vecchio)
        rsort($files);

        // ✅ Otteniamo tutte le domande in un'unica chiamata API
        $response = $primis->listQuestions($projectName, $surveyId);

        // ✅ Log della risposta API per debugging


        // ✅ Verifica se la risposta contiene la chiave "questions"
        if (!isset($response['questions']) || !is_array($response['questions'])) {

            return [];
        }

        // ✅ Recuperiamo solo la lista di domande
        $allQuestions = $response['questions'];

        // ✅ Creiamo una mappa delle domande per accesso rapido
        $questionMap = [];
        foreach ($allQuestions as $question) {
            if (isset($question['id'])) {
                $questionMap[$question['id']] = [
                    'code' => $question['code'] ?? "Codice Sconosciuto",
                    'text' => $question['text'] ?? "Testo non disponibile"
                ];
            }
        }

        foreach ($files as $file) {
            $handle = fopen($file, "r");
            if ($handle) {
                $line = fgets($handle);
                fclose($handle);

                if ($line) {
                    $data = explode(";", trim($line));

                    // Determina l'offset se la prima colonna (versione 2.0) è presente
                    $offset = (isset($data[0]) && $data[0] == "2.0") ? 0 : -1;

                    // Estrapoliamo i dati richiesti
                    $iid = $this->extractValue($data, 3 + $offset);
                    $uid = $this->extractValue($data, 4 + $offset);
                    $ultimoUpdate = $this->extractValue($data, 6 + $offset);
                    $questionId = (int) $this->extractValue($data, 9 + $offset); // Codice ultima domanda
                    $statusCode = (int) $this->extractValue($data, 8 + $offset);

                    // ✅ Verifica se la domanda è presente nella mappa
                    $questionDetails = $questionMap[$questionId] ?? ['code' => "N/A", 'text' => "Domanda non trovata"];

                    // Mappatura stato intervista
                    $statusMap = [
                        0 => "In Corso",
                        3 => "Completa",
                        4 => "Non in target",
                        5 => "Quotafull",
                        7 => "Bloccata"
                    ];
                    $stato = $statusMap[$statusCode] ?? "Sconosciuto";

                    // Durata convertita in secondi/minuti
                    $durataSec = (int) $this->extractValue($data, 7 + $offset);
                    $durata = $this->formatDuration($durataSec);

                    // Aggiungiamo i dati estratti all'array finale
                    $logData[] = [
                        'iid' => $iid,
                        'uid' => $uid,
                        'ultimo_update' => $ultimoUpdate,
                        'ultima_azione' => "<span data-bs-toggle='tooltip' title='{$questionDetails['text']}'>{$questionDetails['code']}</span>",
                        'stato' => $stato,
                        'durata' => $durata
                    ];
                }
            }
        }
    }

    return $logData;
}





// Funzione di supporto per evitare errori se il dato non esiste
private function extractValue($data, $index)
{
    return $data[$index] ?? 'N/A';
}

// Funzione per formattare la durata da ms a minuti e secondi
private function formatDuration($seconds)
{
    if ($seconds < 60) {
        return "{$seconds} sec.";
    } else {
        $minutes = round($seconds / 60, 1); // Converti in minuti con 1 decimale
        return "{$minutes} min.";
    }
}




private function getDataSummaryByDate($directory, $panelNames)
{
    $dataSummaryByPanel = [];

    if (is_dir($directory)) {
        $files = glob($directory . "/*.sre");

        foreach ($files as $file) {
            $handle = fopen($file, "r");
            if ($handle) {
                $line = fgets($handle);
                fclose($handle);

                if ($line) {
                    $data = explode(";", trim($line));

                    // Determina l'offset per la versione 2.0
                    $offset = (isset($data[0]) && $data[0] == "2.0") ? 0 : -1;

                    // Data intervista
                    $interviewDate = $this->extractValue($data, 5 + $offset);
                    if ($interviewDate === "N/A" || empty($interviewDate)) {
                        continue;
                    }

                    // Convertiamo la data al formato YYYY-MM-DD per l'ordinamento
                    $formattedDate = $this->formatDate($interviewDate);

                    // Determina il panel
                    $panelUsed = 'Interactive';
                    foreach ($data as $element) {
                        if (strpos($element, "pan=") !== false) {
                            $panelValue = (int) str_replace("pan=", "", $element);
                            $panelUsed = $panelNames[$panelValue] ?? 'Altro Panel';
                            break;
                        }
                    }

                    // Se il panel non esiste ancora nell'array, inizializziamolo
                    if (!isset($dataSummaryByPanel[$panelUsed])) {
                        $dataSummaryByPanel[$panelUsed] = [];
                    }

                    // Se la data non è ancora presente nell'array del panel, inizializziamola
                    if (!isset($dataSummaryByPanel[$panelUsed][$formattedDate])) {
                        $dataSummaryByPanel[$panelUsed][$formattedDate] = [
                            'contatti' => 0,
                            'complete' => 0,
                            'non_target' => 0,
                            'quotafull' => 0,
                            'total_duration' => 0 // Per calcolare la media LOI
                        ];
                    }

                    // Aumentiamo il conteggio dei contatti per la data
                    $dataSummaryByPanel[$panelUsed][$formattedDate]['contatti']++;

                    // Determiniamo lo status dell'intervista
                    $statusIndex = (isset($data[0]) && $data[0] == "2.0") ? 8 : 7;
                    $status = (int) $this->extractValue($data, $statusIndex);

                    // Calcoliamo la durata (se l'intervista è completata)
                    $durationIndex = $statusIndex - 1; // La durata è nella colonna precedente allo status
                    $duration = (int) $this->extractValue($data, $durationIndex);

                    // Aggiorniamo i conteggi in base allo status
                    switch ($status) {
                        case 3:
                            $dataSummaryByPanel[$panelUsed][$formattedDate]['complete']++;
                            $dataSummaryByPanel[$panelUsed][$formattedDate]['total_duration'] += $duration;
                            break;
                        case 4:
                            $dataSummaryByPanel[$panelUsed][$formattedDate]['non_target']++;
                            break;
                        case 5:
                            $dataSummaryByPanel[$panelUsed][$formattedDate]['quotafull']++;
                            break;
                    }
                }
            }
        }
    }

    // Ordiniamo le date in ordine decrescente per ogni panel
    foreach ($dataSummaryByPanel as &$summary) {
        krsort($summary);
    }

    return $dataSummaryByPanel;
}


private function formatDate($dateString)
{
    // Ensure it's a valid date before parsing
    if (!strtotime($dateString)) {
        return "Data non disponibile";
    }

    return \Carbon\Carbon::parse($dateString)
        ->locale('it')
        ->isoFormat('dddd D MMMM YY');
}







}
