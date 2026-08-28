# Importazione dal dump v1 (ETL)

Fonte: PRD-ORCHESTRATOR-V2.md §11 (M11 — Importazione dal dump v1), `CLAUDE.md` (sezioni ETL/dump
v1, US-201...US-219, US-R02, US-R08). Il codice vive interamente in `app/Import/` (isolato, rimovibile
in blocco a cutover concluso — nessuna classe di dominio ne dipende) più i comandi
`app/Console/Commands/V1{Inspect,Import,Validate}Command.php`.

## Principi (§11.1)

| # | Principio | Come è rispettato |
|---|---|---|
| P1 | Ripetibile e idempotente | ogni stage ha una chiave di riconciliazione (id conservato, vincolo unique composito, o `ImportMapping`) |
| P2 | Non distruttiva sulla sorgente | il dump v1 vive in `db_legacy` (Postgres separato, sola lettura), connessione Eloquent dedicata `legacy` |
| P3 | Isolata | tutto in `app/Import/`, nessuna classe di dominio dipende da essa |
| P4 | A stage | 21 stage indipendenti, dipendenze dichiarate, eseguibili singolarmente |
| P5 | Verificabile | `v1:validate` produce un report ad ogni esecuzione |
| P6 | Trasparente sui compromessi | ogni regola di mapping ambigua è dichiarata, registrata e contata (mai una scelta silenziosa) |
| P7 | Continuità degli id | `users`, `tickets`, `tags`, `documentation_pages`, `organizations`, `activity_reports`, entità fundraising conservano l'id v1 |

## Procedura operativa

```bash
# 1. Ripristina il dump v1 nel database di appoggio (sola lettura)
make etl-up                              # avvia (solo) db_legacy, profilo Compose "etl"
bin/load-v1-dump path/to/dump.sql

# 2. Ispeziona il dump PRIMA di importare
docker compose exec app php artisan v1:inspect

# 3. Prova a vuoto: nessuna scrittura, solo il report
php artisan v1:import --dry-run

# 4. Importa (SEMPRE con --anonymize fuori produzione, vedi sotto)
php artisan v1:import --anonymize

# 5. Verifica
php artisan v1:validate
```

`make setup` (README.md) esegue l'intera sequenza da zero, incluso il caricamento di
`v1dumps/latest.sql`, fallendo subito con un messaggio esplicito se quel file non esiste.

### Opzioni di `v1:import`

| Opzione | Effetto |
|---|---|
| `--dry-run` | nessuna scrittura, report completo |
| `--stage=<nome>` | esegue un solo stage; fallisce esplicitamente se quello stage ha dipendenze non soddisfatte (usare `--from-stage`) |
| `--from-stage=<nome>` | riprende da uno stage in poi (le dipendenze precedenti sono assunte già eseguite) |
| `--limit=N` | importa solo i primi N record per stage (sviluppo rapido) — **non** si applica alla sotto-derivazione dello stage `derive` diversa dal ricalcolo ticket (manutenzioni globali, non incrementali) |
| `--truncate` | svuota le tabelle di destinazione prima di importare — solo non-produzione, con conferma interattiva |
| `--anonymize` | vedi sotto |

`ImportRunner::plan()` risolve l'ordine di esecuzione dalle dipendenze dichiarate (ordinamento
topologico); un nuovo stage reale implementa `App\Import\Stages\Contracts\ImportStage` e si registra
in `config('import.stages')` — nessuna story successiva deve toccare `V1ImportCommand`/`ImportRunner`/
`ImportStageRegistry`.

## Ispezione preliminare — `v1:inspect`

Da eseguire **prima** di finalizzare i mapping: il modello v1 dichiara cose che il dato reale può
smentire. Riporta conteggi per tabella, valori distinti effettivi di `stories.status`/`type`/`priority`,
formati reali di `users.roles`, parsabilità di `stories.customer_request`, conflitti di gerarchia
`story_story`/`parent_id`, ticket con `done_at`/`released_at` null pur essendo `done`/`released`,
media orfani, email duplicate a meno del case, FK orfane. Output salvato in
`storage/app/import/inspect-<timestamp>.md` (disco Laravel nominato `import-reports`, root
`storage_path('app')` — non il default `storage/app/private/`).

**Nota tecnica**: a differenza di `v1:validate`, `v1:inspect` usa `information_schema.tables` sulla
connessione `legacy` per la discovery delle tabelle — query non disponibile su sqlite, quindi
`v1:inspect` non è testabile end-to-end con la stessa infrastruttura di test degli stage
(`InteractsWithLegacyDatabase`).

## Stage (§11.4)

| # | Stage | Sorgente v1 | Destinazione v2 | Chiave di idempotenza |
|---|---|---|---|---|
| 1 | `users` | `users` | `users` | id conservato |
| 2 | `roles_permissions` | `users.roles` (JSON) | tabelle Spatie | `(user_id, role)` / `(user_id, permission)` |
| 3 | `organizations` | `organizations` | `organizations` | id conservato |
| 4 | `organization_members` | `organization_user` | `organization_user` | `(organization_id, user_id)` |
| 5 | `documentation` | `documentations` | `documentation_pages` | id conservato |
| 6 | `tags` | `tags` | `tags` | id conservato |
| 7 | `tickets` | `stories` | `tickets` | id conservato |
| 8 | `ticket_hierarchy` | `stories.parent_id` + `story_story` | `tickets.parent_id` | id del ticket |
| 9 | `ticket_tags` | `taggables` (solo Story) | `ticket_tag` | `(ticket_id, tag_id)` |
| 10 | `ticket_participants` | `story_participants` | `ticket_participants` | `(ticket_id, user_id)` |
| 11 | `ticket_logs` | `story_logs` (esclusi i `watch`) | `ticket_logs` | `ImportMapping` su `story_logs.id` |
| 12 | `ticket_views` | `story_logs` con `changes->watch` | `ticket_views` | `(ticket_id, user_id, viewed_on)` |
| 13 | `ticket_messages` | `stories.customer_request` (parsing) | `ticket_messages` | `ImportMapping` su `(story_id, indice, hash)` |
| 14 | `ticket_attachments` | `media` su `Story` | media su `TicketMessage` | `media.uuid` |
| 15 | `activity_reports` | `activity_reports` | `activity_reports` | id conservato |
| 16 | `activity_report_tickets` | `activity_report_story` | `activity_report_ticket` | `(report_id, ticket_id)` |
| 17 | `fundraising_opportunities` | `fundraising_opportunities` | idem | id conservato |
| 18 | `fundraising_scores` | 34 colonne `evaluation_*` | `fundraising_evaluation_scores` | `(opportunity_id, criterion_key)` |
| 19 | `fundraising_projects` | `fundraising_projects` | idem | id conservato |
| 20 | `fundraising_partners` | `fundraising_project_partners` | idem | `(project_id, user_id)` |
| 21 | `derive` | — (righe già importate) | valori calcolati | ricalcolo completo, idempotente per definizione |

A fine import: riallineamento esplicito delle sequenze PostgreSQL per ogni tabella con id conservato.

## Regole di mapping non banali (§11.5) — tutte implementate e contate nel report

- **Ruoli e permessi (stage 2)**: parse tollerante di `users.roles` (JSON in un `varchar`); un ruolo
  riconosciuto è assegnato via Spatie; **`editor` non è più un ruolo** (D14) — l'utente riceve i
  permessi diretti `documentation.create`/`documentation.update`; un ruolo non riconosciuto è
  scartato e segnalato; un utente senza ruoli è segnalato (in v2 non potrà accedere);
  `horizon.access`/`logs.access` **non** sono assegnati automaticamente ai developer (erano impliciti
  nel ruolo nel v1) — l'ETL produce solo l'elenco dei developer esistenti perché si possa decidere a
  chi concederli come permessi diretti.
- **Tipo e priorità (stage 7)**: mapping case-insensitive e tollerante agli spazi (`Bug`→`bug`,
  `Help desk`/`Helpdesk`/`help desk`→`helpdesk`, `Scrum`/`scrum`→`scrum`; default `helpdesk` +
  segnalazione). Priorità `1`/`2`/`3` → `low`/`medium`/`high`; altri valori → `low` + segnalazione.
- **`status_changed_at` (stage 7)**: non esiste nel v1, derivato dal `story_logs` più recente con un
  cambio di stato; fallback `stories.updated_at`, contato nel report.
- **`previous_status` (stage 7)**: per ticket `waiting`/`problem`, risale ai log fino al primo stato
  diverso da `waiting`/`problem`; fallback `new`, contato.
- **`ticket_logs` (stage 11)**: il `changes` JSON del v1 è tradotto in `event`/`from_status`/`to_status`/
  `changes` con priorità mutuamente esclusiva: `status` presente → `status_changed`; `user_id`
  presente → `assigned`; altrimenti `updated` con diff residuo (mai il corpo di `description`, solo
  il marker `"changed"`). `user_id` mancante → utente di sistema. I log con sola chiave `watch`
  alimentano invece lo stage 12 (`ticket_views`), filtro complementare verificato senza sovrapposizione.
- **Conversazione (stage 13, il mapping più delicato)**: `stories.customer_request` è HTML accumulato
  dove ogni risposta è **prepesa** con un template HTML fisso e riconoscibile (`"<Autore> ha risposto
  il: DD-MM-YYYY HH:MM"` + `<div>` con stile per ruolo). `App\Import\Parsers\CustomerRequestParser`
  riconosce **solo** questo template esatto; qualunque altra forma (email inoltrate in chiaro,
  citazioni Gmail annidate) resta un unico messaggio "originale" con l'HTML integrale — è lo stesso
  codepath del fallback richiesto dall'AC, non una modalità di errore distinta. L'ordine è invertito
  (il v1 prepende, la v2 è cronologica). Se `posted_at` non è ricavabile, distribuzione monotona tra
  `created_at`/`updated_at` del ticket (ordine relativo coerente). Autori dei blocchi di risposta
  risolti per corrispondenza case-insensitive esatta su `users.name`, solo se univoca. HTML sempre
  sanitizzato. Il report elenca: messaggi ricostruiti, ticket con fallback a blocco unico, ticket
  senza conversazione.
- **Allegati (stage 14)**: attaccati al primo messaggio legacy del ticket; se il ticket non ha
  messaggi, viene creato un messaggio di sistema "Allegati importati" (`is_legacy_import = true`,
  ritrovato naturalmente dalla stessa query del "primo messaggio legacy"). File verificati fisicamente
  presenti: media orfani segnalati, non ignorati. I file v1 non sono nel dump SQL né nel backup
  `.tar.gz` (solo il volume Postgres): vanno forniti separatamente e depositati sotto
  `storage/app/v1-media/` (disco nominato `legacy-media`), popolabili con `bin/fetch-legacy-media`
  (recupera da produzione via SSH, indicizzando l'intero albero `media/` per nome+dimensione perché
  il layout reale usa il **titolo corrente** del ticket, non quello al momento dell'upload).
- **Gerarchia (stage 8)**: `stories.parent_id` è la sorgente primaria; righe di `story_story` non
  riflesse applicate solo se non creano conflitti (un figlio con due padri diversi → si tiene
  `parent_id`, segnalato). Una violazione della profondità massima 1 viene appiattita e segnalata.
- **Punteggi fundraising (stage 18)**: ogni colonna `evaluation_*_score` non nulla diventa una riga
  con la `criterion_key` corrispondente; punteggi fuori range clampati e segnalati; i totali non si
  importano, si ricalcolano (stage 21) e si confrontano col v1 nel report. **Scoperta reale (US-213)**:
  il dump di produzione reale **non ha mai avuto** nessuna delle 34 colonne `evaluation_*` (verificato
  sia via `CREATE TABLE` sia via l'elenco delle migrazioni v1 nel dump) — la griglia di valutazione
  risulta una feature mai usata in produzione v1, non solo "poco usata". Lo stage rileva
  dinamicamente quali colonne esistono (`Schema::connection('legacy')->hasColumn(...)`) e produce
  zero righe con un warning esplicito contro il dump reale, invece di un `select` letterale che
  fallirebbe.
- **Date mancanti (stage 21)**: `released_at`/`done_at` ricostruiti dai log per i ticket che ne sono
  privi pur essendo `released`/`done` — necessario prima di rigenerare i report di attività (che
  selezionano per `done_at`).

## Derivati (stage 21)

Ricalcolati da zero, nell'ordine: (1) `released_at`/`done_at` mancanti; (2) `worked_minutes` con
`WorkedTimeCalculator` (v. `docs/time-tracking.md`); (3) `ticket_work_logs` per l'intero storico
(riuso diretto di `RecalculateWorkedTime`); (4) totali di valutazione fundraising; (5) slug
definitivi di `tags`/`documentation_pages` (ricalcolati per l'intera tabella in ordine di id, non
solo per le righe senza slug — deterministico e idempotente); (6) `email_threads` per i ticket con
almeno un `ticket_message` importato (necessario perché il threading email funzioni anche sui ticket
storici). Nessuna lettura da `legacy`: opera solo su entità già importate in v2.

## Come leggere il report di validazione (`v1:validate`)

Tre sezioni:

1. **Conteggi a confronto** (v1 vs v2, Δ atteso) — per le entità con id conservato deve essere 0.
2. **Controlli di integrità**: orfani per ogni FK, unicità violate, enum fuori catalogo, ticket senza
   richiedente, messaggi senza ticket, media mancanti sul disco. **Questi soli determinano l'exit
   code**: `v1:validate` fallisce se e solo se un conteggio d'integrità è diverso da zero.
3. **Confronto dei derivati** (ore lavorate con tolleranza, vedi `docs/time-tracking.md`; totali
   fundraising, devono coincidere esattamente) e **compromessi applicati** (con i conteggi:
   `status_changed_at`/`previous_status` da fallback, conversazioni con fallback a blocco unico,
   messaggi senza autore/con data stimata, ruoli scartati, tipi normalizzati, punteggi clampati,
   conflitti di gerarchia, media orfani) — **solo informativi**, non influenzano l'exit code:
   documentano un'assunzione operativa o un fatto da rivedere col committente, non un difetto
   tecnico. L'import è considerato riuscito solo se nessun controllo di integrità fallisce, i
   conteggi conservati coincidono, i totali fundraising coincidono e i compromessi restano entro le
   soglie concordate.

Il report è salvato in `storage/app/import/` e mostrato anche nell'amministrazione (§8.4, gruppo
"Amministrazione", permesso `import.view`).

## Anonimizzazione — `--anonymize` (ridefinito da US-R08)

Il design originale (§11.8 del PRD: sostituire nome/email/corpo con dati fittizi) è stato
**ridefinito su richiesta del committente**: nome, email, ruoli e contenuti restano **sempre** quelli
reali del dump v1, sia con sia senza `--anonymize`. L'unica cosa che il flag continua a cambiare è la
**password**: impostata a un hash fisso noto (`uat`, via `App\Import\Security\FixedPasswordHasher`)
invece dell'hash v1 reale.

`--anonymize` è **obbligatorio** in ogni ambiente non di produzione (sviluppo, staging, CI): solo un
vero cutover verso la produzione reale può ometterlo (mantiene l'hash v1 as-is). Un guard applicativo
indipendente (`App\Support\Mail\BlockRealRecipientsOutsideProduction`) blocca comunque qualunque
invio email verso un dominio non in `MAIL_TEST_DOMAINS` quando `APP_ENV !== production` — è la sola
protezione contro l'invio accidentale di una notifica verso un cliente/collega reale, dato che gli
utenti importati hanno ora email reali anche fuori produzione.

Il dump di produzione non va mai committato (`v1dumps/` è in `.gitignore`) e resta archiviato come
riferimento storico dei moduli non importati (D11).

## Fixture di CI

`tests/Fixtures/Import/v1-ci-fixture.sql` (già anonimizzata alla creazione) copre i casi limite noti
(`type`/`priority` fuori catalogo, `"editor"` in `users.roles`, conversazione non parsabile,
conflitto di gerarchia, media orfano/con `model_type` diverso da `Story`) contro un vero servizio
Postgres in CI (non sqlite): il job `etl-fixture` esegue `v1:import --anonymize` due volte
(verifica idempotenza) e poi `v1:validate` (deve uscire con successo). Un'email duplicata a meno del
case è **deliberatamente esclusa** dalla fixture: a differenza degli altri casi, fa fallire il
controllo di unicità (comportamento corretto, non un semplice warning) e quindi non è compatibile con
l'AC "il comando esce con successo" — resta comunque coperta a livello di stage isolato.
