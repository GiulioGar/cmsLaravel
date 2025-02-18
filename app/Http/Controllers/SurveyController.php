<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PanelControl;
use DataTables;
use Carbon\Carbon;

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
            'sur_id',
            'description',
            'panel',
            'complete',
            'red_panel',
            'red_surv',
            'end_field',
            'giorni_rimanenti', // Se non serve più dal DB, puoi rimuoverlo
            'Costo',
            'bytes',
            'stato',  // << NOTA: usiamo "stato" al posto di "status"
            'id'
        ])
        // Ordinamento: prima ricerche con stato=0, poi stato=1, poi le altre
        ->orderByRaw("CASE WHEN stato = 0 THEN 0 WHEN stato = 1 THEN 1 ELSE 2 END ASC")
        // Quindi, all’interno di ogni gruppo, ordina per ID decrescente
        ->orderBy('id', 'desc');

        return DataTables::of($query)
            // Se vuoi convertire i valori di "panel" in stringhe
            ->editColumn('panel', function($row) {
                switch ($row->panel) {
                    case 0: return 'Interactive';
                    case 1: return 'Esterno';
                    case 2: return 'Lista';
                    default: return $row->panel;
                }
            })
            // Pallino rosso lampeggiante accanto a sur_id se stato=0
            ->editColumn('sur_id', function($row) {
                // Base: escapare il sur_id (sicurezza)
                $base = e($row->sur_id);

                // Se stato=0, aggiungiamo il pallino rosso lampeggiante
                if ($row->stato == 0) {
                    $dot = '<span class="blinking-dot"></span> ';
                    $base = $dot . $base;
                }

                return $base;
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
                if (!$row->end_field) {
                    // Se manca end_field, mettiamo un badge grigio "N.D."
                    return '<span class="badge bg-secondary">N.D.</span>';
                }

                $today = Carbon::today();
                $end   = Carbon::parse($row->end_field);

                // diffInDays con parametro "false" => calcola differenza con segno
                $diff = $today->diffInDays($end, false);

                if ($diff == 0) {
                    // OGGI
                    return '<span class="badge bg-info">OGGI</span>';
                } elseif ($diff < 0) {
                    // end_field è passato -> "Concluso"
                    return '<span class="badge bg-danger">Concluso</span>';
                } else {
                    // Restano $diff giorni -> "X giorni"
                    return '<span class="badge bg-success">' . $diff . ' giorni</span>';
                }
            })
            // Se Costo è null o vuoto, mostra "N.D."
            ->editColumn('Costo', function($row) {
                return ($row->Costo === null || $row->Costo === '')
                    ? 'N.D.'
                    : $row->Costo;
            })
            // Pulsante Modifica
            ->addColumn('campo_edit', function($row) {
                // Rimuovi l'anchor e metti un button
                return '<button class="btn btn-edit" data-id="'.$row->id.'">
                <i class="fas fa-edit"></i>
                    </button>';
            })
            // Ricordiamoci di abilitare i campi HTML
            ->rawColumns(['sur_id', 'giorni_rimanenti', 'campo_edit'])
            ->make(true);
    }

    public function edit($id)
    {
        $survey = PanelControl::findOrFail($id);

        // Se end_field è "2024-10-09 00:00:00", trasformalo in "2024-10-09"
        if (!empty($survey->end_field)) {
            $survey->end_field = \Carbon\Carbon::parse($survey->end_field)
                                ->format('Y-m-d');
        }

        return response()->json($survey);
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
                    'complete'     => 'nullable|integer|min:0',
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






}
