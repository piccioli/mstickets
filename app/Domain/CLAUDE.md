# Convenzioni condivise fra tutti i moduli di dominio

Questo file si carica insieme a quello del modulo specifico ogni volta che lavori sotto `app/Domain/*`
(Claude Code carica i `CLAUDE.md` di tutte le directory antenate del file che stai toccando). Contiene solo
i pattern architetturali validi per QUALUNQUE modulo — la logica/le regole specifiche di un modulo vivono nel
suo file (`app/Domain/<Modulo>/CLAUDE.md`). Vedi la radice del repo (`CLAUDE.md`) per stack, convenzioni di
Fase 0 e gotcha trasversali d'ambiente/test.

## Action esplicite, mai hook Eloquent (PRD §4.4 A1)

Ogni mutazione significativa di un modello passa da un'Action statica dedicata
(`App\Domain\<Modulo>\Actions\<Verbo>::run(...)`), mai da `boot()`/`booted()`/un Observer. Le Action che
toccano più tabelle si avvolgono in `DB::transaction()`. Il chiamante risolve sempre l'attore esplicitamente
(`User $user` obbligatorio nella firma, mai `auth()->user()` interno): per un'azione di sistema senza utente
autenticato reale, usare `App\Domain\Identity\Models\User::system()` (`firstOrCreate` per email/nome da
`config('orchestrator.system_user.*')`), mai un id hardcoded o un utente fittizio creato ad hoc.

## Policy deny-by-default per modello (US-019)

Ogni modello Eloquent di dominio ha una Policy in `App\Domain\<Modulo>\Policies\<Modello>Policy` (parallela a
`Models/`, mai in `app/Policies/`): Laravel la trova da sola sostituendo `\Models\` con `\Policies\` nel
namespace (`Illuminate\Auth\Access\Gate::guessPolicyName`), senza bisogno di registrarla in nessun
Provider/`AuthServiceProvider` (questo repo non ne ha uno). Un modello senza Policy registrata nega già
`$user->can('view', $model)` di default (nessun bisogno di un `Gate::before` esplicito per il deny-by-default).

Ogni metodo di policy controlla il permesso via `$user->can(Permission::X)`/`$user->canAny([Permission::X,
Permission::Y])` **passando direttamente il case dell'enum**, non `->value`: Laravel 11+ normalizza da solo un
`BackedEnum` passato come ability tramite l'helper globale `enum_value()` (`Illuminate\Auth\Access\
Gate::inspect()`). Questo evita ogni confronto su stringa grezza (§4.4 A4) senza bisogno di un helper custom —
`canAny()` è già nativo di `Illuminate\Foundation\Auth\Access\Authorizable`.

Diversi modelli/azioni non hanno un permesso 1:1 nel catalogo `Permission` (§9.3): in questi casi la Policy
riusa il permesso del modello "padre" più vicino concettualmente (es. gestire i partecipanti di un ticket →
`ticket.assign`). Se emerge il bisogno di un permesso dedicato o di una regola più permissiva, aggiungerlo al
catalogo enum (US-011) e stringere la Policy di conseguenza — non il contrario.

**Gotcha Larastan sui cast enum**: se un modello casta un attributo con lo stile `protected function
casts(): array` (Laravel 11+, non la vecchia proprietà `protected $casts`), Larastan lo tratta come `string`
grezzo a meno che `parseModelCastsMethod: true` non sia abilitato in `phpstan.neon` (lo è, in questo repo) —
senza, ogni `match()`/confronto sull'enum fallisce silenziosamente ("arm sempre falso"). Se un futuro upgrade
di Larastan cambia questo default, non rimuovere il flag esplicito.

## `scope<Nome>VisibleTo()` / `isOwnedBy()` — un solo punto di verità per "chi vede/possiede questo record"

Pattern usato da `Ticket`/`TicketMessage` (`scopeVisibleTo`, US-105), `DocumentationPage` (`scopeVisibleTo`,
US-404), `ActivityReport` (`scopeVisibleTo`/`isOwnedBy`, US-409), `FundraisingProject`
(`scopeInvolving`/`scopeInvolvingAsCustomer`, US-506/US-508): un check per-record (`Policy::view()`) e un
filtro di lista (query object, Resource `getEloquentQuery()`) non devono MAI duplicare la logica di
ownership/visibilità in due posti — la Policy delega sempre allo scope (`Model::query()->whereKey($id)
->visibleTo($user)->exists()`), mai il contrario. Se la definizione di "visibile"/"coinvolto" cambia, va
aggiornato SOLO lo scope.

Attenzione: due permessi "categoria" che sembrano una gerarchia possono essere in realtà due gate
indipendenti (es. `documentation.view.customer` NON implica `documentation.view.internal`, §9.4) — verificare
sempre nel PRD/matrice permessi prima di assumere una gerarchia. Attenzione anche al significato di
"coinvolto"/"own", che può DIFFERIRE fra due model dello stesso dominio che sembrano simili
(`FundraisingOpportunity.view.involved` è un OK generico di modulo, `FundraisingProject.view.involved` è
per-record, US-506) — non riusare lo stesso schema di Policy fra due model senza verificarlo esplicitamente.

**Mai lo scope `role()`/`withoutRole()` di Spatie in una query di sistema**: risolvono il ruolo con
`findByName()` e lanciano `RoleDoesNotExist` se la riga `roles` non esiste ancora — sbagliato per un filtro
che deve solo restituire zero righe in quel caso. Usare sempre `whereHas('roles', fn ($q) => $q->where('name',
$ruolo))` / `whereDoesntHave(...)` direttamente sulla relazione. Esempi: `AllCustomerTicketsQuery`,
`InternalTicketsQuery`, `WorkBoard::assigneeOptions()`, `NotificationRecipientResolver` (Mail),
`MailSendDigestCommand`.

## Eventi di dominio + listener, registrazione manuale

Eventi (`readonly`, nessuna logica) in `App\Domain\<Modulo>\Events\*`, dispatchati con `event(new ...)`
**dentro** la transazione dell'Action che li genera (un listener che lancia durante l'evento fa rollback anche
delle scritture già eseguite nella stessa Action — utile anche per testare la transazionalità). I listener di
dominio vivono in `App\Domain\<Modulo>\Listeners` (MAI `App\Listeners`: l'auto-discovery di Laravel non li
trova) e vanno registrati a mano in `AppServiceProvider::boot()` con `Event::listen(Evento::class,
Listener::class)`.

Un evento "il valore/contenuto è cambiato" va emesso SOLO quando il valore è davvero cambiato (confrontare
prima/dopo dentro l'Action, mai su ogni update) — e due side-effect distinti sullo stesso model meritano due
eventi distinti anche se sembrano simili (`DocumentationPageRenamed` vs `DocumentationPageContentChanged`,
US-406): non allargare la semantica di un evento esistente per farci stare un secondo side-effect che deve
poter variare indipendentemente.

Un evento condiviso da PIÙ percorsi di creazione (es. `TicketCreated` generato sia dalla pagina Filament web
sia da `ApplyInboundEmail`) deve portare un parametro esplicito per distinguerli (`TicketMessageChannel
$channel`, US-311) — mai dedurlo dall'ordine/timing di side-effect successivi, un percorso fragile.

## Comando artisan che esegue un'automazione di sistema

Pattern comune a `mail:retry-failed` (US-325), `reports:generate-monthly` (US-410),
`tickets:progress-to-todo`/`tickets:auto-close-released` (US-610), `tickets:notify-idle-developers` (US-616),
`cai:sync-runts-all`: `User::system()` come attore passato all'Action di dominio (mai un update diretto sulle
colonne — delegare sempre all'Action esistente, così eventi/log/effetti arrivano gratis); `--dry-run` che non
chiama mai l'Action; log strutturato `started`/`item_failed`/`finished`; idempotenza per costruzione (la query
di selezione non deve più includere un record già processato) o via lookup su una tabella storica esistente
(`EmailMessage::where('mailable_class', ...)->where('created_at', '>=', ...)`, mai una nuova colonna
"già notificato" — vedi `app/Domain/Mail/CLAUDE.md`); un elemento fallito nel batch non deve mai fermare gli
altri (try/catch dentro il loop, mai attorno all'intero batch).

## DTO tipizzato per un esito "applica a più record, salta chi fallisce"

`App\Domain\Ticketing\DTO\ApplyStatusToChildrenResult` (`applied: list<Model>`, `skipped: list<array{ticket:
Model, reason: string}>`, US-104) è il pattern di riferimento per qualunque Action che deve applicare la
stessa operazione a più record senza bloccarsi al primo fallimento — riusare questo stesso schema (o lo
stesso DTO) invece di inventarne uno nuovo per un bulk action Filament o un'automazione futura.
