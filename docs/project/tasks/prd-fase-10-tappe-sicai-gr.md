# PRD: Fase 10.9 — Tappe SICAI del GR (e "In attesa" per la Sezione)

> Fase 10, feature 10.9 (`docs/project/tasks/fase-10-roadmap.md`). Design di riferimento, **salvato nel repository**:
> `docs/project/design/fase-10/tappe-sicai-del-gr.dc.html` (copia del file Claude Design "Tappe SICAI del GR"): blocchi
> "Riepilogo", "Mappa", "Elenco delle tappe", "Punti di appoggio" e "Scheda della tappa · solo le parti nuove"
> (navigazione tappa precedente/successiva, "Punti di appoggio vicini" entro 1,5 km in ordine di percorrenza). I tracciati
> e i punti del design sono d'esempio.
> **Dipende da 10.4** (componente mappa, `cai:fetch-huts`, `cai_huts`) **e 10.6/10.7** (script osm2cai, `cai_trails`, scheda
> `CaiTrailDetail`). La **10.8 (Tappe SICAI di Sezione) non si fa**: in osm2cai solo 29 tappe su 525 hanno una Sezione
> (decisione del committente); il badge della Sezione resta "In attesa" (US-1097).

## 1. Introduzione

Il GR vede le tappe del Sentiero Italia CAI (SICAI) della sua regione e i punti di appoggio lungo il percorso.

Fatti verificati:
- **Tappe** (dump osm2cai): il SICAI è diviso in 18 itinerari regionali (`itineraries.name` = "Sentiero Italia - Piemonte
  E00", "Sentiero Italia - Toscana/Emilia-Romagna L00", …), collegati ai sentieri da `hiking_route_itinerary`: **525 tappe**
  (sentieri), quasi tutte in stato 4. Il **codice tappa** sta nel nome del sentiero, "Sentiero Italia - Tappa F05" (varianti:
  "D18S", "S17S", "SI L01AP"); `ref` vale solo "SI". 444 tappe hanno il codice nel nome; per le altre 81 (senza nome, varianti,
  32 tratti "AVxx" dell'Alta Via dei Monti Liguri) la **lettera** si prende dall'itinerario ("… G00" → G).
- **Lettere e regioni**: A Friuli Venezia Giulia, B Veneto, C Trentino-Alto Adige, D Lombardia, E Piemonte, F Valle d'Aosta,
  G Liguria, L Toscana **e** Emilia-Romagna, N Umbria **e** Marche, O Lazio, P Abruzzo, Q Molise, R Puglia, S Campania,
  T Basilicata, U Calabria, V Sicilia, Z Sardegna. **Decisione del committente**: per le lettere condivise (L, N, C) entrambi i
  GR vedono tutte le tappe di quella lettera.
- **Punti di appoggio** (API rifugi.cai.it, pubblica): `GET /api/v1/sicaiRestPoints?page=N` (15 per pagina, 307 punti),
  dettaglio `GET /api/v1/sicaiRestPoints/{id}` con `geo` Point, `shelter_id` (53 collegati a un rifugio), `fields`: `type`
  (Rifugio 110, Hotel 61, B&B 57, Casa vacanze 33, Ostello 8, Camping 7, …), `region_geo`, `municipality_geo`,
  `address_street`, `address_number`, `operator`, `website`, `email_management_property`, `mobilePhone_management_property`,
  `status`, `description`, `notes`, `join_data`; il campo `stages` è sempre vuoto (le tappe non sono indicate dalla fonte).
- **Badge della Dashboard GR** (committente): numero di tappe, nota "N km".

## 2. Obiettivi

- Il GR vede tappe, tracciato, percorribilità e punti di appoggio della sua regione.
- I dati arrivano dagli script di aggiornamento esistenti (dump osm2cai e API rifugi) via datapack.
- Il badge "Tappe SICAI" della Dashboard GR mostra il numero di tappe; quello della Sezione dice "In attesa".

## 3. User story

### US-1091: Tappe SICAI dal dump osm2cai
**Criteri di accettazione:**
- [ ] `bin/update-osm2cai-datapack` estrae anche `snap_osm2cai_sicai_stages` (`trail_id`, `itinerary_name`, `letter`,
      `number`, `variant`, `code`, `sort_key`) per i sentieri degli itinerari il cui nome inizia con "Sentiero Italia".
      Codice dal nome con l'espressione `Tappa\s+(?:SI\s+)?([A-Z])(\d{2})([A-Z]*)`; se non c'è, lettera dall'itinerario
      (`([A-Z])00$`), numero e variante nulli, `code` = `ref` o "—". Regola implementata in PHP (classe testata) se il parsing
      avviene all'import, oppure in SQL nello script: in entrambi i casi coperta da test con i casi reali sopra.
- [ ] I sentieri delle tappe sono già in `cai_trails` (10.6): se qualcuno ha stato fuori da 1–4 va comunque estratto come
      sentiero (verificare: oggi 515 in stato 4, 9 in 3, 1 in 2).
- [ ] Import in `cai_sicai_stages` (PK `cai_trail_id`, colonne sopra) da `cai:import-datapack`, idempotente, con eliminazione.
- [ ] Test del parsing (F05, D18S, S17S, "SI L01AP", nome nullo con itinerario G00, AV16) e dell'import.
- [ ] Lint e analyse puliti.

### US-1092: Punti di appoggio SICAI dall'API rifugi
**Criteri di accettazione:**
- [ ] `cai:fetch-huts` (10.4) scarica anche i punti di appoggio (elenco paginato + dettaglio, stessa robustezza) nella tabella
      `snap_cai_sicai_rest_points` del datapack, nella stessa transazione dei rifugi (o in un comando gemello
      `cai:fetch-sicai-rest-points` con le stesse regole: tutto o niente).
- [ ] Tabella `cai_sicai_rest_points` (PK `id` della fonte, `name`, `type`, `latitude`, `longitude`, `region`, `municipality`,
      `address`, `operator`, `website`, `email`, `phone`, `cai_hut_id_cai` se `shelter_id` collegato a un rifugio importato,
      `fields` json, `source_updated_at`) e import idempotente con eliminazione.
- [ ] Test con `Http::fake()` e datapack fixture.
- [ ] Lint e analyse puliti.

### US-1093: Punti di appoggio vicini alle tappe
**Criteri di accettazione:**
- [ ] All'import (dopo tappe e punti), calcolo della tabella `cai_sicai_stage_rest_point` (`cai_trail_id`, `rest_point_id`,
      `distance_m`, `position` = distanza progressiva lungo il tracciato del punto più vicino) per i punti entro **1,5 km** dal
      tracciato della tappa (distanza punto–polilinea con proiezione equirettangolare locale, precisione sufficiente sotto i
      2 km). Ricalcolata a ogni import.
- [ ] Classe di calcolo testata (punto sul tracciato, a 1,4 km, a 1,6 km, ordine di percorrenza).
- [ ] Lint e analyse puliti.

### US-1094: Tappe e punti di un GR
**Criteri di accettazione:**
- [ ] Metodo sull'enum `Region` (o mappa dedicata) con le lettere SICAI della regione (L per Toscana ed Emilia-Romagna, N per
      Umbria e Marche, C per Trentino-Alto Adige) e test completo delle 20 regioni.
- [ ] Classe `CaiRegionalGroupSicai` per `Region`: tappe delle lettere della regione ordinate per lettera, numero, variante
      (le senza numero in fondo), riepilogo (numero tappe, km totali, dislivello positivo totale, tappe non percorribili o con
      difficoltà, numero punti di appoggio), punti di appoggio del GR = punti con `region` uguale alla regione **oppure** vicini
      (US-1093) ad almeno una tappa del GR, raggruppati per tipo, con il numero di punti vicini per tappa.
- [ ] Test: lettere condivise visibili a entrambi i GR, ordinamento, riepilogo, punti per regione e per vicinanza.
- [ ] Lint e analyse puliti.

### US-1095: Pagina "Tappe SICAI del GR"
**Criteri di accettazione:**
- [ ] `RegionalGroupSicaiStages` smette di essere "in arrivo" e diventa la pagina reale (stesso slug), con i blocchi del design:
      riepilogo; mappa con il tracciato delle tappe (colori alternati e/o etichette del codice come nel design) e i punti di
      appoggio come marker per tipo, anteprima al clic; elenco delle tappe (codice, partenza → arrivo, km, dislivelli,
      difficoltà, percorribilità, punti vicini; varianti indicate; filtro per percorribilità); elenco dei punti di appoggio per
      tipo (nome, comune, gestore, contatti, "Vedi su rifugi.cai.it" se collegato a un rifugio).
- [ ] Mobile come nel design. Stati: molte tappe (Piemonte), poche (Basilicata), tappa non percorribile, nessun punto di
      appoggio, GR senza regione.
- [ ] Clic su una tappa → `CaiTrailDetail` con `?da=sicai`.
- [ ] Test Livewire e verifica in browser (utente GR di prova poi cancellato): Piemonte e Basilicata, desktop e mobile.
- [ ] Lint e analyse puliti.

### US-1096: Scheda della tappa
**Criteri di accettazione:**
- [ ] Quando il sentiero è una tappa SICAI, `CaiTrailDetail` aggiunge le parti nuove del design: codice della tappa in testata,
      navigazione "← tappa precedente / tappa successiva →" nella stessa lettera (secondo l'ordinamento di US-1094) e il blocco
      "Punti di appoggio vicini" (entro 1,5 km, in ordine di percorrenza, con "Nessun punto di appoggio vicino" e il rimando
      alle tappe adiacenti come nel design).
- [ ] Accesso in più: customer GR la cui regione ha la lettera della tappa (anche se il sentiero non ha Sezioni del GR).
- [ ] Ritorno a "Tappe SICAI" con `?da=sicai`.
- [ ] Test di navigazione, punti vicini, accesso.
- [ ] Lint e analyse puliti.

### US-1097: Badge "Tappe SICAI" — GR con il numero, Sezione "In attesa"
**Criteri di accettazione:**
- [ ] Dashboard GR: `sicaiStages` = numero di tappe del GR, nota "{km} km" (1 decimale). GR senza regione → `null`.
- [ ] Dashboard Sezione: il badge "Tappe SICAI" mostra **"In attesa"** al posto di "Non disponibile" (stesso stile attenuato;
      nuova variante/stato del componente `x-cai.metric-badge`, senza cambiare gli altri badge). La pagina `SectionSicaiStages`
      mostra "In attesa dei dati sull'assegnazione delle tappe alle Sezioni." con "Torna alla dashboard".
- [ ] Test aggiornati e nuovi.
- [ ] Lint e analyse puliti.

### US-1098: Collaudo, note per gli agenti e roadmap (10.9)
**Criteri di accettazione:**
- [ ] `docs/collaudo/fase-10.php`: argomento "Tappe SICAI del GR (10.9)" con ID successivi all'ultimo esistente, collegati ai
      test reali di US-1091..US-1097; `collaudo:verify-manifest 10` passa.
- [ ] Test end-to-end: utente GR → dashboard (numero tappe) → pagina → tappa → tappa successiva → punto di appoggio; GR
      Toscana ed Emilia-Romagna vedono le stesse tappe L; utente Sezione → badge "In attesa".
- [ ] Roadmap: riga 10.8 "non prevista (dati assenti in osm2cai)", riga 10.9 aggiornata. `app/Domain/CaiDirectory/CLAUDE.md`:
      fonti, parsing del codice, lettere condivise, raggio 1,5 km.
- [ ] Lint, analyse e test toccati verdi.

## 4. Requisiti funzionali

- FR-1: le tappe vengono dal dump osm2cai, i punti di appoggio dall'API rifugi, sempre via datapack.
- FR-2: un GR vede le tappe delle lettere della sua regione; le lettere condivise valgono per entrambe le regioni.
- FR-3: i punti vicini a una tappa sono quelli entro 1,5 km dal tracciato, in ordine di percorrenza.
- FR-4: il badge GR mostra il numero di tappe con i km; quello della Sezione "In attesa".

## 5. Fuori scope

- Tappe SICAI di Sezione (10.8).
- Modifica dei dati (le fonti restano esterne).
- Calcolo del GR di una tappa per geometria (si usa la lettera, per decisione del committente).

## 6. Design

Vincolante `docs/project/design/fase-10/tappe-sicai-del-gr.dc.html`.

## 7. Considerazioni tecniche

- Nessuna chiamata esterna durante le richieste web o il deploy.
- Verifiche in browser solo con utenti di prova creati e poi cancellati.
