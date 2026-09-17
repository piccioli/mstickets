# Dominio Ticketing

Si carica quando lavori sotto `app/Domain/Ticketing/*` (o su `TicketResource`/pagine correlate in
`app/Filament/Resources/Tickets/*`, che restano nell'albero letto insieme a questo file solo se apri anche un
file di dominio — se lavori SOLO su Filament, consulta anche `app/Filament/CLAUDE.md` per i pattern generici
di Resource/RelationManager/navigazione). Vedi anche `app/Domain/CLAUDE.md` per i pattern condivisi fra tutti
i moduli (Action, Policy, eventi) e `app/Domain/TimeTracking/CLAUDE.md` per il calcolo ore lavorate.

## Schema — Ticketing (US-012)

- **FK verso una tabella non ancora esistente**: quando un AC richiede una colonna FK verso una tabella che
  una story successiva creerà (qui: `tickets.fundraising_project_id` → `fundraising_projects`, arriva in
  US-015; `ticket_messages.email_message_id` → `email_messages`, arriva in US-016; `ticket_tag.tag_id` →
  `tags`, arriva in US-013), creare la colonna come `unsignedBigInteger` **senza** `->constrained()`/vincolo
  FK, con un commento che rimanda alla story che introduce la tabella. Quella story successiva deve
  aggiungere il vincolo FK vero e proprio con una migrazione dedicata (`Schema::table(...)->foreign(...)`),
  non riscrivere questa migrazione.
- **Indici Postgres-only nelle migrazioni (GIN, full-text)**: `to_tsvector(...)` e gli indici `GIN` (usati su
  `tickets.title` e `ticket_logs.changes`) non hanno equivalente su sqlite (usato dai test, `phpunit.xml`).
  Guardia esplicita nella migrazione: `if (Schema::getConnection()->getDriverName() === 'pgsql') {
  DB::statement(...); }`. Verificare comunque questi `DB::statement` contro Postgres reale (`docker compose
  up -d db app` + `php artisan migrate:fresh` nel container `app`), non solo su sqlite: la sintassi non viene
  mai eseguita/validata dai test.
- **`$table->foreignId(...)->constrained()` NON crea un indice standalone su Postgres** (a differenza di
  MySQL, dove la FK crea un indice implicito): se un AC richiede un indice su una colonna FK isolata (es.
  `ticket_messages.author_id`), aggiungere sempre un `$table->index('colonna')` esplicito, non fare
  affidamento su `constrained()`.
- **Colonna `ulid` pubblica separata dalla PK `id` autoincrementale** (§5.2 `ticket_messages`, pattern
  riusabile per qualunque tabella futura con lo stesso bisogno): usare il trait nativo
  `Illuminate\Database\Eloquent\Concerns\HasUlids` ma **sovrascrivere `uniqueIds(): array` per restituire
  `['ulid']`** invece del default (che assume la PK stessa come colonna ulid). Così `id` resta intero
  autoincrementante (FK/ordinamento) e `ulid` viene generato automaticamente solo se vuoto al momento del
  `save()`. `ulid` non è nella lista `#[Fillable]`: per testarne il vincolo unique forzare l'attributo via
  assegnazione diretta (`$model->ulid = '...'`) o `forceFill()`, non `::create(['ulid' => ...])` (verrebbe
  ignorato in mass assignment e rigenerato). **Gotcha ULID verificato empiricamente**: in questo progetto
  `HasUlids` genera ULID in MINUSCOLO, non nel case canonico Crockford — qualunque confronto su una colonna
  `ulid` deve essere case-insensitive (`whereRaw('lower(ulid) = ?', [strtolower($valore)])`).
- **Prima vera integrazione di `spatie/laravel-medialibrary`**: la migrazione `media` e
  `config/media-library.php` vanno pubblicati con `php artisan vendor:publish --provider=
  "Spatie\MediaLibrary\MediaLibraryServiceProvider"` **prima** di qualunque migrazione che li referenzia.
- Dopo un `Model::create([...])` con colonne che hanno un **default a livello di database** e nessun valore
  esplicito passato, l'istanza in memoria ha quell'attributo `null` finché non si rilegge dal DB
  (`->fresh()`/`->refresh()`). Nei test che verificano i default, sempre `->fresh()` dopo la `create()`.
- Mappatura colori `TicketStatus::getColor()` (Filament, non hex): dove il mockup definisce un badge per lo
  stato, usato quello come riferimento semantico; per i 6 stati senza badge nel mockup, scelta la categoria
  Filament più vicina all'hex v1 di riferimento (§5.3 del PRD) — vedi commento nell'enum.

## Macchina a stati del ticket (US-101)

- `App\Domain\Ticketing\StateMachine\TicketStateMachine` (Fase 1, A2 del PRD) tiene TUTTA la tabella delle
  transizioni in `transitions(): array<Transition>` (memoizzata con `static $transitions`, tutti i metodi
  sono statici). Per aggiungere/modificare una transizione, tocca solo quell'array.
- Il "chi" di una riga (colonna "Chi" del PRD §6.1.3) **non è un ruolo**: è
  `App\Domain\Ticketing\StateMachine\TransitionActor` (enum senza backed value), che verifica insieme
  permesso e rapporto col record (`assignee_id`/`tester_id` = utente corrente) in un solo metodo
  `authorize()`. Un developer "generico" senza rapporto col ticket non è mai un attore valido di per sé: o è
  `Assignee`/`Tester`, o `AutoAssigningDeveloper` (il contesto passato alla transizione lo autoassegna
  contestualmente), o `NoRelationRequired`. Il `TicketPolicy` resta solo livello-permesso: le regole di
  record-ownership sui ticket vivono qui.
- **Guard con precedenza sul contesto rispetto alla colonna esistente**: ogni guard controlla prima
  `$context['tester_id'] ?? $ticket->tester_id` — così la macchina a stati può validare *prima* che
  un'Action (US-103) scriva davvero la colonna, passando il valore che sta per applicare in un array
  `$context` (`assignee_id`/`tester_id`/`waiting_reason`/`problem_reason`).
- **Transizioni con target dinamico** (`waiting`/`problem` → `previous_status`, §6.1.3): `Transition::$to` è
  `?TicketStatus`, `null` significa "il target è `$ticket->previous_status`". `matchesTarget(Ticket $ticket,
  TicketStatus $to)` risolve il confronto a runtime.
- **Un errore di validazione localizzato, mai un'eccezione generica** (A2): sia "transizione non in tabella"
  sia "attore non autorizzato" sia "guard fallito" lanciano `Illuminate\Validation\ValidationException::
  withMessages(['status' => [...]])` con un messaggio in italiano. `TicketStateMachine::can(...)` è un
  wrapper `try/catch` che restituisce `bool` per i punti che vogliono solo un check booleano.
- **Attore "sistema" incluso solo dove serve davvero ora**: se una story futura introduce un
  comando/listener che deve agire da sistema su una transizione che oggi non ha `System` tra gli attori,
  aggiungerlo esplicitamente in quel momento — non prima. `User::isSystem(): bool` (confronta l'email con
  `config('orchestrator.system_user.email')`) è il modo per riconoscere l'utente di sistema in un
  guard/attore.
- `effects` su una `Transition` (`TransitionEffect`) è **solo metadato dichiarativo** nella macchina a stati:
  l'esecuzione reale (scrittura colonne, `ticket_logs`, eventi, demote §6.1.4) arriva con l'Action
  `ChangeTicketStatus` (US-103), che itera `$transition->effects`.

## Validation Rules di dominio (US-102, A3 del PRD)

- Le regole di business testabili in isolamento vivono in `App\Domain\Ticketing\Rules\*` come classi
  `Illuminate\Contracts\Validation\ValidationRule`, non `throw new Exception` dentro un metodo di
  salvataggio. Ogni regola espone il proprio messaggio italiano come `public const string MESSAGE`.
- `TicketStateMachine` non duplica la condizione delle Rule nei guard: ogni guard privato calcola il valore
  con la solita precedenza `$context[...] ?? $ticket->...` e lo passa a `TicketStateMachine::passesRule()`.
- **Gotcha Larastan sulla firma di `$fail`**: `ValidationRule::validate()` dichiara `$fail` come
  `Closure(string, ?string=): PotentiallyTranslatedString`, non `Closure(): void`. Quando adatti un `$fail` a
  un booleano, dichiara i parametri `(string $attribute, ?string $message = null)` e ritorna
  `new PotentiallyTranslatedString($message ?? $attribute, app('translator'))`.
- Rule che leggono lo stato del DB (es. `TicketParentDepthRule`) accettano il record "corrente" via
  costruttore (`new TicketParentDepthRule($ticket)`), non tramite un secondo parametro di `validate()`.
- Helper di test condiviso `ruleFails(ValidationRule $rule, mixed $value, string $attribute = 'value'): bool`
  in `tests/Pest.php`.

## Action di dominio, eventi, ticket_logs (US-103)

- Ogni mutazione significativa del ticket passa da un'Action statica in `App\Domain\Ticketing\Actions\*`
  (`CreateTicket::run()`, `ChangeTicketStatus::run()`, `AssignTicket::run()`). Ognuna avvolge le proprie
  scritture in `DB::transaction()`.
- `TicketStateMachine::authorize()` **restituisce** la `Transition` risolta: `ChangeTicketStatus` la usa per
  iterare `$transition->effects` e applicarli davvero. Se estendi `authorize()`, mantieni questo valore di
  ritorno: è il contratto con l'Action.
- **REGOLA §6.1.4 (un solo ticket `progress` per assegnatario)**: `ChangeTicketStatus` la esegue solo quando
  la `Transition` risolta dichiara l'effect `TransitionEffect::DemoteOtherProgressTickets` (oggi solo
  `todo → progress`). La demozione aggiorna direttamente `status`/`status_changed_at` e scrive il proprio
  `ticket_log` per ciascun ticket demosso, SENZA ripassare da `TicketStateMachine::authorize()`.
- `App\Domain\Ticketing\DTO\TicketLogChanges`: unico modo per popolare `ticket_logs.changes`, sempre tramite
  costruttori nominati (`assigneeChanged()`, `descriptionChanged()`), mai un array libero.
  `descriptionChanged()` registra solo il marker `'changed'`: mai il corpo del campo salvato nel log.
- Eventi in `App\Domain\Ticketing\Events\*` (`TicketCreated`, `TicketStatusChanged`, `TicketAssigned`):
  classi `readonly`, emessi con `event(new ...)` **dentro** la transazione dell'Action.
- Helper di test condivisi `ticket()` e `systemUser()` sono in `tests/Pest.php`.

## Propagazione esplicita ai figli (US-104, decisione Q5)

- `App\Domain\Ticketing\Actions\ApplyStatusToChildren::run(Ticket $parent, TicketStatus $to, User $user,
  array $context = [])` è l'UNICO modo per applicare lo stesso cambio di stato ai figli diretti: la
  propagazione automatica del v1 è stata deliberatamente rimossa (Q5), `ChangeTicketStatus` non la richiama
  mai da sé.
- Pattern "un figlio alla volta, isolato": itera `$parent->children`, invoca `ChangeTicketStatus::run()` per
  ciascun figlio dentro un `try/catch (ValidationException)`. Un figlio la cui transizione non è ammessa
  finisce in `skipped` col motivo, SENZA bloccare gli altri figli.
- Esito tipizzato in `App\Domain\Ticketing\DTO\ApplyStatusToChildrenResult` (`applied: list<Ticket>`,
  `skipped: list<array{ticket: Ticket, reason: string}>`) — vedi anche `app/Domain/CLAUDE.md`.

## Regole sul record nella TicketPolicy (US-105, §9.5)

- `Ticket::scopeVisibleTo(Builder $query, User $user)` è l'UNICA fonte di verità per "quali ticket può vedere
  `$user`" (§9.5): `ticket.view.any` non ha vincoli, `ticket.view.assigned` restringe a `assignee_id`/
  `tester_id = $user->id`, `ticket.view.own` restringe a `requester_id = $user->id`. Nessuno dei tre permessi
  → `whereRaw('1 = 0')`. `TicketPolicy::view()` **delega** a questo scope, mai ripete la condizione.
- `TicketPolicy::update()` verifica il permesso **e** il rapporto col record separatamente per ciascun
  livello: `ticket.update.any` nessun vincolo, `ticket.update.own` richiede `requester_id = $user->id`,
  `ticket.update.assigned` richiede `assignee_id = $user->id` **oppure** `tester_id = $user->id`.
- `TicketMessage::scopeVisibleTo(Builder $query, User $user)` esclude i messaggi `visibility = internal` per
  chi non ha `ticket-message.view.internal`: incatenato a `$ticket->messages()` in qualunque query futura
  (timeline conversazione), non solo per un singolo `TicketMessagePolicy::view()`.
- I test di `TicketPolicy` di Fase 0 assumevano che avere `ticket.view.own`/`ticket.update.assigned` bastasse
  indipendentemente dal record — se una story futura tocca `TicketPolicy`, verificare che i fixture dei test
  impostino `requester_id`/`assignee_id`/`tester_id` coerenti con l'esito atteso, non un ticket "vuoto".

## Conversazione del ticket (US-106, §6.1.7)

- `App\Domain\Ticketing\Actions\PostTicketMessage::run(Ticket $ticket, User $author, string $bodyHtml,
  TicketMessageChannel $channel = TicketMessageChannel::Web, ?EmailMessage $emailMessage = null)` (il 4°/5°
  parametro sono estensioni successive, US-307/US-322) è l'UNICO modo per pubblicare un messaggio: canale
  sempre `web`/visibilità sempre `public` di default. Aggiunge l'autore ai `ticket_participants`
  (`syncWithoutDetaching`), scrive il `ticket_log` `message_posted` ed emette `TicketMessagePosted` DENTRO la
  stessa transazione.
- `App\Domain\Ticketing\Support\TicketMessageSanitizer` è la SOLA via per sanitizzare un `body_html` prima di
  salvarlo o mostrarlo: allowlist esplicito (`symfony/html-sanitizer`), non un blocklist — qualunque tag non
  elencato viene rimosso INSIEME al suo contenuto (`<script>` incluso). `toPlainText()` deriva sempre dal
  corpo GIÀ sanitizzato. Mai un `{!! !!}` altrove nel codice su questo campo.
- `Ticket::messageRecipients(User $author)`: partecipanti + richiedente + assegnatario + tester, deduplicati
  per id, escluso l'autore. **Attenzione**: da US-318 questo metodo NON è più la sorgente dei destinatari di
  E4 (cambio di stato), ma resta corretto e usato da E5 (nuovo messaggio pubblico, US-314) — vedi
  `app/Domain/Mail/CLAUDE.md`.
- Regola T7 (decisione Q14) vive in `App\Domain\Ticketing\Listeners\RestoreTicketStatusOnRequesterMessage`,
  MAI dentro `PostTicketMessage`: quando l'autore del messaggio è il `requester_id` del ticket, se lo stato è
  `waiting` torna a `previous_status`; se è `assigned`/`progress` passa a `todo`. Passa sempre da
  `ChangeTicketStatus::run()` con `User::system()`. Per questa regola `TicketStateMachine::transitions()`
  ammette `TransitionActor::System` anche su `assigned → todo` e `progress → todo` (oltre a
  `waiting → previous_status`).

## Allegati sui messaggi (US-107, §9.6/§17.2)

- Unica lista di tipi/dimensione ammessi in `config/ticketing.php`, esposta via
  `App\Domain\Ticketing\Support\TicketAttachmentTypes`. Qualunque futuro punto di ingresso deve leggere da
  qui, mai duplicare la lista.
- `TicketMessage::registerMediaCollections()` registra `attachments` su un disco **privato dedicato**
  (`ticket-attachments`, `serve => false`) con `->acceptsMimeTypes(...)` come guardia aggiuntiva.
- **Gotcha mime-sniffing**: `FileAdder::toMediaCollection()` deriva `$media->mime_type` dal **contenuto
  reale** (sniffing), non dall'estensione dichiarata né da `UploadedFile::getMimeType()` (extension-based nei
  test). Nei test, usare contenuto reale (`UploadedFile::fake()->image(...)`,
  `UploadedFile::fake()->createWithContent(...)`) quando il test deve attraversare davvero
  `addMedia()->toMediaCollection()`. Per testare solo il guard applicativo (che valida PRIMA con gli
  attributi "dichiarati"), un file fake senza contenuto reale basta.
- `App\Domain\Ticketing\Actions\{AddTicketAttachment,RemoveTicketAttachment}` sono l'UNICO modo per
  aggiungere/rimuovere un allegato: validano tipo+dimensione con `ValidationException` localizzata PRIMA di
  scrivere, scrivono un `ticket_log` dedicato. `RemoveTicketAttachment` verifica che il `Media` appartenga
  davvero al `TicketMessage` indicato prima di cancellare.
- **Download SOLO tramite `App\Http\Controllers\TicketAttachmentDownloadController`** (autorizza delegando a
  `TicketPolicy::view()` sul ticket del messaggio proprietario): nessun URL medialibrary diretto viene mai
  esposto. Se `$media->mime_type === 'image/svg+xml'`, il contenuto viene sanitizzato al volo con
  `App\Domain\Ticketing\Support\TicketAttachmentSvgSanitizer` PRIMA di essere servito.

## Tracciamento visualizzazioni (US-108, §6.2.3)

- `App\Domain\Ticketing\Actions\RecordTicketView::run(Ticket $ticket, User $user): TicketView` è l'UNICO modo
  per registrare una visualizzazione. Call site naturale: `ViewTicket::mount()`/`resolveRecord()`.
- Soglia di throttling in `config('ticketing.views.throttle_minutes')`, mai hardcoded: `last_viewed_at`/
  `view_count` avanzano solo se sono trascorsi almeno quei minuti dall'ultima visualizzazione per lo stesso
  (ticket, utente, giorno).
- **Gotcha cast `date` su colonna comparata in una WHERE**: `TicketView::viewed_on` ha cast `'date'`, ma la
  SERIALIZZAZIONE usa comunque il formato datetime completo (`Y-m-d H:i:s`). Confrontare con
  `->where('viewed_on', $dateString)` usando `Y-m-d` puro NON trova mai la riga: usare sempre
  `->whereDate('viewed_on', $dateString)`.
- Il vincolo unique `(ticket_id, user_id, viewed_on)` è a livello DB: l'Action lo sfrutta con
  `lockForUpdate()` dentro `DB::transaction()` per evitare una race condition. Nessuna scrittura in
  `ticket_logs` per una visualizzazione.

## TicketResource — prima Resource Filament "reale" (US-110)

- **Bottoni di transizione dinamici**: `App\Filament\Resources\Tickets\Support\TicketTransitionActions::
  build(Ticket $ticket): list<Action>` itera `TicketStatus::cases()`, risolve la `Transition` applicabile e
  decide se mostrare il bottone SOLO in base ad attore+tabella (`Transition::isAuthorizedFor()`), **mai**
  `TicketStateMachine::can()`/`guardPasses()`: i guard "campo valorizzato" dipendono da un dato fornito nel
  modale dell'action stessa, quindi falliscono SEMPRE se verificati prima che quel modale esista. Il guard
  resta verificato per davvero al submit da `ChangeTicketStatus::run()`: un fallimento produce una notifica
  d'errore, non un bottone mancante.
- **`Action::schema([...])` invece di `requiresConfirmation()`**: quando un'action ha SEMPRE almeno un campo
  nel modale, il modale del form è già la conferma.
- **Un `Select` con `modifyQueryUsing` tipizzato `Builder $query` (generico) non eredita il generic del
  modello collegato**: estrarre un metodo privato con `@param Builder<User> $query` / `@return Builder<User>`
  esplicito e passarlo come first-class callable (`modifyQueryUsing: self::metodo(...)`).
- **Gotcha "il valore è già cambiato quando arriva al tuo `handleRecordUpdate()`"**: per un
  `Select::make('assignee_id')->relationship('assignee', ...)` (BelongsTo), Filament associa già la nuova
  relazione sul modello in memoria PRIMA che `handleRecordUpdate($record, $data)` venga invocato — per
  rilevare "è cambiato rispetto a prima", confrontare col valore ORIGINALE via
  `$record->getOriginal('assignee_id')`, mai con l'attributo corrente.
- **`->hidden()` (non `->disabled()`) per ogni campo che un cliente non deve mai poter scrivere**: un
  componente nascosto non viene dehydratato, sopravvive anche a una `fillForm()` manipolata.
- **Permesso "ombrello" per una distinzione UI ricorrente**: `Permission::TicketManageInternalFields` è il
  modo corretto per esprimere "è staff, non cliente" a livello di campo/sezione, centralizzato in
  `App\Filament\Resources\Tickets\Support\TicketFieldAccess::canManageInternalFields()`.
- **Nessun `RelationManager` in questa fase**: conversazione/storico sono implementati come
  `Filament\Infolists\Components\RepeatableEntry` con `->state(fn (Ticket $record) => Query::...->get())`
  esplicito, perché serve applicare `TicketMessage::scopeVisibleTo()` e l'ordinamento. Se una fase futura ha
  bisogno di azioni CRUD complete su una relazione del ticket, quello è il momento di introdurre un
  RelationManager (vedi `app/Filament/CLAUDE.md` per il pattern generico).
- **Allegati mostrati come link**: `<a href="{{ route('ticket-attachments.download', $media) }}">`.
- **Badge di stato fuori da una colonna tabella/entry**: `Blade::render('<x-filament::badge :color="$color"
  :icon="$icon">{{ $label }}</x-filament::badge>', [...])` dentro un `Placeholder::make('status')
  ->content(...)`.

## TicketResource — viste come query object (US-111, §8.5)

- **Ogni vista di §8.5 è un query object statico in `App\Domain\Ticketing\Queries`, MAI un metodo/scope su
  `Ticket` o una closure inline**: firma uniforme `for(User $user): Builder<Ticket>` che incatena SEMPRE
  `Ticket::query()->visibleTo($user)` prima di applicare il proprio filtro. Le viste "derivate" non ripetono
  la condizione di "Richieste attive": `ActiveRequestsQuery` espone `apply(Builder $query): Builder`, riusato
  dalle altre invece di duplicare `whereNotNull('requester_id')->whereNotIn('status', [...])`.
- **Tab della tabella** (`ListRecords::getTabs()` + `Tab::make()->modifyQueryUsing(...)`), non
  `SelectFilter`/pagine dedicate, per esporre le viste: ogni `Tab` delega per intero al query object
  corrispondente. Il set di tab differisce per ruolo usando `TicketFieldAccess::canManageInternalFields()`.
- **"Giorni in attesa"/"giorni nello stato corrente"** si calcola da `status_changed_at` con
  `->diffInDays(now())`, mai una subquery. Nota Larastan: il cast è non-nullable nel PHPDoc, usare `->`
  semplice (mai `?->`).
- **Auto-assegnazione silenziosa** (attore `AutoAssigningDeveloper`): se `$transition->actors` include
  `AutoAssigningDeveloper` e l'utente corrente NON ha `Permission::TicketTransitionAny`, il context
  `assignee_id` va precompilato con l'id dell'utente corrente e **nessun campo di scelta assegnatario va
  mostrato nel modale**.

## TicketResource — filtri (US-112, §8.5.1)

- **Tre forme di `Tables\Filters\*`**: (1) `SelectFilter::make('colonna')->options(...)` SENZA
  `->relationship()` per un filtro diretto su una colonna scalare; (2) `SelectFilter::make('relazione_id')
  ->relationship('relazione', 'campoTitolo')` per una BelongsTo/BelongsToMany; (3)
  `Filter::make('nome')->schema([...])->query(fn (Builder $query, array $data) => ...)` per qualunque filtro
  che non è un semplice match di valore. Il `->query()` custom su un `Filter` riceve `$data` con le chiavi
  esatte dei componenti dichiarati in `->schema()`, non `value`/`values`.
- I filtri compongono con i tab di `getTabs()` senza alcun collegamento esplicito — funziona già così.
- **Un campo la cui colonna cambia a runtime in una query va sempre whitelistato esplicitamente** nel
  `->query()` (`in_array($data['field'] ?? null, ['created_at', 'done_at'], true) ? ... : 'created_at'`).
- **Testare un `Filter`/`SelectFilter` con `Livewire::test(ListPage::class)->filterTable($nome, $dato)`**: la
  forma di `$dato` dipende dal TIPO di filtro — `SelectFilter` singolo → `['value' => ...]`
  (`['values' => [...]]` se `->multiple()`), `Filter` senza schema → booleano diventa `['isActive' => bool]`,
  `Filter`/`SelectFilter` con `->schema([...])` custom → array completo con le chiavi esatte dei campi.
  `->set('activeTab', 'chiave_tab')` seguito da `filterTable(...)` verifica la composizione con una vista.
- Helper di test `grantTicketPanelRole()` e `tag(array $attributes = [])` sono in `tests/Pest.php`.

## Vista di lavoro essenziale e landing per ruolo (US-113, §6.7.2/§8.6)

- **Landing per ruolo = sostituire la Dashboard registrata**: `App\Filament\Pages\Dashboard extends
  \Filament\Pages\Dashboard` (registrata al posto della classe vendor in `AdminPanelProvider::pages()`), il
  cui `mount()` fa `$this->redirect(WorkBoard::getUrl())` per chi ha
  `TicketFieldAccess::canManageInternalFields()` — **non** chiamare `parent::mount()` (fatal error, non
  esiste più in su nella gerarchia). Vedi anche `app/Filament/CLAUDE.md` §US-602 per il redirect esteso ad
  altri ruoli e per tenere `Dashboard` fuori dalla navigazione.
- **Una sola query per popolare N colonne di un board**: `WorkBoard::columns()` carica TUTTI i ticket
  visibili in un'unica query con `->with(['requester.organizations', 'tags'])`, poi raggruppa in PHP con
  `->groupBy(...)` e mappa su `TicketStatus::cases()`. Per "attività recenti", passare da un `pluck('id')`
  esplicito (`Ticket::query()->visibleTo($user)->pluck('id')`) seguito da un `whereIn('ticket_id', $ids)` sul
  modello target: `whereHas('ticket', fn ($q) => $q->visibleTo($user))` non tipizza (Larastan) su un
  `Builder<Model>` generico.
- `App\Filament\Resources\Tickets\Support\TicketLogFormatter::describe(TicketLog $log): string` è l'unico
  punto per il testo leggibile di un log, riusato da Storico e Attività recenti.
- **Cambio di stato dal board = link alla pagina del ticket**, non un secondo set di action dinamiche
  (complessità reale senza necessità per una "versione essenziale"). Drag & drop non implementato
  (esplicitamente opzionale, rifinitura Fase 6).
- **Gotcha Larastan su un `Collection::first()` di una relazione `BelongsToMany`**: un `?->` dopo
  `->organizations->first()` viene segnalato `nullsafe.neverNull` — assegnare a variabile e confrontare
  esplicitamente (`$organization = ...->first(); return $organization !== null ? ... : ...;`).

## Verifica end-to-end di Fase 1 (US-114, §14)

- **Un test E2E percorre l'intero ciclo di vita con Action reali in sequenza** (`CreateTicket` → N ×
  `ChangeTicketStatus` → ...), mai stato seminato direttamente nel DB: `tests/Feature/Domain/Ticketing/
  TicketLifecycleEndToEndTest.php`.
- **`$this->travelTo(CarbonImmutable::parse(...))` prima di ogni `ChangeTicketStatus::run()`** per costruire
  una sequenza di `ticket_logs` con `occurred_at` deterministici, poi verificare `worked_minutes`/
  `ticket_work_logs` con numeri esatti. Usare sempre un giorno feriale noto (lunedì) per evitare il
  weekend-skip.
- **Un log identificato solo da `to_status` non è univoco** quando lo stesso stato di destinazione viene
  raggiunto più volte (es. `to_status = todo` sia da `assigned → todo` sia da una demozione
  `progress → todo`): filtrare sempre anche su `from_status` prima di uno `->sole()`.
- **Due livelli di difesa distinti contro una transizione/contesto manipolato, da testare separatamente**:
  (1) Filament risolve `$data` dallo stato dello schema dichiarato — un campo iniettato via `setActionData()`
  ma assente dallo schema viene ignorato; (2) `TicketStateMachine::authorize()`/i guard degli attori restano
  comunque la difesa "vera", verificabile chiamando `ChangeTicketStatus::run()` direttamente con un
  `context` impersonato.

## Badge di navigazione combinato con cache (US-604, §8.4)

- `TicketResource` è l'UNICA voce di menu per i ticket (nessuna sottoclasse per filtro, ogni vista è una tab
  di `ListTickets`). Un requisito "badge per voce di menu" su più stati (in attesa/problema/da testare) si
  traduce in UN SOLO badge con il conteggio combinato (somma) più `getNavigationBadgeColor()`/
  `getNavigationBadgeTooltip()` per il dettaglio per categoria (colore = categoria più urgente presente,
  tooltip = riga con i tre conteggi).
- I conteggi riusano direttamente i query object esistenti (`WaitingQuery`/`ProblemTicketsQuery`/
  `ToTestByMeQuery`) — mai una nuova query duplicata solo per il badge.
- Cache: UNA sola chiamata `Cache::remember()` per tutti i conteggi insieme (array associativo), chiave
  scoped per utente (`ticket-navigation-badge-counts:{user_id}`), TTL da
  `config('ticketing.navigation_badges.cache_ttl_seconds')`.
- Gotcha PHPStan: un metodo static **privato** chiamato con `static::` (non `self::`) genera
  `staticClassAccess.privateMethod` — usare sempre `self::` per chiamate a metodi privati statici.
- Il tooltip è reso via Alpine (`x-tooltip`, no attributo `title`): per verificarlo in browser leggere
  l'`innerHTML` del nodo badge, uno screenshot statico senza hover reale non lo mostra.

## Comandi schedulati T3/T4 (`tickets:progress-to-todo`/`tickets:auto-close-released`, US-610, §6.1.5/§10.2)

- **Prima di aggiungere un attore a una riga della tabella dichiarativa, verificare se è già lì**: la riga
  `progress → todo` (T3) aveva già `TransitionActor::System` fra gli attori ammessi fin da US-106. La riga
  `released → done` (T4) NON l'aveva: aggiunta qui — unica modifica allo stato machine per questa story. Non
  fidarsi del commento di classe che elenca "righe non ancora esistenti": controllare sempre `transitions()`
  riga per riga.
- Pattern comando: vedi `app/Domain/CLAUDE.md` § "Comando artisan che esegue un'automazione di sistema".
  Feature flag/env già scaffoldati in Fase 0: nessuna nuova voce, solo la cadenza cron (`config('ticketing.
  progress_to_todo'/'auto_close_released')`) e la soglia giorni lavorativi per T4 (riuso diretto di
  `WorkingDaysCalculator::haveElapsed()`, stesso calcolo del reminder E7).

## Promemoria interno E11 (`tickets:notify-idle-developers`, US-616, §7.5.2/§10.2)

- Il comando vive concettualmente qui (query "developer idle") ma il Mailable/catalogo E-notifiche completo
  (incl. E11) è documentato in `app/Domain/Mail/CLAUDE.md` — consultalo per il pattern di invio.
- "Developer idle" = ha almeno un ticket `assignee_id = lui` **escludendo `done`/`rejected`** E nessuno di
  questi è `status = progress`. Query diretta su `Ticket`, MAI `AssignedToMeQuery`/`scopeVisibleTo()`: quello
  scope filtra per permesso Filament dell'utente, un concetto di UI-authorization estraneo a un comando di
  sistema che deve valutare oggettivamente lo stato dei ticket.
- Vincolo di fascia oraria (09:00–15:30): guard applicativo (`now()->format('H:i')` confrontato con
  `config('ticketing.idle_developer_notice.window_start')`/`window_end`) oltre al cron, testabile con
  `Carbon::setTestNow()`.

## Query object §8.5 per un utente arbitrario, e link cross-cliente (US-705, Fase 7)

- **Un query object `*Query::for(User $user)` non presume `$user === Auth::user()`**: `MyTicketsQuery::
  for($altroUtente)->count()` calcola correttamente il conteggio ticket aperti di un utente DIVERSO da
  quello loggato, perché `Ticket::scopeVisibleTo()` chiama `$user->can(...)` sul parametro ricevuto. Utile
  per metriche aggregate su record altrui senza duplicare la logica di dominio.
- **Un cliente ha solo il permesso `ticket.view.own`**, mai `ticket.view.any`/
  `ticket.manage-internal-fields`: in `ListTickets::getTabs()` vede sempre e solo le tab "I miei
  ticket"/"Archivio", che sovrascrivono la query con `MyTicketsQuery::for($user)` **a prescindere** dai query
  param nell'URL. Un link dalla dashboard cliente verso un `requester_id` diverso naviga senza errori ma il
  filtro viene ignorato dal tab — nessun leak, ma anche nessun risultato utile.
