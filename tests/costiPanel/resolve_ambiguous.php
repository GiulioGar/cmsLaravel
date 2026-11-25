<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use App\Models\PanelControlCost;
use App\Services\FieldControlSreService;

$svc = app(FieldControlSreService::class);

/**
 * Verifica se il panel $panelName risulta davvero coinvolto (almeno un
 * complete/contatto) nei .sre di (prj, surId), leggendo i file reali -
 * stessa logica usata da fieldControl per popolare $panelCounts.
 */
function panelInvolvedIn(FieldControlSreService $svc, string $prj, string $surId, string $panelName): bool
{
    $dir = $svc->resolveResultsDirectory($prj, $surId);
    if (!$dir) {
        return false;
    }
    $files = $svc->getSreFiles($dir);
    if (empty($files)) {
        return false;
    }
    $interviews = $svc->buildInterviewDataset($files, $prj, $surId);
    $summary = $svc->summarizeInterviews($interviews, count($files));
    $panelCounts = $summary['panelCounts'];

    return isset($panelCounts[$panelName]) && (int) ($panelCounts[$panelName]['contatti'] ?? 0) > 0;
}

/**
 * Prova a risolvere un sur_id-fattura ambiguo (piu' varianti mercato con lo
 * stesso codice base) controllando quale variante ha DAVVERO quel panel
 * coinvolto nei propri .sre - puo' succedere che mercati diversi dello
 * stesso progetto multi-market siano stati affidati a panel diversi.
 */
function resolveFamilyByPanelUsage(FieldControlSreService $svc, $family, string $panelName): ?object
{
    $matches = [];
    foreach ($family as $candidate) {
        if (panelInvolvedIn($svc, $candidate->prj, $candidate->sur_id, $panelName)) {
            $matches[] = $candidate;
        }
    }

    return count($matches) === 1 ? $matches[0] : null;
}

$cintImageBreakdown = [
    'R2501016' => 375.60, 'R2501026' => 374.40, 'R2501035' => 567.00, 'R2501046' => 1047.60,
    'R2501023' => 811.80, 'R2501031' => 566.60, 'R2501042' => 1353.60, 'R2501030' => 515.85, 'R2501028' => 4173.00,
];

$files = [
    ['Cint', __DIR__ . '/2025/cint 2025.XLSX'],
    ['Norstat', __DIR__ . '/2025/NORSTAT 2025.XLSX'],
    ['Dynata', __DIR__ . '/2025/DYNATA 2025.XLSX'],
    ['Cint', __DIR__ . '/2026/CINT.XLSX'],
    ['Norstat', __DIR__ . '/2026/norstat.XLSX'],
    ['Dynata', __DIR__ . '/2026/DYNATA.XLSX'],
    ['Purespectrum', __DIR__ . '/2026/PURESPECTRUM.XLSX'],
];

// Righe da escludere a prescindere (duplicati sospetti, confermato dall'utente
// di NON inserire finche' non verifica a mano la fattura originale).
$excludeDuplicates = [
    'Dynata' => ['R2605082'],
    'Purespectrum' => ['R2610155'],
];

$pending = []; // ['panel'=>, 'sur_id'=>, 'totale'=>]

foreach ($files as [$panelName, $path]) {
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

        if (strpos($surId, '+') !== false) {
            // fattura combinata: split 50/50 tra i due codici, ciascuna meta'
            // rientra poi nel normale processo di risoluzione.
            $parts = explode('+', $surId);
            $half = round(((float) $row[$colTotale]) / count($parts), 2);
            foreach ($parts as $p) {
                $p = trim($p);
                $bySurId[$p] = ($bySurId[$p] ?? 0) + $half;
            }
            continue;
        }

        $bySurId[$surId] = ($bySurId[$surId] ?? 0) + (float) $row[$colTotale];
    }

    if ($panelName === 'Cint' && strpos($path, '2025') !== false) {
        foreach ($cintImageBreakdown as $surId => $totale) {
            $bySurId[$surId] = ($bySurId[$surId] ?? 0) + $totale;
        }
    }

    foreach ($bySurId as $surId => $sum) {
        if (in_array($surId, $excludeDuplicates[$panelName] ?? [], true)) {
            continue;
        }

        // Gia' inserito in precedenza come "clean"? Salta.
        $exact = DB::table('t_panel_control')->where('sur_id', $surId)->first(['prj', 'sur_id']);
        $family = DB::table('t_panel_control')->where('sur_id', 'like', $surId . '%')->get(['prj', 'sur_id', 'description']);

        if (($exact && $family->count() === 1) || (!$exact && $family->count() === 1)) {
            continue; // era gia' "clean", gia' committato nei run precedenti
        }

        $pending[] = ['panel' => $panelName, 'sur_id' => $surId, 'totale' => round($sum, 2), 'family' => $family];
    }
}

$resolved = [];
$stillUnresolved = [];

foreach ($pending as $p) {
    if ($p['family']->isEmpty()) {
        $stillUnresolved[] = $p + ['motivo' => 'nessun match nel DB'];
        continue;
    }

    $match = resolveFamilyByPanelUsage($svc, $p['family'], $p['panel']);

    if ($match) {
        $resolved[] = ['panel' => $p['panel'], 'prj' => $match->prj, 'sur_id' => $match->sur_id, 'totale' => $p['totale']];
    } else {
        $stillUnresolved[] = $p + ['motivo' => 'piu\' di una variante (o nessuna) ha questo panel nei .sre'];
    }
}

echo "RISOLTI via controllo panel nei .sre (" . count($resolved) . "):\n";
foreach ($resolved as $r) {
    echo "- [{$r['panel']}] {$r['prj']}/{$r['sur_id']} EUR " . number_format($r['totale'], 2) . "\n";
}

echo "\nANCORA IRRISOLTI (" . count($stillUnresolved) . "):\n";
foreach ($stillUnresolved as $r) {
    echo "- [{$r['panel']}] fattura={$r['sur_id']} EUR " . number_format($r['totale'], 2) . " -- {$r['motivo']}\n";
    if (!$r['family']->isEmpty()) {
        echo "    famiglia: " . $r['family']->map(fn($m) => $m->prj . '/' . $m->sur_id . ' (' . $m->description . ')')->implode(' | ') . "\n";
    }
}

if (($argv[1] ?? '') === '--commit') {
    echo "\n--- COMMIT risolti ---\n";
    $inserted = 0;
    DB::transaction(function () use ($resolved, &$inserted) {
        foreach ($resolved as $r) {
            PanelControlCost::updateOrCreate(
                ['prj' => $r['prj'], 'sur_id' => $r['sur_id'], 'panel_name' => $r['panel']],
                ['costo_totale' => $r['totale'], 'cpi' => null, 'note' => 'Importato da fattura Excel ' . $r['panel'] . ' (risolto per panel coinvolto nei .sre; dato storico)']
            );
            $inserted++;
        }
    });
    echo "Inserite/aggiornate $inserted righe.\n";
}
