# Dominio Fundraising (Fase 5)

Si carica quando lavori sotto `app/Domain/Fundraising/*`. Vedi anche `app/Domain/CLAUDE.md` per i pattern
condivisi e `app/Filament/CLAUDE.md` per i pattern generici usati qui (RelationManager, filtri, gotcha di
test Livewire come `fillForm()`/`mountTableAction`).

## Griglia reattiva (Placeholder + `Get`) e permesso distinto (`FundraisingOpportunityResource`, US-504)

- **Un `Placeholder::make(...)->content(function (Get $get) {...})` SI aggiorna da solo quando un altro campo
  dello stesso schema ha `->live()`**, senza bisogno di marcare il Placeholder stesso: ogni richiesta Livewire
  innescata da un campo `->live()` ri-renderizza l'intero schema e rivaluta tutte le closure
  `content()`/`visible()`. Nessun `wire:poll`/computed property extra per un totale calcolato in tempo reale
  da altri campi del form.
- **Gotcha di verifica Playwright su un campo `->live()` senza `onBlur: true`**: il default (evento `input`,
  nessun debounce esplicito) parte comunque con un piccolo ritardo lato Livewire. Uno script che fa `fill()`
  + `blur()` + `waitForLoadState('networkidle')` può leggere `networkidle` PRIMA che la richiesta Livewire sia
  partita (falso positivo "non reattivo"). Serve un `waitForTimeout(...)` esplicito (800–1200ms) DOPO il
  cambio, PRIMA di `waitForLoadState('networkidle')`.
- `Tabs`/`Tab` di `Filament\Schemas\Components\Tabs` sono puro layout: non alterano lo state path dei campi
  annidati, quindi avvolgere un form esistente non richiede toccare i test che usano `fillForm()`.
- **Un tab/azione dentro una Resource già gated da un permesso "generico" può richiedere un permesso PIÙ
  specifico per una singola sotto-funzionalità** (qui `fundraising.update` apre la pagina Edit, ma
  `fundraising.evaluate` gate solo il tab di valutazione — già esistente nel catalogo/Policy, semplicemente
  mai consumato prima). `->visible(fn () => Auth::user()?->can(Permission::X))` basta come UI gating
  (Filament esclude i componenti non visibili anche dalla dehydration di `getState()`), ma un controllo
  esplicito lato server nel punto dove i dati vengono persistiti resta comunque la difesa autorevole.
  Verificare sempre prima nel catalogo `Permission`/nelle `Policy` esistenti se un permesso più specifico è
  già stato predisposto da una fase precedente, prima di riusare quello generico.

## `view.involved` per-record vs per-modulo, e query "coinvolti" (`FundraisingProject`, US-506)

- **`fundraising.view.involved` non ha lo stesso significato su tutti i model del dominio**: su
  `FundraisingOpportunity` è un OK generico (qualunque customer vede qualunque opportunità, §6.6.4), ma su
  `FundraisingProject` è per-record (§6.6.3: solo capofila/partner/responsabile/creatore).
  `FundraisingProjectPolicy::view()` quindi NON può delegare a `viewAny()` come
  `FundraisingOpportunityPolicy::view()` fa.
- **Pattern "coinvolti" (OR su più colonne + una pivot)**: `FundraisingProject::scopeInvolving(Builder
  $query, User $user)` combina `where('lead_user_id', ...)->orWhere('responsible_user_id', ...)
  ->orWhere('created_by', ...)->orWhereHas('partners', ...)` dentro un unico `where(function ...)`
  innestato, così resta sicuro anche incatenato dopo altri `where()`.
- **Fix del bug v1 `partnerCustomers()`**: v1 filtrava i partner con ruolo customer con `whereHas('roles',
  ...)` su una colonna JSON (query non eseguibile). In v2 un `whereHas('roles', fn ($q) => $q->where('name',
  UserRole::Customer->value))` incatenato su una `BelongsToMany` esistente funziona senza join manuali.
- **Macchina a stati "leggera"**: `FundraisingProjectStateMachine` replica solo la parte
  tabellare/dichiarativa di `TicketStateMachine` (`transitions()`/`canTransitionTo()`/`authorize()`), SENZA
  il livello `TransitionActor`/`effects`/guard-per-transizione: nessun AC richiede un'autorizzazione
  differenziata per attore sulla stessa transizione — l'autorizzazione resta quella uniforme della Policy
  (`fundraising.update`). Non aggiungere la macchina completa "per coerenza" se l'AC non la richiede.

## Filtri "capofila/partner/coinvolti", RelationManager, select cross-dominio (`FundraisingProjectResource`, US-507)

- **`SelectFilter::make(...)->relationship('nomeRelazione', 'colonnaTitolo')` funziona sia su una
  `BelongsTo` sia su una `BelongsToMany`**: in test, `filterTable('lead_user_id', $id)` /
  `filterTable('partner', $id)` impostano entrambi `['value' => $id]` — nessuna differenza nel test.
- **Filtro booleano "coinvolti" con `Filter::make(...)->toggle()->query(...)`**: in test
  `filterTable('nome', true)` (`isActive => true`), MAI `['value' => ...]` come per un `SelectFilter`.
- **`Livewire::test(NomeRelationManager::class, [...])->callTableAction('attach', data: [...])` NON funziona
  più in modo affidabile in questo ambiente**: fallisce con `"mountedActions.0.data.recordId": "The record
  field is required"` anche passando `recordId` esplicito. Workaround verificato:
  `mountTableAction('attach')` → `->set('mountedActions.0.data.recordId', $id)` →
  `callMountedTableAction()` → `assertHasNoTableActionErrors()`.
- **CRITICO: `fillForm()` nei test Livewire NON applica più correttamente lo stato in questo ambiente, su
  QUALUNQUE form Filament del repo, non solo Fundraising** — root cause isolata a
  `Filament\Schemas\Concerns\InteractsWithSchemas::fillFormDataForTesting()`, PREESISTENTE al lavoro di Fase 5
  (verificato con `git stash`: già 7/16 test rotti in `TicketResourceTest.php` a HEAD prima di questa story,
  mai investigato perché nessuna story precedente aveva rilanciato quel file). Un `->set('data.<campo>',
  valore)` esplicito applica invece lo stato correttamente. **Per qualunque nuovo test che compila un form
  Filament**: preferire `->set('data.<campo>', valore)` campo per campo finché la causa non viene isolata
  (possibile regressione di `filament/filament`/`livewire/livewire` in `composer.lock`, mai bisecata).
- **Un `RelationManager` con una sola tabella si carica comunque "lazy" via `x-intersect`
  (IntersectionObserver Alpine.js)**: nell'HTML iniziale appare come placeholder vuoto `role="status"
  aria-busy="true"`, senza errori — uno screenshot Playwright `fullPage: true` subito dopo
  `waitForLoadState('networkidle')` può catturarlo (falso negativo). Prima dello screenshot,
  `scrollIntoViewIfNeeded()` sull'elemento e `waitForSelector(..., { state: 'detached' })`.
- **Un `Select::make(...)->relationship(..., modifyQueryUsing: ...)` su un model di un dominio DIVERSO da
  quello della Resource corrente** (qui `Ticket` → `FundraisingProject`) è il posto giusto per applicare a
  mano la stessa logica di `Policy::view()`/`viewAny()` del dominio target, invece di esporre l'intero
  elenco indiscriminatamente solo perché il campo vive in una sezione già gated da un permesso di un dominio
  diverso. `modifyQueryUsing` restringe le opzioni, non è (da solo) una validazione server-side dura sul
  valore salvato.

## Vista cliente in sola lettura come Resource Filament separata (`Customer*Resource`, US-508)

- **Pattern riusabile "stessa entità, due Resource Filament distinte, staff CRUD vs cliente sola lettura"**:
  quando un dominio ha sia una Resource staff sia una vista cliente con permessi/scope diversi, NON provare a
  far convivere i due casi d'uso in un'unica Resource con `visible()` condizionali — creare una seconda
  Resource dedicata (`Customer<Nome>Resource`), stesso model, `getPages()` che registra SOLO
  `'index'`/`'view'`, `table()`/`infolist()` con solo i campi previsti, `headerActions([])`/
  `toolbarActions([])`, un solo `ViewAction::make()`.
- **Evitare due voci di navigazione duplicate quando entrambe le Resource condividono lo stesso
  `Policy::viewAny()` permissivo**: la Resource staff resta ristretta a `->can(FundraisingViewAny)`; la
  Resource cliente usa il gate simmetrico e opposto `->can(FundraisingViewInvolved) && !
  ->can(FundraisingViewAny)` — nella matrice ruoli attuale l'unico ruolo con `view.involved` ma senza
  `view.any` è `customer`.
- **Due scope diversi sullo stesso model per due significati diversi di "coinvolto"**:
  `scopeInvolving()` (uso interno staff, US-506) include capofila/partner/responsabile/creatore;
  `scopeInvolvingAsCustomer()` (nuovo, uso esclusivo `CustomerFundraisingProjectResource::getEloquentQuery()`)
  è più stretto (SOLO capofila o partner — responsabile/creatore sono ruoli interni). Se compaiono due AC che
  descrivono "coinvolgimento" con perimetri diversi sullo stesso model, non assumere che debbano condividere
  lo stesso scope solo perché il nome è simile.
- **Difesa a due livelli per "il dettaglio non è raggiungibile via URL diretto se non coinvolto"**:
  `getEloquentQuery()` incatena SEMPRE lo scope PRIMA che Filament risolva il route-model-binding — un record
  fuori scope dà **404**, non 403. Il test HTTP deve asserire `assertNotFound()`, non `assertForbidden()`
  (quello è per "nessun permesso sul modulo intero" a livello di indice).

## Checkpoint di fine Fase 5 (US-509) — fatti sui dati reali

- **La griglia di valutazione fundraising (§6.6.2) non ha MAI avuto un solo punteggio reale in tutta la
  storia v1**, confermato due volte con dati reali indipendenti (schema, US-213, e di nuovo con un
  `v1:import --anonymize` fresco su `v1dumps/latest.sql`: 21 opportunità, 33 progetti, 9 partner, **0** righe
  `fundraising_evaluation_scores`). L'AC "i totali ricalcolati devono coincidere con quelli già presenti
  dall'ETL" è quindi banalmente vero sui dati odierni — verificarlo in modo non vacuo richiede di persistere
  punteggi REALI tramite `SaveEvaluationScores` (mai un insert diretto). Da segnalare al committente se serve
  una verifica non vacua, non un lavoro da anticipare senza quei dati.
- **Aggiungere/rimuovere un case enum temporaneo per verificare un AC** ("un criterio aggiunto a runtime
  viene incluso nel calcolo") lascia SEMPRE una traccia di verifica con `git diff --stat` sul file dell'enum
  prima di `git checkout --` per confermare che il ripristino sia stato bit-per-bit identico.
