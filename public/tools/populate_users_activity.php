<?php
ini_set('memory_limit', '2048M');
set_time_limit(0);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$baseUrl = config('services.primis.base_url');
$token   = config('services.primis.token');

echo "=== Inizio popolamento t_users_activity ===\n";

// ==============================
// 1️⃣ Leggiamo solo le survey utili:
//     - panel = 1
//     - non già presenti in t_users_activity
// ==============================
$surveys = DB::table('t_panel_control')
    ->select('sur_id', 'prj')
    ->whereNotNull('sur_id')
    ->whereNotNull('prj')
    ->where('panel', '=', 1)
    ->whereNotIn('sur_id', function ($q) {
        $q->select('sid')->from('t_users_activity');
    })
    ->get();

$total = $surveys->count();
$counter = 0;

echo "Trovate {$total} survey da elaborare.\n";

foreach ($surveys as $s) {
    $sid = trim($s->sur_id);
    $prj = trim($s->prj);
    $counter++;

    echo "\n({$counter}/{$total}) Elaboro: {$prj} / {$sid} ...\n";

    $url = "{$baseUrl}projects/{$prj}/surveys/{$sid}/results";

    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json'
        ])->get($url);

        if ($response->failed()) {
            echo "⚠️  Errore API per {$sid} [status {$response->status()}]\n";
            continue;
        }

        $body = $response->body();

        // Suddividi la risposta in righe
        $rows = preg_split("/\r\n|\n|\r/", trim($body));

        $inserted = 0;

        foreach ($rows as $line) {
            if (empty(trim($line))) continue;

            $parts = explode(';', $line);

            // Gestione presenza del campo versione (es. "2.0;...")
            if (count($parts) > 0 && strpos($parts[0], '2.') === 0) {
                array_shift($parts);
            }

            if (count($parts) < 6) continue;

            $uid        = trim($parts[1]);
            $endDateRaw = trim($parts[3]);
            $status     = (int) trim($parts[5]);

            // Verifica esistenza utente
            $exists = DB::table('t_user_info')->where('user_id', $uid)->exists();
            if (!$exists) continue;

            try {
                $date = Carbon::createFromFormat('d/m/Y H:i:s T', $endDateRaw)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $date = null;
            }

            DB::table('t_users_activity')->insert([
                'sid'         => $sid,
                'uid'         => $uid,
                'status'      => $status,
                'prj_name'    => $prj,
                'last_update' => $date,
            ]);

            $inserted++;
        }

        echo "✅ Completato {$sid} ({$inserted} record inseriti)\n";

    } catch (\Exception $e) {
        echo "❌ Errore {$sid}: " . $e->getMessage() . "\n";
        continue;
    }
}

echo "\n=== Fine popolamento t_users_activity ===\n";
