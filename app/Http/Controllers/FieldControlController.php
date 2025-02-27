<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PanelControl;
use Illuminate\Support\Facades\DB;


class FieldControlController extends Controller
{
    public function index(Request $request)
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

        // Recupera il valore di bytes da t_panel_control
        $bytes = DB::table('t_panel_control')->where('sur_id', $sid)->value('bytes') ?? 0;

        // Aggiorna il database con i nuovi dati
        $this->updatePanelControl($sid, $counts, $abilitati, $panelCounts, $redemption, $bytes);

        //Conta filtrate per panel
        $filtrateCountsByPanel = $this->contaFiltrate($directory, $panelNames, $prj, $sid);


        return view('fieldControl', compact('prj', 'sid', 'panelData', 'counts', 'abilitati', 'redemption', 'panelCounts', 'filtrateCountsByPanel', 'quotaData'));
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


//  FUNZIONE PER CONTEGGIO FILTRATE

private function contaFiltrate($directory, $panelNames, $prj, $sid)
{


    $panelFiltrateCounts = [];

    if (is_dir($directory)) {
        $files = glob($directory . "/*.sre");

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

                    // Verifica se lo status è "4" (Non in target)
                    if (!isset($data[$statusIndex]) || (int)$data[$statusIndex] !== 4) {
                        continue;
                    }

                    // Determina il panel
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
                        $questionCode = $data[$lastCodeIndex];


                        // Otteniamo i dettagli della domanda
                        $questionDetails = $this->getQuestionDetails($prj, $sid, $questionCode);
                        $questionLabel = $questionDetails['code'] . " - " . $questionDetails['text'];


                        // Aggiunge il codice alla lista delle occorrenze per il panel specifico
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

    foreach ($panelFiltrateCounts as &$filtrateCounts) {
        arsort($filtrateCounts);
    }


    return $panelFiltrateCounts;
}


// LETTURA FILE SDL

private function getQuestionDetails($prj, $sid, $questionCode)
{
    // Percorso del file .sdl
    $sdlFile = base_path("var/imr/fields/$prj/$sid/$sid.sdl");

    // Controlliamo se il file esiste
    if (!file_exists($sdlFile)) {

        return ['text' => 'N/A', 'code' => 'N/A'];
    }


    // Variabili per memorizzare i dettagli della domanda
    $questionFound = false;
    $questionText = 'N/A';
    $questionCodeText = 'N/A';

    // Legge il file riga per riga
    $handle = fopen($sdlFile, "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);


            // Cerca la riga con "new question(...)" e verifica il codice della domanda
            if (preg_match('/new question\(".*?",\s*(\d+)\);/', $line, $matches)) {


                if ((int)$matches[1] === (int)$questionCode) {

                    $questionFound = true;
                    continue; // Procedi alla lettura delle righe successive
                }
            }

            // Se la domanda è stata trovata, cerchiamo il testo e il codice
            if ($questionFound) {
                if (strpos($line, 'qst.setProperty("text",') !== false) {
                    preg_match('/qst.setProperty\("text",\s*"(.*?)"\);/', $line, $matches);
                    if (isset($matches[1])) {
                        $questionText = $matches[1];

                    }
                }
                if (strpos($line, 'qst.setProperty("code",') !== false) {
                    preg_match('/qst.setProperty\("code",\s*"(.*?)"\);/', $line, $matches);
                    if (isset($matches[1])) {
                        $questionCodeText = $matches[1];

                    }
                    break; // Abbiamo trovato sia il testo che il codice, possiamo interrompere
                }
            }
        }
        fclose($handle);
    } else {

    }



    return ['text' => $questionText, 'code' => $questionCodeText];
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






}
