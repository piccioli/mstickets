# PRD: Seeding con dati reali (ETL) per locale e UAT

> Riferimento: `docs/superpowers/specs/2026-08-02-etl-real-data-seeding-design.md` (design già approvato dal
> committente — le decisioni lì prese non vanno riaperte). Lavoro trasversale tra la Fase 2 (ETL) e la Fase 3
> (email) del roadmap principale, non una fase del PRD-ORCHESTRATOR-V2.md: le story usano il prefisso
> `US-R0x` per non collidere con la numerazione `US-3xx` che la vera Fase 3 userà.

## 1. Introduzione/Overview

Locale (`make setup`) e UAT (deploy automatico su push a `develop`) oggi popolano il database con dati
fittizi (`DevelopmentSeeder`, `UatSeeder`). Il committente vuole che entrambi riflettano invece la
situazione reale dell'ultimo dump v1 disponibile, importato via `v1:import --anonymize` (già esistente,
Fase 2) — nessun dato fittizio residuo.

## 2. Obiettivi

- Locale e UAT partono sempre da dati reali (anonimizzati) importati via ETL, non da un seed fittizio.
- Convenzione unica e condivisa (`v1dumps/latest.sql`) per "qual è il dump da usare", identica su entrambi
  gli ambienti.
- Nessun hash di password reale v1 replicato fuori produzione.
- Nessuna automazione recupera da sola dump/media da produzione: resta sempre un passo umano esplicito.
- `DevelopmentSeeder`/`UatSeeder` rimossi per intero (nessun codice morto).

## 3. User Stories

### US-R01: Reset password in `Anonymizer` quando `--anonymize` è attivo
**Description:** As a developer, quando importo dati con `--anonymize` in un ambiente non di produzione, non
voglio che l'hash reale della password v1 di nessun utente finisca mai fuori produzione.

**Acceptance Criteria:**
- [ ] Con `--anonymize` attivo, la password di ogni utente importato viene sovrascritta con l'hash Laravel
  di una password fissa nota (`'password'`), mai l'hash v1 copiato as-is
- [ ] Senza `--anonymize`, la password resta quella importata da v1, invariata (comportamento identico a
  oggi — nessuna regressione sul percorso di cutover reale in produzione)
- [ ] **Idempotenza**: la password fa parte SOLO dell'array di `insert` per un utente nuovo; su un utente
  già esistente (path di `update`/diff), la password NON viene ricontrollata né riscritta a ogni
  riesecuzione — altrimenti, essendo l'hash bcrypt salato casualmente a ogni chiamata, ogni riesecuzione con
  `--anonymize` produrrebbe un hash diverso e verrebbe segnalata come "utente aggiornato" pur non essendo
  cambiato nulla di sostanziale (stesso principio già consolidato in questo stage per altri campi
  one-time-derived, vedi `orchestrator/CLAUDE.md` sezione ETL — US-205/US-212)
- [ ] Test: un utente importato con `--anonymize` supera `Hash::check('password', $user->password)`; un
  utente importato senza `--anonymize` ha la password v1 originale (non l'hash fisso); una seconda
  esecuzione con `--anonymize` non produce un `update` sulla sola password di un utente già importato
- [ ] Typecheck passes
- [ ] Tests pass

### US-R02: Convenzione `v1dumps/latest.sql` + `make setup` con ETL reale
**Description:** As a developer, voglio che `make setup` popoli il mio ambiente locale con i dati reali
dell'ultimo dump disponibile, fallendo con un messaggio chiaro se il dump non è presente, invece di un seed
fittizio.

**Acceptance Criteria:**
- [ ] Documentata (README.md e `orchestrator/CLAUDE.md`) la convenzione `v1dumps/latest.sql`: un umano con
  accesso SSH a produzione la mantiene aggiornata (symlink o copia) puntando al dump reale più recente
  scaricato con `bin/load-v1-dump`; nessuno script la aggiorna da solo
- [ ] `make setup` fallisce con un messaggio esplicito (spiega come ottenere il dump) se
  `v1dumps/latest.sql` non esiste, PRIMA di eseguire la sequenza ETL — non un fallback silenzioso a dati
  fittizi
- [ ] `make setup` porta su anche `db_legacy` (oggi richiede `--profile etl`/`make etl-up` a parte):
  incorporato nella sequenza del target, non più un passo manuale separato per questo flusso
- [ ] `make setup` carica `v1dumps/latest.sql` in `db_legacy` con `bin/load-v1-dump` prima di eseguire
  l'ETL
- [ ] Sequenza finale: `migrate --force` → `db:seed --class=RolePermissionSeeder --force` →
  `php artisan v1:import --anonymize` (nessun `--truncate`)
- [ ] Gli allegati restano best-effort: se `storage/app/v1-media/` è vuota, il target NON fallisce (l'ETL
  segnala i media come compromesso, comportamento già esistente di `TicketAttachmentsStage`)
- [ ] A fine setup, il target stampa un promemoria: la password di tutti gli utenti importati con
  `--anonymize` è `'password'`; per il login, individuare l'email anonimizzata di un utente reale noto con
  una query diretta su `users` (id conservato dal v1) o consultare i manifest di collaudo aggiornati dal
  committente — nessun utente sintetico con credenziali fisse viene creato da questo target
- [ ] Verificato eseguendo davvero `make setup` da zero (dump reale già presente in questo repo) e
  confermando che l'ETL gira senza errori fino in fondo — non solo ispezionando il `Makefile`
- [ ] Typecheck/Tests pass (nessuna regressione sulla suite esistente)

### US-R03: Rimozione `DevelopmentSeeder`
**Description:** As a developer, non voglio codice morto: `DevelopmentSeeder` non ha più un punto di
ingresso nel flusso reale dopo US-R02.

**Acceptance Criteria:**
- [ ] Rimossi `database/seeders/DevelopmentSeeder.php` e il suo test
- [ ] `DatabaseSeeder.php` non richiama più `DevelopmentSeeder` (solo `RolePermissionSeeder`)
- [ ] Nessun riferimento residuo a `DevelopmentSeeder` nel codice applicativo o nella documentazione tecnica
  (verificato con grep)
- [ ] Typecheck/Tests pass

### US-R04: Rimozione `UatSeeder`
**Description:** As a developer, `UatSeeder` non serve più dopo che il deploy UAT userà l'ETL reale
(US-R06).

**Acceptance Criteria:**
- [ ] Rimossi `database/seeders/UatSeeder.php` e il suo test
- [ ] **Fuori scope esplicito**: NON toccare `docs/collaudo/*` — il committente li aggiorna direttamente,
  non riferimenti da sistemare qui
- [ ] Se `remote-deploy.sh`/il workflow di deploy referenziano ancora `UatSeeder` in questo momento della
  sequenza (US-R06 non ancora eseguita), è accettabile lasciarli momentaneamente così: verranno aggiornati
  nella story successiva, non in questa
- [ ] Typecheck/Tests pass

### US-R05: Infrastruttura UAT per l'ETL reale (`db_legacy` + volume allegati)
**Description:** As a developer, l'ambiente UAT deve poter eseguire l'ETL reale ad ogni deploy: serve una
sorgente `db_legacy` e un percorso per gli allegati, come già esiste in locale.

**Acceptance Criteria:**
- [ ] `docker-compose.uat.yml`: nuovo servizio `db_legacy` (`postgres:16-alpine`), **sempre attivo** (non
  dietro un profilo come in locale — qui serve a ogni deploy), volume dedicato `db_legacy_data`,
  healthcheck (`pg_isready`), `mem_limit` esplicito e `restart: unless-stopped` (coerente con gli altri
  servizi UAT)
- [ ] Nuove variabili `DB_LEGACY_*` aggiunte a `.env.uat.example` (coerenti con quelle già usate in locale)
- [ ] `docker-compose.uat.yml`: nuovo bind-mount/volume per `LEGACY_MEDIA_PATH` nel container `app`, su un
  percorso dedicato del disco host **documentato esplicitamente** (non l'intero repo, che l'immagine UAT
  non monta)
- [ ] Verifica: `docker compose -f docker-compose.uat.yml config --quiet` non produce errori (con un
  `.env.uat` temporaneo per il check, poi rimosso)
- [ ] **Nessuna azione dal vivo contro il server msuat reale**: questa story prepara solo file di
  configurazione, non si connette a msuat e non esegue nulla lì
- [ ] Tests pass (nessuna regressione; questa story non tocca codice PHP)

### US-R06: `remote-deploy.sh` aggiornato al flusso ETL reale
**Description:** As a developer, il deploy automatico su UAT deve eseguire l'ETL reale invece del seed
fittizio.

**Acceptance Criteria:**
- [ ] Lo script/step di deploy sostituisce `migrate --force` + `db:seed --class=UatSeeder --force` con:
  `migrate:fresh --force` → `db:seed --class=RolePermissionSeeder --force` →
  `php artisan v1:import --anonymize`
- [ ] Il comando attende che `db_legacy` sia sano (healthcheck di US-R05) prima di procedere
- [ ] Nessun riferimento residuo a `UatSeeder` nello script/workflow di deploy
- [ ] **Nessuna esecuzione dal vivo contro msuat**: la story modifica solo il contenuto dello
  script/workflow (verificato con `bash -n`/lettura del diff), non lo esegue contro il server reale
- [ ] Tests pass (nessuna regressione)

### US-R07: Documentazione aggiornata
**Description:** As a developer che si unisce al progetto, voglio che README/CLAUDE.md riflettano il nuovo
flusso, non quello vecchio con i seeder fittizi.

**Acceptance Criteria:**
- [ ] `README.md`/`orchestrator/CLAUDE.md` aggiornati: nuova sequenza di `make setup`, convenzione
  `v1dumps/latest.sql`, rimozione di `DevelopmentSeeder`/`UatSeeder`, nuova infrastruttura `db_legacy` su
  UAT
- [ ] Nota esplicita che `docs/collaudo/*` restano **fuori scope**, aggiornati dal committente separatamente
  (per evitare che una story futura ci provi per errore)
- [ ] Nessun riferimento residuo a `DevelopmentSeeder`/`UatSeeder` in tutta la documentazione del repo
  (verificato con grep)
- [ ] `composer run lint` / `composer run analyse` / suite di test eseguiti senza regressioni (solo
  documentazione toccata, ma verificato comunque)
- [ ] Tests pass

## 4. Requisiti funzionali

1. Con `--anonymize`, ogni password importata deve essere l'hash di una password fissa nota, mai l'hash v1
   reale; senza `--anonymize`, il comportamento resta invariato.
2. `make setup` deve fallire esplicitamente se `v1dumps/latest.sql` non esiste, mai un fallback fittizio
   silenzioso.
3. `make setup` deve eseguire l'intera sequenza ETL reale (db_legacy su, caricamento dump, migrate, seed
   ruoli, import anonimizzato) con un solo comando.
4. UAT deve avere una propria sorgente `db_legacy` e un percorso per gli allegati, popolati manualmente da
   un umano, mai da un'automazione che si collega a produzione.
5. Il deploy UAT deve eseguire `migrate:fresh` + `v1:import --anonymize` ad ogni push su `develop`.
6. `DevelopmentSeeder` e `UatSeeder` devono essere rimossi per intero, senza lasciare riferimenti residui.

## 5. Non-Goals (fuori scope)

- Automatizzare il recupero di dump/media da produzione dalla pipeline CI/CD o da `make setup` — resta
  sempre un passo umano con accesso SSH diretto.
- Aggiornare `docs/collaudo/*` — il committente li aggiorna direttamente.
- Qualunque esecuzione reale contro il server msuat (deploy live, caricamento dump reale lì): questo lavoro
  prepara solo codice/configurazione, la verifica reale su UAT avviene al primo push su `develop` dopo il
  merge, con il committente coinvolto.
- Modifiche allo schema v2 o agli stage ETL esistenti (oltre al reset password in `Anonymizer`).

## 6. Considerazioni tecniche

- `Anonymizer`/`UsersStage` vivono in `app/Import/`, isolati dal resto del dominio (§4.3 del PRD
  principale): il reset password è un cambiamento contenuto a quei due file.
- `bin/load-v1-dump`/`bin/fetch-legacy-media` esistono già (Fase 0/US-219) e non richiedono modifiche di
  codice per questo lavoro — solo la nuova convenzione di percorso (`v1dumps/latest.sql`) va documentata e
  usata da `make setup`.
- Le story US-R05/US-R06 toccano infrastruttura condivisa reale (msuat): il codice va scritto e verificato
  strutturalmente (syntax/config check), ma nessuna esecuzione live è nello scope di questo lavoro.

## 7. Metriche di successo

- `make setup` da zero, con `v1dumps/latest.sql` presente, produce un ambiente locale con dati reali
  anonimizzati, senza errori.
- Nessun hash di password v1 reale rilevabile in un ambiente non di produzione dopo un import con
  `--anonymize`.
- Zero riferimenti a `DevelopmentSeeder`/`UatSeeder` in tutto il repository dopo il completamento.
- `docker-compose.uat.yml` valido sintatticamente con la nuova infrastruttura.

## 8. Domande aperte

- Quale id utente v1 documentare come account "admin" di riferimento per il collaudo su dati anonimizzati:
  resta una decisione del committente, da riflettere nei manifest di collaudo (fuori scope qui).
