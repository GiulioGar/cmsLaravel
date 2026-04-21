<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PanelControl;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // se usi query builder



class SurveyController extends Controller
{
    /**
     * Mostra la view con la tabella DataTables.
     */
    public function index()
    {
        // Ritorna semplicemente la vista surveys.blade.php
        return view('surveys');
    }

    /**
     * Fornisce i dati a DataTables in formato JSON.
     */
    public function getData(Request $request)
    {
            $query = PanelControl::select([
                't_panel_control.sur_id',
                't_panel_control.prj',
                't_panel_control.description',
                't_panel_control.panel',
                't_panel_control.complete',
                't_panel_control.red_panel',
                't_panel_control.red_surv',
                't_panel_control.end_field',
                't_panel_control.sur_date',
                't_panel_control.giorni_rimanenti',
                't_panel_control.costo',
                't_panel_control.bytes',
                't_panel_control.stato',
                't_panel_control.id',
                't_surveys.status as primis_status' // 👈 NUOVO
            ])
            ->leftJoin('t_surveys', function($join) {
                $join->on('t_surveys.sid', '=', 't_panel_control.sur_id')
                    ->on('t_surveys.prj_name', '=', 't_panel_control.prj');
            });

        // Ordinamento: prima ricerche con stato=0, poi stato=1, poi le altre
       // ->orderByRaw("CASE WHEN stato = 0 THEN 0 WHEN stato = 1 THEN 1 ELSE 2 END ASC")
        // Quindi, all’interno di ogni gruppo, ordina per ID decrescente
        //->orderBy('id', 'desc');

        return DataTables::of($query)

                // 2. Forza l’ordinamento con la callback `->order(...)`
                ->order(function($q) {
                    // QUI imponi l'ordine che desideri
                    $q->orderByRaw("CASE WHEN stato = 0 THEN 0 WHEN stato = 1 THEN 1 ELSE 2 END")
                      ->orderBy('id', 'desc');
                })

            // Se vuoi convertire i valori di "panel" in stringhe
            ->editColumn('panel', function($row) {
                $label = 'N.D.';
                $class = 'sv-panel-icon--slate';
                $icon  = 'fas fa-question-circle';

                switch ((int)$row->panel) {
                    case 1:
                        $label = 'Panel Interactive';
                        $class = 'sv-panel-icon--green';
                        $icon  = 'fas fa-user-friends';
                        break;

                    case 0:
                        $label = 'Panel Esterno';
                        $class = 'sv-panel-icon--slate';
                        $icon  = 'fas fa-globe';
                        break;

                    case 2:
                        $label = 'Da Lista';
                        $class = 'sv-panel-icon--violet';
                        $icon  = 'fas fa-list';
                        break;

                    default:
                        $label = 'N.D.';
                        $class = 'sv-panel-icon--slate';
                        $icon  = 'fas fa-question-circle';
                        break;
                }

                return '<span class="sv-panel-icon ' . $class . '"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-title="' . e($label) . '">
                            <i class="' . $icon . '"></i>
                        </span>';
            })

                ->editColumn('red_panel', function($row) {
                    // Se non è panel Interactive, IR Panel non ha senso
                    if ((int)$row->panel !== 1) {
                        return '<span class="sv-badge sv-ir sv-ir--na">N.A.</span>';
                    }

                    $val = is_numeric($row->red_panel) ? (float)$row->red_panel : null;

                    if ($val === null) {
                        return '<span class="sv-badge sv-ir sv-ir--na">N.D.</span>';
                    }

                    $class = ($val >= 25) ? 'sv-ir--good' : (($val >= 12) ? 'sv-ir--mid' : 'sv-ir--bad');
                    $txt = rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.');

                    return "<span class=\"sv-badge sv-ir {$class}\">{$txt}%</span>";
                })

                ->editColumn('red_surv', function($row) {
                    $val = is_numeric($row->red_surv) ? (float)$row->red_surv : null;

                    if ($val === null) {
                        return '<span class="sv-badge sv-ir sv-ir--na">N.D.</span>';
                    }

                    $class = ($val >= 25) ? 'sv-ir--good' : (($val >= 12) ? 'sv-ir--mid' : 'sv-ir--bad');
                    $txt = rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.');

                    return "<span class=\"sv-badge sv-ir {$class}\">{$txt}%</span>";
                })
                ->editColumn('complete', function($row) {
                    $val = is_numeric($row->complete) ? (int)$row->complete : 0;
                    return "<span class=\"sv-num\">{$val}</span>";
                })

                    ->editColumn('costo', function($row) {

                        if ($row->costo === null || $row->costo === '') {
                            return '<span class="sv-badge sv-ir sv-ir--na">N.D.</span>';
                        }

                        if (is_numeric($row->costo)) {
                            $txt = number_format((float)$row->costo, 2, ',', '.');

                            return '<span class="sv-num">' . $txt . '</span><span class="sv-muted ms-1">€</span>';
                        }

                        return e((string)$row->costo);
                    })

                ->editColumn('bytes', function($row) {
                    if ($row->bytes === null || $row->bytes === '') {
                        return '<span class="sv-badge sv-ir sv-ir--na">N.D.</span>';
                    }

                    if (is_numeric($row->bytes)) {
                        $txt = number_format((float)$row->bytes, 0, ',', '.');
                        return "<span class=\"sv-num\">{$txt}</span>";
                    }

                    return e((string)$row->bytes);
                })

                ->editColumn('primis_status', function($row) {

                    $status = $row->primis_status;

                    if ($status === null) {
                        return '<span class="sv-badge sv-ir--na">N.D.</span>';
                    }

                    if ((int)$status === 2) {
                        return '<span class="sv-status sv-status--green"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    data-bs-title="Aperta">
                                    <i class="fas fa-play-circle"></i>
                                </span>';
                    }

                    if ((int)$status === 0 || (int)$status === 1) {
                        return '<span class="sv-status sv-status--yellow"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    data-bs-title="In progettazione">
                                    <i class="fas fa-pencil-ruler"></i>
                                </span>';
                    }

                    if ((int)$status >= 3) {
                        return '<span class="sv-status sv-status--red"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    data-bs-title="Chiusa">
                                    <i class="fas fa-stop-circle"></i>
                                </span>';
                    }

                    return '<span class="sv-badge sv-ir--na">N.D.</span>';
                })   

            // Pallino rosso lampeggiante accanto a sur_id se stato=0
            ->editColumn('sur_id', function($row) {
                // 1) Ricaviamo il codice (sur_id) e lo “escapiamo” per sicurezza
                $codice = e($row->sur_id);

                // 2) Eventuale pallino rosso lampeggiante se stato=0
                $dot = '';
                if ($row->stato == 0) {
                    $dot = '<span class="blinking-dot"></span> ';
                }

                // 3) Creiamo l’URL dove passiamo prj e sid
                //    Ad esempio: /fieldControl?prj=XXX&sid=YYY
                $url = url('fieldControl') . '?' . http_build_query([
                    'prj' => $row->prj,
                    'sid' => $row->sur_id,
                ]);

                // 4) Costruiamo il link con un effetto hover (class bootstrap o stile personalizzato)
                //    Mettiamo "title" per un tooltip, e un eventuale classe per hover
                $link = "<a href=\"{$url}\" class=\"link-sur-id\" title=\"Vai a FieldControl\">{$dot}{$codice}</a>";

                return $link;
            })
            // Formattazione end_field (es: "Lun 1 Gen 25")
            ->editColumn('end_field', function($row) {
                if (!$row->end_field) {
                    return 'N.D.';
                }

                $date = Carbon::parse($row->end_field)->locale('it');
                $dayOfWeek = ucfirst($date->isoFormat('ddd')); // "Lun"
                $dayNum    = $date->isoFormat('D');            // "1"
                $month     = ucfirst($date->isoFormat('MMM')); // "Gen"
                $year      = $date->isoFormat('YY');           // "25"

                return "$dayOfWeek $dayNum $month $year";
            })

            // Calcolo dei giorni rimanenti
            ->editColumn('giorni_rimanenti', function($row) {
                $stato = (int) ($row->stato ?? 0);

                // =========================
                // RICERCA CHIUSA
                // =========================
                if ($stato === 1) {
                    if (empty($row->sur_date) || empty($row->end_field)) {
                        return '<span class="sv-day sv-day--na"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    data-bs-title="Durata field non disponibile">N.D.</span>';
                    }

                    $start = Carbon::parse($row->sur_date)->startOfDay();
                    $end   = Carbon::parse($row->end_field)->startOfDay();

                    $duration = max(1, $start->diffInDays($end) + 1);

                    return '<span class="sv-day sv-day--closed"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-title="Durata field ' . $duration . ' giorni">'
                                . $duration .
                            '</span>';
                }

                // =========================
                // RICERCA APERTA
                // =========================
                if (empty($row->end_field)) {
                    return '<span class="sv-day sv-day--na">N.D.</span>';
                }

                $today = Carbon::today();
                $end   = Carbon::parse($row->end_field)->startOfDay();

                $diff = $today->diffInDays($end, false);

                if ($diff === 0) {
                    return '<span class="sv-day sv-day--today"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-title="Fine field oggi">Oggi</span>';
                }

                if ($diff > 0) {
                    return '<span class="sv-day sv-day--open"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-title="' . $diff . ' giorni alla fine">-' . $diff . '</span>';
                }

                $overdue = abs($diff);

                return '<span class="sv-day sv-day--over"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            data-bs-title="' . $overdue . ' giorni oltre la fine">+' . $overdue . '</span>';
            })



            // Se Costo è null o vuoto, mostra "N.D."
            ->editColumn('Costo', function($row) {
                return ($row->Costo === null || $row->Costo === '')
                    ? 'N.D.'
                    : $row->Costo;
            })
            // Pulsante Modifica
                ->addColumn('campo_edit', function($row) {
                    return '<button class="btn btn-edit" data-id="'.$row->id.'" title="Modifica">
                                <i class="fas fa-pen"></i>
                            </button>';
                })
            // Ricordiamoci di abilitare i campi HTML
            ->rawColumns(['sur_id', 'panel', 'complete', 'red_panel', 'red_surv', 'costo', 'bytes', 'giorni_rimanenti', 'campo_edit','primis_status'])
            ->setRowClass(function ($row) {
             return ((int)$row->stato === 0) ? 'sv-row-active' : '';
                    })
            ->make(true);
    }

            public function edit($id)
            {
                $survey = PanelControl::findOrFail($id);

                // normalizza end_field per input type="date"
                $endField = null;
                if (!empty($survey->end_field)) {
                    $endField = \Carbon\Carbon::parse($survey->end_field)->format('Y-m-d');
                }

                return response()->json([
                    'id'          => $survey->id,
                    'sur_id'      => $survey->sur_id,
                    'panel'       => $survey->panel,
                    'sex_target'  => $survey->sex_target,
                    'age1_target' => $survey->age1_target,
                    'age2_target' => $survey->age2_target,

                    // QUI: interviste in modale = goal (colonna goal in t_panel_control)
                    'goal'        => $survey->goal,

                    'description' => $survey->description,
                    'stato'       => $survey->stato,
                    'end_field'   => $endField,
                ]);
            }


        public function update(Request $request, $id)
            {
                // 1. Trova il record
                $survey = PanelControl::findOrFail($id);

                // 2. Regole di validazione per i campi interessati
                //    Adegua "in:1,2,3" se usi codici diversi per "sex_target".
                //    Stessa cosa per panel (0,1,2) e stato (0,1).
                $request->validate([
                    'sur_id'       => 'required|string|max:50',
                    'panel'        => 'in:0,1,2',       // 0=Interactive, 1=Esterno, 2=Lista (esempio)
                    'sex_target'   => 'in:1,2,3',       // 1=Uomo, 2=Donna, 3=Uomo/Donna
                    'age1_target'  => 'nullable|integer|min:0|max:120',
                    'age2_target'  => 'nullable|integer|min:0|max:120',
                    'goal'     => 'nullable|integer|min:0',
                    'end_field'    => 'nullable|date',  // Se ricevi solo "YYYY-MM-DD"
                    'description'  => 'nullable|string',
                    'stato'        => 'in:0,1',         // 0=Aperto, 1=Chiuso
                ]);

                // 3. Assegna i valori
                $survey->sur_id       = $request->input('sur_id');
                $survey->panel        = $request->input('panel');
                $survey->sex_target   = $request->input('sex_target');
                $survey->age1_target  = $request->input('age1_target');
                $survey->age2_target  = $request->input('age2_target');
                $survey->complete     = $request->input('complete');
                $survey->description  = $request->input('description');
                $survey->stato        = $request->input('stato'); // 0=Aperto, 1=Chiuso

                // Gestione end_field
                // Se nel form arriva una stringa "2014-02-16" (senza ora),
                // possiamo aggiungere manualmente "00:00:00", se la colonna è datetime:
                $endFieldValue = $request->input('end_field'); // Esempio "2025-02-17"
                if (!empty($endFieldValue)) {
                    // Se vuoi forzare l'ora a 00:00:00
                    // (usa Carbon per sicurezza)
                    $survey->end_field = \Carbon\Carbon::parse($endFieldValue)
                                        ->format('Y-m-d 00:00:00');
                } else {
                    // Se non viene passato nulla, lo rendiamo null (o lasciamo il valore esistente)
                    $survey->end_field = null;
                }

                // 4. Salva
                $survey->save();

                // 5. Risposta JSON per AJAX
                return response()->json(['success' => true]);
            }


            public function store(Request $request)
            {
                // 1. Preleva il valore di "panel"
                $panelVal = $request->input('panel');

                // 2. Crea un array di regole "base" (sempre valide)
                $rules = [
                    'sid'          => 'required|string|max:50',
                    'prj'          => 'required|string|max:50',
                    'cliente'      => 'required|string|max:100',
                    'tipologia'    => 'required|string|max:10',
                    'panel'        => 'required|in:0,1,2',
                    'goal'         => 'required|integer|min:0',   // interviste => "complete"
                    'end_date'     => 'nullable|date',
                    'descrizione'  => 'required|string|max:255',
                    'paese'        => 'required|string|max:50',
                    'sex_target'   => 'nullable|in:1,2,3',        // di default nullable
                    'age1_target'  => 'nullable|integer|min:0',
                    'age2_target'  => 'nullable|integer|min:0',
                ];

                // 3. Se panel=1 (Millebytes), i campi "ir, loi, point, argomento, sex_target" diventano obbligatori
                if ($panelVal == '1') {
                    $rules['ir']         = 'required|integer|min:0';
                    $rules['loi']        = 'required|integer|min:0';
                    $rules['point']      = 'required|integer|min:0';
                    $rules['argomento']  = 'required|string|max:255';
                    $rules['sex_target'] = 'required|in:1,2,3';  // se vuoi che genere sia obbligatorio
                }
                else {
                    // Se panel ≠ 1, questi campi possono essere semplicemente "nullable"
                    $rules['ir']         = 'nullable|integer|min:0';
                    $rules['loi']        = 'nullable|integer|min:0';
                    $rules['point']      = 'nullable|integer|min:0';
                    $rules['argomento']  = 'nullable|string|max:255';
                    // sex_target è già "nullable|in:1,2,3" di default
                }

                // 4. Valida l'input in base alle regole finali
                $validated = $request->validate($rules);


                DB::transaction(function () use ($validated) {

                    // =========================
                    // 1) Salvataggio t_panel_control
                    // =========================
                    $survey = new PanelControl();
                    $survey->sur_id      = $validated['sid'];
                    $survey->prj         = $validated['prj'];
                    $survey->cliente     = $validated['cliente'];
                    $survey->tipologia   = $validated['tipologia'];
                    $survey->panel       = $validated['panel'];

                    $survey->goal        = $validated['goal'];
                    $survey->end_field   = $validated['end_date'] ?? null;

                    $survey->description = $validated['descrizione'];
                    $survey->paese       = $validated['paese'];
                    $survey->sur_date    = \Carbon\Carbon::now();
                    $survey->stato       = 0;

                    $survey->sex_target  = $validated['sex_target'] ?? null;
                    $survey->age1_target = $validated['age1_target'] ?? null;
                    $survey->age2_target = $validated['age2_target'] ?? null;

                    $survey->save();

                    // =========================
                    // 2) Salvataggio t_surveys_env (solo panel==1)
                    // =========================
                    if ((string)$validated['panel'] === '1') {

                        // Mappa: name => value
                        $envRows = [
                            'survey_object'       => $validated['argomento'],
                            'prize_complete'      => $validated['point'],
                            'length_of_interview' => $validated['loi'],
                        ];

                        foreach ($envRows as $name => $value) {
                            DB::table('t_surveys_env')->updateOrInsert(
                                [
                                    'prj_name' => $validated['prj'],
                                    'sid'      => $validated['sid'],
                                    'name'     => $name,
                                ],
                                [
                                    'value' => $value,
                                    'store' => 0,
                                ]
                            );
                        }
                    }
                });

                return response()->json(['success' => true]);
            }



            public function getAvailableSurIds()
            {
                // Elenco di sur_id già presenti in t_panel_control
                // (cioè i sid che abbiamo già associato a un progetto)
                $usedSurIds = DB::table('t_panel_control')->pluck('sur_id')->toArray();

                // Seleziona i record da t_surveys:
                // - colonna "sid"
                // - where status=2
                // - excluding i sid presenti in $usedSurIds
                // - ordina in modo decrescente (descending) per sid
                $available = DB::table('t_surveys')
                    ->select('sid')
                    ->where('status', 2)
                    ->whereNotIn('sid', $usedSurIds)
                     ->orderByRaw("
                CASE
                    WHEN sid REGEXP '^R[0-9]+$' THEN 1
                    ELSE 0
                END DESC
            ")
            ->orderByRaw("
                CASE
                    WHEN sid REGEXP '^R[0-9]+$' THEN CAST(SUBSTRING(sid, 2) AS UNSIGNED)
                    ELSE NULL
                END DESC
            ")
            ->orderBy('sid', 'desc')
                    ->get();

                return response()->json($available);
            }

            public function getPrjInfo(Request $request)
                {
                    $sid = $request->input('sid'); // recupera ?sid=...
                    // Leggi la colonna prj_name (o come si chiama) dalla tabella t_surveys
                    $row = DB::table('t_surveys')
                        ->select('prj_name')      // Se il campo si chiama in modo diverso, adegua
                        ->where('sid', $sid)
                        ->first();

                    // Se trovi il record, restituisci prj_name in JSON
                    if ($row) {
                        return response()->json(['prj_name' => $row->prj_name]);
                    } else {
                        // Se non trovato, restituisci un oggetto vuoto o errore
                        return response()->json(['prj_name' => null], 404);
                    }
                }

                public function getClientByPrj(Request $request)
                    {
                        $prj = $request->input('prj');

                        // Se usi QueryBuilder
                        $record = DB::table('t_panel_control')
                            ->select('cliente')
                            ->where('prj', trim($prj))
                            ->first();

                        if ($record && !empty($record->cliente)) {
                            // Restituisci il cliente trovato
                            return response()->json(['cliente' => $record->cliente]);
                        }

                        // Altrimenti, se non trovato o vuoto, restituisci stringa vuota
                        return response()->json(['cliente' => '']);
                    }



}
