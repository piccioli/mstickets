# Architettura

Fonte: PRD-ORCHESTRATOR-V2.md §4 (Architettura tecnica), §4.3 (struttura applicativa), §4.4
(principi architetturali A1-A9), `CLAUDE.md` (decisioni e gotcha accumulati fase per fase). Questo
documento descrive **come il codice è organizzato oggi**, non un piano: ogni riferimento a un
percorso o a una classe è verificato contro il codice presente in questo repository.

## Stack

| Componente | Scelta | Note |
|---|---|---|
| PHP | 8.4 | `docker/php/Dockerfile` (sviluppo), `docker/uat/Dockerfile` (produzione, FrankenPHP) |
| Framework | Laravel 13 | |
| Admin panel | Filament 4 (stabile) | Tailwind CSS v4, MFA nativa, mai la beta v5 |
| Database | PostgreSQL 16 | connessione di default `pgsql` + connessione dedicata `legacy` per l'ETL |
| Cache / code / sessioni | Redis 7 | |
| Queue monitor | Laravel Horizon | processo `queue` in Docker |
| Media | `spatie/laravel-medialibrary` v11+ | dischi privati dedicati per contesto, mai `public` per contenuti riservati |
| PDF | `spatie/laravel-pdf` (driver `chrome-php/chrome`, non Browsershot) | vedi `README.md` → "Generazione PDF" per la motivazione della scelta |
| Email inbound | `webklex/php-imap` (libreria standalone, mai `webklex/laravel-imap`) | dietro l'interfaccia `InboundMailTransport` |
| Ruoli e permessi | `spatie/laravel-permission` v6+ | mai sostituisce le Policy — vedi `docs/authorization.md` |
| Impersonation | `stechstudio/filament-impersonate` | Filament 4 non la include nativamente |
| Sanitizzazione HTML | `symfony/html-sanitizer` (allowlist) | `TicketMessageSanitizer`, riusato anche dal parser email |
| Test | Pest 4.x (non 3.x: richiede supporto Laravel 13) | sintassi Pest, non classi PHPUnit |
| Static analysis | Larastan / PHPStan livello 6 | `phpstan.neon`, `parseModelCastsMethod: true` |
| Formatter | Laravel Pint, preset `laravel` | |
| Assets | Vite | tema Filament compilato in `resources/css/filament/admin/theme.css` |

Pacchetti esplicitamente vietati dal PRD e mai installati in questo repo: `spatie/laravel-google-calendar`,
`spatie/laravel-translatable`, `overtrue/laravel-favorite`, `filament-shield`.

## Struttura a moduli di dominio

```
app/
├── Domain/
│   ├── Ticketing/       Models, Enums, StateMachine, Actions, Queries, Events, Listeners, Rules, DTO, Support
│   ├── TimeTracking/     Models, Actions, Jobs, Listeners, Enums
│   ├── Tags/             Models, Actions, Enums, Listeners, Policies
│   ├── Documentation/    Models, Actions, Enums, Events, Jobs, Listeners, Policies
│   ├── Reporting/        Models (ActivityReport, Organization), Actions, Enums, Events, Jobs, Services, Policies
│   ├── Fundraising/      Models, Actions, Enums, Policies, Services, StateMachine
│   ├── Identity/         Models (User), Enums (UserRole, Permission), Listeners, Policies
│   ├── Mail/             Contracts, Transports, Parsers, Actions, Events, Listeners, Mailables, Models, Support, Policies
│   └── CaiDirectory/     Models (CaiSection/CaiSubsection/CaiRuntsRegistration/CaiFinancialStatement/
│                         CaiBoardMember/CaiDocument), Import (CaiDatapackImporter)
├── Import/                ETL dal v1 (§11) — Stages, Mappers, Parsers, Validation, Security, Models, Enums, Inspect
├── Filament/
│   ├── Resources/         una Resource per entità (Tickets, Users, Roles, Tags, DocumentationPages,
│   │                      ActivityReports, Organizations, FundraisingOpportunities/Projects,
│   │                      CustomerFundraisingOpportunities/Projects, EmailMessages, CaiSections —
│   │                      sola consultazione, nessun Create/Edit/Delete)
│   ├── Pages/              pagine senza Resource dietro (WorkBoard, Dashboard, CustomerDashboard,
│   │                      NotificationPreferences, EmailQuarantine, EmailSuppressions,
│   │                      CaiSectionsMap, CaiSectionRegionalDetail)
│   ├── Widgets/            (es. EmailPipelineMetricsOverview)
│   ├── Navigation/         classi pure per voci di navigazione condizionali (es. MailpitNavigationItem)
│   ├── Auth/               Pages/Login custom, Middleware MFA per ruolo
│   └── Providers/          AdminPanelProvider (namespace App\Filament\Providers, NON app/Providers/Filament/)
├── Support/                DesignTokens, Doctor (diagnostica), Latex (collaudo), Mail (guard anti-invio-reale), Pdf
├── Http/Controllers/        solo controller "a termine" fuori da Filament (es. download allegati)
└── Providers/               AppServiceProvider (wiring listener di dominio), MailServiceProvider, ImportServiceProvider
```

`app/Import/` è codice a termine (§4.3 del PRD, P3 di §11.1): isolato apposta, nessuna classe di
dominio ne dipende, pensato per essere rimosso in blocco a cutover concluso (§14 Fase 7). Le classi
`v1:inspect`/`v1:import`/`v1:validate` sono in `app/Console/Commands/`, non dentro `app/Import/`.

Ogni modulo di dominio ha, dove pertinente, la propria `Policies/` parallela a `Models/`: Laravel la
risolve per convenzione di naming (sostituendo `\Models\` con `\Policies\` nel namespace), senza
bisogno di un `AuthServiceProvider` (questo repo non ne ha uno).

## Principi architetturali vincolanti (A1-A9)

Questi principi esistono per correggere problemi specifici del v1 (§4.4 del PRD, si veda anche
`docs/differences-from-v1.md`). Sono vincoli, non suggerimenti: dove il codice sembra deviarne, è un
difetto da correggere, non un'eccezione tollerata.

| # | Principio | Applicazione concreta in questo repo |
|---|---|---|
| **A1** | Niente business logic negli hook Eloquent | Nessun `boot()`/`booted()`/Observer di dominio. Ogni mutazione passa da un'Action esplicita (`App\Domain\<Modulo>\Actions\*`, un metodo pubblico statico `run()` per classe). Esempi: `ChangeTicketStatus`, `PostTicketMessage`, `RecalculateWorkedTime`, `ArchiveTicket` |
| **A2** | Macchina a stati dichiarativa | `App\Domain\Ticketing\StateMachine\TicketStateMachine::transitions()`: un array statico di `Transition` (da/a/attori/guard/effetti), memoizzato. Nessun `if` sparso. Una transizione non ammessa produce sempre un `ValidationException` localizzato — vedi `docs/ticket-lifecycle.md` |
| **A3** | Validazione nel layer di validazione | Regole in `App\Domain\Ticketing\Rules\*` (`Illuminate\Contracts\Validation\ValidationRule`), riusate sia dai guard della macchina a stati sia da qualunque form/API futura. Mai `throw new Exception` dentro un metodo di salvataggio |
| **A4** | Enum sempre castati | Ogni stato/tipo/priorità/ruolo/permesso è un backed enum PHP (`TicketStatus`, `TicketType`, `TicketPriority`, `UserRole`, `Permission`, ecc.), mai una stringa grezza confrontata a mano. `phpstan.neon` abilita `parseModelCastsMethod: true` perché questo repo usa il metodo `casts()` (stile Laravel 11+), non la proprietà `$casts` |
| **A5** | Effetti collaterali via eventi e queue | Eventi di dominio (`TicketCreated`, `TicketStatusChanged`, `TicketMessagePosted`, `TicketAssigned`, `InboundEmailApplied`, `EmailQuarantined`, `ActivityReportPdfGenerated`, ...) dispatchati dentro la transazione dell'Action; i Listener che inviano notifiche implementano sempre `ShouldQueue` — mai un invio SMTP sincrono in una request HTTP |
| **A6** | Query object invece di sottoclassi di resource | `App\Domain\Ticketing\Queries\*` (`WaitingQuery`, `ProblemTicketsQuery`, `ToTestByMeQuery`, `AllCustomerTicketsQuery`, ...): un query object per vista, esposto come tab di `ListRecords::getTabs()`, mai una Resource/Page duplicata per filtro (§8.5 del PRD) |
| **A7** | Idempotenza esplicita | Ogni comando schedulato/ETL è ripetibile senza duplicare: chiave di riconciliazione (id conservato, vincolo unique composito, o `ImportMapping` per l'ETL), `withoutOverlapping()`, `--dry-run`. Vedi `docs/operations.md` e `docs/import-v1.md` |
| **A8** | Nessuna colonna/relazione/campo fantasma | Ogni `$fillable`, relazione, accessor corrisponde a qualcosa che esiste davvero. Esempio negativo esplicitamente non riprodotto: `Documentation::creator()` del v1 (verso una colonna inesistente) — `DocumentationPage` in questo repo non ha alcuna colonna/relazione autore finché non serve davvero |
| **A9** | Lo schema è la documentazione | Nomi espliciti, vincoli DB (`NOT NULL`, FK con `ON DELETE` dichiarato, unique dove serve, CHECK dove sensato). Vedi `docs/data-model.md` |

## Dove sta cosa (mappa rapida)

| Argomento | Percorso |
|---|---|
| Macchina a stati e transizioni | `app/Domain/Ticketing/StateMachine/TicketStateMachine.php` |
| Calcolo ore lavorate | `app/Domain/TimeTracking/WorkedTimeCalculator.php` + `app/Domain/TimeTracking/Actions/RecalculateWorkedTime.php` |
| Pipeline email inbound | `app/Domain/Mail/Actions/{ParseInboundEmail,ClassifyInboundEmail,ResolveEmailSender,ResolveEmailThread,ApplyInboundEmail,ImportInboundEmailAttachments,ProcessDeliveryStatusNotification}.php` |
| Invio outbound (punto unico) | `app/Domain/Mail/Actions/SendOutboundTicketMail.php` |
| Autorizzazione — enum | `app/Domain/Identity/Enums/{UserRole,Permission}.php` |
| Autorizzazione — seeder | `database/seeders/RolePermissionSeeder.php` |
| Autorizzazione — policy | `app/Domain/<Modulo>/Policies/*Policy.php` |
| ETL | `app/Import/` + `app/Console/Commands/V1{Inspect,Import,Validate}Command.php` |
| Comandi schedulati | `app/Console/Commands/*Command.php`, registrazione in `routes/console.php` |
| Diagnostica ambiente | `app/Support/Doctor/` + `php artisan orchestrator:doctor` |
| Design tokens | `app/Support/DesignTokens.php`, letti da `resources/css/theme.css` |
| Pannello Filament | `app/Filament/Providers/AdminPanelProvider.php` |
| Vista di lavoro (WorkBoard) | `app/Filament/Pages/WorkBoard.php` + `resources/views/filament/pages/work-board.blade.php` |
| Dashboard cliente | `app/Filament/Pages/CustomerDashboard.php` |
| Tipologia di cliente CAI — enum/classificazione | `app/Domain/Identity/Enums/{CustomerType,Region}.php`, `app/Import/Stages/CustomerClassificationStage.php`, `app/Domain/Identity/Queries/SectionsInRegionQuery.php` |
| Integrazione dati RUNTS-CAI | `app/Domain/CaiDirectory/` (Models + `Import/CaiDatapackImporter.php`), `app/Console/Commands/CaiImportDatapackCommand.php`, `app/Filament/Resources/CaiSections/`, `app/Filament/Pages/{CaiSectionsMap,CaiSectionRegionalDetail}.php`, `app/Http/Controllers/CaiDocumentDownloadController.php` |
| Preferenze di notifica | `app/Filament/Pages/NotificationPreferences.php` + `app/Domain/Mail/Enums/NotificationType.php` |

## Documenti correlati

- `docs/data-model.md` — schema completo e mappa dei nomi v1→v2.
- `docs/ticket-lifecycle.md` — macchina a stati in dettaglio.
- `docs/email.md` — sottosistema email.
- `docs/time-tracking.md` — algoritmo ore lavorate.
- `docs/authorization.md` — i tre livelli di autorizzazione.
- `docs/import-v1.md` — procedura ETL.
- `docs/operations.md` — deploy, scheduler, coda, diagnostica.
- `docs/differences-from-v1.md` — differenze di comportamento e bug v1 corretti.
- `docs/design-system.md` / `docs/design-inventory.md` — identità visiva e inventario del design importato (non duplicati qui).
