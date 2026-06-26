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
- Nel log fieldControl conviene distinguere tra `ultima domanda da sistema` e `ultima domanda nel File Dati`.
- L'ultima domanda nel File Dati può essere letta scorrendo l'ultima riga utile del file `.sre` e prendendo il secondo campo.
- In `Ultimo Update`, se presente timezone `CET/CEST`, va rimossa in visualizzazione; preferire data sopra e ora sotto.
- Per tooltip Bootstrap con HTML custom, evitare stili inline nel `title`: usare `data-bs-custom-class` e CSS dedicato, altrimenti la sanitizzazione rimuove la formattazione.

Premi panel:
- La tabella premi Amazon e' `t_premidb`.
- Campi usati per import CSV Amazon: `sequenza`, `codice`, `valore`, `scadenza`, `seriale`, `status`, `user`, `pagamento`.
- CSV Amazon atteso con separatore `;`: Sequenza, Codice, Valore, Scadenza, Numero di serie.
- Il valore CSV puo' arrivare come `EUR 2,00` e viene convertito in intero.
- I codici importati devono entrare con `status = disponibile`, `user = null`, `pagamento = null`.
- I duplicati non devono essere inseriti e vanno segnalati.

## Codex usage rules

- Do not scan the entire repository unless explicitly requested.
- For normal tasks, read only the files mentioned in the prompt and directly related files.
- Before modifying files, provide a short plan.
- Prefer small, incremental changes.
- Do not perform massive refactors.
- Do not run git push, merge, deploy, or remote server commands.
- Local git add and git commit are allowed only when explicitly requested.
- Keep compatibility with Laravel 8 and PHP 7.4.23.
- Do not use PHP 8 syntax.
- Do not modify .env files.
- Do not modify storage/logs or cache files.

## Performance and Context Rules

- Never scan the entire repository unless explicitly requested.
- Never perform architecture reviews unless explicitly requested.
- Prefer reading only files mentioned in the prompt.
- If additional files are needed, explain why before reading them.
- Keep responses concise.
- Prefer summaries over detailed reports.
- For analysis tasks, inspect only the minimum number of files required.
- Do not inspect vendor/, node_modules/, storage/, bootstrap/cache/, public/build/ unless explicitly requested.
- When resuming a session, use git history and current branch status instead of re-analysing the entire project.
- Before opening large controllers (>500 lines), confirm they are required for the task.
- Do not search unrelated controllers or views.

## Response Style

- Keep answers concise.
- Prefer bullet points.
- Do not generate long reports unless requested.
- Summarize findings in less than 10 bullet points when possible.

## Memoria operativa recente

- FieldControl: nei box `Log Attivita'` e `Report giornaliero`, se non ci sono interviste conviene mostrare un empty state con icona robottino invece di lasciare tabelle vuote.
- FieldControl: nel blocco `Riepilogo interviste` la navigazione panel e' stata portata in alto con stile distinto dalle tabs di `Analisi filtrate`.
- FieldControl `Controllo Quote`: le righe aggregate `Interviste totali - ...` possono essere arricchite leggendo `quota.total_by_leg` da `resources/config.json`.
- FieldControl `Controllo Quote`: per target come `gdo_muffin_0` o `auto_donut_4` la label non deve mostrare `Risposta X`; il tooltip va invece sulle righe target dettagliate, con domanda sopra e opzione sotto, recuperando `question_id` e `option_id` dal `config.json`.
- Select SID: per select ricercabili esiste un componente condiviso `search-select` in `public/js/search-select.js` + `public/css/search-select.css`.
- Ordinamento SID: valori tipo `R2604069NO` o `R2604069IT` vanno trattati come SID numerici sulla parte iniziale dopo `R`, non come puro testo.
