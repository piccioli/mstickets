# ETL / dump v1 (US-007+)

Si carica quando lavori sotto `app/Import/*` (o su `app/Console/Commands/V1*.php`). Vedi anche
`app/Domain/CLAUDE.md` per i pattern condivisi e `docker/CLAUDE.md`/`deploy/CLAUDE.md` per come `db_legacy` e
`v1:import` si inseriscono in Docker/deploy.

- `db_legacy` (Postgres 16, database di appoggio in sola lettura per il dump v1) parte SOLO con
  `docker compose --profile etl up -d db_legacy` / `make etl-up`, mai con un `docker compose up` normale: non
  aggiungere `db_legacy` alle dipendenze di default di `app`/`web`/`queue`.
- `bin/load-v1-dump path/to/dump.sql` ripristina il dump in `db_legacy`.
- Comandi ETL (`v1:inspect`, `v1:import`/`v1:validate`) leggono da `db_legacy` tramite la connessione Eloquent
  dedicata `legacy` (`config/database.php`, env `DB_LEGACY_*`), mai riusando la connessione di default
  `pgsql`. Usare `DB::connection('legacy')->table(...)` (query builder), non Eloquent: le tabelle v1 non
  hanno (e non devono avere) un Model in questo repo.
- **Dump reali di produzione v1 vanno cercati in `v1dumps/`** (in `.gitignore`: mai committare
  `.sql`/`.sql.gz`). Il più recente conosciuto va scaricato via `scp` dall'host di produzione (alias SSH
  `ms`). **Prima di rieseguire `v1:inspect` per una fase successiva**, verificare se il committente ha
  fornito un dump più recente e preferire sempre quello con la data più alta.
- **Convenzione `v1dumps/latest.sql` (design ETL real data seeding, US-R02)**: un puntatore fisso (symlink o
  copia, `ln -sf production_dump_YYYYMMDD_HHMMSS.sql v1dumps/latest.sql`) all'ultimo dump reale, mantenuto
  **manualmente** da un umano con accesso SSH a produzione — nessuno script (`make setup`, deploy UAT) lo
  aggiorna da solo. Leggono sempre questo path fisso, mai un pattern di data: qualunque script che debba
  "usare l'ultimo dump" va scritto contro `v1dumps/latest.sql`, non contro l'ultimo file per data di
  modifica.
- **Il volume `db_legacy_data` persiste tra un `docker compose --profile etl down`/`up` e l'altro** (down
  senza `-v` non lo rimuove): se si ricarica un dump diverso, resettare prima lo schema
  (`DROP SCHEMA public CASCADE; CREATE SCHEMA public;` via `psql`), altrimenti il restore fallisce su oggetti
  già esistenti. Il ruolo Postgres `orchestrator` (richiesto dagli `ALTER ... OWNER TO` nel dump) va creato
  solo se non esiste già (verificare con `\du`).
- **Fermare `db_legacy` con `docker compose --profile etl stop db_legacy`, mai con `down`**, se gli altri
  servizi del progetto sono in uso attivo: `down` fermerebbe l'intero stack, condividendo la stessa rete
  Compose.
- **Restore di un dump reale (pg_dump completo) contro `db_legacy` (`postgres:16-alpine` vanilla) fallisce
  di default** per due motivi indipendenti, entrambi non legati alle tabelle applicative:
  1. Il dump contiene `ALTER ... OWNER TO orchestrator`: va creato prima con `CREATE ROLE orchestrator;`,
     altrimenti con `ON_ERROR_STOP=1` (usato da `bin/load-v1-dump`) il restore si ferma al primissimo
     statement e non crea NESSUNA tabella.
  2. Il cluster sorgente ha PostGIS installato (schemi `tiger`/`tiger_data`/`topology`) che
     `postgres:16-alpine` non ha: questi statement falliscono sempre, ma sono innocui (nessuna tabella
     applicativa dipende da PostGIS). Per un restore una-tantum di verifica, ricreare lo schema pulito e
     ripristinare con `psql` **senza** `-v ON_ERROR_STOP=1`, poi controllare con `\dt`/conteggi riga che le
     tabelle mstickets siano presenti — non serve investire tempo a rendere l'intero dump "pulito".
- **`Storage::disk('local')` NON scrive in `storage/app/`**: in Laravel 11+ il disco `local` di default ha
  `root` = `storage_path('app/private')`. Se una story richiede esplicitamente un path letterale sotto
  `storage/app/...` (come l'AC di US-008), non usare `Storage::disk('local')`: costruire un disco dedicato
  con `Storage::build(['driver' => 'local', 'root' => storage_path('app')])` (vedi
  `V1InspectCommand::appDisk()`).
- `storage/app/.gitignore` ignora tutto tranne `private/`, `public/`, `.gitignore`: qualunque nuova
  sottocartella di `storage/app/` che deve finire nel repository richiede un'eccezione esplicita (`!import/`
  + `!import/**`) in quel file, altrimenti `git add` non la traccia silenziosamente.
- **Scaffold di `v1:import` (US-201, `App\Import\Stages\*`)**: uno stage reale implementa
  `App\Import\Stages\Contracts\ImportStage` (nome, dipendenze dichiarate, `run(ImportContext):
  StageResult`) e si registra elencando la propria classe in `config('import.stages')` — nessuna story
  successiva deve toccare `V1ImportCommand`, `ImportRunner` o `ImportStageRegistry`, solo quel file di
  config. `ImportRunner::plan()` risolve l'ordine di esecuzione dalle dipendenze (ordinamento topologico):
  `--from-stage=X` esegue X e tutto ciò che segue (le dipendenze precedenti si assumono già eseguite in una
  sessione precedente, punto di ripresa dopo un errore parziale); `--stage=X` esegue **solo** X e fallisce
  esplicitamente se X dichiara anche una sola dipendenza. Vedi il commento in testa a `ImportRunner`.
- I test del runner usano stage fittizi (`Tests\Feature\Import\Fixtures\FakeImportStage`) invece di una
  classe fittizia per scenario: ogni story che aggiunge uno stage reale scrive i propri test contro la sua
  classe, non contro le fixture del runner.
- **Come feature-testare uno stage reale senza `db_legacy` in esecuzione (US-202+)**: il trait
  `Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase` (`useSqliteLegacyConnection()`) riconfigura a
  runtime la connessione `legacy` da pgsql a sqlite in-memory. Il test crea con
  `Schema::connection('legacy')->create('nome_tabella_v1', ...)` solo le colonne v1 di cui lo stage ha
  bisogno e ci inserisce righe fixture: lo stage gira così per intero dentro `php artisan test`/CI. Riusare
  questo trait per ogni stage successivo invece di re-inventare la riconfigurazione.
- **Schema reale delle tabelle v1**: leggere il `CREATE TABLE public.<tabella> (...)` direttamente nel dump
  SQL non compresso (`grep -n "CREATE TABLE public.<tabella>"`) — più affidabile del solo report
  `v1:inspect`. Esempio verificato per `users`: **non** ha `deactivated_at` né `deleted_at`, ha invece
  `activity_report_language` (→ `locale` v2), `google_drive_url`/`google_drive_budget_url` (→
  `drive_url`/`drive_budget_url` v2) e `help_desk_chat`/`help_desk_chat_url` (fuori mapping, Q17 del PRD).
- **Slug provvisorio per uno stage che importa in una tabella con `slug` unique/non-null e nessuna colonna
  v1 equivalente** (US-204): trait `App\Import\Stages\Concerns\GeneratesProvisionalSlugs`
  (`uniqueSlug(string $source, array &$existingSlugs)`), genera `Str::slug($source)` con suffisso numerico
  sui duplicati confrontando contro un array di slug già assegnati (precaricato una volta, aggiornato per
  riferimento ad ogni insert). Lo slug è generato **solo in fase di insert**, mai ricalcolato quando la riga
  esiste già e viene aggiornata: il ricalcolo definitivo/idempotente è delegato allo stage `derive` (US-215).
- **Il morph polimorfico v1 `tags.taggable_id`/`taggable_type` è diverso dalla pivot `taggables`** (che
  collega `Story`↔`Tag`): solo `tags.taggable_type = 'App\Models\Documentation'` sopravvive in v2 come FK
  esplicita `tags.documentation_id`; qualunque altro `taggable_type` collassa a tag semplice con
  `documentation_id = null`, contato in un unico warning aggregato di fine stage.
- **Colonne derivate una-tantum al primo insert, mai ricalcolate su un ticket già importato**
  (`TicketsStage`, US-205: `status_changed_at`/`previous_status` ricostruiti da `story_logs`,
  `worked_minutes` fissato a 0): l'attributo va incluso nell'array passato a `insert()` ma **escluso**
  dall'array `$attributes` usato per il confronto/`update()` di una riga già esistente, altrimenti una
  riesecuzione idempotente sovrascriverebbe silenziosamente valori mantenuti dall'applicazione. Riusare
  questa distinzione insert-only per qualunque futura colonna "ricostruita dai log, poi mantenuta dall'app".
- **`stories.user_id`/`creator_id`/`tester_id` → `tickets.assignee_id`/`requester_id`/`tester_id`** (§0.3 del
  PRD, `user_id` v1 è il developer assegnato, non un utente generico) — verificare sempre la tabella di
  glossario §0.3 prima di assumere una corrispondenza per nome colonna.
- **Idempotenza via `App\Import\Models\ImportMapping` (US-208)**: per uno stage che crea righe v2 senza una
  chiave naturale/composita su cui ri-matchare, registrare `ImportMapping::create(['source_table' => ...,
  'source_key' => (string) $row->id, 'target_table' => ..., 'target_id' => $newId])` ad ogni insert, e
  precaricare una volta per stage l'insieme delle `source_key` già mappate
  (`array_flip(ImportMapping::query()->where(...)->pluck('source_key')->all())`) per uno skip idempotente
  O(1) per riga.
- **`story_logs.changes` (JSON libero v1) → colonne esplicite v2, priorità mutuamente esclusiva**
  (`TicketLogsStage`, US-208): `status` presente vince su `user_id` presente, che vince sul fallback
  generico `event = updated` con il diff residuo in `changes` (mai il corpo di `description`, solo il marker
  `'changed'`). I log con **sola** chiave `watch` sono esclusi (alimentano `ticket_views`, US-209).
- **Stage di aggregazione (molte righe sorgente → una riga v2)** (`TicketViewsStage`, US-209): filtro
  complementare (`array_keys($changes) === ['watch']`). Le righe `watch` sono raggruppate **in memoria** per
  `(ticket_id, user_id, date(viewed_at))`: `view_count` = conteggio del gruppo, `last_viewed_at` = timestamp
  massimo. Idempotenza sul vincolo unique applicativo esistente, non su `ImportMapping`: un gruppo il cui
  `ticket_views` esiste già viene saltato per intero, mai aggiornato — corretto perché lo stage non deve mai
  sovrascrivere visualizzazioni reali registrate dall'app dopo l'import.
- **`stories.customer_request` (conversazione accumulata, `TicketMessagesStage`/US-210)**: ogni risposta è
  **prepesa** dall'applicazione v1 con un template HTML fisso e riconoscibile.
  `App\Import\Parsers\CustomerRequestParser::parse()` (puro) riconosce **solo** questo template esatto in
  sequenza dall'inizio; qualunque altra forma (email inoltrate, citazioni Gmail, notifiche form) resta un
  unico messaggio "originale". Il contenuto dopo l'ultimo blocco di risposta è sempre il messaggio più
  vecchio: assegnato a `tickets.requester_id`. Gli autori dei blocchi risposta portano solo un nome v1:
  risolti per corrispondenza case-insensitive esatta su `users.name`, solo se univoca. Verificare sempre
  `checkdate()` prima di `Carbon::create()` su una data testuale non fidata: quest'ultimo trabocca
  silenziosamente al mese successivo invece di segnalare un timestamp non ricostruibile.
- **`media` v1 → collection medialibrary `attachments` su `TicketMessage`** (`TicketAttachmentsStage`,
  US-211): i file sorgente non sono nel dump SQL — vanno depositati sotto `storage/app/v1-media/`, disco
  Laravel **nominato** `legacy-media` (root `env('LEGACY_MEDIA_PATH', storage_path('app/v1-media'))`), usato
  sempre via `Storage::disk('legacy-media')` (un disco nominato è l'unico modo per poterlo `Storage::fake()`
  nei test). Un media la cui riga esiste ma il file è assente è un compromesso segnalato, mai un crash.
- **AGGIORNAMENTO (US-219): il percorso file su `legacy-media` è `<uuid>-<file_name>`**, non il `file_name`
  piatto: `file_name` NON è univoco tra ticket diversi (110 nomi duplicati su 752 righe nel dump reale)
  mentre `media.uuid` è garantito univoco. `bin/fetch-legacy-media` popola `legacy-media` da produzione:
  costruisce un indice nome-file+dimensione dell'INTERO albero `media/` server-side (il layout reale è
  fragile: un ticket rinominato dopo l'upload lascia i file fisici sotto il titolo VECCHIO) e trasferisce
  tutto con un solo `scp` di un archivio compresso. Recupera 732/752 media (97.1%) sul dump reale — i
  restanti sono genuinamente assenti anche su produzione.
- **`addMedia($absolutePath)` cancella per default il file sorgente dopo la copia**: per un file letto in
  sola lettura da un disco che non ci appartiene, chiamare sempre `->preservingOriginal()` prima di
  `->toMediaCollection(...)`.
- **Attaccare un media storico a un messaggio che potrebbe non esistere ancora**: se il ticket non ha alcun
  messaggio "legacy" (`is_legacy_import = true`), lo stage ne crea uno di sistema con lo STESSO flag —
  diventa automaticamente il "primo messaggio legacy" anche per una riesecuzione futura, senza una seconda
  chiave di idempotenza dedicata.
- **`activity_reports.owner_type`/`customer_id`/`organization_id` (v1) → `owner_kind`/`owner_user_id`/
  `owner_organization_id` (v2), vincolo CHECK già esistente in v2** (`ActivityReportsStage`, US-212):
  `customer_id` v1 punta in realtà a `users`. Prima di ogni insert/update la riga v1 è validata a mano contro
  la stessa logica del CHECK Postgres: una riga ambigua è scartata e contata in un warning aggregato
  **prima** di arrivare a `insert()`, mai lasciata sollevare l'eccezione SQL del vincolo.
- **`activity_reports.locale` derivato dall'owner già risolto** (`users.locale`/`organizations.locale`,
  precaricati una volta per stage con `pluck('locale', 'id')`, non una query per riga).
- **SCOPERTA IMPORTANTE (US-213): il dump reale NON ha MAI avuto nessuna delle 34 colonne `evaluation_*`**
  che il PRD descrive su `fundraising_opportunities` v1. `FundraisingScoresStage` rileva **dinamicamente**
  con `Schema::connection('legacy')->hasColumn(...)` quali colonne esistono davvero, e produce zero righe con
  un warning esplicito. Pattern "colonna legacy che potrebbe non esistere": usare `hasColumn()` prima di
  aggiungerla al `select()`, mai leggerla alla cieca (un `select()` su una colonna assente lancia un errore
  SQL immediato).
- **Stage `derive` (US-215, ultimo dell'ETL)**: bundle di 6 derivazioni indipendenti su entità già importate
  (nessuna lettura da `legacy`): backfill `tickets.released_at`/`done_at`, ricalcolo `worked_minutes`/
  `ticket_work_logs` (riuso diretto di `RecalculateWorkedTime`, vedi `app/Domain/TimeTracking/CLAUDE.md`),
  totali di valutazione fundraising, slug definitivi, `email_threads`, riallineamento sequenze Postgres.
  `--limit` si applica **solo** al ricalcolo ticket: le altre sono manutenzioni globali non incrementali.
- `email_threads`: il segnale pratico di "ticket con conversazione importata" è la sola presenza di almeno
  una riga `ticket_messages` per quel ticket. `participants` è un array JSON di email distinte. La
  normalizzazione del subject vive per ora in un metodo privato di `DeriveStage` — se Fase 3 introduce il
  matching lato inbound, riusare la STESSA funzione (vedi `app/Domain/Mail/CLAUDE.md` §US-306).
- **Rigenerazione slug DEFINITIVA con lo stesso trait `GeneratesProvisionalSlugs`**: `DeriveStage` itera
  l'intera tabella in ordine di `id` crescente con un `$seenSlugs = []` fresco e ricalcola OGNI riga da zero,
  deterministico e idempotente.
- **Verificare un `DB::statement` Postgres-only (`setval(pg_get_serial_sequence(...))`) senza toccare i dati
  di sviluppo persistenti**: `docker compose exec app php artisan tinker`, avvolgere in
  `DB::beginTransaction()`/`DB::rollBack()` (mai un `migrate:fresh` distruttivo), inserire una riga con un
  `id` esplicito alto, eseguire il `setval`, creare una riga Eloquent normale, verificare, rollback.
- **Comando `v1:validate` (US-216)**: NON usa `information_schema.tables` (non esiste su sqlite) — usa
  `Schema::connection('legacy')->hasTable($nome)` per ogni tabella nota, portabile su entrambi i driver: è
  per questo che l'intero comando è testabile via `tests/Feature/Console/V1ValidateCommandTest.php`.
- **Report `v1:validate` su un disco nominato `import-reports`** (`root: storage_path('app')`), non un
  `Storage::build()` ad-hoc — stesso motivo di `legacy-media`.
- **`v1:validate` è un gate CI in senso stretto (§11.7)**: fallisce SOLO se un conteggio con id conservato
  non coincide o un controllo di integrità ha un conteggio diverso da zero. Il confronto dei derivati (ore
  lavorate ±5%, totali fundraising) e "compromessi applicati" sono SOLO informativi, non influenzano l'exit
  code.
- **BUG DI IDEMPOTENZA REALE trovato in `TicketsStage` durante il test di idempotenza dell'intera pipeline**
  (US-216, `V1ImportPipelineIdempotencyTest.php`): (1) `released_at`/`done_at` erano inclusi nell'array
  `$attributes` usato sia per l'insert sia per il diff/update — quando `derive` backfilla `done_at`, una
  riesecuzione di `TicketsStage` rileggeva `stories.done_at` (sempre `null` in v1) e lo confrontava col valore
  ora valorizzato → `UPDATE` che azzerava di nuovo il backfill. Fix: spostati nell'array di solo-insert,
  esclusi dal diff. (2) `updated_at` era confrontato nel diff pur essendo l'UNICA colonna che uno stage
  successivo (`derive`) tocca silenziosamente — rendeva "cambiato" ogni ticket già derivato ad ogni
  riesecuzione. Fix: `attributesDiffer()` esclude esplicitamente `updated_at`. **Pattern generale**:
  qualunque colonna che un processo A SUCCESSIVO allo stage può modificare non va MAI usata per decidere se
  il record "è cambiato" alla riesecuzione dello stage originario — né come valore da confrontare né come
  valore da riscrivere ciecamente.
- **Come si è scoperto il bug sopra**: nessun test per singolo stage lo intercettava (ognuno testa il proprio
  stage in isolamento). Solo un test che esegue `Artisan::call('v1:import')` due volte con TUTTI gli stage
  reali registrati e verifica `created===0 && updated===0` per ENTRAMBI gli stage lo intercetta. Se una fase
  futura aggiunge un nuovo stage con una relazione simile a `derive`, estendere la fixture di
  `V1ImportPipelineIdempotencyTest` invece di scrivere un test isolato.
- **`--anonymize` — RIDEFINITO da US-R08 (2026-08-11)**: il design originale (`Anonymizer`, rimosso)
  sostituiva nome/email/contenuti con dati fittizi. Il committente ha richiesto il contrario: nome/email/
  ruoli/contenuti restano SEMPRE quelli reali, sia con sia senza `--anonymize` — l'unica cosa che il flag
  continua a cambiare è la password, impostata a un hash fisso noto (`App\Import\Security\
  FixedPasswordHasher`, password `uat`). `UsersStage`/`TicketMessagesStage` non toccano più `name`/`email`/
  il corpo dei messaggi in alcun caso. Le identità di riferimento del collaudo elencano ora l'id/nome/email
  REALE di una persona rappresentativa per ruolo — solo l'account Manager resta creato ex novo con email
  fissa `manager@oc.test`.
- Il guard anti-invio-reale (`App\Support\Mail\BlockRealRecipientsOutsideProduction`, listener di
  `MessageSending`, registrato a mano in `AppServiceProvider::boot()`) blocca qualunque destinatario il cui
  dominio non è in `config('orchestrator.anonymization.mail_test_domains')`, MA SOLO fuori produzione — ora
  PIÙ rilevante che mai (gli utenti importati hanno email reali). Testabile con `config(['mail.default' =>
  'array'])` + `Mail::mailer('array')->raw(...)` + conteggio messaggi accumulati.
- **Fixture ridotta per il gate CI (US-218, `tests/Fixtures/Import/v1-ci-fixture.sql`)**: a differenza di
  ogni test precedente, il job `etl-fixture` di CI carica questo file con un vero `psql` in un vero servizio
  Postgres, poi esegue `v1:import` due volte e `v1:validate` per davvero — puro SQL, senza vincoli FK
  dichiarati apposta e senza `ALTER ... OWNER TO`. Un'email duplicata a meno del case NON può stare in questa
  fixture (`UsersStage` non deduplica, farebbe fallire il gate) — resta coperto isolatamente da
  `UsersStageTest.php`.
- **Verificare a mano una fixture SQL contro `db_legacy` senza toccare i dati di sviluppo persistenti**:
  `docker compose --profile etl up -d db_legacy` (resettare schema se necessario), caricare con `psql -f`,
  eseguire gli `artisan` sul container `app` con override una-tantum
  (`docker compose exec -e DB_CONNECTION=sqlite -e DB_DATABASE=/tmp/qualcosa.sqlite app ...`).
- **BUG REALE (Postgres-only) trovato al checkpoint di fine Fase 2 (US-219)**:
  `V1ValidateCommand::reportDuplicateCount()` costruiva `->selectRaw("{$expr} as dedup_key, count(*) as
  total")->having('total', '>', 1)`, referenziando l'alias `total` in `HAVING` — SQLite lo tollera, Postgres
  lo rifiuta (`SQLSTATE[42703]`). Nessun test l'ha mai intercettato perché lo schema v2 di ogni test era
  sqlite (solo la connessione `legacy` era Postgres reale). Fix: `->havingRaw('count(*) > 1')` (nessun alias
  referenziato). **Pattern generale**: qualunque `->having(...)` su un alias di `selectRaw` va scritto con
  `havingRaw('<espressione letterale>')`, mai riferendo l'alias.
- **Il job CI `etl-fixture` è stato esteso in US-219 per usare un vero Postgres anche per lo schema v2**
  (nuovo servizio `db` accanto a `db_legacy`, porta host 5433) proprio perché il bug sopra dimostra che
  v2-su-sqlite in CI è un angolo cieco reale. Se una fase futura introduce altro codice ETL con
  `selectRaw`/aggregazioni SQL dirette, verificarlo sempre anche contro questo job.
