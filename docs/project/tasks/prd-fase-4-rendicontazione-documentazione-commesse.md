# PRD: Fase 4 — Rendicontazione, documentazione, commesse

> Riferimento completo: `PRD-ORCHESTRATOR-V2.md` §6.3 (M3 — Tag/commesse), §6.4 (M4 — Documentation), §6.5 (M5 —
> Activity Report e Organizations), §9.3-9.4 (permessi), §10.2 (automazioni schedulate) e §14 (Roadmap, Fase 4).
> Numerazione story `US-4xx` per restare nel range riservato a questa fase.

## 1. Introduzione/Overview

Fase 2 ha importato dal v1 `tags`, `documentation_pages`, `organizations` e `activity_reports` con i loro dati
storici; Fase 3 ha riscritto il sottosistema email. Questa fase costruisce la **logica applicativa e l'interfaccia**
sopra quei dati: il calcolo del SAL delle commesse, la generazione PDF della documentazione con tag automatico, e
la rendicontazione periodica dell'attività verso clienti e organizzazioni.

Nessuna delle tre aree ha logica applicativa oggi in v2 oltre allo schema e all'ETL: `Tag`, `DocumentationPage`,
`Organization` e `ActivityReport` esistono come tabelle popolate ma senza Model con comportamento, senza Policy,
senza UI Filament e senza generazione PDF. Questa fase li porta a un livello utilizzabile dallo staff (SAL,
documentazione con PDF, rendicontazione) — la vista cliente vera e propria (dashboard, viste ristrette) resta in
Fase 6 (§14): qui il cliente **accede in sola lettura** ai propri report attività, permesso già presente nella
matrice §9.4, ma non riceve ancora un portale dedicato.

**Decisione presa in questa fase**: il renderer PDF per Documentation (§6.4.3) e Activity Report (§6.5.3) è
`spatie/laravel-pdf` (Chromium headless), non `dompdf`. Motivazione: nello stesso repository il PDF di collaudo
usava `dompdf` ed è stato abbandonato perché "mai stato in grado di riprodurre fedelmente la carta intestata
Montagna Servizi" (vedi `CLAUDE.md`, sezione sul PDF di collaudo via pdfLaTeX) — sostituito con pdflatex, ma solo
per un comando manuale eseguito in dev, mai in produzione. Documentation PDF e Activity Report PDF, al contrario,
**devono generarsi in coda in produzione/UAT** per utenti reali: pdflatex non è un'opzione (deliberatamente escluso
dall'immagine UAT per non appesantirla di TeX Live), mentre Chromium headless rende CSS moderno correttamente e
permette di riusare gli stessi stili del design system (`docs/design-system.md`/`resources/css/theme.css`) per
header/footer con logo e dati societari — la stessa fedeltà che è mancata a dompdf.

## 2. Goals

- Il SAL di una commessa (`worked_hours / estimated_hours * 100`) esiste in **un'unica implementazione**, corretta
  su stima nulla, nessun ticket collegato, e arrotondamenti (requisito di test esplicito, §13.1).
- Creare/rinominare una pagina di documentazione mantiene sincronizzato il tag "Documentation: <titolo>" senza
  intervento manuale, tramite un listener di evento (non un hook `booted()`, correzione esplicita rispetto al v1).
- Ogni PDF (documentazione, report attività) è generato **in coda**, mai in modo sincrono in una request HTTP, e
  riflette fedelmente la carta intestata Montagna Servizi.
- La sincronizzazione dei ticket di un `ActivityReport` (`syncTickets()`) è un **service esplicito e idempotente**,
  non un Observer implicito come nel v1 — invocarlo due volte sullo stesso report non produce risultati diversi.
- Un cliente/organizzazione vede e scarica solo i **propri** report attività (`activity-report.view.own`), mai
  quelli di altri owner, nemmeno via richiesta manipolata.
- La rigenerazione batch dei PDF documentazione (`documentation:regenerate-pdfs`) e la generazione mensile dei
  report (`reports:generate-monthly`) seguono le regole comuni di §10.1 (feature flag, idempotenza, `--dry-run`,
  log strutturato, `ticket_logs`/audit dove pertinente).
- La fase si chiude con il pacchetto di collaudo completo (manifest + manuale narrativo + PDF) prodotto **da
  subito**, non retrofittato come per Fase 2/3 (obbligo esplicito in `CLAUDE.md` per ogni fase da qui in poi).

## 3. User Stories

### US-401: Modello `Tag` con SAL — implementazione unica
**Description:** Come manager, ho bisogno che ogni commessa mostri ore lavorate e stato di avanzamento calcolati
in modo corretto e coerente, per poter valutare a colpo d'occhio se una commessa è in linea con la stima (§6.3).

**Acceptance Criteria:**
- [ ] Model `App\Domain\Tagging\Models\Tag` con `workedMinutes(): int` (somma di `tickets.worked_minutes` dei
      ticket collegati via `ticket_tag`) e `sal(): ?float` (`worked_hours / estimated_hours * 100`), **una sola**
      implementazione — nel v1 esistevano un accessor e un metodo duplicato con la stessa formula, qui va rimossa
      l'ambiguità.
- [ ] `sal()` ritorna `null` quando `estimated_hours` è nullo o zero (non un errore di divisione, non zero
      fuorviante).
- [ ] `isClosed(): bool` — nessun ticket collegato in uno stato diverso da `released`/`done`; un tag senza ticket
      collegati non è considerato chiuso (nessun lavoro da chiudere ≠ commessa completata).
- [ ] Policy `TagPolicy` (`tag.view`/`.create`/`.update`/`.delete`, §9.4) — deny by default, coerente con le
      Policy già esistenti da Fase 0.
- [ ] Test unitari su `sal()`: stima nulla, nessun ticket collegato, arrotondamenti (requisito esplicito §13.1).
- [ ] Typecheck/lint/test passano.

### US-402: Azione "crea tag da ticket" + relazione ticket ↔ tag
**Description:** Come developer, voglio poter trasformare un ticket in una commessa con un'azione, per non dover
compilare a mano nome e stima quando la commessa nasce da un ticket già esistente (§6.3).

**Acceptance Criteria:**
- [ ] Azione Filament "Crea commessa" su `TicketResource` (view/edit) che crea un `Tag` con `estimated_hours`
      precompilato dal `tickets.estimated_hours` del ticket sorgente e collega il ticket al nuovo tag.
- [ ] `slug` generato e reso univoco (suffisso numerico sui duplicati), stesso pattern già stabilito in Fase 2/0
      per entità con `slug` unique.
- [ ] Un ticket può avere più tag e un tag più ticket (`ticket_tag`, N-N già esistente da Fase 0/2); l'azione non
      duplica il collegamento se il ticket è già taggato con lo stesso tag.
- [ ] Test feature: creazione tag da ticket, campi precompilati correttamente, collegamento N-N verificato.
- [ ] Typecheck/lint/test passano.

### US-403: Vista elenco tag e filtri ticket per commessa
**Description:** Come manager, voglio un elenco delle commesse con stima, ore lavorate e SAL, e la possibilità di
filtrare i ticket per commessa, per individuare rapidamente commesse fuori stima o ticket non ancora taggati
(§6.3).

**Acceptance Criteria:**
- [ ] `TagResource` Filament: elenco con nome, ore stimate, ore lavorate, **barra di avanzamento SAL**, numero di
      ticket aperti/chiusi, badge stato chiuso/aperto (`isClosed()`).
- [ ] Filtri su `TicketResource`: ticket senza tag (`whereDoesntHave('tags')`), ticket con più di un tag, tag per
      trimestre (pattern sul nome del tag, coerente con la convenzione di naming già in uso nei dati importati).
- [ ] Nessuna azione di CRUD oltre a quelle già coperte dalla Policy (`tag.create`/`.update`/`.delete`, §9.4:
      `manager` non ha `tag.delete`).
- [ ] Test feature: filtri producono il sottoinsieme corretto di ticket; policy testata riga per riga per ruolo.
- [ ] Typecheck/lint/test passano.

### US-404: Modello `DocumentationPage` — visibilità e Policy
**Description:** Come editor di documentazione, ho bisogno che ogni pagina abbia una categoria che ne determina la
visibilità (solo staff o anche clienti), per poter pubblicare contenuti interni e contenuti cliente nello stesso
sistema senza esporre l'uno all'altro (§6.4.1-6.4.2).

**Acceptance Criteria:**
- [ ] Model `App\Domain\Documentation\Models\DocumentationPage` (`title`, `slug`, `body` rich text obbligatorio,
      `category` enum `DocumentationCategory` = `internal`|`customer`), media collection `documents` e `images`
      (medialibrary, stesso pattern degli allegati ticket).
- [ ] `body` sanificato allo stesso modo di `ticket_messages.body_html` (riuso di `TicketMessageSanitizer` o
      estrazione di un sanitizer condiviso se la logica diverge da quella dei messaggi ticket).
- [ ] Policy `DocumentationPagePolicy`: `documentation.view.customer` (staff + cliente + fundraising, §9.4),
      `documentation.view.internal` (staff + fundraising, mai cliente), `.create`/`.update`/`.delete` come da
      matrice — **nessuna** relazione `creator()` verso una colonna inesistente (bug v1 esplicitamente da non
      riprodurre, §5.2); se in futuro servirà tracciare l'autore, sarà una story dedicata con una migrazione, non
      un'aggiunta implicita qui.
- [ ] Una pagina `internal` non è raggiungibile da un utente `customer` nemmeno via URL diretto (test su policy +
      su query scope).
- [ ] Typecheck/lint/test passano.

### US-405: Auto-tag e ricerca full-text
**Description:** Come editor, mi aspetto che ogni pagina di documentazione sia automaticamente collegabile come
commessa dal sistema, e che i clienti/staff possano trovare rapidamente una pagina cercando nel titolo o nel corpo
(§6.4.2).

**Acceptance Criteria:**
- [ ] Listener su evento `DocumentationPageCreated`: crea un `Tag` denominato `"Documentation: <titolo>"` collegato
      alla pagina (`tags.documentation_id`); **non** un hook `booted()`/`creating()` sul model (correzione
      esplicita rispetto al v1, §6.4.2).
- [ ] Listener su evento `DocumentationPageRenamed` (dispatchato quando `title` cambia): rinomina il tag collegato
      mantenendo lo stesso `Tag`, non creandone uno nuovo.
- [ ] Ricerca full-text su `title` e `body` (stesso meccanismo — indice full-text Postgres o equivalente — già in
      uso per `tickets.title`, §5.2), esposta come filtro/ricerca in `DocumentationPageResource`.
- [ ] Test feature: creazione pagina → tag creato con nome atteso e collegato; rinomina → tag rinominato, nessun
      tag duplicato; ricerca full-text trova pagine per corrispondenza su titolo e su corpo.
- [ ] Typecheck/lint/test passano.

### US-406: Generazione PDF documentazione (spatie/laravel-pdf) + rigenerazione batch
**Description:** Come cliente o membro dello staff, voglio poter scaricare una pagina di documentazione come PDF
con la stessa identità visiva del sito, per condividerla o archiviarla, e come admin voglio poter rigenerare tutti
i PDF in blocco dopo un cambio di logo o layout (§6.4.3).

**Acceptance Criteria:**
- [ ] `spatie/laravel-pdf` aggiunto come nuova dipendenza Composer; verificato che Chromium headless è disponibile
      **sia** nell'immagine di sviluppo **sia** nell'immagine UAT/produzione (`docker/uat/Dockerfile`,
      FrankenPHP) — a differenza di pdflatex, qui è un requisito di runtime, non solo di dev.
- [ ] Job in coda che genera il PDF da una vista Blade che riusa gli stessi componenti/stili del design system
      (`docs/design-system.md`/`resources/css/theme.css`, stessa fonte di verità già stabilita per il layout email
      in Fase 3), con header logo da `PDF_LOGO_PATH` e footer dati societari da `PDF_FOOTER` (nuove variabili
      `.env.example`, documentate con commento come da convenzione del file).
- [ ] Rigenerazione automatica in coda alla creazione della pagina e a ogni modifica di `title` o `body`.
- [ ] File salvato su **storage privato**, colonne `pdf_path` + `pdf_generated_at` valorizzate; download solo
      tramite route autorizzata dalla Policy (stesso pattern di `ticket_messages` allegati, §6.1.8).
- [ ] Comando `documentation:regenerate-pdfs` (manuale, §10.2): rigenera tutti i PDF, rispetta le regole comuni di
      §10.1 (`--dry-run`, log strutturato, idempotente — non fallisce l'intero batch per un errore su una singola
      pagina).
- [ ] Scelta del renderer motivata in `README.md` (richiesto esplicitamente da §6.4.3).
- [ ] Test: generazione PDF (contenuto/non-vuoto, non il rendering pixel-per-pixel), rigenerazione su modifica di
      titolo/corpo, comando batch con `--dry-run` che non scrive nulla.
- [ ] Typecheck/lint/test passano.

### US-407: Modello `Organization` e gestione membri
**Description:** Come admin, voglio raggruppare più utenti cliente sotto un'unica organizzazione, per poter
rendicontare l'attività a livello di ente invece che di singolo utente (§6.5.4).

**Acceptance Criteria:**
- [ ] Model `App\Domain\Identity\Models\Organization` (`name`, `locale` default `it`) con relazione N-N verso
      `users` via `organization_user` (già esistente da Fase 0/2).
- [ ] Policy `OrganizationPolicy` (`organization.view`/`.create`/`.update`/`.delete`, §9.4: `manager` vede ma non
      modifica, solo `admin` fa CRUD completo).
- [ ] `OrganizationResource` Filament: gestione anagrafica + gestione membri (aggiungi/rimuovi utenti) + vista dei
      report attività generati per quell'ente (collegamento a US-408/US-410).
- [ ] Test feature: policy per ruolo riga per riga; aggiunta/rimozione membro riflessa correttamente nella
      relazione N-N.
- [ ] Typecheck/lint/test passano.

### US-408: Modello `ActivityReport` + servizio di sincronizzazione ticket
**Description:** Come sistema, devo determinare quali ticket appartengono a un report attività di un periodo dato,
in modo ripetibile e senza effetti collaterali se rieseguito, per garantire che la rendicontazione non diverga tra
generazioni successive (§6.5.2).

**Acceptance Criteria:**
- [ ] Model `App\Domain\Reporting\Models\ActivityReport` (`owner_kind` `user`|`organization`, FK owner coerenti
      col CHECK già esistente da Fase 0, `period_type` `monthly`|`annual`, `year`, `month` nullable coerente col
      tipo periodo, `locale`), con accessor derivati: data inizio/fine periodo, nome owner, etichetta periodo
      (`"2026"`, `"Febbraio 2026"` — localizzata).
- [ ] Vincolo di unicità applicativo replicato sopra il vincolo DB già esistente (`owner_kind` + owner + tipo +
      periodo): un tentativo di creare un duplicato fallisce con un errore leggibile, non con l'eccezione SQL
      grezza.
- [ ] `App\Domain\Reporting\Services\ActivityReportSyncService::syncTickets(ActivityReport $report): void` —
      **service esplicito**, non un Observer su `created`/`updated` come nel v1: seleziona i ticket con `done_at`
      nel periodo del report (owner utente: `requester_id` = owner; owner organizzazione: richiedente appartenente
      all'organizzazione), poi `sync()` sul risultato; se l'owner risulta assente/non risolvibile, `detach()`
      totale. Invocato esplicitamente dall'action di creazione/aggiornamento e dal comando di generazione (US-410),
      **mai** da un hook implicito sul model.
- [ ] Idempotenza verificata: invocare `syncTickets()` due volte di seguito sullo stesso report produce lo stesso
      insieme di ticket collegati, senza duplicati né effetti collaterali aggiuntivi.
- [ ] ⚠️ Dipendenza esplicita: la selezione usa `tickets.done_at`. Sul dump v1 molti ticket storici hanno `done_at`
      nullo — verificare che `tickets:backfill-dates` (comando già previsto da §11.5/§10.2, stage `derive` di
      Fase 2) sia stato eseguito sui dati reali **prima** di generare report su dati storici, altrimenti ticket
      validi spariscono dalla rendicontazione. Non è un compito di questa story ma un prerequisito da verificare al
      checkpoint (US-411).
- [ ] Test feature: selezione per periodo e owner (utente e organizzazione), idempotenza, unicità (requisito
      esplicito §13.1 "Sync `ActivityReport`").
- [ ] Typecheck/lint/test passano.

### US-409: Generazione PDF report attività
**Description:** Come cliente o organizzazione, voglio ricevere/scaricare un PDF che riepiloga l'attività svolta
nel periodo, con lo stesso taglio grafico del resto della piattaforma, per avere un documento condivisibile con il
mio ente (§6.5.3).

**Acceptance Criteria:**
- [ ] Job in coda (stesso renderer `spatie/laravel-pdf` di US-406, stessi componenti di design system) che genera
      il PDF nella lingua dell'owner (`report.locale`, già derivata in US-408 da `users.locale`/
      `organizations.locale`), memorizzata sul record.
- [ ] Nome file basato su `PLATFORM_ACRONYM`, owner e periodo — variabile nuova, non presente in nessuna fase
      precedente (verificato), da introdurre in `.env.example` con lo stesso livello di dettaglio delle altre.
- [ ] Contenuto: intestazione con periodo e owner, elenco ticket (titolo, tipo, date, ore), totali di periodo.
- [ ] Cancellazione del record `ActivityReport` rimuove anche il file PDF associato (nessun file orfano su
      storage).
- [ ] Download autorizzato da `activity-report.generate-pdf`/policy coerente con §9.4 (cliente/organizzazione
      scaricano solo i **propri** report — `activity-report.view.own`; mai quelli di altri owner, nemmeno via
      richiesta manipolata sull'URL/ID).
- [ ] Test: generazione PDF con contenuto atteso (ticket e totali corretti per un caso noto), rimozione file alla
      cancellazione, autorizzazione testata per ruolo/owner.
- [ ] Typecheck/lint/test passano.

### US-410: Comando `reports:generate-monthly` e accesso ai propri report
**Description:** Come admin, voglio che i report del mese precedente vengano generati automaticamente per tutti
gli owner attivi, e come cliente voglio vedere l'elenco dei miei report senza dover chiedere allo staff (§6.5.3,
§10.2).

**Acceptance Criteria:**
- [ ] Comando `reports:generate-monthly`, schedulato il primo del mese alle 12:00 per il mese precedente, dietro
      feature flag `ENABLE_REPORTS_MONTHLY` (default `false`, §10.2), conforme alle regole comuni di §10.1
      (`--dry-run`, idempotente, log strutturato, `withoutOverlapping()`).
- [ ] Per ogni owner attivo (utente cliente con almeno un ticket nel periodo, organizzazione con almeno un
      membro/ticket collegato nel periodo) crea (se non esiste già per quel periodo, US-408) l'`ActivityReport`,
      invoca `syncTickets()` e accoda la generazione PDF (US-409).
- [ ] Vista Filament per `customer`/membri organizzazione: elenco dei propri report (`activity-report.view.own`)
      con download PDF; **non** una dashboard cliente completa (fuori scope, Fase 6) — solo l'elenco/download.
- [ ] Test: comando in `--dry-run` non scrive nulla; esecuzione reale non duplica report già esistenti per lo
      stesso periodo (idempotenza su ri-esecuzione); vista cliente mostra solo i propri report.
- [ ] Typecheck/lint/test passano.

### US-411: Checkpoint di fine Fase 4 — verifica end-to-end e pacchetto di collaudo
**Description:** Come team, prima di considerare chiusa la fase dobbiamo verificare l'intero flusso su dati reali
importati dal v1 e produrre il pacchetto di collaudo completo, **da subito** e non come retrofit successivo
(obbligo esplicito in `CLAUDE.md`, "Processo di collaudo", valido per ogni fase da Fase 4 in poi).

**Acceptance Criteria:**
- [ ] Verifica end-to-end su dati reali (post `v1:import` + `tickets:backfill-dates`): SAL calcolato su commesse
      reali, PDF documentazione generato e scaricabile con carta intestata corretta, almeno un `ActivityReport`
      generato per un owner reale con ticket e totali verificati a campione.
- [ ] `docs/collaudo/fase-4.php` — manifest topic → test numerati (`F4-01`, `F4-02`, ...) → riferimento a un test
      automatico realmente esistente; `php artisan collaudo:verify-manifest 4` passa.
- [ ] Manuale narrativo `docs/collaudo/0N-fase-4.md` (stesso formato per-test già in uso: Obiettivo/Riferimenti/
      Modalità di esecuzione/Priorità/Ruolo del tester/Prerequisiti/Procedura di esecuzione/Criterio di
      superamento/Campi di consuntivazione), scritto **per ultimo**, dopo che tutte le story US-401→US-410 sono
      complete e il manifest passa — mai in parallelo alle story.
- [ ] Aggiornamento del pacchetto cumulativo: `README.md` (indice + riepilogo numerico), `00-istruzioni-generali.md`
      (§1 versione, ambito incluso/escluso), `01-matrice-tracciabilita.md` (una riga per test), `07-registro-esiti.md`
      (una riga ID+Titolo per test, generabile dal manifest).
- [ ] `php artisan collaudo:generate 4` produce il PDF di collaudo (carta intestata Montagna Servizi) senza errori;
      `storage/app/collaudo/` contiene solo l'ultima versione generata per questa fase.
- [ ] Report dei compromessi/gap emersi (se presenti) rivisto con il committente, stesso pattern di checkpoint già
      usato in Fase 2 (US-219) e Fase 3 (US-326).

## 4. Requisiti funzionali

- FR-1: Il SAL di un tag/commessa (`sal()`) ha un'unica implementazione, corretta su stima nulla/assente.
- FR-2: Creare o rinominare una pagina di documentazione mantiene sincronizzato, senza intervento manuale, il tag
  "Documentation: <titolo>" collegato.
- FR-3: Ogni PDF (documentazione, report attività) è generato **in coda**, mai sincronamente in una request HTTP,
  usando `spatie/laravel-pdf` con gli stessi stili del design system.
- FR-4: Il PDF di una pagina di documentazione si rigenera automaticamente a ogni modifica di titolo o corpo, ed è
  rigenerabile in batch via `documentation:regenerate-pdfs`.
- FR-5: `ActivityReportSyncService::syncTickets()` è un service esplicito e idempotente, invocato solo da action e
  comando — mai da un Observer implicito sul model.
- FR-6: Un `ActivityReport` è univoco per `(owner_kind, owner, period_type, year, month)`; il vincolo applicativo
  produce un errore leggibile prima di arrivare al CHECK del DB.
- FR-7: `reports:generate-monthly` genera i report del mese precedente per tutti gli owner attivi, dietro feature
  flag, in modo idempotente e con `--dry-run`.
- FR-8: Un utente `customer` (o membro di un'organizzazione) vede e scarica solo i **propri** report attività, mai
  quelli di altri owner, anche via richiesta manipolata; una pagina di documentazione `internal` non è mai
  raggiungibile da un utente `customer`.
- FR-9: Ogni comando schedulato/manuale di questa fase (`documentation:regenerate-pdfs`, `reports:generate-monthly`)
  rispetta le regole comuni di §10.1 (feature flag, idempotenza, `--dry-run`, log strutturato).
- FR-10: La fase produce il pacchetto di collaudo completo (manifest, manuale narrativo, PDF) come **ultimo** passo,
  dopo che tutte le altre story sono complete.

## 5. Non-Goals (fuori scope)

- **Dashboard/portale cliente**: viste ristrette, ricerca globale, badge di navigazione — assegnati alla Fase 6
  (§14). Questa fase espone solo l'elenco/download dei propri report attività al cliente/organizzazione, non un
  portale.
- **Fundraising** (opportunità, griglia di valutazione, progetti): Fase 5, non toccato qui.
- **`documentation_pages.created_by`**: nessun requisito di questa fase lo richiede; l'ETL non potrebbe comunque
  popolarlo sui dati storici (§5.2). Non aggiunto in questa fase.
- **Automazioni schedulate complete** oltre a `reports:generate-monthly`: il resto di §10.2 (es.
  `tickets:notify-idle-developers`, `mail:send-digest`) resta in Fase 6 o è già coperto da fasi precedenti.
- **Messaggi ticket `internal`** e altri punti di estensione di §15.2: non toccati da questa fase.

## 6. Design Considerations

- Layout PDF (documentazione e report attività) deve derivare da `docs/design-system.md`/
  `resources/css/theme.css`, stessa fonte di verità già stabilita per il pannello (Fase 0) e per il layout email
  (Fase 3) — niente hex o stili duplicati.
- `TagResource`, `DocumentationPageResource`, `OrganizationResource` seguono lo stesso pattern Filament già
  consolidato nel repo (Resource con Policy risolta per convenzione, infolist per contenuti in sola lettura dove
  pertinente, come già fatto per `EmailMessageResource` in Fase 3).
- La barra di avanzamento SAL nell'elenco tag è un componente riusabile (stesso principio dei componenti Blade
  riusabili già introdotti per le email in Fase 3).

## 7. Technical Considerations

- **Nessuna nuova migrazione di schema prevista** per `tags`, `documentation_pages`, `organizations`,
  `activity_reports`: esistono già da Fase 0/2. `PDF_LOGO_PATH`, `PDF_FOOTER` e `PLATFORM_ACRONYM` **non esistono
  ancora** in `.env.example` (verificato): sono tre variabili nuove di questa fase, da documentare con lo stesso
  livello di dettaglio (commento sopra ogni riga) già usato per le altre.
- `spatie/laravel-pdf` è una nuova dipendenza Composer; verificare che Chromium headless sia installabile e
  funzionante **sia** nell'immagine dev sia nell'immagine UAT/produzione (FrankenPHP) — a differenza di pdflatex
  (solo dev), qui è un requisito di runtime.
- Riuso esplicito: `TicketMessageSanitizer` (o estrazione di un sanitizer condiviso) per `documentation_pages.body`,
  pattern slug-univoco già stabilito in Fase 0/2 (`GeneratesProvisionalSlugs` o equivalente) per `tags`/
  `documentation_pages`, `tickets:backfill-dates` (Fase 2) come prerequisito dati per `ActivityReport.syncTickets()`.
- Nessuna chiamata `env()` fuori da `config/*.php` (§13.3), stesso vincolo già rispettato nelle fasi precedenti.

**Addendum post-implementazione (merge PR #9, prima del deploy UAT)**: la verifica "Chromium funzionante sia in
dev sia in UAT" del punto sopra si è rivelata insufficiente in pratica — i test locali/CI passavano tutti prima
del merge, ma 3 gotcha ambientali distinti sono emersi solo al primo giro reale di CI e al primo deploy UAT
(dettagli completi in `CLAUDE.md`, sezione "Chrome/ext-sockets"):
1. l'auto-discovery di `chrome-php/chrome` non trova il binario installato da `browser-actions/setup-chrome` sul
   runner CI (va passato esplicitamente `LARAVEL_PDF_CHROME_BINARY`);
2. `ext-sockets` (richiesta a runtime da `chrome-php/wrench`) mancava sia nell'immagine dev sia in quella UAT —
   mai intercettato in CI perché il runner `ubuntu-latest` la preinstalla di default;
3. compilare `ext-sockets` su Alpine richiede `linux-headers` nei build-deps, altrimenti fallisce su
   `linux/sock_diag.h` mancante.

Per qualunque futura fase che aggiunga una dipendenza di sistema (estensione PHP, binario, driver): verificarla
esplicitamente contro CI e contro l'immagine di produzione reale, non assumerla allineata solo perché i test
passano in dev.

## 8. Success Metrics

- SAL calcolato su tutte le commesse reali importate coincide, a campione, con un calcolo manuale sugli stessi
  dati (verificato al checkpoint, US-411).
- 100% dei PDF (documentazione + report attività) generati in coda, zero generazioni sincrone rilevate nei test.
- Zero tag "Documentation: ..." duplicati o disallineati dal titolo dopo un ciclo di rinomina in collaudo.
- `php artisan collaudo:verify-manifest 4` passa al checkpoint, nessun test referenziato nel manifest inesistente.

## 9. Open Questions

- Formato esatto del pattern "tag per trimestre" sul nome (US-403): dedotto dalla convenzione già presente nei
  dati importati, da confermare/affinare quando si osservano i tag reali durante l'implementazione, non bloccante
  per iniziare.
- Definizione operativa di "owner attivo" per `reports:generate-monthly` (US-410): proposta "almeno un ticket con
  `done_at` nel periodo", da confermare col committente durante il checkpoint — non bloccante per iniziare
  l'implementazione.
