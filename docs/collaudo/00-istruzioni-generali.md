# Istruzioni generali per il collaudo

## 1. Titolo, versione, data e stato del documento

- **Titolo**: Documento di collaudo — Fase 0 (Fondazioni) + Fase 1 (Ticketing core) + Fase 1A (Landing, Login, Recupero password) + Fase 2 (Importazione dal v1 — ETL) + Fase 3 (Sottosistema email)
- **Versione**: 3.0

  La versione 1 era la matrice sintetica preesistente (`docs/collaudo/fase-0-1.php`, manifest di
  tracciabilità sorgente, più il PDF generato a partire da essa dal comando `php artisan
  collaudo:generate`). La versione 2.0 ha affiancato a quel manifest un manuale operativo
  dettagliato per Fase 0/Fase 1, pensato per essere eseguito passo per passo da un tester umano,
  con campi di consuntivazione, glossario, criteri di sospensione/superamento e procedura di
  segnalazione anomalie. La versione 2.1 aggiunge la **Fase 1A** (addendum contenuto, non una nuova
  fase del roadmap PRD §14): landing page pubblica, login e recupero password con la nuova identità
  visiva "Montagna Servizi" — manifest dedicato `docs/collaudo/fase-1a.php`, 16 nuovi test. La
  versione 2.2 aggiorna l'intero pacchetto dopo il completamento della **Fase 2 (ETL)**: l'ambiente
  UAT non usa più un seeder di dati fittizi (`UatSeeder`, rimosso) ma dati reali importati da v1
  (`v1:import --anonymize`) — credenziali, meccanismo di popolamento e composizione del dataset
  aggiornati di conseguenza (punti 4, 6, 9, 13). La versione 2.3 (11 agosto 2026) ridefinisce cosa
  fa `--anonymize` su richiesta del committente: nome/email/ruoli/contenuti restano **sempre** quelli
  reali del dump v1 (mai anonimizzati, a differenza di quanto descritto nella v2.2), l'unica cosa che
  cambia è la password, impostata a `uat` per tutti (punto 9). La versione 3.0 (24 agosto 2026) porta
  finalmente nell'ambito di questo documento le due fasi che le versioni precedenti elencavano come
  escluse pur essendo nel frattempo state realizzate: **Fase 2 (ETL)**, di cui esisteva già un
  manifest di tracciabilità (`docs/collaudo/fase-2.php`) ma nessun manuale operativo dettagliato
  passo-passo, e **Fase 3 (Sottosistema email)**, completata dopo la v2.3 e mai censita fin qui —
  manifest dedicato `docs/collaudo/fase-3.php`, manuali dettagliati `05-fase-2.md`/`06-fase-3.md`,
  74 + 113 nuovi test. Il totale del pacchetto passa così da 146 a 333 test; §4 (Ambito escluso) è
  stato aggiornato di conseguenza (restano escluse solo Fase 4, Fase 5 e Fase 6, non ancora
  costruite), e §10 (Mailpit) riflette il nuovo uso reale di Mailpit anche per la posta di Fase 3.
- **Data di stesura**: 26 luglio 2026 (v2.0), 27 luglio 2026 (v2.1), 10 agosto 2026 (v2.2), 11 agosto
  2026 (v2.3), 24 agosto 2026 (v3.0)
- **Data di pubblicazione ufficiale**: DA VERIFICARE CON IL PRODUCT OWNER
- **Stato**: Bozza per revisione

## 2. Scopo del collaudo

Verificare che il software realizzato in Fase 0 (Fondazioni), Fase 1 (Ticketing core), Fase 1A
(Landing, Login, Recupero password), Fase 2 (Importazione dal v1 — ETL) e Fase 3 (Sottosistema
email) rispetti i requisiti funzionali e le regole di dominio descritti nel PRD di Orchestrator v2,
attraverso un collaudo eseguibile sia da personale funzionale (che non deve conoscere il codice) sia
da personale tecnico (che verifica anche a livello di terminale, database e suite di test
automatica).

Il collaudo copre 333 casi di test, organizzati in 71 argomenti, tracciati uno a uno nei manifest
`docs/collaudo/fase-0-1.php` (Fase 0/Fase 1), `docs/collaudo/fase-1a.php` (Fase 1A),
`docs/collaudo/fase-2.php` (Fase 2) e `docs/collaudo/fase-3.php` (Fase 3) verso un test automatico
realmente esistente nel repository.

## 3. Ambito incluso

Il collaudo copre esattamente i 71 argomenti seguenti (titoli letterali dai manifest di
tracciabilità), per un totale di 333 test.

**Fase 0 — Fondazioni** (56 test, F0-01…F0-56):

| # | Argomento | Test |
|---|---|---|
| 1 | Autenticazione, ruoli e permessi | 13 (F0-01…F0-13) |
| 2 | Schema dati — anagrafiche e organizzazioni | 3 (F0-14…F0-16) |
| 3 | Schema dati — Ticketing (tabelle e vincoli) | 9 (F0-17…F0-25) |
| 4 | Autorizzazioni per modulo — policy deny-by-default | 10 (F0-26…F0-35) |
| 5 | Schema dati — rendicontazione, fundraising, email e infrastruttura di importazione | 5 (F0-36…F0-40) |
| 6 | Diagnostica e configurazione ambiente | 5 (F0-41…F0-45) |
| 7 | Design system e tema del pannello | 1 (F0-46) |
| 8 | Seed di sviluppo | 2 (F0-47…F0-48) |
| 9 | ETL — analizzatori di v1:inspect (solo verifica struttura) | 8 (F0-49…F0-56) |

**Fase 1 — Ticketing core** (74 test, F1-01…F1-74):

| # | Argomento | Test |
|---|---|---|
| 10 | Macchina a stati del ticket | 4 (F1-01…F1-04) |
| 11 | Validazioni di dominio del ticket | 4 (F1-05…F1-08) |
| 12 | Creazione e cambio di stato del ticket: log ed eventi | 6 (F1-09…F1-14) |
| 13 | Propagazione esplicita ai ticket figli | 2 (F1-15…F1-16) |
| 14 | Regole sul record — chi vede e modifica quale ticket | 3 (F1-17…F1-19) |
| 15 | Conversazione del ticket | 6 (F1-20…F1-25) |
| 16 | Allegati sui messaggi | 6 (F1-26…F1-31) |
| 17 | Tracciamento visualizzazioni | 3 (F1-32…F1-34) |
| 18 | Calcolo delle ore lavorate | 6 (F1-35…F1-40) |
| 19 | Scheda ticket — campi e comportamenti | 9 (F1-41…F1-49) |
| 20 | Viste operative della lista ticket | 14 (F1-50…F1-63) |
| 21 | Filtri della lista ticket | 5 (F1-64…F1-68) |
| 22 | Vista di lavoro e landing per ruolo | 3 (F1-69…F1-71) |
| 23 | Verifica end-to-end di Fase 1 | 3 (F1-72…F1-74) |

**Fase 1A — Landing, Login, Recupero password** (16 test, F1A-01…F1A-16):

| # | Argomento | Test |
|---|---|---|
| 24 | Landing pubblica | 2 (F1A-01…F1A-02) |
| 25 | Login | 6 (F1A-03…F1A-08) |
| 26 | Recupero password | 7 (F1A-09…F1A-15) |
| 27 | Identità visiva e separazione dai temi | 1 (F1A-16) |

**Fase 2 — Importazione dal v1 (ETL)** (74 test, F2-01…F2-74):

| # | Argomento | Test |
|---|---|---|
| 28 | Scaffold ETL e runner (US-201) | 5 (F2-01…F2-05) |
| 29 | Utenti e ruoli/permessi (US-202) | 5 (F2-06…F2-10) |
| 30 | Organizzazioni e membership (US-203) | 3 (F2-11…F2-13) |
| 31 | Documentazione e tag (US-204) | 4 (F2-14…F2-17) |
| 32 | Mappatura ticket (US-205) | 5 (F2-18…F2-22) |
| 33 | Gerarchia dei ticket (US-206) | 3 (F2-23…F2-25) |
| 34 | Tag e partecipanti dei ticket (US-207) | 4 (F2-26…F2-29) |
| 35 | Log dei ticket (US-208) | 4 (F2-30…F2-33) |
| 36 | Visualizzazioni dei ticket (US-209) | 4 (F2-34…F2-37) |
| 37 | Parser dei messaggi dei ticket (US-210) | 5 (F2-38…F2-42) |
| 38 | Allegati (US-211) | 4 (F2-43…F2-46) |
| 39 | Report di attività (US-212) | 4 (F2-47…F2-50) |
| 40 | Opportunità e punteggi di fundraising (US-213) | 5 (F2-51…F2-55) |
| 41 | Progetti e partner di fundraising (US-214) | 4 (F2-56…F2-59) |
| 42 | Derive (US-215) | 5 (F2-60…F2-64) |
| 43 | Comando v1:validate (US-216) | 5 (F2-65…F2-69) |
| 44 | Password fissa fuori produzione (US-217, ridefinito da US-R08) | 4 (F2-70…F2-73) |
| 45 | Fixture CI (US-218) | 1 (F2-74) |

**Fase 3 — Sottosistema email** (113 test, F3-01…F3-113):

| # | Argomento | Test |
|---|---|---|
| 46 | Configurazione IMAP e interfaccia InboundMailTransport (US-301) | 4 (F3-01…F3-04) |
| 47 | Comando mail:fetch-inbound — fetch e archiviazione grezza (US-302) | 4 (F3-05…F3-08) |
| 48 | Parsing del messaggio — subject, corpo, charset (US-303) | 5 (F3-09…F3-13) |
| 49 | Classificazione anti-loop e scarti obbligatori (US-304) | 7 (F3-14…F3-20) |
| 50 | Identificazione del mittente (US-305) | 4 (F3-21…F3-24) |
| 51 | Risoluzione del thread — VERP, In-Reply-To, subject, euristica (US-306) | 6 (F3-25…F3-30) |
| 52 | Applicazione — creazione ticket o nuovo messaggio, notifiche post-commit (US-307) | 5 (F3-31…F3-35) |
| 53 | Mittente non riconosciuto — quarantena (US-308) | 5 (F3-36…F3-40) |
| 54 | Allegati inbound (US-309) | 5 (F3-41…F3-45) |
| 55 | Layout email unico e componenti riusabili (US-310) | 4 (F3-46…F3-49) |
| 56 | Mailable E1/E2 — conferme di ricezione/apertura ticket (US-311) | 5 (F3-50…F3-54) |
| 57 | Mailable E3/E9 — notifica staff (US-312) | 4 (F3-55…F3-58) |
| 58 | Mailable E4 — cambio di stato (US-313) | 4 (F3-59…F3-62) |
| 59 | Mailable E5 — nuovo messaggio sul ticket (US-314) | 3 (F3-63…F3-65) |
| 60 | Mailable E6 — assegnazione (US-315) | 3 (F3-66…F3-68) |
| 61 | Mailable E7 — reminder ticket in attesa + scheduling (US-316) | 2 (F3-69…F3-70) |
| 62 | Preferenze di notifica — applicazione effettiva (US-317) | 2 (F3-71…F3-72) |
| 63 | Regole di destinazione — attore × transizione → destinatari (US-318) | 3 (F3-73…F3-75) |
| 64 | Bounce, DSN e soppressioni (US-319) | 6 (F3-76…F3-81) |
| 65 | Localizzazione reale delle comunicazioni (US-320) | 5 (F3-82…F3-86) |
| 66 | Amministrazione email — Registro e dettaglio (US-321) | 5 (F3-87…F3-91) |
| 67 | Amministrazione email — Azioni e quarantena (US-322) | 6 (F3-92…F3-97) |
| 68 | Amministrazione email — Soppressioni e metriche (US-323) | 4 (F3-98…F3-101) |
| 69 | Voce di menu Email con Mailpit come prima sotto-voce (US-324) | 3 (F3-102…F3-104) |
| 70 | Comando mail:retry-failed (US-325) | 3 (F3-105…F3-107) |
| 71 | Checkpoint di fine fase — verifica end-to-end su dati reali (US-326) | 6 (F3-108…F3-113) |

Il dettaglio di ciascun test (descrizione, passi, esito atteso, campi di consuntivazione) è nei
file `02-fase-0.md`, `03-fase-1.md`, `04-fase-1a.md`, `05-fase-2.md` e `06-fase-3.md` del pacchetto.

## 4. Ambito escluso

Sono esplicitamente **fuori scopo** di questa release e di questo collaudo:

- **Fase 4 — Generazione PDF di documentazione/report**: non in ambito.
- **Fase 5 — UI fundraising completa**: solo lo schema dati e le opportunità/progetti di
  fundraising importati da v1 (Fase 2) sono verificabili come dati; l'interfaccia utente dedicata al
  fundraising non è in ambito di questo collaudo.
- **Fase 6 — Automazioni schedulate del ciclo di vita del ticket e rifinitura della vista di
  lavoro** (drag&drop incluso, es. `tickets:auto-close-released`): non in ambito. Le righe della
  macchina a stati che in futuro serviranno a questi comandi schedulati esistono già a livello di
  tabella dichiarativa, ma nessun comando/cron di questo tipo gira in questa release. Diverso dalle
  automazioni schedulate del sottosistema email (`mail:fetch-inbound`, il reminder E7,
  `mail:retry-failed`), introdotte in Fase 3 e quindi **incluse** in questo collaudo (vedi argomenti
  47, 61 e 70 di §3), oltre a **E8 (digest periodico)**, **E10 (report di attività disponibile)** ed
  **E11 (developer senza ticket in lavorazione)**, tre comunicazioni del catalogo email assegnate
  esplicitamente alla Fase 6 e quindi non ancora costruite.

## 5. Riferimenti tecnici e funzionali

- `../PRD-ORCHESTRATOR-V2.md` — specifica di prodotto completa (nella root del progetto, un
  livello sopra questo repository).
- `../../tasks/prd-fase-2-etl-import-v1.md` e `../../tasks/prd-fase-3-email-subsystem.md` — PRD
  specifici di Fase 2 e Fase 3 (due livelli sopra questo repository, cartella `tasks/` del
  monorepo), con lo user-story-by-user-story dettaglio da cui sono derivati i manifest
  `fase-2.php`/`fase-3.php` e i manuali `05-fase-2.md`/`06-fase-3.md`.
- `docs/ticket-lifecycle.md` — descrizione della macchina a stati del ticket, delle transizioni
  ammesse e delle regole di dominio associate.
- `docs/collaudo/fase-0-1.php`, `docs/collaudo/fase-1a.php`, `docs/collaudo/fase-2.php`,
  `docs/collaudo/fase-3.php` — manifest di tracciabilità sorgente: collegano ogni test di questo
  manuale a un test automatico realmente esistente nel repository.
- `CLAUDE.md` (root del repository) — note tecniche di implementazione per fase/story, utili al
  tester tecnico per capire le scelte di progettazione sottostanti (per Fase 3 in particolare, le
  sezioni sulla pipeline email inbound/outbound e sui bug reali già trovati e corretti).

## 6. Definizioni e glossario

- **Ticket**: la richiesta/segnalazione/attività tracciata dal sistema (bug, richiesta funzionale,
  richiesta di assistenza, o attività interna di sprint), unità base del ticketing.
- **Stato del ticket**: la fase del ciclo di vita in cui si trova un ticket (es. nuovo, assegnato,
  da fare, in lavorazione, in test, testato, rilasciato, concluso, in attesa, in problema,
  rifiutato, backlog — 12 stati in totale).
- **Transizione**: il passaggio di un ticket da uno stato a un altro, ammesso solo se previsto
  dalla tabella delle transizioni e se chi lo richiede ne ha diritto.
- **Assegnatario**: l'utente staff a cui un ticket è stato affidato per la lavorazione.
- **Tester**: l'utente staff incaricato di collaudare internamente un ticket prima del rilascio.
- **Richiedente**: l'utente (tipicamente un socio/cliente) che ha aperto il ticket.
- **Ruolo**: la categoria applicativa assegnata a un utente (Admin, Developer, Manager, Customer,
  Fundraising): determina l'accesso al pannello e, insieme ai permessi, cosa l'utente può fare.
- **Permesso**: una singola capacità concessa a un utente (direttamente o tramite il ruolo),
  espressa con la convenzione `<dominio>.<azione>[.<ambito>]` (es. `ticket.view.own` = "può vedere
  i propri ticket").
- **Policy**: la regola applicativa che decide se un utente può compiere una certa azione su un
  determinato record, applicata per difetto in modo restrittivo (nessun accesso senza un permesso
  esplicito).
- **Ticket padre/figlio**: un ticket può avere ticket "figli" collegati gerarchicamente (max un
  livello di profondità); un cambio di stato del padre non si propaga mai ai figli in automatico,
  solo se richiesto esplicitamente.
- **Tag**: etichetta associabile a uno o più ticket, usata anche come riferimento a una
  commessa/area di lavoro.
- **Organizzazione**: l'ente (es. una sezione CAI) a cui un utente cliente può essere collegato.
- **Storico (ticket_logs)**: il registro immutabile dei cambiamenti rilevanti di un ticket (cambi
  di stato, assegnazioni, messaggi postati, allegati aggiunti/rimossi), sempre scritto dal sistema,
  mai modificabile manualmente.
- **Visualizzazione (ticket_view)**: la registrazione automatica del fatto che un utente ha aperto
  la scheda di un ticket in un determinato giorno.
- **Seed/importazione dati**: la procedura automatica che popola il database dell'ambiente UAT
  prima del collaudo. Non è più un seeder di dati fittizi: è l'ETL reale (`v1:import
  --anonymize`), che importa dal dump di produzione v1 senza alterare nomi/email/contenuti (sempre
  reali) e impone solo una password fissa nota a tutti gli utenti (vedi punto 9).
- **UAT**: User Acceptance Test, il collaudo di accettazione condotto dall'utente/committente,
  oggetto di questo manuale.
- **Ambiente di collaudo**: l'installazione dedicata dell'applicazione, separata da sviluppo e
  produzione, usata per eseguire i test descritti in questo manuale.
- **Manifest di tracciabilità**: i file `docs/collaudo/fase-0-1.php`, `fase-1a.php`, `fase-2.php`,
  `fase-3.php`, che collegano ogni test numerato (es. F0-01, F2-01, F3-01) a un test automatico
  realmente esistente nel codice.
- **Sottosistema email (Fase 3)**: la pipeline che legge le email in arrivo su una casella IMAP
  (`mail:fetch-inbound`), le trasforma in ticket/messaggi, e invia le comunicazioni automatiche del
  catalogo E1-E9 in uscita, sempre in coda, mai in modo sincrono. **Mailpit** è l'interfaccia web che
  intercetta ogni email in uscita dall'ambiente di collaudo (nessuna email esce mai verso un
  indirizzo reale fuori da questo strumento, vedi punto 10).
- **Anomalia**: uno scostamento tra il comportamento osservato durante il collaudo e quello atteso,
  da segnalare secondo la procedura del punto 19.
- **PASS**: il test è stato eseguito e il comportamento osservato corrisponde a quello atteso.
- **FAIL**: il test è stato eseguito e il comportamento osservato non corrisponde a quello atteso;
  richiede una anomalia tracciata.
- **BLOCKED**: il test non ha potuto essere eseguito per una causa esterna al test stesso (es.
  ambiente non raggiungibile, un test precedente propedeutico non superato).
- **NOT APPLICABLE**: il test non è pertinente nel contesto specifico in cui viene eseguito il
  collaudo (da motivare sempre esplicitamente nei campi di consuntivazione).

## 7. Ruoli coinvolti nel collaudo

- **Tester funzionale**: esegue i test classificati come manuali da interfaccia utente o misti,
  senza necessità di conoscere il codice sorgente. Opera dal pannello dell'applicazione con le
  credenziali fornite al punto 9.
- **Tester tecnico/sviluppatore**: esegue i test classificati come tecnici da riga di comando, da
  database o automatici. Ha accesso a terminale, database, repository del progetto e alla suite di
  test automatica (Pest).
- **Product Owner**: approva le classificazioni segnalate come "DA VERIFICARE CON IL PRODUCT
  OWNER" in questo documento e nel resto del pacchetto, e firma il verbale conclusivo di collaudo
  (`08-verbale-collaudo.md`).

## 8. Ambiente UAT

- **URL applicazione**: `https://ticket-uat.montagnaservizi.com` — pannello Filament raggiungibile
  al percorso `/admin` (login: `https://ticket-uat.montagnaservizi.com/admin/login`).
- **Architettura**: ambiente pubblico dedicato esclusivamente al collaudo, separato dagli ambienti
  di sviluppo e produzione, con i dati reali reimportati da v1 ad ogni pubblicazione (vedi punto
  13). Non
  sono qui descritti dettagli infrastrutturali interni (porte, nomi dei container, topologia dei
  servizi): sono note operative per lo sviluppatore, non necessarie al collaudo funzionale.
- **Stato di attivazione**: **DA VERIFICARE CON IL PRODUCT OWNER** — l'infrastruttura server
  dell'ambiente UAT reale (virtual host, certificati, primo deploy) non risultava ancora
  completata al momento della stesura di questo documento. Prima di avviare un ciclo di collaudo,
  verificare con il Product Owner la data di attivazione effettiva e raggiungibilità dell'URL sopra
  indicato.

## 9. Credenziali e profili di test

Le identità Admin/Developer/Fundraising/Customer corrispondono a **4 utenti reali del sistema
v1**, scelti come riferimento fisso: nome, email, ruolo e ogni contenuto associato (ticket,
conversazioni, ecc.) sono quelli **reali** del dump — l'ETL (`v1:import --anonymize`) non li altera
mai (a differenza del design originale di questo progetto: nome/email non vengono più anonimizzati).
L'unica cosa che `--anonymize` cambia è la password: sempre `uat` per ogni utente importato, mai
la password v1 reale della persona. Il ruolo Manager non esiste in alcun utente v1 (introdotto solo
in questa versione del prodotto): il relativo account è creato appositamente dal comando
`collaudo:ensure-manager-account`, eseguito automaticamente a fine `make setup` e ad ogni deploy
UAT, con la stessa password fissa `uat`. Nessuna registrazione manuale è necessaria.

| Ruolo | Nome utente | Email | Password |
|---|---|---|---|
| Admin | Montagna Servizi (account aziendale, non una persona) | info@montagnaservizi.com | uat |
| Developer | Lorena Sava | lorena.sava@montagnaservizi.com | uat |
| Manager | Manager Collaudo (account creato ex novo, nessun utente v1 ha questo ruolo) | manager@oc.test | uat |
| Customer | Sentiero Italia CAI - SICAI | infosentieroitalia@cai.it | uat |
| Fundraising | Sara Mariani | sara.mariani@montagnaservizi.com | uat |

Questi sono i soli 5 ruoli applicativi esistenti nel sistema: non esiste un ruolo "editor" né altri
ruoli oltre a questi cinque. Le identità reali sopra sono persone/enti reali di Montagna Servizi:
trattare questo documento di conseguenza (uso interno al collaudo, non distribuzione pubblica).

## 10. Accesso a Mailpit

URL: `https://mailpit-ticket-uat.montagnaservizi.com`.

Nessuno dei 146 test di Fase 0/Fase 1/Fase 1A/Fase 2 relativi alla conversazione del ticket
richiede Mailpit per quella parte specifica: nel collaudo di Fase 0/1 ogni messaggio di
conversazione viaggia sempre sul canale "web", mai su un canale email reale (l'unica eccezione già
presente da subito è la **Fase 1A**, che usa Mailpit per il flusso di recupero password — F1A-09,
F1A-10, F1A-11 — verificabile in UAT esattamente su questo URL).

Con la **Fase 3 (Sottosistema email)**, Mailpit diventa invece centrale: ogni email in uscita
generata dalla pipeline (conferme di ricezione/apertura ticket, notifiche di cambio stato/nuovo
messaggio/assegnazione, reminder, notifiche staff) è intercettata qui, mai recapitata a un
indirizzo reale — è quindi il primo posto da controllare per gran parte dei test manuali "MANUALE
UI + MAILPIT" di `06-fase-3.md` (in particolare gli argomenti 56-61 e 64, Mailable E1-E7).
Dal pannello admin, il gruppo di navigazione "Email" espone anche un link diretto "Mailpit" (prima
sotto-voce, argomento 69, F3-102…F3-104): non serve ricordare a memoria questo URL durante il
collaudo di Fase 3.

Le credenziali di accesso HTTP Basic Auth a Mailpit sono fornite separatamente dal committente
(non riportate in questo documento pubblico).

## 11. Browser e dispositivi supportati

**DA VERIFICARE CON IL PRODUCT OWNER**: nel codice del progetto non è presente alcuna
configurazione o dichiarazione di browser/dispositivi ufficialmente supportati per il pannello.

In assenza di un'indicazione ufficiale, si suggerisce come default ragionevole per l'esecuzione del
collaudo:

- Browser desktop nelle versioni correnti: Google Chrome, Mozilla Firefox, Microsoft Edge.
- Solo desktop: il pannello Filament non è stato validato per l'uso da dispositivo mobile in questa
  fase, l'esecuzione da smartphone/tablet non è raccomandata per il collaudo.

## 12. Prerequisiti generali

Per tutti i tester:

- Accesso a Internet e raggiungibilità dell'URL dell'ambiente UAT (punto 8).
- Le credenziali di test fornite al punto 9.

Aggiuntivi per il tester tecnico/sviluppatore (test classificati come tecnici da riga di comando,
da database o automatici):

- Accesso a un terminale/SSH sul repository del progetto, oppure un ambiente PHP/Docker locale
  equivalente configurato secondo `CLAUDE.md`.
- Familiarità con `php artisan` e con la suite di test Pest (`php artisan test` /
  `vendor/bin/pest`), necessaria per eseguire ed interpretare i test automatici collegati nel
  manifest di tracciabilità.

Aggiuntivi per i test di **Fase 3 (Sottosistema email)** che richiedono un invio reale in ingresso
(non solo la lettura da Mailpit, già coperta dalle credenziali del punto 9): l'indirizzo email reale
della casella monitorata da `mail:fetch-inbound` in UAT, fornito separatamente dal committente
(non riportato in questo documento pubblico) — necessario per i pochi test di `06-fase-3.md` che
richiedono di inviare davvero un'email verso l'ambiente (es. verifica del plus-addressing
`ticket+<ulid>@dominio`, argomento 51). La maggioranza dei test di Fase 3 non richiede questo
accesso: usa invece email già presenti/simulabili come descritto test per test.

## 13. Preparazione e ripristino dei dati

L'ambiente UAT viene popolato dall'ETL reale (`v1:import --anonymize`, non più un seeder di dati
fittizi), eseguito **ad ogni deploy** con un ciclo completo di `migrate:fresh` seguito dall'import:
questo significa che **l'intero database viene ricreato da zero e reimportato** a ogni nuova
pubblicazione dell'ambiente, esattamente come prima con `UatSeeder` — cambia la fonte dei dati, non
il fatto che si riparta sempre da zero.

Subito dopo un deploy, l'ambiente contiene sempre:

- **L'intero storico reale del sistema v1** (nomi, email e contenuti reali, mai anonimizzati) al
  momento dell'ultimo dump caricato sul server: dell'ordine di alcune centinaia di utenti, alcune
  migliaia di ticket distribuiti su tutti gli stati e tipi realmente occorsi in produzione (non un
  piccolo campione curato a mano), tag, organizzazioni, pagine di documentazione, report di
  attività e opportunità/progetti di fundraising reali.
- Le **5 identità di riferimento** del punto 9 (Admin/Developer/Manager/Customer/Fundraising): per
  le 4 legate a un utente v1 reale, la stessa email a ogni reimport perché è la loro email reale,
  invariata nel dump da un dump all'altro (non un'assegnazione fissa artificiale); Manager resta
  l'unico account creato ex novo con email fissa.

**Cambia rispetto alla versione precedente di questo documento**: la composizione *esatta* del
dataset (quanti ticket in un determinato stato, quali tag esistono, quali pagine di documentazione)
**non è più fissa e nota in anticipo**: dipende dal dump v1 più recente caricato sul server al
momento del deploy, e può cambiare quando viene caricato un dump più aggiornato. Un test che nel
dettaglio (`02-fase-0.md`/`03-fase-1.md`/`05-fase-2.md`/`06-fase-3.md`) presuppone "esiste un
ticket con questa caratteristica specifica" richiede quindi di **verificarlo empiricamente nell'ambiente al momento del collaudo**
(es. tramite i filtri della lista ticket, punto 21) invece di assumerlo da un elenco fisso — oppure,
dove il test lo richiede esplicitamente, di crearlo ad-hoc secondo la convenzione del punto 14.

**Punto critico per chi pianifica un collaudo su più giorni**: qualunque dato creato manualmente
durante un test (un nuovo ticket, un nuovo messaggio, un nuovo tag, ecc.) **non sopravvive a un
nuovo deploy** dell'ambiente UAT. Se il ciclo di collaudo si estende su più giorni e nel frattempo
viene pubblicato un nuovo deploy (es. per un fix a un'anomalia), il tester deve aspettarsi che
tutti i dati creati manualmente fino a quel momento siano stati cancellati insieme al resto del
database, e deve rieseguire i passi di creazione dati dei test non ancora consuntivati. Verificare
sempre con il Product Owner/team di sviluppo se un deploy è avvenuto prima di riprendere un ciclo di
collaudo interrotto.

## 14. Convenzioni per nominare i dati creati durante il test

Qualunque dato creato manualmente dal tester durante l'esecuzione di un test (titolo di un ticket,
testo di un messaggio, nome di un tag, ecc.) deve seguire la convenzione:

```
COLL-[ID-TEST]-[DATA]-[PROGRESSIVO]
```

Esempio: `COLL-F1-09-20260726-01` (test F1-09, eseguito il 26/07/2026, primo dato creato in quel
test).

Questa convenzione permette di riconoscere a colpo d'occhio quali dati siano stati creati durante
il collaudo (per differenziarli da quelli del seeder) e di ripulirli in seguito se necessario. Va
usata nel titolo o nel testo di qualunque ticket, messaggio o tag creato manualmente durante un
test — non è comunque necessaria per la pulizia dell'ambiente in vista di un nuovo ciclo di
collaudo, dato che un nuovo deploy rigenera comunque l'intero dataset (punto 13).

## 15. Modalità di raccolta delle evidenze

Per ogni test eseguito, raccogliere l'evidenza più adatta al tipo di test:

- **Screenshot**: formato PNG o JPG, nome file suggerito `<ID-TEST>-<progressivo>.png` (es.
  `F1-41-01.png`).
- **URL del record**: l'indirizzo completo della pagina Filament aperta al momento della verifica.
- **ID pubblico del ticket**: l'identificativo mostrato nell'interfaccia (non l'id numerico interno
  del database).
- **Output di comando**: per i test tecnici da riga di comando, l'output copiato integralmente
  (mai riassunto o parafrasato) del comando eseguito.
- **Export della suite di test automatica**: per i test collegati a un test automatico Pest,
  l'output completo dell'esecuzione (`php artisan test` / `vendor/bin/pest`), che indica
  chiaramente il nome del test e l'esito.

## 16. Criteri di sospensione e ripresa del collaudo

**Quando sospendere il collaudo**:

- L'ambiente UAT non è raggiungibile.
- Un'anomalia classificata come Critica (vedi punto 18) blocca l'esecuzione di più del 20% dei test
  ancora da eseguire.
- Le credenziali fornite al punto 9 non funzionano.

**Quando riprendere**: solo dopo conferma esplicita che la causa del blocco è stata risolta (es. un
nuovo deploy che corregge l'anomalia, un fix applicato e verificato, il ripristino della
raggiungibilità dell'ambiente). Alla ripresa, verificare sempre se nel frattempo è avvenuto un
nuovo deploy: in tal caso vale quanto descritto al punto 13 sulla perdita dei dati creati
manualmente.

## 17. Criteri generali di superamento

Il collaudo nel suo complesso è considerato superato se, al termine dell'esecuzione dei 333 test:

- Non è aperta alcuna anomalia classificata come Critica.
- Almeno il 95% dei test applicabili (esclusi quelli classificati NOT APPLICABLE) è in stato PASS.
- Ogni test in stato FAIL ha un'anomalia tracciata con priorità assegnata secondo la
  classificazione del punto 18.

## 18. Classificazione delle anomalie

Ogni anomalia rilevata durante il collaudo va classificata in uno dei 4 livelli seguenti:

| Livello | Definizione |
|---|---|
| **Critica** | Blocca il collaudo, oppure rappresenta una perdita o un'esposizione di dati. |
| **Alta** | Una funzionalità richiesta dal PRD non funziona come atteso e non esiste alcun modo di aggirare il problema. |
| **Media** | La funzionalità non è conforme a quanto atteso, ma esiste un aggiramento praticabile. |
| **Bassa** | Difetto estetico o cosmetico, non impatta il risultato funzionale del test. |

## 19. Procedura per segnalare un'anomalia

Alla rilevazione di uno scostamento tra comportamento atteso e osservato durante un test:

1. **Assegnare un ID progressivo** all'anomalia, nel formato `AN-NNN` (es. `AN-001`, `AN-002`, in
   ordine di apertura, mai riutilizzato).
2. **Titolo**: una frase breve che descrive lo scostamento osservato.
3. **ID del test collegato**: l'identificativo del test in cui l'anomalia è stata rilevata (es.
   F1-44).
4. **Descrizione riproducibile**: passi eseguiti in ordine, comportamento atteso, comportamento
   effettivamente ottenuto.
5. **Evidenze allegate**: screenshot, URL, output di comando secondo il punto 15.
6. **Priorità**: assegnata secondo la classificazione del punto 18 (Critica/Alta/Media/Bassa).
7. **Stato**: una delle quattro fasi Aperta → In analisi → Risolta → Chiusa, aggiornata man mano che
   l'anomalia viene lavorata.

Ogni anomalia va registrata nel registro degli esiti (`07-registro-esiti.md`) e richiamata nel
verbale conclusivo di collaudo (`08-verbale-collaudo.md`).
