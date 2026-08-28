# Autorizzazione

Fonte: PRD-ORCHESTRATOR-V2.md §9 (M7 — Autorizzazioni), §6.7.1 (gestione ruoli/permessi in UI),
`CLAUDE.md` (US-010, US-011, US-018, US-019, US-020, US-021, US-606, US-607, US-608). Verificato
contro `app/Domain/Identity/Enums/{UserRole,Permission}.php`, `database/seeders/RolePermissionSeeder.php`
e le Policy reali in `app/Domain/*/Policies/`.

## I tre livelli (§9.1)

Un pacchetto di permessi **non sostituisce** le Policy: la maggior parte delle regole di questo
sistema è legata al singolo record, e nessun catalogo di permessi la esprime da solo.

| Livello | Risponde a | Implementazione | Esempio in questo repo |
|---|---|---|---|
| **1. Permesso** | "questo utente ha la capacità X?" | `spatie/laravel-permission` | `$user->can(Permission::TicketUpdateAny)` |
| **2. Policy** | "questo utente può fare X su **questo record**?" | Policy Laravel native, una per modello | `TicketPolicy::update()`: il developer aggiorna solo se `assignee_id`/`tester_id` è suo |
| **3. Campo / query** | "questo utente vede/scrive **questo campo**, e quali righe rientrano nella sua query?" | schema dei form Filament (`->hidden()`) + scope globali (`scopeVisibleTo()`) | il cliente non vede mai `priority`/`assignee_id`; `TicketMessage::scopeVisibleTo()` esclude i messaggi `internal` dalla query stessa, non solo dalla vista |

Regole vincolanti applicate ovunque nel codice:

- **Deny by default**: ogni modello di dominio ha una Policy propria in
  `App\Domain\<Modulo>\Policies\<Modello>Policy` (parallela a `Models/`), risolta da Laravel per
  convenzione di naming — nessun `AuthServiceProvider` in questo repo. Un modello **senza** Policy
  nega già tutto di default (verificato empiricamente in US-019): corregge il v1, dove `Tag`,
  `Organization`, `StoryLog` e `ActivityReport` non avevano alcuna Policy ed erano di fatto aperti.
- Le Policy **partono dal permesso** e poi applicano la regola sul record: mai un `hasRole()` dentro
  una Policy per esprimere una capacità — il ruolo si usa solo dove il comportamento è davvero
  legato al ruolo (navigazione, landing, visibilità dei campi), non per esprimere un permesso.
- Il livello 3 non è cosmetico: un campo nascosto da `->hidden()` (mai solo `->disabled()`) non viene
  **dehydratato** dal form, quindi sopravvive anche a una richiesta manipolata (`fillForm()` con
  campi iniettati via test/DevTools) — verificato con test dedicati su `TicketForm`.
- L'accesso al pannello è governato da un gate esplicito: `User implements FilamentUser`,
  `canAccessPanel()` nega se `deactivated_at` non è null, altrimenti richiede `hasAnyRole(...)` sui 5
  ruoli applicativi validi (mai un controllo su permesso qui: è un gate di navigazione, non
  un'autorizzazione di dominio).

## Ruoli e permessi nel codice, non a runtime (§9.2)

I ruoli sono l'enum PHP `App\Domain\Identity\Enums\UserRole` (5 case: `Admin`, `Developer`,
`Manager`, `Customer`, `Fundraising` — `editor` eliminato, decisione D14) ed è quella la sorgente di
verità; le righe nella tabella `roles` di Spatie sono una **proiezione**, creata da un seeder
idempotente (`RolePermissionSeeder`). Stesso principio per i permessi (`App\Domain\Identity\Enums\Permission`,
52 case) e per la mappa ruolo→permessi.

Perché non modificabili da interfaccia:

1. I ruoli sono **comportamentali**, non bundle di permessi: `customer` cambia navigazione
   (`getNavigationGroup()` dinamico), landing post-login, campi visibili nei form e scoping delle
   query. Un ruolo creato da UI produrrebbe un utente che l'interfaccia non sa rendere.
2. Un enum dà completamento, analisi statica (Larastan) e refactor sicuri; una stringa letta dal
   database no.
3. La mappa ruolo→permessi è una decisione di prodotto: appartiene al versionamento (revisionata in
   code review), non modificabile in produzione senza traccia.

Cosa **è** modificabile a runtime: l'assegnazione di ruoli a un utente, e la concessione di
**permessi diretti** a una singola persona — è questo che dà flessibilità senza il rischio.
`filament-shield` è esplicitamente vietato dal PRD (genererebbe l'intera UI di gestione runtime che
questa decisione esclude).

Il seeder (`database/seeders/RolePermissionSeeder.php`) è idempotente e va eseguito a ogni deploy
(`php artisan db:seed --class=RolePermissionSeeder --force`, vedi `docs/operations.md`): materializza
la matrice sopra, **revoca** (con `delete()`, cascade su `role_has_permissions`/`model_has_permissions`)
ogni permesso o ruolo Spatie non più presente nel catalogo enum — nessun orfano lasciato in tabella.

## Catalogo dei permessi (§9.3)

Convenzione di naming: `<dominio>.<azione>[.<ambito>]`. `any` = su qualunque record, `own` = solo
sui propri, `assigned` = solo su quelli in cui l'utente è assegnatario o tester.

| Dominio | Permessi |
|---|---|
| Ticket | `ticket.view.any`, `ticket.view.own`, `ticket.view.assigned`, `ticket.create`, `ticket.update.any`, `ticket.update.own`, `ticket.update.assigned`, `ticket.delete`, `ticket.assign`, `ticket.transition.any`, `ticket.manage-internal-fields` |
| Messaggi ticket | `ticket-message.create`, `ticket-message.view.internal`, `ticket-message.create.internal` |
| Log ticket | `ticket-log.view` |
| Tag / commesse | `tag.view`, `tag.create`, `tag.update`, `tag.delete` |
| Documentazione | `documentation.view.customer`, `documentation.view.internal`, `documentation.create`, `documentation.update`, `documentation.delete` |
| Report attività | `activity-report.view.any`, `activity-report.view.own`, `activity-report.create`, `activity-report.update`, `activity-report.delete`, `activity-report.generate-pdf` |
| Organizzazioni | `organization.view`, `organization.create`, `organization.update`, `organization.delete` |
| Fundraising | `fundraising.view.any`, `fundraising.view.involved`, `fundraising.create`, `fundraising.update`, `fundraising.delete`, `fundraising.evaluate` |
| Utenti | `user.view`, `user.create`, `user.update`, `user.deactivate`, `user.assign-roles`, `user.grant-permissions`, `user.impersonate` |
| Email | `email.view`, `email.manage` |
| Sistema | `horizon.access`, `logs.access`, `import.view` |

`ticket.manage-internal-fields` governa il livello 3 sui ticket (tipo, priorità, assegnatario,
tester, ore stimate, `description` interna, URL degli ambienti) — è il permesso "ombrello" per "è
staff, non cliente", centralizzato in `App\Filament\Resources\Tickets\Support\TicketFieldAccess::canManageInternalFields()`.

## Mappa ruolo → permessi

Matrice effettivamente materializzata da `RolePermissionSeeder::ROLE_PERMISSIONS` (`admin` riceve
**tutti** i permessi del catalogo, non elencati singolarmente sotto).

| Permesso | manager | developer | customer | fundraising |
|---|---|---|---|---|
| `ticket.view.any` / `.view.own` | ✔ / ✔ | ✔ / ✔ | — / ✔ | — |
| `ticket.create` | ✔ | ✔ | ✔ | — |
| `ticket.update.any` / `.update.own` / `.update.assigned` | ✔ / ✔ / — | — / — / ✔ | — / — / — | — |
| `ticket.assign` | ✔ | ✔ | — | — |
| `ticket.transition.any` | ✔ | — | — | — |
| `ticket.manage-internal-fields` | ✔ | ✔ | — | — |
| `ticket.delete` | — | — | — | — |
| `ticket-message.create` | ✔ | ✔ | — | — |
| `ticket-message.view.internal` / `.create.internal` | ✔ / ✔ | ✔ / ✔ | — | — |
| `ticket-log.view` | ✔ | ✔ | — | — |
| `tag.view` | ✔ | ✔ | — | — |
| `tag.create` / `.update` | ✔ / ✔ | — | — | — |
| `documentation.view.customer` / `.view.internal` | ✔ / ✔ | ✔ / ✔ | — | ✔ / ✔ |
| `documentation.create` / `.update` | ✔ / ✔ | ✔ / ✔ | — | — |
| `activity-report.view.any` / `.view.own` | ✔ / ✔ | — | — / ✔ | — |
| `organization.view` | ✔ | — | — | — |
| `fundraising.view.any` / `.view.involved` | — | — | — / ✔ | ✔ / ✔ |
| `fundraising.create` / `.update` / `.evaluate` / `.delete` | — | — | — | ✔ (tutti) |
| `user.view` | — | — | — | ✔ (selezione partner) |

Nota: questa è la matrice **effettivamente seedata** nel codice oggi, verificata contro
`database/seeders/RolePermissionSeeder.php` — differisce in alcuni dettagli dalla tabella di bozza
del PRD §9.4 (es. qui `manager` non ha `ticket.delete`, `developer` non ha `ticket.update.own`,
`customer` non ha `tag.*`): il seeder è la fonte di verità, non il PRD. `horizon.access`/`logs.access`/
`user.assign-roles`/`user.grant-permissions`/`user.impersonate`/`email.*` non sono inclusi in
**nessun** ruolo oltre `admin` — si concedono solo come permessi diretti a chi ne ha bisogno (è il
caso d'uso esplicito dei permessi diretti).

## Regole sul record (§9.5, livello 2/3)

Le regole più significative, tutte coperte da test:

| Regola | Dove |
|---|---|
| Il developer aggiorna un ticket solo se `assignee_id` o `tester_id` è suo | `TicketPolicy::update()` |
| Il cliente vede/aggiorna solo i propri ticket | `TicketPolicy` + `Ticket::scopeVisibleTo()` |
| Il cliente non vede mai i messaggi `internal` | `TicketMessage::scopeVisibleTo()`, sulla query, non in vista |
| Il cliente vede solo la documentazione `category = customer` | `DocumentationPagePolicy` + scope |
| Il cliente vede solo i propri report di attività | `ActivityReportPolicy` + scope |
| Un progetto fundraising è visibile al cliente solo se coinvolto (capofila, partner, responsabile, creatore) | `FundraisingProjectPolicy::view()` |
| Le transizioni di stato ammesse dipendono dal rapporto con il ticket | `TicketStateMachine` (consulta permesso **e** rapporto, tramite `TransitionActor`) |
| Il cliente non modifica tipo/priorità/assegnatario/tester/ore/`description`/URL ambienti | schema del form, governato da `ticket.manage-internal-fields` |
| Un utente disattivato non accede e non è selezionabile nei picker | gate di accesso + `User::scopeActive()` |

`Ticket::scopeVisibleTo()` è l'unica fonte di verità per "quali ticket può vedere questo utente":
ogni query object (`WaitingQuery`, `ProblemTicketsQuery`, ...), ogni filtro Filament e ogni comando
che deve rispettare l'autorizzazione UI di un utente incatena questo scope — mai un `where` scritto
a mano altrove. Un comando **di sistema** (es. `tickets:notify-idle-developers`) che deve valutare
oggettivamente lo stato dei ticket di un altro utente **non** usa mai questo scope: `scopeVisibleTo()`
filtra per permesso Filament dell'utente corrente, un concetto di UI-authorization non pertinente lì.

## MFA e impersonation

MFA (autenticazione a due fattori) e impersonation sono meccanismi di **accesso**, non di
autorizzazione sul record: la procedura di setup/recovery della MFA e i dettagli operativi
dell'impersonation sono documentati in `docs/operations.md` (sezione "MFA" e "Impersonation").
Riassunto:

- MFA nativa Filament, opzionale e abilitabile per ruolo (`config('mfa.required_roles')`, env
  `MFA_REQUIRED_ROLES`, default vuoto — nessun ruolo obbligato di default).
- Impersonation riservata a `Permission::UserImpersonate` (solo `admin` nella matrice sopra), con
  banner sempre visibile e log strutturato di ogni sessione. Un effetto collaterale noto del
  pacchetto scelto è documentato in `docs/operations.md`.

## Come concedere un permesso diretto

Da UI (`UserResource`, sezione "Permessi diretti" di `app/Filament/Resources/Users/Schemas/UserForm.php`,
visibile solo a chi ha `user.grant-permissions`):

1. Aprire la scheda dell'utente (`/admin/users/{id}/edit`).
2. Nella sezione **Permessi diretti**, selezionare uno o più permessi dal catalogo esistente (nessuna
   creazione di un nuovo permesso da qui — l'elenco proviene dalle righe già materializzate dal
   seeder). Il permesso si aggiunge **in aggiunta** a quelli già derivati dai ruoli assegnati.
3. Salvare: `CheckboxList::make('permissions')->relationship('permissions', 'name', ...)` sincronizza
   direttamente `model_has_permissions`.
4. La scheda di visualizzazione dell'utente (`ViewUser`, sezione "Permessi effettivi") elenca ogni
   permesso effettivo con la sua provenienza (da uno o più ruoli, oppure diretto) — utile per
   verificare l'effetto della concessione.

Un permesso diretto ha effetto anche se nessun ruolo dell'utente lo include, e la revoca (deselezione
+ salvataggio) lo rimuove immediatamente — coperto da test in
`tests/Feature/Filament/Identity/RoleAndPermissionManagementTest.php`.
