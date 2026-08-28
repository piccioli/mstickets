# Registro degli esiti

> Torna a [`README.md`](README.md) · Matrice di tracciabilità: [`01-matrice-tracciabilita.md`](01-matrice-tracciabilita.md)

Tabella da compilare durante il collaudo, una riga per test. Il campo "Esito" accetta solo uno tra
`PASS`, `FAIL`, `BLOCKED`, `NOT APPLICABLE` (vedi §17 di `00-istruzioni-generali.md` per i criteri generali
e la sezione "Criterio di superamento" di ciascun test in `02-fase-0.md`/`03-fase-1.md`/`04-fase-1a.md`/
`05-fase-2.md`/`06-fase-3.md`/`07-fase-4.md`/`10-fase-5.md`/`13-fase-6.md`/`14-fase-7.md`/`15-fase-8.md`
per il criterio specifico). Il campo "Anomalia" riporta l'ID assegnato secondo
§19 di `00-istruzioni-generali.md` (es. `AN-001`), lasciare vuoto se non ci sono anomalie da segnalare per
quel test.

## Fase 0 (Fondazioni) — 56 test

| ID | Titolo | Esito | Tester | Data | Versione | Evidenza | Anomalia | Note |
|---|---|---|---|---|---|---|---|---|
| F0-01 | Un utente con un ruolo applicativo valido accede al pannello |  |  |  |  |  |  |  |
| F0-02 | Un utente senza nessuno dei 5 ruoli applicativi non accede al pannello |  |  |  |  |  |  |  |
| F0-03 | Un utente disattivato non accede al pannello anche con un ruolo valido |  |  |  |  |  |  |  |
| F0-04 | Le query di selezione utenti escludono gli utenti disattivati |  |  |  |  |  |  |  |
| F0-05 | Il catalogo ruoli contiene esattamente i 5 ruoli previsti |  |  |  |  |  |  |  |
| F0-06 | Il catalogo permessi contiene esattamente i permessi previsti |  |  |  |  |  |  |  |
| F0-07 | Le tabelle di ruoli/permessi sono pubblicate correttamente: guard unico web, nessuna gestione a team |  |  |  |  |  |  |  |
| F0-08 | Il seeder di ruoli/permessi assegna a ciascun ruolo esattamente i permessi previsti dalla matrice |  |  |  |  |  |  |  |
| F0-09 | I permessi riservati non sono assegnati a nessun ruolo salvo Admin |  |  |  |  |  |  |  |
| F0-10 | Il seeder di ruoli/permessi è idempotente e revoca un permesso/ruolo rimosso dal catalogo senza lasciarlo orfano |  |  |  |  |  |  |  |
| F0-11 | Un utente senza il permesso richiesto riceve sempre accesso negato; con il permesso viene autorizzato |  |  |  |  |  |  |  |
| F0-12 | Un admin può assegnare/revocare ruoli e permessi diretti di un utente dalla UI; la risorsa Ruoli resta di sola lettura |  |  |  |  |  |  |  |
| F0-13 | La scheda utente mostra i permessi effettivi con la provenienza (dal ruolo oppure diretto) |  |  |  |  |  |  |  |
| F0-14 | La tabella utenti rispetta i vincoli richiesti: email unica case-insensitive, soft delete |  |  |  |  |  |  |  |
| F0-15 | Le organizzazioni collegano gli utenti con vincolo di unicità sulla coppia organizzazione/utente |  |  |  |  |  |  |  |
| F0-16 | Le organizzazioni sono protette da policy deny-by-default |  |  |  |  |  |  |  |
| F0-17 | La tabella `tickets` rispetta colonne, default e relazioni richieste |  |  |  |  |  |  |  |
| F0-18 | I messaggi di un ticket hanno un identificativo pubblico univoco e vengono eliminati a cascata col ticket |  |  |  |  |  |  |  |
| F0-19 | Lo storico dei ticket registra un diff strutturato dei cambiamenti, non il valore grezzo del campo |  |  |  |  |  |  |  |
| F0-20 | Al massimo una visualizzazione per (ticket, utente, giorno) è ammessa a livello di database |  |  |  |  |  |  |  |
| F0-21 | Un utente non può essere aggiunto due volte come partecipante dello stesso ticket |  |  |  |  |  |  |  |
| F0-22 | Il collegamento ticket/tag ha un vincolo reale a livello di database, non solo applicativo |  |  |  |  |  |  |  |
| F0-23 | Le righe di ore lavorate sono uniche per (giorno, utente, ticket) |  |  |  |  |  |  |  |
| F0-24 | Lo stato del ticket copre esattamente i 12 valori previsti (incluso "Testing", non "Test") |  |  |  |  |  |  |  |
| F0-25 | I tag e le pagine di documentazione rispettano slug univoco e collegamento opzionale reciproco |  |  |  |  |  |  |  |
| F0-26 | Un messaggio interno del ticket non è mai visibile senza il permesso dedicato |  |  |  |  |  |  |  |
| F0-27 | Lo storico del ticket è visualizzabile solo con il permesso dedicato e non è mai scrivibile manualmente |  |  |  |  |  |  |  |
| F0-28 | La gestione dei partecipanti al ticket è riservata a chi ha il permesso di assegnazione |  |  |  |  |  |  |  |
| F0-29 | Le visualizzazioni del ticket sono protette dalla stessa policy di visualizzazione del ticket |  |  |  |  |  |  |  |
| F0-30 | Le ore lavorate registrate sono protette da policy dedicata (visualizzazione vs modifica) |  |  |  |  |  |  |  |
| F0-31 | I tag sono protetti da policy deny-by-default |  |  |  |  |  |  |  |
| F0-32 | Le pagine di documentazione distinguono correttamente accesso cliente vs interno |  |  |  |  |  |  |  |
| F0-33 | I report di attività sono protetti da policy deny-by-default |  |  |  |  |  |  |  |
| F0-34 | Le opportunità di fundraising sono protette da policy deny-by-default |  |  |  |  |  |  |  |
| F0-35 | I messaggi email sono protetti da policy deny-by-default |  |  |  |  |  |  |  |
| F0-36 | Un report di attività deve avere esattamente un proprietario (utente oppure organizzazione) |  |  |  |  |  |  |  |
| F0-37 | Le opportunità di fundraising rispettano i default e le relazioni richieste |  |  |  |  |  |  |  |
| F0-38 | I messaggi email hanno un identificativo pubblico univoco (ULID) distinto dalla chiave primaria |  |  |  |  |  |  |  |
| F0-39 | Le preferenze di notifica sono uniche per (utente, tipo di notifica, canale) |  |  |  |  |  |  |  |
| F0-40 | Le tabelle di infrastruttura per l'importazione rispettano lo schema richiesto |  |  |  |  |  |  |  |
| F0-41 | Il comando diagnostico segnala l'esito di ogni controllo con codice di uscita coerente |  |  |  |  |  |  |  |
| F0-42 | Il controllo delle variabili ambiente obbligatorie segnala ogni variabile mancante o vuota |  |  |  |  |  |  |  |
| F0-43 | Il controllo di scrittura delle directory storage rilevanti passa su un ambiente pulito |  |  |  |  |  |  |  |
| F0-44 | L'utente di sistema viene creato se assente e non consente mai l'accesso al pannello |  |  |  |  |  |  |  |
| F0-45 | Le feature flag delle automazioni schedulate sono tutte disattivate di default |  |  |  |  |  |  |  |
| F0-46 | Il tema del pannello deriva dai token del design system, non da valori scritti a mano |  |  |  |  |  |  |  |
| F0-47 | `v1:import --anonymize` popola un ambiente locale completo con dati reali anonimizzati |  |  |  |  |  |  |  |
| F0-48 | Una seconda esecuzione di `v1:import` non duplica nulla (idempotenza) |  |  |  |  |  |  |  |
| F0-49 | L'analizzatore di chiavi esterne orfane conta correttamente le righe orfane e ignora i valori nulli |  |  |  |  |  |  |  |
| F0-50 | L'analizzatore di email duplicate individua i duplicati che differiscono solo per maiuscole/minuscole |  |  |  |  |  |  |  |
| F0-51 | L'analizzatore del changes di story_logs conta i JSON interpretabili e la distribuzione delle chiavi |  |  |  |  |  |  |  |
| F0-52 | L'analizzatore di customer_request separa correttamente un elenco HTML in messaggi distinti |  |  |  |  |  |  |  |
| F0-53 | L'analizzatore dei ruoli utente v1 distingue ruoli JSON, ruoli scalari e valori nulli/sconosciuti |  |  |  |  |  |  |  |
| F0-54 | L'analizzatore delle incongruenze stato/timestamp trova le righe in uno stato che richiede una data assente |  |  |  |  |  |  |  |
| F0-55 | L'analizzatore della gerarchia story_story individua le incongruenze rispetto a stories.parent_id in entrambe le direzioni |  |  |  |  |  |  |  |
| F0-56 | L'analizzatore dei tag polimorfici raggruppa i taggable_type e conta quelli diversi da Documentation |  |  |  |  |  |  |  |

## Fase 1 (Ticketing core) — 74 test

| ID | Titolo | Esito | Tester | Data | Versione | Evidenza | Anomalia | Note |
|---|---|---|---|---|---|---|---|---|
| F1-01 | Percorso principale completo: da "Nuovo" a "Completato" passando per ogni stato |  |  |  |  |  |  |  |
| F1-02 | Percorso senza collaudo interno: da "Nuovo" a "Completato" saltando "In test" e "Testato" |  |  |  |  |  |  |  |
| F1-03 | Matrice completa delle transizioni: ammesse e vietate per stato, attore e condizioni |  |  |  |  |  |  |  |
| F1-04 | Auto-assegnazione: un developer si assegna un ticket nuovo, ma non può assegnarlo a un collega |  |  |  |  |  |  |  |
| F1-05 | La transizione verso "In test" richiede un tester assegnato |  |  |  |  |  |  |  |
| F1-06 | La transizione verso "In attesa" richiede un motivo di attesa non vuoto |  |  |  |  |  |  |  |
| F1-07 | La transizione verso "Problema" richiede un motivo del problema non vuoto |  |  |  |  |  |  |  |
| F1-08 | Un ticket che ha già dei figli non può diventare figlio di un altro ticket |  |  |  |  |  |  |  |
| F1-09 | La creazione di un ticket lo porta in stato "Nuovo" e registra uno storico con l'utente autore |  |  |  |  |  |  |  |
| F1-10 | Una transizione vietata non scrive nulla e restituisce un errore leggibile |  |  |  |  |  |  |  |
| F1-11 | Portare un ticket "In lavorazione" retrocede automaticamente gli altri ticket in lavorazione dello stesso assegnatario |  |  |  |  |  |  |  |
| F1-12 | Se la retrocessione automatica di un altro ticket fallisce, l'intera operazione viene annullata |  |  |  |  |  |  |  |
| F1-13 | L'assegnazione di un ticket a un utente registra uno storico con l'assegnatario precedente e quello nuovo |  |  |  |  |  |  |  |
| F1-14 | Un cambio della descrizione del ticket non salva mai il testo nello storico, solo il fatto che sia cambiato |  |  |  |  |  |  |  |
| F1-15 | Il cambio di stato si propaga ai ticket figli diretti solo se richiesto esplicitamente dall'utente |  |  |  |  |  |  |  |
| F1-16 | Un ticket figlio la cui transizione non è ammessa viene saltato, con motivo, senza bloccare gli altri figli |  |  |  |  |  |  |  |
| F1-17 | Un developer con permesso limitato agli assegnati non può aggiornare un ticket di cui non è assegnatario né tester |  |  |  |  |  |  |  |
| F1-18 | Un cliente vede solo i ticket di cui è il richiedente, mai quelli di altri clienti |  |  |  |  |  |  |  |
| F1-19 | Un messaggio marcato come interno non è mai raggiungibile da un cliente, nemmeno tramite accesso diretto |  |  |  |  |  |  |  |
| F1-20 | Pubblicare un messaggio produce HTML formattato sanitizzato e un corpo testuale derivato |  |  |  |  |  |  |  |
| F1-21 | L'autore di un messaggio viene aggiunto ai partecipanti, senza righe duplicate |  |  |  |  |  |  |  |
| F1-22 | I destinatari calcolati del messaggio combinano partecipanti/richiedente/assegnatario/tester, deduplicati, escluso l'autore |  |  |  |  |  |  |  |
| F1-23 | Un messaggio del richiedente su un ticket "In attesa" lo riporta allo stato precedente |  |  |  |  |  |  |  |
| F1-24 | Un messaggio del richiedente su un ticket assegnato o in lavorazione lo riporta a "Da fare" |  |  |  |  |  |  |  |
| F1-25 | Uno script incorporato in un messaggio viene rimosso interamente, mai lasciato inline |  |  |  |  |  |  |  |
| F1-26 | Un file di tipo ammesso viene caricato correttamente e salvato su disco privato |  |  |  |  |  |  |  |
| F1-27 | Un file con estensione non ammessa o oltre la dimensione massima viene rifiutato |  |  |  |  |  |  |  |
| F1-28 | La rimozione di un allegato che non appartiene al messaggio indicato viene rifiutata |  |  |  |  |  |  |  |
| F1-29 | Un utente che non può vedere il ticket non può scaricarne un allegato, nemmeno conoscendo il link diretto |  |  |  |  |  |  |  |
| F1-30 | Un allegato SVG viene servito sanitizzato: lo script incorporato viene rimosso prima del download |  |  |  |  |  |  |  |
| F1-31 | La lista di tipi e dimensioni ammessi per gli allegati è unica e condivisa, non duplicata |  |  |  |  |  |  |  |
| F1-32 | La prima visualizzazione di un ticket nel giorno crea un nuovo record di visualizzazione |  |  |  |  |  |  |  |
| F1-33 | Visualizzazioni entro la soglia non aggiornano il contatore, oltre la soglia lo aggiornano |  |  |  |  |  |  |  |
| F1-34 | La registrazione di una visualizzazione non produce mai una voce nello storico del ticket |  |  |  |  |  |  |  |
| F1-35 | Il calcolo dei minuti lavorati su un intervallo chiuso rispetta la finestra oraria configurata |  |  |  |  |  |  |  |
| F1-36 | Il weekend viene escluso dal calcolo e le ore vengono limitate alla finestra lavorativa |  |  |  |  |  |  |  |
| F1-37 | Un ticket ancora in lavorazione ha le ore limitate a un tetto configurato, non proiettate a oggi |  |  |  |  |  |  |  |
| F1-38 | Il ricalcolo massivo aggiorna le ore lavorate del ticket in modo idempotente |  |  |  |  |  |  |  |
| F1-39 | Un cambio di stato accoda il ricalcolo delle ore, unendo più cambi ravvicinati in un solo ricalcolo |  |  |  |  |  |  |  |
| F1-40 | Il comando di ricalcolo massivo permette di ricalcolare un singolo ticket o un intervallo di date |  |  |  |  |  |  |  |
| F1-41 | Un cliente che manipola direttamente il modulo non può alterare alcun campo riservato allo staff |  |  |  |  |  |  |  |
| F1-42 | Le sezioni riservate allo staff sono nascoste a un cliente nella vista di dettaglio del ticket |  |  |  |  |  |  |  |
| F1-43 | Un developer che porta un ticket nuovo ad "assegnato" si auto-assegna silenziosamente, senza dover scegliere sé stesso |  |  |  |  |  |  |  |
| F1-44 | La transizione verso "in test" richiede la scelta di un tester e fallisce leggibilmente se assente |  |  |  |  |  |  |  |
| F1-45 | Una transizione di stato vietata mostra all'utente il messaggio di errore leggibile tramite notifica |  |  |  |  |  |  |  |
| F1-46 | Postare un nuovo messaggio dalla scheda ticket lo fa comparire nella conversazione |  |  |  |  |  |  |  |
| F1-47 | La gestione dei partecipanti al ticket dalla UI è visibile solo a chi ha il permesso di assegnazione |  |  |  |  |  |  |  |
| F1-48 | La selezione di un ticket padre non valido mostra il messaggio leggibile della regola di profondità massima |  |  |  |  |  |  |  |
| F1-49 | L'apertura della pagina di dettaglio di un ticket registra una visualizzazione |  |  |  |  |  |  |  |
| F1-50 | La vista "Richieste attive" include ed esclude correttamente i ticket attesi |  |  |  |  |  |  |  |
| F1-51 | La vista "In attesa" ordina i ticket dal più vecchio, per giorni di attesa decrescenti |  |  |  |  |  |  |  |
| F1-52 | La vista "Assegnati a me" mostra solo i ticket assegnati all'utente corrente |  |  |  |  |  |  |  |
| F1-53 | La vista "Da testare" mostra solo i ticket in cui l'utente corrente è il tester |  |  |  |  |  |  |  |
| F1-54 | La vista "In test" mostra solo i ticket nello stato di collaudo interno |  |  |  |  |  |  |  |
| F1-55 | La vista "Problemi" mostra solo i ticket nello stato problema |  |  |  |  |  |  |  |
| F1-56 | La vista "Backlog" mostra solo i ticket nello stato backlog |  |  |  |  |  |  |  |
| F1-57 | La vista "Archivio" (staff) mostra solo i ticket conclusi |  |  |  |  |  |  |  |
| F1-58 | La vista "Interni" mostra solo i ticket senza un richiedente esterno |  |  |  |  |  |  |  |
| F1-59 | La vista "I miei ticket" per un cliente mostra solo le proprie richieste non ancora concluse |  |  |  |  |  |  |  |
| F1-60 | La vista "Archivio" per un cliente mostra solo le proprie richieste concluse o rifiutate |  |  |  |  |  |  |  |
| F1-61 | La vista "Nuovi" mostra solo i ticket appena creati non ancora assegnati |  |  |  |  |  |  |  |
| F1-62 | La vista "In lavorazione" mostra solo i ticket nello stato progress |  |  |  |  |  |  |  |
| F1-63 | La vista "Tutti i ticket di clienti" mostra tutti i ticket con un richiedente esterno, indipendentemente dallo stato |  |  |  |  |  |  |  |
| F1-64 | Il filtro per stato permette la selezione multipla |  |  |  |  |  |  |  |
| F1-65 | Il filtro per organizzazione del richiedente restringe correttamente la lista |  |  |  |  |  |  |  |
| F1-66 | I filtri "senza tag" e "con più di un tag" restituiscono le liste corrette |  |  |  |  |  |  |  |
| F1-67 | Il filtro periodo restringe la lista per intervallo di data di creazione o di completamento |  |  |  |  |  |  |  |
| F1-68 | I filtri si combinano correttamente con una vista/tab già attiva, senza sostituirla |  |  |  |  |  |  |  |
| F1-69 | La vista di lavoro raggruppa in colonne i ticket visibili per stato, rispettando la visibilità per ruolo |  |  |  |  |  |  |  |
| F1-70 | Il selettore di assegnatario permette di vedere la vista di lavoro di un collega |  |  |  |  |  |  |  |
| F1-71 | Staff atterra sulla vista di lavoro dopo il login; un cliente resta sulla propria dashboard |  |  |  |  |  |  |  |
| F1-72 | Le ore lavorate calcolate end-to-end su un intero ciclo di vita del ticket sono coerenti con i cambi di stato reali |  |  |  |  |  |  |  |
| F1-73 | Manomettere il contesto di una transizione con auto-assegnazione non permette di assegnare il ticket a un altro utente |  |  |  |  |  |  |  |
| F1-74 | Una transizione vietata tentata direttamente contro l'azione di cambio stato viene rifiutata e non scrive nulla |  |  |  |  |  |  |  |

## Fase 1A (Landing, Login, Recupero password) — 16 test

| ID | Titolo | Esito | Tester | Data | Versione | Evidenza | Anomalia | Note |
|---|---|---|---|---|---|---|---|---|
| F1A-01 | La landing "/" è raggiungibile da un visitatore anonimo con una sola CTA |  |  |  |  |  |  |  |
| F1A-02 | Un utente con sessione attiva che visita "/" viene rimandato alla dashboard |  |  |  |  |  |  |  |
| F1A-03 | Aspetto della pagina di login conforme al design Montagna Servizi |  |  |  |  |  |  |  |
| F1A-04 | Credenziali corrette autenticano e portano alla dashboard |  |  |  |  |  |  |  |
| F1A-05 | Credenziali errate mostrano un messaggio di errore e non autenticano |  |  |  |  |  |  |  |
| F1A-06 | Il toggle "Mostra/Nascondi password" funziona |  |  |  |  |  |  |  |
| F1A-07 | "Salva per le prossime sessioni" mantiene l'accesso dopo la chiusura del browser |  |  |  |  |  |  |  |
| F1A-08 | Dopo 5 tentativi di login falliti, il sesto viene bloccato temporaneamente |  |  |  |  |  |  |  |
| F1A-09 | Richiesta di reset con un'email registrata invia il link ed è visibile su Mailpit |  |  |  |  |  |  |  |
| F1A-10 | Richiesta di reset con un'email inesistente non rivela l'assenza dell'utente |  |  |  |  |  |  |  |
| F1A-11 | "Invia di nuovo" immediato è bloccato dal throttling nativo (60 secondi) |  |  |  |  |  |  |  |
| F1A-12 | Impostare una nuova password con un token valido, rispettando le regole reali |  |  |  |  |  |  |  |
| F1A-13 | Una password che non rispetta le regole reali viene rifiutata |  |  |  |  |  |  |  |
| F1A-14 | Un link di reset già usato o inesistente viene rifiutato |  |  |  |  |  |  |  |
| F1A-15 | Un link di reset scaduto (oltre 60 minuti) viene rifiutato |  |  |  |  |  |  |  |
| F1A-16 | Le pagine pubbliche usano il design system "marketing", il pannello interno resta sul tema teal |  |  |  |  |  |  |  |

## Fase 2 (Importazione dal v1 — ETL) — 74 test

| ID | Titolo | Esito | Tester | Data | Versione | Evidenza | Anomalia | Note |
|---|---|---|---|---|---|---|---|---|
| F2-01 | Il runner risolve l'ordine di esecuzione dalle dipendenze dichiarate degli stage, non dall'ordine di registrazione |  |  |  |  |  |  |  |
| F2-02 | Una dipendenza circolare tra stage viene rifiutata esplicitamente |  |  |  |  |  |  |  |
| F2-03 | Gli stage vengono eseguiti nell'ordine di dipendenza e i conteggi sono registrati su import_runs.stages |  |  |  |  |  |  |  |
| F2-04 | La modalità --dry-run non scrive righe sulla tabella di destinazione |  |  |  |  |  |  |  |
| F2-05 | --truncate è rifiutato esplicitamente in un ambiente di produzione |  |  |  |  |  |  |  |
| F2-06 | "editor" non è un ruolo: viene segnalato separatamente, mai incluso nei roles |  |  |  |  |  |  |  |
| F2-07 | Gli utenti v1 vengono importati in v2 con l'id preservato e le colonne mappate |  |  |  |  |  |  |  |
| F2-08 | Rieseguire lo stage sullo stesso dump è idempotente: la seconda esecuzione si limita a saltare |  |  |  |  |  |  |  |
| F2-09 | Un ruolo riconosciuto viene assegnato tramite Spatie |  |  |  |  |  |  |  |
| F2-10 | "editor" concede i permessi diretti sulla documentazione invece di un ruolo, ed è segnalato se era l'unico ruolo presente |  |  |  |  |  |  |  |
| F2-11 | Le organizzazioni v1 vengono importate in v2 con l'id preservato e le colonne mappate |  |  |  |  |  |  |  |
| F2-12 | Rieseguire lo stage sullo stesso dump è idempotente: la seconda esecuzione si limita a saltare |  |  |  |  |  |  |  |
| F2-13 | Una membership che referenzia un'organizzazione v2 inesistente viene segnalata, non manda in crash lo stage |  |  |  |  |  |  |  |
| F2-14 | Le documentation v1 vengono importate in v2 documentation_pages con l'id preservato e le colonne mappate |  |  |  |  |  |  |  |
| F2-15 | Viene generato uno slug provvisorio univoco quando due documentation v1 condividono lo stesso nome |  |  |  |  |  |  |  |
| F2-16 | Il legame con una Documentation viene preservato come foreign key esplicita documentation_id |  |  |  |  |  |  |  |
| F2-17 | Un legame a una Documentation verso una pagina v2 inesistente viene ridotto a tag semplice e segnalato, non manda in crash lo stage |  |  |  |  |  |  |  |
| F2-18 | Una story v1 viene importata nei ticket v2 con l'id preservato e la mappatura principale applicata |  |  |  |  |  |  |  |
| F2-19 | status_changed_at viene derivato dal più recente cambio di stato in story_logs |  |  |  |  |  |  |  |
| F2-20 | Per un ticket in waiting, previous_status risale i log fino al primo stato diverso da waiting/problem |  |  |  |  |  |  |  |
| F2-21 | Un riferimento utente verso un utente v2 inesistente viene azzerato e segnalato, non manda in crash lo stage |  |  |  |  |  |  |  |
| F2-22 | Rieseguire lo stage sullo stesso dump è idempotente: la seconda esecuzione si limita a saltare |  |  |  |  |  |  |  |
| F2-23 | Una gerarchia coerente a un livello da stories.parent_id viene applicata così com'è |  |  |  |  |  |  |  |
| F2-24 | Una gerarchia a 2+ livelli viene appiattita sull'antenato più in alto e segnalata |  |  |  |  |  |  |  |
| F2-25 | Un riferimento al genitore verso un ticket v2 inesistente viene azzerato e segnalato, non manda in crash lo stage |  |  |  |  |  |  |  |
| F2-26 | La pivot v1 ticket<->tag viene importata in v2, ignorando il lato Documentation |  |  |  |  |  |  |  |
| F2-27 | Un legame a un tag v2 inesistente viene segnalato, non manda in crash lo stage |  |  |  |  |  |  |  |
| F2-28 | La pivot v1 ticket<->partecipante viene importata in v2 |  |  |  |  |  |  |  |
| F2-29 | Una partecipazione che referenzia un utente v2 inesistente viene segnalata, non manda in crash lo stage |  |  |  |  |  |  |  |
| F2-30 | Un delta di stato diventa un evento status_changed con from_status derivato dal log precedente |  |  |  |  |  |  |  |
| F2-31 | Un log con solo la chiave "watch" viene escluso e segnalato, non importato come ticket_log |  |  |  |  |  |  |  |
| F2-32 | Un log senza autore risolvibile ricade sull'utente di sistema |  |  |  |  |  |  |  |
| F2-33 | Rieseguire lo stage sullo stesso dump è idempotente tramite import_mappings: la seconda esecuzione si limita a saltare |  |  |  |  |  |  |  |
| F2-34 | I log "solo watch" dello stesso giorno si aggregano in un'unica riga ticket_views |  |  |  |  |  |  |  |
| F2-35 | I log non "solo watch" vengono esclusi e segnalati, non importati come ticket_view |  |  |  |  |  |  |  |
| F2-36 | ticket_logs e ticket_views leggono lo stesso input story_logs senza alcuna sovrapposizione |  |  |  |  |  |  |  |
| F2-37 | Rieseguire lo stage sullo stesso dump è idempotente: la seconda esecuzione si limita a saltare, nessuna riga duplicata |  |  |  |  |  |  |  |
| F2-38 | Una catena di reply prependute reale (story id 1641 dal dump v1) viene scomposta in ordine cronologico |  |  |  |  |  |  |  |
| F2-39 | Una conversazione reale con quote inoltrata da Gmail (story id 3642) non viene scomposta: un unico blocco di fallback |  |  |  |  |  |  |  |
| F2-40 | Un customer_request reale multi-risposta viene scomposto in messaggi cronologici |  |  |  |  |  |  |  |
| F2-41 | Un tentativo di XSS nel corpo viene neutralizzato da TicketMessageSanitizer |  |  |  |  |  |  |  |
| F2-42 | Rieseguire lo stage sullo stesso dump è idempotente tramite import_mappings: la seconda esecuzione si limita a saltare |  |  |  |  |  |  |  |
| F2-43 | Un media con il file fisico presente su disco viene allegato al primo messaggio legacy del suo ticket |  |  |  |  |  |  |  |
| F2-44 | Un media il cui file fisico è mancante viene segnalato come orfano, non allegato |  |  |  |  |  |  |  |
| F2-45 | Un ticket senza alcun messaggio legacy ottiene un messaggio di sistema creato per ospitare i suoi allegati |  |  |  |  |  |  |  |
| F2-46 | Rieseguire lo stage sullo stesso dump è idempotente tramite import_mappings su media.uuid: la seconda esecuzione si limita a saltare |  |  |  |  |  |  |  |
| F2-47 | Un report v1 di proprietà di un utente viene importato in v2 con l'id preservato e la locale del proprietario derivata |  |  |  |  |  |  |  |
| F2-48 | Un report v1 ambiguo (con sia customer_id che organization_id impostati) viene saltato e segnalato, senza mai violare il CHECK sul proprietario |  |  |  |  |  |  |  |
| F2-49 | La pivot v1 activity_report<->story viene importata in v2 come activity_report_ticket |  |  |  |  |  |  |  |
| F2-50 | Un'associazione che referenzia un report di attività inesistente viene saltata e segnalata |  |  |  |  |  |  |  |
| F2-51 | Un'opportunità di fundraising v1 viene importata in v2 con l'id preservato e le colonne mappate |  |  |  |  |  |  |  |
| F2-52 | Un'esecuzione ripetuta non sovrascrive mai evaluated_by/evaluated_at/i totali di valutazione impostati da un uso reale di v2 dopo l'import |  |  |  |  |  |  |  |
| F2-53 | Una colonna v1 evaluation_*_score con un valore nel range diventa una riga fundraising_evaluation_scores |  |  |  |  |  |  |  |
| F2-54 | Un punteggio v1 fuori range viene troncato al range del catalogo criteri e il troncamento viene segnalato |  |  |  |  |  |  |  |
| F2-55 | Un'opportunità referenziata dalle colonne v1 evaluation_* ma assente in v2 viene saltata, non manda in crash lo stage |  |  |  |  |  |  |  |
| F2-56 | Un progetto di fundraising v1 viene importato in v2 con l'id preservato e le colonne mappate |  |  |  |  |  |  |  |
| F2-57 | Un progetto i cui lead_user_id/responsible_user_id non esistono in v2 vengono azzerati, non saltati |  |  |  |  |  |  |  |
| F2-58 | La pivot v1 progetto di fundraising<->partner viene importata in v2 |  |  |  |  |  |  |  |
| F2-59 | Un partner che referenzia un progetto di fundraising v2 inesistente viene segnalato, non manda in crash lo stage |  |  |  |  |  |  |  |
| F2-60 | released_at viene ricostruito a partire dalla transizione status_changed in ticket_logs, quando mancante |  |  |  |  |  |  |  |
| F2-61 | worked_minutes e ticket_work_logs vengono ricalcolati da un intervallo "progress" in ticket_logs, riusando RecalculateWorkedTime |  |  |  |  |  |  |  |
| F2-62 | Vengono rigenerati slug finali univoci per tag e documentation_pages, con suffisso numerico sui duplicati |  |  |  |  |  |  |  |
| F2-63 | Viene generato un email_thread per ogni ticket con una conversazione importata |  |  |  |  |  |  |  |
| F2-64 | Rieseguire derive sullo stesso stato è idempotente: la seconda esecuzione si limita a saltare |  |  |  |  |  |  |  |
| F2-65 | Un ticket entro la tolleranza del 5% sulle ore lavorate viene classificato come conforme |  |  |  |  |  |  |  |
| F2-66 | Un ticket oltre la tolleranza del 5% viene elencato con lo scostamento percentuale |  |  |  |  |  |  |  |
| F2-67 | Il comando ha successo e riporta OK quando i conteggi v1/v2 e i controlli di integrità coincidono |  |  |  |  |  |  |  |
| F2-68 | Il comando fallisce quando il conteggio di un'entità a id preservato non corrisponde |  |  |  |  |  |  |  |
| F2-69 | Una seconda esecuzione consecutiva di v1:import non crea/aggiorna nulla su nessuno stage registrato |  |  |  |  |  |  |  |
| F2-70 | Con --anonymize nome/email/contenuti restano sempre quelli reali del dump v1, mai alterati |  |  |  |  |  |  |  |
| F2-71 | Con --anonymize la password è sempre l'hash di un valore fisso noto, mai l'hash v1 reale |  |  |  |  |  |  |  |
| F2-72 | Un'email verso un dominio reale non in allowlist viene bloccata fuori produzione |  |  |  |  |  |  |  |
| F2-73 | Il guard viene bypassato del tutto in produzione, destinatari reali inclusi |  |  |  |  |  |  |  |
| F2-74 | Le email duplicate case-insensitive vengono segnalate senza far fallire lo stage (deviazione coperta dalla fixture CI) |  |  |  |  |  |  |  |

## Fase 3 (Sottosistema email) — 113 test

| ID | Titolo | Esito | Tester | Data | Versione | Evidenza | Anomalia | Note |
|---|---|---|---|---|---|---|---|---|
| F3-01 | Il container risolve InboundMailTransport all'implementazione Webklex |  |  |  |  |  |  |  |
| F3-02 | La config account IMAP ha la forma richiesta da ClientManager::make() |  |  |  |  |  |  |  |
| F3-03 | Ogni ImapFolderRole ha una cartella configurata |  |  |  |  |  |  |  |
| F3-04 | Il gruppo di notifica staff è derivato da una env comma-separated |  |  |  |  |  |  |  |
| F3-05 | Il .eml grezzo è archiviato PRIMA di creare la riga email_messages (status=received) |  |  |  |  |  |  |  |
| F3-06 | Rieseguire il comando sullo stesso stato IMAP non crea duplicati |  |  |  |  |  |  |  |
| F3-07 | IMAP viene sempre disconnesso, anche quando fetch lancia un errore |  |  |  |  |  |  |  |
| F3-08 | --limit sovrascrive il default di configurazione |  |  |  |  |  |  |  |
| F3-09 | SubjectNormalizer rimuove i prefissi di risposta/inoltro anche in cascata |  |  |  |  |  |  |  |
| F3-10 | EmailBodyParser preferisce il text/plain quando entrambi i corpi sono presenti |  |  |  |  |  |  |  |
| F3-11 | QuotedTextRemover rimuove una citazione introdotta da "On ... wrote:" |  |  |  |  |  |  |  |
| F3-12 | body_html è sempre sanitizzato con la stessa allowlist del ticketing |  |  |  |  |  |  |  |
| F3-13 | Un .eml grezzo mancante non lancia un'eccezione non gestita: il messaggio passa a failed |  |  |  |  |  |  |  |
| F3-14 | Un DSN (multipart/report, report-type delivery-status) è scartato e non va al ticketing |  |  |  |  |  |  |  |
| F3-15 | Un mittente MAILER-DAEMON/postmaster/no-reply/vuoto è scartato, un mittente normale no |  |  |  |  |  |  |  |
| F3-16 | Auto-Submitted diverso da no è scartato |  |  |  |  |  |  |  |
| F3-17 | Precedence bulk/list/junk è scartato |  |  |  |  |  |  |  |
| F3-18 | List-Id presente è scartato come mailing list |  |  |  |  |  |  |  |
| F3-19 | X-Auto-Response-Suppress presente è scartato |  |  |  |  |  |  |  |
| F3-20 | Oltre la soglia oraria il messaggio è comunque classificato ma il mittente va in loop_protection |  |  |  |  |  |  |  |
| F3-21 | Un mittente che corrisponde esattamente a users.email viene identificato |  |  |  |  |  |  |  |
| F3-22 | Un mittente con sub-address (plus-addressing) viene identificato rimuovendo il tag |  |  |  |  |  |  |  |
| F3-23 | Nessuna identificazione per solo dominio, anche con mittente sullo stesso dominio |  |  |  |  |  |  |  |
| F3-24 | Un mittente non identificato va in quarantena, mai scartato |  |  |  |  |  |  |  |
| F3-25 | Livello 1 (VERP): un token ticket+ulid valido nel To risolve il ticket_message e il suo ticket |  |  |  |  |  |  |  |
| F3-26 | Livello 2 (In-Reply-To): un In-Reply-To che referenzia un message_id esistente risolve il ticket |  |  |  |  |  |  |  |
| F3-27 | Livello 3 (token subject): [#<id>] nel subject normalizzato risolve direttamente il ticket |  |  |  |  |  |  |  |
| F3-28 | Livello 4 (euristica): stesso mittente + subject identico + thread aperto di recente, marcato come euristico |  |  |  |  |  |  |  |
| F3-29 | Un match di livello più affidabile (In-Reply-To) non è mai scavalcato dall'euristica |  |  |  |  |  |  |  |
| F3-30 | Nessun match sui quattro livelli restituisce una risoluzione vuota (nuovo ticket) |  |  |  |  |  |  |  |
| F3-31 | Mittente identificato senza match di thread crea un nuovo ticket helpdesk con il primo messaggio via email |  |  |  |  |  |  |  |
| F3-32 | Mittente identificato con match di thread accoda un messaggio sul ticket esistente invece di crearne uno nuovo |  |  |  |  |  |  |  |
| F3-33 | Un fallimento nella risoluzione del ticket esistente annulla sia il messaggio sia l'aggiornamento di email_messages (stessa transazione) |  |  |  |  |  |  |  |
| F3-34 | Un fallimento nella notifica post-commit non annulla il ticket/messaggio già creati (problema 2 del v1) |  |  |  |  |  |  |  |
| F3-35 | La risposta del richiedente via email applica la transizione T7 (waiting torna a previous_status) |  |  |  |  |  |  |  |
| F3-36 | Mittente non identificato non crea nessun ticket e lascia il messaggio in quarantena, mai scartato |  |  |  |  |  |  |  |
| F3-37 | Un mittente sconosciuto senza soppressioni attive emette EmailQuarantined con auto-reply consentito |  |  |  |  |  |  |  |
| F3-38 | Un mittente già soppresso per rate limit (US-304) emette EmailQuarantined senza consentire l'auto-reply |  |  |  |  |  |  |  |
| F3-39 | Un messaggio già in quarantena viene riprocessato con successo una volta che il mittente diventa identificabile |  |  |  |  |  |  |  |
| F3-40 | E9 e una notifica in-app arrivano a ogni destinatario staff risolto quando un messaggio va in quarantena |  |  |  |  |  |  |  |
| F3-41 | Un allegato regolare viene importato nella collection attachments del ticket_message con record stored |  |  |  |  |  |  |  |
| F3-42 | Gli allegati inline sono esclusi per default, nessun record creato |  |  |  |  |  |  |  |
| F3-43 | Un allegato di tipo non consentito produce un record rejected_mime, senza fallire gli altri |  |  |  |  |  |  |  |
| F3-44 | Un allegato più grande del limite per singolo file produce un record rejected_size, senza fallire gli altri |  |  |  |  |  |  |  |
| F3-45 | Un errore nel salvataggio di un singolo allegato produce un record failed, senza fermare gli altri |  |  |  |  |  |  |  |
| F3-46 | Il layout condiviso produce HTML valido, senza errori di parsing, per un Mailable reale |  |  |  |  |  |  |  |
| F3-47 | L'email renderizzata contiene tutti i componenti riusabili: header, badge, blocco messaggio, CTA, footer |  |  |  |  |  |  |  |
| F3-48 | La versione plain-text è generata insieme all'HTML con lo stesso contenuto |  |  |  |  |  |  |  |
| F3-49 | Il footer mostra il link alle preferenze di notifica quando un URL è configurato |  |  |  |  |  |  |  |
| F3-50 | E1 viene inviata quando un'email inbound applica un nuovo ticket |  |  |  |  |  |  |  |
| F3-51 | E1 non viene inviata quando l'email inbound si aggancia a un ticket esistente |  |  |  |  |  |  |  |
| F3-52 | E2 viene inviata quando il ticket è creato dal canale web |  |  |  |  |  |  |  |
| F3-53 | Ogni Mailable outbound imposta Message-Id e Reply-To VERP dalla riga email_messages |  |  |  |  |  |  |  |
| F3-54 | Nessun invio se il destinatario è in email_suppressions: la riga outbound resta comunque tracciata come suppressed |  |  |  |  |  |  |  |
| F3-55 | E3 viene inviata quando un'email inbound applica un nuovo ticket per un cliente |  |  |  |  |  |  |  |
| F3-56 | E3 viene inviata quando un cliente apre un ticket dal web |  |  |  |  |  |  |  |
| F3-57 | E9 e una notifica in-app arrivano a ogni destinatario staff quando un messaggio va in quarantena |  |  |  |  |  |  |  |
| F3-58 | Cambiare il gruppo staff in configurazione cambia i destinatari senza toccare Mailable/listener |  |  |  |  |  |  |  |
| F3-59 | E4 viene inviata ai destinatari del ticket quando lo stato cambia |  |  |  |  |  |  |  |
| F3-60 | Il contenuto mostra un testo diverso per un destinatario cliente rispetto allo staff |  |  |  |  |  |  |  |
| F3-61 | L'attore dell'azione è sempre escluso dai destinatari, anche quando la tabella lo indicherebbe |  |  |  |  |  |  |  |
| F3-62 | La notifica raggiunge il ruolo atteso per ciascuna transizione della tabella (US-318) |  |  |  |  |  |  |  |
| F3-63 | E5 viene inviata ai destinatari del ticket quando un messaggio pubblico viene pubblicato |  |  |  |  |  |  |  |
| F3-64 | Un messaggio interno non genera mai E5, nemmeno verso lo staff |  |  |  |  |  |  |  |
| F3-65 | I destinatari sono richiedente, assegnatario e tester, escluso l'autore del messaggio |  |  |  |  |  |  |  |
| F3-66 | E6 viene accodata al nuovo assegnatario quando TicketAssigned viene dispatchato |  |  |  |  |  |  |  |
| F3-67 | Nessuna notifica se il nuovo assegnatario è l'attore che ha eseguito l'azione |  |  |  |  |  |  |  |
| F3-68 | E6 viene inviata anche al nuovo tester quando TicketTesterAssigned viene dispatchato |  |  |  |  |  |  |  |
| F3-69 | Il comando invia il reminder al richiedente di un ticket waiting fermo da almeno la soglia |  |  |  |  |  |  |  |
| F3-70 | Un ticket già ricordato di recente non riceve un secondo reminder nella finestra di cooldown |  |  |  |  |  |  |  |
| F3-71 | Un tipo di notifica senza nessuna riga preferenza è consentito per default |  |  |  |  |  |  |  |
| F3-72 | Un tipo di notifica esplicitamente disabilitato nelle preferenze viene bloccato |  |  |  |  |  |  |  |
| F3-73 | La transizione "new to rejected" risolve al solo richiedente |  |  |  |  |  |  |  |
| F3-74 | Il richiedente è escluso quando è anche lui l'attore dell'azione |  |  |  |  |  |  |  |
| F3-75 | "problem" risolve a ogni manager attivo, escludendo l'attore se è lui stesso un manager |  |  |  |  |  |  |  |
| F3-76 | Il DSN è correlato all'email/ticket originale via Message-ID citato nel report |  |  |  |  |  |  |  |
| F3-77 | Un hard bounce (Action: failed) sospende permanentemente il destinatario originale |  |  |  |  |  |  |  |
| F3-78 | Un soft bounce sotto soglia incrementa bounce_count senza attivare la sospensione |  |  |  |  |  |  |  |
| F3-79 | Un soft bounce che raggiunge la soglia configurata attiva la sospensione |  |  |  |  |  |  |  |
| F3-80 | Un hard bounce correlato aggiorna anche lo stato dell'email originale a bounced |  |  |  |  |  |  |  |
| F3-81 | Le soppressioni sono rimovibili da amministrazione, riabilitando l'invio |  |  |  |  |  |  |  |
| F3-82 | La lingua risolve a users.locale quando è impostato |  |  |  |  |  |  |  |
| F3-83 | Fallback alla locale della prima organizzazione quando users.locale è vuoto |  |  |  |  |  |  |  |
| F3-84 | Fallback a config app.locale quando né users.locale né una locale organizzazione sono impostati |  |  |  |  |  |  |  |
| F3-85 | Ogni chiave di traduzione usata dalla pipeline di Fase 3 esiste, non vuota, in italiano e inglese |  |  |  |  |  |  |  |
| F3-86 | Il subject viene costruito nella locale dell'assegnatario, non sempre in italiano |  |  |  |  |  |  |  |
| F3-87 | Un utente senza email.view non accede al registro email |  |  |  |  |  |  |  |
| F3-88 | La tabella è filtrabile per direzione |  |  |  |  |  |  |  |
| F3-89 | La tabella è filtrabile per stato |  |  |  |  |  |  |  |
| F3-90 | La vista di dettaglio mostra header, corpo, allegati e diagnostica |  |  |  |  |  |  |  |
| F3-91 | La risorsa non ha pagina di creazione né di modifica manuale |  |  |  |  |  |  |  |
| F3-92 | Un admin può riprocessare un messaggio tramite l'azione dedicata |  |  |  |  |  |  |  |
| F3-93 | Un admin può assegnare un mittente a un messaggio in quarantena tramite l'azione dedicata |  |  |  |  |  |  |  |
| F3-94 | Un admin può collegare un messaggio a un altro ticket tramite l'azione dedicata |  |  |  |  |  |  |  |
| F3-95 | La pagina Quarantena può associare un utente esistente e riprocessare il messaggio |  |  |  |  |  |  |  |
| F3-96 | La pagina Quarantena può creare un nuovo utente e riprocessare il messaggio |  |  |  |  |  |  |  |
| F3-97 | Ogni azione amministrativa è tracciata (chi, quando) in email_message_logs |  |  |  |  |  |  |  |
| F3-98 | L'elenco soppressioni è filtrabile per motivo |  |  |  |  |  |  |  |
| F3-99 | Un admin con email.manage può rimuovere una soppressione, riabilitando l'invio |  |  |  |  |  |  |  |
| F3-100 | Le metriche contano elaborati/scartati/falliti nelle ultime 24h |  |  |  |  |  |  |  |
| F3-101 | Il bounce rate è calcolato su invii tentati (bounced + queued), mai su sent |  |  |  |  |  |  |  |
| F3-102 | La voce Mailpit è visibile in locale con l'URL configurato |  |  |  |  |  |  |  |
| F3-103 | La voce Mailpit è nascosta in produzione anche con l'URL configurato |  |  |  |  |  |  |  |
| F3-104 | La voce Mailpit è la prima voce di navigazione nel gruppo Email |  |  |  |  |  |  |  |
| F3-105 | Il comando riaccoda tutti i messaggi outbound falliti |  |  |  |  |  |  |  |
| F3-106 | Un destinatario finito in soppressione blocca il reinvio ma il comando prosegue con gli altri |  |  |  |  |  |  |  |
| F3-107 | --email-message reinvia solo il messaggio indicato |  |  |  |  |  |  |  |
| F3-108 | Una risposta via VERP a una notifica accoda un messaggio sul ticket esistente invece di crearne uno nuovo |  |  |  |  |  |  |  |
| F3-109 | Una risposta su un ticket importato dal v1 risolve via token subject anche senza VERP disponibile |  |  |  |  |  |  |  |
| F3-110 | Un hard bounce sospende permanentemente il destinatario originale, non crea ticket e non genera auto-reply |  |  |  |  |  |  |  |
| F3-111 | Un mittente già in blacklist anti-loop viene scartato e riprocessare lo stesso messaggio non duplica nulla |  |  |  |  |  |  |  |
| F3-112 | Un mittente sconosciuto va in quarantena e resta ispezionabile in amministrazione (US-321) insieme a un messaggio scartato |  |  |  |  |  |  |  |
| F3-113 | La conferma di apertura ticket via email arriva nella lingua del richiedente (US-320) attraverso tutta la pipeline end-to-end |  |  |  |  |  |  |  |

## Fase 4 (Tag/commesse, Documentation, Activity Report/Organizations) — 42 test

| ID | Titolo | Esito | Tester | Data | Versione | Evidenza | Anomalia | Note |
|---|---|---|---|---|---|---|---|---|
| F4-01 | sal() è null quando la commessa non ha ore stimate |  |  |  |  |  |  |  |
| F4-02 | sal() somma i minuti lavorati di tutti i ticket collegati e arrotonda a 2 decimali |  |  |  |  |  |  |  |
| F4-03 | La commessa risulta chiusa solo quando ogni ticket collegato è rilasciato o completato |  |  |  |  |  |  |  |
| F4-04 | Creare una commessa da un ticket precompila le ore stimate dal ticket e li collega |  |  |  |  |  |  |  |
| F4-05 | Lo slug generato riceve un suffisso numerico in caso di collisione, incluse le commesse soft-deleted |  |  |  |  |  |  |  |
| F4-06 | Un utente con tag.create può trasformare un ticket in commessa dalla pagina di visualizzazione |  |  |  |  |  |  |  |
| F4-07 | Un utente senza tag.create non vede l'azione "crea commessa" |  |  |  |  |  |  |  |
| F4-08 | L'elenco commesse mostra ore stimate/lavorate, barra SAL e conteggio ticket aperti/chiusi |  |  |  |  |  |  |  |
| F4-09 | Una commessa senza ore stimate mostra un placeholder SAL invece di un errore di divisione |  |  |  |  |  |  |  |
| F4-10 | Un utente senza tag.view non accede all'elenco commesse |  |  |  |  |  |  |  |
| F4-11 | Lo scope di visibilità esclude le pagine interne per chi non ha documentation.view.internal |  |  |  |  |  |  |  |
| F4-12 | Un cliente non può visualizzare una pagina interna nemmeno richiedendone direttamente l'id |  |  |  |  |  |  |  |
| F4-13 | Creare una pagina di documentazione crea un tag collegato "Documentation: <titolo>" |  |  |  |  |  |  |  |
| F4-14 | Rinominare una pagina rinomina il tag collegato esistente senza crearne un duplicato |  |  |  |  |  |  |  |
| F4-15 | Un utente con documentation.view.customer accede al registro e vede solo le pagine cliente |  |  |  |  |  |  |  |
| F4-16 | La ricerca full-text trova una pagina da un termine presente solo nel corpo |  |  |  |  |  |  |  |
| F4-17 | Creare una pagina genera un PDF non vuoto e valorizza pdf_path/pdf_generated_at |  |  |  |  |  |  |  |
| F4-18 | Modificare il titolo rigenera il PDF con un timestamp più recente |  |  |  |  |  |  |  |
| F4-19 | Il comando documentation:regenerate-pdfs rigenera il PDF di ogni pagina |  |  |  |  |  |  |  |
| F4-20 | Un utente che può visualizzare la pagina può scaricarne il PDF |  |  |  |  |  |  |  |
| F4-21 | Un utente senza il permesso di categoria corrispondente è negato, anche via accesso diretto per id |  |  |  |  |  |  |  |
| F4-22 | Un utente con organization.view accede al registro organizzazioni |  |  |  |  |  |  |  |
| F4-23 | Collegare un utente tramite il relation manager "Membri" lo collega all'organizzazione |  |  |  |  |  |  |  |
| F4-24 | periodStart/periodEnd coprono l'intero mese per un report mensile |  |  |  |  |  |  |  |
| F4-25 | periodLabel è il nome del mese localizzato e capitalizzato più l'anno per un report mensile |  |  |  |  |  |  |  |
| F4-26 | syncTickets seleziona solo i ticket del proprietario utente completati nel periodo |  |  |  |  |  |  |  |
| F4-27 | syncTickets seleziona i ticket richiesti da ogni membro dell'organizzazione proprietaria |  |  |  |  |  |  |  |
| F4-28 | syncTickets è idempotente se invocato due volte di seguito sullo stesso report |  |  |  |  |  |  |  |
| F4-29 | Creare il report sincronizza i suoi ticket in un'unica chiamata |  |  |  |  |  |  |  |
| F4-30 | Un duplicato proprietario/periodo viene rifiutato con un errore leggibile invece della QueryException grezza |  |  |  |  |  |  |  |
| F4-31 | Generare il PDF del report produce un file non vuoto e valorizza pdf_path/pdf_generated_at |  |  |  |  |  |  |  |
| F4-32 | Cancellare il report rimuove il PDF generato dallo storage, nessun file orfano |  |  |  |  |  |  |  |
| F4-33 | activity-report.view.own autorizza un membro dell'organizzazione proprietaria ma non un non-membro |  |  |  |  |  |  |  |
| F4-34 | Un utente con solo activity-report.view.own può scaricare il proprio report |  |  |  |  |  |  |  |
| F4-35 | Il comando reports:generate-monthly crea il report per un cliente con un ticket completato nel mese precedente e accoda il PDF |  |  |  |  |  |  |  |
| F4-36 | Rieseguire il comando non duplica un report già creato per lo stesso proprietario e periodo |  |  |  |  |  |  |  |
| F4-37 | --dry-run esamina i proprietari attivi senza creare report né accodare PDF |  |  |  |  |  |  |  |
| F4-38 | view.own vede solo il proprio report come proprietario diretto, mai quello di un altro owner |  |  |  |  |  |  |  |
| F4-39 | Un cliente con activity-report.view.own vede solo il proprio report nell'elenco "Report Attività" |  |  |  |  |  |  |  |
| F4-40 | Il SAL è calcolato correttamente su una commessa con ticket collegati (replica automatica della verifica manuale su dati reali v1:import) |  |  |  |  |  |  |  |
| F4-41 | Una pagina di documentazione genera un PDF scaricabile con la carta intestata Montagna Servizi corretta |  |  |  |  |  |  |  |
| F4-42 | Un report attività è generato per un proprietario reale con ticket e totali verificati contro i ticket sorgente |  |  |  |  |  |  |  |

## Fase 5 (Fundraising — opportunità/bandi, griglia di valutazione, progetti e vista cliente) — 60 test

| ID | Titolo | Esito | Tester | Data | Versione | Evidenza | Anomalia | Note |
|---|---|---|---|---|---|---|---|---|
| F5-01 | isExpired() è false quando la scadenza è oggi |  |  |  |  |  |  |  |
| F5-02 | isExpired() è true quando la scadenza è ieri |  |  |  |  |  |  |  |
| F5-03 | Lo scope active() restituisce le opportunità con scadenza odierna o futura |  |  |  |  |  |  |  |
| F5-04 | Lo scope expired() restituisce le opportunità con scadenza passata |  |  |  |  |  |  |  |
| F5-05 | Un utente senza alcun permesso fundraising.* è negato su ogni abilità della Policy opportunità |  |  |  |  |  |  |  |
| F5-06 | FundraisingOpportunityPolicy verificata riga per riga per ogni ruolo (§9.4) |  |  |  |  |  |  |  |
| F5-07 | La Resource opportunità è visibile in navigazione solo ad admin/fundraising, mai a manager/developer/customer |  |  |  |  |  |  |  |
| F5-08 | L'elenco mostra di default solo le opportunità attive, l'Archivio mostra solo le scadute |  |  |  |  |  |  |  |
| F5-09 | Il filtro per ambito territoriale produce il sottoinsieme atteso di opportunità |  |  |  |  |  |  |  |
| F5-10 | Il filtro cofinanziamento con/senza quota produce il sottoinsieme atteso di opportunità |  |  |  |  |  |  |  |
| F5-11 | Il filtro scaduto/attivo produce il sottoinsieme atteso di opportunità |  |  |  |  |  |  |  |
| F5-12 | created_by si valorizza automaticamente con l'utente autenticato e non è più alterabile |  |  |  |  |  |  |  |
| F5-13 | Le azioni "Crea progetto" e "Crea ticket" da un'opportunità creano il record collegato correttamente |  |  |  |  |  |  |  |
| F5-14 | Il catalogo contiene esattamente i 26 criteri di §6.6.2, sui 5 blocchi previsti |  |  |  |  |  |  |  |
| F5-15 | I criteri principali hanno range di punteggio 0-5 |  |  |  |  |  |  |  |
| F5-16 | I criteri del blocco Rischi consentono punteggi negativi, unico blocco a farlo |  |  |  |  |  |  |  |
| F5-17 | CalculateEvaluationTotals somma nel totale positivo solo i punteggi >= 0 |  |  |  |  |  |  |  |
| F5-18 | CalculateEvaluationTotals somma nel totale negativo il valore assoluto dei punteggi < 0 |  |  |  |  |  |  |  |
| F5-19 | Il totale complessivo è positivo meno negativo |  |  |  |  |  |  |  |
| F5-20 | Il calcolo gestisce correttamente il valore minimo e massimo di ogni range del catalogo |  |  |  |  |  |  |  |
| F5-21 | Un criterio aggiunto al catalogo solo a runtime viene incluso correttamente nel calcolo dei totali |  |  |  |  |  |  |  |
| F5-22 | SaveEvaluationScores persiste una riga per criterio e calcola i totali da tutti i punteggi persistiti |  |  |  |  |  |  |  |
| F5-23 | Un punteggio sotto il minimo del catalogo per quel criterio viene rifiutato |  |  |  |  |  |  |  |
| F5-24 | Un punteggio sopra il massimo del catalogo per quel criterio viene rifiutato |  |  |  |  |  |  |  |
| F5-25 | evaluated_by/evaluated_at si valorizzano al primo punteggio salvato |  |  |  |  |  |  |  |
| F5-26 | evaluated_by/evaluated_at non vengono mai sovrascritti dai salvataggi successivi al primo |  |  |  |  |  |  |  |
| F5-27 | Compilare la griglia dalla pagina Edit persiste i punteggi e aggiorna i totali coerentemente col service |  |  |  |  |  |  |  |
| F5-28 | Un punteggio fuori dal range del criterio produce un errore di validazione leggibile in UI |  |  |  |  |  |  |  |
| F5-29 | Il tab "Valutazione" non è visibile a chi ha solo fundraising.update, senza fundraising.evaluate |  |  |  |  |  |  |  |
| F5-30 | La griglia riprende correttamente i punteggi già persistiti quando si riapre la pagina Edit |  |  |  |  |  |  |  |
| F5-31 | Ogni transizione ammessa della macchina a stati del progetto può essere eseguita |  |  |  |  |  |  |  |
| F5-32 | Ogni altra transizione non elencata in tabella è vietata |  |  |  |  |  |  |  |
| F5-33 | Gli stati terminali (rejected/completed) non hanno alcuna transizione uscente |  |  |  |  |  |  |  |
| F5-34 | scopeInvolving trova il progetto per capofila, partner, responsabile o creatore |  |  |  |  |  |  |  |
| F5-35 | partnerCustomers() restituisce solo i partner con ruolo customer |  |  |  |  |  |  |  |
| F5-36 | FundraisingProjectPolicy verificata riga per riga per ogni ruolo (§9.4), caso non coinvolto |  |  |  |  |  |  |  |
| F5-37 | Un customer coinvolto come capofila vede il progetto ma non può scriverlo |  |  |  |  |  |  |  |
| F5-38 | Un customer non coinvolto in nessun modo non vede il progetto, nemmeno via URL diretto |  |  |  |  |  |  |  |
| F5-39 | La Resource progetti è visibile in navigazione solo ad admin/fundraising, mai a manager/developer/customer |  |  |  |  |  |  |  |
| F5-40 | Il filtro per stato produce il sottoinsieme atteso di progetti |  |  |  |  |  |  |  |
| F5-41 | Il filtro per capofila produce il sottoinsieme atteso di progetti |  |  |  |  |  |  |  |
| F5-42 | Il filtro per partner produce il sottoinsieme atteso di progetti |  |  |  |  |  |  |  |
| F5-43 | Il filtro "coinvolti" produce il sottoinsieme atteso di progetti |  |  |  |  |  |  |  |
| F5-44 | created_by si valorizza automaticamente con l'utente autenticato alla creazione di un progetto |  |  |  |  |  |  |  |
| F5-45 | Un utente fundraising può aggiungere e rimuovere un partner dal progetto |  |  |  |  |  |  |  |
| F5-46 | Un ticket esistente può essere collegato a un progetto di fundraising |  |  |  |  |  |  |  |
| F5-47 | CustomerFundraisingOpportunityResource è visibile in navigazione solo al ruolo customer |  |  |  |  |  |  |  |
| F5-48 | Qualunque customer vede qualunque opportunità nell'elenco e ne apre il dettaglio in sola lettura |  |  |  |  |  |  |  |
| F5-49 | CustomerFundraisingOpportunityResource non registra alcuna pagina di scrittura |  |  |  |  |  |  |  |
| F5-50 | La Resource opportunità riservata allo staff resta invisibile a un customer |  |  |  |  |  |  |  |
| F5-51 | CustomerFundraisingProjectResource è visibile in navigazione solo al ruolo customer |  |  |  |  |  |  |  |
| F5-52 | Un customer capofila o partner vede il proprio progetto nell'elenco, uno non coinvolto no |  |  |  |  |  |  |  |
| F5-53 | scopeInvolvingAsCustomer trova il progetto SOLO per capofila o partner |  |  |  |  |  |  |  |
| F5-54 | Essere solo responsabile o creatore non basta a far vedere il progetto a un customer |  |  |  |  |  |  |  |
| F5-55 | Il dettaglio di un progetto è raggiungibile da un customer coinvolto |  |  |  |  |  |  |  |
| F5-56 | Il dettaglio di un progetto non coinvolto non è raggiungibile via URL diretto |  |  |  |  |  |  |  |
| F5-57 | CustomerFundraisingProjectResource non registra alcuna pagina di scrittura |  |  |  |  |  |  |  |
| F5-58 | I totali di valutazione ricalcolati coincidono con quelli persistiti da SaveEvaluationScores (replica automatica della verifica manuale su dati reali v1:import) |  |  |  |  |  |  |  |
| F5-59 | Un criterio aggiunto al catalogo a runtime viene incluso correttamente in un totale di valutazione reale, senza lasciare traccia permanente |  |  |  |  |  |  |  |
| F5-60 | Il flusso completo opportunità -> progetto -> partner -> transizione di stato funziona end-to-end |  |  |  |  |  |  |  |

## Fase 6 (Portale cliente e rifinitura) — 141 test

| ID | Titolo | Esito | Tester | Data | Versione | Evidenza | Anomalia | Note |
|---|---|---|---|---|---|---|---|---|
| F6-01 | Un utente non-customer non può accedere alla dashboard cliente |  |  |  |  |  |  |  |
| F6-02 | Un customer può accedere alla propria dashboard |  |  |  |  |  |  |  |
| F6-03 | La card ticket aperti mostra il conteggio corretto per il cliente corrente, scoped ai soli propri ticket |  |  |  |  |  |  |  |
| F6-04 | La card ticket che richiedono una risposta elenca solo i propri ticket in stato waiting/problem |  |  |  |  |  |  |  |
| F6-05 | Un cliente senza ticket aperti e senza ticket in attesa di risposta vede stati vuoti espliciti, non un errore o una sezione silenziosa |  |  |  |  |  |  |  |
| F6-06 | La card documentazione mostra la documentazione customer recente, con stato vuoto quando assente, e non mostra mai pagine interne |  |  |  |  |  |  |  |
| F6-07 | I link drive_url/drive_budget_url compaiono solo quando valorizzati sull'utente autenticato |  |  |  |  |  |  |  |
| F6-08 | La card report attività mostra i propri report, con stato vuoto quando assenti |  |  |  |  |  |  |  |
| F6-09 | La card progetti fundraising mostra i progetti in cui il cliente è coinvolto, con stato vuoto quando assenti |  |  |  |  |  |  |  |
| F6-10 | Un cliente con dati reali su ogni card li vede tutti, scoped a sé stesso, senza alcuno stato vuoto residuo |  |  |  |  |  |  |  |
| F6-11 | Nessun riferimento a un link di chat di supporto compare mai nella dashboard cliente (help_desk_chat_url non confermato) |  |  |  |  |  |  |  |
| F6-12 | Lo staff (admin/manager/developer) che atterra sulla dashboard viene reindirizzato alla WorkBoard, invariato rispetto a prima di questa story |  |  |  |  |  |  |  |
| F6-13 | Un cliente che atterra sulla dashboard viene reindirizzato alla CustomerDashboard (US-601) |  |  |  |  |  |  |  |
| F6-14 | Un membro del team fundraising che atterra sulla dashboard viene reindirizzato all'elenco opportunità |  |  |  |  |  |  |  |
| F6-15 | Un customer vede in navigazione SOLO il gruppo "Area cliente", nessuna voce dei gruppi staff |  |  |  |  |  |  |  |
| F6-16 | Uno staff member non vede mai il gruppo di navigazione "Area cliente" |  |  |  |  |  |  |  |
| F6-17 | Una voce di navigazione riservata allo staff (es. Mailpit) resta nascosta a un customer, anche quando visibile per ambiente/URL configurato |  |  |  |  |  |  |  |
| F6-18 | La ricerca globale trova un ticket per id |  |  |  |  |  |  |  |
| F6-19 | La ricerca globale trova un ticket per titolo |  |  |  |  |  |  |  |
| F6-20 | La ricerca globale trova un ticket per nome o email del richiedente |  |  |  |  |  |  |  |
| F6-21 | La ricerca globale trova un ticket per un termine presente solo nel corpo di un messaggio |  |  |  |  |  |  |  |
| F6-22 | Un cliente non trova nei risultati della ricerca globale ticket appartenenti ad altri richiedenti |  |  |  |  |  |  |  |
| F6-23 | Il badge di navigazione mostra il conteggio combinato corretto e il tooltip col dettaglio per categoria |  |  |  |  |  |  |  |
| F6-24 | Il badge è assente quando non c'è nulla che richieda attenzione |  |  |  |  |  |  |  |
| F6-25 | I conteggi del badge sono cachati tra richieste entro il TTL: una seconda richiesta non genera una nuova query |  |  |  |  |  |  |  |
| F6-26 | I conteggi del badge sono scoped per utente e non trapelano tra chiavi di cache di utenti diversi |  |  |  |  |  |  |  |
| F6-27 | Ogni utente autenticato, a prescindere dal ruolo, può accedere alla pagina preferenze |  |  |  |  |  |  |  |
| F6-28 | Un cliente non vede mai un tipo di comunicazione che riguarda solo lo staff (es. E6 "Assegnazione") |  |  |  |  |  |  |  |
| F6-29 | Un membro dello staff non vede mai un tipo di comunicazione che riguarda solo i clienti |  |  |  |  |  |  |  |
| F6-30 | Un tipo senza riga di preferenza esistente carica come abilitato di default al primo accesso alla pagina |  |  |  |  |  |  |  |
| F6-31 | Un tipo con una riga di preferenza disabilitata già esistente carica come disabilitato |  |  |  |  |  |  |  |
| F6-32 | Salvare persiste una riga updateOrCreate scoped al solo utente corrente, mai a un altro utente |  |  |  |  |  |  |  |
| F6-33 | Salvare non scrive righe per tipi di comunicazione che non si applicano al ruolo corrente |  |  |  |  |  |  |  |
| F6-34 | Disabilitare una preferenza dalla pagina reale delle preferenze di notifica impedisce l'invio di una comunicazione di quel tipo (verifica end-to-end UI -> invio) |  |  |  |  |  |  |  |
| F6-35 | Un ruolo per cui la MFA è obbligatoria non può accedere al pannello senza averla configurata |  |  |  |  |  |  |  |
| F6-36 | Un ruolo per cui la MFA è facoltativa accede normalmente senza averla configurata |  |  |  |  |  |  |  |
| F6-37 | Un ruolo per cui la MFA è obbligatoria accede normalmente una volta che l'ha configurata |  |  |  |  |  |  |  |
| F6-38 | Senza alcun ruolo configurato come obbligatorio, nessun utente è forzato alla MFA |  |  |  |  |  |  |  |
| F6-39 | La pagina profilo espone la gestione della MFA (setup/recovery) per l'utente autenticato |  |  |  |  |  |  |  |
| F6-40 | Un login con MFA attiva mostra la sfida di verifica e si completa solo fornendo un codice valido |  |  |  |  |  |  |  |
| F6-41 | Un login con MFA attiva e un codice errato non completa l'accesso |  |  |  |  |  |  |  |
| F6-42 | Un admin con user.impersonate vede l'azione "Impersona" nella tabella utenti |  |  |  |  |  |  |  |
| F6-43 | Un admin con user.impersonate vede l'azione "Impersona" nella pagina di visualizzazione utente |  |  |  |  |  |  |  |
| F6-44 | Un utente senza user.impersonate non vede mai l'azione "Impersona" |  |  |  |  |  |  |  |
| F6-45 | Un admin può impersonare un utente, il cambio è loggato (chi ha impersonato chi, quando), il banner è visibile con azione per uscire, e uscire ripristina la sessione originale |  |  |  |  |  |  |  |
| F6-46 | Un utente disattivato non può essere impersonato |  |  |  |  |  |  |  |
| F6-47 | Un admin con user.deactivate vede l'azione di disattivazione/riattivazione nella tabella utenti e nella pagina di visualizzazione |  |  |  |  |  |  |  |
| F6-48 | Un utente senza user.deactivate non vede l'azione di disattivazione/riattivazione |  |  |  |  |  |  |  |
| F6-49 | L'azione disattiva un utente attivo e riattiva un utente disattivato (deactivated_at valorizzato/azzerato) |  |  |  |  |  |  |  |
| F6-50 | Disattivare un utente non tocca la relazione storica assegnatario/richiedente/tester su un ticket esistente |  |  |  |  |  |  |  |
| F6-51 | Un utente disattivato non è più selezionabile come partner di un progetto fundraising |  |  |  |  |  |  |  |
| F6-52 | Un utente disattivato non riceve più comunicazioni email (la riga outbound viene marcata soppressa) |  |  |  |  |  |  |  |
| F6-53 | Un utente disattivato non può accedere al pannello nemmeno con un ruolo valido (login bloccato) |  |  |  |  |  |  |  |
| F6-54 | Lo scope "attivi" esclude gli utenti disattivati da una query di selezione utenti (base dei picker di assegnazione/tester/destinatari) |  |  |  |  |  |  |  |
| F6-55 | Un customer senza i permessi di visualizzazione ticket non può accedere alla WorkBoard |  |  |  |  |  |  |  |
| F6-56 | Un developer con il permesso sui campi interni può accedere alla WorkBoard |  |  |  |  |  |  |  |
| F6-57 | Le colonne raggruppano i ticket visibili per stato e nascondono i ticket fuori dallo scope di visibilità |  |  |  |  |  |  |  |
| F6-58 | Il selettore di assegnatario restringe la board a un singolo collega |  |  |  |  |  |  |  |
| F6-59 | Le opzioni del selettore di assegnatario elencano solo membri dello staff (admin/manager/developer), mai clienti |  |  |  |  |  |  |  |
| F6-60 | Il nome cliente sulla card si risolve dall'organizzazione del richiedente, con fallback sul nome del richiedente |  |  |  |  |  |  |  |
| F6-61 | Le colonne eseguono un numero costante di query indipendentemente dal volume di ticket: nessuna regressione N+1 per card introdotta dalla ristilizzazione |  |  |  |  |  |  |  |
| F6-62 | L'attività recente include solo i log dei ticket visibili all'utente corrente |  |  |  |  |  |  |  |
| F6-63 | tickets:progress-to-todo in --dry-run esamina i ticket progress senza transitarne alcuno |  |  |  |  |  |  |  |
| F6-64 | tickets:progress-to-todo transita ogni ticket progress a todo tramite la macchina a stati e lo logga come azione di sistema |  |  |  |  |  |  |  |
| F6-65 | tickets:progress-to-todo non tocca ticket in uno stato diverso da progress |  |  |  |  |  |  |  |
| F6-66 | Rieseguire tickets:progress-to-todo è idempotente: un ticket già todo non viene transitato di nuovo |  |  |  |  |  |  |  |
| F6-67 | tickets:auto-close-released in --dry-run esamina i ticket released senza chiuderne alcuno |  |  |  |  |  |  |  |
| F6-68 | tickets:auto-close-released chiude un ticket released da almeno la soglia configurata di giorni lavorativi e valorizza done_at |  |  |  |  |  |  |  |
| F6-69 | tickets:auto-close-released non chiude un ticket rilasciato più recentemente della soglia |  |  |  |  |  |  |  |
| F6-70 | tickets:auto-close-released non tocca ticket in uno stato diverso da released |  |  |  |  |  |  |  |
| F6-71 | Rieseguire tickets:auto-close-released è idempotente: un ticket già done non viene transitato di nuovo |  |  |  |  |  |  |  |
| F6-72 | La macchina a stati ammette la transizione released -> done sia per l'assegnatario sia per l'utente di sistema (automazione T4, US-610) |  |  |  |  |  |  |  |
| F6-73 | tickets:close-scrum in --dry-run esamina i ticket scrum creati oggi senza chiuderne alcuno |  |  |  |  |  |  |  |
| F6-74 | tickets:close-scrum chiude un ticket scrum creato oggi e valorizza done_at |  |  |  |  |  |  |  |
| F6-75 | tickets:close-scrum chiude anche un ticket scrum aggiornato oggi pur se creato in precedenza |  |  |  |  |  |  |  |
| F6-76 | tickets:close-scrum non tocca un ticket scrum né creato né aggiornato oggi |  |  |  |  |  |  |  |
| F6-77 | tickets:close-scrum non tocca un ticket non-scrum creato oggi |  |  |  |  |  |  |  |
| F6-78 | Rieseguire tickets:close-scrum è idempotente: un ticket scrum già done non viene transitato di nuovo |  |  |  |  |  |  |  |
| F6-79 | tickets:archive-scrum in --dry-run esamina i ticket scrum archiviabili senza archiviarne alcuno |  |  |  |  |  |  |  |
| F6-80 | tickets:archive-scrum archivia un ticket scrum done da almeno la soglia configurata di giorni e lo logga (colonna additiva archived_at, mai una cancellazione o un cambio di stato) |  |  |  |  |  |  |  |
| F6-81 | tickets:archive-scrum non archivia un ticket scrum reso done più di recente della soglia |  |  |  |  |  |  |  |
| F6-82 | tickets:archive-scrum non archivia un ticket scrum che non è done |  |  |  |  |  |  |  |
| F6-83 | tickets:archive-scrum non archivia un ticket non-scrum reso done molto tempo fa |  |  |  |  |  |  |  |
| F6-84 | Rieseguire tickets:archive-scrum è idempotente: un ticket già archiviato non viene archiviato di nuovo |  |  |  |  |  |  |  |
| F6-85 | La macchina a stati ammette * -> done per l'utente di sistema su un ticket scrum, e SOLO per l'utente di sistema (T5, US-611) |  |  |  |  |  |  |  |
| F6-86 | L'utente di sistema non può spostare un ticket non-scrum a done tramite la transizione T5 |  |  |  |  |  |  |  |
| F6-87 | Il catalogo TicketLogEvent contiene esattamente gli 8 valori di §6.2.1 più il nuovo evento "archived" introdotto da US-611 |  |  |  |  |  |  |  |
| F6-88 | tickets:restore-waiting in --dry-run esamina i ticket waiting ripristinabili senza ripristinarne alcuno |  |  |  |  |  |  |  |
| F6-89 | tickets:restore-waiting ripristina un ticket in attesa da esattamente la soglia configurata di giorni di calendario |  |  |  |  |  |  |  |
| F6-90 | tickets:restore-waiting ripristina un ticket in attesa da più della soglia configurata di giorni di calendario |  |  |  |  |  |  |  |
| F6-91 | tickets:restore-waiting non ripristina un ticket in attesa da un giorno in meno della soglia configurata |  |  |  |  |  |  |  |
| F6-92 | tickets:restore-waiting non tocca ticket in uno stato diverso da waiting |  |  |  |  |  |  |  |
| F6-93 | tickets:restore-waiting non tocca un ticket in waiting privo di uno stato precedente |  |  |  |  |  |  |  |
| F6-94 | Rieseguire tickets:restore-waiting è idempotente: un ticket già ripristinato non viene ritoccato |  |  |  |  |  |  |  |
| F6-95 | timetracking:aggregate-daily consolida un ticket con attività odierna, producendo gli stessi aggregati di timetracking:recalculate |  |  |  |  |  |  |  |
| F6-96 | timetracking:aggregate-daily ignora un ticket senza alcuna attività odierna |  |  |  |  |  |  |  |
| F6-97 | timetracking:aggregate-daily in --dry-run esamina i ticket con attività odierna senza scrivere nulla |  |  |  |  |  |  |  |
| F6-98 | Eseguire timetracking:aggregate-daily due volte nello stesso giorno non duplica le righe di ticket_work_logs (idempotenza via upsert) |  |  |  |  |  |  |  |
| F6-99 | mail:send-digest invia un digest a un cliente con attività su uno dei propri ticket |  |  |  |  |  |  |  |
| F6-100 | mail:send-digest non invia alcun digest a un cliente senza attività nelle ultime 24h |  |  |  |  |  |  |  |
| F6-101 | mail:send-digest non invia a un cliente che ha già ricevuto un digest oggi (idempotenza) |  |  |  |  |  |  |  |
| F6-102 | mail:send-digest rispetta la preferenza di notifica E8 disabilitata dal cliente |  |  |  |  |  |  |  |
| F6-103 | mail:send-digest rispetta una soppressione email attiva per il cliente |  |  |  |  |  |  |  |
| F6-104 | mail:send-digest in --dry-run non scrive né invia nulla |  |  |  |  |  |  |  |
| F6-105 | mail:send-digest non fallisce e non invia nulla quando non ci sono clienti |  |  |  |  |  |  |  |
| F6-106 | Il digest include un ticket con un nuovo messaggio pubblico dello staff nelle ultime 24h |  |  |  |  |  |  |  |
| F6-107 | Il digest esclude un messaggio pubblicato dal cliente stesso a cui è destinato il digest |  |  |  |  |  |  |  |
| F6-108 | Il digest esclude un messaggio interno (non pubblico) |  |  |  |  |  |  |  |
| F6-109 | Il digest esclude un messaggio pubblicato prima della finestra delle 24h |  |  |  |  |  |  |  |
| F6-110 | Il digest include un ticket con un cambio di stato nelle ultime 24h, riportando lo stato precedente e quello corrente |  |  |  |  |  |  |  |
| F6-111 | Il digest aggrega più ticket con attività per lo stesso cliente, escludendo quelli senza attività |  |  |  |  |  |  |  |
| F6-112 | Il digest ignora ticket appartenenti a un altro cliente |  |  |  |  |  |  |  |
| F6-113 | Il Mailable E8 renderizza un HTML ben formato che elenca ogni ticket con conteggio messaggi ed eventuale cambio di stato |  |  |  |  |  |  |  |
| F6-114 | Il Mailable E8 valorizza l'header Message-Id e il Reply-To VERP dalla riga email_messages outbound |  |  |  |  |  |  |  |
| F6-115 | Il Mailable E8 genera anche una versione testo semplice accanto all'HTML |  |  |  |  |  |  |  |
| F6-116 | Il Mailable E8 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta |  |  |  |  |  |  |  |
| F6-117 | L'evento di dominio ActivityReportPdfGenerated viene dispatchato la prima volta che il PDF è generato |  |  |  |  |  |  |  |
| F6-118 | L'evento di dominio non viene dispatchato di nuovo quando il PDF viene rigenerato |  |  |  |  |  |  |  |
| F6-119 | Il listener invia E10 all'owner quando il PDF di un report di proprietà utente viene generato |  |  |  |  |  |  |  |
| F6-120 | Il listener invia E10 a ogni membro di un report di proprietà di un'organizzazione |  |  |  |  |  |  |  |
| F6-121 | Il listener non invia a un utente che ha disabilitato questo tipo di notifica |  |  |  |  |  |  |  |
| F6-122 | Il listener implementa ShouldQueue così l'invio avviene in modo asincrono |  |  |  |  |  |  |  |
| F6-123 | Il Mailable E10 renderizza un HTML ben formato col periodo del report e un link di download funzionante, autorizzato dalla Policy esistente |  |  |  |  |  |  |  |
| F6-124 | Il Mailable E10 valorizza l'header Message-Id e il Reply-To VERP dalla riga email_messages outbound |  |  |  |  |  |  |  |
| F6-125 | Il Mailable E10 genera anche una versione testo semplice accanto all'HTML |  |  |  |  |  |  |  |
| F6-126 | Il Mailable E10 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta |  |  |  |  |  |  |  |
| F6-127 | reports:generate-monthly, eseguito realmente end-to-end (comando -> job -> generazione PDF -> evento -> listener), accoda l'email E10 per il proprietario del report |  |  |  |  |  |  |  |
| F6-128 | tickets:notify-idle-developers invia un promemoria a un developer con ticket assegnati e nessuno in lavorazione, entro la finestra oraria configurata (anche come notifica in-app) |  |  |  |  |  |  |  |
| F6-129 | tickets:notify-idle-developers non invia alcun promemoria a un developer con un ticket in lavorazione |  |  |  |  |  |  |  |
| F6-130 | tickets:notify-idle-developers non invia alcun promemoria a un developer il cui unico ticket assegnato è già chiuso |  |  |  |  |  |  |  |
| F6-131 | tickets:notify-idle-developers non invia alcun promemoria fuori dalla finestra oraria configurata |  |  |  |  |  |  |  |
| F6-132 | tickets:notify-idle-developers non invia un secondo promemoria lo stesso giorno, anche in un'esecuzione successiva entro la finestra (idempotenza sulla finestra) |  |  |  |  |  |  |  |
| F6-133 | tickets:notify-idle-developers in --dry-run non scrive né invia nulla |  |  |  |  |  |  |  |
| F6-134 | tickets:notify-idle-developers non fallisce e non invia nulla quando non ci sono developer |  |  |  |  |  |  |  |
| F6-135 | Il Mailable E11 renderizza un HTML ben formato che elenca ogni ticket idle con il proprio stato |  |  |  |  |  |  |  |
| F6-136 | Il Mailable E11 valorizza l'header Message-Id e il Reply-To VERP dalla riga email_messages outbound |  |  |  |  |  |  |  |
| F6-137 | Il Mailable E11 genera anche una versione testo semplice accanto all'HTML |  |  |  |  |  |  |  |
| F6-138 | Il Mailable E11 renderizza il corpo nella lingua impostata via ->locale(), mai una chiave non tradotta |  |  |  |  |  |  |  |
| F6-139 | Due clienti con dati reali su ticket, report e fundraising restano completamente isolati attraverso dashboard, ricerca globale ed elenco ticket, non solo su una superficie alla volta |  |  |  |  |  |  |  |
| F6-140 | Eseguire in sequenza tutti i comandi schedulati di Fase 6 transita ogni ticket guardato esattamente una volta e mai un ticket fuori dal proprio guard, anche ripetendo l'intera sequenza (idempotenza combinata) |  |  |  |  |  |  |  |
| F6-141 | tickets:archive-scrum è un compromesso strettamente additivo: non tocca mai lo stato del ticket né alcun campo oltre archived_at, solo un log di sistema dedicato (garanzia esplicita del compromesso segnalato al committente, US-611) |  |  |  |  |  |  |  |

## Fase 7 (Tipologia di cliente CAI) — 36 test

| ID | Titolo | Esito | Tester | Data | Versione | Evidenza | Anomalia | Note |
|---|---|---|---|---|---|---|---|---|
| F7-01 | Il catalogo CustomerType contiene esattamente i 4 tipi cliente CAI del PRD |  |  |  |  |  |  |  |
| F7-02 | Il catalogo Region contiene esattamente le 20 regioni italiane ufficiali, con Trentino-Alto Adige unificato |  |  |  |  |  |  |  |
| F7-03 | Ogni regione ha una label non vuota per la UI |  |  |  |  |  |  |  |
| F7-04 | Il metodo label() restituisce il nome italiano corretto per i casi con grafia particolare (es. Valle d'Aosta, Friuli-Venezia Giulia) |  |  |  |  |  |  |  |
| F7-05 | La tabella users ha le colonne additive customer_type/region introdotte da questa fase |  |  |  |  |  |  |  |
| F7-06 | Un utente senza customer_type/region resta null senza errori |  |  |  |  |  |  |  |
| F7-07 | customer_type/region sono castati al proprio enum backed sia in lettura sia in scrittura |  |  |  |  |  |  |  |
| F7-08 | Un nome con prefisso GR/GP classifica come Gruppo Regionale ed estrae la regione |  |  |  |  |  |  |  |
| F7-09 | Un nome con prefisso OTCO/SO classifica come Organo Tecnico Centrale/Struttura Operativa, sempre senza regione |  |  |  |  |  |  |  |
| F7-10 | Il pattern OTCO/SO è riconosciuto anche con spazi intorno alla barra |  |  |  |  |  |  |  |
| F7-11 | Un nome nel formato "nome \| regione" classifica come Sezione ed estrae la regione, col o senza il prefisso C.A.I. SEZ. |  |  |  |  |  |  |  |
| F7-12 | Una Sezione senza testo dopo il separatore "\|" resta Sezione con regione null, mai Generico |  |  |  |  |  |  |  |
| F7-13 | Un nome che non corrisponde a nessun pattern classifica come Cliente generico, senza regione |  |  |  |  |  |  |  |
| F7-14 | La normalizzazione regione gestisce le varianti di maiuscole, apostrofo e trattino del dump v1 |  |  |  |  |  |  |  |
| F7-15 | Una regione non normalizzabile registra un warning e lascia region null, senza bloccare l'import con un'eccezione |  |  |  |  |  |  |  |
| F7-16 | Un utente senza ruolo customer non viene mai toccato dallo stage |  |  |  |  |  |  |  |
| F7-17 | Rieseguire lo stage sugli stessi dati è idempotente: la seconda corsa solo salta |  |  |  |  |  |  |  |
| F7-18 | La modalità --dry-run non persiste alcuna classificazione |  |  |  |  |  |  |  |
| F7-19 | I campi tipo cliente e regione sono nascosti quando nessun ruolo customer è selezionato nel form |  |  |  |  |  |  |  |
| F7-20 | Il campo tipo cliente diventa visibile quando il ruolo customer viene selezionato nel form |  |  |  |  |  |  |  |
| F7-21 | Il campo regione diventa visibile solo quando il tipo cliente è Sezione o Gruppo Regionale |  |  |  |  |  |  |  |
| F7-22 | Un admin con user.assign-roles può persistere tipo cliente e regione dal form di modifica |  |  |  |  |  |  |  |
| F7-23 | La regione viene azzerata al salvataggio quando il tipo cliente non è più Sezione o Gruppo Regionale |  |  |  |  |  |  |  |
| F7-24 | Un admin senza user.assign-roles non vede né può modificare tipo cliente e regione |  |  |  |  |  |  |  |
| F7-25 | La colonna tipo cliente (badge colorato) è disponibile nell'elenco utenti per vista rapida e filtro (verificata in browser durante US-703, vedi scripts/ralph/progress.txt) |  |  |  |  |  |  |  |
| F7-26 | Il badge mostra l'etichetta corretta con la regione per un cliente Sezione |  |  |  |  |  |  |  |
| F7-27 | Il badge mostra solo il tipo per un cliente Sezione senza regione |  |  |  |  |  |  |  |
| F7-28 | Il badge mostra l'etichetta corretta con la regione per un cliente Gruppo Regionale |  |  |  |  |  |  |  |
| F7-29 | Il badge mostra solo il tipo per un cliente Organo Tecnico Centrale/Struttura Operativa (mai una regione) |  |  |  |  |  |  |  |
| F7-30 | Il badge mostra solo il tipo per un cliente generico |  |  |  |  |  |  |  |
| F7-31 | Il badge è assente quando il cliente non ha ancora un customer_type classificato |  |  |  |  |  |  |  |
| F7-32 | La card elenca solo le sezioni della stessa regione del Gruppo Regionale, col relativo conteggio ticket aperti |  |  |  |  |  |  |  |
| F7-33 | La card mostra uno stato vuoto esplicito quando la regione non ha ancora nessuna sezione classificata |  |  |  |  |  |  |  |
| F7-34 | La card mostra uno stato vuoto esplicito quando il Gruppo Regionale non ha region valorizzata |  |  |  |  |  |  |  |
| F7-35 | La card è assente per i clienti Sezione, Organo Tecnico Centrale/Struttura Operativa e generico |  |  |  |  |  |  |  |
| F7-36 | L'import classifica correttamente un utente per ciascuno dei 4 tipi cliente, un admin corregge manualmente il tipo di uno di essi, e la dashboard del cliente corretto riflette il nuovo tipo |  |  |  |  |  |  |  |

## Fase 8 (Integrazione dati RUNTS-CAI — Sezioni/Sottosezioni) — 52 test

| ID | Titolo | Esito | Tester | Data | Versione | Evidenza | Anomalia | Note |
|---|---|---|---|---|---|---|---|---|
| F8-01 | La tabella cai_sections ha le colonne richieste da US-801 |  |  |  |  |  |  |  |
| F8-02 | La tabella cai_subsections ha le colonne richieste da US-801 |  |  |  |  |  |  |  |
| F8-03 | La tabella cai_runts_registrations ha le colonne richieste da US-801 |  |  |  |  |  |  |  |
| F8-04 | La tabella cai_financial_statements ha le colonne richieste da US-801 |  |  |  |  |  |  |  |
| F8-05 | La tabella cai_board_members ha le colonne richieste da US-801 (tabella vuota all'origine, struttura pronta) |  |  |  |  |  |  |  |
| F8-06 | La tabella cai_documents ha le colonne richieste da US-801 |  |  |  |  |  |  |  |
| F8-07 | cai_sections usa codice_cai come chiave primaria naturale, non incrementale |  |  |  |  |  |  |  |
| F8-08 | Una sezione ha molte sottosezioni e appartiene a un utente (relazioni Eloquent) |  |  |  |  |  |  |  |
| F8-09 | Eliminare l'utente collegato lascia user_id della sezione a null (FK nullable, mai un errore) |  |  |  |  |  |  |  |
| F8-10 | Una registrazione RUNTS appartiene a una sezione e ha molti bilanci, cariche sociali e documenti |  |  |  |  |  |  |  |
| F8-11 | Il file datapack mancante al percorso indicato stampa un messaggio esplicito e fallisce, mai un errore criptico |  |  |  |  |  |  |  |
| F8-12 | L'opzione --dry-run non scrive alcuna riga né alcun file |  |  |  |  |  |  |  |
| F8-13 | L'import completo popola le sei tabelle con i campi mappati correttamente, collega gli utenti per email case-insensitive, salta gli enti senza match e copia i file degli allegati |  |  |  |  |  |  |  |
| F8-14 | Eseguire l'import due volte sulla stessa fixture è idempotente (nessun duplicato, righe invariate non riscritte) |  |  |  |  |  |  |  |
| F8-15 | make setup esegue cai:import-datapack best-effort, dopo v1:import |  |  |  |  |  |  |  |
| F8-16 | CAI_DATAPACK_HOST_PATH è dichiarata in .env.uat.example, coerente col percorso remoto di default di bin/push-cai-datapack |  |  |  |  |  |  |  |
| F8-17 | CAI_DATAPACK_HOST_PATH è montata in sola lettura nel servizio app, stesso pattern di LEGACY_MEDIA_HOST_PATH |  |  |  |  |  |  |  |
| F8-18 | remote-deploy.sh esegue cai:import-datapack in modo incondizionato, dopo v1:import --anonymize, con il commento esplicito sulla ricopiatura manuale |  |  |  |  |  |  |  |
| F8-19 | Un utente senza cai-directory.view non accede alla lista né al dettaglio |  |  |  |  |  |  |  |
| F8-20 | Un utente con cai-directory.view accede alla lista e vede le colonne attese |  |  |  |  |  |  |  |
| F8-21 | La risorsa è di sola consultazione: nessuna funzione di creazione, modifica o cancellazione |  |  |  |  |  |  |  |
| F8-22 | La tabella è filtrabile per regione |  |  |  |  |  |  |  |
| F8-23 | La tabella è filtrabile per presenza di un utente collegato |  |  |  |  |  |  |  |
| F8-24 | Il dettaglio di una sezione con dati RUNTS, bilanci e allegati mostra i dati attesi |  |  |  |  |  |  |  |
| F8-25 | Il dettaglio di una sezione senza dati RUNTS, bilanci o allegati non genera errori e mostra stati vuoti |  |  |  |  |  |  |  |
| F8-26 | Un utente autorizzato può scaricare un documento CAI |  |  |  |  |  |  |  |
| F8-27 | Un utente senza cai-directory.view non può scaricare un documento CAI |  |  |  |  |  |  |  |
| F8-28 | Un cliente può scaricare un documento della propria sezione CAI |  |  |  |  |  |  |  |
| F8-29 | Un cliente non può scaricare un documento di un'altra sezione CAI |  |  |  |  |  |  |  |
| F8-30 | Un cliente Gruppo Regionale può scaricare un documento di una sezione della propria regione |  |  |  |  |  |  |  |
| F8-31 | Un cliente Gruppo Regionale non può scaricare un documento di una sezione di un'altra regione |  |  |  |  |  |  |  |
| F8-32 | Un utente senza cai-directory.view non accede alla pagina mappa |  |  |  |  |  |  |  |
| F8-33 | Un utente con cai-directory.view vede sulla mappa solo le sezioni geolocalizzate |  |  |  |  |  |  |  |
| F8-34 | Un utente con cai-directory.view può esportare le sezioni correntemente filtrate in CSV |  |  |  |  |  |  |  |
| F8-35 | Un utente con cai-directory.view può esportare le sezioni correntemente filtrate in GeoJSON |  |  |  |  |  |  |  |
| F8-36 | Un utente con cai-directory.view può esportare le sezioni correntemente filtrate in XLSX |  |  |  |  |  |  |  |
| F8-37 | Un utente senza cai-directory.view non vede le azioni di export |  |  |  |  |  |  |  |
| F8-38 | La card CAI mostra i dati della sezione collegata per un cliente Sezione |  |  |  |  |  |  |  |
| F8-39 | La card CAI non mostra mai i dati di un'altra sezione |  |  |  |  |  |  |  |
| F8-40 | La card CAI mostra i dati della sottosezione collegata quando nessuna sezione è collegata |  |  |  |  |  |  |  |
| F8-41 | La card CAI mostra uno stato vuoto esplicito per un cliente Sezione senza sezione o sottosezione collegata |  |  |  |  |  |  |  |
| F8-42 | La card CAI è assente per i clienti non-Sezione |  |  |  |  |  |  |  |
| F8-43 | Un cliente Gruppo Regionale può aprire il dettaglio di una sezione della propria regione |  |  |  |  |  |  |  |
| F8-44 | Un tentativo diretto di aprire una sezione di un'altra regione è respinto (403) |  |  |  |  |  |  |  |
| F8-45 | Un cliente Gruppo Regionale senza regione valorizzata non può aprire alcun dettaglio sezione |  |  |  |  |  |  |  |
| F8-46 | Un cliente Sezione non può accedere alla pagina di dettaglio del Gruppo Regionale |  |  |  |  |  |  |  |
| F8-47 | Un cliente non-customer non può accedere alla pagina di dettaglio del Gruppo Regionale |  |  |  |  |  |  |  |
| F8-48 | Aprire il dettaglio per un utente che non è una Sezione risulta non trovato (404) |  |  |  |  |  |  |  |
| F8-49 | La pagina di dettaglio mostra lo stesso contenuto della dashboard del cliente Sezione, riusando lo stesso Infolist |  |  |  |  |  |  |  |
| F8-50 | La pagina di dettaglio mostra uno stato vuoto esplicito per una sezione senza dati CAI collegati |  |  |  |  |  |  |  |
| F8-51 | La card "Sezioni del gruppo regionale" sulla dashboard cliente collega alla pagina di dettaglio sezione |  |  |  |  |  |  |  |
| F8-52 | Il flusso completo RUNTS-CAI funziona end-to-end: import, matching per email, consultazione staff, dashboard cliente Sezione e dettaglio scoped del cliente Gruppo Regionale |  |  |  |  |  |  |  |
## Riepilogo aggregato (da compilare a collaudo concluso)

| Totale test | PASS | FAIL | BLOCKED | NOT APPLICABLE |
|---|---|---|---|---|
| 664 |  |  |  |  |

