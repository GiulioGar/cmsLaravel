DATA LIST FILE 'PROFUMATOR.txt' LIST(";") /
IID (F4) UID (A25) STIME (A25) ETIME (A25) LENGTH (F4) ECODE (A25) LASTVIEW (A25) target (A25)src (A25)pid (A25) age (A256) ageRec (F4) sex (F4) q40 (F4) q40rec (F4) fumatore (F4) prodotti_0 (F4) prodotti_1 (F4) prodotti_2 (F4) prodotti_3 (F4) 
prodotti_4 (F4) prodotti_5 (F4) prodottitom (F4) Xprodotti_5 (A256) futuro_0 (F4) futuro_1 (F4) futuro_2 (F4) futuro_3 (F4) futuro_4 (F4) futuro_5 (F4) 
futurotom (F4) numeroVolte (A256) exfuma (F4) d1 (F4) d2 (F4) d3 (F4) d4 (F4) d5 (F4) d6 (F4) reddit (F4)

.
VARIABLE LABELS
	IID "Interview Identifier"
	UID "User Identifier"
	STIME "Start Time"
	ETIME "End Time"
	LENGTH "Length (secs)"
	ECODE "Exit Code"
	LASTVIEW "Last View"
	target "target"
	src "src"
	pid "pid"
	age "age: Puoi indicare la tua età esatta:"
	ageRec "ageRec: Ricodifica"
	sex "sex: Sei..."
	q40 "q40: In quale regione vivi?"
	q40rec "q40rec: In quale regione vive?"
	fumatore "fumatore: Sei un fumatore?"
	prodotti_0 "prodotti: Sigaretta Classica (tabacco)"
	prodotti_1 "prodotti: Sigaretta Elettornica da svapo generica (Aspire,Vaporesso,Veev,Voopo,ecc..)"
	prodotti_2 "prodotti: Sigaretta Elettornica da svapo usa e getta Puff (Dinner Lady,Flerbar,SaltSwitch,Yuz,ecc..) "
	prodotti_3 "prodotti: Sigaretta Elettornica - Iquos"
	prodotti_4 "prodotti: Sigaretta Elettornica- Glo"
	prodotti_5 "prodotti: Altre tipologie (specificare) "
	prodottitom "prodotti: Top of Mind"
	Xprodotti_5 "prodotti: Altre tipologie (specificare) "
	futuro_0 "futuro: Sigaretta Classica (tabacco)"
	futuro_1 "futuro: Sigaretta Elettornica da svapo generica (Aspire,Vaporesso,Veev,Voopo,ecc..)"
	futuro_2 "futuro: Sigaretta Elettornica da svapo usa e getta Puff (Dinner Lady,Flerbar,SaltSwitch,Yuz,ecc..) "
	futuro_3 "futuro: Sigaretta Elettornica - Iquos"
	futuro_4 "futuro: Sigaretta Elettornica- Glo"
	futuro_5 "futuro: Nessuna di queste"
	futurotom "futuro: Top of Mind"
	numeroVolte "numeroVolte: Considerando complessivamente un'intera giornata, quante volte in media in una giornata accendi/utilizzi una sigaretta ?"
	exfuma "exfuma: Hai fumato in passato?"
	d1 "d1: Ancora qualche domanda su di te!  Qual &egrave; il suo stato civile? "
	d2 "d2: Qual &egrave; la tua attuale situazione lavorativa? "
	d3 "d3: Qual é la tua professione/attivit&agrave;?"
	d4 "d4: Qual é il suo titolo di studio?"
	d5 "d5: Compreso/a te, quante persone vivono nella sua famiglia?"
	d6 "d6: Lei ha figli di et&agrave; compresa fino ai 18 anni che vivono in casa con Lei?"
	reddit "reddit: Puoi indicare il tuo redditto annuale?"
.
VALUE LABELS
	ageRec
		0 "18-24 anni"
		1 "25-34 anni"
		2 "35-44 anni"
		3 "45-54 anni"
		4 "55-64 anni"
		5 "65-74 anni"
		6 "75 anni o più"
	/
	sex
		0 "Uomo"
		1 "Donna"
	/
	q40
		0 "ABRUZZO"
		1 "BASILICATA"
		2 "CALABRIA"
		3 "CAMPANIA"
		4 "EMILIA-ROMAGNA"
		5 "FRIULI-VENEZIA GIULIA"
		6 "LAZIO"
		7 "LIGURIA"
		8 "LOMBARDIA"
		9 "MARCHE"
		10 "MOLISE"
		11 "PIEMONTE"
		12 "PUGLIA"
		13 "SARDEGNA"
		14 "SICILIA"
		15 "TOSCANA"
		16 "TRENTINO-ALTO ADIGE"
		17 "UMBRIA"
		18 "VALLE D'AOSTA"
		19 "VENETO"
	/
	q40rec
		0 "Nord-Ovest (Piemonte, Val d&#39;Aosta, Liguria, Lombardia;)"
		1 "Nord-est (Trentino-Alto Adige, Veneto, Friuli-Venezia Giulia, Emilia-Romagna)"
		2 "Centro (Toscana, Umbria, Marche, Lazio, Sardegna)"
		3 "Sud + Isole (Abruzzo, Molise, Puglia, Campania, Basilicata, Calabria, Sicilia)"
	/
	fumatore
		0 "Si"
		1 "No"
	/
	prodotti_0 TO prodotti_5
		0 Not Mentioned
		1 Mentioned
	/
	futuro_0 TO futuro_5
		0 Not Mentioned
		1 Mentioned
	/
	exfuma
		0 "Si"
		1 "No"
	/
	d1
		0 "Single"
		1 "Sposato/a"
		2 "Divorziato/vedovo"
		3 "Non sa/ non risponde"
	/
	d2
		0 "Lavoro a tempo pieno"
		1 "Lavoro part-time"
		2 "Lavoro in proprio"
		3 "Disoccupato ma in cerca di lavoro"
		4 "Disoccupato e non in cerca di lavoro / Inabile al lavoro"
		5 "Genitore a tempo pieno, casalingo/a"
		6 "In pensione"
		7 "Studente"
		8 "Non sa/ non risponde"
	/
	d3
		0 "imprenditore/possidente"
		1 "impiegato"
		2 "dirigente/alto funzionario/docente universitario"
		3 "libero professionista"
		4 "artista/giornalista"
		5 "insegnante militare/paramilitare (es. polizia)"
		6 "religioso/ quadro intermedio /impiegato"
		7 "commerciante/negoziante/esercente"
		8 "agente di commercio/rappresentante (autonomo)"
		9 "artigiano con azienda/ altro lavoratore autonomo/in proprio senza azienda"
		10 "agricoltore /conduttore agricoltore dipendente/bracciante"
		11 "altro"
		12 "Preferisco non rispondere"
	/
	d4
		0 "Elementare/privo di titolo"
		1 "Media inferiore"
		2 "Diploma di scuola media superiore"
		3 "Laurea"
		4 "Preferisco non rispondere"
	/
	d5
		0 "Una (solo io)"
		1 "Due"
		2 "Tre"
		3 "Quattro"
		4 "Cinque o pi&ugrave;"
	/
	d6
		0 "S&igrave;"
		1 "No"
	/
	reddit
		0 "Meno di 15.000"
		1 "Tra 15.000 e 20.000"
		2 "Tra 20.000 e 25.000"
		3 "Tra 25.000 e 30.000"
		4 "Tra 30.000 e 35.000"
		5 "Oltre i 35.000"
		6 "Preferisco non rispondere"
	/
.
