# Registro degli esiti

> Torna a [`README.md`](README.md) · Matrice di tracciabilità: [`01-matrice-tracciabilita.md`](01-matrice-tracciabilita.md)

Tabella da compilare durante il collaudo, una riga per test. Il campo "Esito" accetta solo uno tra
`PASS`, `FAIL`, `BLOCKED`, `NOT APPLICABLE` (vedi §17 di `00-istruzioni-generali.md` per i criteri generali
e la sezione "Criterio di superamento" di ciascun test in `02-fase-0.md`/`03-fase-1.md`/`04-fase-1a.md` per il
criterio specifico). Il campo "Anomalia" riporta l'ID assegnato secondo §19 di `00-istruzioni-generali.md` (es. `AN-001`),
lasciare vuoto se non ci sono anomalie da segnalare per quel test.

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
| F0-47 | Il seed di sviluppo rifiuta di girare in produzione e popola un ambiente completo |  |  |  |  |  |  |  |
| F0-48 | Una seconda esecuzione del seed di sviluppo non duplica ticket, tag o documentazione |  |  |  |  |  |  |  |
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

## Riepilogo aggregato (da compilare a collaudo concluso)

| Totale test | PASS | FAIL | BLOCKED | NOT APPLICABLE |
|---|---|---|---|---|
| 146 |  |  |  |  |

