<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PanelControl;

class FieldControlController extends Controller
{
    public function index(Request $request)
    {
        $prj = $request->query('prj');
        $sid = $request->query('sid');

        // Recupera i dati dal database
        $panelData = PanelControl::where('sur_id', $sid)->first();

        return view('fieldControl', compact('prj', 'sid', 'panelData'));
    }
}

