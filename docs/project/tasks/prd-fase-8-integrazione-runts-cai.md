# PRD: Fase 8 — Integrazione dati RUNTS-CAI (Sezioni/Sottosezioni)

> Riferimento completo: `orchestrator/docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md`
> (design approvato col committente in sessione di brainstorming, 2026-08-28) e PRD-ORCHESTRATOR-V2.md §14
> (Roadmap, Fase 8). Numerazione story `US-80x` per restare nel range riservato a questa fase.

## 1. Introduzione/Overview

Integra in Orchestrator le funzionalità del prototipo esterno RUNTS-CAI (dati Sezioni/Sottosezioni CAI dal
RUNTS — registro pubblico del Ministero del Lavoro — più una directory ufficiale CAI: contatti, bilanci,
allegati), limitatamente a Sezioni/Sottosezioni (i Gruppi Regionali RUNTS restano fuori scope). Non è
un'importazione ricorrente: un datapack (`cai-datapack/`, mai versionato con git, stesso principio di
`v1dumps/`) è già stato preparato e reso disponibile in locale e su UAT (`bin/push-cai-datapack`, già
committato ed eseguito in questa sessione).

**Verificato prima di scrivere le story**: il datapack (`cai-datapack/runts-cai.sqlite` + `cai-datapack/
attachments/`) esiste già in locale e su msuat (`/opt/mstickets-uat/cai-datapack`, rsync già eseguito). Le
tabelle nel datapack: `sezioni_cai` (529 righe), `sottosezioni_cai` (224), `enti` (184, solo righe RUNTS con
match su una sezione nota), `bilanci` (67), `cariche_sociali` (0 — struttura pronta, dati non ancora
disponibili dalla fonte), `allegati` (201, con i relativi file PDF in `attachments/`). Il collegamento agli
utenti clienti esistenti (`customer_type = Sezione`, Fase 7) avviene per **email** (case-insensitive):
verificato 505/529 sezioni con match diretto.

## 2. Goals

- Ogni riga di `sezioni_cai`/`sottosezioni_cai` del datapack è importata in Orchestrator, collegata
  all'utente cliente corrispondente quando l'email combacia — mai un errore quando non combacia.
- Lo staff può consultare (sola lettura) l'intero directory CAI Sezioni/Sottosezioni: lista, filtri,
  dettaglio con bilanci/allegati scaricabili/sottosezioni, mappa, export.
- Un cliente Sezione vede i propri dati CAI/RUNTS sulla propria dashboard (Fase 6/7), con uno stato vuoto
  esplicito se non esiste un match.
- Un cliente Gruppo Regionale può aprire il dettaglio completo di ogni sezione della propria regione (mai di
  un'altra) dalla card "Sezioni del gruppo regionale" già esistente (Fase 7).
- L'import è idempotente e rieseguibile (locale e ad ogni deploy UAT, dove l'intero DB viene ricreato da zero
  ad ogni push su `develop`).

## 3. User Stories

### US-801: Schema dati `App\Domain\CaiDirectory`
**Description:** As a developer, ho bisogno delle tabelle per persistere i dati CAI/RUNTS di Sezioni e
Sottosezioni collegati agli utenti clienti esistenti.

**Acceptance Criteria:**
- [ ] Nuove migrazioni additive: `cai_sections` (da `sezioni_cai`: `codice_cai` PK naturale, contatti,
      indirizzo, anno fondazione, soci, coordinate, regione, `user_id` nullable FK `users`), `cai_subsections`
      (da `sottosezioni_cai`: `cai_codice` PK naturale, FK `cai_section_id`, stessi campi contatto, `user_id`
      nullable FK `users`), `cai_runts_registrations` (da `enti`: `id_runts` PK naturale, FK `cai_section_id`
      nullable, natura giuridica, data iscrizione, PEC, rappresentante legale, url scheda ufficiale),
      `cai_financial_statements` (da `bilanci`: FK `cai_runts_registration_id`, oneri/proventi per categoria,
      risultato d'esercizio), `cai_board_members` (da `cariche_sociali`: FK `cai_runts_registration_id`, ruolo,
      nominativo, validità — tabella vuota all'origine, struttura pronta per un futuro arricchimento),
      `cai_documents` (da `allegati`: FK `cai_runts_registration_id`, tipo documento, anno, riferimento al
      file nello storage privato, hash).
- [ ] Modelli Eloquent nel dominio `App\Domain\CaiDirectory\Models`, relazioni esplicite (`CaiSection
      hasMany CaiSubsection`, `belongsTo User` nullable, ecc.).
- [ ] Nessuna tabella per `gruppi_regionali_cai`/`geocoding_cache` (fuori scope, verificato nel design).
- [ ] Typecheck passes.
- [ ] Tests pass.

### US-802: Comando `cai:import-datapack`
**Description:** As a sistema, devo importare il datapack RUNTS-CAI nelle tabelle di Orchestrator,
collegando ogni sezione/sottosezione all'utente cliente corrispondente per email.

**Acceptance Criteria:**
- [ ] Nuovo comando `php artisan cai:import-datapack {--path=cai-datapack/runts-cai.sqlite}`: apre il file
      SQLite del datapack tramite una connessione DB dedicata in sola lettura (estensione `pdo_sqlite`/
      `sqlite3`, già presente nell'immagine PHP — verificato).
- [ ] Se il file al path indicato non esiste: messaggio esplicito e uscita, nessun errore criptico (stesso
      principio di `bin/load-v1-dump`).
- [ ] Importa `sezioni_cai`→`cai_sections`, `sottosezioni_cai`→`cai_subsections` (per intero), `enti`→
      `cai_runts_registrations` (collegate a `cai_sections` via codice fiscale), `bilanci`→
      `cai_financial_statements`, `cariche_sociali`→`cai_board_members`, `allegati`→`cai_documents`.
- [ ] `user_id` di `cai_sections`/`cai_subsections` valorizzato per match case-insensitive fra
      `cai_email`/`email` dell'utente e `users.email` — nessun match lascia `user_id = null`, mai un errore.
- [ ] I file referenziati da `allegati` (path relativo dentro `cai-datapack/attachments/`) vengono copiati
      nello storage privato esistente (stesso disco/pattern degli allegati ticket, Fase 1), con download
      autorizzato coerente con quel pattern.
- [ ] Idempotente: una seconda esecuzione sullo stesso datapack non duplica nulla né modifica righe invariate
      (stesso pattern diff/update degli stage ETL esistenti). `--dry-run` non scrive. Log strutturato
      (letti/creati/aggiornati/saltati per ciascuna tabella).
- [ ] Test unit/feature: matching email (case-insensitive, nessun match → `user_id = null`), import completo
      su una fixture ridotta, idempotenza, `--dry-run`, file mancante → messaggio esplicito.
- [ ] Typecheck passes.
- [ ] Tests pass.

### US-803: Wiring dell'import in `make setup` e nel deploy UAT
**Description:** As a sistema, i dati CAI devono essere popolati automaticamente in locale (se il datapack è
presente) e ad ogni deploy UAT (dove l'intero DB viene ricreato da zero).

**Acceptance Criteria:**
- [ ] `make setup` (locale): esegue `cai:import-datapack` **best-effort** dopo `v1:import` — se
      `cai-datapack/runts-cai.sqlite` non esiste, logga un avviso e prosegue (nessun blocco del setup,
      stesso principio già stabilito per gli allegati ticket in
      `docs/superpowers/specs/2026-08-02-etl-real-data-seeding-design.md`).
- [ ] Nuova variabile `CAI_DATAPACK_HOST_PATH` in `.env.uat.example`/`docker-compose.uat.yml`, bind-mount del
      servizio `app` su `cai-datapack/` (stesso pattern già in uso per `LEGACY_MEDIA_HOST_PATH`).
- [ ] `deploy/remote-deploy.sh`: nuova riga che esegue `cai:import-datapack` dopo `v1:import --anonymize`,
      leggendo dal bind-mount. **Nota per il committente**: `remote-deploy.sh` è copiato a mano su msuat da un
      umano (mai sincronizzato automaticamente, design esistente) — questo cambiamento richiede lo stesso
      passo manuale dopo il merge di questa PR (il datapack stesso è già stato sincronizzato su msuat in
      questa sessione, `/opt/mstickets-uat/cai-datapack`).
- [ ] Test: verifica che il comando venga effettivamente invocato nel flusso `make setup` (test di
      integrazione o verifica statica dello script, secondo cosa è ragionevolmente testabile per uno script
      di shell).
- [ ] Typecheck passes.
- [ ] Tests pass.

### US-804: Filament Resource staff — consultazione Sezioni/Sottosezioni CAI
**Description:** As a membro dello staff, voglio consultare l'intero directory CAI di Sezioni e Sottosezioni
(contatti, dati RUNTS, bilanci, allegati), per avere il quadro completo a fini di segreteria/gestione.

**Acceptance Criteria:**
- [ ] Nuova Filament Resource `CaiSectionResource` (namespace `App\Filament\Resources\CaiSections`), **sola
      consultazione**: nessuna azione Create/Edit/Delete.
- [ ] Lista: colonne principali (denominazione, comune, regione, utente collegato se presente), filtri per
      regione e per presenza di un utente collegato.
- [ ] Dettaglio: tab/sezioni per dati CAI (contatti, indirizzo, anno fondazione, soci), dati RUNTS (natura
      giuridica, data iscrizione, PEC, rappresentante legale, link alla scheda ufficiale), bilanci (tabella
      per anno), allegati (elenco con download autorizzato), sottosezioni collegate.
- [ ] Gated da un permesso dedicato nel catalogo (`Permission::CaiDirectoryView` o nome equivalente coerente
      con §9.3 del PRD principale), concesso di default almeno ad admin/manager/developer.
- [ ] Test feature: lista/filtri funzionano; dettaglio mostra i dati attesi per una sezione con
      bilanci/allegati e per una senza; un utente senza permesso non accede alla resource.
- [ ] Typecheck passes.
- [ ] Tests pass.
- [ ] Verifica in browser (screenshot Chrome headless) di lista e dettaglio con dati reali (dopo
      `cai:import-datapack` sul dataset locale).

### US-805: Mappa e export (staff)
**Description:** As a membro dello staff, voglio vedere le sezioni su una mappa ed esportare l'elenco, per
analisi e condivisione con terzi.

**Acceptance Criteria:**
- [ ] Nuova pagina Filament "Mappa sezioni CAI" con tutte le sezioni geolocalizzate (Leaflet via CDN — se già
      in uso altrove nel pannello riusare lo stesso meccanismo di inclusione, altrimenti introdurlo qui in
      modo isolato).
- [ ] Azioni di export sulla tabella di `CaiSectionResource`: CSV, XLSX, GeoJSON (solo sezioni correntemente
      filtrate/visibili, coerente coi filtri applicati).
- [ ] Stesso permesso di US-804.
- [ ] Test feature: ogni formato di export produce un file col contenuto atteso per un dataset noto.
- [ ] Typecheck passes.
- [ ] Tests pass.
- [ ] Verifica in browser (screenshot Chrome headless) della mappa con dati reali.

### US-806: Dati CAI sulla dashboard del cliente Sezione
**Description:** As a cliente Sezione, voglio vedere i miei dati CAI/RUNTS (contatti ufficiali, bilanci,
allegati, sottosezioni) sulla mia dashboard, senza dover contattare lo staff.

**Acceptance Criteria:**
- [ ] Nuova card/sezione su `CustomerDashboard` (Fase 6/7), visibile per un cliente `customer_type =
      Sezione`/`Sottosezione` con un `CaiSection`/`CaiSubsection` collegato (`user_id` = utente corrente).
- [ ] Contenuto: contatti ufficiali CAI, anno fondazione, soci, bilanci (per anno), allegati scaricabili,
      sottosezioni proprie (se il cliente è una Sezione con sottosezioni collegate).
- [ ] Se nessun `CaiSection`/`CaiSubsection` è collegato all'utente corrente: stato vuoto esplicito ("nessun
      dato CAI/RUNTS disponibile per la tua sezione"), mai una card assente silenziosa.
- [ ] Test feature: la card mostra i dati corretti per un cliente con match; stato vuoto per un cliente senza
      match; un cliente non vede mai dati di un'altra sezione.
- [ ] Typecheck passes.
- [ ] Tests pass.
- [ ] Verifica in browser (screenshot Chrome headless) per un cliente Sezione con match reale.

### US-807: Dettaglio sezione dalla dashboard del Gruppo Regionale
**Description:** As a cliente Gruppo Regionale, voglio aprire il dettaglio completo di una sezione della mia
regione dalla card "Sezioni del gruppo regionale" (Fase 7), per avere visibilità sul territorio che
rappresento.

**Acceptance Criteria:**
- [ ] Ogni riga della card "Sezioni del gruppo regionale" (`SectionsInRegionQuery`, Fase 7 US-705) diventa un
      link a una pagina di dettaglio.
- [ ] La pagina di dettaglio mostra lo stesso contenuto della card cliente Sezione (US-806) per la sezione
      scelta, **riusando lo stesso componente/vista** (nessuna duplicazione di markup/logica fra i tre punti
      di accesso: staff, cliente Sezione, cliente Gruppo Regionale — solo l'autorizzazione cambia).
- [ ] Autorizzazione verificata **lato server**: un cliente Gruppo Regionale può aprire solo sezioni la cui
      `region` combacia con la propria — un tentativo di accesso diretto a una sezione di un'altra regione
      (URL manipolato) deve fallire (403 o redirect), non solo essere assente dal link in UI.
- [ ] Nessun campo nascosto per sensibilità (dati di fonte pubblica RUNTS) — la restrizione riguarda
      esclusivamente lo scope (propria regione), non i singoli campi mostrati.
- [ ] Test feature: apertura riuscita per una sezione della propria regione; tentativo diretto su una sezione
      di un'altra regione respinto; nessuna card/link per un Gruppo Regionale senza sezioni classificate.
- [ ] Typecheck passes.
- [ ] Tests pass.
- [ ] Verifica in browser (screenshot Chrome headless) del percorso completo: card → click → dettaglio, per
      un cliente Gruppo Regionale con sezioni reali nella propria regione.

### US-808: Checkpoint di fine fase — verifica end-to-end e pacchetto di collaudo
**Description:** As a team, prima di considerare la fase conclusa, voglio un test end-to-end che replichi il
flusso completo e un pacchetto di collaudo aggiornato.

**Acceptance Criteria:**
- [ ] Nuovo test end-to-end in `tests/Feature/EndToEnd/` che copre: import del datapack (fixture ridotta) →
      collegamento per email → consultazione staff (resource) → dashboard cliente Sezione (con e senza
      match) → dashboard cliente Gruppo Regionale (dettaglio sezione della propria regione, accesso negato
      su un'altra regione).
- [ ] `docs/collaudo/fase-8.php` (manifest di tracciabilità) e manuale dettagliato `docs/collaudo/
      15-fase-8.md`, con un topic per ciascuna user story di questa fase.
- [ ] `php artisan collaudo:verify-manifest 8` passa.
- [ ] `php artisan collaudo:generate 8` genera il PDF, verificato visivamente.
- [ ] `docs/data-model.md`/`docs/architecture.md` aggiornati per riflettere lo schema/dominio nuovi.
- [ ] Typecheck passes.
- [ ] Tests pass (suite completa, nessuna regressione sulle fasi precedenti).

## 4. Functional Requirements

- FR-1: Le tabelle `cai_*` sono popolate solo tramite `cai:import-datapack`, mai da UI (sola consultazione).
- FR-2: Il collegamento `cai_sections`/`cai_subsections` ↔ `users` avviene esclusivamente per email
  case-insensitive, mai per nome (troppo fragile, verificato nel design).
- FR-3: L'import gira sia in locale (`make setup`, best-effort) sia su UAT (`remote-deploy.sh`, sempre, dopo
  ogni `migrate:fresh`).
- FR-4: Un cliente Gruppo Regionale può vedere il dettaglio SOLO delle sezioni della propria regione,
  verificato lato server.

## 5. Non-Goals (Out of Scope)

- `gruppi_regionali_cai` — nessuna tabella, nessuna UI.
- Refresh automatico/periodico del datapack (solo import iniziale, ricaricabile a mano).
- Report PDF per singola sezione (route `/ente/{id}/pdf` del prototipo sorgente).
- Editing dei dati CAI/RUNTS da UI.
- Scraper/geocoder Python (restano nel prototipo RUNTS-CAI).

## 6. Design Considerations

Vedi `orchestrator/docs/superpowers/specs/2026-08-28-integrazione-runts-cai-design.md` per il design
completo, inclusi i numeri verificati sul dataset reale e le tabelle escluse.

## 7. Technical Considerations

- Datapack e script di sincronizzazione (`bin/push-cai-datapack`) già pronti, committati e già sincronizzati
  su msuat in questa sessione (non da rifare).
- Il datapack (`cai-datapack/runts-cai.sqlite`) va copiato in locale (a mano, come `v1dumps/latest.sql`)
  prima di eseguire/testare `cai:import-datapack` durante lo sviluppo di questa fase.

## 8. Success Metrics

- ≥ 500 sezioni CAI consultabili dallo staff dopo un import sul dataset reale.
- ≥ 500 sezioni con `user_id` collegato correttamente (match per email).

## 9. Open Questions

- 38 righe `enti` del dataset sorgente non hanno un match diretto su nessuna `sezioni_cai` nota (problema di
  qualità dati nel prototipo RUNTS-CAI sorgente, non di questa integrazione) — restano fuori dal datapack
  attuale, da rivedere in un futuro refresh se il prototipo sorgente migliora il proprio matching.
