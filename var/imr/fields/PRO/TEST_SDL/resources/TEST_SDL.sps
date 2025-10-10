DATA LIST FILE 'TEST_SDL.txt' LIST(";") /
IID (F4) UID (A25) STIME (A25) ETIME (A25) LENGTH (F4) ECODE (A25) LASTVIEW (A25) Svar0 (A25)Svar1 (A25)Svar2 (A25) C6 (F4)
.
VARIABLE LABELS
	IID "Interview Identifier"
	UID "User Identifier"
	STIME "Start Time"
	ETIME "End Time"
	LENGTH "Length (secs)"
	ECODE "Exit Code"
	LASTVIEW "Last View"
	Svar0 "Svar0"
	Svar1 "Svar1"
	Svar2 "Svar2"
	C6 "C6: Se dovesse cambiare casa, ristrutturarla o semplicemente abbellirla, quale stanza vorrebbe decidere e adattare secondo le sue personali esigenze e dare il proprio contributo decorativo in modo che la rappresenti?"
.
VALUE LABELS
	C6
		1 "CUCINA"
		2 "SOGGIORNO/SALOTTO"
		3 "SALA DA BAGNO/BAGNO"
		4 "GIARDINO"
		5 "CAMERA DA LETTO"
		6 "BALCONE/TERRAZZO"
		7 "GIARDINO"
		8 "GARAGE/DEPOSITO ATTREZZI"
		9 "ORTO PRIVATO"
		10 "STUDIO/AMBIENTE PER LAVORO E/O STUDIO"
		11 "ZONA LAVANDERIA"
	/
.
