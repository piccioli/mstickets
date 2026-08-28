<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Documentazione

Questo repository (Laravel 13 + Filament 4, PHP 8.4, PostgreSQL 16, Redis 7/Horizon) sostituisce un
gestionale ticket Nova ("v1"), riscritto da zero con un'architettura a moduli di dominio
(`app/Domain/<Modulo>/{Models,Actions,Enums,Policies,...}`). Dettagli completi e verificati contro il
codice in `docs/`:

| Documento | Contenuto |
|---|---|
| [`docs/architecture.md`](docs/architecture.md) | stack, struttura a moduli, principi architetturali vincolanti (A1-A9) |
| [`docs/data-model.md`](docs/data-model.md) | schema completo, diagramma ER, mappa dei nomi v1→v2 |
| [`docs/ticket-lifecycle.md`](docs/ticket-lifecycle.md) | macchina a stati del ticket, transizioni manuali e automatiche |
| [`docs/time-tracking.md`](docs/time-tracking.md) | algoritmo di calcolo delle ore lavorate |
| [`docs/email.md`](docs/email.md) | sottosistema email (inbound/outbound, catalogo comunicazioni E1-E11) |
| [`docs/authorization.md`](docs/authorization.md) | i tre livelli di autorizzazione (permesso/Policy/campo), MFA, impersonation |
| [`docs/import-v1.md`](docs/import-v1.md) | procedura ETL dal dump v1 (`v1:inspect`/`v1:import`/`v1:validate`) |
| [`docs/operations.md`](docs/operations.md) | deploy, scheduler, coda, backup, diagnostica, MFA/impersonation operativi, cutover |
| [`docs/differences-from-v1.md`](docs/differences-from-v1.md) | differenze di comportamento e bug del v1 corretti |
| [`docs/design-system.md`](docs/design-system.md) / [`docs/design-inventory.md`](docs/design-inventory.md) | identità visiva e inventario del design importato |

## Setup rapido (`make setup`)

Un solo comando porta l'ambiente da zero (nessun volume/vendor/node_modules preesistente) a navigabile
(§4.2 del PRD) con **dati reali**, non un seed fittizio: build delle immagini, dipendenze PHP e frontend,
chiave applicativa, `db_legacy` con l'ultimo dump v1 reale disponibile e l'ETL completo
(`php artisan v1:import --anonymize`, design in
[`docs/superpowers/specs/2026-08-02-etl-real-data-seeding-design.md`](docs/superpowers/specs/2026-08-02-etl-real-data-seeding-design.md)).

```bash
make setup
```

Richiede, oltre a Docker e Node.js sul host, che **`v1dumps/latest.sql` esista già** (convenzione descritta
sotto): il target fallisce subito con un messaggio esplicito se manca, invece di procedere con dati
fittizi. Rilanciabile senza distruggere dati esistenti: `.env` non viene sovrascritto se già presente, il
reset di `db_legacy`, le migrazioni e l'ETL sono già idempotenti di loro. Gli allegati restano
**best-effort**: se `storage/app/v1-media/` è vuota (nessuno ha ancora lanciato `bin/fetch-legacy-media`),
il setup non fallisce, l'ETL segnala solo i media come compromesso.

L'app è raggiungibile su `http://localhost:8080/admin`. Nome/email/contenuti di ogni utente e ticket
importato sono quelli reali del dump v1 (US-R08): solo la password cambia, impostata a `uat` per
**tutti** gli utenti importati con `--anonymize`. Per il login, usare l'email reale di un utente noto
(vedi `docs/collaudo/00-istruzioni-generali.md` per le identità di riferimento del collaudo) oppure
individuarne una con una query diretta su `users`. Il pannello riflette il tema Montagna Servizi importato
in US-004/US-005 (palette teal, font Nunito Sans, logo), non il tema di default di Filament.

### Convenzione del dump corrente: `v1dumps/latest.sql`

`v1dumps/` è gitignored: i dump reali non vanno mai versionati. `v1dumps/latest.sql` è un puntatore fisso
(symlink o copia, a scelta) al dump v1 reale più recente, mantenuto **manualmente** da un umano con accesso
SSH a produzione — nessuno script (né `make setup` né il deploy UAT) lo aggiorna da solo:

```bash
scp ms:/percorso/dump.sql v1dumps/production_dump_YYYYMMDD_HHMMSS.sql
ln -sf production_dump_YYYYMMDD_HHMMSS.sql v1dumps/latest.sql
```

Stessa convenzione riusata identica in locale e su UAT (vedi `bin/load-v1-dump` e il deploy remoto): chi
deve "usare l'ultimo dump" legge sempre questo path fisso, mai un pattern di data.

## Deploy UAT

Ogni push su `develop` esegue `deploy/remote-deploy.sh` su `msuat` (comando forzato via `authorized_keys`,
non l'output del workflow GitHub Actions), che ripristina **sempre l'ETL reale da zero** ad ogni deploy
(nessun dato persistente tra un push e l'altro):

```bash
docker compose -f docker-compose.uat.yml --env-file .env.uat up -d --wait   # attende gli healthcheck, incluso db_legacy
docker compose -f docker-compose.uat.yml --env-file .env.uat exec -T app php artisan migrate:fresh --force
docker compose -f docker-compose.uat.yml --env-file .env.uat exec -T app php artisan db:seed --class=RolePermissionSeeder --force
docker compose -f docker-compose.uat.yml --env-file .env.uat exec -T app php artisan v1:import --anonymize
```

`docker-compose.uat.yml` aggiunge, rispetto allo stack applicativo, la stessa infrastruttura ETL già
disponibile in locale, ma sempre attiva (non dietro un profilo, perché qui serve a ogni deploy):

- **`db_legacy`** (`postgres:16-alpine`, healthcheck `pg_isready`, volume dedicato `db_legacy_data`):
  sorgente v1 in sola lettura, popolata su `msuat` con `v1dumps/latest.sql` + `bin/load-v1-dump` da un
  umano con accesso SSH a produzione — nessuna automazione la aggiorna da sola (stessa convenzione del
  paragrafo precedente).
- Un bind-mount dedicato sul servizio `app` per gli allegati v1 reali (`LEGACY_MEDIA_HOST_PATH` in
  `.env.uat`, di default `/opt/mstickets-uat/v1-media` sul disco host di `msuat`), popolato con
  `bin/fetch-legacy-media`.

Variabili `.env.uat` rilevanti (vedi `.env.uat.example`): `DB_LEGACY_HOST`/`DB_LEGACY_PORT`/
`DB_LEGACY_DATABASE`/`DB_LEGACY_USERNAME`/`DB_LEGACY_PASSWORD` (connessione a `db_legacy`) e
`LEGACY_MEDIA_HOST_PATH` (path host degli allegati, non l'env var applicativa `LEGACY_MEDIA_PATH` letta
dentro al container).

`docker-compose.uat.yml`/`.env.uat.example`/`deploy/remote-deploy.sh` sono la fonte di verità versionata in
questo repository: un umano con accesso SSH copia manualmente il contenuto aggiornato su `msuat` quando
cambia, nessuna automazione sincronizza da sola questi file sul server reale.

**`docs/collaudo/*` non si aggiornano nel corso ordinario di una story**: il pacchetto di collaudo
cresce solo al checkpoint di fine fase (l'ultima story di ogni fase, es. US-219/US-326/US-411/
US-509/US-618 — vedi CLAUDE.md, "Collaudo di fine fase"), che estende il manifest/manuale della
fase appena conclusa e aggiorna il pacchetto cumulativo (istruzioni generali, matrice di
tracciabilità, registro esiti, verbale). Nessuna story ordinaria a metà fase deve toccarli.

## Docker

Ambiente di sviluppo containerizzato (§4.2 del PRD): `app` (PHP 8.4-FPM), `web` (nginx, unico entrypoint HTTP), `db` (Postgres 16), `redis` (Redis 7), `queue` (Horizon), `scheduler` (`schedule:work`), `mailpit` (SMTP+IMAP locale).

`make setup` esegue già tutti i passi seguenti in sequenza; questi comandi restano utili per gestire lo
stack manualmente (rebuild di una sola immagine, restart di un servizio, ecc.):

```bash
cp .env.example .env
docker compose up -d --build
```

L'app è raggiungibile su `http://localhost:8080`, la UI di Mailpit su `http://localhost:8025`. Nessun servizio gira come `root` e non è richiesto alcun `chown` manuale: il mismatch UID/GID tra host e container è risolto in build-time passando `WWWUSER`/`WWWGROUP` (UID/GID dell'utente host) come build arg del servizio `app`.

### Database di appoggio per il dump v1 (`db_legacy`)

Il servizio `db_legacy` (Postgres 16, §4.2 / §11.1 principio P2 del PRD) ospita il dump v1 in **sola
lettura**, isolato dall'esercizio normale: non parte con `docker compose up`, solo col profilo Compose
dedicato `etl`. `make setup` lo avvia e carica `v1dumps/latest.sql` già da solo (vedi sopra); i comandi
seguenti restano utili per gestirlo a parte (es. per rinfrescare il dump senza rieseguire tutto il setup):

```bash
make etl-up                       # avvia (solo) il servizio db_legacy
bin/load-v1-dump path/to/dump.sql # ripristina il dump SQL in db_legacy (avvia da solo db_legacy)
```

L'ETL (Fase 2+) non scrive mai sul database v1: `db_legacy` è la sorgente in sola lettura usata da tutto il
codice di importazione successivo (`app/Import/`, comandi `v1:inspect`/`v1:import`/`v1:validate`).

Prima di finalizzare lo schema v2 (§0.1 punto 5), ispezionare il dump reale con:

```bash
docker compose exec app php artisan v1:inspect
```

Il report viene salvato in `storage/app/import/inspect-<timestamp>.md` (conteggi per tabella, formati di
`users.roles`, parsing di `stories.customer_request`, FK orfane, ecc.) ed è versionato nel repository per essere
allegato alla PR di questa fase. Il report generato su un dump v1 reale di questa fase è
[`storage/app/import/inspect-20260725_225710.md`](storage/app/import/inspect-20260725_225710.md).

### Password fissa fuori produzione (`--anonymize`, ridefinito da US-R08)

`--anonymize` su `php artisan v1:import` impone a **ogni** utente importato la stessa password fissa nota
(`uat`, hash Laravel via `App\Import\Security\FixedPasswordHasher`), mai l'hash v1 reale. Nome, email e
contenuti (messaggi, ticket, ecc.) **non vengono mai alterati**: restano sempre quelli reali del dump v1,
con o senza `--anonymize` — a differenza del design originale (US-217, §11.8 del PRD), che anonimizzava
anche l'identità. La sola password resta un segreto degno di nota: mai l'hash v1 reale fuori produzione.

**`--anonymize` è OBBLIGATORIO per ogni esecuzione di `v1:import` in un ambiente non di produzione**
(sviluppo, staging, CI): fuori produzione nessun utente deve poter accedere con la propria password v1
reale. Solo l'import verso l'ambiente di produzione reale può ometterlo (mantiene l'hash v1 as-is, un vero
cutover).

Un guard applicativo indipendente (`App\Support\Mail\BlockRealRecipientsOutsideProduction`, registrato in
`AppServiceProvider::boot()`) blocca **qualunque** invio email dell'applicazione verso un indirizzo il cui
dominio non è in `MAIL_TEST_DOMAINS` (`.env`) quando `APP_ENV !== production`: dato che gli utenti importati
hanno ora email reali, questo guard è la sola protezione contro l'invio accidentale di una notifica verso
un cliente/collega reale durante un test/uno sviluppo locale.

## Generazione PDF

I PDF applicativi (documentazione, §6.4.3 del PRD; report attività) usano
[`spatie/laravel-pdf`](https://spatie.be/docs/laravel-pdf) con il driver
[`chrome-php/chrome`](https://github.com/chrome-php/chrome) — non `browsershot`
(il driver predefinito del pacchetto), non `barryvdh/laravel-dompdf`, non
`pdflatex`. Motivazione della scelta:

- **`dompdf` è stato scartato**: in questo stesso repository è già stato
  abbandonato per il PDF di collaudo perché incapace di riprodurre fedelmente
  la carta intestata Montagna Servizi (CSS moderno/flexbox renderizzato in modo
  approssimativo). La sua sostituzione lì, `pdflatex`, non è un'opzione qui:
  è deliberatamente escluso dall'immagine UAT/produzione (solo `docker/php/Dockerfile`
  di sviluppo lo include), mentre questi PDF devono generarsi in coda in
  produzione per utenti reali, non solo in fase di collaudo manuale.
- **`browsershot` (Chromium via Node.js/Puppeteer) è stato scartato** a favore
  di `chrome-php/chrome`: entrambi guidano Chromium headless e renderizzano
  correttamente CSS moderno riusando gli stessi componenti/stili del design
  system (`resources/css/theme.css` via `App\Support\DesignTokens`, la stessa
  fonte già usata dal layout email), ma `chrome-php/chrome` parla il Chrome
  DevTools Protocol in **puro PHP**: l'unica dipendenza di runtime è il binario
  Chromium stesso, senza un secondo runtime Node.js/Puppeteer da mantenere nelle
  immagini Docker. Node.js esiste già in questo repo, ma solo come stage di
  build (`docker/uat/Dockerfile`, compilazione degli asset Vite) scartato prima
  dell'immagine finale — reintrodurlo a runtime solo per i PDF avrebbe
  appesantito l'immagine di produzione senza necessità.

Chromium headless è installato in entrambe le immagini (`apk add chromium`):
`docker/php/Dockerfile` (sviluppo) e `docker/uat/Dockerfile` (produzione, stage
finale FrankenPHP). Il percorso del binario è auto-scoperto da `chrome-php`
(`LARAVEL_PDF_CHROME_BINARY` vuoto in `.env.example`): funziona così sia dentro
i container Alpine sia su un Mac di sviluppo con Google Chrome installato, senza
un percorso hardcoded. `LARAVEL_PDF_CHROME_NO_SANDBOX=true` è necessario in
qualunque container, incluso quello di sviluppo che gira come `www-data` non
root (`docker/php/Dockerfile:50`): il sandbox di Chrome richiede di creare
user/PID namespace, syscall che il runtime container nega di norma a prescindere
dall'utente — non un problema specifico di root, verificato di persona durante
la verifica in browser di questa story (il job falliva con "Operation not
permitted" sulla creazione del namespace anche da `www-data`).

## Punto di controllo obbligatorio prima della Fase 1

La Fase 0 (Fondazioni) non introduce nessuna business logic di dominio: prima di iniziare la Fase 1
(Ticketing core) è **richiesta una conferma esplicita del committente** su:

- [`docs/design-inventory.md`](docs/design-inventory.md) — inventario di ogni schermata/componente del
  mockup importato, con classificazione in-scope/fuori-scope per questa release;
- il report di `v1:inspect` referenziato sopra — cosa smentisce il dato reale v1 rispetto al modello
  dichiarato, prima di finalizzare lo schema v2.

Nessun lavoro della Fase 1 va iniziato prima di questa conferma (§0.1 punto 4 del PRD).

## CI

Ogni pull request esegue `.github/workflows/ci.yml` (GitHub Actions), che deve essere verde prima del merge:

1. **Pint** (`vendor/bin/pint --test`) — stile del codice, preset `laravel`.
2. **Larastan** (`vendor/bin/phpstan analyse --memory-limit=1G`) — analisi statica a livello 6.
3. **Pest con coverage** (`vendor/bin/pest --coverage`) — suite di test (driver di coverage `pcov`).
4. **ETL su fixture ridotta** (job `etl-fixture`, US-218) — vedi sotto.
5. **Build dei container Docker** (`docker compose build app`) — verifica che l'immagine PHP-FPM buildi senza errori.

La pipeline fallisce se uno qualunque di questi step fallisce.

### Job ETL dedicato (`etl-fixture`, US-218)

Il job `etl-fixture` gira in parallelo al job `quality` e verifica l'intera pipeline `v1:import`/
`v1:validate` **contro un vero servizio Postgres** (un container `postgres:16-alpine` come connessione
`legacy`, non sqlite): il dump reale di produzione non può essere usato in CI (troppo grande/sensibile,
`v1dumps/` è in `.gitignore`), quindi la pipeline gira invece su una fixture ridotta, già anonimizzata alla
creazione, versionata in
[`tests/Fixtures/Import/v1-ci-fixture.sql`](tests/Fixtures/Import/v1-ci-fixture.sql).

Il job, in ordine:

1. carica la fixture in un servizio `db_legacy` effimero (`psql -f tests/Fixtures/Import/v1-ci-fixture.sql`);
2. esegue `php artisan v1:import --anonymize` **due volte consecutive** e verifica che la seconda esecuzione
   non crei/aggiorni nessuna riga (idempotenza dimostrata direttamente in pipeline, non solo nel test Pest
   `tests/Feature/Console/V1ImportPipelineIdempotencyTest.php` che copre lo stesso principio contro una
   connessione `legacy` sqlite);
3. esegue `php artisan v1:validate` e richiede che esca con successo (zero controlli di integrità falliti:
   conteggi, FK orfane, enum fuori catalogo, unicità, media mancanti su disco — vedi
   `app/Console/Commands/V1ValidateCommand.php`);
4. verifica che il report generato segnali comunque, con un conteggio non a zero, i compromessi noti che la
   fixture introduce apposta (vedi sotto): l'obiettivo di questi casi è che vengano **rilevati e contati**,
   non che spariscano.

La fixture copre esplicitamente i casi limite già documentati in
[`storage/app/import/inspect-20260726_101916.md`](storage/app/import/inspect-20260726_101916.md) (Fase 0):
un valore di `type`/`priority` fuori catalogo, `users.roles` con `"editor"`, un `customer_request` non
parsabile, un conflitto di gerarchia `story_story`/`stories.parent_id`, un media orfano (file assente su
disco) e un media con `model_type` diverso da `Story`. **Deliberatamente esclusa**: un'email duplicata a
meno del case — nel dato reale non se n'è mai vista una, e a differenza degli altri casi non produce un
semplice warning ma fa fallire il controllo di unicità di `v1:validate` (comportamento corretto e voluto:
sarebbe una vera anomalia da correggere a mano su un dump reale), quindi non può convivere con l'AC "il
comando esce con successo" in questa fixture. Resta comunque coperta a livello di stage da
`tests/Feature/Import/Stages/UsersStageTest.php`.

**Come rigenerare/estendere la fixture se lo schema v1 cambia**: individuare le colonne reali con
`grep -n "CREATE TABLE public.<tabella>"` sul dump non compresso più recente in `v1dumps/` (mai il solo
report `v1:inspect`, che non elenca tutte le colonne), poi aggiornare sia le `CREATE TABLE` sia i dati in
`tests/Fixtures/Import/v1-ci-fixture.sql`. Per verificarla in locale prima di aprire una PR (serve
`docker compose --profile etl` con `db_legacy` avviato):

```bash
make etl-up
docker compose --profile etl exec db_legacy psql -U orchestrator_legacy -d orchestrator_legacy \
  -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public;"
docker compose --profile etl exec -T db_legacy psql -v ON_ERROR_STOP=1 -U orchestrator_legacy \
  -d orchestrator_legacy < tests/Fixtures/Import/v1-ci-fixture.sql
docker compose exec app php artisan v1:import --anonymize
docker compose exec app php artisan v1:import --anonymize   # deve creare/aggiornare zero righe
docker compose exec app php artisan v1:validate              # deve uscire con successo
```

Il badge di stato verrà aggiunto al README non appena il repository avrà un remote GitHub configurato (`https://github.com/<org>/<repo>/actions/workflows/ci.yml/badge.svg`).

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
