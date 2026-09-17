# Dominio CaiDirectory — anagrafica CAI/RUNTS (Fase 8-9)

Si carica quando lavori sotto `app/Domain/CaiDirectory/*`. Vedi anche `app/Domain/CLAUDE.md` per i pattern
condivisi, `app/Filament/CLAUDE.md` per i pattern generici (export da Action, Infolist riusabile, pagina con
parametro di rotta) e `docs/collaudo/CLAUDE.md` per il metodo di generazione del manuale di collaudo Fase 8.

## Schema — chiave naturale string non incrementale (US-801)

- **Prima volta nel repo che un modello Eloquent usa una chiave primaria naturale string** (non
  auto-incrementale): `CaiSection` (`codice_cai`), `CaiSubsection` (`cai_codice`), `CaiRuntsRegistration`
  (`id_runts`) — chiavi del datapack sorgente RUNTS-CAI, non generate da Orchestrator. Pattern: migrazione
  con `$table->string('<chiave>')->primary()` (niente `$table->id()`), modello con `protected $primaryKey =
  '<chiave>'; protected $keyType = 'string'; public $incrementing = false;`. Le FK verso queste tabelle sono
  colonne string (`$table->string('cai_section_id')` + `->foreign(...)->references('codice_cai')
  ->on('cai_sections')`), mai `foreignId()`/`constrained()`.
- Le altre 3 tabelle del dominio (`cai_financial_statements`, `cai_board_members`, `cai_documents`) restano
  sullo schema standard (`$table->id()`), perché non hanno una chiave naturale nella fonte.
- `cai_sections.region`/`cai_runts_registrations.region` sono **string libere**, NON castate sull'enum
  `App\Domain\Identity\Enums\Region` (Fase 7): il dataset reale contiene valori come `EXTRA REGIONE` che non
  sono una delle 20 regioni italiane ufficiali — castare romperebbe l'import per quelle righe. Se una fase
  futura deve confrontare `region` con `users.region`, serve una normalizzazione esplicita a quel punto, non
  un cast condiviso.
- **`php artisan test` (l'intero suite) crasha in modo deterministico e preesistente**, indipendente da
  questa story (verificato con `git stash`): sempre subito dopo `Tests\Unit\Domain\Mail\
  MailPipelineConfigTest`, appena prima dei test in `tests/Unit/Domain/Mail/Parsers/*` che esercitano
  `symfony/html-sanitizer` → `\Dom\HTMLDocument` (API DOM nativa di PHP 8.4) — OOM con `memory_limit`
  default, `exit 255` senza output (compatibile con segfault nativo) anche con 512M. Ogni file preso
  singolarmente passa. Per verificare "Tests pass" su una story che non tocca Mail: eseguire i test del
  proprio dominio mirati, non fidarsi del codice di uscita di una run senza filtri.

## Import — `cai:import-datapack` (US-802): due gotcha di dati reali non visibili sulla fixture

- **`sezioni_cai.cai_indirizzo_sede`/`cai_indirizzo_postale` (e l'equivalente di `sottosezioni_cai`) sono
  JSON di geocoding, non testo semplice** (100% delle righe reali: `{"address1":...,"address2":...,"number":
  ...,"zip":...,"city":...,"province":...,"nation":...,...}`), a differenza di `enti.sede_indirizzo` (sempre
  testo semplice). Inserire il JSON grezzo in `cai_sections.address`/`postal_address` (`string(255)`)
  trabocca la colonna su Postgres per alcune righe reali (280+ caratteri).
  `CaiRuntsAddressFormatter::format()` lo converte in una riga leggibile (max osservato 142 caratteri) — se
  un futuro import aggiunge un nuovo campo indirizzo dal datapack, verificare prima col dataset reale se è
  JSON o testo semplice (`json_decode($valore) !== null`), non assumerlo.
- **`sezioni_cai.cai_lat` ha almeno una riga reale con coordinate palesemente corrotte alla fonte** (es.
  `25614`, non una latitudine) che trabocca `decimal(10,7)` (|x| < 1000) e fa fallire l'intero insert.
  `CaiDatapackImporter::toCoordinate()` scarta a `null` qualunque valore |x| >= 1000 invece di propagarlo. La
  fixture di test (`CaiImportDatapackCommandTest.php`) include entrambi i casi — usata come riferimento per
  estendere la fixture se emergono altri campi con lo stesso problema.

## Filament staff `CaiSectionResource` (US-804): infolist riusabile, permesso unico, niente Policy

- `App\Filament\Resources\CaiSections\Schemas\CaiSectionInfolist::configure()` è una classe statica
  indipendente dalla Resource (non un metodo `infolist()` inline): US-806 (dashboard cliente Sezione) e
  US-807 (dettaglio da Gruppo Regionale) **riusano lo stesso componente/vista**, cambiando solo
  l'autorizzazione — vedi `app/Filament/CLAUDE.md` per il pattern generico "Schema riusabile fra Resource e
  pagina custom".
- I tab "Dati RUNTS"/"Bilanci"/"Allegati" leggono da `RepeatableEntry::state(fn (CaiSection $record) =>
  ...)` esplicito, perché `cai_runts_registrations` è 0..n rispetto a una sezione e bilanci/documenti pendono
  da lì (`flatMap(fn ($r) => $r->financialStatements)`). Ogni `RepeatableEntry` ha un `->placeholder(...)`
  esplicito per il caso vuoto (normale, non un bug).
- `cai_sections.region` è testo libero dal datapack (include "EXTRA REGIONE"): il filtro regione della
  tabella costruisce le opzioni da `CaiSection::query()->distinct()->pluck('region', 'region')`, mai
  dall'enum applicativo, altrimenti alcuni valori reali sparirebbero dal filtro senza errore.
- Nessuna Policy dedicata: `CaiSection`/`CaiDocument` sono popolati solo dall'importer, senza owner
  applicativo. Un unico permesso di catalogo (`Permission::CaiDirectoryView`) gate sia i metodi `can*()`
  della Resource sia il controller di download (`CaiDocumentDownloadController`, verificato con
  `Auth::user()?->can(...)` diretto, non `AuthorizesRequests`). Se una story futura introduce un vincolo di
  scope (es. US-807, solo sezioni della propria regione), aggiungerlo come controllo aggiuntivo lato server
  nel punto d'accesso specifico, non cambiare questo permesso di catalogo.
- Verifica in browser via Playwright ad-hoc (nessun MCP registrato): richiede prima
  `php artisan cai:import-datapack` sul dataset reale locale — verificare con `CaiSection::count()` prima di
  assumere che i dati ci siano già (`v1:import` non tocca le tabelle CAI, ma un `migrate:fresh` sì).
  `collaudo:ensure-manager-account` richiede a sua volta `RolePermissionSeeder` già eseguito.

## Export di file da un'azione Filament — header/record action → download browser (US-805)

- Un'`Action::make(...)->action(fn (HasTable $livewire) => ...)` il cui closure ritorna una
  `StreamedResponse`/`BinaryFileResponse` **funziona out-of-the-box** come download browser: l'hook nativo
  Livewire `SupportFileDownloads` lo intercetta automaticamente — nessun controller/route dedicato (a
  differenza del pattern PDF già in uso da `CaiDocumentDownloadController`/
  `ActivityReportPdfDownloadController`).
- `HasTable $livewire` dentro un `->action()` di `headerActions()` dà accesso a
  `$livewire->getFilteredSortedTableQuery()`: la stessa query (filtri+ricerca correnti, senza paginazione)
  che la tabella usa per popolare la pagina — necessario per un export "solo le righe correntemente
  filtrate", non `Model::query()->get()` diretto.
- Test Pest: `Livewire::test(ListRecords::class)->filterTable(...)->callTableAction('nomeAzione')`, poi
  `$test->effects['download']` (chiave magica, non un metodo) dà `['name' => ..., 'content' => <base64>,
  'contentType' => ...]` — `base64_decode()` per asserire il contenuto reale.
- Formati generati: CSV (`fputcsv` su `php://temp`), XLSX (`openspout/openspout`, già dipendenza transitiva
  di `filament/actions` — **non** aggiungere `maatwebsite/excel`; scrivere su un file temporaneo con
  `Writer::openToFile($tempPath)`, mai `openToBrowser()`/`php://output` diretto dentro una
  `streamDownload`, perché `openToBrowser()` chiama `header()` internamente e collide con gli header già
  impostati), GeoJSON (`json_encode` a mano — Filament non lo supporta nativamente). In test, l'XLSX si
  verifica con `OpenSpout\Reader\XLSX\Reader` (il contenuto binario non è deterministico byte-per-byte fra
  run per i timestamp nello zip).

## Mappa Leaflet in una pagina Filament custom (US-805)

- Nessuna dipendenza Leaflet preesistente: introdotta via CDN (`unpkg.com/leaflet@1.9.4`) solo nella view
  Blade della pagina mappa, non come asset globale.
- Una pagina Filament custom con `wire:navigate` attivo (default del pannello) non ha garantito un
  `DOMContentLoaded` a ogni navigazione: lo script inline va eseguito immediatamente (IIFE), non dentro
  `DOMContentLoaded`, con un guard su `container.dataset.leafletInitialized` per evitare doppia
  inizializzazione.
- **Gotcha sui dati reali**: il datapack contiene almeno una sezione con coordinate palesemente errate (una
  sezione piemontese geocodificata in Papua Nuova Guinea) — un `map.fitBounds()` su *tutti* i marker
  zoomerebbe fuori dall'Italia per quell'outlier. Mitigazione lato vista: calcolare il `fitBounds()` iniziale
  solo sui marker dentro un bounding box dell'Italia (`L.latLngBounds([35, 6], [47.5, 19])`), fallback a
  tutti i marker se nessuno ricade nel box — il marker outlier resta comunque sulla mappa, solo escluso dal
  calcolo dell'inquadratura iniziale. Verificare sempre con lo screenshot reale (non solo la fixture) prima
  di dichiarare completa una story che usa dati RUNTS-CAI.

## Riusare un Infolist Filament dentro una pagina custom non-Resource (US-806)

- `Filament\Pages\Page` eredita già `InteractsWithSchemas` tramite `Filament\Pages\BasePage` — nessun
  trait/interfaccia aggiuntiva per embeddare uno Schema/Infolist in una pagina che non è una `ViewRecord`.
  Basta un metodo pubblico tipato `fn (Schema $schema): Schema` (es. `caiSectionInfolist(Schema $schema):
  Schema`): la risoluzione dinamica lo individua da solo per nome.
  `{{ $this->caiSectionInfolist }}` in Blade lo renderizza direttamente (`Schema implements Htmlable`).
  `->record($model)` imposta il record. Vedi `app/Filament/CLAUDE.md` per il pattern generico completo.
- Applicato per riusare `CaiSectionInfolist::configure()` (US-804) identico fra staff (`CaiSectionResource`),
  cliente Sezione (`CustomerDashboard`) e cliente Gruppo Regionale (US-807): stesso schema PHP, stesso
  markup, cambia solo chi/quando viene chiamato e quale record viene passato a `->record()`.
- `CaiSectionInfolist` presuppone un record `CaiSection` (relazioni `subsections`/`runtsRegistrations`): un
  utente collegato invece a una `CaiSubsection` **non può riusare questo schema tal quale** — gestito con un
  blocco Blade separato più semplice, non un secondo Infolist.
- `CaiDocumentDownloadController` (US-804) ha ora due vie d'accesso: lo staff con
  `Permission::CaiDirectoryView` **oppure** il cliente proprietario, verificato risalendo
  `CaiDocument::runtsRegistration->section->user_id === $user->id` — nessuna Policy dedicata. Se US-807 deve
  scaricare documenti di sezioni della propria regione, estendere questo stesso metodo `authorized()`, non
  introdurne un secondo.

## Pagina Filament custom con parametro di rotta + record scoping in `mount()` (US-807)

- Un `Filament\Pages\Page` (non-Resource) può avere un parametro di rotta come una `ViewRecord`: basta
  sovrascrivere `public static function getRoutePath(Panel $panel): string { return
  parent::getRoutePath($panel).'/{record}'; }` e dichiarare `public function mount(string $record): void`.
  Nessun route model binding implicito: risolvere `$record` a mano con una query esplicita
  (`User::query()->where(...)->findOrFail($record)`) — un id inesistente/tipo sbagliato dà 404, non un
  errore criptico.
- **Due livelli di autorizzazione distinti**: `canAccess()` (statico) verifica solo il gate generico "chi
  può aprire questa classe di pagina" — non ha accesso al parametro di rotta. Lo scoping sul singolo record
  (qui: la sezione aperta deve appartenere alla propria regione) va verificato dentro `mount()` con
  `abort_unless(..., 403)`, l'unico punto che riceve `$record`. Un tentativo via URL manipolato fallisce con
  403 reale, non solo con l'assenza di un link in UI.
- **Riuso di un componente di presentazione fra route/pagine diverse**: quando lo stesso blocco di markup
  deve comparire identico su più pagine Livewire/Filament, estrarlo in una partial Blade dedicata
  (`resources/views/filament/pages/partials/*.blade.php`) e richiamarla con `@include(...)` **senza passare
  esplicitamente i dati**: finché entrambe le classi Page espongono gli stessi metodi pubblici, `$this`
  dentro la partial risolve correttamente al componente Livewire che la sta rendendo. L'unica differenza
  ammessa è una variabile semplice (es. testo diverso a seconda del contesto).
- Quando una card cliente collega ogni riga a una pagina di dettaglio nuova, e la riga aveva già un'altra
  funzionalità (qui: link ai ticket della sezione), **non perderla silenziosamente**: spostarla in un punto
  coerente della nuova pagina (header action) invece di lasciare un metodo ormai orfano sulla pagina di
  origine.

## Checkpoint di fine Storia 3 — servizio `cai-runts-scraper` + wiring PHP (US-928, Fase 9)

- **Bug reale trovato SOLO dallo smoke test manuale contro il servizio Python realmente in esecuzione, mai
  dai test mockati delle story precedenti**: `cai_documents.year` è nullable, ma
  `cai_financial_statements.year` è NOT NULL e parte della chiave composita `(cai_runts_registration_id,
  year)`. Tutti i fixture di test valorizzavano sempre `year`, quindi nessuno aveva mai esercitato il caso
  reale (2 documenti su 5 con `year=null` sul CF 00951210103), che faceva fallire il job con `PDOException`
  per tutti e 3 i tentativi Horizon. Fix: guard esplicito `if ($document->year === null) { return; }` in
  cima a `AnalyzeCaiFinancialStatementDocument::handle()`, PRIMA di scaricare il PDF/chiamare il servizio.
  **Pattern generale**: quando un job/Action legge una colonna nullable sulla sorgente per scriverla su una
  colonna NOT NULL sulla destinazione (specie se parte di una chiave composita), verificare sempre
  esplicitamente il caso null PRIMA di scrivere — un fixture che valorizza sempre quel campo "per comodità"
  non lo intercetta mai.
- **Uno smoke test manuale end-to-end contro dati reali è l'unico modo per scoprire questo genere di bug**:
  nessun mock realistico avrebbe prodotto un documento RUNTS reale con anno mancante senza saperlo in
  anticipo. Quando un checkpoint prevede un passo di verifica manuale con dati reali, eseguirlo per davvero
  prima di dichiarare la story chiusa.
- **Gotcha ambientale, non specifico a questo dominio**: un ambiente Docker "già in esecuzione da giorni" può
  avere migrazioni committate ma mai applicate al Postgres di sviluppo persistente (i test Pest girano su
  sqlite in-memory e non lo intercettano mai). Prima di uno smoke test manuale che scrive sul DB reale,
  verificare sempre con `php artisan migrate --pretend` (poi `--force` se necessario).
- `php artisan tinker` di default gira con `memory_limit=128M`: una risposta HTTP con più PDF in base64 (il
  payload di `/scrape/runts-entity`, ~19 documenti) può esaurirlo dentro `json_decode()`. Usare
  `docker compose exec -T app php -d memory_limit=512M artisan tinker --execute="..."` per qualunque
  verifica manuale che invochi `CaiRuntsScraperClient::scrapeEntity()` o un payload HTTP comparabile.
