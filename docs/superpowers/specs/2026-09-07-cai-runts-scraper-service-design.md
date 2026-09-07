# RUNTS scraper — sincronizzazione live (Storie 2-3) — Design

**Data**: 2026-09-07
**Contesto**: Fase 9, seguito diretto della Storia 1 (scraper CAI nativo PHP, già consegnata e verificata in
locale — vedi `docs/superpowers/plans/2026-09-07-cai-scraper-native-php.md`). Design doc precedente:
`docs/superpowers/specs/2026-09-07-cai-runts-live-sync-design.md` (§6, Storie 2-3, qui dettagliate). Richiesta
diretta del committente: il bottone "Sincronizza dati RUNTS" della dashboard cliente deve (1) scaricare/scrivere
sincronamente i metadati RUNTS, (2) scaricare sincronamente i documenti di bilancio non ancora presenti e
allegarli, (3) mettere l'ANALISI di quei documenti (estrazione cifre finanziarie) in una coda di job dedicata,
asincrona.

## 1. Verificato nel prototipo Python (`RUNTS/scraper/{main,scraper,analyzer,db}.py`)

- Nessun `dataclass`/Pydantic: ogni record scraper (entità, carica sociale, documento) è un semplice `dict`
  Python, sia in memoria sia come argomento delle funzioni di persistenza — nessuna conversione di forma
  necessaria oltre a un mapping diretto a JSON.
- `run_scraper(denominazione, headless, delay_ms, codice_fiscale, attachments_dir)` (async, `scraper.py`) è la
  funzione libreria riusabile per uno scrape di una sola entità — **mai passare dal CLI `main()`**: ha un bug
  reale non ancora corretto nel prototipo (un codice fiscale senza risultati può, a seconda di dove il
  timeout Playwright scatta, tornare un dict vuoto che fa esplodere con `KeyError` il codice di stampa del
  report, oppure propagare fino a un `sys.exit(1)` indistinguibile da un errore fatale generico — nessun
  esito pulito "non trovato" garantito). Il nuovo servizio chiama `run_scraper()` direttamente e gestisce
  esplicitamente i tre esiti possibili (§3.3).
- **Il download degli allegati avviene DENTRO la stessa sessione Playwright della pagina di dettaglio**
  (`_playwright_download_allegati`, `scraper.py:318-437`): RUNTS è ASP.NET WebForms con bottoni-immagine per
  il download, mai un URL diretto (`att["url"]` è sempre `None` nel dict grezzo). Non esiste quindi un modo
  di scaricare un bilancio senza rifare uno scrape Playwright completo — le operazioni 1 (metadati) e 2
  (download bilanci) sono per costruzione la stessa chiamata, non due passi indipendenti componibili.
- Retry già presente in `run_scraper()`: 3 tentativi per entità, backoff `2**attempt` secondi (1s, 2s) —
  riusato as-is, nessuna nuova logica di retry da scrivere per lo scraping.
- `extract_bilancio_pdf(path: str, ocr_fallback: bool = True) -> dict` (`analyzer.py:198-238`): prende un
  **path su disco** (non bytes), estrae testo con pdfplumber e applica una lista di regex per 15 campi
  finanziari (proventi/oneri per categoria A-E, totali, risultato ante imposte, imposte, risultato
  d'esercizio). Ritorna **sempre** un dict di 15 chiavi pre-inizializzate a `None` — anche un PDF illeggibile
  o senza match torna tutto `None`, mai un'eccezione. Nessun bisogno di Playwright/browser: candidato
  naturale per il job in coda separato.
- Il fallback OCR (`_ocr_pdf`, richiede `tesseract`+`pdf2image`+`poppler-utils`) non è dichiarato come
  dipendenza da nessuna parte nemmeno nel prototipo (non nel suo `requirements.txt`, non nel suo devcontainer)
  — **omesso dalla prima consegna** (§6/§7): un bilancio scansionato torna semplicemente tutti i 15 campi
  `None`, degradazione già presente e accettata nel prototipo stesso quando quella libreria manca.
- `.p7m` (firma CAdES) gestito shellando a `openssl cms -verify -noverify` — `openssl` è presente di default
  nelle immagini Debian-based, incluse quelle ufficiali Playwright: nessuna installazione aggiuntiva.
- **Nessun wrapper HTTP riusabile**: la FastAPI già esistente nel prototipo (`web/app.py`) apre il datapack
  SQLite in **sola lettura** e non innesca mai uno scrape — il nuovo servizio va scritto da zero importando
  le funzioni di libreria (`run_scraper`, `extract_bilancio_pdf`), che sono già chiamabili da altro codice
  Python (nessun accoppiamento ad argparse nella logica stessa, solo nel `main()` del CLI).

## 2. Cosa cambia rispetto al design doc precedente

Il design doc di Fase 9 (§3.2) prevedeva il servizio `cai-runts-scraper` con lo stesso confine HTTP JSON;
questo documento lo specifica nel dettaglio, e introduce esplicitamente la separazione sync/async richiesta
dal committente: metadati+documenti sincroni nella richiesta del bottone, analisi finanziaria sempre
asincrona in una coda Horizon dedicata (mai `default`, per non far competere l'estrazione PDF con la posta o
la generazione PDF dei report attività).

## 3. Nuovo servizio `cai-runts-scraper`

Nuova cartella di primo livello nel repo `mstickets` (sibling di `app/`, `docker/` — un secondo linguaggio nel
repo, non un secondo repository, come già stabilito nel design doc precedente).

### 3.1 Dockerfile

Basato su `mcr.microsoft.com/playwright/python:v1.4x.0-jammy` (immagine ufficiale Playwright, Debian-based,
Chromium e tutte le librerie di sistema già installate) invece di ricostruire a mano il lungo elenco
`apt-get` del devcontainer del prototipo — riduce il rischio di build fallita per una dipendenza di sistema
dimenticata (stesso tipo di gap incontrato in Fase 4 per `ext-sockets`/chrome-php, qui evitato scegliendo
un'immagine che lo risolve a monte). `requirements.txt`: `playwright`, `pdfplumber`, `fastapi`, `uvicorn`,
più `httpx`/`pytest` per i test.

### 3.2 Struttura codice (porting, non riscrittura)

```
cai-runts-scraper/
  Dockerfile
  requirements.txt
  app/
    main.py        # FastAPI app, due route
    scraper.py      # portato da RUNTS/scraper/scraper.py (run_scraper + helper), logica invariata
    analyzer.py      # portato da RUNTS/scraper/analyzer.py (extract_bilancio_pdf), logica invariata
  tests/
    test_main.py     # pytest + FastAPI TestClient; run_scraper/extract_bilancio_pdf mockati — nessun
                      # Playwright/RUNTS reale nei test automatici
```

### 3.3 Endpoint 1 — `POST /scrape/runts-entity?codice_fiscale=<CF>`

Chiama `run_scraper(codice_fiscale=cf, denominazione=None, headless=True, delay_ms=500,
attachments_dir=<directory temporanea effimera per-request>)`. Gestisce esplicitamente (fix del bug del
prototipo, §1) i tre esiti:

- **Nessun risultato** (0 righe trovate) → `{"found": false}`, HTTP 200 — un esito legittimo, non un
  fallimento del servizio.
- **Timeout sulla ricerca stessa** (selettore risultati mai apparso, es. CF sintatticamente valido ma
  RUNTS non renderizza nulla) → stesso `{"found": false}` quando distinguibile dal codice, altrimenti un
  errore 502 esplicito — in entrambi i casi il messaggio per l'utente finale lato PHP è lo stesso ("nessuna
  registrazione trovata, verifica il codice fiscale o riprova più tardi").
- **Successo** → risposta con entità, cariche sociali, documenti.

Nessun timeout esplicito lato FastAPI (Playwright gestisce già i propri timeout interni, ordine di ~1-2
minuti nel caso con tutti e 3 i retry); il client PHP userà un timeout HTTP di 150 secondi.

Schema risposta (successo):

```json
{
  "found": true,
  "entity": {
    "id_runts": "...", "codice_fiscale": "...", "denominazione": "...",
    "forma_giuridica": null, "natura_giuridica": null,
    "sede_indirizzo": "...", "sede_civico": "...", "sede_comune": "...",
    "sede_provincia": "...", "sede_regione": "...", "sede_cap": "...",
    "data_iscrizione": "...", "sezione_registro": "...", "settori_attivita": null,
    "rappresentante_legale": "...", "sito_web": "...", "pec": "...", "url_dettaglio": "..."
  },
  "board_members": [
    {"ruolo": "presidente", "nome": "...", "cognome": "...", "codice_fiscale": "...", "valid_from": "...", "valid_to": null}
  ],
  "documents": [
    {
      "codice_pratica": "...", "tipo": "bilancio_esercizio", "anno": 2024,
      "filename": "...", "mime": "application/pdf", "size": 123456, "hash_sha256": "...",
      "content_base64": "...", "skip_reason": null
    }
  ]
}
```

`forma_giuridica`/`natura_giuridica`/`settori_attivita` non risultano effettivamente popolati da
`extract_fields()` nel prototipo (verificato: assenti dal dict reale prodotto dallo scrape) — restano
sempre `null`, coerente con `cai_runts_registrations` già nullable su quelle colonne (nessuna regressione:
il datapack storico le aveva comunque quasi sempre vuote). `content_base64` è **assente/null quando
`skip_reason` è valorizzato** (download fallito o bottone non trovato per quella riga) — un documento con
`skip_reason` non nullo non produce mai un file da allegare lato PHP, solo un record di metadato scartato
(stesso comportamento di `CaiDatapackImporter::importDocuments()` per un file mancante).

### 3.4 Endpoint 2 — `POST /analyze/bilancio`

Multipart upload di un file PDF. Scrive su un path temporaneo effimero (`tempfile`), chiama
`extract_bilancio_pdf(path)`, cancella il temporaneo, ritorna il dict di 15 campi + `raw_text` (troncato,
debug) + `ocr` (sempre `false`, essendo l'OCR fuori scope in questa consegna). Nessuno stato persistito
lato Python: ogni chiamata è indipendente e stateless.

## 4. Lato PHP

### 4.1 Nuova migrazione

`cai_runts_registrations.runts_last_synced_at` (timestamp nullable) — stesso pattern esatto di
`cai_sections.cai_last_synced_at`/`cai_subsections.cai_last_synced_at` (Storia 1).

### 4.2 `App\Domain\CaiDirectory\Support\CaiRuntsScraperClient`

Wrapper HTTP verso il nuovo servizio (stesso ruolo di `CaiApiClient` per la Storia 1, ma verso un servizio
Docker diverso, non l'API pubblica CAI): `scrapeEntity(string $codiceFiscale): array`,
`analyzeBilancio(string $pdfContent): array`. URL base da `config('cai_directory.runts_scraper.base_url')`
(nuovo servizio Docker, es. `http://cai-runts-scraper:8000` in locale — nome host risolto dalla rete Compose
interna, nessuna porta pubblicata verso l'host necessaria).

### 4.3 `App\Domain\CaiDirectory\Actions\SyncCaiRuntsRegistration`

`run(CaiSection $section): array` (forma di ritorno da rifinire in fase di piano, indicativamente un
`CaiImportTableResult`-per-tabella come per la Storia 1):

1. Chiama `CaiRuntsScraperClient::scrapeEntity($section->tax_code)`. Se `found: false`, ritorna un esito
   esplicito ("nessuna registrazione RUNTS trovata per il codice fiscale della sezione"), mai un'eccezione.
2. Upsert `CaiRuntsRegistration` (stesso mapping campo-per-campo di
   `CaiDatapackImporter::importRegistrations()`, da valutare in fase di piano se estrarre un mapper condiviso
   equivalente a `CaiSectionFieldMapper`, coerente col principio già stabilito nella Storia 1 di non
   duplicare la logica di mapping tra fonte statica e fonte live) + `runts_last_synced_at = now()`.
3. Sincronizza le cariche sociali (`CaiBoardMember`), stesso schema di dedup di
   `CaiDatapackImporter::importBoardMembers()` (ruolo + codice fiscale + `valid_from`).
4. Sincronizza i documenti: per ciascuno nella risposta, dedup per `(cai_runts_registration_id, filename)`
   (stesso criterio di `CaiDatapackImporter::importDocuments()`); se nuovo e con `content_base64` presente,
   decodifica e salva come allegato reale sul disco `cai-documents` già esistente, crea la riga
   `CaiDocument` — **raccoglie l'elenco dei documenti NUOVI il cui `tipo` è un tipo di bilancio**
   (`bilancio_esercizio` e varianti già classificate da `classify_codice_pratica()` nel prototipo).
5. Per ciascun documento nuovo di tipo bilancio, dispatcha
   `AnalyzeCaiFinancialStatementDocument::dispatch($document)->onQueue('cai-runts-analysis')`.

Passi 1-4 restano interamente sincroni, dentro la stessa richiesta del bottone Filament (nessuna coda, come
richiesto esplicitamente).

### 4.4 `App\Domain\CaiDirectory\Jobs\AnalyzeCaiFinancialStatementDocument implements ShouldQueue`

- Legge i byte del `CaiDocument` dal disco `cai-documents`, li invia a
  `CaiRuntsScraperClient::analyzeBilancio()`.
- Upsert `CaiFinancialStatement` per `(cai_runts_registration_id, year)` con i 15 campi ritornati — `year`
  è già noto dal metadato del documento (`CaiDocument::year`), non dalla risposta di analisi.
- `->onQueue('cai-runts-analysis')`, tries/backoff standard (indicativamente 3 tentativi). Un fallimento
  (PDF non interpretabile, timeout di rete verso il servizio Python) resta un fallimento Horizon isolato,
  loggato, senza bloccare né il bottone né altre sezioni — stesso principio già stabilito per
  `cai:sync-national` (Storia 1, un errore su una sezione non blocca le altre).

### 4.5 Horizon

Nuovo supervisor in `config/horizon.php` (`defaults` + eventuale override per `local`/`production`),
`'queue' => ['cai-runts-analysis']` — oggi esiste solo `'default'`, questa è la prima coda dedicata del
repo. Timeout più ampio del default (indicativamente 120s, l'estrazione testo/regex su un PDF multi-pagina
può richiedere qualche secondo mai osservato prima in questo progetto), `maxProcesses` contenuto (1-2, non
è un carico alto: al più poche decine di documenti per sezione).

### 4.6 Wiring bottone + Infolist

`CustomerDashboard::syncRuntsDataAction()`: chiama `SyncCaiRuntsRegistration::run($section)` invece di
`CaiDatapackImporter::import(..., skipSectionFields: true)` — stesso principio già applicato al bottone CAI
nella Storia 1 (nome/label/icona/colore/`->visible()` invariati, cambia solo il closure `->action()`).
`CaiSectionInfolist`, tab "Dati RUNTS": mostra `runts_last_synced_at` per ciascuna registrazione elencata,
stesso pattern "Mai sincronizzato dal vivo" già introdotto per `cai_last_synced_at` nella Storia 1.

## 5. Gestione errori (riepilogo)

| Caso | Comportamento |
|---|---|
| Codice fiscale non trovato su RUNTS | Notifica informativa esplicita, mai un errore generico |
| Scrape fallito dopo i retry interni del servizio Python | Notifica danger, stesso pattern di `ScrapeCaiSection` (Storia 1) |
| Job di analisi fallito | Fallimento Horizon standard, loggato, non blocca il bottone né altre sezioni |

## 6. Cosa NON cambia

- Il bottone "Sincronizza dati CAI" (Storia 1) resta invariato.
- L'import da datapack statico (`cai:import-datapack`) resta il meccanismo di bootstrap per un ambiente
  nuovo/UAT — invariato.
- Nessun refresh nazionale RUNTS in questa consegna (Storia 4 del design doc originale, `runts:sync-national`
  + `POST /scrape/runts-national`, resta futura).
- Nessun deploy UAT in questa consegna (Storia 5, resta futura — nuova immagine nel workflow CI, nuovo
  servizio in `docker-compose.uat.yml`, verifica reale su UAT).
- Nessun fallback OCR per bilanci scansionati (§1/§7).

## 7. Rischi noti

- **Fragilità intrinseca dello scraping Playwright contro un sito reale governativo**: già accettato/noto
  dal design doc originale, non nuovo qui.
- **Bug di gestione "non trovato" nel prototipo**: mitigato chiamando `run_scraper()` direttamente (mai il
  CLI `main()`) e gestendo esplicitamente i tre esiti in §3.3 — da verificare con almeno una chiamata reale
  contro RUNTS (un codice fiscale noto esistente e uno inesistente) prima di considerare la storia del
  servizio chiusa, non solo con test automatici che mockano `run_scraper`.
- **Dimensione della risposta base64**: se una sezione ha molti anni di bilanci, la risposta di
  `/scrape/runts-entity` cresce proporzionalmente — accettabile per un singolo click-sezione (poche unità di
  MB nel caso peggiore realistico), da rivalutare se in futuro la Storia 4 (refresh nazionale) chiamasse lo
  stesso endpoint per ~226 enti in sequenza.
- **OCR omesso**: un bilancio scansionato (non testuale) risulterà con tutti i 15 campi finanziari `null` —
  comportamento esplicito e accettato, non un bug silenzioso.
- **Primo servizio Python mai deployato da questo repo**: stesso rischio già segnalato nel design doc
  originale per le Storie 2/5 — verificato qui solo in locale, il deploy UAT resta storia futura separata.
- **Prima coda Horizon dedicata (non `default`) di questo repo**: nessun precedente diretto nel codebase da
  cui copiare la configurazione del supervisor — da verificare con un avvio reale di `php artisan horizon`
  in locale (non solo lettura statica di `config/horizon.php`) prima di considerare la storia chiusa.

## 8. Sequenza di storie proposta

1. **Servizio `cai-runts-scraper`** (§3): porting del codice, Dockerfile, wiring in `docker-compose.yml`
   locale, i due endpoint funzionanti e testati (pytest, mockando `run_scraper`/`extract_bilancio_pdf` — mai
   Playwright/RUNTS reale nei test automatici), verificati manualmente con almeno una chiamata reale contro
   RUNTS per un codice fiscale noto esistente e uno inesistente.
2. **Wiring lato Orchestrator** (§4): migrazione, `CaiRuntsScraperClient`, `SyncCaiRuntsRegistration`,
   `AnalyzeCaiFinancialStatementDocument`, coda Horizon dedicata, wiring bottone, colonna Infolist.

Le due storie sono sequenziali: la 2 dipende da un servizio Python realmente raggiungibile in locale (Docker
Compose) per i propri test di integrazione, anche se i test PHP unitari/feature restano mockati via
`Http::fake()`, mai contro un servizio reale in CI.
