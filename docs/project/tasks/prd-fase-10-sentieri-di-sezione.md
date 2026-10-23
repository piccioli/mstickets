# PRD: Fase 10.6 — Sentieri di Sezione

> Fase 10, feature 10.6 (`docs/project/tasks/fase-10-roadmap.md`). Design di riferimento, **salvato nel repository**:
> `docs/project/design/fase-10/sentieri-di-sezione.dc.html` (copia del file Claude Design "Sentieri di Sezione"): stili
> inline e blocco `<script type="text/x-dc">` con dati d'esempio e logica (scenari molti / pochi / nessuno; schede
> percorribile / non percorribile / condiviso; colori degli stati in `SDA` e della percorribilità in `PER`). Sezioni
> "1 · Elenco sentieri" e "2 · Scheda sentiero".
> Fa parte del run Ralph unico 10.4–10.9 e **dipende dalla 10.4** (`prd-fase-10-rifugi-di-sezione.md`: componente
> `x-cai.map`, pattern del datapack e delle pagine). Costruisce la pipeline osm2cai che 10.7, 10.8 e 10.9 estendono.

## 1. Introduzione

La Sezione vede i sentieri che gestisce: riepilogo per stato di accatastamento, mappa con le linee colorate, elenco
filtrabile e scheda del sentiero. **Fonte di verità esterna**: il catasto sentieri CAI **osm2cai** (osm2cai.cai.it), letto
da un **dump PostgreSQL/PostGIS** del suo database.

Fatti verificati sul dump (analisi del progetto `/Users/alessiopiccioli/Documents/LAVORO/MS/SOFTWARE/OSM2CAIReport`):
- File: `/Users/alessiopiccioli/Documents/LAVORO/MS/SOFTWARE/OSM2CAIReport/data/last_dump.sql.gz`, `pg_dump` SQL testuale
  gzip (~1,6 GB, PostgreSQL 16.4, proprietario ruolo `osm2cai2`). **URL di download non ancora disponibile**: per ora lo
  script riceve il percorso del file; il download automatico arriverà in seguito (decisione del committente).
- Import come in OSM2CAIReport `app/bin/import-dump.php`: database **da zero** (DROP/CREATE: il dump crea i propri schemi
  `tiger`/`topology`), ruolo `NOLOGIN` `osm2cai2` creato prima, utente superuser,
  `gunzip -c dump | psql -v ON_ERROR_STOP=1`. Circa 5 minuti. Immagine `postgis/postgis:16-3.4-alpine`.
- **Il dump contiene dati personali** (`users`, `cas_users`, `password_resets`) e 2 milioni di `failed_jobs`: **non va mai
  importato nel DB dell'applicazione**. Si carica in un database PostGIS temporaneo, si estraggono le sole tabelle/colonne
  necessarie nel datapack, poi il database temporaneo si elimina.
- `hiking_routes` (27.076 righe): `id`, `osmid`, `osmfeatures_id`, `name`, `osm2cai_status` (0–4), `validation_date`,
  `issues_status` (es. "percorribile", "sconosciuto"), `issues_last_update`, `deleted_on_osm`, `is_geometry_correct`,
  `parent_hiking_route_id`, `geometry geography(MultiLineStringZ,4326)`, `osmfeatures_data jsonb` con in `properties`:
  `ref`, `from`, `to`, `cai_scale` (T/E/EE/EEA, presenza da verificare), `distance`, `ascent`, `descent`, `operator`.
- Legame con le Sezioni: `hiking_route_club (hiking_route_id, club_id)` → `clubs.cai_code`, **stesso codice a 7 cifre di
  `cai_sections.codice_cai`**. Un sentiero può avere più Sezioni.
- Km: `ST_Length(geometry)` sulla `geography` (metri). Il report considera solo `osm2cai_status IN (1,2,3,4)`.
- Nomi degli stati di accatastamento (committente): **1 · Difficoltà, 2 · Ricognizione, 3 · Pre-validato, 4 · Validato**.

## 2. Obiettivi

- Uno script di aggiornamento porta i sentieri dal dump osm2cai al datapack; il deploy li importa nel DB.
- La Sezione vede riepilogo, mappa, elenco e schede dei suoi sentieri come nel design.
- Il badge "Sentieri" (km) della Dashboard Sezione mostra un numero reale.

## 3. User story

### US-1051: Estrazione dal dump osm2cai nel datapack
**Descrizione:** come gestore dei dati, voglio uno script che dal dump osm2cai produca le tabelle dei sentieri nel datapack,
senza mai portare il dump nel DB dell'applicazione.

**Criteri di accettazione:**
- [ ] Script `bin/update-osm2cai-datapack <percorso-dump.sql.gz> [--datapack=cai-datapack/runts-cai.sqlite] [--keep-db]`:
      avvia un container PostGIS temporaneo (`postgis/postgis:16-3.4-alpine`, nome univoco, **mai** i servizi di
      `docker-compose.yml`, nessuna porta pubblicata se non serve), crea il ruolo `osm2cai2`, importa il dump, esegue le
      query di estrazione, scrive nel datapack, **rimuove il container** anche in caso di errore (`trap`), salvo `--keep-db`.
      Controlla lo spazio disco prima di partire (servono ~10 GB liberi) e si ferma con un messaggio chiaro se manca.
- [ ] Estrazione in un file SQL versionato (`database/osm2cai/extract.sql` o simile) e scrittura nel datapack con un
      comando artisan (`cai:import-osm2cai-extract <cartella-csv>` o equivalente) in **una transazione** sul file SQLite
      (DROP+CREATE+INSERT delle sole tabelle `snap_osm2cai_*`, mai toccare le altre).
- [ ] Tabella `snap_osm2cai_trails`: sentieri con `osm2cai_status IN (1,2,3,4)` e `deleted_on_osm` falso: `id`, `osmid`,
      `ref`, `name`, `from_place`, `to_place`, `cai_scale`, `length_km` (da `ST_Length` sulla geography / 1000, 3
      decimali), `ascent`, `descent`, `status` (1–4), `issues_status`, `issues_last_update`, `validation_date`,
      `geometry_geojson` (semplificata con `ST_SimplifyPreserveTopology` a una tolleranza che mantenga la forma a zoom 16 e
      riduca il peso; 2D; 6 decimali).
- [ ] Tabella `snap_osm2cai_trail_sections`: (`trail_id`, `section_code` = `clubs.cai_code`) per i sentieri estratti.
- [ ] Riepilogo a fine script: sentieri estratti, collegati ad almeno una Sezione, codici di Sezione non presenti in
      `cai_sections`, peso del datapack.
- [ ] Documentazione in `deploy/CLAUDE.md` e `app/Domain/CaiDirectory/CLAUDE.md`: script → `cai:import-datapack` in locale →
      `bin/push-cai-datapack` → deploy UAT; il dump non entra mai nel DB dell'app né in git.
- [ ] Test Pest del comando di scrittura nel datapack con file CSV fixture (transazione, altre tabelle intatte); lo script
      bash verificato con `bash -n` ed **eseguito davvero** sul dump reale in locale a fine storia (conteggi nel progress).
- [ ] Lint e analyse puliti.

### US-1052: Tabelle dei sentieri e import dal datapack
**Descrizione:** come sviluppatore, voglio i sentieri in tabelle locali importate dal datapack.

**Criteri di accettazione:**
- [ ] Migrazioni `cai_trails` (PK `id` = id osm2cai, colonne di `snap_osm2cai_trails`, `geometry` come json GeoJSON,
      indici su `status`, `ref`) e `cai_trail_section` (`cai_trail_id`, `section_code` stringa 7 senza FK, PK composta,
      indice su `section_code`).
- [ ] Modello `CaiTrail` con cast, enum backed `CaiTrailStatus` (1–4, label "1 · Difficoltà"… e colori del design: 1
      `#e8b500`, 2 `#f07c22`, 3 `#a24cc7`, 4 `#2563c9`) ed enum `CaiTrailPracticability` (percorribile, percorribile con
      difficoltà, non percorribile, sconosciuto) mappato da `issues_status` con regola testata (valori non riconosciuti →
      sconosciuto); relazione `CaiSection::trails()`; Policy di sola lettura.
- [ ] `cai:import-datapack` importa `snap_osm2cai_*` (righe di riepilogo `snapshot_sentieri`, `snapshot_sentieri_sezioni`):
      crea, aggiorna, elimina i sentieri non più presenti, idempotente, `--dry-run`. Assenza delle tabelle nel datapack →
      passo saltato con avviso (datapack vecchi restano validi).
- [ ] Test Pest con datapack fixture: import, aggiornamento, eliminazione, idempotenza, dry-run, tabelle assenti,
      mappatura della percorribilità.
- [ ] Lint e analyse puliti.

### US-1053: Linee sulla mappa CAI
**Descrizione:** come sviluppatore, voglio disegnare tracciati sul componente mappa.

**Criteri di accettazione:**
- [ ] `x-cai.map` disegna le linee GeoJSON (predisposte nella 10.4) con colore per linea, spessore e stato
      selezionato/hover come nel design (linea evidenziata più spessa, le altre attenuate); clic sulla linea → anteprima
      (numero, nome, km, stato) con "Apri scheda"; sincronizzazione con l'elenco come per i marker.
- [ ] Prestazioni: fino a 300 linee semplificate senza blocchi percepibili (verificato con la SAT).
- [ ] Test del rendering (JSON delle linee nel markup); i test della 10.4 restano verdi.
- [ ] Lint e analyse puliti.

### US-1054: Pagina "Sentieri di Sezione"
**Descrizione:** come utente Sezione, voglio vedere i miei sentieri con riepilogo, mappa ed elenco.

**Criteri di accettazione:**
- [ ] `SectionTrails` smette di essere "in arrivo" e diventa la pagina reale (stesso slug), come nel design §1.
- [ ] Riepilogo: km totali, numero di sentieri, barra dei km per stato 1–4 con legenda (colori `CaiTrailStatus`), km
      percorribili / non percorribili.
- [ ] Mappa con le linee colorate per stato; elenco desktop a tabella e mobile a card (primi 12 + "Mostra altri N"):
      numero (`ref`, "—" se assente), nome con partenza → arrivo, km (1 decimale), difficoltà, pillola stato, pillola
      percorribilità, nota "condiviso con …" (nomi delle altre Sezioni, abbreviati come nel design).
- [ ] Filtri per stato (chip multipli), difficoltà, percorribilità; ordinamento per numero (ordinamento naturale:
      "2" prima di "10"), km, stato; stato in `#[Url]`.
- [ ] Stati: molti (SAT ~150), pochi, nessuno ("Nessun sentiero associato a questa Sezione nel catasto osm2cai"), filtri
      senza risultati; utente di Sottosezione o senza Sezione: stato vuoto senza errori.
- [ ] Test Livewire: riepilogo, filtri, ordinamenti, nota condiviso, stati vuoti, 403 per non-Sezione.
- [ ] Verifica in browser (utente di prova poi cancellato): SAT e una Sezione con pochi sentieri, desktop e mobile.
- [ ] Lint e analyse puliti.

### US-1055: Scheda del sentiero
**Descrizione:** come utente Sezione o GR, voglio la scheda di un sentiero.

**Criteri di accettazione:**
- [ ] Pagina `CaiTrailDetail` con slug `sentieri/{id}`, fuori navigazione, come nel design §2: testata (numero, nome,
      partenza → arrivo, pillole), avviso se non percorribile o percorribile con difficoltà, mappa del tracciato, dati (km,
      dislivelli, difficoltà, data di validazione, aggiornamento della percorribilità, Sezioni che lo gestiscono), link "Vedi
      su osm2cai" (`https://osm2cai.cai.it/hiking-route/id/{id}`) e "Vedi su OpenStreetMap"
      (`https://www.openstreetmap.org/relation/{osmid}`).
- [ ] Accesso: customer Sezione fra quelle del sentiero, customer GR il cui prefisso contiene almeno una delle Sezioni,
      staff con `CaiDirectoryView`; altrimenti 403 (anche con id manipolato).
- [ ] Link di ritorno all'elenco di provenienza (Sezione, o GR con `?da=gr` per la 10.7) conservando i filtri.
- [ ] Test: contenuti, avvisi, link, autorizzazioni.
- [ ] Lint e analyse puliti.

### US-1056: Badge "Sentieri" della Dashboard Sezione
**Criteri di accettazione:**
- [ ] `CaiSectionDashboardMetrics::forSection()` valorizza `trailsKm` con la somma di `length_km` dei sentieri della
      Sezione (un sentiero condiviso conta per intero). Nessun sentiero → "0,0 km" (dato noto). Sottosezione → `null`.
- [ ] Test aggiornati e nuovi.
- [ ] Lint e analyse puliti.

### US-1057: Collaudo, note per gli agenti e roadmap (10.6)
**Criteri di accettazione:**
- [ ] `docs/collaudo/fase-10.php`: argomento "Sentieri di Sezione (10.6)" con ID successivi all'ultimo esistente, collegati
      ai test reali di US-1051..US-1056; `collaudo:verify-manifest 10` passa.
- [ ] Test end-to-end: utente Sezione → dashboard (km) → elenco → scheda; un'altra Sezione → 403 sulla scheda.
- [ ] Roadmap riga 10.6; `app/Domain/CaiDirectory/CLAUDE.md`: fonte, script, tabelle, regola dei sentieri condivisi,
      nomi e colori degli stati.
- [ ] Lint, analyse e test toccati verdi.

## 4. Requisiti funzionali

- FR-1: i sentieri vengono solo dal dump osm2cai tramite `bin/update-osm2cai-datapack` → datapack → `cai:import-datapack`.
- FR-2: un sentiero appartiene a ogni Sezione il cui `codice_cai` compare fra i suoi club osm2cai.
- FR-3: solo sentieri con stato 1–4 e non eliminati su OSM.
- FR-4: la pagina della Sezione mostra riepilogo per stato, mappa, elenco filtrabile; la scheda mostra il dettaglio.
- FR-5: il badge "Sentieri" somma i km dei sentieri della Sezione.

## 5. Fuori scope

- Download automatico del dump (in attesa dell'URL).
- Modifica dei sentieri (la fonte resta esterna).
- Statistiche territoriali per regione/provincia/settore (10.7) e tappe SICAI (10.8/10.9).

## 6. Design

Vincolante `docs/project/design/fase-10/sentieri-di-sezione.dc.html` (stili, testi, logica degli stati, colori `SDA` e `PER`).

## 7. Considerazioni tecniche

- Lo script richiede Docker in locale e ~10 GB liberi; su UAT arriva solo il datapack.
- Il peso delle geometrie nel datapack va controllato (semplificazione); `bin/push-cai-datapack` ha già il controllo di
  spazio.
- Verifiche in browser solo con utenti di prova creati e poi cancellati.
