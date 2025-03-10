<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PrimisApiService;

class TargetFieldController extends Controller
{
    public function index(Request $request, PrimisApiService $primis)
    {
        $prj = $request->query('prj');
        $sid = $request->query('sid');

        $ricercheInCorso = DB::table('t_panel_control')
            ->where('stato', 0)
            ->orderBy('description', 'asc')
            ->get(['sur_id', 'description', 'prj']);

        $questions = [];
        try {
            $resp = $primis->listQuestions($prj, $sid);
            if (!empty($resp['questions']) && ($resp['success'] ?? true)) {
                $questions = $resp['questions'];
            }
        } catch (\Exception $e) {
            //
        }

        $filtered = array_filter($questions, function($q) {
            if (($q['type'] ?? '') === 'choice') {
                return true;
            }
            if (($q['type'] ?? '') === 'scale') {
                return ($q['selection'] ?? '') === 'single';
            }
            return false;
        });

        $filtered = array_values($filtered);

        return view('targetField', [
            'prj' => $prj,
            'sid' => $sid,
            'ricercheInCorso' => $ricercheInCorso,
            'questions' => $filtered
        ]);
    }

    public function getQuestionDetail(Request $request, PrimisApiService $primis)
    {
        $prj = $request->query('prj');
        $sid = $request->query('sid');
        $questionId = (int)$request->query('question_id');

        if (!$prj || !$sid || !$questionId) {
            return response()->json([
                'success' => false,
                'message' => 'Parametri mancanti (prj, sid o question_id).'
            ]);
        }

        try {
            // 1) Recupera i dati della domanda da Primis
            $response = $primis->getQuestion($prj, $sid, $questionId);

            if (!($response['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $response['error_message'] ?? 'Question not found'
                ]);
            }

            $question = $response['question'];
            $type     = $question['type'] ?? '';
            $options  = $question['options'] ?? [];  // per choice
            $rows     = $question['rows']    ?? [];  // per scale
            $cols     = $question['cols']    ?? [];  // per scale

            // 2) Inizializziamo una struttura distribution per i conteggi
            $distribution = [];

            if ($type === 'choice') {
                foreach ($options as $idx => $opt) {
                    $distribution[$idx] = 0;
                }
            } elseif ($type === 'scale') {
                foreach ($rows as $rIndex => $rVal) {
                    $distribution[$rIndex] = [];
                    foreach ($cols as $cIndex => $cVal) {
                        $distribution[$rIndex][$cIndex] = 0;
                    }
                }
            }

            // 3) Leggiamo i file .sre e contiamo le risposte
            $directory = base_path("var/imr/fields/$prj/$sid/results/");
            $files = glob($directory . "/*.sre");

            // nInterviews -> quante interviste totali hanno risposto a questa domanda
            $nInterviews = 0;

            foreach ($files as $file) {
                $handle = fopen($file, 'r');
                if (!$handle) {
                    continue;
                }

                while (($line = fgets($handle)) !== false) {
                    $line = trim($line);
                    $parts = explode(';', $line);
                    if (count($parts) < 2) {
                        continue;
                    }

                    $rowType = $parts[0] ?? '';
                    $rowQid  = (int)($parts[1] ?? -1);

                    if ($rowQid !== $questionId) {
                        continue;
                    }

                    // Ogni riga che corrisponde a questionId vale 1 intervista
                    $nInterviews++;

                    // Se CHOICE
                    if ($rowType === 'choice' && $type === 'choice') {
                        // Formato single: choice;id;numOptions;selectedIndex
                        // Formato multiple: choice;id;numOptions;stringa(0/1...);[altro?]

                        $nOpt = (int) ($parts[2] ?? 0);
                        if ($nOpt > 0 && count($parts) >= 4) {
                            $field = $parts[3];
                            // Se $field è lungo esattamente $nOpt caratteri, interpretala come multipla
                            if (strlen($field) === $nOpt) {
                                // Risposte multiple: 0->non selezionata, 1->selezionata
                                for ($i=0; $i<$nOpt; $i++) {
                                    if ($field[$i] === '1' && isset($distribution[$i])) {
                                        $distribution[$i]++;
                                    }
                                }
                            } else {
                                // Altrimenti single: $field è l'indice selezionato
                                $selectedIndex = (int)$field;
                                if (isset($distribution[$selectedIndex])) {
                                    $distribution[$selectedIndex]++;
                                }
                            }
                        }
                    }
                    // Se SCALE
                    elseif ($rowType === 'scale' && $type === 'scale') {
                        // Formato: scale;id;numRows;numCols;colSelRow1;colSelRow2;...
                        if (count($parts) >= 4) {
                            $nRows = (int)$parts[2];
                            $nCols = (int)$parts[3];
                            for ($r=0; $r<$nRows; $r++) {
                                $colSel = (int)($parts[4 + $r] ?? -1);
                                if (isset($distribution[$r][$colSel])) {
                                    $distribution[$r][$colSel]++;
                                }
                            }
                        }
                    }
                }
                fclose($handle);
            }

            // 4) Ritorniamo anche 'countInterviews' nel JSON (denominatore)
            return response()->json([
                'success'        => true,
                'question'       => $question,
                'distribution'   => $distribution,
                'countInterviews'=> $nInterviews
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore: '.$e->getMessage()
            ]);
        }
    }

    public function getTargetUIDs(Request $request)
{
    $prj = $request->query('prj');
    $sid = $request->query('sid');
    $questionId = (int)$request->query('question_id');
    $optionIndex = (int)$request->query('option_index');

    if (!$prj || !$sid || !$questionId) {
        return response()->json([
            'success' => false,
            'message' => 'Parametri mancanti.'
        ]);
    }

    // Directory dei file .sre
    $directory = base_path("var/imr/fields/$prj/$sid/results/");
    $files = glob($directory . "/*.sre");

    // Raccogliamo tutti i sysUID
    $uids = [];

    foreach ($files as $file) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines || !isset($lines[0])) {
            continue;
        }

        // Prima riga per sysUID
        $firstLine = trim($lines[0]);
        $uid = $this->extractSysUID($firstLine);
        if (!$uid || $uid === 'N/A') {
            continue;
        }

        // Riga per questionId (choice)
        for ($i = 1; $i < count($lines); $i++) {
            $parts = explode(';', trim($lines[$i]));
            if (count($parts) < 2) {
                continue;
            }

            $rowType = $parts[0] ?? '';
            $rowQid  = (int)($parts[1] ?? -1);

            if ($rowQid === $questionId && $rowType === 'choice') {
                // Formati possibili:
                // Single:  choice;id;N;selectedIndex
                // Multipla: choice;id;N;stringa(0/1...);
                $nOpt = (int)($parts[2] ?? 0);
                if ($nOpt > 0 && isset($parts[3])) {
                    $field = $parts[3];
                    // Se multipla: lunghezza stringa == nOpt
                    if (strlen($field) === $nOpt) {
                        // Verifica opzione 1
                        if (isset($field[$optionIndex]) && $field[$optionIndex] === '1') {
                            $uids[] = $uid;
                        }
                    } else {
                        // Altrimenti single
                        $selIndex = (int)$field;
                        if ($selIndex === $optionIndex) {
                            $uids[] = $uid;
                        }
                    }
                }
            }
        }
    }

    // Filtriamo solo gli UID presenti in t_user_info (campo user_id)
    if (empty($uids)) {
        return response()->json([
            'success'   => true,
            'validCount'=> 0,
            'uids'      => []
        ]);
    }

    $validUIDs = DB::table('t_user_info')
        ->whereIn('user_id', $uids)
        ->pluck('user_id')
        ->toArray();

    $countValid = count($validUIDs);

    return response()->json([
        'success'   => true,
        'validCount'=> $countValid,
        'uids'      => $validUIDs
    ]);
}

private function extractSysUID($line)
{
    // Scompone la riga e cerca "sysUID=..."
    $parts = explode(';', $line);
    foreach ($parts as $p) {
        if (strpos($p, 'sysUID=') === 0) {
            return str_replace('sysUID=', '', $p);
        }
    }
    return 'N/A';
}

public function fetchTargets()
{
    try {
        $data = DB::table('elencotag')
            ->orderBy('tag')
            ->get(['id','tag']);

        return response()->json([
            'success' => true,
            'targets' => $data
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Errore: '.$e->getMessage()
        ]);
    }
}

public function addTarget(Request $request)
{
    $targetName = trim($request->input('targetName',''));
    if(!$targetName){
        return response()->json([
            'success' => false,
            'message' => 'Nome del target non valido'
        ]);
    }

    try {
        // Inserisci nella tabella elencotag
        DB::table('elencotag')->insert([
            'tag' => $targetName
        ]);
        return response()->json([
            'success' => true
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Errore inserimento: '.$e->getMessage()
        ]);
    }
}


public function assignTarget(Request $request)
{
    $targetId = (int)$request->input('targetId');
    $uids     = $request->input('uids');

    if (!$targetId || empty($uids)) {
        return response()->json([
            'success' => false,
            'message' => 'Parametri mancanti.'
        ]);
    }

    if (!is_array($uids)) {
        $uids = explode(',', $uids);
    }
    $uids = array_unique(array_filter($uids));

    if (count($uids) === 0) {
        return response()->json([
            'success' => false,
            'message' => 'Nessun UID da inserire.'
        ]);
    }

        // 1) Recupera il 'tag' corrispondente a $targetId
        $targetName = DB::table('elencotag')
        ->where('id', $targetId)
        ->value('tag');

    $already = DB::table('utenti_target')
        ->where('target_id', $targetId)
        ->whereIn('uid', $uids)
        ->pluck('uid')
        ->toArray();

    $toInsert = array_diff($uids, $already);
    if (empty($toInsert)) {
        return response()->json([
            'success'       => true,
            'insertedCount' => 0
        ]);
    }

    $insertData = [];
    foreach ($toInsert as $u) {
        $insertData[] = [
            'uid'       => $u,
            'target_id' => $targetId,
            'target_name' => $targetName
        ];
    }

    DB::table('utenti_target')->insert($insertData);

    return response()->json([
        'success'       => true,
        'insertedCount' => count($toInsert)
    ]);
}




}
