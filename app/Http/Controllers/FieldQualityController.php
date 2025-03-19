<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PrimisApiService; // Adatta al tuo namespace


class FieldQualityController extends Controller
{
    public function index(Request $request, PrimisApiService $primis)
    {
        // 1) Parametri GET
        $prj = $request->query('prj');
        $sid = $request->query('sid');

        // 2) Path file .sre
        $directory = base_path("var/imr/fields/$prj/$sid/results/");

        // 3) Punteggio di partenza
        $defaultScore = 6.0;

        // 4) Array delle interviste complete
        $completeInterviews = [];

        // 5) Per la seconda riga - colonna sinistra (LOI formattata)
        $loiData = [];

        // 6) Eventuali domande aperte
        $openQuestionsData = [];

        // 7) Lettura file .sre
        if (is_dir($directory)) {
            $files = glob($directory . "/*.sre");

            foreach ($files as $file) {
                $line = $this->readFirstLine($file);
                if (!$line) {
                    continue;
                }

                $data = explode(";", trim($line));

                // Determina offset
                $offset = (isset($data[0]) && $data[0] === '2.0') ? 0 : -1;

                // Status
                $statusIndex = 8 + $offset;
                $status = isset($data[$statusIndex]) ? (int)$data[$statusIndex] : null;

                // IID
                $iid = $data[3 + $offset] ?? 'N/A';

                // UID
                $uid = $data[4 + $offset] ?? 'N/A';

                // LOI sec
                $loiIndex = 7 + $offset;
                $loiSec = isset($data[$loiIndex]) ? (int)$data[$loiIndex] : 0;

                // Se Completa
                if ($status === 3) {
                    // Salviamo
                    $completeInterviews[] = [
                        'iid'    => $iid,
                        'uid'    => $uid,
                        'loiSec' => $loiSec,
                        'score'  => $defaultScore
                    ];

                    // Per la tabella LOI (in min.sec)
                    $minutes = floor($loiSec / 60);
                    $seconds = $loiSec % 60;
                    $loiFormatted = $minutes . '.' . str_pad($seconds, 2, '0', STR_PAD_LEFT);

                    $loiData[] = [
                        'iid' => $iid,
                        'uid' => $uid,
                        'loi' => $loiFormatted
                    ];

                    // *** LEGGIAMO TUTTE LE RIGHE open: ***
                    $this->extractOpenQuestions($file, $iid, $uid, $openQuestionsData);
                }
            }
        }

         // *** 1) Scarichiamo da Primis l'elenco domande e creiamo la questionMap ***
         $questionMap = $this->buildQuestionMap($primis, $prj, $sid);

         // *** 2) Completiamo i dati in $openQuestionsData con "codice" e "text" da questionMap ***
         $this->populateOpenQuestionsDetails($openQuestionsData, $questionMap);

         // *** 3) Ordiniamo $openQuestionsData per UID (e volendo per IID) ***
         usort($openQuestionsData, function($a, $b) {
             return $a['uid'] <=> $b['uid'] ?: $a['iid'] <=> $b['iid'];
         });

        // 8) Applichiamo Criterio LOI e otteniamo la LOI media (in secondi)
        $loiMediaSec = $this->applyLoiCriterion($completeInterviews);

        // 9) Convertiamo la LOI media in "minuti.secondi"
        $loiMediaFormatted = '0.00';
        if ($loiMediaSec > 0) {
            $minutes = floor($loiMediaSec / 60);
            $seconds = $loiMediaSec % 60;
            $loiMediaFormatted = $minutes . '.' . str_pad($seconds, 2, '0', STR_PAD_LEFT);
        }

        // 10) Calcolo statistiche punteggio
        $count = count($completeInterviews);
        $averageScore = 0;
        $maxScore = 0;
        $minScore = 0;

        if ($count > 0) {
            $scores = array_column($completeInterviews, 'score');
            $averageScore = round(array_sum($scores) / $count, 1);
            $maxScore = round(max($scores), 1);
            $minScore = round(min($scores), 1);
        }

        // 11) Ricerche in corso (navbar)
        $ricercheInCorso = DB::table('t_panel_control')
            ->where('stato', 0)
            ->orderBy('description', 'asc')
            ->get(['sur_id', 'description', 'prj']);

        // 12) Info base panel
        $panelData = DB::table('t_panel_control')->where('sur_id', $sid)->first();

        // 13) Return view
        return view('fieldQuality', [
            'prj' => $prj,
            'sid' => $sid,
            'panelData' => $panelData,
            'ricercheInCorso' => $ricercheInCorso,

            // Statistiche punteggio
            'averageScore' => $averageScore,
            'maxScore'     => $maxScore,
            'minScore'     => $minScore,

            // <-- Aggiungiamo la LOI media formattata
            'loiMediaFormatted' => $loiMediaFormatted,

            // Prima riga - destra
            'completeInterviews' => $completeInterviews,

            // Seconda riga - sinistra
            'loiData' => $loiData,

            // Seconda riga - destra
            'openQuestionsData' => $openQuestionsData,
        ]);
    }

   // *** FUNZIONE 1) Legge TUTTO il file .sre (oltre la prima linea) e cerca righe "open;...".
    // Aggiunge i dati grezzi in $openQuestionsData.
    // Ricordiamo: se la risposta open è SOLO numerica, la ignoriamo.
    private function extractOpenQuestions(string $filePath, string $iid, string $uid, array &$openQuestionsData): void
    {
        // Apriamo di nuovo il file per leggere *tutte* le righe
        // (la prima l'abbiamo già letta, ma la rileggeremo tranquillamente)
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return;
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            $fields = explode(";", $line);

            // Cerchiamo le righe che iniziano con "open"
            // Il primo campo deve essere "open"
            if (isset($fields[0]) && $fields[0] === 'open') {
                // open;idDomanda;Risposta
                $questionId   = $fields[1] ?? null;
                $openResponse = $fields[2] ?? '';

                // Se la risposta open è *solo* numerica => ignoriamo
                // Esempio di check: ctype_digit per int, is_numeric per decimali
                // Adegua se serve logica diversa
                if (is_numeric($openResponse)) {
                    continue;
                }

                // Salviamo in $openQuestionsData: non abbiamo ancora "codice" e "testo" => li gestiremo dopo
                $openQuestionsData[] = [
                    'iid'          => $iid,
                    'uid'          => $uid,
                    'questionId'   => (int)$questionId,
                    'openResponse' => $openResponse
                ];
            }
        }

        fclose($handle);
    }

    // *** FUNZIONE 2) Scarica da Primis la lista domande e crea la questionMap => [ id => [code, text] ]
    private function buildQuestionMap(PrimisApiService $primis, string $prj, string $sid): array
    {
        // Chiamiamo la rotta ->listQuestions($prj, $sid)
        $response = $primis->listQuestions($prj, $sid);

        if (!isset($response['questions']) || !is_array($response['questions'])) {
            // Nessuna domanda disponibile
            return [];
        }

        $questionMap = [];
        foreach ($response['questions'] as $q) {
            // $q['id'], $q['code'], $q['text']
            if (isset($q['id'])) {
                $id = (int)$q['id'];
                $questionMap[$id] = [
                    'code' => $q['code'] ?? 'N/A',
                    'text' => $q['text'] ?? 'No text'
                ];
            }
        }

        return $questionMap;
    }

    // *** FUNZIONE 3) Integra $openQuestionsData con i "codice" e "testo" presi dalla questionMap.
    private function populateOpenQuestionsDetails(array &$openData, array $questionMap): void
    {
        foreach ($openData as &$row) {
            $id = $row['questionId'];
            if (isset($questionMap[$id])) {
                $row['codice']  = $questionMap[$id]['code'];
                $row['tooltip'] = $questionMap[$id]['text']; // Per eventuale tooltip
            } else {
                // Domanda non trovata in questionMap
                $row['codice']  = 'unknown';
                $row['tooltip'] = 'Domanda non presente';
            }
        }
        unset($row);
    }

    // *** Criterio LOI (come prima)
    private function applyLoiCriterion(array &$interviews): float
    {
        // 1) LOI media su chi ha <2700s
        $loiEligible = [];
        foreach ($interviews as $iv) {
            if ($iv['loiSec'] > 0 && $iv['loiSec'] < 2700) {
                $loiEligible[] = $iv['loiSec'];
            }
        }

        $loiMediaSec = 0;
        if (count($loiEligible) > 0) {
            $loiMediaSec = array_sum($loiEligible) / count($loiEligible);
        }

        // 2) bonus/malus
        foreach ($interviews as &$iv) {
            if ($loiMediaSec <= 0) {
                continue;
            }
            $loiSingle = $iv['loiSec'];

            if ($loiSingle >= $loiMediaSec) {
                continue; // 0
            }

            $diff = ($loiMediaSec - $loiSingle) / $loiMediaSec;

            if ($diff >= 0.7) {
                $iv['score'] -= 0.8;
            } elseif ($diff >= 0.5) {
                $iv['score'] -= 0.5;
            } elseif ($diff >= 0.3) {
                $iv['score'] -= 0.3;
            } else {
                $iv['score'] += 0.4;
            }
        }
        unset($iv);

        return $loiMediaSec;
    }

    private function readFirstLine($filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return null;
        }
        $line = fgets($handle);
        fclose($handle);
        return $line;
    }
}
