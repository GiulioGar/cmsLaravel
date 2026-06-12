# Istruzioni progetto CMS

Stack:
- Laravel 8
- PHP 7.4.23
- MySQL
- Blade
- Bootstrap
- DataTables server-side

Regole:
- Non usare sintassi PHP 8.
- Non modificare il database senza proporre prima la migration.
- Preferire controller snelli, service class e query ottimizzate.
- Evitare breaking changes sulle route esistenti.
- Non toccare file .env.
- Non modificare storage/logs/laravel.log.
- Prima di proporre modifiche, spiegare i file coinvolti.
- Dopo ogni modifica, indicare come testarla.

Convenzioni Blade:
- Il layout usa @section('scripts'), non @push('scripts').

File .sre:
- La prima colonna può contenere versione tipo 2.0.
- Se presente, gli indici dei campi vanno scalati.
- Campi principali: prj, sid, iid, uid, data inizio, data fine, durata, status, ultima domanda.

Premi panel:
- La tabella premi Amazon e' `t_premidb`.
- Campi usati per import CSV Amazon: `sequenza`, `codice`, `valore`, `scadenza`, `seriale`, `status`, `user`, `pagamento`.
- CSV Amazon atteso con separatore `;`: Sequenza, Codice, Valore, Scadenza, Numero di serie.
- Il valore CSV puo' arrivare come `EUR 2,00` e viene convertito in intero.
- I codici importati devono entrare con `status = disponibile`, `user = null`, `pagamento = null`.
- I duplicati non devono essere inseriti e vanno segnalati.
