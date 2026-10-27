<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Pre-carica in cache l'esito del controllo MX/A per tutti i domini email
 * attualmente in uso nel panel. Il controllo "Email con dominio sospetto"
 * di panelUsers legge solo da questa cache durante le richieste web (mai
 * un lookup DNS dal vivo), per evitare che una cache fredda (dopo un deploy,
 * un cache:clear o la scadenza naturale) blocchi una richiesta web per
 * decine di secondi mentre controlla centinaia di domini uno a uno.
 *
 * Da schedulare (es. nightly) in app/Console/Kernel.php.
 */
class WarmEmailDomainMxCache extends Command
{
    protected $signature = 'panelusers:warm-email-domain-cache {--force : Ricontrolla anche i domini gia in cache}';

    protected $description = 'Pre-carica la cache dei controlli MX/A sui domini email del panel (usata da panelUsers senza lookup DNS live)';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $domains = DB::table('t_user_info')
            ->where('active', 1)
            ->where('confirm', 1)
            ->whereNotNull('email')
            ->where('email', 'like', '%@%')
            ->selectRaw('DISTINCT LOWER(SUBSTRING_INDEX(email, "@", -1)) as domain')
            ->pluck('domain')
            ->filter(function ($domain) {
                return $domain !== '' && strpos($domain, '.') !== false;
            });

        $this->info("Domini distinti trovati: {$domains->count()}");

        $checked = 0;
        $skipped = 0;

        foreach ($domains as $domain) {
            $key = 'email_domain_mx_' . $domain;

            if (!$force && Cache::has($key)) {
                $skipped++;
                continue;
            }

            $valid = checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
            Cache::put($key, $valid, now()->addDays(7));
            $checked++;
        }

        $this->info("Controllati: {$checked}, gia' in cache (saltati): {$skipped}");

        return self::SUCCESS;
    }
}
