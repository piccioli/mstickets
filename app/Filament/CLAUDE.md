# Filament — pattern generici di pannello (Resource, RelationManager, navigazione, tema)

Si carica quando lavori sotto `app/Filament/*`. Contiene i pattern Filament RIUSABILI da qualunque dominio —
la logica/i permessi specifici di una singola Resource vivono nel `CLAUDE.md` del dominio a cui appartiene
(`app/Domain/<Modulo>/CLAUDE.md`), spesso con un pointer da qui. Vedi anche la radice del repo per i gotcha
trasversali di test Filament (`fillForm()` rotto, notifiche Postgres `text ->> unknown`) e per la sezione
"Verifica in browser" (Chrome headless/Playwright).

## Design system — come è cablato il tema Filament (US-004/US-005)

- Fonte di verità dei token visivi: `docs/design-system.md` (documento) e `resources/css/theme.css` (custom
  properties `--ms-*`). Il tema Filament/Tailwind v4 **deve** derivare da queste custom properties, non
  riscrivere hex/valori a mano una seconda volta.
- Il progetto Claude Design (tool MCP `DesignSync`) contiene DUE design distinti bundlati insieme: il
  mockup applicativo (teal `#17a180`, font Nunito Sans — quello usato per i token) e, sotto `_ds/`, una copia
  in sola lettura del design system del sito marketing (verde pino `#1D574B`, font Manrope) che **non** è la
  fonte per il pannello. Non confonderli se serve ri-consultare il design.
- Nessun SVG del logo/mark è mai stato fornito: solo PNG raster in `assets/`. Se serve un logo vettoriale
  (favicon, sidebar ad alta densità), richiederlo al committente invece di tracciarlo dal PNG.
- I colori di stato ticket nel mockup coprono solo 6 dei 12 case dell'enum `TicketStatus` (§5.2): vedi la
  tabella di mappatura/gap in `docs/design-system.md` prima di implementare badge di stato.
- **`App\Support\DesignTokens`** legge e parsa `resources/css/theme.css` a runtime (regex su `--ms-*:
  valore;`) ed espone `DesignTokens::get('ms-brand')`/`DesignTokens::primaryFontFamily()`. `AdminPanelProvider`
  usa **solo** questa classe per `->colors()`/`->font()` — mai un hex scritto a mano nel Provider. Per un
  nuovo colore (danger/warning/ecc.), aggiungere prima il token in `theme.css`, poi leggerlo da
  `DesignTokens::get()`, mai il contrario.
- `->colors([...])` di Filament accetta direttamente una stringa hex: internamente chiama
  `Color::generatePalette()`. **Non serve** `Color::hex()`/`Color::rgb()` a meno di dover passare un array di
  shade già pronto.
- Il tema Vite-compilato vive in `resources/css/filament/admin/theme.css` (importa
  `vendor/filament/filament/resources/css/theme.css` + `resources/css/theme.css`), registrato in
  `vite.config.js` e in `AdminPanelProvider` via `->viteTheme(...)`. Le direttive `@source` sono già in quel
  file — non serve aggiungerle altrove per nuove viste Blade custom sotto `resources/views/filament/`.
- Gli asset di brand (`assets/*.png`) NON sono serviti dal web server da quella posizione: una copia va
  tenuta in `public/images/branding/` (referenziata con `asset(...)` in `AdminPanelProvider` per
  `brandLogo`/`darkModeBrandLogo`/`favicon`). `assets/` resta la posizione "sorgente" documentata, `public/
  images/branding/` la copia servibile — se il committente fornirà un SVG in futuro, aggiornare entrambe.
- Verifica di temi/branding senza browser MCP: vedi radice del repo § Verifica in browser (tecnica
  `docker compose up` + `curl` sull'HTML per branding/font/link tema in `<head>`).

## Prima Filament Resource del repo — `UserResource`/`RoleResource` (US-021)

- Scaffoldate con `php artisan make:filament-resource <Model> --model-namespace="..." --view`. Per un modello
  fuori da `App\Models` passare sempre `--model-namespace`.
- **Proprietà statiche tipo `$navigationGroup`/`$navigationIcon` devono usare il tipo union dichiarato dalla
  classe base** (`UnitEnum|string|null`, non `?string`): un tipo più stretto di quello ereditato è un
  **fatal error a runtime** (verifica covarianza proprietà tipizzate al caricamento della classe, prima
  ancora di test/analisi statica).
- **Resource Filament di sola lettura su un modello SENZA una Policy Laravel** (qui `Spatie\Permission\
  Models\Role`, un modello vendor): il deny-by-default nativo nega tutto per mancanza di Policy, quindi
  bisogna **sovrascrivere direttamente `canViewAny()`/`canView()`/`canCreate()`/`canEdit()`/`canDelete()`/
  `canDeleteAny()`** nella classe Resource. Per renderla realmente di sola lettura: registrare in
  `getPages()` **solo** `index`/`view`, rimuovere `CreateAction`/`EditAction` dalle pagine, e comunque
  forzare `canCreate()`/`canEdit()`/`canDelete()`/`canDeleteAny()` a `false` esplicitamente (non basta
  l'assenza delle pagine: alcuni pulsanti/azioni di terze parti potrebbero comunque tentare l'azione). Questa
  è la Convention "B" (Resource senza Policy propria) — la Convention "A" è una Resource Policy-backed su un
  modello di dominio con Policy (`UserResource`, `EmailMessageResource`, `ActivityReportResource`,
  `DocumentationPageResource`), dove nessun metodo `can*()` viene sovrascritto.
- **Larastan non conosce le proprietà magiche (`->name`) né i metodi di relazione dei modelli Eloquent che
  vivono FUORI dai `paths` analizzati** (qui `Spatie\Permission\Models\{Role,Permission}`, in `vendor/`).
  Sintomi: `Access to an undefined property`/`Call to an undefined method` anche se esistono davvero. Pattern
  per aggirarlo: usare `$model->getAttribute('name')` invece della proprietà magica; per una relazione
  many-to-many il cui provenienza-per-riga serve, sostituire il giro Eloquent con una **query diretta
  `DB::table(...)` sulle tabelle pivot** (restituisce `stdClass`, nessun errore Larastan, bonus: una sola
  query invece di N+1). Il contrario funziona regolarmente: un modello Spatie passato come **parametro di
  closure esplicitamente tipizzato** (es. `fn (SpatieRole $record) => $record->name` dentro
  `CheckboxList::getOptionLabelFromRecordUsing()`) non genera errori.
- **`Collection<int, Model>::map()`/`::each()` non accettano un callback tipizzato su un sottotipo**
  (`Closure(Role): string` quando la Collection è tipata `Collection<int, Model>`, il default quando la
  relazione Spatie non dichiara i generici): errore di varianza dei parametri, non risolvibile restringendo
  il tipo del parametro closure. Usare un parametro **non tipizzato** (`fn ($permission) => ...`) e narrowing
  solo internamente (`getAttribute()`), oppure evitare del tutto `map()`/`each()` su quella collection.
- Pattern "sezione del form visibile solo con un permesso specifico"
  (`Section::make(...)->visible(fn (): bool => Auth::user()?->can(Permission::X))`): un componente Filament
  nascosto da `->visible()` non viene dehydratato — quindi un admin senza il permesso che salva il form non
  altera involontariamente ruoli/permessi che non doveva nemmeno vedere.
- Per collegare un `CheckboxList`/`Select` a una relazione many-to-many Spatie con **etichette localizzate
  diverse dal valore grezzo in colonna**: `->relationship('roles', 'name', modifyQueryUsing: ...)` **non**
  usa `name` come etichetta se si aggiunge anche `->getOptionLabelFromRecordUsing(...)`: quest'ultimo ha
  sempre precedenza per la label mostrata, ma il secondo argomento di `relationship()` resta comunque
  necessario per dire a Filament quale relazione Eloquent usare per salvare la selezione.
- Testare una Filament Resource **senza** `pestphp/pest-plugin-livewire`: usare
  `\Livewire\Livewire::test(PageClass::class, ['record' => $model->getKey()])` direttamente (non l'helper
  globale `livewire()`). Serve `Filament::setCurrentPanel('admin')` in un `beforeEach()` prima di ogni test
  che monta pagine Filament fuori da una richiesta HTTP reale.

## Colonna custom con markup HTML — `ViewColumn`, non `TextColumn->html()` (US-403)

- **`TextColumn::make(...)->html()->formatStateUsing(fn () => new HtmlString(...))` NON è il pattern giusto
  per un layout non banale** (es. una barra di progresso con più `<div>` annidati): Filament avvolge lo stato
  di un `TextColumn` in `<div class="fi-ta-text ...">`, che applica troncamento/line-clamp pensato per testo
  — dei `<div>` a blocco annidati dentro quel wrapper vengono compressi/non visibili. Per markup HTML non
  banale usare `Filament\Tables\Columns\ViewColumn::make('nome')->view('filament.tables.columns.mia-vista')`:
  la vista riceve `$record` direttamente, senza il wrapper di troncamento testo — vedi
  `app/Filament/Resources/Tags/Tables/TagsTable.php` (colonna `sal`) e
  `resources/views/filament/tables/columns/tag-sal-bar.blade.php`. `->getStateUsing()` resta utile su una
  `ViewColumn` solo per rendere lo stato ispezionabile da `assertTableColumnStateSet()` nei test.
- **Qualunque classe Tailwind usata SOLO in una vista Blade nuova non compare nel CSS finché non si
  ricompila**: il pannello usa `resources/css/filament/admin/theme.css` compilato in `public/build/` da
  Vite; su questa macchina non gira un dev server Vite in watch, quindi `public/build/assets/theme-*.css`
  resta quello dell'ultima build finché non si lancia `npm run build` a mano. Sintomo: HTML corretto ma
  nessun elemento visivo, senza errori/log. Dopo aver aggiunto/modificato markup con classi Tailwind non
  ancora usate altrove, eseguire `npm run build` PRIMA di uno screenshot di verifica. `public/build/` non è
  tracciato da git (verificare `git status`), quindi la rebuild non sporca il commit.

## Pixel-perfect login/recupero password (ciclo `ralph/login-design-pixel-fixes`)

- **Verifica visiva reale disponibile**: vedi radice del repo § Verifica in browser (Chrome headless senza
  MCP). Le note qui sotto sono i bug CSS/Alpine specifici scoperti su questa view.
- **Pipeline font Manrope verificata end-to-end e risultata già corretta**: `vite.config.js` dichiara
  `bunny('Manrope', {...})`, i manifest font contengono i relativi `@font-face`/preload,
  `@fonts('manrope')` in `filament/auth/layout.blade.php` li inietta correttamente, e ogni selettore CSS che
  ne ha bisogno dichiara `font-family: var(--mkt-font-sans)` esplicitamente — incluso `.mkt-field input` con
  `!important` perché gli elementi form nativi NON ereditano il font dagli antenati per default. Regression
  guard: `tests/Feature/Http/AuthFontLoadingTest.php`.
- **Gotcha selettore discendente vs figlio diretto** in `components/auth/panel.blade.php`:
  `.mkt-auth__panel img` (pensato solo per la foto di sfondo full-bleed) intercettava per errore anche
  `img.mkt-logo`, annidato più a fondo, applicandogli `position: absolute; inset: 0` e togliendolo dal
  flusso — qualunque `margin`/spaziatura diventava ininfluente. Fix: combinatore di figlio diretto
  (`.mkt-auth__panel > img`). Se un elemento non risponde a `margin`/spacing nonostante il CSS compilato sia
  corretto, controllare prima se ha ereditato `position: absolute/fixed` da un selettore discendente troppo
  generico scritto per un altro elemento. Diagnosi utile: riprodurre in un file HTML statico isolato con la
  stessa CSS compilata, per escludere interferenze Alpine/Livewire. Regression guard:
  `tests/Unit/AuthHeroLogoSpacingTest.php`.
- **Misurare un dettaglio geometrico del mockup quando "a occhio" non basta**: uno script Python veloce con
  PIL+numpy (crop, mask su un range di grigio, ricerca della riga/colonna dove la coordinata minima si
  stabilizza) è più affidabile della sola ispezione visiva. Non serve conoscere lo scale factor esatto dello
  screenshot: basta la proporzione relativa fra il raggio misurato e le dimensioni dell'elemento.
- **Gotcha `Livewire::test(...)->assertSeeText($value)`**: a differenza di `assertSeeHtml()`,
  `assertSeeText()` ha `$escape = true` come default e passa il valore atteso per `e()` prima di confrontarlo
  — un apostrofo dritto (`'`) diventa `&#039;` e non matcha più testo Blade statico (apostrofo letterale non
  incodificato). Se un'asserzione fallisce con un valore atteso già HTML-encoded, passare `false` come
  secondo argomento (`assertSeeText("...l'account...", false)`), come già si fa con `assertSee($value,
  false)`.
- **Il plugin Alpine `@alpinejs/focus` (`x-trap`) è già caricato globalmente su tutte le pagine Filament**
  (i modali nativi di Filament lo usano internamente): per una modale Alpine inline "fatta a mano" si può
  usare `x-trap.noscroll="apertaBool"` senza caricare nulla in più — intrappola il focus e lo ripristina
  automaticamente sull'elemento che aveva il focus prima dell'apertura. Nessuna regola CSS globale per
  `[x-cloak]` è definita nei bundle: per una modale con `x-show`, impostare `style="display: none;"` inline
  invece di affidarsi a `x-cloak`, altrimenti c'è un breve flash del contenuto prima che Alpine si
  inizializzi.

## Primo Filament `RelationManager` del repo — autorizzazione ad ogni hydrate (US-407)

- **Un `RelationManager` (es. `UsersRelationManager` su `users()` di `OrganizationResource`) ri-autorizza
  `viewAny` sul MODEL CORRELATO ad OGNI hydrate Livewire, non solo alla prima mount**: anche con permesso
  sufficiente sulla Resource proprietaria, il RelationManager resta bloccato con un 403 silenzioso in fase di
  hydrate se manca il permesso di `viewAny` sul model relazionato (`RelationManager::
  hydrateCanAuthorizeAccess()` chiama `canViewForRecord()` → `authorize('viewAny', $relatedModelClass)`).
  Sintomo in test: non un'eccezione leggibile ma `ErrorException: Attempt to read property "mountedActions"
  on null` dentro `TestsActions::callAction()` — il vero errore (403) va scoperto avvolgendo con
  `$this->withoutExceptionHandling()` e ispezionando lo status code. Quando si aggiunge un nuovo
  RelationManager, elencare esplicitamente TUTTI i permessi coinvolti (proprietario + correlato) sia nei
  test sia nella documentazione utente.
- **`AttachAction`/`DetachAction`/`DetachBulkAction`/`AssociateAction`/`DissociateAction` controllano SOLO
  `RelationManager::isReadOnly()`** (dipende dalla pagina), non un metodo di Policy dedicato tipo
  `attach()`/`detach()`: la sicurezza reale del "chi può aggiungere/rimuovere un membro" è delegata
  interamente a QUALI PAGINE espongono il RelationManager.
- Test di un'azione header (`AttachAction`) o di record (`DetachAction`): usare
  `Livewire::test(NomeRelationManager::class, ['ownerRecord' => $model, 'pageClass' => EditXxx::class])
  ->callTableAction('attach', data: ['recordId' => $id])` / `->callTableAction('detach', record:
  $relatedRecord)` — **non** `->callAction(...)` (è per azioni di pagina). **Aggiornamento (US-507)**: in
  questo ambiente `callTableAction('attach', data: [...])` non è più affidabile — vedi
  `app/Domain/Fundraising/CLAUDE.md` §US-507 per il workaround
  (`mountTableAction`/`set('mountedActions.0.data.recordId', ...)`/`callMountedTableAction`).

## Gruppo di navigazione condiviso staff/cliente (US-602, §8.4)

- **Quando una Resource è visibile sia allo staff sia al customer** (`TicketResource`, `ActivityReportResource`,
  `DocumentationPageResource`), il gruppo di navigazione **non può essere la proprietà statica**
  `$navigationGroup` (condivisa da TUTTI gli utenti): sovrascrivere `public static function
  getNavigationGroup(): string|UnitEnum|null` (firma esatta della classe base) che ramifica su
  `Auth::user()?->hasRole(UserRole::Customer->value)`, restituendo `'Area cliente'` per un customer, il
  gruppo originale altrimenti. Per una Resource **esclusivamente** customer (`canViewAny()`/`canAccess()` già
  vero SOLO per quel ruolo), la proprietà statica resta corretta e più semplice.
- **Ogni voce di navigazione registrata globalmente in `AdminPanelProvider` va riverificata contro un login
  reale da customer**, non solo le Resource toccate dalla story: la voce "Mailpit" (US-324) era gated solo su
  ambiente + URL configurato, **nessun controllo di ruolo** — quindi visibile anche a un customer in
  locale/staging. Scoperto SOLO nello screenshot di verifica browser richiesto dalla story (mai dai test).
  `MailpitNavigationItem::isVisible()` ora nega esplicitamente se l'utente ha il ruolo customer, PRIMA del
  check su ambiente/URL. Lezione generale: la verifica in browser di una story di navigazione deve
  controllare l'INTERA sidebar per il ruolo target, non solo le voci esplicitamente elencate nell'AC.
- `App\Filament\Pages\Dashboard` (root `/`, landing per ruolo) va tenuta fuori da qualunque voce di
  navigazione (`protected static bool $shouldRegisterNavigation = false;`) da quando OGNI ruolo autenticato
  viene reindirizzato altrove nel suo `mount()` — vedi `app/Domain/Ticketing/CLAUDE.md` §US-113.

## Sotto-menu di navigazione — voci genitore senza URL (US-940)

Per annidare voci sotto un "sotto-menu" dentro un gruppo (es. `Anagrafica CAI` → Sezioni / Bilanci / Gruppi regionali) NON servono più
`NavigationGroup`: si usa la funzione nativa Filament 4. Il genitore è un `NavigationItem::make('Etichetta')->group(...)->sort(...)` senza URL,
registrato in `AdminPanelProvider::navigationItems()`; i figli dichiarano `$navigationParentItem = 'Etichetta'` (stessa stringa) e `$navigationSort`.
Filament scarta un genitore senza figli visibili, quindi chi non ha i permessi dei figli non vede un sotto-menu vuoto; un genitore senza URL mostra
sempre i figli espansi. Gotcha: "Sezioni" è anche il nome del `NavigationGroup` della pagina cliente `CustomerRegionalSectionsDashboard` — il genitore
`Sezioni` di Anagrafica CAI è una VOCE dentro un altro gruppo, quindi non collide; non trasformarlo in gruppo (le due etichette si fonderebbero) e non
rinominare la stringa del genitore senza aggiornare i `$navigationParentItem` dei figli (il legame è per etichetta, nessun errore se non combacia: il figlio
non viene annidato).

