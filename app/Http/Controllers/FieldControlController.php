<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;
use App\Models\PanelControl;
use Illuminate\Support\Facades\DB;
use App\Services\PrimisApiService; // ✅ Importa PrimisApiService
use Symfony\Component\HttpFoundation\StreamedResponse;


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

        //ricerche in corso
        $ricercheInCorso = DB::table('t_panel_control')
        ->where('stato', 0)
        ->orderBy('description', 'asc')
        ->get(['sur_id', 'description', 'prj']); // Recuperiamo anche 'prj'



        return view('fieldControl', compact('prj', 'sid', 'panelData', 'counts', 'abilitati', 'redemption', 'panelCounts', 'utentiDisponibili',
        'stimaInterviste', 'filtrateCountsByPanel', 'quotaData', 'logData', 'dataSummaryByPanel','ricercheInCorso' ));
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

                    // Data intervista (es. "25/02/2025 17:01:52 CET")
                    $interviewDate = $this->extractValue($data, 5 + $offset);
                    if ($interviewDate === "N/A" || empty($interviewDate)) {
                        continue;
                    }

                    // Converto la data in oggetto Carbon (rimuovendo CET e usando formato d/m/Y H:i:s)
                    $carbon = $this->parseDateToCarbon($interviewDate);
                    if (!$carbon) {
                        // Se parsing fallisce, saltiamo questa riga
                        continue;
                    }

                    // Chiave di raggruppamento (formato YYYY-MM-DD)
                    $dayKey = $carbon->format('Y-m-d');
                    // Data “umana” da visualizzare in Blade
                    $displayDate = $carbon->locale('it')->isoFormat('dddd D MMMM YYYY');

                    // Determina il panel (se presente "pan=")
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

                    // Se il giorno non è ancora presente nell'array del panel, inizializziamolo
                    if (!isset($dataSummaryByPanel[$panelUsed][$dayKey])) {
                        $dataSummaryByPanel[$panelUsed][$dayKey] = [
                            // Salviamo la data formattata per la vista
                            'display_date' => $displayDate,
                            'contatti'      => 0,
                            'complete'      => 0,
                            'non_target'    => 0,
                            'quotafull'     => 0,
                            'bloccate'     => 0,
                            'total_duration'=> 0 // Per calcolare la media LOI
                        ];
                    }

                    // Aumentiamo il conteggio dei contatti per la data
                    $dataSummaryByPanel[$panelUsed][$dayKey]['contatti']++;

                    // Determiniamo lo status dell'intervista
                    $statusIndex = (isset($data[0]) && $data[0] == "2.0") ? 8 : 7;
                    $status = (int) $this->extractValue($data, $statusIndex);

                    // Calcoliamo la durata (se l'intervista è completata)
                    $durationIndex = $statusIndex - 1;
                    $duration = (int) $this->extractValue($data, $durationIndex);

                    // Aggiorniamo i conteggi in base allo status
                    switch ($status) {
                        case 3:
                            // Completa
                            $dataSummaryByPanel[$panelUsed][$dayKey]['complete']++;
                           $dataSummaryByPanel[$panelUsed][$dayKey]['total_duration'] += $duration;
                            break;
                        case 4:
                            // Non in target
                            $dataSummaryByPanel[$panelUsed][$dayKey]['non_target']++;
                            break;
                        case 5:
                            // Quota full
                            $dataSummaryByPanel[$panelUsed][$dayKey]['quotafull']++;
                            break;

                            case 7:
                        // Quota full
                         $dataSummaryByPanel[$panelUsed][$dayKey]['bloccate']++;
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

/**
 * Converte una data di tipo "gg/mm/aaaa hh:mm:ss CET" in un oggetto Carbon
 * (rimuovendo " CET" se presente). Restituisce null se il parsing fallisce.
 */
private function parseDateToCarbon($rawDate)
{
    // Rimuoviamo la parte " CET"
    $cleanDate = str_replace(' CET', '', trim($rawDate));

    try {
        // Esempio: "d/m/Y H:i:s", "25/02/2025 17:01:52"
        $carbon = Carbon::createFromFormat('d/m/Y H:i:s', $cleanDate, 'Europe/Rome');
        return $carbon;
    } catch (\Exception $e) {
        Log::warning("parseDateToCarbon() - Impossibile parsare la data: [{$rawDate}], errore: {$e->getMessage()}");
        return null;
    }
}



private function formatDate($dateString)
{
    Log::info(">>> ENTRATO in formatDate() con data: [{$dateString}]");

    // Rimuovo spazi vuoti iniziali/finali
    $dateString = trim($dateString);

    // Loggo il valore dopo trim
    Log::debug("Tentativo di formattare la data dopo trim: [{$dateString}]");

    // Rimuovo la parte " CET" perché non viene parsata da createFromFormat() in molte configurazioni
    $cleanString = str_replace(' CET', '', $dateString);

    // Loggo il valore dopo aver tolto CET
    Log::debug("Data dopo rimozione 'CET': [{$cleanString}]");

    try {
        Log::debug("Provo createFromFormat('d/m/Y H:i:s', {$cleanString}, 'Europe/Rome')");
        $carbonDate = Carbon::createFromFormat('d/m/Y H:i:s', $cleanString, 'Europe/Rome');

        // Se siamo qui, vuol dire che non c'è stata eccezione
        Log::debug("Parsing avvenuto con successo, Carbon date => " . $carbonDate->toDateTimeString());

        // Formatto in italiano
        $formatted = $carbonDate->locale('it')->isoFormat('dddd D MMMM YYYY HH:mm');

        Log::debug("Data formattata final: [{$formatted}]");

        return $formatted;
    } catch (\Exception $e) {
        // Se finiamo qui, il parsing è fallito
        Log::warning("Parsing fallito per data: [{$cleanString}], errore: " . $e->getMessage());
        return "Data non disponibile";
    }
}

/* download csv file */

public function downloadCSV(Request $request)
{
    $prj = $request->query('prj');
    $sid = $request->query('sid');
    $panelName = $request->query('panel');

    if (!$panelName) {
        return redirect()->back()->with('error', 'Seleziona un panel per scaricare il file.');
    }

    // Percorso della directory dei file .sre
    $directory = base_path("var/imr/fields/$prj/$sid/results/");

    if (!is_dir($directory)) {
        return redirect()->back()->with('error', 'Directory dei file .sre non trovata.');
    }

    $files = glob($directory . "/*.sre");
    if (empty($files)) {
        return redirect()->back()->with('error', 'Nessun file .sre trovato.');
    }

    // Lista dei panel (presa da index())
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

    // Mappatura dei campi per ogni panel
    $panelMapping = [
        'Cint' => 'rid',
        'Dynata' => 'psid',
        'Bilendi' => 'p',
        'Norstat' => 'sysUID',
        'Toluna' => 'sysUID',
        'Netquest' => 'ticket',
        'CATI' => 'sysUID',
        'Makeopinion' => 'session_id',
        'Altro Panel' => 'sysUID',
        'Interactive' => 'sysUID' // Interactive usa sysUID
    ];

    // Determiniamo il nome del campo specifico per il panel selezionato
    $varPanelField = $panelMapping[$panelName] ?? 'sysUID';

    // Nome del file CSV
    $fileName = "download_{$panelName}.csv";

    // Creazione dello StreamedResponse per generare il CSV in tempo reale
    $response = new StreamedResponse(function () use ($files, $varPanelField, $prj, $sid, $panelName, $panelNames) {
        $handle = fopen('php://output', 'w');

        // Intestazione CSV con il nome corretto per il campo panel
        fputcsv($handle, ['uid', $varPanelField, 'statusCode', 'Status', 'link'], ';');

        foreach ($files as $file) {
            $handleFile = fopen($file, "r");
            if ($handleFile) {
                $line = fgets($handleFile);
                fclose($handleFile);

                if ($line) {
                    $data = explode(";", trim($line));

                    // Determina la posizione della colonna status
                    $statusIndex = (isset($data[0]) && $data[0] == "2.0") ? 8 : 7;
                    $statusCode = isset($data[$statusIndex]) ? (int) $data[$statusIndex] : null;

                    // Ricodifica dello Status
                    $statusMap = [
                        0 => 'suspended',
                        3 => 'complete',
                        4 => 'screenout',
                        5 => 'quotafull',
                        7 => 'badQuality'
                    ];
                    $statusLabel = $statusMap[$statusCode] ?? 'unknown';

                    // Recupero del codice panel dal file .sre (se pan= non è presente, è Interactive)
                    $panelUsed = 'Interactive'; // Default Interactive se non trova "pan="
                    foreach ($data as $element) {
                        if (strpos($element, "pan=") !== false) {
                            $panelValue = (int) str_replace("pan=", "", $element);
                            $panelUsed = $panelNames[$panelValue] ?? 'Unknown';
                            break;
                        }
                    }

                    // Log per debugging
                    Log::debug("📂 File analizzato: $file");
                    Log::debug("📝 Panel rilevato: $panelUsed | Panel selezionato: $panelName");

                    // Filtriamo solo i file che appartengono al panel selezionato
                    if ($panelUsed !== $panelName) {
                        Log::info("❌ Saltato file perché il panel non corrisponde: $panelUsed");
                        continue;
                    }

                    // Recupero sysUID e varPanelField
                    $uid = $this->extractFieldValue($data, 'sysUID');
                    $varPanelValue = $this->extractFieldValue($data, $varPanelField);

                // Troviamo il codice numerico del panel selezionato
                $panelCode = array_search($panelName, $panelNames) ?: 9; // Default 9 se non trovato

                // Costruzione del link senza &pan se il panel è Interactive
                $link = ($panelName === 'Interactive')
                    ? "https://www.primisoft.com/primis/run.do?sid=$sid&prj=$prj&uid=$uid"
                    : "https://www.primisoft.com/primis/run.do?sid=$sid&prj=$prj&uid=$uid&pan=$panelCode";


                    // Scriviamo la riga nel CSV
                    fputcsv($handle, [$uid, $varPanelValue, $statusCode, $statusLabel, $link], ';');
                }
            }
        }

        fclose($handle);
    });

    // Impostiamo le intestazioni della risposta per il download del file
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

    return $response;
}


/**
 * Estrae il valore di un campo specifico dai dati del file .sre, cercando dalla fine
 */
private function extractFieldValue($data, $fieldKey)
{
    Log::debug("🔍 Cerco il valore per il campo: $fieldKey");

    // Log del contenuto dell'array estratto dalla riga del file
    Log::debug("📄 Contenuto della riga estratta: " . json_encode($data));

    // Scorriamo il file .sre dalla fine per trovare il valore corretto
    for ($i = count($data) - 1; $i >= 0; $i--) {
        if (strpos($data[$i], "$fieldKey=") !== false) {
            $value = str_replace("$fieldKey=", "", $data[$i]);

            Log::debug("✅ Trovato valore per '$fieldKey': $value");

            return $value;
        }
    }

    Log::warning("⚠️ Valore '$fieldKey' non trovato nel file .sre");

    return 'N/A';
}


public function closeSurvey(Request $request)
{
    $prj = $request->input('prj');
    $sid = $request->input('sid');

    // Troviamo la ricerca corrispondente
    $survey = DB::table('t_panel_control')->where('prj', $prj)->where('sur_id', $sid)->first();

    if (!$survey) {
        return response()->json(['success' => false, 'message' => 'Ricerca non trovata.'], 404);
    }

    if ($survey->stato == 1) {
        return response()->json(['success' => false, 'message' => 'La ricerca è già chiusa.'], 400);
    }

    // Aggiorniamo lo stato a 1 (chiuso)
    DB::table('t_panel_control')
        ->where('prj', $prj)
        ->where('sur_id', $sid)
        ->update(['stato' => 1]);

    return response()->json(['success' => true]);
}

public function resetBloccate(Request $request)
{
    $prj = $request->input('prj');
    $sid = $request->input('sid');

    // Percorso della directory dei file .sre
    $directory = base_path("var/imr/fields/$prj/$sid/results/");

    if (!is_dir($directory)) {
        return response()->json(['success' => false, 'message' => 'Directory non trovata.'], 404);
    }

    $files = glob($directory . "/*.sre");
    if (empty($files)) {
        return response()->json(['success' => false, 'message' => 'Nessun file .sre trovato.'], 404);
    }

    $resetCount = 0;

    foreach ($files as $file) {
        $handle = fopen($file, "r");
        if ($handle) {
            $line = fgets($handle);
            fclose($handle);

            if ($line) {
                $data = explode(";", trim($line));

                // Determina la posizione della colonna status
                $statusIndex = (isset($data[0]) && $data[0] == "2.0") ? 8 : 7;
                $statusCode = isset($data[$statusIndex]) ? (int) $data[$statusIndex] : null;

                // Se lo status è 7 (bloccato), eliminiamo il file e aggiorniamo il DB
                if ($statusCode === 7) {
                    // Recuperiamo l'uid per aggiornare il database
                    $uid = $this->extractFieldValue($data, 'sysUID');

                    // Eliminazione del file
                    unlink($file);
                    $resetCount++;

                    // Aggiorniamo la tabella t_respint
                    DB::table('t_respint')
                        ->where('sid', $sid)
                        ->where('uid', $uid)
                        ->update(['status' => 0, 'iid' => -1]);
                }
            }
        }
    }

    return response()->json([
        'success' => true,
        'resetCount' => $resetCount
    ]);
}



}
