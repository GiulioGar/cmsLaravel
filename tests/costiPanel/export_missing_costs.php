<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\FieldControlSreService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$svc = app(FieldControlSreService::class);

// Ricerche con almeno una complete da panel esterno (complete_ext > 0,
// mantenuto gia' da FieldControlController::updatePanelControl() - nessun
// bisogno di riparsare i .sre solo per saperlo).
$surveys = DB::table('t_panel_control')
    ->where('complete_ext', '>', 0)
    ->orderBy('sur_date', 'desc')
    ->get(['prj', 'sur_id', 'description', 'sur_date', 'complete_ext', 'stato']);

// prj+sur_id che hanno GIA' almeno un costo salvato (di qualsiasi panel).
$withCost = DB::table('t_panel_control_costs')
    ->select('prj', 'sur_id')
    ->distinct()
    ->get()
    ->map(fn($r) => $r->prj . '|' . $r->sur_id)
    ->flip();

$missing = [];

foreach ($surveys as $s) {
    $key = $s->prj . '|' . $s->sur_id;
    if (isset($withCost[$key])) {
        continue;
    }

    // Se i .sre sono disponibili in locale, proviamo a capire il nome
    // esatto del/dei panel esterno/i coinvolti (pura info, non obbligatoria:
    // in produzione dove girera' questo stesso script i .sre ci sono per
    // tutte le ricerche, qui in locale solo per alcune).
    $panelNames = '';
    $dir = $svc->resolveResultsDirectory($s->prj, $s->sur_id);
    if ($dir) {
        $files = $svc->getSreFiles($dir);
        if (!empty($files)) {
            $interviews = $svc->buildInterviewDataset($files, $s->prj, $s->sur_id);
            $summary = $svc->summarizeInterviews($interviews, count($files));
            $externalPanels = array_filter(
                array_keys($summary['panelCounts']),
                fn($name) => !in_array($name, ['Interactive', 'Da lista'], true)
            );
            $panelNames = implode(', ', $externalPanels);
        }
    }

    $missing[] = [
        'prj' => $s->prj,
        'sur_id' => $s->sur_id,
        'description' => $s->description,
        'sur_date' => $s->sur_date ? date('d/m/Y', strtotime($s->sur_date)) : '',
        'stato' => ((int) $s->stato === 1) ? 'Chiusa' : 'Aperta',
        'complete_ext' => (int) $s->complete_ext,
        'panel' => $panelNames,
    ];
}

echo "Ricerche con panel esterno: " . $surveys->count() . "\n";
echo "Gia' con un costo salvato: " . ($surveys->count() - count($missing)) . "\n";
echo "Ancora SENZA costo (da esportare): " . count($missing) . "\n";

// ---- Export Excel ----
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Costi mancanti');

$headers = ['PRJ', 'SUR_ID', 'Descrizione', 'Data', 'Stato', 'Complete esterni', 'Panel (se noto)', 'CPI', 'Costo totale', 'Referente', 'Note'];
$sheet->fromArray($headers, null, 'A1');

$headerStyle = $sheet->getStyle('A1:K1');
$headerStyle->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('404044');

$row = 2;
foreach ($missing as $m) {
    $sheet->fromArray([
        $m['prj'], $m['sur_id'], $m['description'], $m['sur_date'], $m['stato'],
        $m['complete_ext'], $m['panel'], '', '', '', '',
    ], null, 'A' . $row);
    $row++;
}

foreach (range('A', 'K') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}
$sheet->freezePane('A2');

$outPath = __DIR__ . '/costi_mancanti.xlsx';
(new Xlsx($spreadsheet))->save($outPath);
echo "Salvato in: $outPath\n";
