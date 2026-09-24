# Sincronizzazione live dati CAI/RUNTS — Design

**Data**: 2026-09-07
**Contesto**: Fase 9 (hardening interattivo, dopo la chiusura di Fase 8 — Integrazione RUNTS-CAI). Richiesta
diretta del committente (product owner): i dati CAI (directory ufficiale) e RUNTS (registro pubblico) devono
essere aggiornati con frequenza almeno mensile, o su richiesta della singola sezione cliccando i bottoni
"Sincronizza dati CAI"/"Sincronizza dati RUNTS" già esistenti (Fase 9, Storie 4/6). Deve essere sempre
evidente la data dell'ultimo aggiornamento. **Il prototipo Python separato `/Users/.../SOFTWARE/RUNTS`, da
cui oggi si prepara manualmente il datapack statico, verrà dismesso**: la capacità di scraping deve essere
assorbita dentro il repo `mstickets`, non dipendere più da quel progetto.

## 1. Cosa cambia rispetto a Fase 8

Fase 8 (design doc `2026-08-28-integrazione-runts-cai-design.md`) importava un **datapack statico**
(`cai-datapack/runts-cai.sqlite`), preparato una tantum a mano da un operatore col prototipo Python, mai
un refresh automatico ("Non obiettivi: refresh automatico/periodico del datapack... Scraper/geocoder Python
restano nel prototipo, non vengono portati/rieseguiti da Orchestrator"). Questa decisione è ora **superata
esplicitamente** da questa storia: serve un refresh reale, periodico e on-demand, e la logica di scraping
deve vivere dentro `mstickets`.

## 2. Due fonti, due pesi molto diversi

Verificato leggendo il codice reale del prototipo (`scraper/cai_main.py`, `scraper/main.py`,
`docs/02-scraper.md`, `openspec/specs/scraper-resilience`) prima di scrivere questo design:

| Fonte | Meccanismo | Peso | Già supporta uno scope singolo? |
|---|---|---|---|
| **CAI** (directory ufficiale) | HTTP GET a un'API JSON WordPress (`cai.it/wp-json/cai-section/v2/sections-list-simple`) | Leggero — nessuna automazione browser, secondi per l'intero dataset nazionale | Sì, l'API stessa restituisce la lista completa; filtrare a una sezione è banale lato Orchestrator |
| **RUNTS** (registro pubblico) | Playwright contro `servizi.lavoro.gov.it`: form di ricerca, paginazione DOM, estrazione dettaglio con retry (backoff 1s/2s, max 3 tentativi), download PDF bilanci + estrazione testo (`pdfplumber`) | Pesante — uno scan nazionale (226 enti) è plausibilmente decine di minuti; **un singolo ente via `--codice-fiscale` è già supportato ed è sotto il minuto** | Sì, già una CLI flag dedicata nel prototipo |

Questa asimmetria guida tutta l'architettura: il percorso CAI può essere sincrono e nativo PHP; il percorso
RUNTS nazionale deve restare un job schedulato in background, mentre il percorso RUNTS per-sezione (quello
dietro il bottone di una sezione) è abbastanza leggero da essere un job in coda con notifica di completamento
(mai bloccante su una richiesta HTTP, ma nemmeno un batch di decine di minuti).

## 3. Architettura

### 3.1 Scraper CAI — porting nativo in PHP

Nessuna automazione browser necessaria: un'`Action` Laravel (`App\Domain\CaiDirectory\Actions\ScrapeCaiSection`
o simile) che chiama l'API JSON via `Illuminate\Support\Facades\Http`, mappa i campi sugli stessi nomi già
usati da `CaiDatapackImporter::importSections()`/`importSubsections()` (stesso schema, stessa logica di
diff/upsert — va **estratta e condivisa**, non duplicata, tra l'import da datapack statico e questo scrape
live: entrambi finiscono per scrivere sulle stesse tabelle con la stessa forma di dati). Rischio basso, non
richiede nuovi servizi Docker.

### 3.2 Scraper RUNTS — nuovo servizio Python interno a `mstickets`

**Non riscritto in PHP** (motivazione: la logica DOM/paginazione/retry del prototipo è già verificata contro
il sito reale; l'estrazione testo dai PDF di bilancio ha già una libreria Python matura, `pdfplumber` — una
riscrittura PHP di entrambe le parti sarebbe sforzo e rischio significativi senza un beneficio immediato).
Il codice Python dello scraper (`scraper/main.py` + moduli di supporto del prototipo) viene **portato
(copiato e adattato)** dentro `mstickets`, in una nuova cartella di primo livello `cai-runts-scraper/`
(sibling di `app/`, `docker/`, ecc. — un secondo linguaggio nel repo, non un secondo repository), con un
sottile wrapper FastAPI che espone due sole rotte HTTP interne:

- `POST /scrape/runts-entity?codice_fiscale=<CF>` — scraping di un solo ente (percorso "bottone sezione").
- `POST /scrape/runts-national` — scan completo (percorso "job mensile"), risposta asincrona (l'endpoint
  avvia il job e ritorna subito; il chiamante non resta in attesa di decine di minuti su una connessione HTTP).

Nuovo servizio Docker `cai-runts-scraper`, **containerizzato per la prima volta per un uso reale** (a
differenza del prototipo, dove Playwright viveva solo nel devcontainer di uno sviluppatore): `Dockerfile`
dedicato con `playwright install chromium --with-deps`, aggiunto sia a `docker-compose.yml` (dev) sia a
`docker-compose.uat.yml` (nuovo servizio + nuova immagine, nuovo step di build/push in
`.github/workflows/deploy-uat.yml`, stesso pattern già in uso per l'immagine `app`). **Questo Chromium è
indipendente e separato** da quello già presente per `chrome-php/chrome` (generazione PDF, US-406): stesso
tipo di dipendenza, due scopi/container diversi, nessuna condivisione.

Orchestrator (PHP) parla con questo servizio **solo via HTTP JSON**, mai import diretto di codice Python.
Questo confine è deliberato: è il punto in cui, se in futuro si deciderà di riscrivere lo scraper RUNTS in
PHP (es. con `chrome-php/chrome`, già presente per i PDF), **nessun'altra parte di Orchestrator dovrà
cambiare** — solo cosa sta dietro quell'endpoint HTTP. Il contratto (rotte, JSON in/out) è la vera interfaccia
stabile, non l'implementazione dietro.

### 3.3 Timestamp "ultimo aggiornamento"

Il prototipo scrive già `sezioni_cai.cai_scraped_at`/`sottosezioni_cai.cai_scraped_at` ed `enti.updated_at`
ma non li mostra mai in UI. Nuove colonne equivalenti in Orchestrator:

- `cai_sections.cai_last_synced_at`, `cai_subsections.cai_last_synced_at` (nullable — `null` finché la riga
  proviene solo dal vecchio import da datapack statico, mai sincronizzata live).
- `cai_runts_registrations.runts_last_synced_at` (stesso principio).

Mostrate nella tab "Dati CAI"/"Dati RUNTS" dell'`Infolist` condiviso (`CaiSectionInfolist`, già usato da
staff/dashboard cliente/dettaglio regionale — un solo punto di modifica copre tutte le superfici), come
"Ultimo aggiornamento: dd/mm/yyyy hh:mm" o "Mai sincronizzato dal vivo" quando `null`.

### 3.4 Bottoni esistenti — cambiano cosa fanno, non l'interfaccia

- **"Sincronizza dati CAI"**: oggi rilancia `CaiDatapackImporter::import(..., onlyCaiSectionCode: ...)` sul
  datapack statico. Passa a chiamare `ScrapeCaiSection` (live, sincrono — resta un'azione Filament normale,
  nessuna coda: la chiamata HTTP all'API CAI è dell'ordine dei secondi).
- **"Sincronizza dati RUNTS"**: oggi rilancia l'import scoped RUNTS dal datapack statico. Passa a
  **accodare un job** (`SyncSingleRuntsEntity`, Horizon) che chiama `POST /scrape/runts-entity` sul nuovo
  servizio; il bottone mostra "sincronizzazione avviata", il job scrive i dati e invia una notifica in-app
  (stesso pattern già usato per E3/E9, `Filament\Notifications\Notification::make()->sendToDatabase()`)
  quando finito. Necessario perché anche un solo ente resta "sotto il minuto" ma non "istantaneo": troppo
  per bloccare una richiesta web, non abbastanza per giustificare l'assenza di un job.

### 3.5 Refresh mensile nazionale

Due nuovi comandi schedulati (`routes/console.php`, stesso pattern di `reports:generate-monthly`):
`cai:sync-national` (chiama l'API CAI per tutte le sezioni, in-process, minuti) e `runts:sync-national`
(chiama `POST /scrape/runts-national` sul servizio scraper, che a sua volta processa i 226 enti internamente
con lo stesso retry/resilienza già presente nel prototipo — Orchestrator non orchestra un job per entità,
delega l'intero batch al servizio scraper). Entrambi dietro un feature flag (`config('orchestrator.features.*')`,
stesso principio già in uso per tutti gli altri comandi schedulati di questo repo), disattivi di default.

## 4. Cosa NON cambia

- Lo schema/import da datapack statico (`CaiDatapackImporter`, `cai:import-datapack`) **resta**: continua a
  essere il modo per popolare un ambiente nuovo/UAT da zero rapidamente (un `v1:import`-like bootstrap),
  senza dover aspettare uno scrape live completo. Il nuovo scrape live è un **refresh incrementale**
  successivo, non un sostituto del bootstrap iniziale.
- `gruppi_regionali_cai`/geocoder — restano fuori scope, invariato da Fase 8.
- Nessun editing dei dati CAI/RUNTS da UI — resta sola consultazione.

## 5. Rischi noti

- **Novità reale**: nessun servizio Python è mai stato deployato da questo repo prima. Il build/push su
  GHCR, il wiring in `docker-compose.uat.yml`, e le risorse (mem/CPU) di un container Playwright su un host
  UAT già condiviso da altri stack sono territorio nuovo — da verificare con un deploy reale, non solo in
  locale, prima di considerare la storia chiusa.
- **Blocchi/rate-limit dal sito RUNTS governativo**: il prototipo non ne documenta di osservati, ma uno
  scrape mensile automatico aumenta la frequenza di interrogazione rispetto all'uso manuale-raro originale —
  da monitorare dopo il primo mese reale, non bloccante per la prima consegna.
- **Estrazione PDF bilanci**: dipende dalla stabilità del layout dei PDF pubblicati da RUNTS — stesso rischio
  già presente e accettato nel prototipo, non nuovo.

## 6. Sequenza di storie proposta

Dato il peso del lavoro, consegna incrementale (numerazione "Storia N" progressiva, come le precedenti di
questa fase):

1. **Scraper CAI nativo PHP** (§3.1) + colonna `cai_last_synced_at` + bottone "Sincronizza dati CAI" live +
   comando mensile `cai:sync-national`. Autonomo, basso rischio, consegnabile e verificabile per intero senza
   toccare Python/Docker.
2. **Nuovo servizio `cai-runts-scraper`** (§3.2): porting del codice Python, `Dockerfile`, wiring in
   `docker-compose.yml` locale, endpoint `POST /scrape/runts-entity` funzionante e verificato manualmente
   (senza ancora collegarlo a Orchestrator).
3. **Wiring lato Orchestrator**: job `SyncSingleRuntsEntity`, colonna `runts_last_synced_at`, bottone
   "Sincronizza dati RUNTS" live, notifica di completamento.
4. **Refresh nazionale**: endpoint `POST /scrape/runts-national` + comando schedulato `runts:sync-national`.
5. **Deploy UAT**: nuova immagine nel workflow CI, nuovo servizio in `docker-compose.uat.yml`, verifica reale
   su UAT (non solo locale).

Le storie 2-5 dipendono l'una dall'altra in sequenza; la storia 1 è indipendente e può partire subito.
