# PRD: Menu "Anagrafica CAI" — sotto-menu Sezioni / Bilanci / Gruppi regionali + lista "Bilancio 2025"

> Origine: memo vocale del 03/10/2026 10:05 (3 min 10 s, trascrizione in §1.1). Numerazione story `US-940..US-948`.
> Branch: `ralph/orchestrator-v2-fase-9`. Note di dominio da leggere prima di toccare codice:
> `app/Filament/CLAUDE.md` (pattern navigazione/Resource/pagine), `app/Domain/CaiDirectory/CLAUDE.md`,
> `app/Domain/CLAUDE.md`, `docs/collaudo/CLAUDE.md`.

## 1. Introduzione / Overview

Il menu di amministrazione ("Anagrafica CAI", visibile agli utenti staff come `info@montagnaservizi.com`, ruolo
Admin) oggi è un unico gruppo piatto con tre voci: **Anagrafica CAI** (lista sezioni), **Mappa sezioni**,
**Bilanci non interpretati**. Con l'arrivo dei bilanci 2025 delle Sezioni (import appena concluso: 402 file,
345 sezioni, `CaiDocument` con `source = Manual`) serve:

1. riorganizzare il menu in tre sotto-menu (**Sezioni**, **Bilanci**, **Gruppi regionali**);
2. una nuova voce **Bilancio 2025** che elenca le sezioni con filtri sulla presenza dei file di conto economico /
   stato patrimoniale e sul fatto che siano stati "interpretati" (cifre estratte dal parser);
3. una lista dei **Gruppi regionali** come prima voce del relativo sotto-menu.

### 1.1 Trascrizione del memo (testo originale, dettatura automatica)

> Voglio modificare il menù per gli utenti amministrazione come info@montagnaservizi.com. In particolare, voglio
> lavorare sulla sezione anagrafica CAI. La sezione anagrafica CAI deve avere tre sotto menu. Primo sotto menù,
> **sezioni**, che contiene l'attuale **mappa sezioni** e la voce **anagrafica CAI** che, dato che riguarda le
> sezioni, deve chiamarsi **anagrafica sezioni**. Un sottovoce che si chiama **bilanci**. Nella sottovoce bilanci
> deve esserci l'attuale voce **bilanci non interpretati**. Inoltre anno per anno verranno aggiunti, per ogni
> sezione, il bilancio di un particolare anno; voglio partire con il 2025. Quindi ci dev'essere una nuova voce di
> menù che si chiama **bilancio 2025**, dove deve essere presente una **lista filtrabile** con **denominazione
> della sezione** e **regione di appartenenza**. Se è presente almeno un file per il **conto economico**, quindi un
> booleano sì/no, dev'essere anche filtrabile; se è presente almeno un file per lo **stato patrimoniale**, anche
> questo dev'essere un campo presente nei filtri; e se questi file sono **stati interpretati** (una voce che
> rappresenta il risultato economico e una voce che rappresenta lo stato patrimoniale), nei filtri deve essere
> presente un filtro booleano che permette di selezionare solo quelli che hanno effettivamente il **conto
> economico interpretato** o lo **stato patrimoniale interpretato**. Altra voce principale dell'anagrafica CAI è
> **gruppi regionali**. E come prima voce deve avere la **lista dei gruppi regionali** con le informazioni che
> ritieni opportuno. Per ora ci fermiamo così.

### 1.2 Stato attuale verificato nel codice (NON rifare l'esplorazione)

- Il gruppo di navigazione è la stringa `'Anagrafica CAI'` dichiarata in 3 punti:
  `app/Filament/Resources/CaiSections/CaiSectionResource.php` (label "Anagrafica CAI", icona
  `OutlinedBuildingLibrary`), `app/Filament/Pages/CaiSectionsMap.php` ("Mappa sezioni", gate
  `Permission::CaiDirectoryView`), `app/Filament/Pages/CaiUnparsedFinancialStatements.php` ("Bilanci non
  interpretati", gate `Permission::CaiDirectoryReviewUnparsedDocuments`).
- `AdminPanelProvider` (`app/Filament/Providers/AdminPanelProvider.php`) fa `discoverResources`/`discoverPages` e
  non definisce `NavigationGroup`; l'unico `NavigationItem` manuale è "Mailpit" (gruppo "Email").
- Nel menu **cliente** esiste già un gruppo chiamato `'Sezioni'` (`CustomerRegionalSectionsDashboard`, solo
  utenti cliente Gruppo Regionale): staff e clienti non vedono mai le pagine dell'altro (gate `canAccess`), ma
  attenzione a non creare una collisione di etichette se si riusa il nome "Sezioni" come gruppo.
- **Conto economico interpretato = già supportato**: `cai_financial_statements` (una riga per sezione/anno:
  chiave `cai_section_id`+`year` per le sezioni dirette, oppure per registrazione RUNTS) ha `total_expenses`,
  `total_revenues`, `net_result` e le voci oneri/proventi A–E, popolate da
  `AnalyzeCaiFinancialStatementDocument` + servizio Python `cai-runts-scraper/app/analyzer.py`.
- **Stato patrimoniale interpretato = NON esiste**: l'analyzer Python estrae solo oneri/proventi/risultato
  (regex su "Totale oneri e costi", "Totale proventi e ricavi", …); nessuna colonna di attivo/passivo/patrimonio
  netto. Va costruito (US-942, US-943).
- `CaiDocument` ha due genitori mutuamente esclusivi: `cai_runts_registration_id` (documenti RUNTS) oppure
  `cai_section_id` (upload/import manuale). `document_type` è stringa libera: i documenti RUNTS usano un
  vocabolario proprio (`bilancio_esercizio`, …); quelli manuali usano `CaiDocumentType` (`mod_a`, `mod_b`,
  `altro`, …). **Nell'import dei bilanci 2025 la maggior parte dei documenti è `altro`** (etichette libere tipo
  "Bilancio consuntivo", "Conto economico", "Stato Patrimoniale e Rendiconto gestionale"): per riconoscere un
  file di conto economico/stato patrimoniale servono sia il tipo sia parole chiave nel `title` (US-941).
- I Gruppi Regionali **non hanno un modello dedicato** (fuori scope di Fase 8): sono utenti
  (`users.customer_type = CustomerType::GruppoRegionale`, `users.region` castata sull'enum
  `App\Domain\Identity\Enums\Region`, es. `GR Toscana`, `gr_cai_toscana@cai.it`; 21 in locale).
  `App\Domain\Identity\Queries\SectionsInRegionQuery` risolve già "le sezioni della regione di un GR": riusarlo,
  non reinventare la normalizzazione `toscana` ↔ `TOSCANA` (e `cai_sections.region` è testo libero, include
  "EXTRA REGIONE").
- Gotcha Postgres/sqlite: i test girano su sqlite, lo sviluppo su Postgres. Per confronti case-insensitive usare
  `LOWER(col) LIKE ?`, mai `ILIKE`; mai `->having('alias')` su alias di `selectRaw` (usare `havingRaw`); per i
  flag booleani per riga usare sub-query `EXISTS` (portabili), non funzioni specifiche di un motore.

## 2. Goals

- Menu staff "Anagrafica CAI" con tre sotto-menu chiari: **Sezioni**, **Bilanci**, **Gruppi regionali**.
- La voce `Anagrafica CAI` diventa **Anagrafica sezioni**; "Mappa sezioni" e "Anagrafica sezioni" stanno sotto
  **Sezioni**; "Bilanci non interpretati" e il nuovo "Bilancio 2025" stanno sotto **Bilanci**; la lista dei
  gruppi regionali è la prima (e per ora unica) voce sotto **Gruppi regionali**.
- "Bilancio 2025": lista sezioni con denominazione e regione, filtrabile per presenza file conto economico,
  presenza file stato patrimoniale, conto economico interpretato, stato patrimoniale interpretato.
- Estrazione dello stato patrimoniale (attivo/passivo/patrimonio netto) nel parser, così che
  "stato patrimoniale interpretato" sia un dato reale e non un segnaposto.
- Struttura estendibile: aggiungere "Bilancio 2026" in futuro deve costare una riga (anno parametrico).

## 3. User Stories

### US-940: Riorganizzazione del menu "Anagrafica CAI" in tre sotto-menu
**Description:** As an admin, voglio vedere nel menu "Anagrafica CAI" tre sotto-menu (Sezioni, Bilanci, Gruppi
regionali) così da ritrovare le voci per argomento.

**Acceptance Criteria:**
- [ ] Riferimento obbligatorio: `docs/project/tasks/prd-menu-anagrafica-cai-bilanci-gruppi-regionali.md` (§1.2).
- [ ] Gruppo di navigazione unico `Anagrafica CAI` (come oggi), con questa struttura visibile nella sidebar:
      **Sezioni** → { *Anagrafica sezioni*, *Mappa sezioni* }; **Bilanci** → { *Bilanci non interpretati* };
      **Gruppi regionali** → (vuoto fino a US-947). L'ordine delle voci è fisso via `$navigationSort`.
- [ ] Tecnica: provare **prima** le voci figlie native di Filament 4 (`protected static ?string
      $navigationParentItem` sulle pagine/Resource figli, con voci genitore "Sezioni"/"Bilanci"/"Gruppi regionali"
      registrate come `NavigationItem` in `AdminPanelProvider::navigationItems()` o come pagine hub). Se, **dopo
      verifica reale in browser**, il rendering annidato non è utilizzabile (genitore non cliccabile, voce non
      espandibile, ecc.), usare il **fallback**: tre `NavigationGroup` con etichette `CAI · Sezioni`,
      `CAI · Bilanci`, `CAI · Gruppi regionali`, dichiarati in `->navigationGroups([...])` nel provider con
      l'ordine desiderato. Documentare in `progress.txt` quale via è stata scelta e perché.
- [ ] `CaiSectionResource`: `$navigationLabel = 'Anagrafica sezioni'` e titolo della lista/breadcrumb "Anagrafica
      sezioni" (non cambiare lo slug/URL né il nome della classe: link e test esistenti restano validi).
- [ ] Nessun cambio di permessi: ogni voce mantiene il proprio `canAccess()`/`canViewAny()` attuale (Sezioni e
      Mappa → `CaiDirectoryView`, Bilanci non interpretati → `CaiDirectoryReviewUnparsedDocuments`). Un sotto-menu
      senza voci visibili per l'utente non deve comparire (nessun gruppo/voce vuota per ruoli senza permesso).
- [ ] Il menu cliente (gruppi "GR", "Sezioni", "Area cliente") e gli altri gruppi staff restano invariati.
- [ ] Test Pest su `Filament::getNavigation()` (o equivalente) con utente Admin: etichette, ordine e
      raggruppamento attesi; con utente senza `CaiDirectoryView` il gruppo non compare.
- [ ] Tests pass (`php -d memory_limit=1G vendor/bin/pest --filter=Cai` e il nuovo file), Pint, Larastan verdi.
- [ ] Typecheck passes
- [ ] Verify in browser using dev-browser skill (Playwright come da `CLAUDE.md`): login come
      `info@montagnaservizi.com`, screenshot della sidebar con i sotto-menu, click su ogni voce esistente apre la
      pagina giusta.

### US-941: Riconoscimento dei documenti "conto economico" e "stato patrimoniale"
**Description:** As a developer, voglio un unico punto di verità che dica se un `CaiDocument` è (o contiene) un
conto economico e/o uno stato patrimoniale, perché le liste e i filtri dipendono da questo.

**Acceptance Criteria:**
- [ ] Nuova classe `App\Domain\CaiDirectory\Support\CaiFinancialDocumentKind` con due elenchi costanti:
      tipi `CaiDocumentType` e parole chiave del titolo, per ciascun "tipo" (conto economico / stato patrimoniale).
      Regole di partenza (da affinare solo con evidenza dai dati reali):
      - **Conto economico**: tipi `ModB`, `ModAB`, `CompletoABC`, `BilancioEconomicoFinanziario`, `ModD`
        (rendiconto per cassa: equivalente per le sezioni piccole); parole chiave nel titolo (lowercase,
        accenti ignorati): `conto economico`, `rendiconto gestionale`, `rendiconto per cassa`, `rendiconto
        finanziario`, `bilancio consuntivo`, `bilancio economico`, `situazione economica`, `rendiconto economico`.
      - **Stato patrimoniale**: tipi `ModA`, `ModAB`, `CompletoABC`; parole chiave: `stato patrimoniale`,
        `situazione patrimoniale`, `bilancio patrimoniale`, `patrimoniale`.
      - Un documento può essere **entrambi** (es. "Stato Patrimoniale e Rendiconto gestionale").
      - Per i documenti RUNTS (`document_type = bilancio_esercizio`) valgono le sole parole chiave del titolo
        (es. "Mod. B - Rendiconto Gestionale", "Mod. A - Stato Patrimoniale").
- [ ] Scope Eloquent su `CaiDocument`: `scopeIncomeStatement()` e `scopeBalanceSheet()` (SQL portabile
      sqlite+Postgres: `whereIn('document_type', [...]) OR LOWER(title) LIKE '%…%'`, mai ILIKE) più i metodi
      d'istanza `isIncomeStatement(): bool` / `isBalanceSheet(): bool` che usano **le stesse** costanti
      (un solo elenco, due modi di applicarlo; test di parità sui titoli reali).
- [ ] Scope `forYear(int $year)` (`where('year', $year)`).
- [ ] Test Pest con un dataset di almeno 25 titoli reali (dall'import: `Bilancio consuntivo`, `Mod D - Rendiconto
      per cassa`, `Conto economico`, `Stato Patrimoniale e conto economico`, `Stato Patrimoniale`, `Rendiconto per
      cassa`, `Mod A e B - Stato Patrimoniale e Rendiconto gestionale`, `Relazione di missione` (→ nessuno dei
      due), `Verbale assemblea` (→ nessuno), `Quote associative 2026` (→ nessuno), …) che asserisce sia gli
      scope (su DB sqlite) sia i metodi d'istanza.
- [ ] Nessuna colonna nuova, nessuna business logic in hook Eloquent.
- [ ] Tests pass, Pint, Larastan verdi.
- [ ] Typecheck passes

### US-942: Estrazione dello stato patrimoniale nel servizio Python `cai-runts-scraper`
**Description:** As a developer, voglio che l'analyzer Python estragga dai PDF Mod A i totali dello stato
patrimoniale, così "stato patrimoniale interpretato" diventa un dato reale.

**Acceptance Criteria:**
- [ ] `cai-runts-scraper/app/analyzer.py` estrae tre campi aggiuntivi, restituiti nella stessa risposta di
      `POST /analyze/bilancio` accanto ai campi esistenti: `totale_attivo`, `totale_passivo`,
      `patrimonio_netto` (float o `null`). Pattern (da affinare sui testi reali): "TOTALE ATTIVO", "Totale
      attività", "TOTALE PASSIVO", "Totale passivo", "Totale patrimonio netto", "Patrimonio netto", con la stessa
      gestione dei numeri italiani (`1.234,56`, `1.234`, apostrofo) già usata per oneri/proventi.
- [ ] I campi esistenti e il comportamento su tutti i test pytest esistenti **non cambiano** (nessuna regressione).
- [ ] Test pytest con testi sintetici (stringhe inline, mai PDF reali committati) per: Mod A standard ETS, layout
      a due colonne attivo/passivo, numeri con apostrofo, testo senza stato patrimoniale (→ tutti `null`).
- [ ] Verifica manuale su **almeno 8 PDF reali** di "Mod A - Stato Patrimoniale" / "Stato Patrimoniale" /
      "Mod A e B …" presi da `cai-datapack/bilanci-sezioni-2026/normalized/` (gitignored, non committare nulla),
      eseguendo `analyze` localmente: riportare in `progress.txt` quanti restituiscono almeno `totale_attivo`.
      Obiettivo ≥ 60% dei file testuali (i PDF scansione/OCR possono restare `null`); sotto il 60% annotare i
      pattern mancanti ma non bloccare la story.
- [ ] Immagine Docker del servizio ricostruita (`docker compose build cai-runts-scraper`) e `GET /health` ok.
- [ ] Tests pass (pytest)
- [ ] Typecheck passes

### US-943: Persistenza dello stato patrimoniale in `cai_financial_statements`
**Description:** As a developer, voglio salvare i totali dello stato patrimoniale estratti, con merge
campo-per-campo come già avviene per il conto economico.

**Acceptance Criteria:**
- [ ] Nuova migrazione **additiva** (mai modificare quelle già committate) che aggiunge a
      `cai_financial_statements` tre colonne `decimal(15,2)` nullable: `total_assets`, `total_liabilities`,
      `net_equity`. `CaiFinancialStatement`: `#[Fillable]` e cast aggiornati.
- [ ] `CaiFinancialStatementFieldMapper::mapFinancialStatement()` mappa `totale_attivo`→`total_assets`,
      `totale_passivo`→`total_liabilities`, `patrimonio_netto`→`net_equity` (assenti/`null` → `null`; il mapper
      resta usato anche dall'import del datapack, dove quei campi non esistono: nessun errore).
- [ ] `AnalyzeCaiFinancialStatementDocument::upsertMerging()` include le tre colonne nel merge "il valore non-null
      vince" già esistente, con lo stesso `lockForUpdate` (nessuna nuova race). Aggiornare
      `recordDocumentAnalysisOutcome()`/`$hasAnyData` perché un documento con **solo** stato patrimoniale estratto
      conti come `Extracted` (non `NoDataExtracted`).
- [ ] Helper sul modello: `CaiFinancialStatement::hasIncomeStatementData(): bool`
      (`total_expenses`, `total_revenues` o `net_result` non null) e `hasBalanceSheetData(): bool`
      (`total_assets` o `net_equity` non null) — un solo punto di verità per "interpretato".
- [ ] Test Pest aggiornati/aggiunti: mapper (con e senza campi nuovi), job con risposta del client mockata che
      contiene solo stato patrimoniale / solo conto economico / entrambi, merge fra due documenti dello stesso
      anno (uno CE, uno SP → record con entrambi), helper `has*Data()`. I test esistenti di
      `AnalyzeCaiFinancialStatementDocumentTest` e `CaiImportDatapackCommandTest` restano verdi.
- [ ] Migrazione verificata con `php artisan migrate --pretend` prima di applicarla al Postgres di sviluppo
      (gotcha in `app/Domain/CaiDirectory/CLAUDE.md`); applicarla con `--force` solo dopo.
- [ ] Tests pass, Pint, Larastan verdi.
- [ ] Typecheck passes

### US-944: Query "stato bilancio per sezione e anno"
**Description:** As a developer, voglio un unico query object che arricchisca le sezioni con i quattro flag
dell'anno, usato dalla lista "Bilancio 2025" e dalla lista Gruppi regionali.

**Acceptance Criteria:**
- [ ] Nuovo `App\Domain\CaiDirectory\Queries\CaiSectionFinancialYearQuery::forYear(int $year): Builder`
      (sulla `CaiSection`), che aggiunge via sub-query `EXISTS` (niente `having` su alias) i flag booleani:
      `has_income_statement_file`, `has_balance_sheet_file`, `income_statement_parsed`,
      `balance_sheet_parsed`.
      - `has_*_file`: esiste un `CaiDocument` dell'anno (`forYear`) che soddisfa `scopeIncomeStatement()` /
        `scopeBalanceSheet()` (US-941) **collegato alla sezione sia direttamente (`cai_section_id`) sia tramite una
        sua `CaiRuntsRegistration`**.
      - `*_parsed`: esiste un `CaiFinancialStatement` dell'anno collegato alla sezione (direttamente o via
        registrazione) con `hasIncomeStatementData()`/`hasBalanceSheetData()` logicamente veri (stesse colonne
        non null, espresse in SQL).
- [ ] Scope di filtro riusabili sulla stessa classe (`withIncomeStatementFile(bool)`,
      `withBalanceSheetFile(bool)`, `withIncomeStatementParsed(bool)`, `withBalanceSheetParsed(bool)`) con un
      solo punto di verità per le condizioni (la colonna booleana e il filtro non devono divergere).
- [ ] Nessuna N+1: una sola query per pagina di lista (verificato con `DB::enableQueryLog()` in un test).
- [ ] Test Pest su sqlite con fixture: sezione con documento diretto 2025 CE (flag file vero, parsed falso);
      sezione con documento via registrazione RUNTS; sezione con financial statement 2025 con solo CE; sezione
      con solo SP; sezione con documenti di un altro anno (tutti falsi per 2025); sezione senza nulla.
- [ ] Tests pass, Pint, Larastan verdi.
- [ ] Typecheck passes

### US-945: Pagina "Bilancio 2025" nel sotto-menu Bilanci
**Description:** As an admin, voglio una lista delle sezioni con lo stato dei bilanci 2025 filtrabile, per
sapere chi ha consegnato cosa e cosa è stato interpretato.

**Acceptance Criteria:**
- [ ] Nuova pagina Filament `App\Filament\Pages\CaiFinancialYear2025` (classe tabella generica parametrica
      sull'anno, es. base astratta `CaiFinancialYearPage` con `abstract static function year(): int`, così
      "Bilancio 2026" richiede una sola sottoclasse di poche righe), voce di menu **"Bilancio 2025"** sotto
      **Bilanci** (dopo "Bilanci non interpretati" nell'ordine; usare la tecnica scelta in US-940), titolo pagina
      "Bilancio 2025", gate `Permission::CaiDirectoryView`.
- [ ] Tabella (pattern `InteractsWithTable` + `EmbeddedTable` come `CaiUnparsedFinancialStatements`) sulla query di
      US-944, con colonne: **Denominazione** (searchable, sortable), **Regione** (sortable), **Codice CAI**,
      **Conto economico (file)**, **Stato patrimoniale (file)**, **Conto economico interpretato**, **Stato
      patrimoniale interpretato** — le quattro booleane come `IconColumn` (sì/no) con tooltip. Ordinamento
      predefinito: regione, poi denominazione. Paginazione 25/50/100.
- [ ] Filtri: **Regione** (`SelectFilter` con i valori distinti di `cai_sections.region`, mai l'enum, come in
      `CaiSectionsTable`), e quattro `TernaryFilter` (Tutte / Sì / No): *Ha file conto economico*, *Ha file stato
      patrimoniale*, *Conto economico interpretato*, *Stato patrimoniale interpretato*, ciascuno collegato allo
      scope corrispondente di US-944. I filtri sono combinabili (AND).
- [ ] La ricerca per testo copre denominazione e codice CAI.
- [ ] Azione di riga "Apri scheda" che porta alla vista della sezione (`CaiSectionResource` view) sul tab
      Allegati se già esiste l'helper (vedi `CaiSectionInfolist`, ricarica su `?tab=allegati`).
- [ ] Stato vuoto esplicito quando i filtri non danno risultati.
- [ ] Test Pest Livewire (`Livewire::test(...)`, `->filterTable(...)`, **non** `fillForm` — gotcha in `CLAUDE.md`):
      con fixture di 4 sezioni verificare ogni filtro singolarmente e una combinazione (regione + CE
      interpretato); accesso negato (403) a un utente senza `CaiDirectoryView`.
- [ ] Tests pass, Pint, Larastan verdi.
- [ ] Typecheck passes
- [ ] Verify in browser using dev-browser skill: la pagina elenca le sezioni reali, il filtro "Ha file conto
      economico = Sì" riduce l'elenco, screenshot letto con Read.

### US-946: Comando di analisi dei documenti dell'anno (conto economico e stato patrimoniale)
**Description:** As a data manager, voglio far interpretare i bilanci 2025 già importati, perché l'import non
accoda mai l'analisi da solo.

**Acceptance Criteria:**
- [ ] Comando `cai:analyze-financial-documents` in `app/Console/Commands/`
      (`--year=2025` obbligatorio, `--section=<codice_cai>` opzionale, `--force`, `--dry-run`).
      Seleziona i `CaiDocument` dell'anno che soddisfano `scopeIncomeStatement()` **o** `scopeBalanceSheet()`
      (US-941) e `financial_analysis_status IS NULL` (mai analizzati; con `--force` anche gli altri); per ognuno
      `AnalyzeCaiFinancialStatementDocument::dispatch($id)->onQueue('cai-runts-analysis')`.
- [ ] `--dry-run` non accoda nulla ma stampa quanti documenti verrebbero accodati e il dettaglio per regione.
- [ ] Output finale: conteggio accodati, saltati (già analizzati), e promemoria di guardare Horizon/coda
      `cai-runts-analysis`. Comando sottile (opzioni + delega a una query già esistente + stampa), log
      strutturato `started`/`finished`, idempotente per costruzione (stato non più `null` dopo l'analisi).
- [ ] Un documento con `year = null` non viene mai accodato (il job lo ignorerebbe comunque).
- [ ] Test Pest con `Queue::fake()`: accodamento selettivo (solo CE/SP, solo anno richiesto, solo mai analizzati),
      `--force`, `--section`, `--dry-run`, anno mancante → errore esplicito.
- [ ] Tests pass, Pint, Larastan verdi.
- [ ] Typecheck passes

### US-947: Voce "Gruppi regionali" → lista dei gruppi regionali
**Description:** As an admin, voglio una lista dei Gruppi Regionali con le informazioni utili a colpo d'occhio,
come prima voce del sotto-menu Gruppi regionali.

**Acceptance Criteria:**
- [ ] Nuova pagina Filament `App\Filament\Pages\CaiRegionalGroups`, voce **"Elenco gruppi regionali"** come
      **prima** (e per ora unica) voce sotto il sotto-menu **Gruppi regionali** (tecnica come US-940), gate
      `Permission::CaiDirectoryView`.
- [ ] Sorgente: utenti con `customer_type = CustomerType::GruppoRegionale` (confronto con l'enum, mai stringhe
      grezze). Colonne: **Gruppo** (nome utente, searchable), **Regione** (`Region::label()`), **Email** (searchable),
      **Sezioni** (numero di sezioni della regione, via `SectionsInRegionQuery`), **Con bilancio 2025** (sezioni
      con almeno un documento dell'anno), **Conto economico interpretato 2025** (n° sezioni), **Copertura %**
      ((con bilancio 2025 / sezioni), una cifra intera, "—" se 0 sezioni), **Utente attivo** (stato account,
      come mostrato altrove nel repo per gli utenti disattivati).
- [ ] I conteggi si calcolano con **una query aggregata per regione** (mai una query per riga): riusare
      `CaiSectionFinancialYearQuery::forYear(2025)` (US-944) raggruppata per regione + mappatura
      `Region` ↔ `cai_sections.region`; verifica con `DB::enableQueryLog()` che il numero di query non cresca con
      il numero di gruppi.
- [ ] Filtro **Regione** (SelectFilter sulle regioni dei GR presenti) e ordinamento per ogni colonna numerica.
- [ ] Azione di riga "Apri utente" verso la scheda dell'utente nella Resource Utenti, **solo** se l'utente
      corrente ha `Permission::UserView`; altrimenti l'azione non compare.
- [ ] Stato vuoto esplicito se non esiste nessun GR.
- [ ] Test Pest Livewire: 3 GR + sezioni di prova in regioni diverse (inclusa una sezione "EXTRA REGIONE" che non
      appartiene a nessun GR), conteggi corretti, filtro regione, azione "Apri utente" presente/assente per
      permesso, 403 senza `CaiDirectoryView`.
- [ ] Tests pass, Pint, Larastan verdi.
- [ ] Typecheck passes
- [ ] Verify in browser using dev-browser skill: la lista mostra i 21 GR locali con conteggi plausibili
      (es. Toscana 27 sezioni), screenshot letto con Read.

### US-948: Verifica su dati reali, collaudo e note per gli agenti
**Description:** As a maintainer, voglio la prova su dati reali che menu e liste funzionano, e la
documentazione allineata.

**Acceptance Criteria:**
- [ ] `docker compose exec -T app php artisan cai:analyze-financial-documents --year=2025 --dry-run`, poi la
      run reale (con il servizio `cai-runts-scraper` attivo e la coda `queue` in esecuzione); attendere la fine
      dei job e riportare in `progress.txt`: documenti accodati, quanti `Extracted`/`NoDataExtracted`, quante
      sezioni con CE interpretato e con SP interpretato nella pagina "Bilancio 2025".
- [ ] Verifica in browser (Playwright, script in `/tmp`, mai nel repo; utente `info@montagnaservizi.com` o
      account creato con `collaudo:ensure-manager-account`, ricordando il gotcha `->databaseNotifications()` su
      Postgres in `CLAUDE.md`): screenshot di (1) sidebar con i tre sotto-menu, (2) "Bilancio 2025" con un filtro
      attivo, (3) "Elenco gruppi regionali". Utenti di verifica creati → `forceDelete()` a fine prova.
- [ ] `docs/collaudo/fase-9.php`: topic "Menu Anagrafica CAI e bilanci 2025" con test numerati collegati a test
      automatici realmente esistenti (US-940..US-947); `php artisan collaudo:verify-manifest 9` passa. Paragrafo
      breve nel manuale `docs/collaudo/16-fase-9.md` (non rigenerare il PDF: `pdflatex` assente in locale; i
      contatori in testa al manuale vanno allineati quando si rigenera il PDF — annotarlo).
- [ ] `app/Filament/CLAUDE.md`: sezione breve "Sotto-menu di navigazione" con la tecnica scelta in US-940, il
      motivo e il gotcha della collisione col gruppo cliente "Sezioni". `app/Domain/CaiDirectory/CLAUDE.md`: nota
      su `CaiFinancialDocumentKind` (un elenco, scope + metodi), su `hasIncomeStatementData()`/
      `hasBalanceSheetData()` e su `cai:analyze-financial-documents`.
- [ ] `progress.txt` aggiornato nel formato esistente.
- [ ] `composer run lint`, `composer run analyse` e i test mirati (`--filter=Cai`, `--filter=Filament`)
      verdi; la suite intera NON è un criterio (crash preesistente documentato).
- [ ] Typecheck passes

## 4. Functional Requirements

- FR-1: Il menu staff mostra il gruppo "Anagrafica CAI" con i sotto-menu Sezioni, Bilanci, Gruppi regionali, nell'ordine
  indicato, solo agli utenti con i permessi esistenti (nessun nuovo permesso).
- FR-2: "Anagrafica sezioni" è la lista già esistente (stesso URL, stessa Resource); cambia solo l'etichetta.
- FR-3: "Bilancio 2025" mostra una riga per `CaiSection`, indipendentemente dal fatto che abbia documenti
  (le sezioni senza file compaiono con i quattro flag a "No").
- FR-4: Un documento conta come "file conto economico" / "file stato patrimoniale" secondo `CaiFinancialDocumentKind`
  (tipo documento **o** parole chiave del titolo), per l'anno selezionato.
- FR-5: "Interpretato" significa che esiste un `CaiFinancialStatement` dell'anno con almeno una cifra non nulla
  del relativo prospetto (CE: totale oneri/proventi o risultato; SP: totale attivo o patrimonio netto).
- FR-6: I flag della lista e i filtri usano lo stesso query object (nessuna logica duplicata fra colonna e filtro).
- FR-7: L'elenco gruppi regionali deriva dagli utenti `GruppoRegionale`; nessun nuovo modello/tabella per i GR.
- FR-8: Nessuna logica di business in hook Eloquent; confronti su enum (`CustomerType`, `Region`,
  `CaiDocumentType`), mai su stringhe grezze (§4.4 A4); `declare(strict_types=1);` in ogni file nuovo; test in
  sintassi Pest; testi utente in italiano.
- FR-9: SQL portabile fra sqlite (test) e Postgres (sviluppo/UAT): `EXISTS` per i flag, `LOWER(...) LIKE` per i
  confronti, niente `having` su alias.

## 5. Non-Goals (Out of Scope)

- Nessuna modifica ai menu cliente (Sezione, Gruppo Regionale) né ai permessi/ruoli.
- Nessun nuovo modello `RegionalGroup` o anagrafica GR dedicata; nessuna pagina di dettaglio/modifica dei GR
  (solo l'elenco e il link all'utente esistente).
- Nessuna riscrittura del parser oltre ai tre totali di stato patrimoniale (nessun dettaglio voce per voce, nessun
  nuovo OCR).
- Nessun upload/modifica/eliminazione documenti dalla lista "Bilancio 2025" (sola lettura + link alla scheda).
- Nessuna pagina per altri anni (solo struttura parametrica pronta; "Bilancio 2026" arriverà in un PRD successivo).
- Nessun export (CSV/XLSX) della lista "Bilancio 2025" in questa fase.
- Nessuna rigenerazione del PDF di collaudo.

## 6. Design Considerations

- Riusare i pattern esistenti: `CaiUnparsedFinancialStatements` (pagina con `EmbeddedTable`),
  `CaiSectionsTable` (filtro regione dai valori distinti), `app/Filament/CLAUDE.md` per icone/tema.
- Le colonne booleane come icone sì/no (verde/grigio) con tooltip esplicito ("Presente", "Assente"); evitare
  il solo colore come significato.
- Le due sezioni "Sezioni" e "Gruppi regionali" non hanno icona di gruppo propria se si usano le voci figlie
  native: scegliere icone coerenti (`Heroicon::Outlined*`) per i genitori.
- Etichette in italiano, esattamente come nel memo: "Anagrafica sezioni", "Mappa sezioni", "Bilanci non
  interpretati", "Bilancio 2025", "Gruppi regionali".

## 7. Technical Considerations

- Namespace: query in `App\Domain\CaiDirectory\Queries\*`, classificatore in `App\Domain\CaiDirectory\Support\*`,
  pagine in `app/Filament/Pages/`, comando in `app/Console/Commands/` (convenzione già in uso).
- Il servizio Python gira in Docker (`cai-runts-scraper`): dopo ogni modifica a `analyzer.py` ricostruire
  l'immagine e verificare `/health`; i test pytest girano nel venv del servizio (vedi US-912/US-918 in
  `progress.txt` per il comando esatto).
- Prima di scrivere sul Postgres di sviluppo: `docker compose ps`, `php artisan migrate --pretend`
  (gotcha ambientale in `app/Domain/CaiDirectory/CLAUDE.md`).
- Test isolati dal DB di sviluppo con `env -u DB_CONNECTION -u DB_DATABASE -u DB_HOST -u DB_PORT -u DB_USERNAME
  -u DB_PASSWORD` nel container (gotcha CRITICO in `CLAUDE.md`).
- Dati reali locali per le verifiche: 529 sezioni, 403 documenti manuali + 1999 RUNTS, 512
  `cai_financial_statements`, 21 utenti Gruppo Regionale.
- L'analisi dei documenti importati richiede il servizio Python e la coda `cai-runts-analysis` (Horizon) attivi.

## 8. Success Metrics

- Il menu staff mostra esattamente la struttura del memo; ogni voce preesistente apre la stessa pagina di prima.
- "Bilancio 2025" elenca tutte le 529 sezioni; il filtro "Ha file conto economico = Sì" restituisce un
  sottoinsieme coerente con i 345 documenti/sezioni importati (verifica incrociata con un conteggio SQL).
- Dopo `cai:analyze-financial-documents --year=2025`: almeno una parte misurabile delle sezioni compare come
  "conto economico interpretato" e, grazie a US-942/943, anche "stato patrimoniale interpretato"; i numeri sono
  riportati in `progress.txt`.
- "Elenco gruppi regionali" mostra i 21 GR con conteggi sezioni coerenti con `SectionsInRegionQuery`.
- Test dei domini Cai/Filament, pytest, Pint e Larastan verdi.

## 9. Open Questions

- **Sotto-menu annidato nativo vs gruppi separati**: Filament 4 supporta voci figlie (`navigationParentItem`)
  ma il genitore deve essere una voce cliccabile; US-940 decide in base al rendering reale. Se preferisci
  esplicitamente tre gruppi separati ("CAI · Sezioni", …) dillo prima di lanciare Ralph.
- **Rendiconto per cassa / Mod D** e "Bilancio consuntivo" contano come "conto economico"? Il PRD li include
  (sono il prospetto economico delle sezioni piccole); se vuoi solo Mod B / "Conto economico" esplicito, basta
  togliere le voci da `CaiFinancialDocumentKind`.
- **Filtri "interpretato"**: il memo parla di "un filtro booleano" per "conto economico interpretato o stato
  patrimoniale interpretato". Il PRD realizza due filtri separati (combinabili in AND). Serve anche un filtro
  unico "almeno uno dei due" (OR)?
- **Informazioni dei Gruppi regionali**: scelte di default (nome, regione, email, sezioni, copertura bilanci
  2025, CE interpretati, stato utente). Altre colonne utili? (es. presidente/contatti del GR, data ultimo
  accesso, ticket aperti).
- **Stato patrimoniale**: la qualità dell'estrazione dipende dai PDF (scansioni/immagini restano `null`);
  accettabile che una quota di sezioni risulti "non interpretato" e vada vista a mano dalla pagina "Bilanci non
  interpretati"?
