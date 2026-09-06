# PRD: Fase 6 — Portale cliente e rifinitura

> Riferimento completo: `PRD-ORCHESTRATOR-V2.md` §6.7 (M7 — Utenti, ruoli e portale cliente), §7.5.2 (E8/E10/E11),
> §8.4/§8.6/§8.7 (navigazione, vista di lavoro, requisiti UI trasversali), §10.2 (automazioni schedulate),
> §13.4 (documentazione) e §14 (Roadmap, Fase 6). Numerazione story `US-6xx` per restare nel range riservato a
> questa fase.

## 1. Introduzione/Overview

Questa è la fase di chiusura funzionale prima del cutover (Fase 7): porta a termine tutto ciò che le fasi
precedenti hanno deliberatamente lasciato indietro come "essenziale per ora, rifinito in Fase 6" — il portale
cliente vero e proprio, la vista di lavoro rifinita secondo il design, ricerca globale e badge di navigazione,
la schermata di gestione delle preferenze di notifica, autenticazione MFA/impersonation, disattivazione utenti,
le 6 automazioni schedulate ancora mancanti, le comunicazioni opzionali E8/E10/E11, e la documentazione completa
del progetto.

**Scoperta emersa scrivendo questo PRD, non nota prima**: `docs/design-inventory.md` (l'inventario del design
fatto in Fase 0) mostra che il mockup importato copre **solo** il portale cliente (dashboard, ticket, bandi,
progetti) — **zero schermate per la vista di lavoro dello staff**. §8.6 del PRD principale dice "va rifatta
secondo il design", ma quel design per la WorkBoard non esiste. Il committente ha confermato la direzione (vedi
Decisioni sotto): mantenere il paradigma Kanban a colonne per stato, rifinendone solo lo stile secondo il design
system già importato — non un cambio di paradigma.

**Decisioni prese col committente per questa fase**:
- **WorkBoard**: Kanban rifinito (stesso paradigma a colonne per stato dell'attuale versione essenziale di
  Fase 1, ma con la cura visiva del design system — non una vista a lista né a swimlane per assegnatario).
- **E8/E10/E11**: tutte e tre approvate, da implementare in questa fase.
- **`help_desk_chat_url`**: **non** confermata — non viene aggiunta la colonna né il link nel portale cliente
  (evita di violare A9, colonna inutilizzata, coerente con `§5.2` del PRD principale).

**Verificato nel codice prima di scrivere le story** (per non assumere scope già coperto): nessuna
implementazione esiste ancora per ricerca globale, badge di navigazione, impersonation, MFA, azione di
disattivazione utente, dashboard cliente dedicata, e i comandi `tickets:progress-to-todo`,
`tickets:auto-close-released`, `tickets:close-scrum`, `tickets:restore-waiting`, `tickets:archive-scrum`,
`timetracking:aggregate-daily` (nessuno dei 6 esiste, non solo "da schedulare"). La gestione permessi
effettivi con provenienza (§6.7.1) e `notification_preferences` (tabella) esistono già da Fase 0.

## 2. Goals

- Un cliente che accede vede una dashboard con i propri ticket, i propri report, i propri progetti fundraising
  coinvolti e la documentazione `customer` — mai i dati di altri clienti (criterio di accettazione esplicito
  della roadmap, §14).
- La vista di lavoro (WorkBoard) rifinita secondo il design system carica in tempi accettabili sull'intero
  dataset importato (query aggregate, non N+1) — stesso requisito di performance già esplicito in §8.6.
- Un utente può gestire le proprie preferenze di notifica da una schermata dedicata, non solo vederle rispettate
  passivamente (già garantito dalla Fase 3).
- Le 6 automazioni ticketing/time-tracking ancora mancanti esistono, sono schedulate dietro feature flag
  (default `false`), e ogni comando ha `--dry-run` e log strutturato (criterio di accettazione esplicito della
  roadmap, §14).
- E8/E10/E11 rispettano lo stesso layout unico, la stessa localizzazione e le stesse regole anti-loop/
  soppressioni già stabilite in Fase 3 per E1-E7/E9 — nessuna logica di invio duplicata o divergente.
- Un admin può disattivare/riattivare un utente da UI, con effetti coerenti su assegnabilità e destinatari delle
  comunicazioni (§6.7.5).
- La documentazione del progetto (§13.4) è completa: tutti gli 11 file elencati esistono con contenuto reale.

## 3. User Stories

### US-601: Dashboard cliente
**Description:** As a cliente, voglio una dashboard con una panoramica della mia attività (ticket, report,
progetti, documentazione), per orientarmi subito senza dover cercare tra i menu (§6.7.3).

**Acceptance Criteria:**
- [ ] Nuova Filament Page `CustomerDashboard` (sostituisce la Dashboard di base per il ruolo `customer`, stesso
      pattern già usato per il redirect di `admin`/`manager`/`developer` verso la WorkBoard in
      `App\Filament\Pages\Dashboard::mount()`, Fase 1).
- [ ] Contenuto: card ticket aperti (conteggio + link), card ticket che richiedono una risposta (stato
      `waiting`/`problem` di competenza del cliente), documentazione `customer` recente, link `drive_url`/
      `drive_budget_url` (se valorizzati), propri report attività (link a US già esistente da Fase 4), progetti
      fundraising in cui è coinvolto (link a `CustomerFundraisingProjectResource`, Fase 5). **Nessun** link chat
      di supporto (`help_desk_chat_url` non confermato, vedi Decisioni).
- [ ] Ogni card mostra dati **solo** dell'utente autenticato (query scope esplicito, mai un elenco generico).
- [ ] Test feature: ogni card mostra i conteggi/link corretti per un cliente con dati reali; un cliente senza
      dati in una sezione vede uno stato vuoto scritto, non un errore o una sezione vuota silenziosa (§8.7).
- [ ] Typecheck passa.
- [ ] Verifica in browser (screenshot Chrome headless) della dashboard con un cliente reale che ha ticket,
      report e almeno un progetto fundraising coinvolto.

### US-602: Navigazione "Area cliente" e landing per ruolo
**Description:** As a cliente, voglio un menu che raggruppi solo le voci che mi riguardano, e as a membro del
team fundraising voglio atterrare direttamente sull'elenco opportunità al login, per non dover navigare a mano
ogni volta (§8.4, §6.7.2).

**Acceptance Criteria:**
- [ ] Gruppo di navigazione "Area cliente" (§8.4): Dashboard (US-601), I miei ticket, Nuovo ticket, Archivio,
      I miei report, Documentazione, Fundraising — visibile **solo** al ruolo `customer`, nessuna voce dei
      gruppi staff (Lavoro, Ticket, Commesse, Amministrazione, ecc.) visibile a un cliente.
- [ ] Landing per ruolo aggiornata in `App\Filament\Pages\Dashboard::mount()` (Fase 1): `customer` → redirect a
      `CustomerDashboard` (US-601), `fundraising` → redirect a `FundraisingOpportunityResource` (elenco), invariato
      per `admin`/`manager`/`developer` (→ WorkBoard).
- [ ] Test feature: login come cliente mostra solo il gruppo Area cliente in navigazione e reindirizza alla
      dashboard cliente; login come `fundraising` reindirizza all'elenco opportunità.
- [ ] Typecheck passa.
- [ ] Verifica in browser (screenshot Chrome headless) della navigazione per un utente `customer` e per un
      utente `fundraising`.

### US-603: Ricerca globale
**Description:** As a membro dello staff, voglio cercare un ticket per id, titolo, richiedente o contenuto di un
messaggio da un unico campo di ricerca, per non dover aprire l'elenco ticket e applicare filtri ogni volta
(§8.7).

**Acceptance Criteria:**
- [ ] Global Search Filament nativa su `TicketResource`: risultati per `id`, `title`, nome/email del richiedente,
      corpo dei messaggi (`ticket_messages.body_text`) — keyword-based, **non** conversazionale/AI (da non
      confondere con la Ricerca CAI/RAG del brief commerciale, fuori scope, già segnalata in
      `docs/design-inventory.md`).
- [ ] Risultati scoped secondo la Policy dell'utente corrente (un cliente cerca solo tra i propri ticket, mai
      tra quelli di altri).
- [ ] Query indicizzata (riuso dell'indice full-text già esistente su `tickets.title` da Fase 0, nuovo indice se
      necessario su `ticket_messages.body_text` per performance).
- [ ] Test feature: ricerca per id/titolo/richiedente/corpo messaggio trova il ticket atteso; un cliente non
      trova ticket di altri nei risultati.
- [ ] Typecheck passa.
- [ ] Verifica in browser (screenshot Chrome headless) della ricerca globale con un termine che matcha solo nel
      corpo di un messaggio.

### US-604: Badge di navigazione con cache
**Description:** As a membro dello staff, voglio vedere a colpo d'occhio nel menu quanti ticket sono in attesa,
in problema o da testare, per capire dove intervenire senza aprire ogni vista (§8.4).

**Acceptance Criteria:**
- [ ] `getNavigationBadge()` sulle voci di menu pertinenti (In attesa, Problemi, Da testare — almeno queste tre,
      §8.4 le cita esplicitamente), scoped per l'utente corrente dove rilevante (es. "Da testare" solo i ticket
      con `tester_id = auth()`).
- [ ] **Con cache** (requisito esplicito §8.4: "non una query per voce a ogni render") — TTL breve configurabile,
      invalidata o naturalmente scaduta, mai una query sincrona a ogni caricamento di pagina per ogni voce di
      menu.
- [ ] Test feature: il badge mostra il conteggio corretto; una seconda richiesta entro il TTL non genera una
      nuova query (verificabile con un contatore di query in test).
- [ ] Typecheck passa.
- [ ] Verifica in browser (screenshot Chrome headless) dei badge visibili nel menu con dati reali.

### US-605: Schermata preferenze di notifica
**Description:** As a utente, voglio poter scegliere quali comunicazioni ricevere, per tipo e canale, da una
schermata dedicata, invece di dover chiedere allo staff di disattivarle per me (§6.7.4).

**Acceptance Criteria:**
- [ ] Pagina Filament (Page personale, non una Resource — accessibile da ogni ruolo autenticato) su
      `notification_preferences` (tabella già esistente da Fase 0): un utente vede e modifica **solo** le
      proprie preferenze.
- [ ] Le preferenze coprono almeno i tipi di comunicazione del catalogo E1-E11 applicabili al ruolo dell'utente
      (un cliente non vede il toggle per E6 "Assegnazione", che non lo riguarda mai).
- [ ] Il footer email (già presente da Fase 3, link "preferenze di notifica" condizionale a
      `mail_pipeline.notification_preferences_url`) punta ora a questa pagina reale — valorizzare quella
      config, non lasciarla vuota.
- [ ] Test feature: modificare una preferenza e verificare che una comunicazione di quel tipo non venga più
      inviata (riuso dei test di rispetto preferenze già esistenti da Fase 3, estesi a coprire l'aggiornamento
      via questa UI).
- [ ] Typecheck passa.
- [ ] Verifica in browser (screenshot Chrome headless) della pagina preferenze con un utente reale.

### US-606: Autenticazione MFA opzionale
**Description:** As an admin, voglio poter richiedere l'autenticazione a due fattori per ruolo, per alzare la
sicurezza degli account con più privilegi senza imporla a tutti (§6.7.2).

**Acceptance Criteria:**
- [ ] MFA nativa Filament 4 abilitata, **opzionale** e **abilitabile per ruolo** (es. obbligatoria per `admin`,
      opzionale per gli altri — la granularità esatta è una scelta di configurazione, non hardcoded per ruolo
      nel codice applicativo).
- [ ] Flusso di setup/recovery funzionante (QR/codici di recupero), documentato in `docs/operations.md`
      (US-617).
- [ ] Test feature: un ruolo per cui MFA è richiesta non può completare il login senza; un ruolo per cui è
      opzionale può accedere senza averla configurata.
- [ ] Typecheck passa.
- [ ] Verifica in browser (screenshot Chrome headless) del flusso di setup MFA e di un login con MFA attiva.

### US-607: Impersonation
**Description:** As an admin, voglio poter vedere il pannello con gli occhi di un altro utente per riprodurre un
problema che mi ha segnalato, con la certezza che l'azione resti tracciata (§6.7.2).

**Acceptance Criteria:**
- [ ] Pacchetto di impersonation consolidato (es. `stechstudio/filament-impersonate` o equivalente maturo per
      Filament 4) integrato, usando i metodi `canImpersonate()`/`canBeImpersonated()` **già esistenti** sul
      model `User` (Fase 0, non duplicarli).
- [ ] Azione "Impersona" disponibile solo dove `Permission::UserImpersonate` lo consente (§9.4: solo `admin`).
- [ ] **Banner sempre visibile** mentre l'impersonation è attiva, con azione esplicita per uscire.
- [ ] Ogni impersonation genera un log dell'azione (chi ha impersonato chi, quando — riuso del pattern
      `ticket_logs`/`email_message_logs` con attore di sistema se serve una nuova tabella dedicata, oppure log
      applicativo strutturato se basta).
- [ ] Test feature: un admin può impersonare, il banner compare, l'azione è loggata, uscire ripristina la
      sessione originale; un utente senza il permesso non vede l'azione.
- [ ] Typecheck passa.
- [ ] Verifica in browser (screenshot Chrome headless) di un ciclo completo impersona → banner visibile → esci.

### US-608: Disattivazione e riattivazione utente
**Description:** As an admin, voglio poter disattivare un utente che non deve più accedere, senza cancellarlo e
senza perdere lo storico in cui compare, per gestire il turnover di staff e clienti (§6.7.5).

**Acceptance Criteria:**
- [ ] Azione Filament "Disattiva"/"Riattiva" su `UserResource`, dietro `Permission::UserDeactivate`, che
      valorizza/azzera `deactivated_at` (colonna già esistente da Fase 0).
- [ ] Un utente disattivato: non può accedere (guard di login), non compare come opzione selezionabile in
      assegnatario/tester/partner fundraising/destinatari di comunicazioni **da questo punto in poi** (verificare
      se gli scope/query builder rilevanti già escludono `deactivated_at` non nullo — Fase 0 ha lo scope
      `whereNull('deactivated_at')` sul model, verificare che ogni select/picker pertinente lo usi davvero, non
      solo che esista) — resta invece visibile nei dati storici (ticket_logs, messaggi, membership organizzazioni)
      senza alcuna modifica.
- [ ] Un utente disattivato non riceve più comunicazioni (integrazione con le regole di destinazione già
      esistenti da Fase 3, non una nuova soppressione: un controllo aggiuntivo su `deactivated_at` prima
      dell'invio).
- [ ] Test feature: disattivare un utente lo rimuove dai picker di assegnazione/partner ma non dallo storico;
      un utente disattivato non riceve email; il login è bloccato.
- [ ] Typecheck passa.
- [ ] Verifica in browser (screenshot Chrome headless) di disattivazione/riattivazione su un utente reale.

### US-609: Rifinitura della vista di lavoro (WorkBoard) secondo il design system
**Description:** As a membro dello staff, voglio che la vista di lavoro rifletta la cura visiva del resto del
pannello, restando comunque veloce sull'intero dataset importato, per lavorarci quotidianamente senza attrito
(§8.6). Decisione presa: stesso paradigma Kanban a colonne per stato della versione essenziale di Fase 1, solo
rifinito nello stile.

**Acceptance Criteria:**
- [ ] Ristilizzazione della WorkBoard esistente (Fase 1, US-113) secondo `docs/design-system.md`/
      `resources/css/theme.css` — stessa fonte di verità già usata per il resto del pannello e per le email
      (Fase 3): tipografia, palette, densità delle card coerenti col design system, **nessun** cambio di
      paradigma (resta colonne per stato).
- [ ] Card ticket: id, titolo, cliente, tag, priorità, tempo trascorso nello stato corrente
      (`status_changed_at`) — tutti già presenti nella versione essenziale, verificare che restino tutti
      visibili dopo la ristilizzazione.
- [ ] Selettore di assegnatario (vedere la board di un collega) — se non già presente nella versione essenziale,
      aggiungerlo qui.
- [ ] Performance verificata sull'intero dataset importato: query aggregate (conteggi/raggruppamenti in poche
      query), **non** N+1 per card — misurare (es. `assertQueryCountLessThan` o log query in un test) prima e
      dopo, non assumere che la ristilizzazione non abbia introdotto regressioni.
- [ ] Drag & drop **resta fuori scope** salvo la validazione lato server sia già invariata (nessun nuovo lavoro
      di validazione richiesto da questa story, coerente con la nota "opzionale" già in US-113 di Fase 1).
- [ ] Test feature: la board carica con i dati attesi per ogni colonna di stato; il selettore di assegnatario
      cambia la board mostrata.
- [ ] Typecheck passa.
- [ ] Verifica in browser (screenshot Chrome headless) della board rifinita, prima/dopo a confronto se utile.

### US-610: Comandi `tickets:progress-to-todo` e `tickets:auto-close-released`
**Description:** As a sistema, alle 18:00 devo riportare in `todo` i ticket rimasti `progress` a fine giornata, e
alle 7:45 chiudere automaticamente i ticket `released` da abbastanza giorni lavorativi, per mantenere la board
pulita senza intervento manuale (§10.2).

**Acceptance Criteria:**
- [ ] `tickets:progress-to-todo` (schedulato 18:00, flag `ENABLE_TICKETS_PROGRESS_TO_TODO`): tutti i ticket
      `progress` → `todo`, tramite la macchina a stati esistente (mai un update diretto sulla colonna `status`
      che bypassa `ChangeTicketStatus`).
- [ ] `tickets:auto-close-released` (schedulato 07:45, flag `ENABLE_TICKETS_AUTO_CLOSE_RELEASED`): ticket
      `released` da ≥ 3 giorni lavorativi (weekend esclusi, stesso calcolo giorni lavorativi già stabilito per
      il time tracking, Fase 1) → `done`, con `done_at` valorizzato.
- [ ] Entrambi conformi a §10.1: feature flag (default `false`), idempotenti, `--dry-run`, log strutturato,
      `ticket_logs` con `is_system = true` e `User::system()` come attore (pattern già stabilito in Fase 3,
      US-325).
- [ ] Test: ogni comando in `--dry-run` non scrive nulla; esecuzione reale transita solo i ticket nello stato
      atteso, rispettando la macchina a stati; idempotenza su ri-esecuzione.
- [ ] Typecheck passa.

### US-611: Comandi `tickets:close-scrum` e `tickets:archive-scrum`
**Description:** As a sistema, devo chiudere i ticket di tipo `scrum` creati/aggiornati in giornata e archiviare
periodicamente quelli scrum più vecchi, per non lasciare che il tipo di ticket "riunione" si accumuli
indefinitamente (§10.2).

**Acceptance Criteria:**
- [ ] `tickets:close-scrum` (schedulato 16:00, flag `ENABLE_TICKETS_CLOSE_SCRUM`): ticket `type = scrum`
      creati/aggiornati oggi → `done`.
- [ ] `tickets:archive-scrum` (schedulato 05:00, flag `ENABLE_TICKETS_ARCHIVE_SCRUM`): **verificare il
      comportamento v1 in dettaglio prima di riprodurlo** (nota esplicita del PRD principale, §10.2, "Q9") — se
      il comportamento v1 non è recuperabile con certezza dal codice/dump, implementare la lettura più
      conservativa (non cancellare mai dati, solo un cambio di stato/flag di archiviazione) e segnalarlo come
      compromesso al checkpoint (US-618), non indovinare.
- [ ] Entrambi conformi a §10.1 (feature flag, idempotenza, `--dry-run`, log strutturato, `ticket_logs`
      `is_system`).
- [ ] Test: `--dry-run` non scrive; idempotenza; `close-scrum` non tocca ticket non-scrum o non odierni.
- [ ] Typecheck passa.

### US-612: Comando `tickets:restore-waiting`
**Description:** As a sistema, devo far uscire automaticamente dallo stato `waiting` un ticket rimasto lì troppo
a lungo, per evitare che una richiesta in attesa di risposta del cliente resti bloccata a tempo indeterminato
(§10.2).

**Acceptance Criteria:**
- [ ] `tickets:restore-waiting` (schedulato giornaliero, flag `ENABLE_TICKETS_RESTORE_WAITING`): ticket
      `waiting` da ≥ `N` giorni (default 7, `TICKET_RESTORE_WAITING_DAYS`) → `previous_status` (colonna già
      esistente da Fase 0, valorizzata dalla macchina a stati quando si entra in `waiting`).
- [ ] Conforme a §10.1.
- [ ] Test: un ticket `waiting` da esattamente `N`/`N-1`/`N+1` giorni si comporta secondo la soglia attesa;
      `--dry-run`; idempotenza.
- [ ] Typecheck passa.

### US-613: Comando `timetracking:aggregate-daily`
**Description:** As a sistema, devo consolidare ogni sera le ore lavorate della giornata in `ticket_work_logs`,
per avere l'aggregato pronto senza doverlo ricalcolare a ogni lettura (§10.2, colma un gap esplicito del v1: "il
job esiste ma non ha alcuna cadenza schedulata").

**Acceptance Criteria:**
- [ ] `timetracking:aggregate-daily` (schedulato 23:30, flag `ENABLE_TIMETRACKING_AGGREGATE`): consolida
      `ticket_work_logs` per la giornata, riusando `WorkedTimeCalculator` (Fase 1) — nessuna nuova logica di
      calcolo, solo l'orchestrazione schedulata che oggi manca.
- [ ] Conforme a §10.1; idempotente per costruzione (upsert su `ticket_work_logs`, vincolo unique già esistente
      da Fase 1).
- [ ] Test: esecuzione produce gli stessi aggregati di `timetracking:recalculate` (Fase 1) sullo stesso giorno;
      `--dry-run`; ri-esecuzione non duplica.
- [ ] Typecheck passa.

### US-614: Mailable E8 — Digest periodico
**Description:** As a cliente che l'ha abilitato, voglio ricevere un riepilogo giornaliero dei ticket con
attività, invece di un'email per ogni singolo evento, per non essere sommerso di notifiche (§7.5.2).

**Acceptance Criteria:**
- [ ] Nuovo Mailable E8 (layout unico di Fase 3, localizzato, `ShouldQueue`), contenuto: ticket del cliente con
      attività nelle 24h precedenti (nuovi messaggi, cambi di stato) — **non** ogni singolo evento raw, un
      riepilogo aggregato per ticket.
- [ ] Comando `mail:send-digest` (schedulato 07:00, flag `ENABLE_MAIL_DIGEST` — già provisionato da Fase 0,
      verificato inutilizzato fino a questa story): un'email per cliente con attività, rispetta soppressioni e
      preferenze di notifica (nessun invio a chi ha disattivato E8, US-605).
- [ ] **Riscritto da zero**: il v1 lo ha come dead code con 4 bug noti — nessun codice v1 riusato, stessa
      disciplina già applicata all'intero sottosistema email in Fase 3.
- [ ] Conforme a §10.1 (feature flag, idempotenza — non duplica un digest già inviato oggi per lo stesso
      cliente, `--dry-run`, log strutturato).
- [ ] Test: contenuto del digest corretto per un cliente con attività su più ticket; nessun digest per un
      cliente senza attività nelle 24h; rispetto soppressioni/preferenze; localizzazione.
- [ ] Typecheck passa.

### US-615: Mailable E10 — Report attività disponibile
**Description:** As un owner di un report attività, voglio essere avvisato quando il PDF del mio report è
pronto, per non dover controllare periodicamente se è stato generato (§7.5.2).

**Acceptance Criteria:**
- [ ] Nuovo Mailable E10, dispatchato quando `ActivityReport.pdf_generated_at` viene valorizzato per la prima
      volta (evento di dominio, non un hook Eloquent — stesso principio già applicato in `DocumentationPageCreated`
      di Fase 4).
- [ ] Destinatario: l'owner del report (utente, o tutti i membri se organizzazione — coerente con
      `ActivityReport::ownerName()` già esistente da Fase 4).
- [ ] Link al download del PDF nell'email, autorizzato dalla Policy già esistente (Fase 4, nessuna nuova rotta).
- [ ] Rispetta soppressioni/preferenze di notifica.
- [ ] Test: invio triggerato dalla generazione PDF (sia manuale sia da `reports:generate-monthly`), link di
      download funzionante, rispetto preferenze.
- [ ] Typecheck passa.

### US-616: Mailable E11 — Developer senza ticket in lavorazione + comando
**Description:** As a developer con ticket assegnati ma nessuno in lavorazione attiva, voglio un promemoria
interno, per non dimenticarmi di riprendere un ticket in coda (§7.5.2).

**Acceptance Criteria:**
- [ ] Nuovo Mailable E11 (o solo notifica in-app Filament se il PRD la considera equivalente — verificare §7.5.2
      "ogni comunicazione ha anche una notifica in-app quando il destinatario è interno": qui il destinatario è
      sempre interno, quindi **entrambe** email e notifica in-app).
- [ ] Comando `tickets:notify-idle-developers` (schedulato ogni 30 min, 09:00–15:30, flag
      `ENABLE_TICKETS_IDLE_DEVELOPER_NOTICE` — già provisionato da Fase 0): un developer con almeno un ticket
      `assignee_id = lui` ma nessuno `status = progress` riceve il promemoria — **non** più volte nella stessa
      finestra se già notificato (idempotenza sulla finestra, non solo sulla singola esecuzione).
- [ ] **Comando schedulato**, non un job ritardato da observer come nel v1 (correzione esplicita del PRD
      principale, §10.2).
- [ ] Conforme a §10.1.
- [ ] Test: un developer idle nella finestra oraria riceve il promemoria una sola volta; fuori finestra o con un
      ticket in progress, nessun invio; `--dry-run`.
- [ ] Typecheck passa.

### US-617: Documentazione completa del progetto
**Description:** As a chiunque si unisca al progetto o debba operarlo in produzione, ho bisogno che la
documentazione descriva davvero come funziona il sistema, non solo il codice sorgente, per non dover
reverse-engineerare ogni comportamento (§13.4).

**Acceptance Criteria:**
- [ ] Scritti gli 8 file mancanti di `docs/` (verificato assenti prima di questa story): `architecture.md`
      (struttura a moduli, principi A1–A9, dove sta cosa), `data-model.md` (schema con diagramma e mappa nomi
      v1→v2 di §0.3), `email.md` (pipeline inbound/outbound, configurazione, troubleshooting, catalogo
      comunicazioni E1-E11 aggiornato con questa fase), `time-tracking.md` (algoritmo, ipotesi, comandi di
      ricalcolo/aggregazione), `authorization.md` (i tre livelli §9.1, catalogo permessi, matrice ruolo→permessi,
      perché non modificabili a runtime, come concedere un permesso diretto), `import-v1.md` (procedura ETL,
      stage, tutti i compromessi di mapping di §11.5, come leggere il report), `operations.md` (deploy,
      scheduler, coda, backup, `orchestrator:doctor`, cutover, **setup/recovery MFA** da US-606), `differences-
      from-v1.md` (differenze di comportamento, elenco bug v1 corretti).
- [ ] `README.md` (repo root, già esistente) verificato/aggiornato: setup da zero, importazione dal dump,
      comandi principali, architettura in una pagina — non riscritto da zero se già adeguato, solo integrato
      dove mancante.
- [ ] `docs/ticket-lifecycle.md` (già esistente) verificato aggiornato con eventuali transizioni/automazioni
      aggiunte in questa fase (US-610/611/612).
- [ ] Nessun file duplica contenuto già coperto da `docs/design-system.md`/`docs/design-inventory.md`
      (già esistenti, non toccati da questa story salvo refusi).
- [ ] Typecheck passa (nessuna modifica a codice in questa story oltre a eventuali refusi, ma il gate resta
      comunque verificato).

### US-618: Checkpoint di fine Fase 6 — verifica end-to-end e pacchetto di collaudo
**Description:** As a team, prima di considerare chiusa l'ultima fase funzionale prima del cutover dobbiamo
verificare l'intero flusso su dati reali e produrre il pacchetto di collaudo completo (obbligo esplicito in
`CLAUDE.md`, "Processo di collaudo").

**Acceptance Criteria:**
- [ ] Verifica end-to-end su dati reali: un utente cliente reale importato dal dump accede e vede **solo** i
      propri dati in ogni schermata toccata da questa fase (dashboard, ticket, report, documentazione,
      fundraising) — criterio di accettazione esplicito della roadmap, §14.
- [ ] Ogni comando introdotto in questa fase (US-610→613, US-616) verificato con `--dry-run` e log strutturato
      su dati reali — secondo criterio di accettazione esplicito della roadmap, §14.
- [ ] Se `tickets:archive-scrum` (US-611) è stato implementato in modo conservativo per incertezza sul
      comportamento v1, il compromesso è esplicitamente rivisto col committente qui, non lasciato implicito.
- [ ] `docs/collaudo/fase-6.php` — manifest topic → test numerati → riferimento a un test automatico realmente
      esistente; `php artisan collaudo:verify-manifest 6` passa.
- [ ] Manuale narrativo `docs/collaudo/<N>-fase-6.md` (numerazione file da verificare sullo stato reale di
      `docs/collaudo/` al momento dell'implementazione — Fase 5 ha lasciato `10-fase-5.md`/
      `11-registro-esiti.md`/`12-verbale-collaudo.md`), scritto **per ultimo**, dopo che tutte le story
      US-601→US-617 sono complete.
- [ ] Aggiornamento del pacchetto cumulativo (README/istruzioni generali/matrice tracciabilità/registro esiti).
- [ ] `php artisan collaudo:generate 6` produce il PDF di collaudo senza errori.
- [ ] Report dei compromessi/gap emersi rivisto con il committente.

## 4. Requisiti funzionali

- FR-1: Un cliente vede in ogni schermata (dashboard, ticket, report, fundraising, documentazione) solo i
  propri dati, mai quelli di altri clienti — verificato anche via richiesta manipolata.
- FR-2: La navigazione si adatta al ruolo: nessuna voce di un altro ruolo è visibile, la landing post-login è
  quella corretta per ruolo (customer/fundraising/staff).
- FR-3: La ricerca globale e i badge di navigazione rispettano la Policy dell'utente corrente e non introducono
  una query per elemento a ogni render (badge con cache).
- FR-4: Un utente gestisce le proprie preferenze di notifica da UI; il rispetto di quelle preferenze
  (già garantito da Fase 3) resta invariato.
- FR-5: Ogni nuova automazione schedulata (US-610→613, US-616) rispetta le regole comuni di §10.1: feature
  flag default `false`, idempotenza, `--dry-run`, log strutturato, `ticket_logs`/audit con `is_system`/
  `User::system()` dove pertinente.
- FR-6: E8/E10/E11 riusano layout, localizzazione, soppressioni e preferenze già stabiliti in Fase 3 — nessuna
  logica di invio parallela o divergente.
- FR-7: Un utente disattivato non accede, non è selezionabile in nessun picker di assegnazione/partner/
  destinatario, ma resta intatto nello storico.
- FR-8: La WorkBoard rifinita mantiene lo stesso paradigma a colonne per stato della versione essenziale di
  Fase 1 (decisione presa), con le stesse card/informazioni, solo con lo stile del design system.
- FR-9: MFA è opzionale e abilitabile per ruolo, mai obbligatoria in modo hardcoded per tutti gli utenti senza
  possibilità di configurazione.
- FR-10: Ogni impersonation è tracciata e visibilmente segnalata (banner) per tutta la sua durata.

## 5. Non-Goals (fuori scope)

- **`help_desk_chat_url`/link chat di supporto**: esplicitamente non confermato dal committente per questa
  fase (vedi Decisioni) — nessuna colonna, nessun link.
- **Drive Standard, Riunioni e verbali, Ricerca CAI (RAG), ETS Dashboard, Escursioni**: le 5 nuove feature
  commerciali già classificate fuori scope in `docs/design-inventory.md` (Fase 0) — non toccate da questa fase,
  restano in `docs/future-features.md`.
- **Cambio di paradigma della WorkBoard** (lista/tabella, swimlane per assegnatario): valutato e scartato dal
  committente in favore del Kanban rifinito (vedi Decisioni).
- **Drag & drop sulla WorkBoard**: resta opzionale/non implementato, come già stabilito in Fase 1 (US-113) —
  questa fase rifinisce lo stile, non aggiunge questa interazione.
- **Cutover, prova completa su staging, confronto v1/v2, test di carico, verifica di sicurezza formale**: Fase 7,
  non questa.

## 6. Design Considerations

- Ogni schermata nuova o rifinita (dashboard cliente, WorkBoard, preferenze notifica) deriva da
  `docs/design-system.md`/`resources/css/theme.css`, stessa fonte di verità già stabilita in tutte le fasi
  precedenti — niente hex o stili duplicati.
- Il banner di impersonation (US-607) è un elemento persistente cross-pagina: verificare come il pacchetto
  scelto lo implementa (layout override vs componente Filament nativo) prima di scrivere CSS custom.
- La dashboard cliente (US-601) e la navigazione "Area cliente" (US-602) seguono la struttura di riferimento di
  §8.4, esplicitamente "da riconciliare con il design" — dato che il mockup reale copre solo un sottoinsieme di
  queste schermate (vedi `docs/design-inventory.md`), usare i pattern Filament di default per le parti senza
  mockup dedicato, coerente con la regola già applicata in Fase 0 (§8.3).

## 7. Technical Considerations

- **Nessuna nuova migrazione prevista** per `notification_preferences` (esiste da Fase 0); eventuali nuove
  tabelle (es. log di impersonation, se il pacchetto scelto non ne fornisce una propria) vanno introdotte con
  una migrazione dedicata e documentate in US-607.
- Riuso esplicito: `WorkedTimeCalculator` (Fase 1) per US-613, `ChangeTicketStatus`/macchina a stati (Fase 1)
  per US-610/611/612 — mai un update diretto su `tickets.status`, `User::system()` (Fase 0/3) come attore per
  ogni comando schedulato, layout email unico e `RecipientLocale` (Fase 3) per US-614/615/616.
- `ENABLE_MAIL_DIGEST` e `ENABLE_TICKETS_IDLE_DEVELOPER_NOTICE` sono feature flag **già provisionati** da
  Fase 0/3 ma mai wired a un comando reale (verificato con `grep` prima di questa fase, stessa disciplina già
  applicata in Fase 3 US-325) — non introdurre flag duplicati.
- Scegliere il pacchetto di impersonation (US-607) verificandone la compatibilità reale con Filament 4 al
  momento dell'implementazione (l'ecosistema Filament evolve rapidamente), non assumerla da questo PRD.
- Nessuna chiamata `env()` fuori da `config/*.php` (§13.3), stesso vincolo già rispettato nelle fasi precedenti.

## 8. Success Metrics

- Un cliente reale importato dal dump accede e vede solo i propri dati in ogni schermata toccata da questa fase
  (criterio di accettazione esplicito §14, verificato al checkpoint).
- Tutti i comandi introdotti in questa fase hanno `--dry-run` funzionante e log strutturato (criterio di
  accettazione esplicito §14).
- `php artisan collaudo:verify-manifest 6` passa al checkpoint.
- Zero regressioni di performance sulla WorkBoard rifinita rispetto alla versione essenziale (stesso numero di
  query o meno, misurato in test).
- Tutti gli 11 file di `docs/` di §13.4 esistono con contenuto reale (non placeholder).

## 9. Open Questions

- Granularità esatta di "MFA abilitabile per ruolo" (US-606): quali ruoli la richiedono obbligatoriamente vs
  opzionalmente — proposta di default "obbligatoria per `admin`, opzionale per gli altri", da confermare col
  committente durante l'implementazione, non bloccante per iniziare.
- Comportamento esatto di `tickets:archive-scrum` (US-611, "Q9" mai risolta nel PRD principale): se il dump v1
  non chiarisce il comportamento storico con certezza, si procede con l'opzione conservativa descritta nell'AC
  e si rivede col committente al checkpoint (US-618).
- Pacchetto di impersonation esatto (US-607): scelta implementativa da confermare al momento, non vincolante
  ora.
