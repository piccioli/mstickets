# Dominio Identity — utenti, ruoli/permessi, organizzazioni, MFA, impersonation

Si carica quando lavori sotto `app/Domain/Identity/*`. Vedi anche `app/Domain/CLAUDE.md` per i pattern
condivisi (in particolare Policy deny-by-default) e `app/Filament/CLAUDE.md` per i pattern generici di
Resource/RelationManager usati da `UserResource`/`RoleResource`/`OrganizationResource`.

## Identità / spatie-permission (US-010)

- `App\Models\User` è stato **spostato** in `App\Domain\Identity\Models\User` per rispettare la struttura a
  moduli (§4.3): qualunque riferimento nuovo (config, factory, seeder, Filament) deve puntare a
  `App\Domain\Identity\Models\User` — la cartella `app/Models/` non esiste più.
- Spostare un modello fuori da `App\Models` rompe **due convenzioni Laravel basate sul namespace**:
  1. `HasFactory` prova a indovinare la classe factory dal namespace del modello; se la factory non segue
     quella convenzione (qui resta in `Database\Factories\UserFactory`, non annidata), va aggiunto un
     override esplicito `protected static function newFactory(): UserFactory` nel modello.
  2. Il contrario: la Factory indovina il modello dal proprio namespace; se il modello non è lì, va impostato
     esplicitamente `protected $model = User::class;` nella classe Factory. Senza questi due fix si ottiene
     un errore runtime tipo `Class "App\User" not found`, non un errore a livello di autoload.
  3. Dopo un `git mv`/spostamento di file di classe, se `composer.json` ha `optimize-autoloader: true` (il
     caso qui), rigenerare l'autoload con `composer dump-autoload -o`: altrimenti il classmap ottimizzato
     punta ancora al vecchio path (`include(...): Failed to open stream` fuorviante).
- Pattern per un indice funzionale case-insensitive su una colonna (`users.email`, §5.2):
  `DB::statement('create index <nome> on <tabella> (lower(<colonna>))')` dentro la migrazione dopo lo
  `Schema::create` — sintassi portabile sia su Postgres sia su sqlite, non serve un `if` sul driver. Resta un
  indice separato dal vincolo `unique()` standard (case-sensitive), non lo sostituisce.
- Le tabelle di `spatie/laravel-permission` si pubblicano con `php artisan vendor:publish --provider=
  "Spatie\Permission\PermissionServiceProvider"`: **non riscriverle a mano**. `config('permission.teams')` è
  già `false` e il guard di default è già l'unico usato: nessun'altra configurazione necessaria per questo
  progetto (guard unico `web`, niente teams).
- `User` usa `Spatie\Permission\Traits\HasRoles` (non `filament-shield`, vietato dal PRD): ruoli/permessi
  vanno creati solo dal seeder (§9.2), mai con `Role::create()`/`Permission::create()` da codice applicativo
  a runtime.

## Ruoli e permessi — enum (US-011)

- `App\Domain\Identity\Enums\UserRole` (5 case: `Admin`/`Developer`/`Manager`/`Customer`/`Fundraising`) e
  `App\Domain\Identity\Enums\Permission` (52 case, catalogo completo §9.3) sono la **sorgente di verità**: il
  seeder di ruoli/permessi (US-018) deve iterare `UserRole::cases()`/`Permission::cases()`, mai stringhe
  letterali. Aggiungere/rimuovere un permesso significa modificare `Permission` qui, non una tabella
  scollegata.
- Naming dei case: `PascalCase` che rispecchia il `value` `<dominio>.<azione>[.<ambito>]` (es. case
  `TicketManageInternalFields` → value `'ticket.manage-internal-fields'`).
- `UserRole` implementa `HasLabel`/`HasColor`/`HasIcon` di Filament (`getColor()` restituisce un nome colore
  già registrato in `AdminPanelProvider`, es. `'danger'`, non un hex). `Permission` implementa solo
  `HasLabel` (colore/icona per 52 permessi granulari non veicolerebbe informazione utile).
- Nessuna delle due enum ha una migrazione/tabella propria: catalogate solo in PHP; le tabelle Spatie
  restano quelle pubblicate in US-010.

## Seeder idempotente ruolo → permessi (US-018)

- Pattern riusabile per qualunque "seeder di catalogo" idempotente E che revoca ciò che sparisce dalla fonte
  di verità PHP (`RolePermissionSeeder`, matrice §9.4): (1) `firstOrCreate` ogni riga del catalogo enum
  corrente; (2) per ogni ruolo, `$role->syncPermissions([...])` — già idempotente da solo; (3) per revocare
  le righe "orfane": `Permission::whereNotIn('name', $catalogoAttuale)->delete()` — il `delete()` su un
  `Permission`/`Role` di Spatie fa cascade reale a livello DB su `role_has_permissions`/`model_has_permissions`
  (FK `onDelete('cascade')`), non serve staccare manualmente i pivot prima.
- La mappa ruolo→permessi vive come `const array` di case enum (non stringhe), convertiti a `->value` solo al
  momento di chiamare l'API di Spatie.
- Test di un seeder così fatto vanno oltre "gira senza errori": verificare (a) la matrice esatta per ruolo,
  (b) una seconda esecuzione non introduce differenze, (c) un permesso/ruolo creato manualmente FUORI dal
  catalogo (simulando "rimosso da un refactor futuro") viene effettivamente cancellato dalla nuova
  esecuzione.
- **Pattern Larastan riusabile per un seeder che stampa output solo quando lanciato da `db:seed`**:
  `Illuminate\Database\Seeder::$command` non è mai `null` in PHPDoc, ma lo è realmente quando il seeder è
  istanziato direttamente nei test. Con `treatPhpDocTypesAsCertain` (default true) Larastan tratta sia
  `$this->command?->...` sia `isset($this->command)` come errori. Aggirare dichiarando una **propria**
  proprietà `private ?Command $console = null;`, sovrascrivendo `setCommand(Command $command): static` per
  popolarla (chiamando comunque `parent::setCommand($command)`), e usando `$this->console?->...` ovunque.
- **Seed di sviluppo (US-023, rimosso in US-R03)**: US-023 introduceva un seeder di dati fittizi per l'
  ambiente locale, rimosso interamente quando `make setup` (US-R02) è passato a popolare l'ambiente con
  l'ETL reale (`v1:import --anonymize` su un dump reale, mai dati fittizi) — `DatabaseSeeder::run()` oggi
  richiama solo `RolePermissionSeeder`.

## Gate di accesso al pannello (US-020)

- `User implements Filament\Models\Contracts\FilamentUser` con `canAccessPanel(Panel $panel): bool`: nega se
  `deactivated_at` non è null, altrimenti richiede `hasAnyRole(array_column(UserRole::cases(), 'value'))` — un
  gate di navigazione, non un'autorizzazione di dominio (mai un controllo su permesso qui).
- In test, `Filament\Panel` **non ha costruttore**: si può istanziare direttamente con `new Panel` per
  chiamare `$user->canAccessPanel(new Panel)`.
- `User::scopeActive()` (`whereNull('deactivated_at')`) è il punto unico da usare in qualunque futura query
  di selezione utenti (assegnazione ticket, participants, ecc.): non duplicare `whereNull('deactivated_at')`
  a mano.

## Comando diagnostico `orchestrator:doctor` (US-022)

- Architettura a controlli indipendenti (§12): `App\Support\Doctor\Contracts\DoctorCheck` +
  `App\Support\Doctor\Checks\*` (una classe per famiglia di controllo). `OrchestratorDoctorCommand` istanzia
  ogni check elencato in `self::CHECKS` via il container, exit code 1 se un `DoctorCheckResult::$passed` è
  `false`. Per un controllo di una fase futura: nuova classe `DoctorCheck` + aggiunta a `self::CHECKS`, non
  toccare le altre.
- `EnvironmentVariablesCheck` NON chiama `env()` direttamente (vietato fuori da `config/`): legge lo snapshot
  `config('orchestrator.required_env')` (popolato in `config/orchestrator.php` con `env('X')` SENZA
  default). Per estendere la lista, aggiungere la chiave in `config/orchestrator.php`, non nel Check.
- L'utente di sistema (`config('orchestrator.system_user')`) è creato con `User::query()->firstOrCreate(
  ['email' => ...], ['name' => ...])`, senza la chiave `password` (il cast `'hashed'` gestisce `null` senza
  errori). Nessun ruolo assegnato: `canAccessPanel()` nega comunque l'accesso.

## Prima Filament Resource — UserResource/RoleResource

Le convenzioni Filament generiche stabilite qui (proprietà statiche `UnitEnum|string|null`, Resource senza
Policy come `RoleResource` che sovrascrive `can*()` a mano, `CheckboxList` visibile-e-dehydratato solo con un
permesso, Larastan su model vendor esterni ai `paths` analizzati) sono documentate in `app/Filament/CLAUDE.md`
§US-021 — questo file resta il riferimento per QUALI permessi/dati gestiscono `UserResource`/`RoleResource`.

## MFA nativa Filament per ruolo — timing di `isRequired` (US-606, §6.7.2)

- `Panel::multiFactorAuthentication([...], isRequired: ...)`: il parametro `isRequired` (bool|Closure) è
  valutato in `routes/web.php` (vendor) alla REGISTRAZIONE delle route — al boot della richiesta, PRIMA che
  la sessione/il guard `Auth::user()` siano disponibili. Una closure che tenta di leggere il ruolo
  dell'utente lì (`fn () => Auth::user()?->hasRole(...)`) vede sempre `null` e non funziona MAI per un
  controllo per-ruolo, né in produzione né nei test. Soluzione adottata: `isRequired: true` sempre, e tutta
  la logica per-ruolo spostata in un middleware custom
  (`App\Filament\Auth\Middleware\EnsureRoleRequiresMultiFactorAuthentication`, sostituisce
  `multiFactorAuthenticationRequiredMiddlewareName()`) che legge `config('mfa.required_roles')` e
  `Filament::auth()->user()` A RUNTIME (dentro `handle()`) e delega al middleware nativo solo se il ruolo è
  nella lista — altrimenti lascia passare. Con `mfa.required_roles` vuoto di default, zero regressioni sui
  test esistenti.
- **Gotcha scoperto SOLO in verifica browser** (i test Pest con `actingAs()` non lo intercettano, bypassano
  `Filament\Auth\Pages\Login::authenticate()`): questo repo sostituisce l'intera view della pagina di login
  con un blade scritto a mano, MAI attraverso il meccanismo `content(Schema $schema)` nativo di Filament che
  normalmente mostra/nasconde il secondo step (`multiFactorChallengeForm`). Risultato: abilitare la MFA NON
  bastava, un utente restava bloccato per sempre sulla schermata email/password. Fix: nella stessa view,
  `@if ($this->userUndertakingMultiFactorAuthentication)` renderizza `{{ $this->multiFactorChallengeForm }}`
  (la `Schema` implementa `Htmlable`, si stampa con `{{ }}`) dentro un secondo `<form
  wire:submit="authenticate">`. Ogni futura modifica a una pagina di auth con view completamente custom deve
  verificare se sta bypassando un meccanismo nativo condizionale (MFA, verifica email, ecc.).
- Contratti `HasAppAuthentication`/`HasAppAuthenticationRecovery` implementati su `User` con cast
  `encrypted`/`encrypted:array` sulle due colonne segrete — mai in chiaro a riposo.
- `->profile()` (default `Filament\Auth\Pages\EditProfile`, mai creato prima) è l'UNICO punto in cui Filament
  espone la UI di setup MFA: abilitare la MFA senza abilitare `->profile()` lascerebbe la configurazione
  teoricamente attiva ma senza alcuna schermata reale da cui impostarla.
- Verifica in browser end-to-end (setup + login con sfida): `docker exec <container> php artisan tinker
  --execute="echo app(PragmaRX\Google2FAQRCode\Google2FA::class)->getCurrentOtp('<secret>');"` per calcolare
  un OTP valido.

## Impersonation (US-607, §6.7.2)

- `stechstudio/filament-impersonate` richiede un guard esplicito in cima a `UserPolicy::viewAny()`:
  `if (Impersonation::isImpersonating()) { return true; }` (bug noto del pacchetto, README "Potential Issues
  — 403 when a ListUsers widget has InteractsWithPageTable").
- **Effetto collaterale accettato consapevolmente, non un bug di questa story**: finché un'impersonation è
  attiva, QUALUNQUE utente impersonato può navigare a `/admin/users` e vedere l'elenco completo di tutti gli
  utenti reali — il guard è cieco rispetto a CHI è impersonato. Non praticamente sfruttabile (l'admin che
  impersona è già privilegiato), ma rompe la premessa "vedere il pannello con gli occhi dell'altro utente"
  per questa Resource. Rimuovere il guard reintroduce il 403 spurio del pacchetto — nessuna soluzione pulita
  nota con l'API pubblica. Flaggato per revisione col committente al checkpoint US-618, non silenziato.
- `User::canImpersonate()`/`canBeImpersonated()` (rilevati via `method_exists()`, nessuna interfaccia):
  `canImpersonate()` delega a `Gate::forUser($this)->check('impersonate', $this)` (unica fonte di verità in
  `UserPolicy::impersonate()`); `canBeImpersonated()` esprime solo il vincolo `deactivated_at === null`.
- Log strutturato tramite gli eventi nativi del pacchetto (`EnterImpersonation`/`LeaveImpersonation`), NON un
  observer/hook Eloquent — due listener in `AppServiceProvider::boot()`, nessuna nuova tabella dedicata.
  `LeaveImpersonation::$impersonated` è nullable (sessione ripulita da un guard esterno, es. logout).
- Test dell'intero ciclo NON affidabile con `Log::spy()->shouldHaveReceived(...)->once()` quando lo stesso
  metodo viene invocato più volte — catturare invece la cronologia reale con `Log::listen(function ($event)
  use (&$logs) { $logs[] = [...]; })`.

## Disattivazione utente (US-608, §6.7.5)

- Azione "Disattiva"/"Riattiva" unica estratta in `App\Filament\Resources\Users\Support\
  DeactivateUserAction::make()`, riusata sia dalla riga di `UsersTable` sia dall'header di `ViewUser`.
  Autorizzazione delegata interamente a `UserPolicy::deactivate()` (`Permission::UserDeactivate`).
  Assegnamento diretto di proprietà + `save()`, mai `fill()`/`update()`: la colonna non è nel `#[Fillable]`
  di proposito.
- **La soppressione delle comunicazioni per un utente disattivato è stata aggiunta in UN SOLO punto**:
  `SendOutboundTicketMail::blockedReason()` (`app/Domain/Mail/CLAUDE.md`) — è l'unico punto di invio
  dell'intero catalogo E1-E11, un controllo `deactivated_at !== null` lì copre automaticamente ogni Mailable
  futuro senza filtrare a monte ogni fonte del destinatario.
- **Verificare ogni picker "utente" prima di dare per scontato che escluda i disattivati**: un `Select` su
  `belongsTo` si scopa con `->relationship('assignee', 'name', modifyQueryUsing: self::activeUsersQuery(...))`;
  un `AttachAction` su `belongsToMany` è un meccanismo DIVERSO e va scopato con
  `->recordSelectOptionsQuery(fn (Builder $query) => $query->active())` — nessuno dei due copre l'altro caso.
  Gotcha PHPStan: `Builder $query` non generico non porta il tipo del model, `$query->active()` risulta
  "metodo indefinito" — estrarre in un metodo privato tipizzato `@param Builder<User> $query`.

## Filament Select condizionato dalla selezione corrente di ruoli/tipo (US-703, Fase 7)

- **`#[Fillable([...])]` sul model `User` è la fonte di verità per quali colonne un `->update($data)` da
  Filament può davvero scrivere**: aggiungere una nuova colonna nullable a `users` NON basta per renderla
  salvabile — se manca dalla lista dell'attributo, `$record->update($data)` la scarta silenziosamente per
  mass-assignment protection, **senza errori né eccezioni**. Prima di sospettare un bug nel form (visibility/
  dehydration), controllare sempre questo attributo quando un campo "sembra salvarsi" ma il valore persistito
  resta quello vecchio/null.
- **`->visible(fn (Get $get) => ...)` e `->dehydrated(fn (Get $get) => ...)` NON bastano per azzerare
  esplicitamente un campo quando diventa non pertinente**: di default un componente `hidden`/`visible(false)`
  viene escluso dalla dehydration a prescindere da `->dehydrated(true)`. Per forzare l'inclusione (e quindi
  l'azzeramento via `dehydrateStateUsing`) serve **`->dehydratedWhenHidden()`**. Pattern completo:
  ```php
  Select::make('region')
      ->visible(fn (Get $get): bool => /* pertinente */)
      ->dehydratedWhenHidden()
      ->dehydrateStateUsing(fn (Get $get, $state) => /* pertinente */ ? $state : null),
  ```
  Vedi `app/Filament/Resources/Users/Schemas/UserForm.php` (`customer_type`/`region` condizionati dal ruolo
  `customer` in un `CheckboxList::make('roles')->relationship(...)`, il cui stato via `Get` è un array di ID
  di ruolo, non di nomi, perché legato a una relazione `BelongsToMany`).
