# Dominio Documentation (e Tag/commesse correlati)

Si carica quando lavori sotto `app/Domain/Documentation/*` (o `app/Domain/Tags/*`, tenuto qui per la stretta
relazione fra i due — vedi US-405). Vedi anche `app/Domain/CLAUDE.md` per i pattern condivisi, `docker/CLAUDE.md`
per il driver Chrome/PDF, e `app/Filament/CLAUDE.md` §US-403 per il pattern `ViewColumn` usato dalla barra SAL
dei Tag.

## Schema — Tag/commesse e Documentazione (US-013)

- **Come completare una FK "differita" annunciata da una story precedente**: quando US-012 ha creato
  `ticket_tag.tag_id` come `unsignedBigInteger` senza vincolo (perché `tags` non esisteva ancora), questa
  story ha aggiunto una **migrazione dedicata separata** (`..._add_tag_foreign_key_to_ticket_tag_table.php`),
  **non** ha modificato la migrazione originale di `ticket_tag`. Stesso pattern per qualunque FK differita
  futura: una migrazione `add_..._foreign_key_to_..._table` a parte, ordinata dopo.
- Il vincolo FK differito, una volta aggiunto, va anche **testato con dati reali**: crea le due righe
  collegate, verifica la relazione Eloquent, poi `forceDelete()` la riga referenziata e verifica il
  cascade/nullify lato SQLite (i vincoli FK di SQLite sono abilitati di default in questo progetto).
- `documentation_pages.category` (`DocumentationCategory`: `internal`/`customer`) implementa solo `HasLabel`,
  non `HasColor`/`HasIcon`: per un enum a 2 valori senza semantica di stato, un colore/icona non aggiungerebbe
  informazione.
- Anti-pattern esplicito da non riprodurre (§16.2 #13 del PRD): il v1 aveva `Documentation::creator()` verso
  una colonna `creator_id` inesistente. `DocumentationPage` **non ha alcuna colonna/relazione autore** finché
  nessun AC la richiede — se una story futura ne avrà bisogno, aggiungere una migrazione `created_by` FK→users
  nullable esplicita (l'ETL non potrà popolarla sui dati storici, resterà `null`), non aggiungerla
  preventivamente.

## Model con visibilità per categoria/ruolo — `scopeVisibleTo()` + Policy che delega (US-404)

- **Pattern canonico** (vedi anche `app/Domain/CLAUDE.md`): `DocumentationPage::scopeVisibleTo()` e la
  Policy `view()` delega a quello scope invece di ripetere la logica sul model già caricato — un solo punto
  di verità, riusabile sia per il controllo puntuale sia per filtrare un elenco.
- **Attenzione a non trattare due permessi "categoria" come una gerarchia se non lo sono esplicitamente**:
  `documentation.view.customer` e `documentation.view.internal` sono due gate indipendenti (§9.4) — avere
  `.view.internal` NON implica vedere anche le pagine `category=customer`. Nella pratica tutti i ruoli con
  `.view.internal` hanno anche `.view.customer` via `RolePermissionSeeder`, quindi il bug sarebbe invisibile
  nei test end-to-end per ruolo ma rotto in un test isolato con un solo permesso.
- **Media collection su un model con visibilità ristretta va sempre su un disco privato dedicato**, mai sul
  disco `public` di default di medialibrary: nuovo disco in `config/filesystems.php` (`root` sotto
  `storage_path('app/private/...')`, `serve` a `false`), richiamato con `->useDisk('nome-disco')` in
  `registerMediaCollections()` — vedi `documentation-attachments`, stessa motivazione di `ticket-attachments`.

## Auto-tag da evento di dominio, ricerca full-text, form con upload di media (US-405)

- **La cartella di una Filament Resource deve corrispondere allo slug/plurale della Resource, non al
  namespace del dominio sottostante**: creare `app/Filament/Resources/Documentation/...` (cartella singolare,
  dal namespace) produce un URL sbagliato (`admin/documentation/documentation-pages`) invece di
  `admin/documentation-pages`. Fix: rinominare la cartella per farla coincidere con il plurale
  (`DocumentationPages`, come `Tags`/`Tickets`/`Users`/`EmailMessages`).
- **Un `hasMany`/`belongsTo` con FK esplicita su un lato deve avere la stessa FK esplicita anche sul lato
  inverso**: `Tag::documentationPage()` dichiarava `belongsTo(DocumentationPage::class, 'documentation_id')`,
  ma `DocumentationPage::tags()` era un `hasMany(Tag::class)` senza secondo argomento — Eloquent deduce la
  convenzione `documentation_page_id`, colonna inesistente. Quando si aggiunge la seconda metà di una
  relazione già esistente, verificare sempre la FK effettiva in migrazione, mai fidarsi della convenzione.
- **La "ricerca full-text" di questo repo è, all'atto pratico, `TextColumn::make(...)->searchable()`
  Filament standard** (LIKE/ILIKE): l'indice GIN Postgres è un'ottimizzazione DB mai interrogata con
  `@@`/`plainto_tsquery`. Per cercare su più colonne, marcare `->searchable()` su entrambe (Filament le
  combina in OR). Se una colonna cercabile non deve essere visibile di default, usare
  `->toggleable(isToggledHiddenByDefault: true)`, MAI `->hidden()` (esclude anche dalla ricerca).
- **Un evento di dominio innescato da un cambio-di-campo specifico va emesso SOLO quando il valore è
  cambiato davvero**, confrontando prima/dopo dentro l'Action — mai su ogni update.
- **Pattern "un Model A crea/rinomina un Model B collegato" senza hook Eloquent**: Action
  (`CreateDocumentationPage`/`UpdateDocumentationPage`) che fa `DB::transaction` + `event(new XxxEvent(...))`,
  listener nel modulo di dominio del *risultato* (`App\Domain\Tags\Listeners`, non
  `App\Domain\Documentation\Listeners`), registrazione manuale in `AppServiceProvider::boot()`.
- **Un `FileUpload::make('nome')->storeFiles(false)` per un campo che NON è un attributo Eloquent** (qui
  `documents`/`images`, media collection Spatie) va rimosso esplicitamente da `$data` prima dell'Action, poi
  processato a parte con `$record->addMedia($file)->toMediaCollection('nome')` in
  `handleRecordCreation()`/`handleRecordUpdate()` (stesso idioma di `AddTicketAttachment`). Se sia
  `CreateXxx` sia `EditXxx` ripetono la logica, estrarla in un trait condiviso
  (`AttachesDocumentationMedia`).
- **Verifica in browser di un submit Filament che redirige**: non fidarsi di `page.click(...)` seguito subito
  da `page.url()` — usare `page.waitForURL(url => ...)` con una condizione esplicita PRIMA di procedere
  (rischio concreto: doppio click, record duplicato).

## Generazione PDF con `spatie/laravel-pdf` — Action+Job+comando, evento separato dal Tag (US-406, §6.4.3)

- **Driver `chrome` (chrome-php/chrome, CDP puro PHP), non `browsershot`**: nessun secondo runtime Node.js a
  runtime. Il binario e i pacchetti `apk` da installare vivono in `docker/CLAUDE.md` (Dockerfile
  sviluppo/UAT) — consultalo per l'installazione e i 3 bug di infra scoperti al primo merge/deploy reale.
- **Header/footer del PDF vanno incorporati nell'HTML del contenuto**, non passati come
  `headerHtml()`/`footerHtml()` di Chrome (CSS troppo limitato). Il logo (`PDF_LOGO_PATH`, un percorso file
  system, mai un URL) va letto ed embeddato come data URI base64 (`App\Support\Pdf\LogoDataUri`, riusata da
  `app/Domain/Reporting/CLAUDE.md` §US-409) — un job in coda non deve dipendere dalla raggiungibilità HTTP
  dell'app verso se stessa.
- **Pattern Action+Job+comando, tre chiamanti un solo punto di generazione**:
  `GenerateDocumentationPagePdf::run($page)` chiamata sia dal `GenerateDocumentationPagePdfJob` (ShouldQueue,
  dispatchato da un listener su creazione/modifica) sia dal comando `documentation:regenerate-pdfs`.
- **Un evento "il contenuto è cambiato" per innescare un side-effect NON va confuso con un evento più
  specifico già esistente per un side-effect diverso**: `DocumentationPageRenamed` (solo `title`, rinomina il
  Tag collegato) e `DocumentationPageContentChanged` (`title` O `body`, rigenera il PDF) restano due eventi
  distinti — un cambio del solo `body` deve rigenerare il PDF ma non rinominare il Tag.

## Colonna custom con markup HTML — barra SAL sui Tag (US-403)

La barra SAL nella tabella Tags usa un pattern Filament GENERICO (`ViewColumn` invece di `TextColumn
->html()`, più il gotcha "rebuild CSS obbligatorio dopo classi Tailwind nuove") — vedi
`app/Filament/CLAUDE.md` §US-403 per il dettaglio completo, riusabile per qualunque colonna con markup non
banale.
