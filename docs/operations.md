# Operazioni

Fonte: `routes/console.php` (registrazione scheduler, fonte di verità), `docker-compose.yml`/
`docker-compose.uat.yml`, `deploy/remote-deploy.sh`, `Makefile`, `app/Support/Doctor/`, `CLAUDE.md`
(sezioni US-022, US-606, US-607, checkpoint di Fase 6). Questo documento descrive **come si opera
oggi** questo sistema (sviluppo, UAT, e cosa manca per un vero cutover in produzione), non un piano.

## Deploy

### Sviluppo locale

`make setup` (vedi `README.md` → "Setup rapido") porta l'ambiente da zero a navigabile con dati reali:
build immagini, dipendenze, `db_legacy` popolato da `v1dumps/latest.sql`, migrazioni, seeder ruoli,
`v1:import --anonymize`. Idempotente, rilanciabile.

### UAT (`msuat`)

Ogni push su `develop` esegue `deploy/remote-deploy.sh` (comando forzato via `authorized_keys` su
`msuat`, copiato a mano da un umano con accesso SSH — nessuna pipeline CI lo invoca direttamente):

```bash
docker compose -f docker-compose.uat.yml --env-file .env.uat pull
docker compose -f docker-compose.uat.yml --env-file .env.uat up -d --wait
docker compose -f docker-compose.uat.yml --env-file .env.uat exec -T app php artisan migrate:fresh --force
docker compose -f docker-compose.uat.yml --env-file .env.uat exec -T app php artisan db:seed --class=RolePermissionSeeder --force
docker compose -f docker-compose.uat.yml --env-file .env.uat exec -T app php artisan v1:import --anonymize
docker compose -f docker-compose.uat.yml --env-file .env.uat exec -T app php artisan collaudo:ensure-manager-account
```

**Ogni deploy UAT riparte da zero**: `migrate:fresh` + ETL completo, nessun dato persistente tra un
push e l'altro (design intenzionale, non un limite tecnico — vedi `docs/superpowers/specs/2026-08-02-etl-real-data-seeding-design.md`).
Questo significa che **UAT non richiede backup**: il suo intero stato è rigenerato dal dump v1 +
dalle migrazioni ad ogni deploy, mai da una modifica manuale da conservare.

`docker-compose.uat.yml` differisce dallo stack di sviluppo in un punto rilevante per la coda:
il servizio `worker` esegue `docker/uat/worker-entrypoint.sh`, che lancia **`php artisan queue:work`**
+ **`php artisan schedule:work`** in due `restart_loop` distinti nello stesso processo (mai Horizon in
UAT), mentre in sviluppo (`docker-compose.yml`) i due ruoli sono separati in due container dedicati:
`queue` (`php artisan horizon`) e `scheduler` (`php artisan schedule:work`). Horizon (dashboard e
metriche coda) è quindi disponibile solo in sviluppo/produzione futura con lo stack "completo", non
sull'immagine UAT attuale.

## Scheduler

Fonte di verità: `routes/console.php`. Ogni comando è registrato con `Schedule::command(...)
->withoutOverlapping()->when(fn (): bool => (bool) config('orchestrator.features.<flag>'))`: **nessun
comando gira per davvero finché il proprio feature flag non è `true`** (default `false` per tutti, in
`config/orchestrator.php` — abilitarlo è una scelta di deploy, mai un default applicativo). La cadenza
di ognuno è sempre letta da `config()`, mai un'espressione cron letterale in `routes/console.php`.

| Comando | Cadenza (default) | Flag (env) | Cosa fa |
|---|---|---|---|
| `mail:fetch-inbound` | `*/5 * * * *` (`mail_pipeline.fetch.schedule_cron`) | `ENABLE_MAIL_FETCH_INBOUND` | orchestra l'intera pipeline inbound (vedi `docs/email.md`) |
| `tickets:remind-waiting` | `0 6 * * *` (`ticketing.waiting_reminder.schedule_cron`) | `ENABLE_TICKETS_WAITING_REMINDERS` | E7, reminder al richiedente di un ticket `waiting` da ≥ 3 giorni lavorativi |
| `mail:retry-failed` | `0 * * * *` (`mail_pipeline.retry.schedule_cron`) | `ENABLE_MAIL_RETRY_FAILED` | reinvia i messaggi outbound `failed` |
| `reports:generate-monthly` | (`reporting.monthly_schedule_cron`) | `ENABLE_REPORTS_MONTHLY` | genera i report di attività del mese precedente per ogni owner attivo |
| `tickets:progress-to-todo` (T3) | `0 18 * * *` (`ticketing.progress_to_todo.schedule_cron`) | `ENABLE_TICKETS_PROGRESS_TO_TODO` | riporta a `todo` i ticket rimasti `progress` a fine giornata |
| `tickets:auto-close-released` (T4) | `45 7 * * *` (`ticketing.auto_close_released.schedule_cron`) | `ENABLE_TICKETS_AUTO_CLOSE_RELEASED` | chiude in `done` i ticket `released` da ≥ `threshold_working_days` giorni lavorativi |
| `tickets:close-scrum` (T5) | `0 16 * * *` (`ticketing.close_scrum.schedule_cron`) | `ENABLE_TICKETS_CLOSE_SCRUM` | chiude in `done` i ticket `scrum` creati/aggiornati oggi |
| `tickets:archive-scrum` | `0 5 * * *` (`ticketing.archive_scrum.schedule_cron`) | `ENABLE_TICKETS_ARCHIVE_SCRUM` | archivia (`archived_at`) i ticket `scrum` `done` da ≥ `threshold_days` giorni di calendario |
| `tickets:restore-waiting` (T6) | `30 6 * * *` (`ticketing.restore_waiting.schedule_cron`) | `ENABLE_TICKETS_RESTORE_WAITING` | ripristina a `previous_status` i ticket `waiting` da ≥ `threshold_days` giorni di calendario |
| `timetracking:aggregate-daily` | `30 23 * * *` (`timetracking.aggregate_daily.schedule_cron`) | `ENABLE_TIMETRACKING_AGGREGATE` | consolida `ticket_work_logs` per i ticket con attività oggi |
| `mail:send-digest` | `0 7 * * *` (`mail_pipeline.digest.schedule_cron`) | `ENABLE_MAIL_DIGEST` | E8, digest giornaliero per i clienti che l'hanno abilitato |
| `tickets:notify-idle-developers` (E11) | `*/30 9-15 * * *` (`ticketing.idle_developer_notice.schedule_cron`) | `ENABLE_TICKETS_IDLE_DEVELOPER_NOTICE` | promemoria interno 09:00–15:30 per developer con ticket assegnati ma nessuno `progress` |

Tutti e dodici sono `withoutOverlapping()`: un'esecuzione già in corso (lock condiviso via cache)
impedisce una seconda sovrapposta, mai una race su due processi `schedule:run` concorrenti. Per
eseguirne uno manualmente (bypassando cron/flag) da CLI: `php artisan <comando>`, ogni comando resta
richiamabile indipendentemente dal proprio feature flag (il flag governa solo `Schedule::command()`,
non l'esecuzione diretta).

**Comando schedulato che non parte mai**: verificare prima il flag `config('orchestrator.features.*')`
(quasi sempre la causa — tutti `false` di default), poi che il container `scheduler` (sviluppo) o il
processo `schedule:work` dentro `worker` (UAT) sia effettivamente in esecuzione (`docker compose ps`).

## Coda (Horizon / `queue:work`)

- **Sviluppo**: container dedicato `queue` (`php artisan horizon`), monitorabile da `/admin` una volta
  autenticati con un ruolo che ha `Permission::HorizonAccess` — **nota importante**: questo permesso è
  nel catalogo (`App\Domain\Identity\Enums\Permission::HorizonAccess`, concedibile come permesso
  diretto) ma **non è collegato al gate reale di Horizon**. `App\Providers\HorizonServiceProvider::gate()`
  definisce `Gate::define('viewHorizon', fn ($user = null) => in_array(optional($user)->email, []))`:
  è lo stub di default generato da Laravel/Horizon, con la lista di email autorizzate ancora vuota.
  In pratica, in un ambiente non-`local` **nessun utente** (a prescindere dal permesso
  `horizon.access`) può aprire `/horizon` finché questo gate non viene collegato esplicitamente al
  permesso applicativo (es. `Gate::define('viewHorizon', fn ($user) => $user?->can(Permission::HorizonAccess->value) ?? false)`).
  È un gap noto, non ancora colmato da nessuna story: da correggere prima di affidarsi al permesso
  `horizon.access` per l'accesso reale alla dashboard.
- **UAT**: nessun processo Horizon (vedi sopra, `queue:work` semplice dentro `worker-entrypoint.sh`).
  Nessuna dashboard di monitoraggio coda disponibile su UAT oggi.
- **Riavvio dopo modifica `.env`**: un worker long-running (Horizon o `queue:work`) non rilegge da solo
  le variabili d'ambiente cambiate — serve un riavvio del processo (`docker compose restart queue` in
  sviluppo), non solo `php artisan config:clear` (vedi gotcha PDF/Chrome in `CLAUDE.md`, US-406).

## Backup

- **`db_legacy` (dump v1)**: non è un backup applicativo, è la sorgente di sola lettura dell'ETL. Il
  dump reale (`v1dumps/*.sql`/`*.tar.gz`) è mantenuto manualmente da un umano con accesso SSH a
  produzione v1 (`scp` + aggiornamento del symlink `v1dumps/latest.sql`), **mai committato**
  (`v1dumps/` è in `.gitignore`) e conservato come riferimento storico dei moduli non importati (D11).
- **UAT**: nessun backup necessario per il DB applicativo (`db`) — ogni deploy lo ricrea da zero
  (`migrate:fresh` + ETL). Gli allegati v1 reali (`LEGACY_MEDIA_HOST_PATH` sul disco host di `msuat`)
  sono invece uno stato persistente popolato da `bin/fetch-legacy-media`: nessuna automazione ne fa
  backup oggi, la fonte di verità resta la produzione v1 stessa (ri-eseguibile in caso di perdita).
- **Produzione reale**: non esiste ancora un ambiente di produzione v2 in questo repository (il
  cutover, sotto, non è stato eseguito). Una strategia di backup del database applicativo
  (`pg_dump` schedulato, retention, restore testato) **non è implementata da nessuna story**: è un
  prerequisito da definire esplicitamente prima del cutover, non assumere che "funzioni come UAT" (su
  UAT la perdita dati è per design irrilevante, in produzione reale non lo sarebbe).

## Diagnostica — `php artisan orchestrator:doctor`

Comando `orchestrator:doctor` (`app/Console/Commands/OrchestratorDoctorCommand.php`, US-022): esegue
in sequenza un elenco chiuso di controlli indipendenti (`App\Support\Doctor\Contracts\DoctorCheck`),
stampa un esito `[OK]`/`[FAIL]` per riga, ed esce con `1` se un qualunque controllo fallisce:

| Check | Cosa verifica |
|---|---|
| `EnvironmentVariablesCheck` | ogni voce di `config('orchestrator.required_env')` (APP_KEY, DB_*, REDIS_*, MAIL_*, ecc.) è valorizzata |
| `StorageWritableCheck` | i dischi applicativi sono scrivibili |
| `SystemUserCheck` | l'utente di sistema (`config('orchestrator.system_user.email')`, default `system@orchestrator.local`) esiste, senza ruoli/password (non deve poter accedere al pannello) — creato al volo se manca |
| `FeatureFlagsCheck` | riporta lo stato di ogni `config('orchestrator.features.*')` (solo informativo, non fa fallire il comando) |

Per aggiungere un controllo di una fase successiva (IMAP/SMTP, logo PDF, stato ultimo import): creare
una nuova classe `DoctorCheck` sotto `App\Support\Doctor\Checks\` e aggiungerla a
`OrchestratorDoctorCommand::CHECKS`, senza toccare le altre classi.

## MFA — setup e recovery (US-606, §6.7.2)

MFA nativa Filament (`Panel::multiFactorAuthentication([AppAuthentication::make()->recoverable()],
isRequired: true)` in `App\Filament\Providers\AdminPanelProvider`), autenticazione da app (TOTP),
**obbligatoria solo per i ruoli elencati in `config('mfa.required_roles')`** (env `MFA_REQUIRED_ROLES`,
CSV di valori `UserRole`, default vuoto — nessun ruolo obbligato di default, un ruolo non elencato può
comunque attivarla volontariamente).

- **Perché `isRequired: true` sempre, e non una closure per-ruolo**: il parametro nativo `isRequired`
  è valutato alla registrazione delle route (boot dei provider), prima che la sessione/`Auth::user()`
  siano disponibili — una closure lì vedrebbe sempre un utente `null`. La logica per-ruolo vive invece
  in un middleware custom, `App\Filament\Auth\Middleware\EnsureRoleRequiresMultiFactorAuthentication`
  (sostituisce `multiFactorAuthenticationRequiredMiddlewareName()`), che legge `Filament::auth()->user()`
  **a runtime** dentro `handle()` e delega al middleware nativo
  `EnsureMultiFactorAuthenticationIsEnabled` solo se il ruolo dell'utente è in `mfa.required_roles`.
- **Setup**: `->profile()` (pagina `Filament\Auth\Pages\EditProfile`, mai creata prima in questo repo)
  è l'unico punto che espone la UI di gestione MFA (QR code TOTP, generazione codici di recovery). Un
  utente con ruolo obbligato che non ha ancora configurato l'app authentication viene reindirizzato
  automaticamente alla schermata di setup al primo login successivo.
- **Login con sfida**: la pagina di login (`App\Filament\Auth\Pages\Login`) usa una view Blade
  completamente custom (brand Montagna Servizi, da un ciclo precedente a questa story) — **non** il
  meccanismo `content(Schema $schema)` nativo che normalmente mostra/nasconde il secondo step. La view
  è stata estesa con `@if ($this->userUndertakingMultiFactorAuthentication)` per renderizzare
  `{{ $this->multiFactorChallengeForm }}` in un secondo `<form wire:submit="authenticate">`: qualunque
  futura modifica a una pagina di auth con view custom in questo repo deve verificare che non stia
  bypassando un meccanismo nativo condizionale allo stesso modo.
- **Recovery**: i codici di recupero (`Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery`,
  implementato su `User`) sono generati dalla stessa UI di setup (`->recoverable()`) e persistiti in
  `users.app_authentication_recovery_codes` (cast `encrypted:array` — mai in chiaro a riposo, come
  `app_authentication_secret`, cast `encrypted`). Un utente che perde l'app authenticator usa uno dei
  codici di recovery al posto dell'OTP nella stessa schermata di sfida; consumarne uno lo rimuove
  dall'elenco persistito (comportamento nativo del provider Filament, non codice applicativo custom).
- **Verifica di un flusso MFA reale end-to-end** (setup + login con sfida) richiede di calcolare un
  OTP valido per il secret generato:
  `php artisan tinker --execute="echo app(PragmaRX\Google2FAQRCode\Google2FA::class)->getCurrentOtp('<secret>');"`.

## Impersonation (US-607, §6.7.2)

`stechstudio/filament-impersonate`, riservata a `Permission::UserImpersonate` (solo `admin` nella
matrice di `docs/authorization.md`). `User::canImpersonate()`/`canBeImpersonated()` delegano
interamente a `UserPolicy::impersonate()` (permesso) e al vincolo `deactivated_at === null`
(indipendente dall'attore) — nessun controllo di ruolo duplicato altrove. Ogni sessione è loggata
tramite gli eventi nativi del pacchetto (`EnterImpersonation`/`LeaveImpersonation`, mai un hook
Eloquent) da `App\Domain\Identity\Listeners\LogImpersonationStarted`/`LogImpersonationStopped`.

**Effetto collaterale noto, accettato consapevolmente** (non un difetto di questa story): finché
un'impersonation è attiva, `UserPolicy::viewAny()` inizia con
`if (Impersonation::isImpersonating()) { return true; }` — un guard richiesto dal pacchetto stesso
(bug noto documentato nel suo README, "403 quando una `ListUsers` widget ha `InteractsWithPageTable`").
Il guard è cieco rispetto a **chi** è impersonato: qualunque utente impersonato (anche un `customer`)
può navigare direttamente a `/admin/users` e vedere l'elenco completo di utenti reali, incluso
l'admin che lo sta impersonando. Non è uno sfruttamento praticabile da un attaccante esterno (la
sessione resta comunque guidata da un admin già privilegiato), ma è un compromesso non rimosso:
rimuovere il guard reintroduce il 403 spurio del pacchetto. Nessuna soluzione pulita nota con l'API
pubblica del pacchetto; flaggato per revisione esplicita col committente.

## Processo di cutover verso la produzione reale

Non esiste ancora, in questo repository, un ambiente di produzione v2 né un comando/script di
cutover dedicato: quanto segue è ricostruito da ciò che il codice già distingue esplicitamente tra
"non-produzione" e "produzione", non un processo eseguito.

- **`v1:import` senza `--anonymize`**: l'unico modo, oggi, in cui il codice distingue un vero cutover
  da un ambiente di sviluppo/staging/CI. Con `--anonymize` (obbligatorio ovunque tranne che verso la
  produzione reale) ogni utente importato riceve la password fissa nota (`uat`, via
  `App\Import\Security\FixedPasswordHasher`); senza, resta l'hash v1 reale — l'unico caso in cui
  questo è accettabile è un import verso l'ambiente che sostituisce davvero il v1 in produzione.
- **`App\Support\Mail\BlockRealRecipientsOutsideProduction`** (registrato in
  `AppServiceProvider::boot()`): blocca qualunque invio email verso un dominio non in
  `config('orchestrator.anonymization.mail_test_domains')` (env `MAIL_TEST_DOMAINS`) quando
  `APP_ENV !== 'production'`. Un vero cutover richiede quindi `APP_ENV=production` per poter
  effettivamente notificare gli utenti reali — nessun'altra configurazione lo abilita.
- **`app/Import/` è isolato apposta** (nessuna classe di dominio ne dipende, principio P3 di
  `docs/import-v1.md`) **per poter essere rimosso in blocco a cutover concluso**: nessuna story ha
  ancora eseguito questa rimozione, è un passo pianificato per una fase successiva (§14 Fase 7 del
  PRD), non ancora avvenuto in questo repository.
- **Backup di produzione**: da definire, vedi sopra — non presente.
- **Deploy verso msuat/produzione**: resta, per scelta esplicita del committente, un passo eseguito
  da un umano con accesso SSH (mai una pipeline automatica che tocchi `msuat` direttamente al di
  fuori del comando forzato `remote-deploy.sh` per l'ambiente UAT). Un eventuale ambiente di
  produzione distinto da UAT richiederebbe un proprio `docker-compose.<env>.yml`/`.env.<env>` e un
  proprio script di deploy, non ancora scritti in questo repository.

`docs/collaudo/*` restano esplicitamente fuori scope per l'automazione (il committente li aggiorna
direttamente a mano) — non toccarli da nessuna story di questo repository.

## Documenti correlati

- `docs/architecture.md` — struttura del codice e principi vincolanti.
- `docs/email.md` — pipeline email, comandi schedulati del modulo Mail.
- `docs/import-v1.md` — procedura ETL completa (`v1:inspect`/`v1:import`/`v1:validate`).
- `docs/authorization.md` — permessi `horizon.access`/`logs.access`, MFA, impersonation nel contesto
  dei tre livelli di autorizzazione.
- `docs/differences-from-v1.md` — perché T3-T6/E7-E11 sono comandi schedulati e non job/observer come
  nel v1.
