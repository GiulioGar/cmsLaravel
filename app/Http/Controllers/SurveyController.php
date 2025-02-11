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
                $editUrl = route('surveys.edit', $row->id);
                return '<a href="'.$editUrl.'" class="btn btn-sm btn-primary">Modifica</a>';
            })
            // Ricordiamoci di abilitare i campi HTML
            ->rawColumns(['sur_id', 'giorni_rimanenti', 'campo_edit'])
            ->make(true);
    }

}
