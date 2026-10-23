# PRD: Fase 10.4 — Rifugi di Sezione

> Fase 10, feature 10.4 (`docs/project/tasks/fase-10-roadmap.md`). Design di riferimento, **salvato nel repository**:
> `docs/project/design/fase-10/rifugi-di-sezione.dc.html` (copia del file Claude Design "Rifugi di Sezione"). È un file
> HTML con stili inline e un blocco `<script type="text/x-dc">` che contiene i dati d'esempio e la logica degli stati:
> misure, colori, testi e comportamenti si leggono da lì. Sezione "1 · Elenco rifugi" (desktop + mobile, scenari uno /
> molti / nessuno) e "2 · Scheda rifugio" (desktop + mobile, schede tosa / bonvecchio / rossi).
> Questa feature fa parte di un unico run Ralph che comprende anche 10.5, 10.6, 10.7, 10.8 e 10.9: costruire qui i pezzi
> riusabili (mappa, import, scheda) pensando che le feature successive li riuseranno.

## 1. Introduzione

La Sezione vede i propri rifugi: un elenco con mappa e una scheda per ogni rifugio. La **fonte di verità è esterna**:
la banca dati ufficiale **rifugi.cai.it**, con API pubblica senza autenticazione
(`https://rifugi.cai.it/api/v1/shelters`, documentazione `https://rifugi.cai.it/api/documentation`, OpenAPI
`https://rifugi.cai.it/docs/api-docs.json`). Non si modificano mai i dati: si importano con uno script di aggiornamento,
passando dal datapack, così UAT ha gli stessi dati del locale.

Fatti verificati sull'API (ottobre 2026):
- 764 rifugi. Elenco paginato: `GET /api/v1/shelters?per_page=200&page=N` (campi base: `id`, `id_cai`, `title`, `slug`,
  `geo` GeoJSON Point `[lon, lat]`, `published`, `updated_at`, `altitude_geo`). Dettaglio: `GET /api/v1/shelters/{id_cai}`
  con `fields` (lista `{name, value}`, 136 nomi distinti) e `media` (foto: `original_url`, 698 rifugi su 764 ne hanno).
- **Collegamento alla Sezione**: campo `branch_id` (7 cifre), uguale al `cai_sections.codice_cai` per **761 rifugi su
  764**; gli altri 3 hanno `9300100` (CAI Sede Centrale, 2) e `9500046` (GR Sicilia, 1). 255 Sezioni hanno almeno un
  rifugio (SAT `9218001`: 70; SEZ. PISA nessuno).
- Campi utili (nome → significato): `alias` nome alternativo; `type` tipo (Rifugio custodito 307, Bivacco 252, Capanna
  sociale 89, Rifugio incustodito 84, Punto d'appoggio 32); `status` stato (In attività 683, Cessazione affido,
  Immobile non agibile, Alienato, Momentaneamente chiuso, In ristrutturazione); `altitude_geo`, `locality_geo`,
  `municipality_geo`, `province_geo`, `region_geo`, `valley_geo`, `massif_geo`, `protected_area_geo`; posti
  `posti_letto_service` / `posti_totali_service` / `posti_letto_invernali_service`; servizi `*_service` (0/1);
  contatti `fixedPhone_contact`, `emailAddress`, `webAddress_contact`, `facebook_contact`, `instagram_contact`,
  `openingTime_contact`; gestione `company_management_property`, `name_management_property`,
  `surname_management_property`, `mobilePhone_management_property`, `email_management_property`,
  `pec_management_property`, `possession_type_management_property`, `contract_start_date_management_property`,
  `contract_end_date_management_property`, `contract_fee_management_property` (formato "48564,05"); proprietà
  `name_property`, `taxCode_property`; certificazioni `*_certification` (0/1); `shelter_cin`.
- Pagina pubblica del rifugio: `https://rifugi.cai.it/shelters/{id_cai}`.
- Tile della cartografia CAI: `https://r3-it.storage.cloud.it/tiles/{z}/{x}/{y}.png`, zoom 6–16 (oltre: 404).

Decisioni del committente:
- I **dati di gestione** (gestore, contratto, canone, proprietà, certificazioni, CIN) sono **riservati**: li vedono solo la
  Sezione proprietaria del rifugio, il suo GR (regola del prefisso della 10.2) e lo staff con
  `Permission::CaiDirectoryView`.
- Il badge "Rifugi" della Dashboard Sezione conta **tutti** i rifugi della Sezione, qualunque sia lo stato.

## 2. Obiettivi

- Uno script di aggiornamento porta tutti i rifugi da rifugi.cai.it al datapack; il deploy li importa nel DB.
- La Sezione vede elenco, mappa (cartografia CAI) e schede dei suoi rifugi, come nel design.
- Il badge "Rifugi" della Dashboard Sezione mostra un numero reale.
- Mappa, import e scheda sono riusabili da 10.5 (rifugi del GR) e dalle feature su sentieri e SICAI.

## 3. User story

### US-1031: Tabella dei rifugi e import dal datapack
**Descrizione:** come sviluppatore, voglio i rifugi in una tabella locale importata dal datapack, così le pagine non
chiamano mai l'API esterna.

**Criteri di accettazione:**
- [ ] Migrazione `cai_huts`: `id_cai` (PK naturale, intero), `branch_code` (stringa 7, indicizzata, senza FK: può puntare
      a codici che non sono Sezioni come Sede Centrale o GR), `branch_name` (campo `branch` della fonte, es. "GR Sicilia",
      serve alla 10.5 per i rifugi intestati ai GR), `name`, `alias`, `slug`, `type`, `status`, `latitude`,
      `longitude`, `altitude`, `locality`, `municipality`, `province`, `region`, `valley`, `massif`, `protected_area`,
      `beds`, `winter_beds`, `opening_times`, `photos` (json: lista di URL), `services` (json: chiave → 0/1/null),
      `contacts` (json), `management` (json: gestore, possesso, contratto con `start_date`/`end_date`/`fee` numerico,
      proprietà, certificazioni, `cin`), `fields` (json: tutti i campi grezzi), `source_updated_at`, timestamp.
- [ ] Modello `App\Domain\CaiDirectory\Models\CaiHut` con cast (json, date, decimali) ed enum backed `CaiHutType` e
      `CaiHutStatus` (valori = testo della fonte, label italiane uguali, colori come nel design: custodito `#17a180`,
      incustodito `#2f6fb0`, bivacco `#d9822b`, capanna `#8a5cc2`, punto d'appoggio `#5b6475`; stati come la tabella `S`
      dello script del design). Un valore sconosciuto della fonte non deve rompere l'import: enum `null` + valore grezzo
      in `fields`.
- [ ] Relazione `CaiSection::huts()` (per `branch_code` = `codice_cai`) e Policy di sola lettura (deny-by-default).
- [ ] Datapack: nuova tabella `snap_cai_huts` nel file SQLite del datapack (stesso file e stesso pattern delle `snap_*`
      della Fase 9, vedi `app/Domain/CaiDirectory/CLAUDE.md` §Snapshot): una riga per rifugio con il JSON del dettaglio
      API grezzo e `id_cai`.
- [ ] `cai:import-datapack` importa `snap_cai_huts` in `cai_huts` (riga del riepilogo `snapshot_rifugi`): crea, aggiorna,
      **elimina** i rifugi non più presenti nella fonte (la fonte è di verità), idempotente (seconda esecuzione: 0 creati,
      0 aggiornati), `--dry-run` senza scritture. Parsing dei campi in un mapper dedicato e testato (canone "48564,05" →
      48564.05; date `Y-m-d`; `None`/stringa vuota → `null`).
- [ ] Test Pest con un datapack fixture: import, aggiornamento, eliminazione, idempotenza, dry-run, valore sconosciuto di
      tipo/stato, parsing del mapper.
- [ ] `composer run lint` e `composer run analyse` puliti.

### US-1032: Script di aggiornamento dei rifugi dall'API
**Descrizione:** come gestore dei dati, voglio un comando che scarichi i rifugi da rifugi.cai.it nel datapack.

**Criteri di accettazione:**
- [ ] Comando `cai:fetch-huts {--datapack=cai-datapack/runts-cai.sqlite} {--dry-run}`: scarica l'elenco paginato e il
      dettaglio di ogni rifugio (concorrenza massima 4, timeout 30 s, 3 tentativi con pausa), scrive `snap_cai_huts` in
      **una sola transazione** (DROP+CREATE+INSERT) solo se ha scaricato tutti i rifugi; se un dettaglio fallisce dopo i
      tentativi, il comando esce con errore e lascia la tabella precedente intatta.
- [ ] Riepilogo: rifugi scaricati, con foto, collegati a una Sezione esistente, non collegati (con i loro `branch_id`).
- [ ] Logica in una classe di dominio (`App\Domain\CaiDirectory\Import\CaiHutsFetcher` o simile), comando sottile. Client
      HTTP Laravel (`Http::`), testato con `Http::fake()`: paginazione, errore con tabella intatta, dry-run.
- [ ] Procedura documentata in `deploy/CLAUDE.md` e `app/Domain/CaiDirectory/CLAUDE.md`: `cai:fetch-huts` →
      `cai:import-datapack` in locale → `bin/push-cai-datapack` → deploy UAT. Mai chiamare l'API durante il deploy.
- [ ] Eseguito davvero in locale a fine storia: datapack aggiornato con i 764 rifugi e import nel DB di sviluppo
      (conteggi nel progress).
- [ ] Lint e analyse puliti.

### US-1033: Componente mappa con cartografia CAI
**Descrizione:** come sviluppatore, voglio un componente mappa unico per rifugi, sentieri e tappe SICAI.

**Criteri di accettazione:**
- [ ] Componente Blade `x-cai.map` basato su Leaflet 1.9.4 (già usato in `cai-sections-map.blade.php`), con tile
      `https://r3-it.storage.cloud.it/tiles/{z}/{x}/{y}.png`, `maxZoom` 16, `minZoom` 6, attribuzione
      "© CAI · © OpenStreetMap contributors". Caricamento di Leaflet una sola volta per pagina.
- [ ] Input: lista di marker (`lat`, `lng`, `id`, `label`, `color`, `letter`, `popupHtml` facoltativo) e, predisposto per
      i sentieri, lista di linee GeoJSON con colore. Adatta la vista ai marker (`fitBounds` con margine); con un solo marker
      zoom 13; senza marker vista sull'Italia.
- [ ] Marker come nel design: cerchio colorato del tipo con la lettera (R, I, B, C, P), più grande quando selezionato o in
      hover; popup con foto facoltativa, nome, tipo, quota, stato e "Apri scheda".
- [ ] Interazione con Livewire/Alpine: clic sul marker evidenzia la riga dell'elenco e viceversa (hover), senza ricaricare
      la pagina. Mappa reinizializzata correttamente dopo un aggiornamento Livewire (filtri).
- [ ] Test del rendering del componente (attributi/JSON dei marker); verifica in browser nella story della pagina.
- [ ] Lint e analyse puliti.

### US-1034: Pagina "Rifugi di Sezione" (elenco + mappa)
**Descrizione:** come utente Sezione, voglio vedere i miei rifugi su una mappa e in un elenco filtrabile.

**Criteri di accettazione:**
- [ ] `SectionHuts` smette di essere "in arrivo" e diventa la pagina reale (stesso slug), come nel design §1: link
      "← Dashboard", titolo, nome della Sezione, contatori "in attività / totale" e "posti letto".
- [ ] Filtri a chip per tipo (con conteggio, "—" se zero, selezione multipla, "Tutti"), filtro per stato (menu),
      ordinamento per nome (A–Z / Z–A) e quota (crescente / decrescente), conteggio "N strutture" / "n di N strutture".
      Stato in proprietà Livewire con `#[Url]`.
- [ ] Desktop: mappa a sinistra (o come nel design) ed elenco con miniatura foto (segnaposto se manca), nome, nome
      alternativo, tipo, pillola di stato, quota, comune, posti letto; clic sulla riga → scheda.
- [ ] Mobile: mappa sopra, elenco a card con i primi 12 e "Mostra altri N".
- [ ] Stati: un solo rifugio, molti (SAT), nessuno ("Nessun rifugio associato a questa Sezione nella banca dati
      rifugi.cai.it"), filtri senza risultati.
- [ ] Utente di Sottosezione o senza Sezione: stato vuoto coerente con la dashboard (nessun errore).
- [ ] Test Livewire: elenco, contatori, filtri, ordinamento, stati vuoti, 403 per non-Sezione.
- [ ] Verifica in browser (utente di prova poi cancellato, mai modificare utenti reali): SAT (molti) e una Sezione con 1
      rifugio, desktop e mobile.
- [ ] Lint e analyse puliti.

### US-1035: Scheda del rifugio
**Descrizione:** come utente Sezione o GR, voglio la scheda completa di un rifugio, con i dati di gestione se ne ho diritto.

**Criteri di accettazione:**
- [ ] Pagina `CaiHutDetail` con slug `rifugi/{idCai}`, fuori navigazione, come nel design §2: testata con foto (o
      segnaposto), nome, alias, tipo, stato, quota; mappa centrata; "Dove si trova" (località, comune, provincia,
      regione, valle, massiccio, area protetta, coordinate in gradi decimali con virgola); "Ospitalità e servizi" (posti
      letto, invernali, griglia dei 15 servizi del design con Sì / No / "—"); "Contatti" (link cliccabili); "Gestione";
      "Vedi su rifugi.cai.it" (`https://rifugi.cai.it/shelters/{id_cai}`) e "Ultimo aggiornamento del dato".
- [ ] Accesso: customer Sezione proprietario (`branch_code` = sua Sezione), customer GR il cui prefisso contiene il
      `branch_code`, staff con `CaiDirectoryView`. Chiunque altro: 403 (controllo in `mount()`, anche con id manipolato).
- [ ] La sezione **Gestione** (gestore, possesso, contratto, canone, proprietà, certificazioni, CIN) è resa **solo** per i
      tre profili autorizzati sopra: verificato da un test che controlla l'HTML (nessun dato di gestione nel markup).
- [ ] Scadenza del contratto come nel design: scaduto → evidenza rossa "Contratto scaduto" e "Scaduto da N mesi/giorni";
      in scadenza entro 6 mesi → evidenza ambra; date nel formato del design.
- [ ] Link "← Rifugi di Sezione" (o "← Rifugi del GR" se aperta dalla pagina del GR, parametro `?da=gr`).
- [ ] Test: contenuti, servizi Sì/No/—, scadenze (oggi fissato con `Carbon::setTestNow`), autorizzazioni, gestione nascosta.
- [ ] Verifica in browser: un rifugio custodito completo, un bivacco con molti dati mancanti, un contratto scaduto.
- [ ] Lint e analyse puliti.

### US-1036: Badge "Rifugi" della Dashboard Sezione
**Descrizione:** come utente Sezione, voglio vedere sul badge della dashboard quanti rifugi ha la mia Sezione.

**Criteri di accettazione:**
- [ ] `CaiSectionDashboardMetrics::forSection()` valorizza `huts` con il numero di **tutti** i `cai_huts` della Sezione
      (qualunque stato). Nessun rifugio → `0` mostrato come "0" (dato noto: la banca dati è completa), non "Non
      disponibile". Sottosezione: resta `null`.
- [ ] Test aggiornati e nuovi (con rifugi, senza rifugi = 0, Sottosezione = null).
- [ ] Lint e analyse puliti.

### US-1037: Collaudo, note per gli agenti e roadmap (10.4)
**Criteri di accettazione:**
- [ ] `docs/collaudo/fase-10.php`: argomento "Rifugi di Sezione (10.4)" con ID successivi all'ultimo esistente, collegati
      ai test reali di US-1031..US-1036; `collaudo:verify-manifest 10` passa.
- [ ] Test end-to-end: utente Sezione → dashboard (badge Rifugi con numero) → elenco → scheda (gestione visibile) ; un
      utente Sezione di un'altra Sezione → 403 sulla scheda.
- [ ] `docs/project/tasks/fase-10-roadmap.md` riga 10.4 aggiornata; `app/Domain/CaiDirectory/CLAUDE.md` con fonte,
      script di aggiornamento, tabella, regola di visibilità della gestione.
- [ ] Lint, analyse e test toccati verdi.

## 4. Requisiti funzionali

- FR-1: i rifugi vengono solo da rifugi.cai.it, tramite `cai:fetch-huts` → datapack → `cai:import-datapack`.
- FR-2: un rifugio appartiene alla Sezione il cui `codice_cai` è uguale al `branch_id` della fonte.
- FR-3: la pagina della Sezione mostra mappa (cartografia CAI) ed elenco filtrabile e ordinabile.
- FR-4: la scheda mostra tutti i dati pubblici; i dati di gestione solo a Sezione proprietaria, suo GR e staff.
- FR-5: il badge "Rifugi" conta tutti i rifugi della Sezione.

## 5. Fuori scope

- Modifica dei dati dei rifugi (la fonte resta esterna).
- Rifugi del GR (10.5): qui solo l'accesso del GR alla scheda.
- Copia locale delle foto (si usano gli URL di rifugi.cai.it).
- Aggiornamento automatico pianificato (lo script si lancia a mano prima di un push del datapack).

## 6. Design

Vincolante il file `docs/project/design/fase-10/rifugi-di-sezione.dc.html`: leggere stili inline, testi e la logica
dello script (`renderVals`, `mapView`, tabelle `T` e `S`, calcolo della scadenza). Token nuovi come `--ms-*` in
`resources/css/theme.css`. La mappa del design è disegnata a mano con le tile: nell'app si usa Leaflet con le stesse tile e
lo stesso aspetto dei marker.

## 7. Considerazioni tecniche

- Nessuna chiamata all'API durante le richieste web o il deploy.
- Verifiche in browser solo con utenti di prova creati e poi cancellati (vedi Codebase Patterns in `progress.txt`).
- `npm run build` dopo nuove classi Tailwind.

## 8. Domande aperte

- Il rifugio intestato al GR Sicilia (`9500046`) e i 2 della Sede Centrale non appartengono a nessuna Sezione: li tratta
  la 10.5.
