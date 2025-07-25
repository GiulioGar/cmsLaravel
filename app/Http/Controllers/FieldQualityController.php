<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PrimisApiService; // Adatta al tuo namespace
use Illuminate\Support\Facades\Log; // In testa al file, se non c’è già


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

        // 6b) Nuovo array per le scale
      $scaleData = []; // Qui memorizzeremo la varianza delle scale singole

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

                $panelUsed = $this->detectPanel($data, /* se necessario $dbPanelValue */ );

                // Se Completa
                if ($status === 3) {
                    // Salviamo
                    $completeInterviews[] = [
                        'iid'    => $iid,
                        'uid'    => $uid,
                        'panel'  => $panelUsed,
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
                    $this->extractOpenQuestions($file, $iid, $uid,$panelUsed,$openQuestionsData);

                    // Scale: estraiamo in un pass dedicato
                    $this->extractScaleData($file, $iid, $uid, $panelUsed, $scaleData);
                }
            }
        }

         // *** 1) Scarichiamo da Primis l'elenco domande e creiamo la questionMap ***
         $questionMap = $this->buildQuestionMap($primis, $prj, $sid);
         $apiResponse = $primis->listQuestions($prj, $sid);
         $questionsFromApi = $apiResponse['questions'] ?? [];

         // *** 2) Completiamo i dati in $openQuestionsData con "codice" e "text" da questionMap ***
         $this->populateOpenQuestionsDetails($openQuestionsData, $questionMap);
         $this->populateScaleQuestionsDetails($scaleData, $questionMap);

         // *** 3) Ordiniamo $openQuestionsData per UID (e volendo per IID) ***
         usort($openQuestionsData, function($a, $b) {
            // 1) Prima ordiniamo per isFake = true => in alto
            //    Possiamo trattare true come 1, false come 0, e vogliamo i "1" PRIMA
            //    Quindi ordiniamo in modo DECRESCENTE su isFake
            $aFake = $a['isFake'] ? 1 : 0;
            $bFake = $b['isFake'] ? 1 : 0;
            if ($aFake !== $bFake) {
                // Ritorna bFake - aFake per avere "true" prima di "false"
                // (se vuoi false in alto, inverti)
                return $bFake - $aFake;
            }

            // 2) Se entrambi hanno lo stesso isFake,
            //    prosegui con l'ordinamento secondario (uid, poi iid).
            if ($a['uid'] !== $b['uid']) {
                return $a['uid'] <=> $b['uid'];
            }
            return $a['iid'] <=> $b['iid'];
        });

        // 8a) Applichiamo Criterio LOI e otteniamo la LOI media (in secondi)
        $loiMediaSec = $this->applyLoiCriterion($completeInterviews);

        // 8b) Applichiamo Criterio "Domande Aperte" (fake = -0.7, non fake = +0.2)
        $this->applyOpenQuestionsCriterion($completeInterviews, $openQuestionsData);

        //// 8c) Criterio Scale Changes
        $this->applyScaleChangesCriterion($completeInterviews, $scaleData);

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

        //stat generali

        // 1) Numero totale di interviste
        $totalInterviews = count($completeInterviews);

        // 2) Classificazione per fascia di punteggio
        $highCount = collect($completeInterviews)->filter(fn($iv) => $iv['score'] >= 6.4)->count();
        $acceptCount = collect($completeInterviews)->filter(fn($iv) => $iv['score'] >= 5 && $iv['score'] < 6.4)->count();
        $lowCount = $totalInterviews - $highCount - $acceptCount;

        // 3) Percentuali (evita divisione per zero)
        if ($totalInterviews > 0) {
            $pctHigh = round($highCount / $totalInterviews * 100);
            $pctAccept = round($acceptCount / $totalInterviews * 100);
            $pctLow = 100 - $pctHigh - $pctAccept;
        } else {
            $pctHigh = $pctAccept = $pctLow = 0;
        }

        // 4) Passa i nuovi valori alla view
        $dataExtras = [
            'totalInterviews' => $totalInterviews,
            'pctHigh'         => $pctHigh,
            'pctAccept'       => $pctAccept,
            'pctLow'          => $pctLow,
        ];



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

             // Terza riga - "Quality Scale"
             'scaleData' => $scaleData,
             'questionsFromApi' => $questionsFromApi,
        ],$dataExtras);

    }

   // *** FUNZIONE 1) Legge TUTTO il file .sre (oltre la prima linea) e cerca righe "open;...".
    // Aggiunge i dati grezzi in $openQuestionsData.
    // Ricordiamo: se la risposta open è SOLO numerica, la ignoriamo.
    private function extractOpenQuestions(string $filePath, string $iid, string $uid,string $panelUsed, array &$openQuestionsData): void
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
                    'panel'        => $panelUsed,
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
        // Supponendo di avere whiteList e blackList da JSON
        $whiteList = $this->loadWhiteList();
        $blackList = $this->loadBlackList();

        foreach ($openData as &$row) {
            // Codice e tooltip
            $id = $row['questionId'];
            if (isset($questionMap[$id])) {
                $row['codice']  = $questionMap[$id]['code'];
                $row['tooltip'] = $questionMap[$id]['text'];
            } else {
                $row['codice']  = 'unknown';
                $row['tooltip'] = 'Domanda non presente';
            }

            // *** Check se fake ***
            $row['isFake'] = $this->isSuspiciousResponse($row['openResponse'], $whiteList, $blackList);
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

    /**
 * Applica un bonus/malus basato sulle domande aperte:
 * - Se la domanda è FAKE => -0.7
 * - Se la domanda NON è FAKE => +0.2
 *
 * Nota: Un'intervista può avere più domande aperte. Ciascuna incide cumulativamente.
 */
private function applyOpenQuestionsCriterion(array &$completeInterviews, array $openQuestionsData): void
{
    // Creiamo una mappa veloce di interview (chiave = iid)
    // così possiamo aggiornare il punteggio senza dover ciclare ogni volta
    $indexedInterviews = [];
    foreach ($completeInterviews as &$iv) {
        $indexedInterviews[$iv['iid']] = &$iv;
    }
    unset($iv); // buona pratica quando si usa & (reference)

    // Ora iteriamo su tutte le "open" e aggiorniamo il punteggio
    foreach ($openQuestionsData as $open) {
        $iid = $open['iid'] ?? null;
        if (!$iid) {
            continue; // se manca iid, saltiamo
        }

        // Cerchiamo l'intervista corrispondente
        if (!isset($indexedInterviews[$iid])) {
            continue; // se non c'è match, skip
        }

        // Se "isFake" = true => -0.7, altrimenti => +0.2
        if (!empty($open['isFake']) && $open['isFake'] === true) {
            $indexedInterviews[$iid]['score'] -= 0.7;
        } else {
            $indexedInterviews[$iid]['score'] += 0.2;
        }
    }
}

/**
 * Applica bonus/malus per la varianza delle scale (changesPct) al punteggio dell'intervista.
 * Logica:
 *  0%        => -0.2
 *  1-20%     => -0.1
 *  21-65%    =>  0
 *  66%-100%  => +0.1
 */
private function applyScaleChangesCriterion(array &$completeInterviews, array $scaleData): void
{
    // Creiamo una mappa intervista [iid => &...] come reference
    $indexedInterviews = [];
    foreach ($completeInterviews as &$iv) {
        // Usiamo iid come chiave, come avviene altrove
        $indexedInterviews[$iv['iid']] = &$iv;
    }
    unset($iv);

    // Ora scansioniamo le scale
    foreach ($scaleData as $scale) {
        $iid = $scale['iid'] ?? null;
        if (!$iid) {
            continue; // Se manca iid, skip
        }
        if (!isset($indexedInterviews[$iid])) {
            continue; // Non c'è corrispondenza con un'intervista
        }

        // Leggiamo changesPct
        $pct = $scale['changesPct'] ?? 0;

        // Applichiamo la logica
        if ($pct === 0) {
            // -0.2
            $indexedInterviews[$iid]['score'] -= 0.2;
        } elseif ($pct >= 1 && $pct <= 20) {
            // -0.1
            $indexedInterviews[$iid]['score'] -= 0.1;
        } elseif ($pct >= 21 && $pct <= 65) {
            // 0 => nessuna modifica
            // $indexedInterviews[$iid]['score'] += 0;
        } else {
            // 66%-100% => +0.1
            $indexedInterviews[$iid]['score'] += 0.1;
        }
    }
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

    private function detectPanel(array $data, ?int $dbPanelValue = null): string
    {
        // Esempio di ricodifica
        $panelNames = [
            1 => 'Cint',
            2 => 'Dynata',
            3 => 'Bilendi',
            4 => 'Norstat',
            5 => 'Toluna',
            6 => 'Netquest',
            7 => 'CATI',
            8 => 'Makeopinion',
            9 => 'Altro Panel',
        ];

        // Default
        $foundPanelName = 'Interactive';

        // 1) Se troviamo "pan= X" nella riga
        foreach ($data as $element) {
            if (strpos($element, 'pan=') !== false) {
                $val = (int)str_replace('pan=', '', $element);
                $foundPanelName = $panelNames[$val] ?? 'Altro Panel';
                break;
            }
        }

        // 2) Se non trovato e "dbPanelValue" indica 1 => "Interactive"
        //    Se dbPanelValue=2,3,... => potresti restituire un default, dipende
        return $foundPanelName;
    }


private function isSuspiciousResponse(string $resp, array $whiteList, array $blackList): bool
{
    $respTrim = trim($resp);

    // Convertiamo $respTrim in minuscolo
    $respLower = mb_strtolower($respTrim);

    // Creiamo due array di liste convertite in minuscolo
    // in modo da fare confronti case-insensitive
    $whiteListLower = array_map('mb_strtolower', $whiteList);
    $blackListLower = array_map('mb_strtolower', $blackList);

    // 1) Se la risposta corrisponde (case-insensitive) a una voce della whiteList,
    //    consideriamola NON fake (false).
    if (in_array($respLower, $whiteListLower, true)) {
        return false;
    }

    // 2) Se la risposta corrisponde (case-insensitive) a una voce della blackList,
    //    consideriamola SEMPRE fake (true).
    if (in_array($respLower, $blackListLower, true)) {
        //Log::info("Blacklist matched: {$respTrim}");
        return true;
    }

    // 3) Se lunghezza <3
    if (mb_strlen($respTrim) < 3) {
        //Log::info("Too short response: {$respTrim}");
        return true;
    }

    // 4) Se contiene URL
    if (preg_match('/(http:\/\/|https:\/\/|www\.)|(\.(com|it)(\s|\/|$))/i', $respTrim)) {
        //Log::info("URL detected in response: {$respTrim}");
        return true;
    }

    // 5) Se c'è eccesso di caratteri ripetuti
    if ($this->hasExcessRepeats($respTrim)) {
        //Log::info("Excess repeats detected in response: {$respTrim}");
        return true;
    }

    // 6) Se combina numeri e lettere in modo random
    if ($this->allWordsHaveRandomLetterNumberCombo($respTrim)) {
        //Log::info("All words contain random numbers and letters combo: {$respTrim}");
        return true;
    }

  // 7) Sequenze di almeno 4 consonanti
    if ($this->hasOnlySuspiciousWords($respTrim)) {
        //Log::info("Consecutive consonants detected: {$respTrim}");
        return true;
    }

// 8) Controllo parole singole con sequenze illegali
if ($this->isSingleWordWithIllegalSequence($respTrim)) {
    return true;
}

    // Se nessuno di questi criteri => NON fake
    return false;
}


private function hasExcessRepeats(string $resp, int $threshold = 5): bool
{
    // Esempio di regex: (.)\1{4,} cerca 5 ripetizioni consecutive
    // Se threshold=5, la pattern è {4,} perché la prima cattura +4 repliche = 5 totali
    $pattern = '/(.)\1{'.($threshold-1).',}/u';

    if (preg_match($pattern, $resp)) {
        return true;
    }
    return false;
}



private function hasOnlySuspiciousWords(string $resp): bool
{
    $words = preg_split('/\s+/', $resp);

    foreach ($words as $word) {
        // Se trovi almeno una parola che non è chiaramente random, la risposta è valida
        if (!$this->isClearlyRandomWord($word) &&
            !preg_match('/[bcdfghjklmnpqrstvwxyz]{5,}/i', $word) &&
            !$this->isShortWordWithoutVowels($word) &&
            !$this->isShortSuspiciousWord($word)) {
            return false;
        }
    }

    // Se tutte le parole risultano sospette, allora la frase è sospetta
    return true;
}

private function allWordsHaveRandomLetterNumberCombo(string $resp): bool
{
    $words = preg_split('/\s+/', $resp);

    foreach ($words as $word) {
        // Se troviamo almeno una parola senza combinazioni numeri/lettere, la frase NON è fake
        if (!preg_match('/[A-Za-z]+[0-9]+|[0-9]+[A-Za-z]+/', $word)) {
            return false; // trovata parola normale
        }
    }

    // Tutte le parole hanno numeri e lettere combinate
    return true;
}


private function isShortWordWithoutVowels(string $word): bool
{
    $word = trim(mb_strtolower($word));

    // Lunghezza da 3 a 5 caratteri e assenza di vocali
    if (mb_strlen($word) >= 3 && mb_strlen($word) <= 5 && !preg_match('/[aeiou]/i', $word)) {
        return true;
    }

    return false;
}

private function isShortSuspiciousWord(string $word): bool
{
    $word = mb_strtolower(trim($word));

    // Parole da 2 a 3 lettere formate solo da consonanti
    if (mb_strlen($word) >= 2 && mb_strlen($word) <= 3 && !preg_match('/[aeiou]/i', $word)) {
        return true;
    }

    return false;
}


private function isSingleWordWithIllegalSequence(string $resp): bool
{
    $resp = mb_strtolower(trim($resp));

    // Dividiamo la risposta in parole
    $words = preg_split('/\s+/', $resp);

    // Procediamo SOLO se c'è esattamente una parola
    if (count($words) !== 1) {
        return false; // Più di una parola, non considerare questo controllo
    }

    // Lista sequenze illegali (da estendere nel tempo)
    $illegalPatterns = [
        'dfy', 'fyu', 'efg', 'fgu', 'drt', 'dgu', 'guu', 'dyu', 'xgu',
        'fgi', 'zfg', 'waq', 'iuy', 'dty', 'tyu', 'rtt', 'dgy', 'gyu',
            'qya', 'qop', 'qen', 'aqk', 'ojd', 'ejk', 'axq', 'exkz',
            'uuu', 'iii', 'jjj', 'abg', 'obm', 'apk', 'atk', 'xxz'
    ];

    // Controllo sequenze illegali nella parola singola
    foreach ($illegalPatterns as $pattern) {
        if (strpos($resp, $pattern) !== false) {
            return true; // Trovata sequenza illegale
        }
    }

    return false; // Nessuna sequenza illegale trovata
}

private function isClearlyRandomWord(string $word): bool
{
    $word = mb_strtolower(trim($word));

    // Lunghezza minima per valutare parole casuali (almeno 6 lettere)
    if (mb_strlen($word) < 6) {
        return false;
    }

    // Controllo semplice e poco impattante di almeno 4 vocali consecutive (molto raro)
    if (preg_match('/[aeiou]{4,}/i', $word)) {
        return true;
    }

    // Controllo alternanza insolita di consonanti molto rare e vocali
    if (preg_match('/([jkqwxy][aeiou]){3,}/i', $word)) {
        return true;
    }

    return false; // Nessun altro controllo più aggressivo
}



private function loadWhiteList(): array
{
    // Se i file JSON sono in public/json/whitelist.json:
    $path = public_path('json/whitelist.json');

    if (!file_exists($path)) {
        return [];
    }
    $rawContent = file_get_contents($path);
    $decoded = json_decode($rawContent, true);
    if (is_null($decoded)) {
        return [];
    }

    return $decoded;
}



private function loadBlackList(): array
{
    $path = public_path('json/blacklist.json');
    if (!file_exists($path)) {
        return [];
    }
    return json_decode(file_get_contents($path), true) ?? [];
}


public function addToWhiteList(Request $request)
{
    $text = trim($request->input('text', ''));
    if ($text === '') {
        return response()->json([
            'success' => false,
            'message' => 'Testo vuoto o non fornito.'
        ], 400);
    }

    // Carica file JSON
    $path = public_path('json/whitelist.json');
    $list = [];
    if (file_exists($path)) {
        $list = json_decode(file_get_contents($path), true) ?? [];
    }

    // Confronto case-insensitive o no?
    // Se vuoi ignorare case, potresti scorrere e controllare in minuscolo
    $lowerList = array_map('mb_strtolower', $list);
    $lowerText = mb_strtolower($text);

    // Aggiungiamo solo se non già presente
    if (!in_array($lowerText, $lowerList, true)) {
        $list[] = $text; // Salva con il suo case originario, oppure in minuscolo
        file_put_contents($path, json_encode($list, JSON_PRETTY_PRINT));
    }

    return response()->json(['success' => true]);
}

public function addToBlackList(Request $request)
{
    $text = trim($request->input('text', ''));
    if ($text === '') {
        return response()->json([
            'success' => false,
            'message' => 'Testo vuoto o non fornito.'
        ], 400);
    }

    // Carica file JSON
    $path = public_path('json/blacklist.json');
    $list = [];
    if (file_exists($path)) {
        $list = json_decode(file_get_contents($path), true) ?? [];
    }

    // Confronto case-insensitive
    $lowerList = array_map('mb_strtolower', $list);
    $lowerText = mb_strtolower($text);

    // Aggiungiamo solo se non già presente
    if (!in_array($lowerText, $lowerList, true)) {
        $list[] = $text;
        file_put_contents($path, json_encode($list, JSON_PRETTY_PRINT));
    }

    return response()->json(['success' => true]);
}

/**
     * Legge TUTTE le righe del file .sre per trovare righe "scale;...."
     * e calcolare varianza (cambi consecutivi).
     */
    private function extractScaleData(string $filePath, string $iid, string $uid,string $panelUsed, array &$scaleData): void
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return;
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            // parseScaleLine restituisce un array con info se è scale singola, altrimenti null
            $parsed = $this->parseScaleLine($line);
            if ($parsed !== null) {
                // Aggiungiamo i campi iid, uid, panel ecc. se servono
                $parsed['iid'] = $iid;
                $parsed['uid'] = $uid;
                $parsed['panel'] = $panelUsed;

                // Salviamo
                $scaleData[] = $parsed;
            }
        }
        fclose($handle);
    }


/**
 * Controlla se la riga indica una scale singola da considerare.
 * Se sì, calcola varianza (numero di cambi consecutivi) e %.
 * Ritorna array con questionId, changes, changesPct, answers, ecc., altrimenti null.
 */
private function parseScaleLine(string $line): ?array
{
    $fields = explode(";", trim($line));

    // 1) Deve iniziare con "scale"
    if (empty($fields[0]) || $fields[0] !== 'scale') {
        return null;
    }

    // L'indicazione "terzo dato deve essere superiore a 7" non è più
    // un check iniziale, lo faremo dopo aver filtrato i -1 effettivi.

    // Per sicurezza, controlliamo se esistono i campi [1,2,3] => questionId, nRows, nCols
    if (!isset($fields[1], $fields[2], $fields[3])) {
        return null;
    }

    $questionId = (int)$fields[1];
    // $nRows = (int)$fields[2];  // Non lo usiamo più per la soglia, lo useremo come informativo
    // $nCols = (int)$fields[3];  // Idem, lo lasciamo come info

    $nRows = (int)$fields[2];
    $nCols = (int)$fields[3];

    // 2) Distinguere scale multiple (l'ultimo campo se blocco 0/1 lungo => skip)
    $lastField = end($fields);
    if (preg_match('/^[01]+$/', $lastField) && strlen($lastField) > 5) {
        // E' multi-scale => scartiamo
        return null;
    }

    // 3) Le risposte partono da index=4 in poi
    $answersRaw = array_slice($fields, 4);
    if (empty($answersRaw)) {
        return null; // nessuna risposta => skip
    }

    // Convertiamo in int
    $allAnswers = array_map('intval', $answersRaw);

    // 4) Filtriamo i -1 (non conteggiamo come risposte valide)
    $filteredAnswers = array_filter($allAnswers, function($val) {
        return $val !== -1;
    });
    $countValid = count($filteredAnswers);

    // 5) Se dopo aver escluso i -1 restano 7 o meno risposte, scartiamo
    if ($countValid <= 7) {
        return null;
    }

    // 6) Calcoliamo la varianza = numero di cambi consecutivi
    //    NB: usiamo $filteredAnswers
    $changes = $this->countSequentialChanges(array_values($filteredAnswers));
    $totalAnswers = $countValid;
    $changesPct = 0;
    if ($totalAnswers > 0) {
        $changesPct = (int) round(($changes / $totalAnswers) * 100);
    }

    // 7) Ritorniamo i dati
    return [
        'questionId' => $questionId,
        'nRows'      => $nRows,
        'nCols'      => $nCols,
        'answers'    => array_values($filteredAnswers), // se vuoi memorizzare quelle "valide"
        'changes'    => $changes,
        'changesPct' => $changesPct,
    ];
}

private function populateScaleQuestionsDetails(array &$scaleData, array $questionMap): void
{
    foreach ($scaleData as &$row) {
        $qId = $row['questionId'] ?? null;
        if (isset($questionMap[$qId])) {
            $row['code']    = $questionMap[$qId]['code'];
            $row['tooltip'] = $questionMap[$qId]['text'];
        } else {
            $row['code']    = 'unknown';
            $row['tooltip'] = 'Domanda non presente';
        }
    }
    unset($row);
}


    /**
     * Conta i cambi consecutivi nella sequenza di risposte.
     */
    private function countSequentialChanges(array $answers): int
    {
        $count = count($answers);
        if ($count < 2) {
            return 0;
        }
        $changes = 0;
        for ($i = 1; $i < $count; $i++) {
            if ($answers[$i] !== $answers[$i - 1]) {
                $changes++;
            }
        }
        return $changes;
    }

    public function saveFilter(Request $request)
    {
        // Esempio di lettura parametri
        $question1 = $request->input('question1');
        $operator1 = $request->input('operator1');
        $answer1   = $request->input('answer1');
        $logicalOperator = $request->input('logicalOperator');
        $question2 = $request->input('question2');
        $operator2 = $request->input('operator2');
        $answer2   = $request->input('answer2');

        // Log o salvataggio su DB...
        Log::info("Salvataggio Filtro: ", compact(
            'question1','operator1','answer1','logicalOperator','question2','operator2','answer2'
        ));

        // Esempio di JSON response
        return response()->json(['success' => true]);
    }


}
