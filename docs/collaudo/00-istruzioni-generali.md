# Istruzioni generali per il collaudo

## 1. Titolo, versione, data e stato del documento

- **Titolo**: Documento di collaudo — Fase 0 (Fondazioni) + Fase 1 (Ticketing core)
- **Versione**: 2.0

  La versione 1 era la matrice sintetica preesistente (`docs/collaudo/fase-0-1.php`, manifest di
  tracciabilità sorgente, più il PDF generato a partire da essa dal comando `php artisan
  collaudo:generate`). Questa versione 2.0 non sostituisce quel manifest — resta la fonte di
  tracciabilità verso i test automatici — ma lo affianca con un manuale operativo dettagliato,
  pensato per essere eseguito passo per passo da un tester umano, con campi di consuntivazione,
  glossario, criteri di sospensione/superamento e procedura di segnalazione anomalie.
- **Data di stesura**: 26 luglio 2026
- **Data di pubblicazione ufficiale**: DA VERIFICARE CON IL PRODUCT OWNER
- **Stato**: Bozza per revisione

## 2. Scopo del collaudo

Verificare che il software realizzato in Fase 0 (Fondazioni) e Fase 1 (Ticketing core) rispetti i
requisiti funzionali e le regole di dominio descritti nel PRD di Orchestrator v2, attraverso un
collaudo eseguibile sia da personale funzionale (che non deve conoscere il codice) sia da personale
tecnico (che verifica anche a livello di terminale, database e suite di test automatica).

Il collaudo copre 130 casi di test, organizzati in 23 argomenti, tracciati uno a uno nel manifest
`docs/collaudo/fase-0-1.php` verso un test automatico realmente esistente nel repository.

## 3. Ambito incluso

Il collaudo copre esattamente i 23 argomenti seguenti (titoli letterali dal manifest di
tracciabilità), per un totale di 130 test.

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

Il dettaglio di ciascun test (descrizione, passi, esito atteso, campi di consuntivazione) è nei
file `02-fase-0.md` e `03-fase-1.md` del pacchetto.

## 4. Ambito escluso

Sono esplicitamente **fuori scopo** di questa release e di questo collaudo:

- **Fase 2 — Importazione dati reali dal sistema v1** (ETL): non ancora iniziata. Tutti i dati
  presenti in UAT sono dati fittizi generati da un seeder, mai dati reali migrati da v1.
- **Fase 3 — Sottosistema email reale** (invio/ricezione): non costruito. Nel collaudo di questa
  release ogni messaggio di conversazione del ticket viaggia sempre sul canale "web": nessuna email
  reale viene mai inviata o ricevuta.
- **Fase 4 — Generazione PDF di documentazione/report**: non in ambito.
- **Fase 5 — UI fundraising completa**: solo lo schema dati e le opportunità/progetti di
  fundraising creati dal seeder sono verificabili come dati; l'interfaccia utente dedicata al
  fundraising non è in ambito di questo collaudo.
- **Fase 6 — Automazioni schedulate e rifinitura della vista di lavoro** (drag&drop incluso): non
  in ambito. Le righe della macchina a stati che in futuro serviranno a questi comandi schedulati
  esistono già a livello di tabella dichiarativa, ma nessun comando/cron gira in questa release.

## 5. Riferimenti tecnici e funzionali

- `../PRD-ORCHESTRATOR-V2.md` — specifica di prodotto completa (nella root del progetto, un
  livello sopra questo repository).
- `docs/ticket-lifecycle.md` — descrizione della macchina a stati del ticket, delle transizioni
  ammesse e delle regole di dominio associate.
- `docs/collaudo/fase-0-1.php` — manifest di tracciabilità sorgente: collega ogni test di questo
  manuale a un test automatico realmente esistente nel repository.
- `CLAUDE.md` (root del repository) — note tecniche di implementazione per fase/story, utili al
  tester tecnico per capire le scelte di progettazione sottostanti.

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
- **Seed/Seeder**: procedura automatica che popola il database con un insieme di dati di partenza;
  in ambiente UAT il seeder dedicato è `UatSeeder`.
- **UAT**: User Acceptance Test, il collaudo di accettazione condotto dall'utente/committente,
  oggetto di questo manuale.
- **Ambiente di collaudo**: l'installazione dedicata dell'applicazione, separata da sviluppo e
  produzione, usata per eseguire i test descritti in questo manuale.
- **Manifest di tracciabilità**: il file `docs/collaudo/fase-0-1.php`, che collega ogni test
  numerato (es. F0-01) a un test automatico realmente esistente nel codice.
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
  (`05-verbale-collaudo.md`).

## 8. Ambiente UAT

- **URL applicazione**: `https://ticket-uat.montagnaservizi.com` — pannello Filament raggiungibile
  al percorso `/admin` (login: `https://ticket-uat.montagnaservizi.com/admin/login`).
- **Architettura**: ambiente pubblico dedicato esclusivamente al collaudo, separato dagli ambienti
  di sviluppo e produzione, con dati fittizi rigenerati ad ogni pubblicazione (vedi punto 13). Non
  sono qui descritti dettagli infrastrutturali interni (porte, nomi dei container, topologia dei
  servizi): sono note operative per lo sviluppatore, non necessarie al collaudo funzionale.
- **Stato di attivazione**: **DA VERIFICARE CON IL PRODUCT OWNER** — l'infrastruttura server
  dell'ambiente UAT reale (virtual host, certificati, primo deploy) non risultava ancora
  completata al momento della stesura di questo documento. Prima di avviare un ciclo di collaudo,
  verificare con il Product Owner la data di attivazione effettiva e raggiungibilità dell'URL sopra
  indicato.

## 9. Credenziali e profili di test

Tutti gli utenti sono creati automaticamente dal seeder di ambiente (`UatSeeder`) con la stessa
password. Nessuna registrazione manuale è necessaria.

| Ruolo | Nome utente | Email | Password |
|---|---|---|---|
| Admin | Amministratore Collaudo | admin@orchestrator.local | password |
| Developer | Sviluppatore Collaudo | developer@orchestrator.local | password |
| Manager | Manager Collaudo | manager@orchestrator.local | password |
| Customer | Socio CAI Collaudo | customer@orchestrator.local | password |
| Fundraising | Referente Fundraising Collaudo | fundraising@orchestrator.local | password |

Questi sono i soli 5 ruoli applicativi esistenti nel sistema: non esiste un ruolo "editor" né altri
ruoli oltre a questi cinque.

## 10. Accesso a Mailpit

URL: `https://mailpit-ticket-uat.montagnaservizi.com`.

In questa release **nessuno dei 130 test di Fase 0/Fase 1 richiede l'uso di Mailpit**: il
sottosistema email reale (invio/ricezione) è fuori scopo (Fase 3, vedi punto 4) e ogni messaggio di
conversazione del ticket viaggia sempre sul canale "web", mai su un canale email reale. Nessun test
di questo pacchetto è quindi classificato come "MANUALE UI + MAILPIT".

L'URL è riportato qui solo come riferimento, disponibile per le fasi future in cui il sottosistema
email sarà effettivamente costruito e verrà integrato nel collaudo.

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

## 13. Preparazione e ripristino dei dati

L'ambiente UAT viene popolato dal seeder dedicato `UatSeeder` (`database/seeders/UatSeeder.php`),
eseguito **ad ogni deploy** con un ciclo completo di `migrate:fresh` seguito dal seed: questo
significa che **l'intero database viene ricreato da zero e ripopolato** a ogni nuova pubblicazione
dell'ambiente.

Subito dopo un deploy, l'ambiente contiene sempre:

- 5 utenti, uno per ciascuno dei 5 ruoli applicativi (vedi punto 9).
- 2 organizzazioni ("CAI Sezione di Aosta", "CAI Sezione di Trento").
- 10 tag.
- 5 pagine di documentazione (3 di categoria cliente, 2 di categoria interna).
- 40 ticket, distribuiti su tutti i 12 stati e su tutti i 4 tipi di ticket previsti, ciascuno con
  una conversazione minima (un messaggio del richiedente, una presa in carico dell'assegnatario) e
  un tag associato.
- Alcune opportunità di fundraising e relativi progetti collegati (dati di schema, coerenti con
  quanto descritto al punto 4 come fuori scopo per l'interfaccia utente dedicata).

I titoli specifici dei 40 ticket e degli altri dati puntuali non sono elencati qui: sono riportati
nel dettaglio del singolo test dove effettivamente servono (`02-fase-0.md`, `03-fase-1.md`).

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

Il collaudo nel suo complesso è considerato superato se, al termine dell'esecuzione dei 130 test:

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

Ogni anomalia va registrata nel registro degli esiti (`04-registro-esiti.md`) e richiamata nel
verbale conclusivo di collaudo (`05-verbale-collaudo.md`).
