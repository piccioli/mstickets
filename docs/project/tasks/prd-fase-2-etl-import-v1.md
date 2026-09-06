# PRD: Fase 2 — Importazione dal v1 (ETL)

> Riferimento primario: `PRD-ORCHESTRATOR-V2.md` §11 (M11 — Importazione dal dump v1) e §14 (Roadmap,
> "Fase 2 — Importazione dal v1"). Questo documento scompone quella fase in user story implementabili
> singolarmente, per la conversione in `scripts/ralph/prd.json` (skill `ralph-skills:ralph`), seguendo la
> stessa numerazione usata per Fase 0 (`US-0xx`) e Fase 1 (`US-1xx`): questa fase usa `US-2xx`.

## 1. Introduzione/Overview

Orchestrator v2 ha oggi (fine Fase 1, v0.3.2) uno schema di database completo e un Ticketing core
funzionante, ma **nessun dato reale**: l'app gira solo sul seed di sviluppo. La Fase 2 costruisce la
procedura di importazione (ETL) che porta tutti i dati della v1 di produzione (Laravel Nova, ~30.000 righe
di codice, schema degradato) nel nuovo schema v2, in modo **ripetibile, idempotente e verificabile**
(D1/D10 del PRD). È un prerequisito per ogni fase successiva che debba lavorare su dati reali invece che
sul solo seed (Fase 3 email, Fase 4 rendicontazione, ecc. — per questo il PRD la colloca prima
dell'email).

Il codice vive interamente in `app/Import/` (oggi solo scheletro: cartelle `Stages/Mappers/Parsers/
Validation` vuote, `.gitkeep`), isolato ed eliminabile in blocco a cutover concluso (A-vincolo esplicito
del PRD, §4.3): nessuna classe di dominio deve dipendere da `app/Import/`.

## 2. Obiettivi

- Importare **tutte** le entità in scope (§3.1 del PRD) dal dump v1 allo schema v2, con id conservato dove
  richiesto (§5.1 punto 6) e mapping v1→v2 tracciato (`import_mappings`) dove l'id non è conservato.
- Rendere l'intera procedura **idempotente**: una seconda esecuzione consecutiva sullo stesso dump non
  duplica né corrompe nulla (P1 del PRD, criterio di accettazione esplicito della Fase 2).
- Produrre un **report di validazione** (`v1:validate`, §11.7) che confronti v1 e v2 su conteggi, integrità
  referenziale e derivati, e sia la condizione per dichiarare un import riuscito.
- Rendere **trasparenti** tutti i compromessi che l'ETL applica su dati v1 ambigui (P6): ogni fallback è
  dichiarato, contato, mai silenzioso.
- Fornire un'**anonimizzazione** obbligatoria per ogni ambiente non di produzione (§11.8) e una fixture
  ridotta e anonimizzata eseguita in CI, così che l'ETL sia coperto da test automatici senza dipendere da
  un dump reale non versionabile.

**Non obiettivi di questa fase** (rimandati esplicitamente): la sincronizzazione email reale (Fase 3), il
cutover di produzione vero e proprio (§11.9, Fase 7), qualunque modulo fuori scope (§3.2 del PRD — CRM,
Agile legacy, geografico, deadline, changelog, Google Calendar).

## 3. User Stories

Ogni story è una story Ralph nel senso di `scripts/ralph/prd.json` (`id`, `title`, `description`,
`acceptanceCriteria`): un PR/commit autocontenuto, verificato da test automatici Pest prima di passare
alla successiva. L'ordine riflette le dipendenze dichiarate tra stage in §11.4 del PRD (uno stage non può
girare prima di quelli da cui dipende).

### US-201: Scaffold del comando `v1:import` e registro degli stage

**Description:** As a developer, prima di scrivere un singolo stage mi serve l'infrastruttura che li
esegue in ordine, rispettando le dipendenze dichiarate, con le opzioni richieste dal PRD, così che ogni
stage successivo si limiti a registrarsi nel runner invece di reimplementare parsing opzioni/gestione
errori/audit.

**Acceptance Criteria:**
- [ ] `php artisan v1:import` esiste con le opzioni `--dry-run`, `--stage=<nome>`, `--from-stage=<nome>`,
  `--limit=N`, `--truncate` (con conferma interattiva, rifiutato fuori da un ambiente non-produzione),
  `--anonymize` (§11.2)
- [ ] Un contratto `App\Import\Stages\Contracts\ImportStage` (nome, dipendenze dichiarate, metodo
  `run(ImportContext $context): StageResult`) e un runner che risolve l'ordine di esecuzione dalle
  dipendenze dichiarate, segnalando errore esplicito (non un ordine arbitrario) se `--stage`/`--from-stage`
  richiede uno stage le cui dipendenze non sono state eseguite
  in questa sessione
- [ ] Ogni esecuzione crea/aggiorna una riga `import_runs` (§5.2: `started_at`, `finished_at`,
  `dump_label`, `stages` jsonb con righe lette/create/aggiornate/saltate/errori per stage, `status`,
  `is_dry_run`)
- [ ] `--dry-run` non scrive alcuna riga nelle tabelle di destinazione (verificato con un test che conta le
  righe prima/dopo su uno stage fittizio)
- [ ] Nessuno stage reale è ancora implementato in questa story (arrivano nelle successive): il test
  copre il runner con 2-3 stage fittizi di esempio (`tests/`), rimossi prima del merge o marcati come
  fixture di test isolate
- [ ] `composer analyse` (Larastan livello 6) e `vendor/bin/pint --test` puliti

### US-202: Stage `users` + `roles_permissions`

**Description:** As a developer, l'anagrafica utenti e i loro ruoli/permessi devono essere importati per
primi (ogni altro stage referenzia `users.id`), traducendo la colonna JSON `users.roles` del v1 nelle
tabelle Spatie già seedate in Fase 0.

**Acceptance Criteria:**
- [ ] Stage `users`: importa `users` con `id` conservato (§11.4 stage 1), mapping colonne per colonna (no
  `epic_id`/`project_id`/altre colonne fuori scope), email case-insensitive deduplicate e segnalate se in
  conflitto
- [ ] Stage `roles_permissions`: parse tollerante di `users.roles` (JSON in `varchar`) — ruolo riconosciuto
  → assegnato via Spatie; ruolo `editor` → **non** un ruolo, l'utente riceve i permessi diretti
  `documentation.create`/`documentation.update` (D14) e viene segnalato se `editor` era il suo unico ruolo;
  ruolo non riconosciuto → scartato + segnalato; utente senza ruoli → segnalato; valore non parsabile →
  nessun ruolo + segnalato
- [ ] `horizon.access`/`logs.access` **non** assegnati automaticamente: lo stage produce solo l'elenco dei
  developer esistenti nel report (assegnazione manuale successiva, fuori dall'ETL)
- [ ] Il seeder di ruoli/permessi (§9.2, già esistente da Fase 0) deve aver girato **prima**: lo stage
  fallisce con un errore esplicito (non un errore Spatie criptico) se un ruolo del catalogo enum non esiste
  ancora nel DB
- [ ] Idempotenza: rieseguire lo stage due volte sullo stesso dump non duplica utenti né righe
  `model_has_roles`/`model_has_permissions` (test dedicato: conteggi identici dopo la seconda esecuzione)
- [ ] Test con fixture di `users.roles` reali multiple: JSON valido con più ruoli, JSON con `editor`, valore
  non-JSON, valore vuoto/null

### US-203: Stage `organizations` + `organization_members`

**Description:** As a developer, importo enti e appartenenze utente↔organizzazione, propedeutici a
`activity_reports` (owner_kind = organization) e ai filtri ticket per organizzazione del richiedente.

**Acceptance Criteria:**
- [ ] Stage `organizations`: `id` conservato, mapping diretto
- [ ] Stage `organization_members`: pivot `(organization_id, user_id)`, idempotente su quella chiave
- [ ] Dipende dichiaratamente da `users` (US-202)
- [ ] Test di idempotenza (doppia esecuzione, conteggi invariati) e di orfani (riga v1 che referenzia un
  utente/organizzazione inesistente → segnalata nel report, non un crash)

### US-204: Stage `documentation` + `tags`

**Description:** As a developer, importo pagine di documentazione e tag prima dei ticket, perché
`tags.documentation_id` e `ticket_tag` dipendono da entrambe.

**Acceptance Criteria:**
- [ ] Stage `documentation`: `documentations` → `documentation_pages`, `id` conservato, **nessuna**
  relazione `creator()` verso colonna inesistente riprodotta (§16, anti-pattern esplicito)
- [ ] Stage `tags`: `id` conservato; il morph polimorfico v1 (`taggables`/`tags.taggable_*`) collassa a tag
  semplici, **tranne** il link a `Documentation` che diventa la FK esplicita `documentation_id` (§3.2,
  regola per l'ETL); conta e segnala quanti `tags` avevano un `taggable_type` diverso da `Documentation`
  (perdono il link, non l'esistenza del tag)
- [ ] Slug generato con unicità garantita (suffisso numerico sui duplicati) — nota: il ricalcolo
  definitivo/idempotente dello slug è delegato allo stage `derive` (US-215); questo stage può produrre uno
  slug provvisorio purché non violi il vincolo unique
- [ ] Test di idempotenza e test sul conteggio dei tag con link perso

### US-205: Stage `tickets` — mapping principale

**Description:** As a developer, importo l'entità centrale del sistema (`stories` → `tickets`),
applicando le normalizzazioni di tipo/priorità/stato e derivando le colonne che non esistono nel v1
(`status_changed_at`, `previous_status`).

**Acceptance Criteria:**
- [ ] `id` conservato; colonne v1 non portate (`epic_id`, `project_id`, `history_log`,
  `pull_request_link`, `customer_request`) esplicitamente escluse dal mapping
- [ ] Tipo: mapping case-insensitive e tollerante agli spazi (`Bug`→`bug`, `Feature`→`feature`, `Help
  desk`/`Helpdesk`/`help desk`→`helpdesk`, `Scrum`/`scrum`→`scrum`); default `helpdesk` + segnalazione per
  valori non riconosciuti
- [ ] Priorità: `1`→`low`, `2`→`medium`, `3`→`high`; altri valori → `low` + segnalazione
- [ ] `status_changed_at`: derivato dal `story_logs` più recente con cambio di stato per quel ticket;
  fallback `stories.updated_at` se assente; **conta e segnala** quanti ticket usano il fallback
- [ ] `previous_status`: per ticket in `waiting`/`problem`, risale ai log fino al primo stato diverso da
  `waiting`/`problem`; fallback `new`; conta e segnala i fallback (nota: dopo l'import la colonna è
  mantenuta dall'applicazione, questa ricostruzione una-tantum non si ripete)
- [ ] `worked_minutes` importato come `0` in questa story (il valore reale arriva dallo stage `derive`,
  US-215 — evita di calcolare due volte la stessa cosa in stage diversi)
- [ ] Dipende da `users` (requester/assignee/tester), `tags` non ancora collegato (arriva in US-207)
- [ ] Test di idempotenza; test sui casi limite di tipo/priorità con valori fuori enum noti dal report
  `v1:inspect` (`storage/app/import/inspect-20260726_101916.md`, già prodotto in Fase 0 — usarlo come
  fonte dei valori reali distinti da coprire nei test, non inventarli)

### US-206: Stage `ticket_hierarchy`

**Description:** As a developer, ricostruisco la gerarchia padre/figlio dei ticket da due fonti v1
potenzialmente in conflitto (`stories.parent_id` e la tabella pivot `story_story`, eliminata in v2),
rispettando il vincolo di profondità massima 1 di v2.

**Acceptance Criteria:**
- [ ] `stories.parent_id` è la fonte primaria; le righe di `story_story` non riflesse nella colonna sono
  applicate solo se non creano conflitto
- [ ] Un figlio con due padri diversi tra le due fonti → si mantiene `parent_id`, si segnala il conflitto
  (mai un merge silenzioso)
- [ ] Una gerarchia che violerebbe la profondità massima 1 → viene appiattita (il nipote diventa figlio
  diretto del nonno, o la scelta equivalente dichiarata nel PR) e segnalata nel report
- [ ] Dipende da `tickets` (US-205)
- [ ] Test con fixture che riproducono: gerarchia coerente a 1 livello, conflitto padre singolo vs pivot,
  gerarchia a 2+ livelli da appiattire

### US-207: Stage `ticket_tags` + `ticket_participants`

**Description:** As a developer, importo le associazioni ticket↔tag e i partecipanti espliciti, entrambe
pivot semplici senza logica di trasformazione.

**Acceptance Criteria:**
- [ ] Stage `ticket_tags`: `taggables` (solo lato Story) → `ticket_tag`, idempotente su
  `(ticket_id, tag_id)`
- [ ] Stage `ticket_participants`: `story_participants` → `ticket_participants`, idempotente su
  `(ticket_id, user_id)`; il report segnala il conteggio (§11.3 nota: atteso vicino a zero, §6.1.7 del PRD
  principale)
- [ ] Dipende da `tickets` (US-205) e `tags` (US-204)
- [ ] Test di idempotenza e test su righe orfane (ticket o tag inesistente lato v1)

### US-208: Stage `ticket_logs`

**Description:** As a developer, traduco il JSON libero `changes` dei log v1 nelle colonne esplicite di
v2 (`event`, `from_status`, `to_status`, `changes`), che è la correzione strutturale più importante
rispetto al v1 (§5.2 nota).

**Acceptance Criteria:**
- [ ] Presenza di `status` nel JSON → `event = status_changed`, `to_status` = valore, `from_status` = stato
  del log precedente dello stesso ticket (null se non ricostruibile)
- [ ] Presenza di `user_id` (senza `status`) → `event = assigned`
- [ ] Altre chiavi → `event = updated` con il diff in `changes` (mai il corpo di `description`, solo il
  marker previsto dal PRD)
- [ ] `viewed_at` del v1 → `occurred_at`; `user_id` mancante → utente di sistema (§6.2.1, `User::system()`
  già esistente da Fase 1)
- [ ] I log con **sola** chiave `watch` **non** diventano `ticket_logs`: esclusi da questo stage (vanno
  allo stage `ticket_views`, US-209)
- [ ] Idempotenza tramite `import_mappings` su `story_logs.id` (§11.4)
- [ ] Dipende da `tickets` (US-205), `users` (US-202)
- [ ] Test con fixture reali di `story_logs.changes` per ciascuna delle 4 casistiche sopra

### US-209: Stage `ticket_views`

**Description:** As a developer, separo la tracciabilità delle visualizzazioni (mescolata nel v1 dentro
`story_logs` con chiave `watch`) nella tabella dedicata `ticket_views` di v2.

**Acceptance Criteria:**
- [ ] I `story_logs` con `changes->watch` diventano righe `ticket_views` (`ticket_id`, `user_id`,
  `viewed_on`, `last_viewed_at`, `view_count`)
- [ ] Idempotente sul vincolo unique `(ticket_id, user_id, viewed_on)` già esistente da Fase 0/1: righe
  ripetute nello stesso giorno si aggregano in `view_count`, non duplicano
- [ ] Dipende da `tickets`, `users`; consuma la stessa lettura sorgente di `ticket_logs` (US-208) — chiarire
  nel codice che sono due stage letti dalla stessa tabella `story_logs` ma filtrati in modo mutuamente
  esclusivo (nessun log finisce in entrambe le destinazioni)
- [ ] Test di idempotenza e test che verifica la mutua esclusione con US-208 (stesso set di `story_logs` in
  input, zero sovrapposizioni in output)

### US-210: Stage `ticket_messages` — parser della conversazione (il più delicato)

**Description:** As a developer, scompongo l'HTML accumulato di `stories.customer_request` nei messaggi
strutturati di v2, con un fallback esplicito quando il parsing non è affidabile — è il punto più delicato
dell'intero import (§11.5) e merita una story dedicata con fixture reali, non solo casi sintetici.

**Acceptance Criteria:**
- [ ] Parser che scompone i blocchi HTML prepesi del v1, ricavando per ciascuno autore (da nome/stile),
  timestamp se presente, corpo; l'ordine è **invertito** rispetto al v1 (v1 prepende, v2 è cronologico)
- [ ] Messaggi ricostruiti: `is_legacy_import = true`, `channel = email` se identificabile altrimenti
  `system`, `visibility = public`
- [ ] Autore non ricavabile → `author_id = null`, messaggio attribuito a "storico importato"
  (`author_email` valorizzata se disponibile)
- [ ] `posted_at` non ricavabile → distribuzione monotona tra `created_at`/`updated_at` del ticket
  (l'ordine relativo resta coerente); conta e segnala
- [ ] **Se il parsing fallisce**: fallback a un unico messaggio con l'HTML integrale sanitizzato,
  `is_legacy_import = true`, `posted_at = stories.created_at` — **nessuna perdita di contenuto**
- [ ] HTML **sempre** sanitizzato con `TicketMessageSanitizer` già esistente da Fase 1 (US-106): riuso
  diretto, nessuna seconda implementazione di sanitizzazione nell'ETL
- [ ] Idempotenza tramite `import_mappings` su `(story_id, indice, hash)` (§11.4 stage 13)
- [ ] Il report conta: messaggi ricostruiti, ticket con fallback a blocco unico, ticket senza conversazione
- [ ] Test con **fixture HTML reali** estratte da un campione del dump v1 (non solo HTML sintetico scritto a
  mano): almeno un caso di scomposizione riuscita multi-messaggio, un caso di fallback a blocco unico, un
  caso senza `customer_request`, un caso con tentativo di XSS nel corpo (verifica che la sanitizzazione lo
  neutralizzi)

### US-211: Stage `ticket_attachments`

**Description:** As a developer, sposto i media v1 (attaccati alla `Story`) sui messaggi v2, verificando
che i file esistano fisicamente prima di considerarli importati.

**Acceptance Criteria:**
- [ ] Ogni media v1 viene attaccato al **primo messaggio legacy** del ticket corrispondente (creato da
  US-210); se il ticket non ha messaggi, crea un messaggio di sistema "Allegati importati" e allega lì
- [ ] Verifica l'esistenza fisica del file sorgente: media orfani (riga DB senza file su disco) sono
  **segnalati nel report, non ignorati** e non contati come importati con successo
- [ ] I file sono copiati sul disco privato dedicato (`ticket-attachments`, già esistente da Fase 1 US-107),
  non su un disco pubblico
- [ ] Idempotenza tramite `media.uuid` (§11.4 stage 14): rieseguire lo stage non duplica gli allegati
- [ ] Dipende da `ticket_messages` (US-210)
- [ ] Test con almeno un media presente e uno orfano (file mancante simulato) nella fixture

### US-212: Stage `activity_reports` + `activity_report_tickets`

**Description:** As a developer, importo i report di attività storici e le loro associazioni ai ticket,
rispettando il vincolo che ogni report appartiene esclusivamente a un utente **o** a un'organizzazione
(mai entrambi).

**Acceptance Criteria:**
- [ ] `id` conservato; `owner_kind`/`owner_user_id`/`owner_organization_id` valorizzati coerentemente (il
  v1 aveva `customer_id` che in realtà puntava a `users`, §0.3)
- [ ] Rispetta il vincolo CHECK già esistente in v2 (Fase 0, `activity_reports_owner_check`): un record che
  lo violerebbe è segnalato e scartato, non causa un errore SQL non gestito che interrompe l'intero stage
- [ ] Stage `activity_report_tickets`: pivot `(report_id, ticket_id)`, idempotente
- [ ] Dipende da `users`, `organizations`, `tickets`
- [ ] Test di idempotenza e test sul caso limite (report v1 con dati ambigui su owner)

### US-213: Stage `fundraising_opportunities` + `fundraising_scores`

**Description:** As a developer, importo le opportunità di fundraising e normalizzo le 34 colonne
`evaluation_*` del v1 in righe di `fundraising_evaluation_scores`, un mapping non banale che richiede il
catalogo criteri già definito nell'enum PHP (§6.6.2, esistente).

**Acceptance Criteria:**
- [ ] `fundraising_opportunities`: `id` conservato, mapping diretto delle colonne
- [ ] Ogni colonna `evaluation_*_score` non nulla → riga `fundraising_evaluation_scores` con la
  `criterion_key` corrispondente (mappa §6.6.2); le colonne `evaluation_criterion_*_description` diventano
  `notes` della riga omonima
- [ ] Punteggi fuori range → **clampati** al range del catalogo criteri e segnalati (conteggio nel report)
- [ ] I totali (`evaluation_positive_total`/`negative_total`/`total`) **non** si importano da v1: restano
  vuoti qui, ricalcolati dallo stage `derive` (US-215) e confrontati col v1 nel report di validazione
- [ ] Idempotenza su `(opportunity_id, criterion_key)`
- [ ] Dipende da `users` (created_by/responsible_user_id/evaluated_by)
- [ ] Test con fixture che copre: punteggio nel range, punteggio fuori range (verifica clamp), colonna
  `evaluation_*` nulla (nessuna riga generata)

### US-214: Stage `fundraising_projects` + `fundraising_partners`

**Description:** As a developer, importo i progetti candidati collegati alle opportunità e i loro
partner, completando il modulo fundraising.

**Acceptance Criteria:**
- [ ] `fundraising_projects`: `id` conservato, mapping diretto, collegato a `fundraising_opportunities`
  (US-213)
- [ ] `fundraising_project_partners`: pivot `(project_id, user_id)`, idempotente
- [ ] Dipende da `fundraising_opportunities` (US-213), `users`
- [ ] Test di idempotenza

### US-215: Stage `derive` — ricalcolo dei valori derivati

**Description:** As a developer, dopo che tutte le entità primarie sono importate, ricalcolo da zero ogni
valore derivato di v2 (mai importato direttamente da v1), garantendo che sia sempre rigenerabile (A9 del
PRD) e riallineo le sequenze PostgreSQL per gli id conservati.

**Acceptance Criteria:**
- [ ] `tickets.released_at`/`done_at` mancanti pur essendo il ticket in stato `released`/`done` →
  ricostruiti dai `ticket_logs` importati (US-208)
- [ ] `tickets.worked_minutes` ricalcolato con `WorkedTimeCalculator`/`RecalculateWorkedTime` **già
  esistenti da Fase 1** (riuso diretto, nessuna seconda implementazione nell'ETL) per l'intero storico
  importato
- [ ] `ticket_work_logs` popolato per l'intero storico (stesso riuso di Fase 1)
- [ ] Totali di valutazione fundraising (`evaluation_positive_total`/`negative_total`/`total`) ricalcolati
  dalle righe `fundraising_evaluation_scores` (US-213) secondo la stessa formula del catalogo criteri
- [ ] Slug univoci definitivi per `tags` e `documentation_pages` (suffisso numerico sui duplicati)
- [ ] `email_threads` generati per i ticket con conversazione importata (US-210), così che il threading
  funzioni anche su ticket storici quando il cliente risponde a una vecchia email (prerequisito per Fase 3)
- [ ] **Riallineamento esplicito delle sequenze PostgreSQL** per ogni tabella con id conservato (users,
  tickets, tags, documentation_pages, organizations, activity_reports, fundraising_*): un passo dedicato e
  verificato (test che inserisce una nuova riga applicativa dopo il riallineamento e verifica che non vada
  in conflitto di chiave primaria), non un effetto collaterale implicito di un'altra operazione
- [ ] Idempotente per definizione: ogni derivato è ricalcolato da zero (cancella e ricrea, mai un upsert
  differenziale) — rieseguire lo stage due volte produce lo stesso risultato esatto
- [ ] Test che confronta i totali fundraising ricalcolati con un dataset noto (numeri attesi calcolati a
  mano nel test)

### US-216: Comando `v1:validate` e report di validazione

**Description:** As a developer/PM, ho bisogno di un report unico che confronti v1 e v2 su conteggi,
integrità referenziale, derivati e compromessi applicati, per poter dichiarare un import riuscito con
evidenza verificabile invece che a sensazione.

**Acceptance Criteria:**
- [ ] `php artisan v1:validate` produce un report salvato in `storage/app/import/validate-<timestamp>.md`
  (stesso pattern di `v1:inspect`, Fase 0) e un riepilogo leggibile anche nell'amministrazione (§8.4, se
  già esiste una sezione import in questa fase; altrimenti un semplice output testuale sufficiente per
  questa story — l'integrazione UI Filament dedicata non è un requisito bloccante qui)
- [ ] **Conteggi a confronto**: per ogni entità con id conservato, conteggio v1 vs v2 con Δ atteso (0 per la
  maggior parte; per `ticket_logs` l'atteso è `n_v1 − n_watch` perché i `watch` migrano a `ticket_views`)
- [ ] **Controlli di integrità**: orfani per ogni FK in scope, unicità violate, valori enum fuori catalogo,
  ticket senza richiedente, messaggi senza ticket, media mancanti sul disco — ciascuno con conteggio
  esplicito, zero è il valore atteso per dichiarare il controllo superato
- [ ] **Confronto dei derivati**: ore lavorate per ticket v1 vs v2, con distribuzione degli scostamenti e
  l'elenco dei ticket oltre la **tolleranza del ±5% per ticket** (assunzione operativa per Q6 del PRD,
  documentata come tale — vedi Domande Aperte); totali di valutazione fundraising v1 vs v2, che **devono
  coincidere esattamente**
- [ ] **Compromessi applicati**, con i conteggi già raccolti dagli stage precedenti: ticket con
  `status_changed_at`/`previous_status` da fallback, conversazioni con fallback a blocco unico, messaggi
  senza autore, messaggi con data stimata, ruoli scartati, tipi normalizzati per default, punteggi
  clampati, conflitti di gerarchia, media orfani
- [ ] **Verifica di idempotenza esplicita**: il comando (o un comando/test dedicato collegato) esegue
  `v1:import` due volte consecutive sullo stesso dump e verifica che la seconda esecuzione produca zero
  righe create/aggiornate (solo "saltate") su ogni stage — è il criterio di accettazione esplicito della
  Fase 2 ("una seconda esecuzione consecutiva non modifica nulla")
- [ ] Il comando esce con **status di errore** se un qualunque controllo di integrità fallisce o se i
  conteggi delle entità con id conservato non coincidono, così che sia usabile come gate in CI

### US-217: Anonimizzazione (`--anonymize`, §11.8)

**Description:** As a developer, quando importo dati reali in un ambiente non di produzione (sviluppo,
staging, CI), devo poter sostituire nomi/email/corpi dei messaggi con dati fittizi che **preservano
relazioni e distribuzione**, così da poter lavorare su un dataset realistico senza esporre dati reali dei
clienti.

**Acceptance Criteria:**
- [ ] `--anonymize` su `v1:import` sostituisce, per ogni utente: nome, email (dominio di test, mai un
  dominio reale), e per ogni messaggio: il corpo (testo fittizio della stessa lunghezza approssimativa,
  mantenendo eventuali tag HTML strutturali già sanitizzati) — stesso utente v1 → stessa identità fittizia
  v2 in tutta l'esecuzione (deterministico per id, non casuale ad ogni riga, altrimenti due righe dello
  stesso utente avrebbero email diverse)
- [ ] Le relazioni (chi ha scritto cosa, a chi è assegnato cosa) restano invariate: solo i valori
  "di superficie" cambiano
- [ ] Un guard applicativo (non solo un vincolo dell'ETL) impedisce l'invio di email verso indirizzi reali
  quando `APP_ENV !== 'production'`: allowlist di domini di test in configurazione — verificato con un test
  che tenta un invio verso un dominio non in allowlist e verifica che venga bloccato/reindirizzato
- [ ] `--anonymize` è **obbligatorio** (documentato, non solo disponibile) per ogni ambiente non di
  produzione nel README/CLAUDE.md
- [ ] Il dump v1 e ogni file di import non vengono mai committati: `.gitignore` già copre `v1dumps/` (Fase
  0) — verificare che copra anche eventuali nuovi path di lavoro introdotti da questa fase
  (`storage/app/import/` già eccettuato selettivamente da Fase 0 per i soli report `.md`, non per dump/
  dati grezzi)
- [ ] Test che, sullo stesso input, produce output anonimizzato deterministico a ogni esecuzione (stesso
  utente v1 → stessa email fittizia), diverso dall'originale, con le relazioni intatte

### US-218: Dump di test ridotto per CI

**Description:** As a developer, la CI deve poter eseguire l'intera pipeline ETL senza un dump di
produzione reale (troppo grande, sensibile, non versionabile): serve una fixture ridotta ma
rappresentativa di ogni caso limite già noto dal report `v1:inspect` di Fase 0.

**Acceptance Criteria:**
- [ ] Fixture SQL ridotta (poche decine di righe per tabella, non l'intero dump) versionata nel repository
  (a differenza del dump reale), **già anonimizzata alla creazione** — non generata anonimizzando un dump
  reale a ogni run di CI
- [ ] Copre esplicitamente i casi limite già documentati in `storage/app/import/
  inspect-20260726_101916.md` (Fase 0): almeno un valore di `status`/`type`/`priority` fuori enum, un
  `users.roles` con `editor`, un `customer_request` non parsabile, un conflitto di gerarchia
  `story_story`/`parent_id`, un media orfano, un'email duplicata a meno del case
- [ ] Un job CI dedicato esegue `v1:import` (su questa fixture) → `v1:validate` → verifica che il comando
  esca con successo E che il report non segnali controlli di integrità falliti (i "compromessi" attesi
  sulla fixture sono invece l'obiettivo esplicito del test: verificare che vengano rilevati e contati, non
  che siano zero)
- [ ] Il job CI esegue l'import **due volte** per verificare l'idempotenza direttamente in pipeline (non
  solo in un test locale)
- [ ] `composer.json`/CI documentano dove si trova la fixture e come rigenerarla se lo schema v1 cambia

### US-219: Checkpoint di fine fase — import su dump reale e revisione col committente

**Description:** As a product owner, prima di dichiarare conclusa la Fase 2 voglio vedere l'ETL girare
davvero su un dump di produzione reale e rivedere il report dei compromessi, coerentemente con il
checkpoint obbligatorio già usato per la Fase 0 (§14).

**Acceptance Criteria:**
- [ ] `v1:import` eseguito sul dump reale più recente disponibile (`v1dumps/
  production_dump_20260726_101158.sql.gz`, già usato per `v1:inspect` in Fase 0) contro `db_legacy`, con
  esito `v1:validate` allegato alla PR di chiusura fase
- [ ] Seconda esecuzione consecutiva sullo stesso dump reale dimostra idempotenza (conteggi "creati"/
  "aggiornati" a zero, solo "saltati") — evidenza allegata, non solo asserita
- [ ] I totali di valutazione fundraising v2 coincidono esattamente con quelli calcolati dal v1 sullo stesso
  dump
- [ ] Gli scostamenti sulle ore lavorate sono entro la tolleranza concordata (±5% per ticket, US-216); i
  ticket oltre soglia sono elencati esplicitamente, non nascosti
- [ ] Il report dei compromessi (ruoli scartati, fallback di parsing, conflitti di gerarchia, ecc.) è
  presentato e **rivisto esplicitamente col committente** prima di considerare la fase chiusa (stesso
  pattern del "punto di controllo obbligatorio" di Fase 0) — questa story si considera completa solo dopo
  la conferma esplicita, non al solo superamento tecnico di `v1:validate`
- [ ] Manifest di collaudo (`docs/collaudo/fase-2.php`) esteso con i topic della Fase 2, verificato da
  `php artisan collaudo:verify-manifest 2` (processo obbligatorio già stabilito, vedi CLAUDE.md sezione
  "Processo di collaudo")

## 4. Requisiti funzionali

1. Il sistema deve fornire `php artisan v1:import` con le opzioni `--dry-run`, `--stage`, `--from-stage`,
   `--limit`, `--truncate`, `--anonymize` (§11.2 del PRD).
2. Il sistema deve eseguire i 21 stage di §11.4 nell'ordine di dipendenza dichiarato, ciascuno idempotente
   sulla propria chiave di riconciliazione.
3. Il sistema deve tradurre `users.roles` (JSON in varchar) nelle tabelle Spatie, gestendo esplicitamente
   il caso `editor` (D14) e i permessi `horizon.access`/`logs.access` (non assegnati automaticamente).
4. Il sistema deve scomporre `stories.customer_request` (HTML accumulato) in `ticket_messages` cronologici
   distinti, con fallback a un messaggio unico quando il parsing fallisce, senza mai perdere contenuto.
5. Il sistema deve ricalcolare (mai importare direttamente) tutti i valori derivati: ore lavorate, totali
   fundraising, slug, `email_threads`, `released_at`/`done_at` mancanti.
6. Il sistema deve riallineare le sequenze PostgreSQL per ogni tabella con id conservato, a fine import.
7. Il sistema deve fornire `php artisan v1:validate`, che produce un report di conteggi/integrità/
   derivati/compromessi e fallisce esplicitamente (exit code ≠ 0) se un controllo non passa.
8. Il sistema deve supportare `--anonymize`, sostituendo dati identificativi in modo deterministico e
   preservando relazioni e distribuzione, obbligatorio per ogni ambiente non di produzione.
9. Il sistema deve eseguire l'intera pipeline in CI su una fixture ridotta e già anonimizzata, verificando
   sia il successo della validazione sia l'idempotenza su due esecuzioni consecutive.
10. Ogni compromesso applicato su dati v1 ambigui deve essere dichiarato nel codice, contato nel report,
    mai applicato silenziosamente.

## 5. Non-Goals (fuori scope)

- Nessuna importazione dei moduli fuori scope (§3.2 del PRD: CRM/Preventivi, Agile legacy, geografico,
  Deadline, Changelog, Google Calendar) — dati non toccati, dump archiviato come riferimento storico.
- Nessun cutover di produzione reale (finestra di manutenzione, redirect casella email, ecc.): quello è
  §11.9 / Fase 7.
- Nessuna sincronizzazione permanente v1↔v2: l'ETL è eseguito consapevolmente, non un ponte continuo (D2/D11
  del PRD).
- Nessuna integrazione UI Filament dedicata per lanciare l'ETL da pannello: resta un'operazione da CLI in
  questa fase (un'eventuale UI di amministrazione import, se richiesta, è un punto di estensione futuro,
  non incluso qui).
- Nessuna modifica allo schema v2 esistente: la Fase 2 legge/scrive nello schema già definito in Fase 0,
  non lo modifica (salvo eventuali indici di performance sull'ETL stesso, se necessari, da valutare in fase
  di implementazione).

## 6. Considerazioni tecniche

- Tutto il codice in `app/Import/` (Stages/Mappers/Parsers/Validation), isolato dal resto del dominio (§4.3
  del PRD): nessuna classe `App\Domain\*` deve dipendere da `App\Import\*`. Il contrario è ammesso e atteso
  (l'ETL riusa `WorkedTimeCalculator`, `TicketMessageSanitizer`, `User::system()`, ecc. già scritti in Fase
  0/1).
- Connessione dedicata `legacy` verso `db_legacy` (già configurata in Fase 0, §"ETL / dump v1" di
  CLAUDE.md): query builder (`DB::connection('legacy')->table(...)`), mai Eloquent sulle tabelle v1.
- Ogni stage deve essere eseguibile singolarmente (`--stage=<nome>`) per un ciclo di sviluppo rapido, oltre
  che come parte della pipeline completa.
- Le fixture di test per il parser della conversazione (US-210) e per la fixture CI ridotta (US-218) vanno
  costruite a partire da un campione reale del dump già scaricato (`v1dumps/
  production_dump_20260726_101158.sql.gz`), non inventate da zero, per catturare i casi limite reali già
  emersi da `v1:inspect`.
- La scelta della libreria/tecnica di anonimizzazione (Faker con seed deterministico per id, hash, o
  equivalente) è delegata a chi implementa US-217: nessun vincolo di libreria specifica in questo PRD.

## 7. Metriche di successo

- `v1:import` + `v1:validate` sul dump reale più recente: **zero** controlli di integrità falliti, **zero**
  Δ sui conteggi delle entità con id conservato, totali fundraising **esattamente** coincidenti.
- Seconda esecuzione consecutiva sullo stesso dump: **zero** righe create/aggiornate su ogni stage (solo
  "saltate") — idempotenza dimostrata con evidenza allegata alla PR.
- Scostamento sulle ore lavorate entro **±5% per ticket** su almeno il 95% dei ticket con storico
  `progress` (soglia di riferimento; il report elenca esplicitamente ogni eccezione oltre soglia).
- Pipeline CI (fixture ridotta) verde su ogni PR di questa fase, con idempotenza verificata in automatico.
- Checkpoint col committente (US-219) confermato esplicitamente prima di considerare la fase chiusa.

## 8. Domande aperte

- **Q6 (tolleranza ore lavorate)**: questo PRD assume **±5% per ticket** come soglia operativa di
  riferimento (scelta in fase di stesura, non ancora validata su un confronto reale v1/v2 — §11.7 la
  chiama esplicitamente "tolleranza concordata"). Da confermare o correggere **dopo** aver visto la
  distribuzione reale degli scostamenti prodotta da US-216 sul dump reale: se la maggioranza dei ticket
  eccede il 5% per una causa sistemica (es. la scelta di unificazione dell'algoritmo Q15 di Fase 1, già
  nota per produrre numeri diversi dal v1), la soglia va rivista con il committente prima del checkpoint
  US-219, non irrigidita a priori.
- **Integrazione UI Filament per l'amministrazione import** (§8.4 del PRD la cita): questo PRD la considera
  fuori scope per la Fase 2 (resta CLI-only); da confermare se il committente la vuole già in questa fase o
  rimandata.
- **Permessi `horizon.access`/`logs.access` per i developer esistenti** (US-202): l'ETL produce solo
  l'elenco, ma **chi** decide a chi concederli e **quando** (durante l'ETL con conferma interattiva, o come
  passo manuale separato dopo l'import) non è specificato — da chiarire prima di US-202.
- **Rigenerazione della fixture CI ridotta** (US-218) se lo schema v1 cambia in futuro: questo PRD non
  definisce un processo di aggiornamento automatico, solo la fixture iniziale.
