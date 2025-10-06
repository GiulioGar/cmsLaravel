<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\UidGeneratorService;

class AbilitaUidController extends Controller
{
    public function index()
    {
        $surveys = DB::table('t_surveys')
            ->select('sid', 'prj_name')
            ->where('status', 2)
            ->orderBy('sid', 'desc')
            ->get();

        $panels = DB::table('t_fornitoriPanel')
            ->select('id', 'panel_code', 'name', 'red_3', 'red_4', 'red_5', 'complete', 'spesa')
            ->orderBy('name', 'asc')
            ->get();

        return view('abilitaUid', compact('surveys', 'panels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sid' => 'required|string|exists:t_surveys,sid',
            'prj' => 'required|string',
            'panel_code' => 'required|integer|exists:t_fornitoriPanel,panel_code',
            'num_links' => 'required|integer|min:1|max:10000',
        ]);

        $sid = $request->sid;
        $prj = $request->prj;
        $panel = DB::table('t_fornitoriPanel')->where('panel_code', $request->panel_code)->first();

        if (!$panel) {
            return back()->withErrors(['panel_code' => 'Panel non trovato']);
        }

        $generator = new UidGeneratorService();
        $uids = $generator->generateBatch($panel->name, $request->num_links);

        $links = [];
        foreach ($uids as $uid) {
            $links[] = [
                'link' => "https://www.primisoft.com/primis/run.do?sid={$sid}&prj={$prj}&uid={$uid}&pan={$panel->panel_code}",
                'uid' => $uid,
            ];
        }

        foreach ($uids as $uid) {
            DB::table('t_respint')->insert([
                'sid' => $sid,
                'uid' => $uid,
                'status' => 0,
                'iid' => -1,
                'prj_name' => $prj,
            ]);
        }

        return redirect()->back()->with([
            'links' => $links,
            'success' => count($uids) . ' UID generati e salvati correttamente.'
        ]);
    }

    // === GESTIONE PANEL (AJAX) ===

    public function storePanel(Request $request)
    {
        $request->validate([
            'panel_code' => 'required|integer|unique:t_fornitoriPanel,panel_code',
            'name' => 'required|string|max:100',
            'red_3' => 'nullable|url',
            'red_4' => 'nullable|url',
            'red_5' => 'nullable|url',
            'complete' => 'nullable|integer',
            'spesa' => 'nullable|numeric',
        ]);

        DB::table('t_fornitoriPanel')->insert([
            'panel_code' => $request->panel_code,
            'name' => $request->name,
            'red_3' => $request->red_3,
            'red_4' => $request->red_4,
            'red_5' => $request->red_5,
            'complete' => $request->complete ?? 0,
            'spesa' => $request->spesa ?? 0.00,
        ]);

        return response()->json(['success' => true]);
    }

    public function updatePanel(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:t_fornitoriPanel,id',
            'name' => 'required|string|max:100',
            'red_3' => 'nullable|url',
            'red_4' => 'nullable|url',
            'red_5' => 'nullable|url',
            'complete' => 'nullable|integer',
            'spesa' => 'nullable|numeric',
        ]);

        DB::table('t_fornitoriPanel')->where('id', $request->id)->update([
            'name' => $request->name,
            'red_3' => $request->red_3,
            'red_4' => $request->red_4,
            'red_5' => $request->red_5,
            'complete' => $request->complete ?? 0,
            'spesa' => $request->spesa ?? 0.00,
        ]);

        return response()->json(['success' => true]);
    }

    public function deletePanel($id)
    {
        DB::table('t_fornitoriPanel')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }
}
