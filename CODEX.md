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

Memoria operativa recente:
- FieldControl: nel grafico `Analisi filtrate` vengono mostrati solo record con quota `> 1%`; `Da lista` va sempre ordinato per ultimo, con `Interactive` sempre per primo.
- FieldControl: nel log attivita' le icone stato sono state aumentate di dimensione con classe CSS dedicata.
- FieldControl: nei box `Log Attivita'` e `Report giornaliero`, quando non ci sono interviste, viene mostrato un empty state con icona robottino e messaggio `Al momento non ci sono interviste da conteggiare.`.
- FieldControl: il blocco `Riepilogo interviste` usa la scelta panel in alto con stile dedicato, distinta dalle tabs di `Analisi filtrate`.
- Referral: nel modal bonus welcome il bottone export usa gli utenti eleggibili trovati in verifica, con CSV `email;firstName;bytes`; il bottone ora si chiama `Esporta`.
- Premi Panel: export `Email non disponibili` Paypal corretto per MySQL strict usando `groupBy` + `MAX(h.id)` invece di `distinct` + `order by h.id`.
- User Profile: nella modale anagrafica esiste il reset password forzato; la password impostata e' il prefisso della email e viene salvata in `md5`, coerente con il login attuale.
- User Profile: nella sezione `Attivita'` c'e' il contatore `Amici invitati iscritti`, letto da `t_user_info.provenienza`.
- Dashboard `index`: i grafici principali sono stati migrati a `Apache ECharts`; al momento resta in Chart.js solo il mini doughnut `Andamento` nella tabella `Progetti in corso`.
- Select SID: esiste un componente riusabile `search-select` in `public/js/search-select.js` + `public/css/search-select.css`, gia' usato in `surveys` (`+ Nuovo progetto`) e `autotest`.
- Ordinamento SID: i SID che iniziano con `R` e hanno una parte numerica iniziale, anche con suffissi tipo `NO` o `IT`, vanno ordinati per la parte numerica iniziale e non come puro testo.
