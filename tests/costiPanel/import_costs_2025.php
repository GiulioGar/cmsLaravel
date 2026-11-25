<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use App\Models\PanelControlCost;

$files = [
    'Cint' => __DIR__ . '/2025/cint 2025.XLSX',
    'Norstat' => __DIR__ . '/2025/NORSTAT 2025.XLSX',
    'Dynata' => __DIR__ . '/2025/DYNATA 2025.XLSX',
];

// Due righe del file Cint non avevano "Rif. Commessa" in testo ma uno
// screenshot incollato nella cella con il dettaglio per commessa (letto a
// vista, somme verificate contro i due totali originali 5096.60/4688.85).
$cintImageBreakdown = [
    'R2501016' => 375.60,
    'R2501026' => 374.40,
    'R2501035' => 567.00,
    'R2501046' => 1047.60,
    'R2501023' => 811.80,
    'R2501031' => 566.60,
    'R2501042' => 1353.60,
    'R2501030' => 515.85,
    'R2501028' => 4173.00,
];

$clean = [];
$needsConfirm = [];

foreach ($files as $panelName => $path) {
    $spreadsheet = IOFactory::load($path);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, false);
    $header = array_map('trim', $rows[0]);
    $colTotale = array_search('Totale documento', $header);
    $colCommessa = array_search('Rif. Commessa - Codice', $header);

    $bySurId = [];
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (!isset($row[$colTotale]) || $row[$colTotale] === null || $row[$colTotale] === '') continue;
        $surId = trim((string) ($row[$colCommessa] ?? ''));
        if ($surId === '') continue;
        $bySurId[$surId] = ($bySurId[$surId] ?? 0) + (float) $row[$colTotale];
    }

    if ($panelName === 'Cint') {
        foreach ($cintImageBreakdown as $surId => $totale) {
            $bySurId[$surId] = ($bySurId[$surId] ?? 0) + $totale;
        }
    }

    foreach ($bySurId as $surId => $sum) {
        $exact = DB::table('t_panel_control')->where('sur_id', $surId)->first(['prj', 'sur_id', 'description']);
        $family = DB::table('t_panel_control')->where('sur_id', 'like', $surId . '%')->get(['prj', 'sur_id', 'description']);

        if ($exact && $family->count() === 1) {
            $clean[] = ['panel' => $panelName, 'prj' => $exact->prj, 'sur_id' => $exact->sur_id, 'totale' => round($sum, 2)];
        } elseif ($exact && $family->count() > 1) {
            $needsConfirm[] = [
                'panel' => $panelName, 'fattura_sur_id' => $surId, 'totale' => round($sum, 2),
                'desc_base' => $exact->description,
                'famiglia' => $family->map(fn($m) => $m->prj . '/' . $m->sur_id . ' (' . $m->description . ')')->implode(' | '),
            ];
        } elseif (!$exact && $family->count() === 1) {
            $m = $family->first();
            $clean[] = ['panel' => $panelName, 'prj' => $m->prj, 'sur_id' => $m->sur_id, 'totale' => round($sum, 2)];
        } else {
            $needsConfirm[] = [
                'panel' => $panelName, 'fattura_sur_id' => $surId, 'totale' => round($sum, 2),
                'desc_base' => null,
                'famiglia' => $family->isEmpty() ? '(nessun match, anche con prefisso)' : $family->map(fn($m) => $m->prj . '/' . $m->sur_id . ' (' . $m->description . ')')->implode(' | '),
            ];
        }
    }
}

echo "CLEAN (univoci, nessuna famiglia multi-mercato): " . count($clean) . "\n";
$sumClean = array_sum(array_column($clean, 'totale'));
echo "Somma: " . number_format($sumClean, 2) . "\n\n";

echo "DA CONFERMARE (" . count($needsConfirm) . "):\n";
foreach ($needsConfirm as $r) {
    echo "- [{$r['panel']}] fattura={$r['fattura_sur_id']} EUR " . number_format($r['totale'], 2) . "\n";
    echo "    famiglia: {$r['famiglia']}\n";
}

if (($argv[1] ?? '') === '--commit') {
    echo "\n--- COMMIT (solo righe CLEAN, le ambigue restano escluse) ---\n";
    $inserted = 0;
    DB::transaction(function () use ($clean, &$inserted) {
        foreach ($clean as $r) {
            PanelControlCost::updateOrCreate(
                ['prj' => $r['prj'], 'sur_id' => $r['sur_id'], 'panel_name' => $r['panel']],
                [
                    'costo_totale' => $r['totale'],
                    'cpi' => null,
                    'note' => 'Importato da fattura Excel ' . $r['panel'] . ' 2025 (dato storico)',
                ]
            );
            $inserted++;
        }
    });
    echo "Inserite/aggiornate $inserted righe in t_panel_control_costs.\n";
}
