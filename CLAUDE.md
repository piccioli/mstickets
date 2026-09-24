# Orchestrator v2 — note per agenti

Repository Laravel 13 + Filament 4 per Montagna Servizi. Spec completa: `docs/project/PRD-ORCHESTRATOR-V2.md`.
Piano e story: `docs/project/scripts/ralph/prd.json`. Log di avanzamento: `docs/project/scripts/ralph/progress.txt`.

Questo file contiene solo le convenzioni valide per l'intero repository e i gotcha trasversali (ambiente,
test, framework). Le note specifiche di un modulo/dominio vivono in un `CLAUDE.md` annidato nella directory
di quel modulo — Claude Code lo carica automaticamente insieme a questo quando apri un file in quella
directory. Se stai lavorando SOLO su un file al di fuori dell'albero coperto (es. una vista Blade "orfana"),
usa comunque la mappa qui sotto per aprire a mano il file giusto.

## Mappa dei moduli (`CLAUDE.md` annidati)

| Percorso | Contenuto |
|---|---|
| `app/Domain/CLAUDE.md` | Pattern condivisi da tutti i moduli di dominio (Action, Policy deny-by-default, `scopeVisibleTo()`, eventi+listener, comandi di sistema) |
| `app/Domain/Ticketing/CLAUDE.md` | Ticket, macchina a stati, azioni/eventi, allegati, viste/filtri/board, badge navigazione |
| `app/Domain/TimeTracking/CLAUDE.md` | `WorkedTimeCalculator`, ore lavorate |
| `app/Domain/Mail/CLAUDE.md` | Pipeline email inbound/outbound, catalogo notifiche E1-E11, IMAP, quarantena, soppressioni |
| `app/Domain/Reporting/CLAUDE.md` | Rendicontazione attività (`ActivityReport`), PDF, `reports:generate-monthly` |
| `app/Domain/Fundraising/CLAUDE.md` | Opportunità/progetti fundraising, viste cliente separate |
| `app/Domain/Documentation/CLAUDE.md` | Pagine di documentazione, Tag/commesse, generazione PDF (Chrome) |
| `app/Domain/Identity/CLAUDE.md` | Utenti, ruoli/permessi (spatie-permission), organizzazioni, MFA, impersonation |
| `app/Domain/CaiDirectory/CLAUDE.md` | Anagrafica CAI/RUNTS (Fase 8-9), import, mappa, scraper |
| `app/Import/CLAUDE.md` | ETL dump v1 (`v1:import`/`v1:validate`/`v1:inspect`), `db_legacy` |
| `app/Filament/CLAUDE.md` | Pattern generici Filament (Resource, RelationManager, navigazione, tema/design system) |
| `docs/collaudo/CLAUDE.md` | Processo di collaudo (dettaglio), generazione PDF via pdfLaTeX |
| `docker/CLAUDE.md` | Dockerfile applicativi (estensioni PHP, Chrome, TeX Live) |
| `deploy/CLAUDE.md` | Deploy UAT, `docker-compose.uat.yml` |

## Convenzioni stabilite in Fase 0

- Stack: PHP ^8.4, Laravel 13, Filament 4 (`^4.0`, mai la beta v5).
- Test runner: **Pest 4.x** (non 3.x: `pestphp/pest-plugin-laravel` 3.x non supporta Laravel 13). Test in
  sintassi Pest (`test('...', function () {...})`), non classi PHPUnit.
- Ogni file PHP scaffoldato inizia con `declare(strict_types=1);` subito dopo `<?php`.
- Struttura a moduli sotto `app/`: `Domain/<Modulo>/{Models,Enums,Actions,...}`, `Import/{Stages,Mappers,
  Parsers,Validation}`, `Filament/{Resources,Pages,Widgets,Providers}`, `Support/`. Non aggiungere codice di
  dominio fuori da questa struttura.
- `app/Filament/Providers/AdminPanelProvider.php` (namespace `App\Filament\Providers`) — **non**
  `app/Providers/Filament/` (quello è il default dell'installer Filament).
- Qualità: `composer run lint` (Pint, preset laravel), `composer run analyse` (Larastan livello 6, richiede
  `--memory-limit=1G`), `php artisan test` (Pest — vedi però il gotcha memory_limit sotto).
- Niente business logic negli hook Eloquent (`boot()`/`booted()`/Observer) — vedi PRD §4.4 A1 e
  `app/Domain/CLAUDE.md`. Ogni mutazione passa da una Action esplicita.
- Enum sempre backed e castati, mai confronti su stringhe grezze (PRD §4.4 A4).
- Pacchetti vietati (non installare mai): `spatie/laravel-google-calendar`, `spatie/laravel-translatable`,
  `overtrue/laravel-favorite`, `filament-shield`.

## Ambiente locale vs Docker

- Sviluppo di scaffold verificato con PHP 8.5 locale (compatibile col vincolo `^8.4`), ma il target
  Docker/produzione è PHP 8.4-FPM: il Dockerfile pinna 8.4, non assume la versione locale dell'agente.
- `docker-compose.yml` ha un `name: orchestrator-v2` esplicito in cima: senza, Compose deriva il nome
  progetto dalla directory (`orchestrator`), che può collidere con altri stack Docker Compose sulla stessa
  macchina host. Non rimuovere quel campo. Prima di un `docker compose down`/`rm` verificare sempre
  `docker ps -a` per non toccare container di altri progetti.
- **`make setup` — avvio da zero**: fallisce subito se `v1dumps/latest.sql` manca → `docker compose up -d` →
  `bin/load-v1-dump v1dumps/latest.sql` → `migrate --force` → `db:seed --class=RolePermissionSeeder --force`
  → `php artisan v1:import --anonymize` (mai `--truncate`, richiede una conferma interattiva) →
  `collaudo:ensure-manager-account` → `cai:import-datapack` (best-effort). `composer install`/
  `key:generate` vanno eseguiti con `docker compose run --rm app ...` (container effimero) **prima** di
  `docker compose up -d` dei servizi long-running: `env_file: .env` inietta le variabili al momento della
  CREAZIONE del container — se `app`/`queue`/`scheduler` vengono creati con `.env` ancora vuoto
  (`APP_KEY=`), quella stringa vuota resta l'`APP_KEY` effettivo per tutta la vita del container anche se
  `key:generate` riscrive `.env` subito dopo. `npm install && npm run build` restano un passo host (nessun
  servizio Node in `docker-compose.yml`): `public/build/manifest.json` deve esistere prima di servire
  qualunque pagina del pannello. Dettagli su estensioni PHP/build in `docker/CLAUDE.md`.
- `DatabaseSeeder::run()` oggi richiama solo `RolePermissionSeeder` (il seeder di dati fittizi US-023 è stato
  rimosso in US-R03 quando l'ambiente locale è passato all'ETL reale) — vedi `app/Domain/Identity/CLAUDE.md`.
- Comando diagnostico `orchestrator:doctor` (architettura a controlli indipendenti, §12 del PRD): vedi
  `app/Domain/Identity/CLAUDE.md`.

## Gotcha trasversali (ambiente, framework, test) — validi per QUALUNQUE dominio

- **`php artisan test` ignora `-d memory_limit=1G`** (rilancia PHPUnit in un processo figlio senza propagare
  gli ini settings) e va in OOM sulla suite intera col default 128M: usare `php -d memory_limit=1G
  vendor/bin/pest` (o filtrare con `--filter=...`). In locale, la suite intera esaurisce comunque il limite
  CLI di default indipendentemente dalle modifiche in corso — non è un problema da risolvere nel codice
  applicativo, CI (`vendor/bin/pest --coverage`) non ha questo limite.
- **CRITICO — `DB_CONNECTION`/`DB_HOST`/ecc. dentro il container `app` sono variabili d'ambiente REALI del
  processo** (iniettate da `env_file: .env` alla creazione del container), non solo valori in `.env`. Gli
  `<env>` di `phpunit.xml` (`DB_CONNECTION=sqlite`, ecc.) **non hanno `force="true"`**: PHPUnit non
  sovrascrive una variabile già presente nel processo reale, quindi `docker compose exec app vendor/bin/pest`
  può girare silenziosamente contro il vero Postgres di sviluppo. Effetto collaterale serio:
  `RefreshDatabase` esegue `migrate:fresh` sul primo test, **cancellando i dati reali del database di
  sviluppo**. Sintomo tipico: decine di failure spurie trasversali (`SQLSTATE[25P02]`, ruoli Spatie non
  assegnati). Workaround verificato: `docker compose exec -T app env -u DB_CONNECTION -u DB_DATABASE -u
  DB_HOST -u DB_PORT -u DB_USERNAME -u DB_PASSWORD php -d memory_limit=512M vendor/bin/pest`. Non ancora
  corretto a livello di `phpunit.xml`/`docker-compose.yml` (story dedicata da fare) — isolare sempre così
  prima di interpretare un test fallito come una regressione.
- **`Filament\Schemas\Concerns\InteractsWithSchemas::fillFormDataForTesting()` (chiamato da `->fillForm()`
  nei test Livewire) NON applica più correttamente lo stato in questo ambiente, su QUALUNQUE form Filament
  del repo** — preesistente (verificato con `git stash`), causa non isolata (possibile regressione
  `filament/filament`/`livewire/livewire` in `composer.lock`, mai bisecata). Per qualunque nuovo test che
  compila un form Filament: usare `->set('data.<campo>', valore)` campo per campo invece di `->fillForm([...])`.
  Manifesta diversamente fra PHP 8.4 (container Docker) e 8.5 (host locale): isolare sempre con
  `--filter=<Dominio>` sia su host sia nel container prima di dichiarare una regressione da una story.
- **`Illuminate\Console\Scheduling\Event` (Laravel 13.22) non ha un metodo `timeout()`**: chiamarlo su una
  entry `Schedule::command(...)` qualsiasi fa fallire l'intero bootstrap di Laravel con
  `BadMethodCallException` (anche `php artisan`/Larastan smettono di funzionare). Per un timeout esplicito,
  `set_time_limit(...)` dentro `handle()` del comando; `->withoutOverlapping()` esiste ed è corretto per
  l'anti-overlap.
- **`->having('alias', ...)` su un alias di `selectRaw` fallisce su Postgres ma non su sqlite**
  (`SQLSTATE[42703]: Undefined column`, nessun test lo intercetta se lo schema v2 dei test è sempre sqlite):
  usare sempre `->havingRaw('<espressione letterale>')`, mai riferire l'alias.
- **Larastan non inferisce il tipo backed-enum di un attributo castato con `protected function
  casts(): array`** (stile Laravel 11+) a meno che `parseModelCastsMethod: true` non sia abilitato in
  `phpstan.neon` (lo è) — senza, ogni `match()`/confronto sull'enum fallisce silenziosamente. Non rimuovere
  il flag se un futuro upgrade di Larastan ne cambia il default.
- **Notifiche database Filament rotte su Postgres reale**: `->databaseNotifications()` (abilitato da US-312)
  usa `->>'format'` su `notifications.data`, ma quella colonna è `text` (non `json`/`jsonb`) —
  `SQLSTATE[42883]: operator does not exist: text ->> unknown`. Rompe OGNI pagina Filament autenticata contro
  Postgres reale. **MAI ancora corretto** (richiede una nuova migrazione per cambiare il tipo colonna, mai
  modificare quella già committata) — verificare/disabilitare temporaneamente `->databaseNotifications()`
  quando serve una verifica in browser autenticata contro Postgres, poi ripristinare (`git diff` vuoto prima
  di committare).
- **Full `php artisan test` (Unit+Feature insieme) crasha in modo deterministico e preesistente** subito dopo
  `MailPipelineConfigTest`, dentro `tests/Unit/Domain/Mail/Parsers/*` (`symfony/html-sanitizer` →
  `\Dom\HTMLDocument`, API DOM nativa PHP 8.4) — OOM o `exit 255` senza output anche con `memory_limit=512M`.
  Ogni file preso singolarmente passa. Per verificare "Tests pass" su una story che non tocca Mail: eseguire
  i test del proprio dominio mirati, non fidarsi del codice di uscita di una run senza filtri.
- `pdflatex`/`csquotes.sty` mancano sull'host locale di sviluppo (non in Docker/CI): vedi
  `docs/collaudo/CLAUDE.md`.

## Verifica in browser (Chrome headless / Playwright)

- **Screenshot statico senza MCP**: `"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
  --headless --disable-gpu --no-sandbox --screenshot=/tmp/xyz.png --window-size=1440,1024
  "http://127.0.0.1:PORT/percorso"` (con `php artisan serve` + `SESSION_DRIVER=array CACHE_STORE=array
  QUEUE_CONNECTION=sync` per una pagina pubblica), poi ispezionare il PNG con il tool Read. Basta per una
  pagina pubblica (login), ma NON permette di cliccare un bottone/riempire un modale (nessuna interazione JS
  scriptabile da CLI).
- **Interazione autenticata (Action Filament, modale, form)**: Playwright, già installabile via
  `npx --yes playwright install chromium` (cache in `~/Library/Caches/ms-playwright/`, nessun tool MCP
  necessario) — uno script `.mjs` in `/tmp` (mai nel repo) con `chromium.launch()` → `page.goto('/admin/login')`
  → `fill`/`click` credenziali → naviga al record target → click sul bottone dell'action (attenzione al
  bottone di submit DENTRO al modale, non il trigger dietro, che ha lo stesso testo) → screenshot.
- I container Docker (`app`/`web`/`db`) non sono necessariamente già `Up`: verificare con `docker compose ps`
  prima di assumere che `http://localhost:8080` risponda; avviare con `docker compose up -d app web db` (la
  porta è pubblicata da `web`, non da `app`; `db` non pubblica porta host, quindi `php artisan serve` locale
  con `DB_HOST=db` non funziona).
- Utente/dati di verifica creati via `docker compose exec -T app php artisan tinker` (assegna ruolo con
  `Role::findOrCreate` + `syncPermissions`): rimuoverli sempre con `forceDelete()` subito dopo lo screenshot.
- **Selettori Playwright per Filament**: tab di un `ListRecords` → `page.getByRole('tab', { name: '...' })`;
  bottone filtri tabella (nessun testo visibile) → `aria-label="Filtro"`; se un selettore per testo esatto va
  in "strict mode violation", è quasi sempre perché un `<select>` di un `TernaryFilter` contiene un
  `<option>` con lo stesso testo — disambiguare con `getByRole` invece di `getByText`.
- **`waitForLoadState('networkidle')` può dare un falso positivo** su un campo `->live()` senza `onBlur:
  true`: aggiungere un `waitForTimeout(800-1200ms)` esplicito DOPO il cambio, PRIMA di `networkidle`. Un
  `RelationManager` che si carica via `x-intersect` (lazy, IntersectionObserver) mostra un placeholder
  `role="status" aria-busy="true"` che uno screenshot `fullPage: true` può catturare per errore: usare
  `scrollIntoViewIfNeeded()` + `waitForSelector(..., { state: 'detached' })` prima dello screenshot.
- **Non fidarsi di `page.click(...)` seguito subito da `page.url()` dopo un submit che redirige**: usare
  `page.waitForURL(url => ...)` con una condizione esplicita (rischio concreto: doppio submit, record
  duplicato).
- `Filament\Pages\Concerns\CanAuthorizeAccess::mount()` chiama `abort_unless(static::canAccess(), 403)`
  SEMPRE su una pagina custom, non solo per la visibilità in navigazione: un test/script con permessi
  insufficienti per `canAccess()` fallisce con 403 anche se l'azione che si vuole testare richiede solo un
  permesso diverso — concedere sempre ENTRAMBI i permessi coinvolti.
- `Livewire::test(NomeRelationManager::class, [...])->callTableAction('attach', data: [...])` non è più
  affidabile in questo ambiente — vedi `app/Domain/Fundraising/CLAUDE.md` §US-507 per il workaround
  (`mountTableAction`/`set('mountedActions.0.data.recordId', ...)`/`callMountedTableAction`).
- `php artisan tinker` gira con `memory_limit=128M` di default: per una risposta HTTP con payload grande
  (più PDF in base64, ecc.), usare `php -d memory_limit=512M artisan tinker --execute="..."`.

## Processo di collaudo (obbligatorio per ogni fase, Fase 2 in poi)

Ogni fase completata deve produrre, prima di essere considerata chiusa (dettaglio operativo completo in
`docs/collaudo/CLAUDE.md`):

1. `docs/collaudo/fase-<N>.php` — manifest topic → test numerati → riferimento a un test automatico
   REALMENTE esistente (`php artisan collaudo:verify-manifest <N>` deve passare).
2. Il manuale narrativo di collaudo (`docs/collaudo/0N-fase-N.md`) — SEMPRE l'ULTIMO passo dello sviluppo di
   una fase, mai in parallelo alle story.
3. `php artisan collaudo:generate <N>` — PDF di collaudo con carta intestata (via pdfLaTeX).
4. Il deploy su UAT (automatico al merge su `develop`) deve riflettere lo stato descritto nel manifest:
   l'ETL reale (`migrate:fresh` → `RolePermissionSeeder` → `v1:import --anonymize`) gira ad ogni deploy.
5. Se un test del collaudo fallisce durante una sessione di collaudo reale, il test automatico corrispondente
   va rivisto: non copriva il caso reale che ha fatto fallire il collaudo.
