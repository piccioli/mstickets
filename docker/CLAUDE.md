# Docker — Dockerfile applicativi (`docker/php`, `docker/uat`)

Si carica quando lavori sotto `docker/*`. Vedi anche la radice del repo per `docker-compose.yml`
(`name: orchestrator-v2`, profilo `etl`) e `app/Import/CLAUDE.md` per l'uso di `db_legacy`.

## Estensioni PHP mancanti scoperte solo eseguendo davvero dentro il container (US-024)

- **`docker/php/Dockerfile` deve installare l'estensione `exif`** (`docker-php-ext-install ... exif`):
  `spatie/image`/`spatie/laravel-medialibrary` la richiedono e `composer install` fallisce con "it is missing
  from your system" se non c'è. Il gap è passato inosservato da US-002 a US-023 perché `composer install` era
  sempre stato lanciato sull'host (PHP locale, `exif` già abilitata), mai dentro il container `app`:
  qualunque comando che deve girare nel container va verificato eseguendolo davvero nel container, non
  assumendo che "ha funzionato sull'host" basti.
- **`composer install` dentro un container con volume bind-mount su macOS (virtiofs) può fallire in modo non
  deterministico** durante l'estrazione parallela dei pacchetti
  (`RecursiveDirectoryIterator::__construct(...): Failed to open directory`, un pacchetto diverso ogni
  volta) — race innocua tra creazione directory e scrittura file sul filesystem condiviso. Si risolve con un
  retry (i pacchetti già estratti non vengono ri-scaricati): loop `for i in 1 2 3; do composer install &&
  exit 0; done; exit 1` invece di assumere che il primo tentativo basti.

## Chrome/PDF via `chrome-php/chrome` (US-406, §6.4.3) — driver `chrome`, mai `browsershot`

- **`spatie/laravel-pdf` v2 supporta più driver**: questo repo usa `chrome` (`chrome-php/chrome`, Chrome
  DevTools Protocol in puro PHP), non `browsershot` (richiede Node.js+Puppeteer) — nessun secondo runtime
  Node.js a runtime nelle immagini Docker (Node esiste solo come stage di build per gli asset Vite,
  `docker/uat/Dockerfile`, poi scartato). Vedi `README.md` per la motivazione completa (richiesta esplicita
  §6.4.3 del PRD).
- Il binario Chromium va installato con `apk add chromium` in ENTRAMBE le immagini (`docker/php/Dockerfile`
  sviluppo, `docker/uat/Dockerfile` stage finale) — `apk` risolve da solo tutte le librerie condivise
  necessarie. `LARAVEL_PDF_CHROME_BINARY` va lasciato vuoto in `.env.example`: `HeadlessChromium\AutoDiscover`
  cerca da sola `google-chrome`/`chromium-browser`/`chrome`/`chromium` nel `PATH` su Linux e il path standard
  di `Google Chrome.app` su macOS — un percorso hardcoded romperebbe uno dei due ambienti.
- `LARAVEL_PDF_CHROME_NO_SANDBOX=true` è necessario in QUALSIASI container, **non solo quelli che girano come
  root**: verificato che il container `app` di sviluppo (`USER www-data`) fallisce comunque con
  `Failed to move to new namespace... Operation not permitted` senza questo flag, perché il sandbox di Chrome
  richiede di creare user/PID namespace e il runtime container nega di norma quella syscall a prescindere
  dall'utente.
- Nella suite CI (`ubuntu-latest`) serve un passo esplicito `browser-actions/setup-chrome@v1`, non fidarsi
  che l'immagine del runner abbia già Chrome. **Auto-discovery di chrome-php su GitHub Actions fallisce**:
  cerca da solo il binario nel `PATH` ma non trova quello installato da `setup-chrome@v1` — `proc_open`
  riceve un command array vuoto e fallisce con `ValueError: First element must contain a non-empty program
  name`. Fix: passare esplicitamente `LARAVEL_PDF_CHROME_BINARY` in `.env` dall'output (`chrome-path`) dello
  step `Setup Chrome` (serve un `id:` su quello step per leggerne l'output), non fidarsi dell'auto-discovery
  su questo runner.
- **`ext-sockets` mancante sia in `docker/php/Dockerfile` sia in `docker/uat/Dockerfile`**:
  `chrome-php/wrench` (dipendenza di `chrome-php/chrome`, parla col protocollo DevTools via websocket) la
  richiede davvero a runtime. Il gap è passato inosservato in CI perché il runner `ubuntu-latest` di
  `shivammathur/setup-php` ha `ext-sockets` preinstallata di default — stesso pattern "un ambiente più
  permissivo del necessario nasconde un gap reale che un ambiente più minimale (Alpine) espone" già visto per
  bug Postgres-only mai intercettati da sqlite. Il fallimento reale è arrivato al primo deploy UAT: lo stage
  finale di `docker/uat/Dockerfile` fa `composer install --no-dev` **senza** `--ignore-platform-reqs` (a
  differenza dello stage `vendor`), quindi verifica per davvero i requisiti di piattaforma. Aggiunta
  `sockets` a entrambi i Dockerfile e, per non restare "per fortuna del runner", anche esplicitamente
  all'elenco `extensions` di entrambi i job `ci.yml`.
- **Compilare `ext-sockets` su Alpine richiede `linux-headers` nei build-deps**: senza,
  `docker-php-ext-install sockets` fallisce in compilazione con `linux/sock_diag.h: No such file or
  directory`. Qualunque futura estensione PHP aggiunta via `docker-php-ext-install` su Alpine va verificata
  per bisogni di `-dev`/`-headers` aggiuntivi, non assunta compilabile con i soli `${PHPIZE_DEPS}` già
  presenti.

## Pacchetti Alpine per pdfLaTeX (collaudo, v0.3.2 — vedi `docs/collaudo/CLAUDE.md` per i bug applicativi)

- **Pacchetti esatti (verificati con una build reale, non a tentativi)**, aggiunti alla riga esistente
  `apk add --no-cache` del Dockerfile (mai una seconda riga `apk add` separata): `texlive
  texmf-dist-latex texmf-dist-latexrecommended texmf-dist-latexextra texmf-dist-fontsrecommended
  texmf-dist-fontsextra texmf-dist-langitalian texmf-dist-plaingeneric texmf-dist-pictures poppler-utils`.
  `texmf-dist-langitalian` serve per la sillabazione/i pacchetti in italiano; `poppler-utils` fornisce
  `pdftotext`, usato SOLO dai test automatici (mai a runtime dall'applicazione).
- **`pdflatex`/`pdftotext` esistono SOLO nell'immagine Docker di sviluppo (`docker/php/Dockerfile`, `FROM
  php:8.4-fpm-alpine`), mai in `docker/uat/Dockerfile`**: quest'ultimo è una build multi-stage separata
  basata su FrankenPHP, dove `collaudo:generate` (comando manuale, mai nel deploy automatico o in una request
  applicativa) non gira mai — installare ~500MB di TeX Live lì sarebbe puro peso morto. Se emergesse un
  bisogno reale di generarlo anche da UAT, valutarlo esplicitamente allora.
- Verifica minima dopo un `docker compose build app`: `docker compose run --rm app pdflatex --version` e
  `docker compose run --rm app pdftotext -v` devono rispondere con la versione installata.
