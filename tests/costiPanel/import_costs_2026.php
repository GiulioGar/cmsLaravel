<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use App\Models\PanelControlCost;

$files = [
    'Cint' => __DIR__ . '/2026/CINT.XLSX',
    'Norstat' => __DIR__ . '/2026/norstat.XLSX',
    'Dynata' => __DIR__ . '/2026/DYNATA.XLSX',
    'Purespectrum' => __DIR__ . '/2026/PURESPECTRUM.XLSX',
];

$clean = [];
$needsConfirm = [];
$combined = [];
$suspectDupes = [];

foreach ($files as $panelName => $path) {
    $spreadsheet = IOFactory::load($path);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, false);
    $header = array_map('trim', $rows[0]);
    $colTotale = array_search('Totale documento', $header);
    $colCommessa = array_search('Rif. Commessa - Codice', $header);

    $bySurId = [];       // sur_id => somma
    $lineAmounts = [];   // sur_id => [importi singoli]

    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (!isset($row[$colTotale]) || $row[$colTotale] === null || $row[$colTotale] === '') continue;
        $surId = trim((string) ($row[$colCommessa] ?? ''));
        if ($surId === '') continue;
        $totale = (float) $row[$colTotale];

        if (strpos($surId, '+') !== false) {
            $combined[] = ['panel' => $panelName, 'sur_ids' => $surId, 'totale' => $totale];
            continue;
        }

        $bySurId[$surId] = ($bySurId[$surId] ?? 0) + $totale;
        $lineAmounts[$surId][] = $totale;
    }

    foreach ($lineAmounts as $surId => $amounts) {
        if (count($amounts) > 1 && count($amounts) !== count(array_unique($amounts))) {
            $suspectDupes[] = ['panel' => $panelName, 'sur_id' => $surId, 'importi' => $amounts, 'somma' => round($bySurId[$surId], 2)];
        }
    }

    foreach ($bySurId as $surId => $sum) {
        // Un sur_id con importi di riga duplicati identici resta sempre
        // "da confermare", anche se risolvibile in modo univoco: potrebbe
        // essere un copia-incolla ripetuto nel file sorgente, non vogliamo
        // sommarlo per sbaglio senza una conferma esplicita.
        if (in_array($surId, array_column(array_filter($suspectDupes, fn($d) => $d['panel'] === $panelName), 'sur_id'), true)) {
            $needsConfirm[] = [
                'panel' => $panelName, 'fattura_sur_id' => $surId, 'totale' => round($sum, 2),
                'famiglia' => '(importo di riga duplicato sospetto, vedi sezione dedicata)',
            ];
            continue;
        }

        $exact = DB::table('t_panel_control')->where('sur_id', $surId)->first(['prj', 'sur_id', 'description']);
        $family = DB::table('t_panel_control')->where('sur_id', 'like', $surId . '%')->get(['prj', 'sur_id', 'description']);

        if ($exact && $family->count() === 1) {
            $clean[] = ['panel' => $panelName, 'prj' => $exact->prj, 'sur_id' => $exact->sur_id, 'totale' => round($sum, 2)];
        } elseif ($exact && $family->count() > 1) {
            $needsConfirm[] = [
                'panel' => $panelName, 'fattura_sur_id' => $surId, 'totale' => round($sum, 2),
                'famiglia' => $family->map(fn($m) => $m->prj . '/' . $m->sur_id . ' (' . $m->description . ')')->implode(' | '),
            ];
        } elseif (!$exact && $family->count() === 1) {
            $m = $family->first();
            $clean[] = ['panel' => $panelName, 'prj' => $m->prj, 'sur_id' => $m->sur_id, 'totale' => round($sum, 2)];
        } else {
            $needsConfirm[] = [
                'panel' => $panelName, 'fattura_sur_id' => $surId, 'totale' => round($sum, 2),
                'famiglia' => $family->isEmpty() ? '(nessun match, anche con prefisso)' : $family->map(fn($m) => $m->prj . '/' . $m->sur_id . ' (' . $m->description . ')')->implode(' | '),
            ];
        }
    }
}

echo "CLEAN (univoci): " . count($clean) . " - somma " . number_format(array_sum(array_column($clean, 'totale')), 2) . "\n\n";

echo "DA CONFERMARE - famiglia multi-mercato o non trovato (" . count($needsConfirm) . "):\n";
foreach ($needsConfirm as $r) {
    echo "- [{$r['panel']}] fattura={$r['fattura_sur_id']} EUR " . number_format($r['totale'], 2) . " -> {$r['famiglia']}\n";
}

echo "\nSUR_ID COMBINATI (fattura unica per piu' progetti, " . count($combined) . "):\n";
foreach ($combined as $r) {
    echo "- [{$r['panel']}] {$r['sur_ids']} EUR " . number_format($r['totale'], 2) . "\n";
}

echo "\nIMPORTI SOSPETTI DUPLICATI (stesso sur_id, stesso importo ripetuto, " . count($suspectDupes) . "):\n";
foreach ($suspectDupes as $r) {
    echo "- [{$r['panel']}] {$r['sur_id']}: righe " . implode(', ', $r['importi']) . " -> somma attuale " . number_format($r['somma'], 2) . "\n";
}

if (($argv[1] ?? '') === '--commit') {
    echo "\n--- COMMIT (solo righe CLEAN, escluse ambigue/combinate/duplicati sospetti) ---\n";
    $inserted = 0;
    DB::transaction(function () use ($clean, &$inserted) {
        foreach ($clean as $r) {
            PanelControlCost::updateOrCreate(
                ['prj' => $r['prj'], 'sur_id' => $r['sur_id'], 'panel_name' => $r['panel']],
                [
                    'costo_totale' => $r['totale'],
                    'cpi' => null,
                    'note' => 'Importato da fattura Excel ' . $r['panel'] . ' 2026 (dato storico)',
                ]
            );
            $inserted++;
        }
    });
    echo "Inserite/aggiornate $inserted righe in t_panel_control_costs.\n";
}
