<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMislabeledInteractivePanel extends Command
{
    protected $signature = 'panelquality:fix-interactive-mislabel {--dry-run : Mostra solo quante righe verrebbero corrette, senza scrivere}';

    protected $description = 'Corregge le righe t_user_quality etichettate erroneamente "Interactive" per uid non presenti in t_user_info';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $uids = DB::table('t_user_quality')
            ->where('panel', 'Interactive')
            ->distinct()
            ->pluck('uid');

        $this->info("Uid distinti con panel='Interactive': {$uids->count()}");

        $realUids = DB::table('t_user_info')
            ->whereIn('user_id', $uids)
            ->pluck('user_id');

        $fakeUids = $uids->diff($realUids)->values();

        $this->info("Uid senza corrispondenza in t_user_info (da correggere): {$fakeUids->count()}");

        if ($fakeUids->isEmpty()) {
            $this->info('Nessuna riga da correggere.');
            return self::SUCCESS;
        }

        $affectedRows = DB::table('t_user_quality')
            ->where('panel', 'Interactive')
            ->whereIn('uid', $fakeUids)
            ->count();

        $this->info("Righe t_user_quality che verrebbero aggiornate a panel='Sconosciuto': {$affectedRows}");

        if ($dryRun) {
            $this->comment('Dry-run: nessuna scrittura effettuata. Rilancia senza --dry-run per applicare.');
            return self::SUCCESS;
        }

        if (!$this->confirm("Confermi l'aggiornamento di {$affectedRows} righe?", true)) {
            $this->comment('Annullato.');
            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($fakeUids->chunk(500) as $chunk) {
            $updated += DB::table('t_user_quality')
                ->where('panel', 'Interactive')
                ->whereIn('uid', $chunk->values())
                ->update(['panel' => 'Sconosciuto', 'updated_at' => now()]);
        }

        $this->info("Fatto. Righe aggiornate: {$updated}");

        return self::SUCCESS;
    }
}
