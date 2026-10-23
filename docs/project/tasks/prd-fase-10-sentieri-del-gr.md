# PRD: Fase 10.7 — Sentieri del GR

> Fase 10, feature 10.7 (`docs/project/tasks/fase-10-roadmap.md`). Design di riferimento, **salvato nel repository**:
> `docs/project/design/fase-10/sentieri-del-gr.dc.html` (copia del file Claude Design "Sentieri del GR") con i suoi dati
> d'esempio `docs/project/design/fase-10/data/gr-sentieri-geo.js` (confini di province fittizi, solo per il mockup).
> Blocchi: "Riepilogo regionale", "Km per stato di accatastamento", "Percorribilità", "Mappa", "Per provincia" (con
> dettaglio dei settori), "Per Sezione", "Elenco sentieri del GR"; scenari regione grande / piccola; stati "settore senza
> sentieri" e "Sezione senza sentieri".
> **Dipende dalla 10.6** (`prd-fase-10-sentieri-di-sezione.md`: script `bin/update-osm2cai-datapack`, tabelle `cai_trails` /
> `cai_trail_section`, linee su `x-cai.map`, scheda `CaiTrailDetail`) e dalla 10.4 (componente mappa). Run Ralph unico.

## 1. Introduzione

Il GR vede i sentieri del suo territorio, come fa il report regionale di OSM2CAIReport ma dentro la piattaforma: due totali
di km ben distinti, km per stato di accatastamento, percorribilità, mappa per settori che diventa mappa dei sentieri
zoomando, tabelle per provincia (con i settori) e per Sezione, elenco cercabile dei sentieri.

Regole di calcolo (da OSM2CAIReport, `app/src/Analysis/*`, e decisioni del committente):
- **Km nel territorio regionale**: per ogni sentiero con stato 1–4, la lunghezza della sua intersezione con il poligono
  della regione (`ST_Length(ST_Intersection(hr.geometry::geometry, r.geometry)::geography)`), sommata per stato. Comprende
  anche sentieri senza Sezione. Stesso calcolo per provincia e per settore.
- **Km gestiti dalle Sezioni del GR**: somma di `length_km` dei sentieri collegati ad almeno una Sezione del GR (regola del
  prefisso della 10.2), **ogni sentiero una sola volta** anche se condiviso.
- **Sentieri presenti** in un territorio: almeno il 50% della lunghezza ricade nel territorio (come
  `RoutesPresentByRegionAnalysis`). Numero di sentieri, percorribilità ed elenco del GR si basano sui sentieri presenti.
- **Per Sezione**: km e numero dei sentieri di ciascuna Sezione; un sentiero condiviso conta per ogni Sezione (la somma
  supera i km gestiti dal GR: va detto in pagina, come nel design).
- `regions` del dump contiene anche territori esteri: si usano solo le 20 regioni italiane, abbinate a
  `App\Domain\Identity\Enums\Region` per nome (Trentino-Alto Adige / Friuli-Venezia Giulia ecc. normalizzati e testati).
- Stati: 1 · Difficoltà, 2 · Ricognizione, 3 · Pre-validato, 4 · Validato. "Consolidati" = stati 3 e 4.
- Il dump (tabelle `regions`, `provinces` con `region_id`, `areas` con `province_id`, `sectors` con `area_id`, `code`,
  `full_code`, `human_name`, geometrie `geometry(…,4326)`; `hiking_route_region` / `hiking_route_province` /
  `hiking_route_sector` con `percentage`) va letto solo nel database temporaneo dello script della 10.6.
- Prestazioni: le intersezioni geometriche richiedono minuti sul DB temporaneo (cast `::geometry` che esclude l'indice):
  vanno calcolate **una volta nello script**, mai nell'applicazione.

## 2. Obiettivi

- Il GR vede i due totali di km, la qualità del catasto (stati 3–4) e dove mancano sentieri (settori vuoti).
- Pagina leggibile anche con 4.000 sentieri (mappa per settori, elenco paginato).
- Badge "Sentieri" della Dashboard GR con un numero reale.

## 3. User story

### US-1061: Estrazione dei dati territoriali dal dump
**Descrizione:** come gestore dei dati, voglio che lo script osm2cai produca anche le statistiche per regione, provincia e
settore, calcolate una volta sola.

**Criteri di accettazione:**
- [ ] `bin/update-osm2cai-datapack` (10.6) estrae in più, nello stesso passaggio e nella stessa transazione sul datapack:
  - `snap_osm2cai_regions` (solo le 20 regioni italiane: `id`, `name`, `region_key` = valore dell'enum `Region`);
  - `snap_osm2cai_provinces` (`id`, `region_id`, `name`, `code`);
  - `snap_osm2cai_sectors` (`id`, `province_id`, `code`, `full_code`, `name` = `human_name`, `geometry_geojson`
    semplificata 2D);
  - `snap_osm2cai_territory_km` (`territory_type` regione/provincia/settore, `territory_id`, `status` 1–4, `km`
    intersecati, `present_routes` = sentieri con almeno il 50% nel territorio): le query di OSM2CAIReport adattate;
  - `snap_osm2cai_trail_territory` (`trail_id`, `region_id`, `province_id`, `sector_id`, `percentage`) dalle tabelle
    `hiking_route_*` con `percentage`.
- [ ] Il riepilogo dello script stampa per ogni regione i km nel territorio e i sentieri presenti, confrontabili con i JSON
      di OSM2CAIReport (`output/Italia/json/riepilogo_regioni.json`): stessa regione, stessi numeri entro l'arrotondamento.
- [ ] Test del comando di scrittura con CSV fixture; esecuzione reale sul dump in locale a fine storia (tempi e conteggi
      nel progress).
- [ ] Lint e analyse puliti.

### US-1062: Tabelle territoriali e import
**Criteri di accettazione:**
- [ ] Migrazioni e modelli: `cai_trail_regions`, `cai_trail_provinces`, `cai_trail_sectors` (con `geometry` GeoJSON),
      `cai_trail_territory_km`, `cai_trail_territory` (pivot sentiero–territorio con percentuale). Policy di sola lettura.
- [ ] `cai:import-datapack` le importa (riga `snapshot_territori_sentieri`), sostituendo il contenuto in modo idempotente;
      tabelle assenti nel datapack → passo saltato con avviso.
- [ ] Test con datapack fixture.
- [ ] Lint e analyse puliti.

### US-1063: Dati della pagina "Sentieri del GR"
**Descrizione:** come sviluppatore, voglio una classe di sola lettura che fornisca tutti i numeri della pagina.

**Criteri di accettazione:**
- [ ] Classe `App\Domain\CaiDirectory\Queries\CaiRegionalGroupTrails` per una `Region`: km nel territorio regionale (totale
      e per stato), km gestiti dalle Sezioni del GR (deduplicati), sentieri presenti, km per stato con percentuale
      consolidati (3–4), km per percorribilità (sui sentieri presenti), righe per provincia (km per stato, totale, settori
      con km e numero di sentieri, settori senza sentieri), righe per Sezione del GR (km, numero, km per stato; Sezioni
      senza sentieri incluse a zero), elenco paginato dei sentieri presenti con filtri (provincia, settore, Sezione, stato,
      percorribilità) e ricerca per numero o nome.
- [ ] Nessun N+1; l'elenco è paginato in SQL (non in memoria).
- [ ] Test Pest con dati fixture: due totali diversi (sentiero senza Sezione, sentiero condiviso), deduplica, presenti al
      50%, settori vuoti, Sezione senza sentieri, filtri e ricerca.
- [ ] Lint e analyse puliti.

### US-1064: Mappa per settori e sentieri visibili
**Descrizione:** come utente GR, voglio una mappa leggibile per una regione con migliaia di sentieri.

**Criteri di accettazione:**
- [ ] Come nel design: sotto lo zoom 11 la mappa mostra i **settori** colorati per percentuale di km consolidati (scala del
      design), con clic sul settore → riquadro con nome, codice, km, sentieri e percentuale; da zoom 11 mostra le **linee
      dei sentieri** dell'area visibile colorate per stato, con clic → anteprima e scheda. Etichette e pulsanti di modo come
      nel design ("Vista per settori · zooma per i sentieri", "N sentieri in vista", "Mostra i sentieri" / "Vista per
      settori").
- [ ] Le linee si caricano per area visibile da un endpoint autenticato JSON (rotta dedicata o azione Livewire) che
      restituisce al massimo N sentieri del GR nel riquadro (limite e messaggio se superato); autorizzazione = GR della
      regione.
- [ ] `x-cai.map` estesa con poligoni colorati e caricamento per area visibile, senza rompere 10.4/10.5/10.6.
- [ ] Test dell'endpoint (riquadro, limite, autorizzazione) e del rendering; verifica in browser con la Lombardia.
- [ ] Lint e analyse puliti.

### US-1065: Pagina "Sentieri del GR"
**Criteri di accettazione:**
- [ ] `RegionalGroupTrails` smette di essere "in arrivo" e diventa la pagina reale (stesso slug), con i blocchi del design:
      riepilogo con i **due totali** spiegati (testi del design), numero di sentieri presenti, barra per stato con
      percentuale consolidati, percorribilità, mappa (US-1064), "Per provincia" (tabella km per stato e totale; clic apre i
      settori, settori senza sentieri evidenziati), "Per Sezione" (con la nota sulla somma che supera il totale), "Elenco
      sentieri del GR" (ricerca, filtri, paginazione).
- [ ] Clic su un sentiero → `CaiTrailDetail` con `?da=gr` (ritorno all'elenco del GR con i filtri). Clic su una Sezione →
      elenco dei sentieri di quella Sezione **visto dal GR**: nuova pagina `RegionalGroupSectionTrails`
      (`gr/sentieri/sezioni/{codice}`) che riusa il componente/la vista della 10.6, accessibile solo al GR il cui prefisso
      contiene la Sezione.
- [ ] Mobile come nel design.
- [ ] Stati: regione grande (Lombardia), piccola (Molise), settore senza sentieri, Sezione senza sentieri, GR senza regione.
- [ ] Filtri, ricerca, pagina e provincia aperta in `#[Url]`.
- [ ] Test Livewire: riepilogo, tabelle, apertura provincia, elenco con filtri e ricerca, link, 403 per non-GR e per la
      pagina Sezione di un altro GR.
- [ ] Verifica in browser (utente GR di prova poi cancellato): Lombardia e Molise, desktop e mobile.
- [ ] Lint e analyse puliti.

### US-1066: Badge "Sentieri" della Dashboard GR
**Criteri di accettazione:**
- [ ] `CaiRegionalGroupDashboardMetrics` valorizza `trailsKm` con i **km nel territorio regionale** e la nota "nel
      territorio regionale" (badge semplice). GR senza regione → `null`. Regione senza dati → "0,0 km".
- [ ] Test aggiornati e nuovi.
- [ ] Lint e analyse puliti.

### US-1067: Collaudo, note per gli agenti e roadmap (10.7)
**Criteri di accettazione:**
- [ ] `docs/collaudo/fase-10.php`: argomento "Sentieri del GR (10.7)" con ID successivi all'ultimo esistente, collegati ai
      test reali di US-1061..US-1066; `collaudo:verify-manifest 10` passa.
- [ ] Test end-to-end: utente GR → dashboard (km) → pagina → provincia → settore → Sezione → sentiero; GR di un'altra
      regione → 403 sulla pagina Sezione.
- [ ] Roadmap riga 10.7; `app/Domain/CaiDirectory/CLAUDE.md`: due totali, sentieri presenti al 50%, deduplica, abbinamento
      regioni osm2cai ↔ enum.
- [ ] Lint, analyse e test toccati verdi.

## 4. Requisiti funzionali

- FR-1: le statistiche territoriali si calcolano solo nello script, sul DB temporaneo del dump.
- FR-2: la pagina mostra due totali di km distinti e spiegati.
- FR-3: la mappa mostra i settori agli zoom bassi e i sentieri visibili da zoom 11.
- FR-4: tabelle per provincia (con settori) e per Sezione; elenco cercabile e filtrabile.
- FR-5: dal GR si aprono la scheda del sentiero e l'elenco dei sentieri di una Sezione del GR.

## 5. Fuori scope

- Download automatico del dump.
- Esportazione PDF/CSV (il PDF regionale resta in OSM2CAIReport).
- Statistiche per gruppi montuosi.

## 6. Design

Vincolante `docs/project/design/fase-10/sentieri-del-gr.dc.html` (stili, testi, logica dei modi della mappa, scala di
colore dei settori). I confini in `data/gr-sentieri-geo.js` sono fittizi: nell'app si usano quelli del dump.

## 7. Considerazioni tecniche

- Peso del datapack: settori semplificati; nessuna geometria di regioni/province necessaria (la mappa usa i settori).
- Endpoint delle linee con limite e cache per richiesta; nessuna chiamata a servizi esterni.
- Verifiche in browser solo con utenti di prova creati e poi cancellati.
