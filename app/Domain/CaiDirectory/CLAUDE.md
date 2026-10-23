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

## Pagina elenco rifugi (`SectionHuts`, US-1034 — da riusare per i rifugi del GR)

- Filtri/ordinamento in `Support\CaiHutFilters` (tipi in OR, stato, `Enums\CaiHutSort`) applicati in PHP sui rifugi
  già caricati; nell'URL chiavi brevi `CaiHutType::key()` / `CaiHutStatus::key()` (`?tipo[]=bivacco&stato=chiuso`),
  mai il testo della fonte (spazi/apostrofi). `fromKey()` → `null` per un valore sconosciuto (= filtro assente).
- Layout desktop con **container query** (`container: huts / inline-size`), non media query: con la sidebar di Filament
  a 1440px l'area contenuti è ~1050px e il design "elenco + mappa 500px" schiaccia la tabella; affiancati solo da 1200px
  di contenuto, sotto mappa sopra. Altezza della mappa passata come `height="var(--ms-huts-map-height)"`.
- Mobile: in un flex a colonna con `flex-wrap: wrap` i figli si allargano quanto la riga di chip scorrevole (nowrap):
  sotto i 640px la colonna dei filtri va `nowrap`, i `<select>` in griglia con `width: 100%; min-width: 0`.
- Test Livewire: dopo un `call()`/`set()` gli `assertSee*` lavorano sulla risposta JSON, dove i caratteri non ASCII sono
  `\u00..`: nelle fixture che si riasseriscono dopo un update usare nomi ASCII (accenti → test unitario di `CaiHutSort`).
  Per regex sull'HTML togliere prima i commenti `<!--[if BLOCK]>` di Livewire.

## Scheda del rifugio (`CaiHutDetail`, US-1035 — da riusare dal GR e per sentieri/tappe)

- Rotta `rifugi/{idCai}` (pagina Filament con parametro, vedi §US-807 sotto): `canAccess()` ammette staff
  `CaiDirectoryView` e customer Sezione/GR, `mount()` verifica `CaiHutPolicy::view` sul rifugio (id non numerico o
  inesistente → 404, rifugio di altri → 403). La sezione "Gestione" è dentro un `@if (canViewManagement())`: mai
  nascosta con CSS. Test della gestione nascosta con `Gate::before(fn ($u, $a) => $a === 'viewManagement' ? false : null)`
  (oggi la policy dà `view` = `viewManagement`, quindi non c'è un utente reale che vede la scheda senza gestione).
- Formattazione in `Support\CaiHutSheet` (valore assente = `null`, la vista mette "—"): servizi (`SERVICES`, 15 chiavi
  di `services`), certificazioni (`CERTIFICATIONS`), contatti con link, orari, canone.
- **Scadenza del contratto: un solo punto, `Support\CaiHutContractExpiry`** (`of($hut)` → `endDate`, `status($today)`
  = `Enums\CaiHutContractStatus` scaduto < 0 giorni / in scadenza ≤ 183, `note($today)` "Scaduto da N mesi/giorni" /
  "Scade tra …" / "Scade oggi"). La usano `CaiHutSheet` (scheda) e `Queries\CaiRegionalGroupHuts` (10.5): mai
  ricalcolare le soglie altrove. Oggi passato come argomento per i test con `Carbon::setTestNow`.
- Rifugi del GR (10.5): `Queries\CaiRegionalGroupHuts::forRegion($region)` → `CaiRegionalGroupHutsData` (righe
  `CaiRegionalGroupHutRow` con nome Sezione, flag `ownedByGroup`, scadenza; M, Sezioni con/senza rifugi, contatori).
  Sezioni per prefisso (`sectionsOf()`) **più** i rifugi intestati al GR (`branch_code` `95…` e `branch_name` =
  "GR {Region::label()}" senza maiuscole, oggi solo `9500046` GR Sicilia), con "GR {regione}" come Sezione; un rifugio
  del GR non conta fra le "Sezioni con rifugi". Da riusare per pagina e badge della Dashboard GR. `CaiHutPolicy::view`
  ammette il GR anche sui rifugi intestati a lui (stessa regola, `CaiRegionalGroupHuts::groupName()`): se cambia la
  regola, cambiarla in entrambi i punti.
- Pagina `RegionalGroupHuts` (10.5, slug `gr/rifugi`): filtri/ordinamento/raggruppamento in `Support\CaiRegionalGroupHutFilters`
  (chiave Sezione = codice CAI o `gr`) ed `Enums\CaiRegionalGroupHutSortColumn` (mancanti sempre in fondo). URL
  `sezione`/`tipo`/`stato`/`contratti`/`ordina`/`verso`/`raggruppa`, solo se diversi dal default; `listQuery()` è
  l'elenco dei parametri da conservare e la riga apre `CaiHutDetail` con `da=gr` + quei parametri (il ritorno li legge
  dalla query della scheda). La colonna di ordinamento di default dipende dal filtro contratti (scadenza) e il toggle
  del filtro azzera l'ordinamento.
- Certificazioni del design non presenti in rifugi.cai.it (depuratore, solare termico, Ecolabel): sostituite da chiavi
  reali sì/no (`oilSeparator`, `rain_water`, `greenCertification`). Le altre chiavi `*_certification` sono testo libero
  (anno di costruzione, classe catastale, ...), non vanno nella griglia Sì/No.
- `?da=gr` → link "← Rifugi del GR" (anche senza parametro per un utente GR); staff → scheda della Sezione nella Resource.
  I parametri dell'elenco li legge `mount()` dalla query (chiavi `RegionalGroupHuts::LIST_QUERY_KEYS`, solo stringhe)
  in `$listQuery`, riportati nel link di ritorno solo con `da=gr`: aggiungendo un filtro all'elenco GR, aggiungere la
  chiave a `LIST_QUERY_KEYS` oltre che a `listQuery()`.
- Mobile: ordine del design (testata, mappa, schede, gestione, fonte) con griglie in `display: contents` + `order`.

## Componente mappa `x-cai.map` (US-1033, rifugi / sentieri / tappe SICAI)

- Unico componente mappa delle pagine Sezione/GR della Fase 10: `resources/views/components/cai/map.blade.php`, CSS
  `resources/css/filament/admin/cai-map.css`. Dati come `Support\CaiMapMarker` (rifugio: `CaiMapMarker::forHut($hut,
  $url)`, popup del design da `components.cai.map-popup`) e `Support\CaiMapLine` (GeoJSON + colore, sentiero:
  `CaiMapLine::forTrail()`).
- Legame con l'elenco della pagina **solo via eventi** (`detail.map` = prop `name`): la mappa emette
  `cai-map-marker-selected` / `cai-map-marker-hovered`, ascolta su `window` `cai-map-highlight` / `cai-map-select`.
  Esempio riga: `@mouseenter="$dispatch('cai-map-highlight', { map: 'huts', id: 12 })"`, elenco
  `x-data="{ active: null }" @cai-map-marker-selected.window="active = $event.detail.id"`.
- Aggiornamento Livewire (filtri): il riquadro Leaflet è `wire:ignore`; i dati stanno in un `<div hidden>` con
  `wire:key` = hash dei dati, il cui `x-init` richiama `load()`. Verificato con Playwright che la sostituzione del nodo
  ridisegna la mappa. Rendere il componente sempre (anche vuoto), mai solo dentro un `@if` che appare dopo un update.
- Oggetti Leaflet tenuti in closure, non nello stato Alpine (un Proxy reattivo rompe la mappa). Configurazione e dati
  passano da attributi `data-cai-map-config` / `data-cai-map-payload` (JSON leggibile nei test), non da `@js(...)`.
- Verifica JS senza pannello: rendere il componente con `Blade::render` in tinker, avvolgerlo in una pagina HTML con
  Alpine da CDN e il CSS di `public/build`, aprirla con Playwright (`file://`).
- **Cluster (US-1042)**: prop `cluster` (+ `clusterNoun` per il tooltip) → `leaflet.markercluster` 1.5.3, caricato in un
  `@once` separato solo se una mappa lo chiede (senza l'opzione l'HTML è quello della 10.4). Raggruppa fino allo zoom 11,
  clic sul gruppo = zoom +2 (`zoomToBoundsOnClick: false` + `clusterclick`). Un marker dentro un gruppo non ha `_map`:
  `cai-map-select` dall'elenco usa `zoomToShowLayer()` prima di aprire il popup. In Playwright i marker fuori vista
  restano nel DOM: cliccarli via `element.click()` in `evaluate`, non con `locator.click()`.
- Popup con la Sezione proprietaria: `CaiMapMarker::forHut($hut, $url, $sectionName)` (terzo argomento facoltativo).
- **Linee / sentieri (US-1053)**: `CaiMapLine::forTrail($trail, $url)` (colore dello stato, spessore 3, anteprima
  `components.cai.map-trail-popup` col segnavia `x-cai.trail-sign`, CSS comune dei sentieri in `cai-trails.css`).
  Le linee hanno eventi propri `cai-map-line-selected` / `cai-map-line-hovered` (stesso `detail`); `cai-map-highlight` /
  `cai-map-select` valgono per marker e linee (un solo spazio di id per mappa). Disegnate su `L.canvas` con `tolerance`
  (niente tracciati SVG invisibili per l'area di clic); il bordo bianco della linea attiva è un secondo layer creato
  solo quando serve. Verificato con Parma (380 linee): nessun blocco, ~16 ms per aggiornamento di evidenziazione.
  **Peso**: 380 geometrie ≈ 2 MB di payload nel markup (rimandato a ogni update Livewire che cambia i dati): tenerne
  conto nelle pagine con filtri. In Playwright una linea su canvas non ha un nodo DOM: trovarla col mouse
  (`mouse.move` a griglia finché arriva `cai-map-line-hovered`) o selezionarla con `cai-map-select`.

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

## Fallback CF/PIVA da foglio Excel manuale (`cai:generate-tax-code-fallback` / `cai:fill-tax-codes-from-fallback`)

- Il foglio Excel del committente ("Sezioni CAI con CF e P.IVA") **non contiene `codice_cai`**, solo la
  denominazione in un formato testuale diverso da `cai_sections.name` (es. "C.A.I. SEZIONE DI ABBIATEGRASSO"
  nel foglio vs "SEZ. ABBIATEGRASSO" nel datapack RUNTS-CAI): `CaiTaxCodeFallbackGenerator` matcha per nome
  normalizzato (prefissi, forme giuridiche APS/ETS/ONLUS/ODV, accenti, spazi multipli) — verificato sul
  dataset reale: ~92% delle righe trova un match univoco; il resto (soprattutto le righe "GR <regione>",
  Gruppi Regionali, non sezioni) resta correttamente senza match e va rivisto a mano se un giorno serve
  completarlo. Due sezioni che normalizzano allo stesso nome non vengono mai matchate (nessun modo di
  distinguerle dal solo testo) — meglio "senza corrispondenza" che un match sbagliato.
- Il JSON generato (`resources/data/cai/tax-code-fallback.json`) è **committato nel repo** (a differenza di
  `cai-datapack/`, gitignored): è dato di fallback stabile, non un dump v1/RUNTS rigenerabile ad ogni
  deploy. Va rigenerato a mano (`cai:generate-tax-code-fallback --path=...`) e ricommittato solo quando
  arriva un foglio Excel aggiornato dal committente.
- `FillCaiSectionFiscalCodesFromFallback` riempie SOLO `tax_code`/`vat_number` mancanti, mai sovrascrive un
  valore già presente — riusata sia da `cai:fill-tax-codes-from-fallback` (standalone) sia in testa a
  `cai:sync-runts-all` (fallback automatico prima del giro di sync live).
- **Gotcha test**: `CaiSyncRuntsAllCommandTest`/altri fixture di questo dominio riusano `codice_cai` REALI
  del dataset RUNTS-CAI (es. "9226005" = SEZ. CARRARA) come valori comodi e leggibili. Se
  `CaiTaxCodeFallbackRepository` leggesse di default il JSON reale committato, il fallback riempirebbe a
  sorpresa il `tax_code` di quelle sezioni di test durante `cai:sync-runts-all`, cambiando silenziosamente
  comportamento/asserzioni di test che non parlano affatto di fallback. Il percorso del JSON è quindi esposto
  via `config('cai_directory.tax_code_fallback_path')` (mai un default hardcoded nella classe), puntato da
  `phpunit.xml` a un file inesistente (`CAI_TAX_CODE_FALLBACK_PATH`, sia `<env force="true">` sia `<server>`,
  stesso motivo del blocco DB_CONNECTION già documentato lì) — un file assente è trattato come dataset vuoto,
  mai un errore. Qualunque futuro dataset "di fallback" statico committato nel repo dovrebbe seguire lo
  stesso pattern (percorso via config, mai un default che punta silenziosamente al file reale nei test).

## Bilanci manuali di campagna nel datapack (`cai:build-manual-bilanci-datapack`, US-930..US-935)

- **Layout** (gitignored, mai nel repo): `cai-datapack/bilanci-sezioni-2026/` contiene `2026_Campagna_Sezioni.xlsx`
  (indice, foglio `Sezioni`), `normalized/<Regione>/<codice - CAI Nome>/<codice - CAI Nome - Etichetta.ext>` (409 file,
  352 sezioni al 2026-10-20, usati a runtime) e `originals/` (grezzi Typeform, solo audit: **non** spediti su UAT da
  `bin/push-cai-datapack`, che esclude anche l'xlsx).
- **Ordine operativo**: `cai:build-manual-bilanci-datapack` (riscrive `bilanci_manuali` in `runts-cai.sqlite` con
  `DROP`+`CREATE`+insert in una transazione, tocca solo quella tabella; `--dry-run` per il solo report) →
  `bin/push-cai-datapack` → deploy/`cai:import-datapack`. L'import crea `CaiDocument` con `source=Manual`,
  `cai_section_id` valorizzato e registrazione null (stessa forma di `UploadCaiDocumentManually`); gira solo
  nell'import completo (non con codice sezione singolo né `--skip-section-fields`).
- **Riallineamento dalla cartella di lavoro iCloud** (`MS/BILANCI SEZIONI 2025/GR TOSCANA/`, nonostante il nome
  copre tutte le regioni): `<Regione>/BILANCI 2025 GR <Regione>/` → `normalized/<Regione>/` (`rsync -rc --delete
  --exclude=.DS_Store`), `202609_CampagnaSezioni/` → `originals/`, xlsx copiato. Le cartelle `BILANCI 2025 mancanti/`
  (vuote) ed `EMAIL_GR_*.txt` non entrano nel datapack. Poi: export snapshot (salva lo stato DB) →
  `cai:import-datapack --analyze-manual` → `cai:analyze-financial-documents --year=2025` → riesportare lo snapshot.
- **Etichetta sconosciuta → `Altro`**: `ManualBilancioTypeMapper` mappa solo etichette esatte (o sinonimi
  espliciti) su `CaiDocumentType`; `Bilancio consuntivo`, `Conto economico`, `Stato Patrimoniale…` NON si
  indovinano. Il titolo resta l'etichetta originale; anno 2025 (2026 se l'etichetta lo contiene).
- **Idempotenza**: un documento è considerato già presente per (sezione, `source=Manual`, hash sha256) → `skipped`;
  due run consecutive creano 0 documenti. Un duplicato byte-identico nella stessa sezione (o un upload manuale
  già fatto da UI) produce quindi un documento in meno dei file.
- **Niente analisi di default**: `--analyze-manual` accoda `AnalyzeCaiFinancialStatementDocument` (coda
  `cai-runts-analysis`) solo per i documenti CREATI in quella run di tipo `triggersFinancialAnalysis()` con anno;
  mai in `--dry-run`.
- **Anomalie attese sul dataset reale** (avvisi, mai bloccanti): `ricevuto_senza_file` = 4 (9216083, 9216157,
  9216158, 9248005), `file_senza_ricevuto` = 1 (9216038), `codice_excel_non_nel_datapack` = 2 (9212045, 9219008).
  `sezione_non_nel_datapack` è registrata una volta per sezione; `codice_excel_non_nel_datapack` ha precedenza su
  `ricevuto_senza_file` per lo stesso codice.
- **Gotcha codici Excel**: la colonna A del foglio (senza intestazione) è `codice_cai`, ma talvolta è un numero
  (`9216157.0`): normalizzare sempre a stringa di 7 cifre (`CampagnaSezioniIndexReader`). Le colonne si leggono per
  intestazione, mai per indice.
- **Gotcha comando**: un apostrofo nella descrizione di un'opzione dentro `$signature` (stringa PHP tra apici
  singoli) rompe il parsing e `artisan` smette di elencare TUTTI i comandi (i test falliscono con
  `CommandNotFoundException`). Il test `CaiBuildManualBilanci...` non è incluso da `--filter=ManualBilancio`:
  usare `--filter=CaiBuildManualBilanci` o il path.

## Bilanci per anno: `CaiFinancialDocumentKind`, `has*Data()` e `cai:analyze-financial-documents` (US-941..US-946)

- `Support\CaiFinancialDocumentKind` è l'UNICO elenco di cosa conta come conto economico / stato patrimoniale (costanti `*_TYPES` + `*_KEYWORDS` sul
  titolo): usato sia in PHP (`isIncomeStatement()`/`isBalanceSheet()`) sia in SQL (`applyIncomeStatement()`/`applyBalanceSheet()`). `CaiDocument`
  espone `scopeIncomeStatement/scopeBalanceSheet/scopeForYear` che delegano lì: per cambiare la classificazione si tocca solo quella classe. I
  `bilancio_esercizio` con titolo "BILANCIO D'ESERCIZIO" non sono classificati (voluto).
- `CaiFinancialStatement::hasIncomeStatementData()` / `hasBalanceSheetData()` dicono se il record ha cifre di CE / SP (SP: `total_assets`,
  `total_liabilities`, `net_equity`). Le pagine "Bilancio 2025"/"Gruppi regionali" non rileggono i bilanci: usano `Queries\CaiSectionFinancialYearQuery`
  (colonne `EXISTS`, "collegato" = `cai_section_id` diretto OR via registrazione RUNTS).
- `Queries\CaiSectionDashboardMetrics` (Dashboard V2, Fase 10.1) legge le metriche di UNA Sezione/Sottosezione in un value
  object (`Support\CaiSectionDashboardData`): CE e SP scelti indipendentemente (ultimo anno con dati), a parità di anno vince il
  bilancio collegato via RUNTS. Vincolo unique `(cai_section_id, year)` su `cai_financial_statements`: nei test non creare due
  bilanci diretti dello stesso anno. La regione in maiuscolo si mappa sull'etichetta di `Identity\Enums\Region` confrontando
  `mb_strtoupper(label())`, come fa `CaiRegionalGroupsQuery`.
  Regole di dominio da rispettare anche nella Dashboard GR (10.2) e nelle pagine Soci/Bilancio: soci = `members_count` così
  com'è, **mai** sommato con quello delle Sottosezioni (domanda aperta col CAI, PRD 10.1 §9); un valore di bilancio assente
  resta `null` (il risultato netto non si ricalcola da ricavi/costi) e la vista lo mostra "Non disponibile", mai 0; una
  Sottosezione non ha mai bilanci (le iscrizioni RUNTS sono solo delle Sezioni). La resa usa `x-cai.metric-badge` +
  `App\Support\Formatting\ItalianNumberFormatter` (vedi `app/Filament/CLAUDE.md`): riusarli, non duplicarli.
  Pillola "Terzo Settore" (`CaiSectionDashboardData::$thirdSectorRegistered`): iscritta = `CaiRuntsRegistration` collegata
  OPPURE `runts_presence_status = Registered`; ogni altro caso (null, `Timeout`, `NotRegistered`) = "non iscritta", per scelta
  del committente anche se il dato è incerto (oggi ~300 Sezioni in timeout o mai verificate). Sottosezione = `null`, nessuna pillola.
- **Regole del Gruppo Regionale** (`Queries\CaiRegionalGroupDashboardMetrics`, Dashboard V2 GR, Fase 10.2) — il GR non esiste come
  ente/tabella: tutto si deriva dalle Sezioni. **Da riusare identiche nella 10.3 (bilancio consolidato) e nelle altre pagine GR**,
  senza ricalcolarle altrove:
  - prefisso del GR = i primi 4 caratteri di `codice_cai` più frequenti fra le `CaiSection` con `region` del GR (parità → il
    numericamente più alto); codice CAI del GR = prefisso + `000` (derivato, mai salvato);
  - Sezioni del GR = `CaiSection` con quel prefisso, **non** per `region` (la SAT `9218…` resta fuori, Sappada `9220…` in FVG entra
    nel Veneto); le Sottosezioni non contano mai;
  - download PDF (`CaiDocumentDownloadController`, US-1022): il GR scarica i documenti di ogni Sezione del suo prefisso (anche
    senza utente collegato) oltre alla vecchia regola sulla `region` dell'utente Sezione collegato. Fixture di test: `caiSection()`
    ha default `region` `LOMBARDIA` e codice `CAI-N`, quindi per il GR Lombardia è "dentro il prefisso" — per un 403 cambiare regione;
  - consolidato = **un solo esercizio**, il più recente in cui almeno una Sezione ha dati, sommando solo le Sezioni con dati in
    quell'anno e solo i valori non nulli (totale senza valori = `null`, mai 0); CE e SP scelgono l'anno indipendentemente; il
    bilancio di una Sezione per anno segue `CaiSectionStatementsQuery::forSections()` (una query per tutte, a parità vince RUNTS);
  - la pagina "Sezioni del gruppo regionale" (`CustomerRegionalSectionsDashboard`) elenca invece gli **utenti** Sezione per
    `users.region`: il suo numero può differire dal badge "Sezioni" della V2. Atteso, non allinearli senza un PRD.
  - I numeri attesi del PRD 10.2 §7 sono gonfiati (contano due volte diretto+RUNTS dello stesso anno); il GR Sicilia 2025 ha ricavi
    consolidati anomali (~203 M€ su 5 Sezioni) per un bilancio letto male — qualità del dato, da verificare a parte.
- **Bilancio consolidato per esercizio** (`Queries\CaiRegionalGroupConsolidatedStatements::forRegion($region, ?$year)`, Fase 10.3,
  pagina `RegionalGroupFinancialStatements`) — a differenza della Dashboard GR l'esercizio è **scelto**, non "il più recente per voce":
  - esercizi disponibili (`availableYears()`) = i 5 anni che finiscono con l'anno più alto fra bilanci **e documenti** (un solo PDF
    2026 basta a spostare la finestra: sul dev DB è 2022–2026); default = il più recente con almeno una Sezione in "Dati presenti",
    altrimenti il più recente dei 5; anno assente, non numerico o fuori dai 5 → default, mai errore;
  - stato della Sezione nell'esercizio (`Enums\CaiSectionStatementStatus`): "Dati presenti" = bilancio dell'anno con almeno una cifra
    di CE o SP (CE e SP scelti indipendentemente, a parità vince RUNTS); altrimenti "Solo PDF" = file di bilancio dell'anno
    (`CaiSectionFinancialYearQuery::financialDocumentsOf()`, stessi criteri di `CaiFinancialDocumentKind`); altrimenti "Nessun
    bilancio". Ogni Sezione del prefisso ha sempre una riga, anche senza nulla;
  - "da verificare": ricavi, costi, patrimonio netto, totale attivo (**mai il risultato netto**) con valore assoluto > 10× la mediana
    dei valori assoluti non nulli della voce fra le Sezioni con dati, solo se quei valori sono **almeno 3**. Il valore resta nel
    totale: la card dice solo "Include N valori da verificare";
  - il PDF di una riga è scaricabile dal GR con la regola del prefisso di `CaiDocumentDownloadController` (sopra): non serve un'altra
    autorizzazione per le pagine GR che linkano documenti delle Sezioni.
- `php artisan cai:analyze-financial-documents --year=YYYY [--section=] [--force] [--dry-run]` accoda `AnalyzeCaiFinancialStatementDocument` (coda
  `cai-runts-analysis`) per i documenti CE/SP dell'anno non ancora analizzati. Serve `queue` in esecuzione; il worker ha `memory_limit` 128M e un PDF
  grande può dare OOM (il job viene ritentato: sulla run 2025 tutti i 348 sono comunque arrivati a fine, 0 `failed_jobs`). Controllare l'avanzamento
  con `redis-cli llen laravel-database-queues:cai-runts-analysis` (la chiave ha il prefisso `laravel-database-`).


## Snapshot CAI/RUNTS nel datapack (`cai:export-datapack-snapshot`, US-950..US-956)

Lo snapshot porta su UAT (dove non gira né scraper né analisi) **tutto** il dominio CAI del DB locale.
Operativo (ordine dei comandi e vincolo di spazio remoto): `deploy/CLAUDE.md` § "Snapshot CAI/RUNTS".

- **Ordine dei comandi**: `cai:build-manual-bilanci-datapack` → `cai:export-datapack-snapshot` →
  `bin/push-cai-datapack` → (deploy) `cai:import-datapack`. L'export va rilanciato dopo ogni modifica dei
  dati locali: il datapack non si aggiorna da solo.
- **Export** (`Export\CaiSnapshotExporter` + `CaiSnapshotFileExporter`, comando sottile): DROP+CREATE+INSERT
  delle sei tabelle `snap_cai_{sections,subsections,runts_registrations,financial_statements,board_members,
  documents}` in **una transazione** sul file SQLite; le tabelle legacy del datapack (`sezioni_cai`, `enti`,
  `bilanci`, `allegati`, `bilanci_manuali`...) non si toccano mai. Valori scritti in forma grezza di colonna
  (mai il formato di presentazione di enum/date), senza `id` autoincrementale e **mai `user_id`**: il
  collegamento utente si ricostruisce per email in import. Idempotente (stesso contenuto, stesso ordine).
- **File**: solo i documenti `source = Runts`, content-addressed in `snapshot-files/<hash[0..1]>/<hash>.<ext>`;
  l'hash è **ricalcolato** con `hash_file` (non ci si fida di `cai_documents.hash`) e righe con lo stesso hash
  condividono un solo file. I documenti manuali non si copiano (esistono già in `bilanci-sezioni-2026/`):
  le loro righe servono solo a riportare l'esito di analisi (`file_in_datapack = 0`). File mancante →
  `file_in_datapack = 0` + avviso, mai eccezione. Copia a stream; controllo `disk_free_space` **prima** di scrivere.
- **Import** (`Import\CaiSnapshotImporter`, chiamato da `CaiDatapackImporter::import()` dopo `documenti_manuali`,
  solo per import completo e solo se esiste `snap_cai_sections`): upsert per **chiave naturale** — sezione
  `codice_cai`, sottosezione `cai_codice`, registrazione `id_runts`, bilancio (genitore, anno) con
  **esattamente un** genitore fra registrazione/sezione, carica (registrazione, ruolo, nome, `valid_from`).
  `user_id` non è mai sovrascritto; sulle righe nuove si ricostruisce per email con `CaiSectionFieldMapper::
  matchUserId`. I timestamp dello snapshot non sono importati. Genitore assente → riga saltata + warning.
- **Documenti RUNTS**: dedup per (genitore, `hash`, `source`) anche contro i documenti dell'import legacy
  `allegati` (allinea solo `financial_analysis_status`/`raw_text_excerpt`/`extracted_via_ocr`), altrimenti
  copia in `cai-documents` come `<id_runts|codice_cai>/<uuid>-<file_name>` con `source = Runts`. Due fasi:
  pianificazione senza scritture → controllo spazio (`disk_free_space` ≥ byte × (1 + `config('cai_directory.
  snapshot.disk_margin_percent')`/100), default 10) → copia. Spazio insufficiente: **nessuna copia**, errore
  italiano, comando in FAILURE, fasi precedenti non annullate.
- **Documenti manuali**: `importManualAnalysis()` abbina per (`cai_section_id`, `hash`) il documento creato da
  `documenti_manuali` e ne ripristina l'esito di analisi; non crea documenti né copia file.
- **Nessuna analisi accodata**: l'import non esegue mai `AnalyzeCaiFinancialStatementDocument::dispatch`
  (UAT riceve risultati già calcolati); i test lo verificano con `Queue::fake()` + `assertNothingPushed`.
- Righe di riepilogo di `cai:import-datapack`: `snapshot_sezioni` (sezioni+sottosezioni), `snapshot_registrazioni`,
  `snapshot_bilanci`, `snapshot_cariche`, `snapshot_documenti` (MB copiati), `snapshot_manuali`. In `--dry-run`
  `snapshot_manuali` su DB vuoto conta tutto come senza match (lo step `documenti_manuali` non crea nulla).
- **Gotcha della verifica su DB separato (US-956)**: mai `migrate:fresh` sul DB di sviluppo (è la sorgente).
  Usare un database Postgres a parte (`DB_DATABASE=orchestrator_roundtrip`) e un disco documenti separato con
  `CAI_DOCUMENTS_ROOT=/tmp/...` (`config/filesystems.php`; nel `.env` tenerla **commentata**: valore vuoto
  → `env()` ritorna `""`, non il default, e rompe il disco). Confrontare i conteggi prima/dopo sul DB dev.
  I conteggi del PRD erano di uno stato precedente: confrontare sempre con il DB attuale, non con il documento.
- L'opzione di `cai:import-datapack` è `--path`; quella di `cai:export-datapack-snapshot` è `--datapack`.

## Rifugi da rifugi.cai.it (`cai_huts`, Fase 10.4, US-1031)

- **In sintesi (feature 10.4)**: fonte = API pubblica `https://rifugi.cai.it/api/v1` (unica, nessun altro dato sui
  rifugi) → script `cai:fetch-huts` (a mano, mai pianificato) → tabella `snap_cai_huts` del datapack →
  `cai:import-datapack` → tabella `cai_huts` (PK `id_cai`, `branch_code` = `codice_cai` della Sezione, senza FK) →
  pagine `SectionHuts` (elenco + mappa), `CaiHutDetail` (scheda), badge "Rifugi" della Dashboard Sezione. Visibilità:
  scheda e "Gestione" (gestore, contratto, canone, proprietà, certificazioni, CIN) solo a staff `CaiDirectoryView`,
  Sezione proprietaria e GR della sua regione (`CaiHutPolicy::view`/`viewManagement`, oggi coincidenti; per
  restringere la gestione basta cambiare `viewManagement`, la vista non rende nulla senza permesso). Collaudo:
  `docs/collaudo/fase-10.php` F10-134..F10-188, E2E `tests/Feature/EndToEnd/Fase10RifugiSezioneEndToEndTest.php`.
- **In sintesi (feature 10.5, Rifugi del GR)**: rifugi del GR = quelli delle Sezioni per prefisso **più** quelli
  intestati al GR (`branch_code` `95…` con `branch_name` = "GR {Region::label()}", confronto senza maiuscole; mostrati
  con "GR {regione}" come Sezione, primi nel raggruppamento, mai contati fra le "Sezioni con rifugi"). Un'unica regola
  in `CaiRegionalGroupHuts` (`hutsQuery()`/`groupName()`) usata da pagina `RegionalGroupHuts`, badge della Dashboard GR
  e `CaiHutPolicy::view`: cambiarla lì e basta. Stato del contratto (scaduto < 0 giorni, in scadenza ≤ 183) solo da
  `Support\CaiHutContractExpiry`, condiviso da scheda, righe dell'elenco GR e card "Contratti di gestione" (filtro
  `contratti=1`, che il link di ritorno della scheda conserva). Collaudo: F10-189..F10-226, E2E
  `tests/Feature/EndToEnd/Fase10RifugiGrEndToEndTest.php`.
- **Fonte di verità esterna**, sola lettura: `snap_cai_huts` nel datapack (`id_cai`, `payload` = JSON grezzo del
  dettaglio `GET /api/v1/shelters/{id_cai}`) → `Import\CaiHutsImporter` (chiamato da `CaiDatapackImporter` solo
  nell'import nazionale, riga `snapshot_rifugi`). Crea/aggiorna/**elimina** (conteggio `deleted` di
  `CaiImportTableResult`, stampato come "eliminati N"); senza la tabella `snap_cai_huts` non tocca nulla.
- Parsing in `Import\CaiHutFieldMapper` (testato): la fonte è una lista `fields` `{name, value, data}` → tutti i valori
  grezzi in `cai_huts.fields`, colonne normalizzate per le pagine. `services` = ogni `*_service` (tranne `posti_*`)
  senza suffisso → 1/0/null (numeri > 0 e testo → 1, "Assente"/"Nessuna" → 0); `management.certifications` = ogni
  `*_certification` (0/1 come int, il resto testo). Canone spesso testo libero ("7500 + IVA", "gratuita"):
  `contract.fee` numerico o `null`, `contract.fee_text` grezzo sempre.
- Dati reali (ott. 2026): 764 rifugi, 761 con `branch_code` = una Sezione; 4 con stato "Rudere" (non nell'enum
  `CaiHutStatus` del design → `status` null, testo in `fields`); `openingTime_contact` ha il valore in `data`, non in `value`.
- `DiffsAttributes` confronta le colonne json (cast `array`) come JSON a chiavi ordinate: Postgres `json` (non
  `jsonb`) conserva l'ordine, ma il confronto non deve dipenderne.
- `CaiHutPolicy`: `view` = `viewManagement` = staff `CaiDirectoryView`, customer collegato alla Sezione `branch_code`,
  customer GR il cui `prefixOf(region)` è prefisso del `branch_code` o a cui il rifugio è intestato (10.5);
  create/update/delete sempre `false`.
- **Badge "Rifugi" (US-1036)**: unica eccezione alla regola "mancante = `null`, mai 0" delle dashboard — la banca
  dati rifugi.cai.it è completa, quindi una Sezione senza rifugi ha `huts = 0` (mostrato "0"); solo la Sottosezione
  resta `null`. Conta tutti i `cai_huts` della Sezione, qualunque `status`. Stessa eccezione per il badge "Rifugi"
  della Dashboard GR (US-1045): `huts` = rifugi di `CaiRegionalGroupHuts::hutsQuery()` (Sezioni + intestati al GR, la
  stessa regola della pagina "Rifugi del GR" — se cambia lì, cambia anche il badge), `hutsSectionsCount` per la nota
  "Somma di N Sezioni" (i rifugi intestati al GR non contano come Sezione; nota assente con N = 0); `null` solo se la
  regione non ha Sezioni.
- **Aggiornamento dall'API (US-1032)**: `cai:fetch-huts [--datapack=] [--dry-run]` → `Import\CaiHutsFetcher`
  (`fetch()` elenco paginato + dettagli con `Http::pool` a blocchi, `write()` DROP+CREATE+INSERT in una transazione,
  tutto o niente) → riepilogo `CaiHutsFetchSummary` (collegati = `branch_id` presente in `sezioni_cai` del datapack).
  Procedura: `cai:fetch-huts` → `cai:import-datapack` in locale → `bin/push-cai-datapack` → deploy UAT; mai l'API
  durante il deploy o le richieste web. Parametri in `config('cai_directory.huts_api')`.
- **Rate limit dell'API**: 1000 richieste per finestra, poi `429` con `Retry-After` (~60–100 s). Un run (~770
  richieste) ci sta, due di fila no: il fetcher rispetta `Retry-After` (tetto `max_retry_after_seconds`) fra i
  tentativi. I dettagli restano JSON grezzo (stringhe, ~12,6 MB): decodificati tutti insieme e con 764 risposte del
  pool vive il comando sforava in silenzio il `memory_limit` CLI di 128M. Nei test la pausa si verifica con
  `Sleep::fake()` (il fetcher usa `Illuminate\Support\Sleep`, non `usleep`).

## Sentieri dal catasto osm2cai (`snap_osm2cai_*`, Fase 10.6, US-1051)

- **Fonte**: dump PostgreSQL/PostGIS di osm2cai.cai.it (`pg_dump` testuale gzip, ~1,6 GB; oggi un file locale, URL di
  download non ancora disponibile). **Contiene dati personali** (`users`, `cas_users`, `password_resets`): mai nel DB
  dell'app, su UAT né in git — si carica solo in un container temporaneo.
- **Procedura**: `bin/update-osm2cai-datapack <dump.sql.gz> [--datapack=] [--keep-db]` → `cai:import-datapack` in
  locale → `bin/push-cai-datapack` → deploy UAT. Lo script avvia `postgis/postgis:16-3.4-alpine` (`--platform
  linux/amd64`, emulato su Apple Silicon: import ~6 min) con nome univoco, crea il ruolo `osm2cai2`, importa, esegue
  `database/osm2cai/extract.sql` (schema `osm2cai_extract`), esporta i CSV in `storage/app/osm2cai-extract-*` e chiama
  `cai:import-osm2cai-extract` (nel container `app` se è su, altrimenti `php` dell'host: il datapack va quindi indicato
  relativo alla root del repo). `trap` rimuove container e CSV anche in caso di errore; `--keep-db` lascia il container
  per ispezionare il dump (`docker exec -it <nome> psql -U postgres -d osm2cai`).
- **Riepilogo 10.6 (US-1057)** — fonte: solo il dump osm2cai, via script → datapack → `cai:import-datapack` (mai scritti
  a mano, mai modificati dall'app); estratti solo i sentieri con `osm2cai_status` 1–4 e non `deleted_on_osm`. Tabelle:
  `snap_osm2cai_trails`/`snap_osm2cai_trail_sections` (datapack) → `cai_trails`/`cai_trail_section` (app). **Regola dei
  sentieri condivisi**: un sentiero appartiene a OGNI Sezione il cui `codice_cai` compare fra i suoi club osm2cai, e
  conta per intero in ciascuna (elenco, km del riepilogo, badge "Sentieri" di `CaiSectionDashboardMetrics`): la somma dei
  km di più Sezioni può quindi superare i km reali — chi aggrega più Sezioni (GR, 10.7) deve deduplicare per `id`.
  **Stati** (`CaiTrailStatus`, colori `SDA` del design): 1 Difficoltà `#e8b500`, 2 Ricognizione `#f07c22`, 3
  Pre-validato `#a24cc7`, 4 Validato `#2563c9`. **Percorribilità** (`CaiTrailPracticability`, `PER`): percorribile, con
  difficoltà, non percorribile, sconosciuto. Checkpoint: `tests/Feature/EndToEnd/Fase10SentieriSezioneEndToEndTest.php`,
  collaudo F10-227..F10-287.
- **Gotcha del dump**: i `pg_dump` recenti scrivono `\restrict`/`\unrestrict`, che lo psql 16.4 dell'immagine non
  conosce (`invalid command \restrict`): lo script li filtra con `sed`. `hiking_routes.name` è un JSON traducibile
  (`{"it": "P69A - Partenza - Arrivo"}`), `hiking_routes.osmid` è vuoto (l'id OSM sta in
  `osmfeatures_data.properties.osm_id`), `ascent`/`descent` dei tag OSM mancano in ~60% dei sentieri (restano `null`).
  `issues_status` reali: `percorribile`, `percorribile parzialmente`, `non percorribile`, `sconosciuto`.
- **Scrittura** (`Import\CaiOsm2caiExtractWriter`): DROP+CREATE+INSERT delle sole `snap_osm2cai_trails` /
  `snap_osm2cai_trail_sections` in una transazione; intestazione dei CSV = `TRAIL_COLUMNS` / `TRAIL_SECTION_COLUMNS`
  (se cambia `extract.sql`, cambiare anche le costanti, o il comando rifiuta il CSV). Cella vuota → `NULL`.
- Geometria semplificata a 0.00002° (~1 pixel a zoom 16) e 6 decimali: 108 MB di GeoJSON invece di 200 MB.
- **Tabelle locali** (US-1052): `cai_trails` (modello `CaiTrail`, PK `id` = id osm2cai, `geometry` json GeoJSON, colonna
  `practicability` ricavata all'import) e `cai_trail_section` (`cai_trail_id` FK in cascata, `section_code` **senza FK**:
  i 22 codici osm2cai assenti da `cai_sections` restano nel legame). Relazioni `CaiSection::trails()` /
  `CaiTrail::sections()` (belongsToMany: un sentiero condiviso appartiene a ogni sua Sezione). Enum `CaiTrailStatus`
  (1–4, `getLabel()` "1 · Difficoltà"…, `color()`/`foreground()` di `SDA`) e `CaiTrailPracticability`
  (`fromIssuesStatus()`: "percorribile parzialmente" → con difficoltà, vuoto/ignoto → sconosciuto; colori di `PER`).
  `CaiTrailPolicy`: sola lettura, `view` = staff `CaiDirectoryView`, customer di una delle Sezioni, GR il cui prefisso
  contiene una delle Sezioni.
- **Import** (`Import\CaiTrailsImporter`, righe `snapshot_sentieri` / `snapshot_sentieri_sezioni` di `cai:import-datapack`):
  query builder a blocchi da 500 in una transazione (~7 s per 26.000 sentieri), **mai** Eloquent riga per riga. La
  geometria si scrive e si confronta come stringa della fonte: funziona perché la colonna è `json` (Postgres conserva il
  testo), non `jsonb` — non cambiare il tipo senza rivedere l'idempotenza. Datapack senza `snap_osm2cai_*` → solo una
  riga `snapshot_sentieri` con avviso, sentieri intatti.
- **Dato della fonte**: nel dump di settembre 2026 nessun sentiero è legato a codici `9218…` (SAT/Trentino): la SAT
  risulta senza sentieri. Per verifiche "molti sentieri" usare p.es. Parma (9224001, 380) o Sulmona (9234004, 382).
- **Pagina "Sentieri di Sezione"** (`SectionTrails`, US-1054, slug `sezione/sentieri` della vecchia pagina "in arrivo"):
  filtri/ordinamento in `Support\CaiTrailFilters` (riusabili dal GR) con `Enums\CaiTrailSort` (`?ordina=`, numero in
  ordine naturale, mancanti in fondo), `Enums\CaiTrailDifficulty` (`?difficolta=`, conta la prima sigla di `cai_scale`:
  "EEA:PD" → EEA, "T/E" → T) e `CaiTrailPracticability::key()` (`?percorribilita=`); stati in `?stato[]=`. Nota
  "Condiviso con …" con `Support\CaiSectionShortName` ("SEZ. PARMA" → "CAI Parma", "SEZ. S.A.T." → "SAT"). La mappa
  riceve le sole linee filtrate (Parma: ~1 s a filtro sul dev locale). La riga seleziona la linea, "›" (e la card
  mobile) apre la scheda con `trailUrl()` = `CaiTrailDetail` + `listQuery()` (filtri e ordinamento correnti).
- **Scheda del sentiero** (`Filament\Pages\CaiTrailDetail`, US-1055, slug `sentieri/{trailId}`): stessa struttura di
  `CaiHutDetail` (`canAccess()` per profilo + `CaiTrailPolicy::view` in `mount()`, 404 su id non numerico). Il link di
  ritorno riporta le chiavi `SectionTrails::LIST_QUERY_KEYS` (`stato` è una LISTA: `listQueryFrom()` accetta stringhe e
  array di stringhe); con `?da=gr` o utente GR punta a `RegionalGroupTrails` con gli stessi parametri — **la 10.7, se
  l'elenco del GR usa chiavi diverse, deve estendere `listQueryFrom()`**. Mappa: il tracciato come linea `selected`
  senza popup (spessore/bordo della linea selezionata) + marker `start`/`end` (A/B, primo e ultimo punto della
  geometria, `CaiTrailDetail::endpoints()`), `x-cai.map` con `fit-max-zoom` 15. Avviso di percorribilità e suoi colori
  da `CaiTrailPracticability::alertTitle()`/`alertBorder()`.
- **`Livewire::withQueryParams()` resta attivo per i `Livewire::test` successivi dello stesso test**: azzerarlo con
  `withQueryParams([])` prima di un secondo componente senza parametri.
- **Test Livewire dopo un `call()`**: `assertSeeTextInOrder` lavora sulla risposta JSON, dove "→" non si trova:
  verificare l'ordine sullo `strip_tags($component->html())` (vedi `SectionTrailsTest`).
