# PRD: Fase 5 — Fundraising

> Riferimento completo: `PRD-ORCHESTRATOR-V2.md` §6.6 (M6 — Fundraising), §9.3-9.4 (permessi), §13.1 (test
> obbligatori) e §14 (Roadmap, Fase 5). Numerazione story `US-5xx` per restare nel range riservato a questa fase.

## 1. Introduzione/Overview

Fase 2 ha importato dal v1 `fundraising_opportunities`, `fundraising_evaluation_scores` (le 34 colonne
`evaluation_*` del v1 normalizzate in righe), `fundraising_projects` e `fundraising_project_partners` con i loro
dati storici. Questa fase costruisce la **logica applicativa e l'interfaccia** sopra quei dati: gestione delle
opportunità (bandi), la griglia di valutazione reattiva sul catalogo dei criteri, i progetti con i loro partner, e
la vista in sola lettura per il cliente.

Il modulo è visibile **solo** ai ruoli `admin` e `fundraising` (§6.6): nessuna delle sue schermate compare per
`manager`/`developer`. Il cliente vede solo le opportunità e i propri progetti coinvolti (§6.6.4), mai la griglia
di valutazione né i progetti in cui non è coinvolto.

Nessuna delle entità ha oggi logica applicativa in v2 oltre a schema ed ETL: `FundraisingOpportunity`,
`FundraisingEvaluationScore` e `FundraisingProject` esistono come tabelle popolate ma senza Model con
comportamento, senza Policy, senza UI Filament. `tickets.fundraising_project_id` esiste già da Fase 0 ma non è
ancora collegabile da nessuna UI.

## 2. Goals

- Un'opportunità scaduta (`deadline < oggi`) è distinguibile a colpo d'occhio da una attiva, con un archivio
  separato per le scadute (§6.6.1).
- Il catalogo dei criteri di valutazione vive **solo** in configurazione/enum PHP, mai nel database: aggiungere
  un nuovo criterio in futuro è una voce nel catalogo, **nessuna migrazione** (criterio di accettazione esplicito
  della roadmap, §14).
- Il calcolo dei totali di valutazione (`evaluation_positive_total`/`evaluation_negative_total`/
  `evaluation_total`) è un **service puro con test unitari**, invocato dall'action di salvataggio — mai un hook
  `saving()` che gira a ogni salvataggio.
- I punteggi calcolati su commesse reali importate dal v1 coincidono con quelli originali (criterio di
  accettazione esplicito della roadmap, §14).
- Un progetto percorre il proprio stato (`draft`→`submitted`→`approved`/`rejected`→`completed`) solo tramite
  transizioni esplicite, con capofila/partner/responsabile tracciati correttamente.
- Un cliente vede in sola lettura solo le opportunità e i propri progetti **coinvolti** (capofila o partner); il
  dettaglio di un progetto in cui non è coinvolto non è raggiungibile nemmeno via URL diretto.

## 3. User Stories

### US-501: Modello `FundraisingOpportunity` — Policy e stato di scadenza
**Description:** As a membro del team fundraising, ho bisogno che ogni opportunità sappia da sola se è scaduta,
per poter distinguere bandi ancora validi da quelli ormai archiviati (§6.6.1).

**Acceptance Criteria:**
- [ ] Model `App\Domain\Fundraising\Models\FundraisingOpportunity` con `isExpired(): bool` (`deadline < oggi`,
      confronto sulla sola data, non sull'orario) e scope `active` (`deadline >= today`)/`expired`.
- [ ] Policy `FundraisingOpportunityPolicy` (`fundraising.view.any`/`.view.involved`/`.create`/`.update`/
      `.delete`, §9.4: solo `admin` e `fundraising` hanno `.view.any`/`.create`/`.update`/`.delete`; `customer`
      ha solo `.view.involved`, mai le altre) — deny by default, coerente con le Policy già esistenti da Fase 0.
- [ ] Nessuna schermata del modulo compare in navigazione per `manager`/`developer` (nessun permesso fundraising
      nella loro matrice, §9.4).
- [ ] Test unitari su `isExpired()`/scope `active`/`expired` (deadline oggi, ieri, domani); test feature sulla
      Policy riga per riga per ogni ruolo.
- [ ] Typecheck passa.

### US-502: Vista Filament opportunità — elenco, archivio, filtri
**Description:** As a membro del team fundraising, voglio un elenco delle opportunità attive separato da quelle
scadute, con i filtri per restringere la ricerca, per non dover scorrere bandi non più rilevanti (§6.6.1).

**Acceptance Criteria:**
- [ ] `FundraisingOpportunityResource` Filament: vista elenco (default: solo `active`, §6.6.1) e vista separata
      "Archivio" per le opportunità scadute (`expired`).
- [ ] Filtri: ambito territoriale (`TerritorialScope`), cofinanziamento (con/senza quota), scaduto/attivo.
- [ ] Form CRUD standard con tutti i campi di `fundraising_opportunities` (§5.2): nome, URL ufficiale, dotazione
      del fondo, scadenza (obbligatoria), nome programma, ente finanziatore, quota di cofinanziamento, contributo
      massimo, ambito territoriale, requisiti beneficiario/capofila, creatore, responsabile.
- [ ] `created_by` valorizzato automaticamente dall'utente autenticato alla creazione, mai editabile in un
      secondo momento.
- [ ] Test feature: elenco mostra solo le attive di default, l'archivio mostra solo le scadute, ogni filtro
      produce il sottoinsieme atteso.
- [ ] Typecheck passa.

### US-503: Catalogo criteri di valutazione + service di calcolo totali
**Description:** As a sistema, devo calcolare i totali di una valutazione in modo puro e testabile a partire da un
catalogo di criteri configurabile, per garantire che i punteggi coincidano con quelli storici e che aggiungere un
criterio non richieda mai una migrazione (§6.6.2, §14).

**Acceptance Criteria:**
- [ ] Catalogo criteri in configurazione/enum PHP (non DB): i 5 blocchi di §6.6.2 — Criteri principali
      (`criterion_a`..`criterion_f`, range 0–5), Requisiti base (`base_coerenza_bando`, `base_capofila_idoneo`,
      `base_partner_minimi`, `base_cofinanziamento`, `base_tempistiche`, range 0–1), Qualitativi
      (`qual_coerenza_cai`, `qual_imp_ambientale`, `qual_imp_sociale`, `qual_imp_economico`,
      `qual_obiettivi_chiari`, `qual_solidita_azioni`, `qual_capacita_partner`, range 0–5), Premiali
      (`prem_innovazione`, `prem_replicabilita`, `prem_comunita`, `prem_sostenibilita`, range 0–3), Rischi
      (`risk_tecnici` 0–3, `risk_finanziari` −3..3, `risk_organizzativi` −2..2, `risk_logistici` −2..2) — ognuno
      con chiave, gruppo, etichetta, range minimo/massimo.
- [ ] `fundraising_evaluation_scores` (una riga per criterio, già esistente da Fase 0/2) — il range di ogni
      punteggio è **validato dall'applicazione** contro il catalogo, mai lasciato solo a un commento SQL (v1).
- [ ] `App\Domain\Fundraising\Services\CalculateEvaluationTotals` — service puro: `evaluation_positive_total` =
      somma di tutti i punteggi ≥ 0, `evaluation_negative_total` = somma dei **valori assoluti** dei punteggi < 0
      (solo i criteri del blocco Rischi possono essere negativi), `evaluation_total` = positivo − negativo.
      Invocato dall'action di salvataggio dei punteggi, **mai** da un hook `saving()`/`saved()` sul model.
- [ ] `evaluated_by`/`evaluated_at` si valorizzano quando viene salvato il **primo** punteggio di
      un'opportunità, mai sovrascritti da salvataggi successivi.
- [ ] Test unitari (requisito esplicito §13.1 "Totali griglia fundraising"): somme positive/negative, rischi
      negativi, valori limite (min/max di ogni range), un criterio aggiunto al catalogo runtime (senza toccare il
      DB) viene incluso correttamente nel calcolo.
- [ ] Typecheck passa.

### US-504: UI griglia di valutazione
**Description:** As a membro del team fundraising, voglio compilare la griglia di valutazione di un'opportunità
raggruppata per blocco, con il totale visibile in tempo reale, per capire subito l'effetto di ogni punteggio senza
dover salvare e ricaricare la pagina (§6.6.2).

**Acceptance Criteria:**
- [ ] Sezione/tab dedicata su `FundraisingOpportunityResource` (view/edit) con un campo per ogni `criterion_key`
      del catalogo (US-503), raggruppati per blocco (Criteri principali, Requisiti base, Qualitativi, Premiali,
      Rischi) — i criteri del blocco "Criteri principali" hanno anche un campo nota testuale (§6.6.2).
- [ ] Totale (`evaluation_positive_total`/`.negative_total`/`.total`) calcolato e mostrato **in tempo reale**
      mentre si compila (reattivo, senza submit), con una visualizzazione sintetica del punteggio complessivo.
- [ ] Validazione dei range direttamente in UI (min/max per criterio dal catalogo), messaggio d'errore leggibile
      su un valore fuori range.
- [ ] Test feature: compilare la griglia aggiorna i totali persistiti coerentemente col service di US-503;
      `evaluated_by`/`evaluated_at` valorizzati al primo salvataggio, invariati ai successivi.
- [ ] Typecheck passa.
- [ ] Verifica in browser (screenshot Chrome headless) della griglia compilata su un'opportunità reale, con il
      totale che si aggiorna mentre si inseriscono i punteggi.

### US-505: Azioni "crea un progetto" / "crea un ticket" da un'opportunità
**Description:** As a membro del team fundraising, voglio poter avviare un progetto o un ticket direttamente da
un'opportunità, per non dover ricopiare a mano i riferimenti al bando di origine (§6.6.1).

**Acceptance Criteria:**
- [ ] Azione Filament "Crea progetto" su `FundraisingOpportunityResource` (view) che crea un
      `FundraisingProject` con `fundraising_opportunity_id` precompilato e `title` precompilato dal nome
      dell'opportunità (editabile prima del salvataggio).
- [ ] Azione Filament "Crea ticket" su `FundraisingOpportunityResource` (view) che crea un `Ticket` con
      `fundraising_project_id` valorizzato **solo se** l'opportunità ha già un progetto collegato dall'azione
      precedente, altrimenti il campo resta vuoto (nessun collegamento forzato opportunità→ticket diretto, lo
      schema non lo prevede: `tickets.fundraising_project_id`, mai `tickets.fundraising_opportunity_id`).
- [ ] Test feature: entrambe le azioni creano il record con i campi precompilati attesi e collegamenti corretti.
- [ ] Typecheck passa.
- [ ] Verifica in browser (screenshot Chrome headless) di entrambe le azioni su un'opportunità reale.

### US-506: Modello `FundraisingProject` — stato e partner
**Description:** As a membro del team fundraising, ho bisogno che un progetto percorra un ciclo di vita chiaro
dalla bozza alla decisione, con capofila e partner tracciati correttamente, per sapere sempre a che punto è una
candidatura (§6.6.3).

**Acceptance Criteria:**
- [ ] Model `App\Domain\Fundraising\Models\FundraisingProject` con enum `FundraisingProjectStatus`
      (`draft`→`submitted`→`approved`/`rejected`→`completed`) e transizioni **esplicite** (stessa disciplina
      della macchina a stati dei ticket, Fase 1: nessuna transizione libera non in tabella).
- [ ] Relazione N-N `fundraising_project_partners` (già esistente da Fase 0/2) verso `users`.
- [ ] Policy `FundraisingProjectPolicy` (stesso schema permessi di US-501: `admin`/`fundraising` pieno accesso,
      `customer` solo `.view.involved` sui progetti in cui è **coinvolto** — capofila O partner O responsabile O
      creatore, §6.6.3).
- [ ] **Fix del bug v1 esplicito nel PRD**: il metodo v1 `partnerCustomers()` filtrava i partner con ruolo
      `customer` con `whereHas('roles', ...)` su una colonna JSON (query non eseguibile); in v2 i ruoli sono un
      pivot (`model_has_roles`, Fase 0), quindi lo stesso filtro è una join/`whereHas` normale — implementare
      questo filtro (dove serve distinguere partner interni da partner cliente) con la relazione pivot, mai
      riprodurre la query JSON del v1.
- [ ] Test unitari sulla macchina a stati (ogni transizione ammessa/vietata); test feature sulla Policy riga per
      riga per ogni ruolo, incluso lo scope "coinvolti".
- [ ] Typecheck passa.

### US-507: Vista Filament progetti — elenco, filtri, collegamento ticket
**Description:** As a membro del team fundraising, voglio un elenco dei progetti filtrabile per stato/capofila/
partner/coinvolgimento, e la possibilità di collegare un ticket esistente a un progetto, per tenere insieme il
lavoro operativo e la candidatura che lo ha originato (§6.6.3).

**Acceptance Criteria:**
- [ ] `FundraisingProjectResource` Filament: elenco con stato, capofila, importo richiesto/approvato, date di
      presentazione/decisione.
- [ ] Filtri: per stato, per capofila, per partner, "coinvolti" (capofila OR partner OR responsabile OR
      creatore, stessa definizione di US-506).
- [ ] Gestione partner (aggiungi/rimuovi utenti) sulla vista/edit del progetto.
- [ ] `tickets.fundraising_project_id` collegabile da `TicketResource` (campo select sui progetti visibili
      all'utente corrente secondo la Policy) — **non** il contrario: nessuna azione "aggiungi ticket" dal lato
      progetto in questa story, il collegamento si fa dal ticket (coerente con `fundraising_project_id` come
      unica FK dello schema, §5.2).
- [ ] Test feature: ogni filtro produce il sottoinsieme atteso; collegare un ticket a un progetto dalla
      `TicketResource` persiste correttamente `fundraising_project_id`.
- [ ] Typecheck passa.
- [ ] Verifica in browser (screenshot Chrome headless) dell'elenco progetti con i filtri applicati e del
      collegamento di un ticket reale a un progetto.

### US-508: Vista cliente — opportunità e progetti coinvolti
**Description:** As a cliente, voglio vedere le opportunità di finanziamento disponibili e i progetti in cui sono
coinvolto, in sola lettura, per tenermi informato senza dover chiedere allo staff (§6.6.4).

**Acceptance Criteria:**
- [ ] Un utente `customer` vede, in sola lettura: elenco e dettaglio delle opportunità (nessuna differenza tra
      attive/scadute in questa vista, §6.6.4 non lo richiede) e i progetti in cui è **coinvolto** (capofila o
      partner — non "responsabile"/"creatore", quei ruoli sono interni allo staff, §6.6.4 li limita
      esplicitamente a capofila/partner).
- [ ] Il dettaglio di un progetto in cui il cliente **non** è coinvolto non è raggiungibile nemmeno via URL
      diretto (test su policy + query scope, stesso principio già applicato a `DocumentationPage` in Fase 4).
- [ ] Nessuna azione di scrittura visibile/eseguibile da un cliente su opportunità o progetti (sola lettura reale,
      non solo azioni nascoste in UI — verificato anche via richiesta manipolata).
- [ ] Test feature: un cliente coinvolto vede il progetto, uno non coinvolto riceve 403; le opportunità sono
      visibili a qualunque cliente autenticato.
- [ ] Typecheck passa.
- [ ] Verifica in browser (screenshot Chrome headless) della vista cliente con un utente reale coinvolto in
      almeno un progetto.

### US-509: Checkpoint di fine Fase 5 — verifica end-to-end e pacchetto di collaudo
**Description:** As a team, prima di considerare chiusa la fase dobbiamo verificare l'intero flusso su dati reali
importati dal v1 e produrre il pacchetto di collaudo completo, come per ogni fase da Fase 4 in poi (obbligo
esplicito in `CLAUDE.md`, "Processo di collaudo").

**Acceptance Criteria:**
- [ ] Verifica end-to-end su dati reali (post `v1:import`): i totali di valutazione ricalcolati da
      `CalculateEvaluationTotals` su opportunità reali importate **coincidono** con `evaluation_positive_total`/
      `.negative_total`/`.total` già presenti dall'ETL (criterio di accettazione esplicito della roadmap, §14) —
      uno scostamento qui è un bug da correggere in questa story, non un compromesso da annotare.
- [ ] Aggiungere un criterio di prova al catalogo (solo in codice, nessuna migrazione) e verificare che venga
      incluso correttamente nel calcolo su un'opportunità di test — poi rimuoverlo, nessuna traccia permanente.
- [ ] `docs/collaudo/fase-5.php` — manifest topic → test numerati (`F5-01`, `F5-02`, ...) → riferimento a un test
      automatico realmente esistente; `php artisan collaudo:verify-manifest 5` passa.
- [ ] Manuale narrativo `docs/collaudo/1N-fase-5.md` (stesso formato per-test già in uso), scritto **per ultimo**,
      dopo che tutte le story US-501→US-508 sono complete e il manifest passa.
- [ ] Aggiornamento del pacchetto cumulativo: `README.md`, `00-istruzioni-generali.md`, `01-matrice-
      tracciabilita.md`, `08-registro-esiti.md` (numerazione file coerente con quella già in uso dopo il
      retrofit di Fase 4, verificare i nomi correnti prima di scrivere invece di assumere quelli di fasi
      precedenti).
- [ ] `php artisan collaudo:generate 5` produce il PDF di collaudo senza errori; `storage/app/collaudo/` contiene
      solo l'ultima versione generata per questa fase.
- [ ] Report dei compromessi/gap emersi (se presenti) rivisto con il committente.

## 4. Requisiti funzionali

- FR-1: `FundraisingOpportunity::isExpired()`/scope `active`/`expired` sono l'unica fonte di verità sullo stato
  di scadenza, usati sia in UI sia nei filtri.
- FR-2: Il catalogo dei criteri di valutazione vive solo in configurazione/enum PHP; aggiungere un criterio non
  richiede alcuna migrazione.
- FR-3: `CalculateEvaluationTotals` è un service puro e testato, invocato solo dall'action di salvataggio dei
  punteggi — mai un hook Eloquent.
- FR-4: `evaluated_by`/`evaluated_at` si valorizzano al primo punteggio salvato, mai risovrascritti dopo.
- FR-5: Un progetto cambia stato solo tramite transizioni esplicite della macchina a stati dedicata.
- FR-6: Un cliente vede/scarica solo le opportunità (tutte, sola lettura) e i propri progetti coinvolti
  (capofila o partner), mai altri progetti, anche via richiesta manipolata.
- FR-7: Nessuna schermata del modulo fundraising è visibile a `manager`/`developer`.
- FR-8: `tickets.fundraising_project_id` è collegabile da `TicketResource`; nessuna FK diretta
  opportunità→ticket nello schema, il collegamento passa sempre da un progetto.

## 5. Non-Goals (fuori scope)

- **"Crea opportunità da JSON"** (§6.6.1 del PRD principale): esplicitamente rimandata da Alessio — verrà
  sostituita in futuro da una feature basata su AI (import assistito del bando), non da implementare in questa
  fase. Le opportunità si creano solo tramite il form CRUD standard (US-502).
- **Automazioni schedulate**: nessuna prevista per questo modulo in §10.2, nessuna da aggiungere qui.
- **Vista cliente estesa** (dashboard, notifiche specifiche fundraising): resta nell'ambito generico del
  portale cliente di Fase 6, non di questa fase — qui il cliente ha solo le due viste read-only di US-508.
- **Documentazione/reportistica PDF delle opportunità o dei progetti**: non richiesta da §6.6, non introdotta
  qui (a differenza di Documentation/Activity Report in Fase 4, il PRD principale non la menziona per
  Fundraising).

## 6. Design Considerations

- `FundraisingOpportunityResource`/`FundraisingProjectResource` seguono lo stesso pattern Filament già
  consolidato nel repo (Resource con Policy risolta per convenzione, filtri/scope coerenti con quelli già
  costruiti per `TicketResource`/`DocumentationPageResource` in Fase 1/4).
- La griglia di valutazione (US-504) è il primo caso nel repo di un form con calcolo reattivo lato client mentre
  si compila: verificare se un pattern Filament nativo (`live()` sui campi + un campo calcolato) copre il bisogno
  prima di introdurre JS custom.

## 7. Technical Considerations

- **Nessuna nuova migrazione di schema prevista**: `fundraising_opportunities`, `fundraising_evaluation_scores`,
  `fundraising_projects`, `fundraising_project_partners` esistono già da Fase 0/2.
- Riuso esplicito: pattern Policy deny-by-default e macchina a stati esplicita già stabiliti in Fase 0/1 (stessa
  disciplina di `TicketStateMachine` per `FundraisingProjectStatus`), pattern "service puro + test unitari,
  invocato dall'action" già usato in Fase 4 per `ActivityReportSyncService`.
- **Verificare la numerazione corrente dei file del pacchetto di collaudo prima di scrivere US-509**: Fase 4 ha
  aggiunto `07-fase-4.md` e rinominato `07-registro-esiti.md`→`08-registro-esiti.md`,
  `08-verbale-collaudo.md`→`09-verbale-collaudo.md` — Fase 5 continuerà questa sequenza, ma il numero esatto va
  verificato sullo stato reale di `docs/collaudo/` al momento dell'implementazione, non assunto da questo PRD.
- Nessuna chiamata `env()` fuori da `config/*.php` (§13.3), stesso vincolo già rispettato nelle fasi precedenti.

## 8. Success Metrics

- I totali di valutazione ricalcolati su tutte le opportunità reali importate coincidono con quelli originali
  dell'ETL (0 scostamenti, criterio di accettazione esplicito §14).
- Aggiungere un criterio di prova al catalogo durante il checkpoint non tocca lo schema DB.
- `php artisan collaudo:verify-manifest 5` passa al checkpoint, nessun test referenziato nel manifest
  inesistente.
- Zero progetti raggiungibili da un cliente non coinvolto durante il collaudo (incluso via richiesta
  manipolata).

## 9. Open Questions

- Formato esatto della UI reattiva della griglia di valutazione (Filament `live()` vs componente Livewire
  dedicato): decisione implementativa da prendere durante US-504, non bloccante per iniziare la fase.
- Se in futuro la feature AI di import bando (Non-Goals) richiederà un formato JSON strutturato diverso da
  quello originariamente ipotizzato in fase di brainstorming, quella scelta va rifatta da zero al momento —
  nessun vincolo ereditato da questa fase.
