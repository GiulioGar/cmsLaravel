<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PanelControl; 

class FieldQualityController extends Controller
{
    public function index(Request $request)
    {
        // Recuperiamo eventuali parametri GET (se necessari)
        $prj = $request->query('prj');
        $sid = $request->query('sid');

        // Recuperiamo i dati del panel, se esiste un sid
        $panelData = null;
        if (!empty($sid)) {
            $panelData = PanelControl::where('sur_id', $sid)->first();
        }

        // Ricerche in corso (stato=0, cioè aperta)
        $ricercheInCorso = DB::table('t_panel_control')
            ->where('stato', 0)
            ->orderBy('description', 'asc')
            ->get(['sur_id', 'description', 'prj']);

        // Passiamo alla view i dati che servono nella navbar
        return view('fieldQuality', compact('ricercheInCorso', 'panelData', 'prj', 'sid'));
    }
}
