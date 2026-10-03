# PRD: Datapack "snapshot" — tutti i dati CAI/RUNTS scaricati in locale importati dal deploy UAT

> Numerazione story `US-950..US-958`. Branch: `ralph/orchestrator-v2-fase-9`.
> Da leggere prima: `app/Domain/CaiDirectory/CLAUDE.md`, `deploy/CLAUDE.md`, `app/Domain/CLAUDE.md`,
> `docs/collaudo/CLAUDE.md`. Contesto: PRD `prd-bilanci-sezioni-2026-datapack.md` (import documenti manuali).

## 1. Introduzione / Overview

Su UAT **non** gira (né deve girare) lo scraper RUNTS: la policy definitiva di sincronizzazione sarà decisa in
seguito. Per avere comunque su UAT una **versione iniziale completa** dei dati, il datapack
(`cai-datapack/`, gitignored, sincronizzato su msuat da `bin/push-cai-datapack`) deve contenere **tutto ciò che
oggi esiste nel DB locale** per il dominio CAI, e `cai:import-datapack` (già eseguito da
`deploy/remote-deploy.sh` ad ogni deploy dopo `migrate:fresh`) deve importarlo per intero.

Nessun servizio Python, nessun worker di analisi e nessuna modifica a `docker-compose.uat.yml`/workflow di
deploy: UAT riceve i **risultati** (cifre già estratte, stato di analisi, file) già calcolati in locale.

### 1.1 Stato attuale verificato (3 ottobre 2026) — NON rifare l'esplorazione

DB locale (Postgres di sviluppo) vs contenuto attuale del datapack `cai-datapack/runts-cai.sqlite`:

| Dato | DB locale | Datapack attuale |
|---|---|---|
| `cai_sections` / `cai_subsections` | 529 / 224 | 529 / 224 (ma senza stato presenza RUNTS e `cai_last_synced_at`) |
| `cai_runts_registrations` | **222** (219 con `runts_last_synced_at`) | 184 (`enti`) |
| `cai_financial_statements` | **719** (511 per registrazione + **207 per sezione**) | 67 (`bilanci`, senza colonne stato patrimoniale) |
| `cai_board_members` | 0 | 0 |
| `cai_documents` RUNTS | **1999** file, **1,9 GB**, 1981 hash distinti, 674 analizzati | 201 (`allegati`) |
| `cai_documents` manuali | 403 (291 MB, 348 analizzati) | 402 in `bilanci_manuali` (senza esito analisi) |
| `cai_sections.runts_presence_status` | `registered` 209, `timeout` 235, vuoto 85 | non presente |
| `cai_sections.user_id` | 505 collegate a un utente | — (il collegamento si rifà per email) |

Colonne delle 6 tabelle (per lo schema dello snapshot):
- `cai_sections`: codice_cai, name, tax_code, vat_number, email, pec, phone_office, phone, fax, address,
  postal_address, website, office_hours, notices, founded_year, members_count, latitude, longitude, region,
  user_id, cai_last_synced_at, runts_presence_checked_at, runts_presence_status
- `cai_subsections`: cai_codice, cai_section_id, name, email, phone_office, phone, address, website,
  office_hours, notices, founded_year, members_count, latitude, longitude, user_id, cai_last_synced_at
- `cai_runts_registrations`: id_runts, cai_section_id, tax_code, name, legal_form, legal_nature, address,
  street_number, municipality, province, region, postal_code, latitude, longitude, registration_date,
  register_section, activity_sectors, legal_representative, website, pec, official_page_url,
  runts_last_synced_at
- `cai_financial_statements`: cai_runts_registration_id | cai_section_id (mutuamente esclusivi), year, oneri/
  proventi A–E, totali, pre_tax_result, taxes, net_result, total_assets, total_liabilities, net_equity
- `cai_board_members`: cai_runts_registration_id, role, full_name, tax_code, valid_from, valid_to
- `cai_documents`: cai_runts_registration_id | cai_section_id, document_type, year, title, file_path,
  file_name, mime_type, size, hash, financial_analysis_status, raw_text_excerpt, extracted_via_ocr, source
- File: `storage/app/private/cai-documents/<codice_cai|id_runts>/<uuid>-<nome>` (disco `cai-documents`),
  totale 2,1 GB.

**Vincolo di spazio (bloccante se ignorato)**: msuat ha ~4,5 GB liberi su 38 GB (88% usato). L'import copia i file
dal bind-mount `:ro` del datapack verso lo storage del container: servono ~**2× la dimensione dei file**
(~4,4 GB per 2,2 GB di file). Recuperabili sull'host: ~3,1 GB di immagini non usate + ~2,5 GB di build cache
(`docker image prune`/`docker builder prune`, azione manuale su msuat). Le story prevedono: deduplica per hash,
controllo spazio prima del push e prima dell'import.

## 2. Goals

- Un comando locale `cai:export-datapack-snapshot` fotografa **l'intero** stato del dominio CAI del DB locale
  dentro il datapack (tabelle `snap_*` + file), in modo idempotente.
- `cai:import-datapack` ripristina lo snapshot su un DB vuoto (UAT dopo `migrate:fresh`) fino a **parità di
  conteggi** con il locale e rende scaricabili i documenti.
- Nessuna dipendenza da scraper/servizi Python/code di analisi su UAT.
- Lo snapshot è additivo: un datapack senza tabelle `snap_*` continua a funzionare come oggi.
- Il push su UAT non fallisce silenziosamente per mancanza di spazio.

## 3. User Stories

### US-950: Esportazione delle righe — tabelle `snap_*` nel datapack
**Description:** As a data manager, voglio esportare dal DB locale tutte le righe CAI in tabelle `snap_*` del
datapack, per portarle su UAT.

**Acceptance Criteria:**
- [ ] Riferimento obbligatorio: `docs/project/tasks/prd-datapack-snapshot-runts-uat.md` (§1.1, §4, §7).
- [ ] Comando `cai:export-datapack-snapshot` in `app/Console/Commands/` (opzioni `--datapack=
      cai-datapack/runts-cai.sqlite`, `--dry-run`); classe `App\Domain\CaiDirectory\Export\CaiSnapshotExporter`
      (comando sottile: opzioni + delega + riepilogo).
- [ ] Per ciascuna delle 6 tabelle crea `snap_cai_sections`, `snap_cai_subsections`,
      `snap_cai_runts_registrations`, `snap_cai_financial_statements`, `snap_cai_board_members`,
      `snap_cai_documents` (DROP + CREATE + INSERT in **una sola transazione** sul file SQLite; mai toccare
      `sezioni_cai`, `enti`, `bilanci`, `allegati`, `cariche_sociali`, `bilanci_manuali`, `sottosezioni_cai`).
      Colonne = quelle elencate in §1.1 **senza** `id` autoincrementale, **senza** `user_id` (il link utente
      si ricostruisce per email) e con i timestamp `created_at`/`updated_at`.
- [ ] `snap_cai_financial_statements` e `snap_cai_documents` portano entrambe le colonne genitore
      (`cai_runts_registration_id`, `cai_section_id`) nullable; `snap_cai_documents` ha in più
      `snapshot_file` (path relativo del file nel datapack, valorizzato da US-951) e `file_in_datapack` (0/1).
- [ ] Idempotente (due run = stesso contenuto, stesso ordine) e `--dry-run` non scrive nulla (file invariato).
- [ ] Riepilogo con i conteggi per tabella e confronto con il DB (devono coincidere); i valori Eloquent
      castati (enum, bool, date) sono scritti nella forma grezza di colonna, mai nel formato di presentazione.
- [ ] Test Pest con un DB sqlite di test e un datapack fixture temporaneo: ogni tabella, idempotenza,
      `--dry-run`, altre tabelle del datapack intatte.
- [ ] Tests pass, Pint, Larastan verdi.
- [ ] Typecheck passes

### US-951: Esportazione dei file dei documenti (content-addressed) con controllo spazio
**Description:** As a data manager, voglio copiare nel datapack i file dei documenti RUNTS senza duplicati.

**Acceptance Criteria:**
- [ ] Per ogni `CaiDocument` con `source = Runts` copia il file dal disco `cai-documents` in
      `<datapack>/snapshot-files/<hash[0..1]>/<hash>.<ext>` (nome = sha256 del contenuto, **un solo file** anche
      se più documenti hanno lo stesso hash: 1999 righe → ~1981 file); imposta `snapshot_file` e
      `file_in_datapack = 1` nelle righe `snap_cai_documents`. L'hash si **ricalcola** dal file (`hash_file`), non
      ci si fida di `cai_documents.hash`.
- [ ] I documenti `source = Manual` **non** vengono copiati (esistono già in
      `bilanci-sezioni-2026/normalized/` e arrivano da `bilanci_manuali`): righe esportate con
      `file_in_datapack = 0` (servono solo a ripristinare l'esito di analisi, US-955).
- [ ] File mancante sul disco → riga esportata con `file_in_datapack = 0` e avviso nel riepilogo (mai
      eccezione che ferma l'export; try/catch dentro il loop).
- [ ] Idempotente: un file già presente con lo stesso hash non viene ricopiato; stream (`copy`/`fopen`), mai
      `file_get_contents` di interi PDF.
- [ ] Prima di copiare calcola lo spazio necessario e lo spazio libero locale (`disk_free_space`); se manca,
      termina con errore esplicito in italiano **prima** di scrivere.
- [ ] Riepilogo: n° file copiati/già presenti/mancanti, MB totali, n° hash distinti.
- [ ] Test Pest con `Storage::fake('cai-documents')` e un datapack temporaneo: dedup per hash, file mancante,
      manuale non copiato, idempotenza, controllo spazio (mock di funzione di spazio iniettabile).
- [ ] Tests pass, Pint, Larastan verdi.
- [ ] Typecheck passes

### US-952: Import snapshot — sezioni, sottosezioni, registrazioni, cariche, bilanci
**Description:** As a developer, voglio che `cai:import-datapack` ripristini le tabelle `snap_*` non-documento.

**Acceptance Criteria:**
- [ ] Nuova classe `App\Domain\CaiDirectory\Import\CaiSnapshotImporter`, richiamata da
      `CaiDatapackImporter::import()` **dopo** tutti gli step esistenti (sezioni → … → documenti manuali) e solo
      se `snap_cai_sections` esiste (datapack vecchio = nessun effetto, nessun errore) e solo per l'import
      completo (non quando `$onlyCaiSectionCode !== null` o `$skipSectionFields`).
- [ ] Upsert per chiave naturale: `cai_sections` per `codice_cai` (aggiorna **tutte** le colonne snapshot,
      incluso `runts_presence_status`/`runts_presence_checked_at`/`cai_last_synced_at`, **ma mai `user_id`**),
      `cai_subsections` per `cai_codice`, `cai_runts_registrations` per `id_runts`
      (incluso `runts_last_synced_at`), `cai_board_members` per (registrazione, ruolo, nominativo, validità),
      `cai_financial_statements` per (registrazione, anno) **oppure** (sezione, anno) secondo il genitore valorizzato
      (invariante "esattamente uno dei due genitori" rispettata).
- [ ] Il collegamento `user_id` di sezioni/sottosezioni continua a essere ricostruito per email con la logica già
      esistente nell'import legacy (riusarla, non duplicarla); una sezione già collegata dall'import legacy
      non perde il collegamento.
- [ ] Righe il cui genitore non esiste (sezione/registrazione assente) → saltate con conteggio, mai FK violata.
- [ ] `--dry-run` non scrive e riporta gli stessi conteggi; il riepilogo di `cai:import-datapack` ottiene le
      righe `snapshot_sezioni`, `snapshot_registrazioni`, `snapshot_bilanci`, `snapshot_cariche`.
- [ ] Idempotente: seconda esecuzione = 0 create / 0 aggiornate se nulla è cambiato (confronto attributi come
      in `DiffsAttributes` già usato dall'import legacy).
- [ ] Test Pest: DB sqlite vuoto + datapack fixture con `snap_*` → parità di conteggi, idempotenza, sezione
      sconosciuta, bilancio per registrazione e per sezione, `user_id` preservato, datapack senza `snap_*`.
- [ ] Tests pass, Pint, Larastan verdi.
- [ ] Typecheck passes

### US-953: Import snapshot — documenti RUNTS e relativi file
**Description:** As a developer, voglio ripristinare i documenti RUNTS con i file scaricabili.

**Acceptance Criteria:**
- [ ] Per ogni riga `snap_cai_documents` con `source = runts` e `file_in_datapack = 1`: se esiste già un
      `CaiDocument` con stesso genitore (registrazione o sezione), stesso `hash` e stessa `source` (anche creato
      dall'import legacy `allegati`) → **non** duplicarlo, ma allinea `financial_analysis_status`,
      `raw_text_excerpt`, `extracted_via_ocr`; altrimenti copia il file da
      `<datapack>/<snapshot_file>` al disco `cai-documents` come `<id_runts|codice_cai>/<uuid>-<file_name>` e
      crea il documento con tutti i campi (`source = Runts`, mai `Manual`).
- [ ] Invariante genitori mutuamente esclusivi rispettata; genitore mancante → saltato con conteggio.
- [ ] Controllo spazio **prima** di copiare: se `disk_free_space` dello storage < byte da copiare + margine
      (default 10%, configurabile in `config('cai_directory.snapshot.disk_margin_percent')`), l'import
      **non copia nulla** e termina l'import documenti con errore esplicito in italiano (le righe già importate
      restano); le fasi precedenti non vengono annullate.
- [ ] File sorgente mancante → riga saltata con warning, mai eccezione che ferma gli altri (try/catch nel loop).
- [ ] `--dry-run` non scrive né copia ma riporta conteggi e byte previsti.
- [ ] Riga di riepilogo `snapshot_documenti` (created / updated / skipped / MB copiati).
- [ ] Il file scaricato da `CaiDocumentDownloadController` per un documento importato ha lo stesso sha256 del
      sorgente (test con file reale piccolo in `Storage::fake`).
- [ ] Test Pest: creazione, dedup contro un documento legacy, idempotenza, file mancante, spazio insufficiente
      (soglia mockata via config), dry-run.
- [ ] Tests pass, Pint, Larastan verdi.
- [ ] Typecheck passes

### US-954: Import snapshot — esito di analisi dei documenti manuali
**Description:** As a developer, voglio che i documenti manuali importati da `bilanci_manuali` riabbiano lo
stato di analisi già calcolato in locale.

**Acceptance Criteria:**
- [ ] Per ogni riga `snap_cai_documents` con `source = manual`: trova il `CaiDocument` manuale con stessa
      `cai_section_id` e stesso `hash` (creato dallo step `documenti_manuali`) e imposta
      `financial_analysis_status`, `raw_text_excerpt`, `extracted_via_ocr`; mai crea un documento nuovo, mai
      copia file.
- [ ] Nessun documento corrispondente → saltato con conteggio (`snapshot_manuali_senza_match`).
- [ ] Nessun job di analisi viene accodato (nessun `AnalyzeCaiFinancialStatementDocument::dispatch`):
      test con `Queue::fake()` + `assertNothingPushed`.
- [ ] Dopo l'import completo, la pagina "Bilancio 2025" ottiene gli stessi flag di locale: un test di
      integrazione con `CaiSectionFinancialYearQuery::forYear(2025)` su dati fixture snapshot lo verifica
      (file CE/SP presenti, interpretati).
- [ ] Riga di riepilogo `snapshot_manuali` (aggiornati / senza match).
- [ ] Tests pass, Pint, Larastan verdi.
- [ ] Typecheck passes

### US-955: `bin/push-cai-datapack` con controllo spazio remoto e deploy docs
**Description:** As a developer, voglio che il push verso UAT mi avvisi se lo spazio non basta e mi dica cosa
verrà trasferito.

**Acceptance Criteria:**
- [ ] Prima del rsync lo script calcola la dimensione di ciò che verrà trasferito (rsync `--dry-run --stats`) e
      lo spazio libero remoto (`ssh <host> df -B1 --output=avail <percorso>`), stampa entrambi, e **si ferma
      con errore** se `avail < 2.2 × (dimensione dei file nel datapack)` (l'import li copia una seconda volta
      nello storage del container). Opzione `--force` per proseguire comunque.
- [ ] Il resto del comportamento (`--delete`, esclusioni di `bilanci-sezioni-2026/originals/` e dell'Excel,
      host/percorso di default) è invariato; `bash -n` e una prova a secco con destinazione locale temporanea
      (senza host reale) passano.
- [ ] `deploy/CLAUDE.md`: sezione "Snapshot CAI/RUNTS" con l'ordine operativo
      (`cai:build-manual-bilanci-datapack` → `cai:export-datapack-snapshot` → pulizia spazio su msuat se
      serve (`docker image prune -a -f`, `docker builder prune -f`, **azione manuale**) → `bin/push-cai-datapack`
      → deploy), il vincolo di spazio (~2× i file) e il fatto che UAT **non** esegue scraper né analisi.
- [ ] Nessun file di dati tracciato da git (`git ls-files cai-datapack` vuoto).
- [ ] Typecheck passes

### US-956: Verifica round-trip locale su database di prova (senza toccare quello di sviluppo)
**Description:** As a data manager, voglio la prova che export+import ricostruiscono lo stesso stato.

**Acceptance Criteria:**
- [ ] **Mai** `migrate:fresh` sul database di sviluppo (è la sorgente dei dati): creare un database Postgres
      separato (es. `orchestrator_roundtrip` sul servizio `db`) e lanciare lì i comandi con
      `DB_DATABASE=orchestrator_roundtrip` e un disco di destinazione separato (variabile/`config` per il
      percorso del disco `cai-documents`, oppure `Storage::fake` equivalente via env), più un backup del file
      `cai-datapack/runts-cai.sqlite` prima dell'export (copia nello scratchpad).
- [ ] Sul DB di prova: `migrate:fresh` → `RolePermissionSeeder` → `cai:import-datapack` (senza passare dal
      v1:import: gli utenti non servono, i link utente restano null); esito atteso: conteggi **identici** al
      locale per `cai_sections`, `cai_subsections`, `cai_runts_registrations`, `cai_financial_statements`
      (719), `cai_documents` RUNTS (1999) e manuali (403) — con tolleranza dichiarata e spiegata solo per i
      duplicati identici stessa sezione/hash.
- [ ] Parità campo-per-campo su un campione: 20 righe casuali per tabella confrontate fra i due DB (script
      una tantum in `/tmp`, esito riportato in `progress.txt`).
- [ ] 5 file RUNTS e 5 manuali scaricati via `CaiDocumentDownloadController` (o letti dal disco di prova) con
      sha256 uguale al sorgente.
- [ ] Seconda esecuzione di `cai:import-datapack` sul DB di prova: tutte le righe snapshot `created = 0`.
- [ ] Il database di prova e i file temporanei vengono eliminati a fine verifica; il DB di sviluppo ha gli
      stessi conteggi di prima (verificarlo con un `count` prima/dopo).
- [ ] Typecheck passes

### US-957: Collaudo, note per gli agenti e progress
**Description:** As a maintainer, voglio documentazione e collaudo allineati.

**Acceptance Criteria:**
- [ ] `docs/collaudo/fase-9.php`: nuovo argomento "Snapshot CAI/RUNTS nel datapack (export e import)" con casi
      collegati ai test di US-950..US-954; `php artisan collaudo:verify-manifest 9` passa.
- [ ] `docs/collaudo/16-fase-9.md` + file cumulativi (00, 01, 11, 12, README) aggiornati nello stesso modo usato
      per l'argomento precedente (conteggi test/argomenti, righe in matrice e registro, versione 9.2).
- [ ] `app/Domain/CaiDirectory/CLAUDE.md`: sezione "Snapshot CAI/RUNTS nel datapack" (tabelle `snap_*`,
      chiavi naturali, regola "mai `user_id`", dedup per hash, controllo spazio, nessuna analisi accodata,
      ordine dei comandi, gotcha della verifica su DB separato).
- [ ] `progress.txt` aggiornato nel formato esistente.
- [ ] `composer run lint`, `composer run analyse`, `--filter=Cai`, `--filter=Snapshot` verdi (la suite intera NON
      è un criterio: crash preesistente documentato in `CLAUDE.md`).
- [ ] Typecheck passes

## 4. Functional Requirements

- FR-1: Lo snapshot contiene **tutte** le righe delle 6 tabelle CAI e i file dei documenti RUNTS; nessun dato
  utente (`user_id`, password, email utente) entra nel datapack.
- FR-2: Il collegamento utente si ricostruisce solo per email (logica esistente dell'import).
- FR-3: L'import dello snapshot non esegue mai chiamate di rete né accoda job; non avvia scraper né analisi.
- FR-4: Un documento ha sempre `source` coerente (`Runts` per quelli RUNTS, `Manual` per i manuali) e un solo
  genitore.
- FR-5: Idempotenza su chiavi naturali; chiave documenti = (genitore, hash, source).
- FR-6: Se lo spazio non basta, export/push/import si fermano con messaggio esplicito prima di scrivere.
- FR-7: Datapack senza tabelle `snap_*` o senza `bilanci_manuali` continua a funzionare (nessun errore).
- FR-8: Ogni file PHP nuovo ha `declare(strict_types=1);`; test in sintassi Pest; testi utente in italiano;
  nessuna business logic in hook Eloquent; confronti su enum, mai su stringhe grezze.
- FR-9: SQL portabile sqlite (test) / Postgres (sviluppo, UAT).

## 5. Non-Goals (Out of Scope)

- Nessuno scraper RUNTS, servizio Python, worker di analisi o modifica a `docker-compose.uat.yml`/workflow di
  deploy su UAT; nessuna policy di sincronizzazione periodica (decisione rimandata).
- Nessuna disattivazione/nascondimento del bottone "Sincronizza dati RUNTS" su UAT (su UAT non funzionerà: da
  decidere con la policy definitiva).
- Nessuna modifica ai dati locali; il DB di sviluppo è solo sorgente (mai `migrate:fresh` su di esso).
- Nessun upload effettivo su msuat né deploy in questo PRD: il push e il deploy sono passi manuali successivi
  (§7) da fare con l'approvazione dell'utente.
- Nessuna pulizia automatica dei dischi di msuat.

## 6. Design Considerations

- Nessuna nuova UI. Verifica visiva solo tramite pagine già esistenti (scheda sezione → tab Allegati/Bilanci,
  "Bilancio 2025") sul DB locale/di prova.

## 7. Technical Considerations

- File SQLite: scrittura con `PDO` dedicata nel comando di export; l'import apre il file in sola lettura (come
  `CaiDatapackImporter`). Connessione dinamica `cai_datapack` già disponibile.
- Memoria: stream per i file, `chunkById`/cursori per le tabelle (719 bilanci e 1999+403 documenti sono pochi,
  ma `raw_text_excerpt` può essere grande).
- Dimensioni: `snapshot-files/` ≈ 1,9 GB; datapack totale ≈ 2,4 GB (con `bilanci-sezioni-2026/normalized/`).
- Ordine operativo dopo Ralph (manuale, con approvazione): `docker image prune -a -f`/`builder prune` su msuat →
  `bin/push-cai-datapack msuat` → deploy (rerun del workflow o push su develop) → verifica conteggi su UAT via
  `ssh msuat 'docker exec ticket-uat-app-1 php artisan tinker ...'`.
- Test isolati dal DB di sviluppo con `env -u DB_CONNECTION -u DB_DATABASE -u DB_HOST -u DB_PORT -u DB_USERNAME
  -u DB_PASSWORD` nel container (gotcha CRITICO in `CLAUDE.md`).

## 8. Success Metrics

- DB di prova dopo `cai:import-datapack`: conteggi uguali al locale (sezioni 529, sottosezioni 224,
  registrazioni 222, bilanci 719, documenti RUNTS 1999, manuali 403).
- Seconda esecuzione: 0 righe create.
- Su UAT dopo il deploy: stessi conteggi, pagina "Bilancio 2025" con 97 sezioni a conto economico interpretato e
  44 a stato patrimoniale interpretato (come in locale), documenti scaricabili.
- Spazio libero su msuat dopo il deploy > 1 GB.

## 9. Decisioni prese e questioni aperte

- **Documenti**: si portano su **tutti** i 1999 documenti RUNTS (1,9 GB), non solo i bilanci d'esercizio.
- **Bottone "Sincronizza dati RUNTS" su UAT**: resta com'è (darà errore finché non sarà decisa la policy); nessuna
  modifica in questo PRD.
- **Spazio su msuat** (aperta, da risolvere con l'utente fuori da Ralph): l'import copia i file due volte
  (datapack `:ro` + storage del container). Opzioni in valutazione: pulizia di immagini/build cache sull'host,
  nuovo volume Hetzner, potenziamento della macchina. Le story US-950..US-957 non dipendono da questa decisione
  (il controllo spazio fa fallire in modo esplicito e prima di scrivere); il push/deploy su UAT avviene solo dopo.
