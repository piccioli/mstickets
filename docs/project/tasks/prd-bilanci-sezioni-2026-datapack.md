# PRD: Fase 9 (estensione) — Bilanci 2025 delle Sezioni nel datapack CAI (campagna "Bilanci Sezioni 2026")

> Numerazione story `US-930..US-938` (Fase 9 arriva a US-928). Branch di lavoro: `ralph/orchestrator-v2-fase-9`.
> Note di dominio da leggere prima di toccare codice: `app/Domain/CaiDirectory/CLAUDE.md`,
> `app/Domain/CLAUDE.md`, `deploy/CLAUDE.md` (sezione datapack), `docs/collaudo/CLAUDE.md`.

## 1. Introduzione / Overview

Nel 2026 il Gruppo Regionale di ogni regione ha raccolto (campagna Typeform) i bilanci 2025 delle proprie
Sezioni CAI e li ha normalizzati in cartelle per Sezione. Oggi questi file vivono solo su iCloud. Vogliamo
che finiscano in Orchestrator come **documenti caricati manualmente** (`CaiDocument` con
`source = Manual`, collegati direttamente alla `CaiSection`, esattamente come farebbe
`UploadCaiDocumentManually`), così che:

- ogni `migrate:fresh` + `cai:import-datapack` (locale con `make setup`, UAT ad ogni deploy) li ripopoli da solo;
- compaiano nel tab "Allegati" dell'Infolist della Sezione (staff, cliente Sezione, cliente Gruppo Regionale);
- il download passi dal `CaiDocumentDownloadController` già esistente.

La soluzione è uno **script (comando artisan) che aggiorna il datapack iniziale** (`cai-datapack/runts-cai.sqlite`)
aggiungendo una tabella `bilanci_manuali` + i file in `cai-datapack/bilanci-sezioni-2026/`, e una **nuova fase
dell'import** `cai:import-datapack` che legge quella tabella. Il datapack è gitignored (`/cai-datapack`) e
viene portato su UAT da `bin/push-cai-datapack`: nessun PDF finisce nel repository.

### Stato di partenza verificato (3 ottobre 2026) — NON rifare l'esplorazione

I file necessari sono **già stati copiati in locale** (gitignored, `git status` pulito) in
`cai-datapack/bilanci-sezioni-2026/`:

```
cai-datapack/bilanci-sezioni-2026/
├── 2026_Campagna_Sezioni.xlsx          # indice (foglio "Sezioni")
├── normalized/<Regione>/<codice - Nome sezione>/<codice - Nome sezione - <Etichetta>.<ext>>   # 402 file, 345 sezioni, 19 regioni
└── originals/2026_DA SMISTARE{,_2}/<hash12>-<nome originale>   # 399 file grezzi Typeform (solo audit/riferimento)
```

Sorgente originale (solo lettura, NON usata a runtime):
`~/Library/Mobile Documents/com~apple~CloudDocs/MS/BILANCI SEZIONI 2025/GR TOSCANA/` (nome fuorviante: contiene
tutte le regioni; `<Regione>/BILANCI 2025 GR <Regione>/` = versione normalizzata; `BILANCI 2025 mancanti`/
`BILANCI 2025 row` e `EMAIL_GR_*.txt` non servono). Il percorso iCloud contiene tilde (`com~apple~CloudDocs`).

Fatti verificati sul dataset reale:

- Foglio `Sezioni` del file Excel: intestazioni in riga 1 (`(vuota)`, `Nome`, …, `Regione`, …, `Bilanci`,
  `Link al bilancio`, `Link al bilancio 2`, …); **colonna A senza intestazione = `codice_cai`**, a 7 cifre
  (es. `9226005`), talvolta come stringa e talvolta come `float` (`9216157.0`) — normalizzare sempre a
  stringa di 7 cifre. 1006 righe, di cui 526 con codice; `Bilanci` ∈ {`RICEVUTO` 347, `DA VERIFICARE` 137,
  `PROBLEMA RUNTS` 41, vuoto}. I link sono URL Typeform (non scaricabili: servono solo come riferimento).
- Cartelle normalizzate: ogni sezione è `"<codice> - CAI <Nome>"`; ogni file è
  **`<codice> - CAI <Nome> - <Etichetta>.<ext>`** (nessuna eccezione sui 402 file). 345 sezioni, tutte presenti
  in `sezioni_cai` del datapack e nell'Excel; la regione della cartella coincide sempre con la regione
  dell'Excel. File per sezione: 1 (301 sezioni), 2 (37), 3 (3), 4 (3), 6 (1).
- Estensioni: 377 pdf, 6 xlsx, 5 xls, 4 docx, 3 jpg, 2 xlsm, 2 png, 1 doc, 1 ods, 1 pptx.
- `<Etichetta>` è testo libero con ~80 valori distinti. I più frequenti: `Bilancio consuntivo` (85),
  `Mod D - Rendiconto per cassa` (47), `Conto economico` (24), `Stato Patrimoniale e conto economico` (19),
  `Stato Patrimoniale` (19), `Rendiconto per cassa` (18), `Mod A e B - Stato Patrimoniale e Rendiconto
  gestionale` (17), `Bilancio completo - Mod A, Mod B e Relazione di missione` (16),
  `Stato Patrimoniale e Rendiconto gestionale` (13), `Mod B - Rendiconto gestionale` (11),
  `Mod A - Stato Patrimoniale` (10)… più una coda lunga di etichette uniche (es. `Quote associative 2026`,
  `Nota integrativa`, `Verbale revisori`). Le etichette di `CaiDocumentType::getLabel()` compaiono già
  identiche per alcune voci (Mod A/B/D, `Relazione di missione`, `Relazione revisori`, `Relazione attività`,
  `Verbale assemblea`, `Bilancio sociale`, `Relazione al bilancio`, …).
- Discrepanze note (da riportare nel report del comando, NON da correggere a mano):
  - `9216157` (ISEO), `9216158` (TEGLIO), `9248005` (VILLAGRANDE-OGLIASTRA): `Bilanci = RICEVUTO` ma **nessun
    link e nessun file** → restano senza documenti.
  - `9216031`: ha file normalizzati ma `Bilanci = DA VERIFICARE` → si importa comunque, con avviso.
  - `9212045` e `9219008`: presenti nell'Excel ma **non** in `sezioni_cai` → ignorate, con avviso.
  - Toscana: `9226015` e `9226018` sono nella cartella `BILANCI 2025 mancanti` (nessun file) → nulla da importare.
  - 1 gruppo di file con hash sha256 identico (duplicato) fra i 402 file.
- Il datapack `runts-cai.sqlite` ha 529 `sezioni_cai`; `cai_documents.cai_section_id` (FK su
  `cai_sections.codice_cai`) è la colonna giusta per i documenti manuali (vedi `UploadCaiDocumentManually`).

## 2. Goals

- Un comando solo (`cai:build-manual-bilanci-datapack`) rigenera in modo idempotente la tabella
  `bilanci_manuali` nel datapack a partire dalle cartelle normalizzate + Excel di indice, e stampa un report di
  copertura/anomalie leggibile.
- `cai:import-datapack` importa quei documenti come `CaiDocument` manuali (stessa forma di
  `UploadCaiDocumentManually`: `source=Manual`, `cai_section_id` valorizzato, `cai_runts_registration_id` null),
  senza duplicati ai re-run e senza toccare i documenti RUNTS.
- Dopo un import completo locale: **un documento per ogni file normalizzato** (402, meno al più i duplicati
  byte-identici della stessa sezione — vedi US-934) visibili nel tab Allegati delle 345 sezioni.
- Nessun file di bilancio nel repository; `bin/push-cai-datapack` non spedisce su UAT la cartella `originals/`.

## 3. User Stories

### US-930: Parser del nome file normalizzato e mapper etichetta → tipo/titolo/anno
**Description:** As a developer, voglio una classe pura che trasformi il nome di un file normalizzato nei campi
di un `CaiDocument`, così il builder non contiene logica di parsing.

**Acceptance Criteria:**
- [ ] `App\Domain\CaiDirectory\Import\ManualBilancio\ManualBilancioFilenameParser::parse(string $fileName):
      ?ParsedManualBilancioFilename` (DTO `readonly` con `codiceCai`, `sectionLabel`, `label`, `extension`);
      regex `^(\d{7}) - (.+?) - (.+)\.(\w+)$`; restituisce `null` se il nome non combacia (mai eccezione).
- [ ] `ManualBilancioTypeMapper::map(string $label): ManualBilancioClassification` (DTO con
      `CaiDocumentType $type`, `string $title`, `int $year`):
      - `title` = l'etichetta così com'è nel file (prima lettera maiuscola già presente; nessuna riscrittura).
      - `type`: match **esatto** (case-insensitive, spazi normalizzati) con `CaiDocumentType::getLabel()` oppure con
        la tabella di sinonimi nel mapper: `Mod D - Rendiconto per cassa`→`ModD`; `Mod A e B - …`→`ModAB`;
        `Bilancio completo - Mod A, Mod B e Relazione di missione`→`CompletoABC` (anche la variante con doppio
        spazio `Mod A  Mod B`); `Bilancio completo - Mod D e Relazione revisori`→`CompletoDRevisori`;
        `Relazione attività`→`RelazioneAttivita`; `Relazione di missione`→`RelazioneMissione`;
        `Relazione revisori`→`RelazioneRevisori`; `Verbale assemblea`→`VerbaleAssemblea`;
        `Bilancio sociale`→`BilancioSociale`; `Relazione al bilancio`→`RelazioneBilancio`;
        `Bilancio analitico contabile`→`BilancioAnalitico`; `Bilancio riclassificato`→`BilancioRiclassificato`;
        `Bilancio economico e finanziario`→`BilancioEconomicoFinanziario`.
      - **Qualunque altra etichetta → `CaiDocumentType::Altro`** (in particolare `Bilancio consuntivo`,
        `Conto economico`, `Rendiconto per cassa`, `Stato Patrimoniale…` NON si indovinano: restano `Altro` con
        il titolo originale; decisione conservativa, vedi §9).
      - `year`: `2026` se l'etichetta contiene `2026` (es. `Quote associative 2026`), altrimenti `2025`
        (campagna "Bilanci 2025").
- [ ] Test unit Pest con dataset che copre **tutte** le etichette sopra elencate + 5 etichette della coda lunga
      (`Nota integrativa`, `Quote associative 2025`, `Quote associative 2026`, `Verbale revisori`,
      `Bilancio consuntivo (2)`) + nome non conforme (→ `null`).
- [ ] `declare(strict_types=1);` in ogni file nuovo; Pint e Larastan (livello 6, `--memory-limit=1G`) verdi.
- [ ] Tests pass (`php -d memory_limit=1G vendor/bin/pest --filter=ManualBilancio`).

### US-931: Lettore dell'Excel indice della campagna
**Description:** As a developer, voglio leggere `2026_Campagna_Sezioni.xlsx` in una struttura tipizzata per poter
confrontare i file normalizzati con lo stato dichiarato nell'indice.

**Acceptance Criteria:**
- [ ] `CampagnaSezioniIndexReader::read(string $xlsxPath): array<string, CampagnaSezioniRow>` (chiave = `codiceCai`
      a 7 cifre) usando `OpenSpout\Reader\XLSX\Reader` (già disponibile come dipendenza transitiva di Filament —
      **non** aggiungere `maatwebsite/excel` né altri pacchetti, vedi `app/Domain/CaiDirectory/CLAUDE.md`).
- [ ] Legge solo il foglio chiamato `Sezioni`; individua le colonne **per intestazione** (`Nome`, `Regione`,
      `Bilanci`, `Link al bilancio`, `Link al bilancio 2`), mai per indice posizionale; la colonna A (intestazione
      vuota) è il codice. Righe senza codice o con codice non a 7 cifre dopo normalizzazione (`9216157.0` →
      `9216157`) sono scartate.
- [ ] `CampagnaSezioniRow` (readonly): `codiceCai`, `name`, `region` (testo libero come nell'Excel, es.
      `FRIULI-VENEZIA GIULIA`), `statoBilanci` (`?string`), `links` (`list<string>` non vuote).
- [ ] File mancante o foglio `Sezioni` assente → `InvalidArgumentException` con messaggio italiano esplicito.
- [ ] Test Pest che genera un XLSX minimo con OpenSpout `Writer` in un file temporaneo (celle codice sia stringa
      sia numeriche) e verifica: normalizzazione codice, colonne per intestazione, scarto righe senza codice,
      foglio mancante.
- [ ] Tests pass, Pint, Larastan verdi.

### US-932: Scanner delle cartelle normalizzate e builder delle righe `bilanci_manuali`
**Description:** As a developer, voglio un servizio che percorra `normalized/` e produca le righe da scrivere nel
datapack, con le anomalie di copertura già calcolate.

**Acceptance Criteria:**
- [ ] `ManualBilancioDatapackBuilder::build(string $stagingDir, ?array $sectionCodesInDatapack): ManualBilancioBuildResult`
      percorre `<stagingDir>/normalized/<Regione>/<codice - Nome>/<file>` (ordine deterministico: regione, codice,
      nome file) ignorando `.DS_Store` e qualunque file nascosto; usa US-930 per il parsing e US-931 per
      `<stagingDir>/2026_Campagna_Sezioni.xlsx`.
- [ ] Ogni riga (readonly DTO `ManualBilancioRow`): `codiceCai`, `regione` (nome cartella), `anno`, `tipo`
      (`CaiDocumentType->value`), `titolo`, `fileName` (nome file originale normalizzato), `path` **relativo alla
      cartella del datapack** (es. `bilanci-sezioni-2026/normalized/Toscana/9226005 - CAI Carrara/9226005 - CAI
      Carrara - Mod D - Rendiconto per cassa.pdf`), `mimeType` (`finfo`/`mime_content_type`, fallback
      `application/octet-stream`), `size`, `hashSha256`.
- [ ] Il `codiceCai` si ricava dal **nome della cartella sezione** e deve coincidere con quello del nome file; se
      differisce o il nome file non è parsabile → file scartato e registrato come anomalia `nome_non_conforme`.
- [ ] `ManualBilancioBuildResult` espone `rows`, `anomalies` (lista di `{code, codiceCai?, message}`) e contatori.
      Anomalie previste (codici stabili, usati dai test e dal comando):
      `sezione_non_nel_datapack` (codice assente da `$sectionCodesInDatapack` → righe scartate),
      `ricevuto_senza_file` (Excel `RICEVUTO`, nessun file normalizzato — atteso: 9216157, 9216158, 9248005),
      `file_senza_ricevuto` (file normalizzati ma `statoBilanci` ≠ `RICEVUTO` — atteso: 9216031),
      `sezione_non_in_excel`, `codice_excel_non_nel_datapack` (atteso: 9212045, 9219008),
      `hash_duplicato_stessa_sezione` (due file con lo stesso sha256 nella stessa sezione).
      Un'anomalia **non blocca mai** la build (sono avvisi), salvo `nome_non_conforme`/`sezione_non_nel_datapack`
      che scartano la riga.
- [ ] Test Pest su una fixture in `sys_get_temp_dir()` (mini albero con 3 regioni, un file `.DS_Store`, un
      nome non conforme, una sezione non nel datapack, un duplicato) che copre ogni codice anomalia.
- [ ] Tests pass, Pint, Larastan verdi.

### US-933: Comando `cai:build-manual-bilanci-datapack` — aggiorna il datapack
**Description:** As a data manager, voglio lanciare un comando che aggiunge al datapack i bilanci manuali, così
posso rigenerare il datapack e spedirlo su UAT con `bin/push-cai-datapack`.

**Acceptance Criteria:**
- [ ] Comando `cai:build-manual-bilanci-datapack` in `app/Console/Commands/` (sottile: opzioni + delega al builder
      US-932 + scrittura + report; stesso stile di `CaiImportDatapackCommand`).
      Opzioni: `--datapack=cai-datapack/runts-cai.sqlite`, `--staging=cai-datapack/bilanci-sezioni-2026`
      (relativi alla root o assoluti), `--dry-run` (nessuna scrittura, solo report).
- [ ] File datapack o cartella staging mancanti → errore esplicito in italiano (non un errore PDO), exit
      `FAILURE`.
- [ ] Legge i `codice_cai` esistenti da `sezioni_cai` aprendo il file SQLite con una connessione PDO/DB dedicata;
      scrive in **una sola transazione**: `DROP TABLE IF EXISTS bilanci_manuali` + `CREATE TABLE bilanci_manuali`
      + insert di tutte le righe. Schema:
      `id INTEGER PRIMARY KEY, codice_cai TEXT NOT NULL REFERENCES sezioni_cai(codice_cai), regione TEXT NOT NULL,
      anno INTEGER NOT NULL, tipo TEXT NOT NULL, titolo TEXT NOT NULL, filename TEXT NOT NULL, path TEXT NOT NULL,
      mime TEXT, size INTEGER, hash_sha256 TEXT NOT NULL`. **Nessun'altra tabella del datapack viene toccata**
      (test: checksum/conteggio righe di `sezioni_cai`, `enti`, `allegati` invariati).
- [ ] Idempotente: due run consecutive producono la stessa tabella (stesse righe, stesso ordine).
- [ ] Output: tabella riepilogo (regione → n° sezioni, n° file), totali (file, sezioni, dimensione MB) e l'elenco
      delle anomalie raggruppate per codice. Con i dati reali locali il report deve mostrare: 402 file, 345
      sezioni, anomalie `ricevuto_senza_file` = 3, `file_senza_ricevuto` = 1, `codice_excel_non_nel_datapack` = 2.
- [ ] `--dry-run` non modifica il file SQLite (test: `filemtime`/hash invariato).
- [ ] Test Pest feature con datapack SQLite fixture temporaneo (tabella `sezioni_cai` minima) + staging fixture;
      percorsi sempre da opzioni, mai hardcoded.
- [ ] Tests pass, Pint, Larastan verdi.

### US-934: Import dei documenti manuali in `cai:import-datapack`
**Description:** As a developer, voglio che l'import del datapack crei i `CaiDocument` manuali dalla tabella
`bilanci_manuali`, così ogni ambiente li riceve senza passi manuali.

**Acceptance Criteria:**
- [ ] Nuova classe `App\Domain\CaiDirectory\Import\CaiManualBilanciImporter` richiamata da `CaiDatapackImporter::import()`
      **dopo** l'import di sezioni/sottosezioni/registrazioni (le FK sulle sezioni devono esistere); riusa la stessa
      connessione dinamica read-only `cai_datapack` e lo stesso `$datapackDir` già usato per `attachments/`.
      Guarda come lo step "allegati" copia i file sul disco `cai-documents` e segui lo stesso pattern.
- [ ] **Se la tabella `bilanci_manuali` non esiste** (datapack vecchio) l'import non fa nulla, non fallisce e non
      stampa errori (verificato da test).
- [ ] Per ogni riga: se la sezione `codice_cai` non esiste in `cai_sections` → saltata (conteggio `skipped`). Se
      esiste già un `CaiDocument` con `source = Manual`, stessa `cai_section_id` e stesso `hash` → saltato
      (idempotenza). Altrimenti: copia del file da `<datapackDir>/<path>` al disco `cai-documents` come
      `<codice_cai>/<uuid>-<filename>` (stesso schema di `UploadCaiDocumentManually`) e
      `CaiDocument::create([...])` con `cai_section_id`, `cai_runts_registration_id` = null, `document_type`
      (stringa `tipo`), `year`, `title`, `file_path`, `file_name`, `mime_type`, `size`, `hash`,
      `source = CaiDocumentSource::Manual`. **Nessuna** analisi finanziaria accodata di default (vedi US-935).
- [ ] File sorgente mancante su disco → riga saltata con warning nel riepilogo (mai eccezione che ferma gli
      altri documenti: try/catch dentro il loop, mai attorno all'intero batch).
- [ ] `--dry-run` non scrive righe né file ma restituisce gli stessi conteggi.
- [ ] Il riepilogo di `cai:import-datapack` (`CaiImportTableResult`) ottiene una riga `documenti_manuali` con
      created/skipped e mostra l'eventuale warning; il comportamento delle altre tabelle resta invariato
      (i test esistenti `CaiImportDatapackCommandTest` restano verdi senza modifiche, salvo l'aggiunta della nuova
      riga di output se asserita).
- [ ] L'import con `$onlyCaiSectionCode` (usato dai bottoni dashboard) e `$skipSectionFields` **non** importa i
      documenti manuali (stesso scoping: non lanciare questa fase se `$onlyCaiSectionCode !== null`).
- [ ] Un'Action esplicita non serve (nessuna logica in hook Eloquent): l'importer crea i modelli direttamente come
      già fa `CaiDatapackImporter`.
- [ ] Test Pest: fixture datapack con tabella `bilanci_manuali` + file veri su un disco `Storage::fake('cai-documents')`:
      creazione, idempotenza (seconda run = 0 created), sezione sconosciuta, file mancante, dry-run, tabella
      assente, documenti RUNTS preesistenti intatti.
- [ ] Tests pass, Pint, Larastan verdi.

### US-935: Flag opzionale `--analyze-manual` per l'estrazione cifre
**Description:** As a data manager, voglio poter decidere se far partire l'estrazione automatica delle cifre di
bilancio sui documenti manuali importati, senza che parta da sola ad ogni deploy.

**Acceptance Criteria:**
- [ ] `cai:import-datapack --analyze-manual`: dopo l'import, per ogni `CaiDocument` manuale **creato in questa run**
      con `CaiDocumentType::from($document_type)->triggersFinancialAnalysis() === true`
      e `year !== null`, `AnalyzeCaiFinancialStatementDocument::dispatch($id)->onQueue('cai-runts-analysis')`
      (stesso dispatch di `UploadCaiDocumentManually`). Senza il flag **nessun dispatch** (test con `Queue::fake()`
      che asserisce `assertNothingPushed` / `assertPushedOn`).
- [ ] Con `--dry-run` non si accoda nulla neanche col flag.
- [ ] Il valore di `document_type` che non è un case valido di `CaiDocumentType` viene ignorato (nessuna
      eccezione).
- [ ] Documentare il flag nella sezione "Import" di `app/Domain/CaiDirectory/CLAUDE.md` (US-938).
- [ ] Tests pass, Pint, Larastan verdi.

### US-936: `bin/push-cai-datapack` e documentazione di deploy
**Description:** As a developer, voglio che il push del datapack su UAT porti i bilanci normalizzati ma non i file
grezzi inutili, così il deploy resta leggero.

**Acceptance Criteria:**
- [ ] `bin/push-cai-datapack` esclude dal rsync `bilanci-sezioni-2026/originals/` e
      `bilanci-sezioni-2026/2026_Campagna_Sezioni.xlsx` (restano `normalized/` e `runts-cai.sqlite`); il resto del
      comportamento (`--delete`, host/percorso di default) è invariato; il messaggio finale stampa cosa è escluso.
- [ ] Verificato a secco: `rsync -avn --delete ... ` (dry-run, senza host reale: usare una cartella locale di
      destinazione temporanea) mostra `normalized/` incluso e `originals/` escluso.
- [ ] `deploy/CLAUDE.md`: nota breve nella sezione datapack — l'ordine operativo
      (`cai:build-manual-bilanci-datapack` → `bin/push-cai-datapack` → deploy), il fatto che il bind-mount è
      `:ro` (l'import non scrive mai nella cartella datapack: copia **da** lì verso `storage/app/private/cai-documents`).
- [ ] Il target `make setup` non cambia (usa già `cai:import-datapack`, che ora include la nuova fase).
- [ ] Nessun file di bilancio è tracciato da git (`git ls-files cai-datapack` vuoto; `git status` pulito).

### US-937: Run reale locale e verifica in browser
**Description:** As a data manager, voglio la prova su dati veri che i bilanci arrivano nelle schede Sezione.

**Acceptance Criteria:**
- [ ] Prima di scrivere sul DB di sviluppo: `docker compose ps`, `php artisan migrate --pretend` (poi `--force` se
      servono migrazioni pendenti) — gotcha ambientale in `app/Domain/CaiDirectory/CLAUDE.md`. Fare un backup
      del file `cai-datapack/runts-cai.sqlite` (`cp` verso lo scratchpad) **prima** del build reale.
- [ ] `docker compose exec -T app php artisan cai:build-manual-bilanci-datapack` con i percorsi reali: report coerente
      con US-933 (402 file / 345 sezioni / anomalie attese). Poi `cai:import-datapack`: nuova riga
      `documenti_manuali` con **402 created** (o 402 meno il numero di duplicati identici nella stessa sezione,
      dichiarato nel report — verificare con `CaiDocument::query()->where('source','manual')->count()`).
- [ ] Seconda esecuzione di `cai:import-datapack`: `documenti_manuali` created = 0 (idempotenza reale).
- [ ] Spot-check su dati reali: la sezione `9226001` (Firenze) ha 4 documenti (Mod A, Mod B, Relazione attività,
      Relazione di missione) con i tipi attesi; `9226005` (Carrara) ha 1 documento `Mod D`; ogni documento ha
      `file_path` esistente sul disco `cai-documents` e `hash` uguale allo sha256 del file sorgente
      (`shasum -a 256`).
- [ ] Verifica in browser con Playwright (script `.mjs` in `/tmp`, mai nel repo; credenziali via
      `collaudo:ensure-manager-account`; ricordarsi del gotcha `->databaseNotifications()` su Postgres in
      `CLAUDE.md`): aprire la scheda staff della sezione 9226001 → tab "Allegati" mostra i 4 documenti con fonte
      "Caricamento manuale", il download di uno restituisce un file con lo stesso sha256; screenshot di
      controllo letto con il tool Read. Rimuovere ogni utente di verifica creato (`forceDelete()`).
- [ ] Nessun file temporaneo o screenshot lasciato nel repository (`git status` pulito, escluso `cai-datapack/`
      che è ignorato).
- [ ] Verify in browser using dev-browser skill (o Playwright come sopra se la skill non è disponibile).

### US-938: Collaudo, note per gli agenti e progress
**Description:** As a maintainer, voglio che il processo di collaudo e le note di dominio riflettano il nuovo
import.

**Acceptance Criteria:**
- [ ] `docs/collaudo/fase-9.php`: aggiunto un topic "Bilanci Sezioni 2025 nel datapack" con test numerati
      collegati a test automatici **realmente esistenti** (US-930..US-935); `php artisan collaudo:verify-manifest 9`
      passa. Il manuale narrativo `docs/collaudo/09-fase-9.md` riceve un paragrafo breve (non rigenerare il PDF
      in questa story: `pdflatex` manca sull'host locale, vedi `docs/collaudo/CLAUDE.md`).
- [ ] `app/Domain/CaiDirectory/CLAUDE.md`: nuova sezione "Bilanci manuali di campagna nel datapack" con: layout
      della cartella `cai-datapack/bilanci-sezioni-2026/`, comando di build + ordine operativo, regola
      "etichetta sconosciuta → `Altro`", idempotenza per (sezione, hash), niente analisi di default
      (`--analyze-manual`), anomalie attese sul dataset reale, e il gotcha dei codici Excel numerici (`.0`).
- [ ] `docs/project/scripts/ralph/progress.txt` aggiornato con il riepilogo (formato esistente del file).
- [ ] `composer run lint`, `composer run analyse` e i test del dominio (`--filter=Cai`, `--filter=ManualBilancio`)
      verdi; suite intera NON da usare come criterio (gotcha crash preesistente in `CLAUDE.md`).

## 4. Functional Requirements

- FR-1: Il comando `cai:build-manual-bilanci-datapack` legge **solo** `normalized/` e l'Excel; non legge né
  modifica `originals/` e non effettua mai chiamate di rete.
- FR-2: La tabella `bilanci_manuali` è interamente rigenerata ad ogni build (drop + create in transazione);
  nessuna altra tabella del datapack viene modificata.
- FR-3: `path` in `bilanci_manuali` è relativo alla cartella del datapack, mai assoluto (il datapack viene
  spostato su UAT in un percorso diverso).
- FR-4: L'importer non scrive mai nella cartella del datapack (su UAT è montata `:ro`).
- FR-5: Un `CaiDocument` importato ha sempre `source = Manual`, `cai_section_id` valorizzato e
  `cai_runts_registration_id = null` (invariante dei due genitori mutuamente esclusivi).
- FR-6: Chiave di idempotenza: (`cai_section_id`, `hash`, `source = Manual`). Un file con lo stesso contenuto già
  caricato a mano da un utente sulla stessa sezione **non** viene duplicato (comportamento voluto, riportato nel
  conteggio `skipped`).
- FR-7: Nessuna analisi finanziaria parte senza `--analyze-manual`.
- FR-8: Tutti i testi per l'utente (messaggi di errore, report, label) sono in italiano; nomi di classi/metodi
  in inglese come nel resto del dominio.
- FR-9: Nessun valore grezzo di enum su confronti (§4.4 A4): usare `CaiDocumentType`/`CaiDocumentSource`.
- FR-10: Ogni file PHP nuovo inizia con `declare(strict_types=1);`; test in sintassi Pest.

## 5. Non-Goals (Out of Scope)

- Nessun download dai link Typeform (non sono più necessari: i file sono già in locale).
- Nessuna riconciliazione automatica con i bilanci RUNTS né creazione di `CaiFinancialStatement` a mano.
- Nessun upload da UI nuovo (esiste già `UploadCaiDocumentManually`); nessuna modifica ai permessi.
- Nessuna classificazione "intelligente" delle etichette libere oltre alla tabella esplicita di US-930.
- Non si importano le cartelle `BILANCI 2025 row`, `BILANCI 2025 mancanti`, `EMAIL_GR_*.txt`, né i file in
  `originals/` (solo riferimento di audit).
- Nessun gestore di Sottosezioni: i documenti si collegano sempre alla `CaiSection`.
- Nessuna rigenerazione del PDF di collaudo (richiede pdfLaTeX, assente in locale).
- Nessuna modifica al servizio Python `cai-runts-scraper`.

## 6. Design Considerations

- Nessuna nuova UI: i documenti compaiono già nel tab "Allegati" di `CaiSectionInfolist` (staff, cliente
  Sezione, cliente Gruppo Regionale) e si scaricano da `CaiDocumentDownloadController`. Verificare comunque a
  occhio (US-937) che titolo, tipo, anno e fonte siano leggibili.
- Etichette lunghe (fino a ~100 caratteri) devono stare in `cai_documents.title` (verificare lunghezza colonna
  nella migrazione esistente; se `string(255)` basta).

## 7. Technical Considerations

- Namespace: `App\Domain\CaiDirectory\Import\ManualBilancio\*` per parser/mapper/builder/reader; importer
  `App\Domain\CaiDirectory\Import\CaiManualBilanciImporter`; comando in `app/Console/Commands/`
  (convenzione già in uso per `Cai*Command`).
- Percorsi di default via `config('cai_directory.*')` (aggiungere `manual_bilanci.staging_dir` =
  `env('CAI_MANUAL_BILANCI_STAGING_DIR', 'bilanci-sezioni-2026')`, relativo alla cartella del datapack): mai
  default hardcoded nelle classi (stesso pattern di `tax_code_fallback_path` e relativo gotcha test).
- Connessione al SQLite del datapack: riusare il meccanismo già presente in `CaiDatapackImporter`
  (connessione dinamica `cai_datapack`, read-only per l'import; il builder apre una connessione separata in
  scrittura solo nel comando di build).
- Mime: per xlsx/docx/ods/pptx `mime_content_type` può restituire `application/zip`; accettabile (il download usa
  `file_name`), ma preferire un'estensione→mime esplicita per le estensioni note se è una riga di codice.
- Memoria: gli sha256 sono calcolati con `hash_file`, mai caricando i file in memoria (292 MB totali).
- Ambiente Docker: i file stanno in `cai-datapack/` (bind della root repo nel container `app`); se il container
  non vede la cartella, verificare con `docker compose exec -T app ls cai-datapack/bilanci-sezioni-2026`.
- Test: `php -d memory_limit=1G vendor/bin/pest --filter=ManualBilancio` e `--filter=Cai`; in container usare il
  workaround `env -u DB_CONNECTION ...` documentato in `CLAUDE.md` per non distruggere il DB di sviluppo.
- Dimensioni: il datapack passa da ~6 MB a ~300 MB (solo `normalized/`) sul bind-mount UAT.

## 8. Success Metrics

- Import locale completo: 402 file → 402 `CaiDocument` manuali (o meno, con il numero esatto di duplicati
  spiegato nel report), 345 sezioni con almeno un documento.
- Seconda esecuzione: 0 documenti creati.
- Tutti i test dei domini `Cai*`/`ManualBilancio` verdi; Pint e Larastan senza errori.
- `git status` pulito; nessun PDF tracciato.
- Il report di build elenca esattamente le anomalie attese (3 `ricevuto_senza_file`, 1 `file_senza_ricevuto`,
  2 `codice_excel_non_nel_datapack`).

## 9. Open Questions

- **Tipo per etichette generiche** (`Bilancio consuntivo` 85 file, `Conto economico`, `Rendiconto per cassa`,
  `Stato Patrimoniale…`): il PRD le lascia `Altro` (conservativo, titolo originale visibile). Se il committente
  preferisce che `Rendiconto per cassa` valga `Mod D` (e quindi attivi l'estrazione cifre con `--analyze-manual`),
  basta aggiungere la riga alla tabella sinonimi di US-930 — da decidere prima di lanciare con
  `--analyze-manual`.
- **Anno** fisso a 2025 (2026 per le quote associative 2026): confermare che nessun file normalizzato riguardi un
  altro esercizio.
- **Bilanci mancanti**: le 3 sezioni `RICEVUTO` senza file (ISEO, TEGLIO, VILLAGRANDE-OGLIASTRA) vanno
  sollecitate al Gruppo Regionale? Il PRD si limita a segnalarle nel report.
- Il codice `9216031` (`DA VERIFICARE` ma con file) va importato? Il PRD lo importa con avviso.
