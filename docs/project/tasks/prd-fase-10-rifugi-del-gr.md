# PRD: Fase 10.5 — Rifugi delle Sezioni del GR

> Fase 10, feature 10.5 (`docs/project/tasks/fase-10-roadmap.md`). Design di riferimento, **salvato nel repository**:
> `docs/project/design/fase-10/rifugi-del-gr.dc.html` (copia del file Claude Design "Rifugi del GR"): stili inline e
> blocco `<script type="text/x-dc">` con dati d'esempio e logica degli stati (scenari lombardia / toscana / puglia /
> molise, filtro contratti, raggruppamento, cluster). Sezione "1 · Elenco rifugi del GR" e "2 · Scheda rifugio aperta
> dal GR" (solo testata e percorso di ritorno: il resto è la scheda della 10.4).
> **Dipende dalla 10.4** (`prd-fase-10-rifugi-di-sezione.md`): tabella `cai_huts`, import, componente `x-cai.map`, scheda
> `CaiHutDetail`, regola dei dati di gestione riservati. Fa parte dello stesso run Ralph di 10.4–10.9.

## 1. Introduzione

Il Gruppo Regionale vede in una pagina i rifugi di tutte le sue Sezioni (regola del prefisso della 10.2) e quelli intestati
direttamente al GR, con mappa (cluster agli zoom bassi), riepilogo, contratti di gestione in scadenza, elenco filtrabile e
raggruppabile per Sezione e l'elenco delle Sezioni senza rifugi.

Volumi reali (rifugi.cai.it, ottobre 2026): Lombardia 193 rifugi di 66 Sezioni, Piemonte 159 di 46, Veneto 96 di 36,
Toscana 26 di 14 Sezioni su 27, Puglia e Sardegna 1, Molise 3 di 1 Sezione. Un solo rifugio è intestato a un GR
(`branch_id` 9500046, `branch` "GR Sicilia", punto d'appoggio).

Il GR vede anche i dati di gestione dei rifugi delle sue Sezioni (decisione già presa nella 10.4).

## 2. Obiettivi

- Il GR vede tutti i rifugi del suo territorio e quanti contratti di gestione scadono o sono scaduti.
- Il badge "Rifugi" della Dashboard GR mostra un numero reale.
- La scheda del rifugio aperta dal GR riporta all'elenco del GR con i filtri intatti.

## 3. User story

### US-1041: Rifugi di un GR e riepilogo
**Descrizione:** come sviluppatore, voglio una classe di sola lettura con i rifugi di un GR e i numeri del riepilogo.

**Criteri di accettazione:**
- [ ] Classe `App\Domain\CaiDirectory\Queries\CaiRegionalGroupHuts` con un metodo per `Region` che restituisce un value
      object readonly: rifugi (con nome della Sezione proprietaria o "GR {regione}"), numero di Sezioni del GR (M), Sezioni
      con almeno un rifugio, Sezioni senza rifugi (nome e codice), totale rifugi, in attività, posti letto totali, contratti
      in scadenza e scaduti.
- [ ] Rifugi del GR = `cai_huts` con `branch_code` fra le Sezioni del GR (stessa regola di prefisso di
      `CaiRegionalGroupDashboardMetrics::sectionsOf()`, riusata) **più** quelli con `branch_code` che inizia per `95` e
      `branch_name` uguale (senza distinzione di maiuscole) a "GR {etichetta della regione}" dell'utente GR. Questi ultimi
      hanno come "Sezione" la dicitura "GR {regione}" e il flag "intestato al GR".
- [ ] **Stato del contratto** calcolato in un solo punto condiviso con la scheda della 10.4 (estrarre lì se non c'è già):
      scaduto se la data di fine è prima di oggi, in scadenza se cade entro 183 giorni da oggi, altrimenti nessuno; testo
      "Scaduto da N mesi/giorni" / "Scade tra N mesi/giorni" come nel design.
- [ ] Una query per i rifugi e una per le Sezioni (nessun N+1).
- [ ] Test Pest: prefisso (SAT esclusa dal GR Alto Adige), rifugio intestato al GR, Sezioni senza rifugi, contatori, stato
      contratto ai bordi (oggi, +183, +184, ieri) con `Carbon::setTestNow`.
- [ ] Lint e analyse puliti.

### US-1042: Cluster sulla mappa
**Descrizione:** come utente GR, voglio una mappa leggibile anche con 200 rifugi.

**Criteri di accettazione:**
- [ ] `x-cai.map` accetta un'opzione `cluster`: i marker vicini si raggruppano agli zoom bassi in un cerchio con il numero
      e il tooltip "N rifugi · clic per ingrandire"; clic → zoom +2 sul gruppo (come nel design). Libreria
      `leaflet.markercluster` 1.5.3 (stessa fonte CDN di Leaflet) con stile del cluster come nel design.
- [ ] Senza l'opzione il componente si comporta come nella 10.4 (test della 10.4 verdi senza modifiche).
- [ ] Popup del marker con anche il nome della Sezione.
- [ ] Lint e analyse puliti.

### US-1043: Pagina "Rifugi del GR"
**Descrizione:** come utente GR, voglio vedere e filtrare i rifugi delle mie Sezioni.

**Criteri di accettazione:**
- [ ] `RegionalGroupHuts` smette di essere "in arrivo" e diventa la pagina reale (stesso slug), come nel design §1: link
      "← Dashboard", titolo "Rifugi del GR", "{nome GR} · Codice CAI {codice}".
- [ ] Riepilogo come nel design: rifugi totali, in attività, posti letto, Sezioni con rifugi "su M", card **contratti**
      ("N in scadenza · N scaduti" oppure "Nessuno"; cliccabile: attiva/disattiva il filtro "contratto in scadenza o
      scaduto", testo "Filtra elenco →" / "Filtro attivo · ×").
- [ ] Mappa con cluster di tutti i rifugi filtrati; selezione/hover sincronizzati con l'elenco.
- [ ] Elenco desktop a tabella: nome, Sezione proprietaria, tipo, stato (pillola), quota, posti letto, scadenza contratto
      (evidenza ambra in scadenza, rossa scaduto, "—" se assente); ordinamento per colonna (default nome; con il filtro
      contratti attivo default per scadenza crescente, senza data in fondo); filtri per Sezione, tipo, stato.
- [ ] **Raggruppa per Sezione**: interruttore; testata di gruppo con nome della Sezione (o "GR {regione}") e numero di
      rifugi; disponibile solo se ci sono almeno 2 gruppi e nessun filtro per Sezione; attivo di default se il GR ha più di
      40 rifugi.
- [ ] Riquadro richiudibile "Sezioni senza rifugi" con l'elenco (aperto di default solo se il GR non ha rifugi).
- [ ] Mobile: card come nel design, primi 12 e "Mostra altri N".
- [ ] Stati: molti rifugi (Lombardia: cluster, raggruppato), tipico (Toscana), uno solo (Puglia), nessuno (stato vuoto e
      riquadro aperto), filtro contratti attivo, GR senza regione (avviso come nelle altre pagine GR).
- [ ] Filtri, ordinamento e raggruppamento in proprietà Livewire con `#[Url]`.
- [ ] Clic su un rifugio → `CaiHutDetail` con `?da=gr` e i parametri dell'elenco, così il ritorno li conserva.
- [ ] Test Livewire: contatori, filtro contratti, filtri, ordinamenti, raggruppamento, Sezioni senza rifugi, stati vuoti,
      403 per non-GR.
- [ ] Verifica in browser (utente GR di prova poi cancellato): Lombardia (cluster, raggruppato), Toscana, filtro contratti,
      desktop e mobile.
- [ ] Lint e analyse puliti.

### US-1044: Scheda del rifugio aperta dal GR
**Descrizione:** come utente GR, voglio tornare all'elenco del GR dalla scheda senza perdere i filtri.

**Criteri di accettazione:**
- [ ] Con `?da=gr`, la testata di `CaiHutDetail` mostra il percorso "← Dashboard / Rifugi del GR / {nome}" su desktop e
      "← Rifugi del GR" su mobile (design §2); il link riporta a `RegionalGroupHuts` con gli stessi filtri, ordinamento e
      raggruppamento. Il resto della scheda è invariato.
- [ ] Test del link di ritorno con i parametri.
- [ ] Lint e analyse puliti.

### US-1045: Badge "Rifugi" della Dashboard GR
**Criteri di accettazione:**
- [ ] `CaiRegionalGroupDashboardMetrics` valorizza `huts` con il numero dei rifugi del GR di US-1041 (Sezioni + intestati
      al GR) e la nota "Somma di N Sezioni" con N = Sezioni con almeno un rifugio (singolare gestito). Zero rifugi → "0"
      (dato noto), senza nota. GR senza regione → `null`.
- [ ] Test aggiornati e nuovi.
- [ ] Lint e analyse puliti.

### US-1046: Collaudo, note per gli agenti e roadmap (10.5)
**Criteri di accettazione:**
- [ ] `docs/collaudo/fase-10.php`: argomento "Rifugi del GR (10.5)" con ID successivi all'ultimo esistente collegati ai
      test reali di US-1041..US-1045; `collaudo:verify-manifest 10` passa.
- [ ] Test end-to-end: utente GR → dashboard (badge Rifugi) → elenco → filtro contratti → scheda (gestione visibile) →
      ritorno con filtro conservato; un GR di un'altra regione → 403 sulla scheda.
- [ ] Roadmap riga 10.5 aggiornata; `app/Domain/CaiDirectory/CLAUDE.md` con la regola dei rifugi intestati ai GR e lo
      stato del contratto.
- [ ] Lint, analyse e test toccati verdi.

## 4. Requisiti funzionali

- FR-1: i rifugi del GR sono quelli delle sue Sezioni (prefisso) più quelli intestati al GR.
- FR-2: il riepilogo mostra totale, in attività, posti letto, Sezioni con rifugi su M e contratti in scadenza/scaduti.
- FR-3: l'elenco si filtra, si ordina e si raggruppa per Sezione; la mappa raggruppa i marker vicini.
- FR-4: dalla scheda si torna all'elenco del GR con lo stesso stato.

## 5. Fuori scope

- Codici CAI veri dei GR (prefisso 95): non servono qui; il codice mostrato resta quello derivato della 10.2.
- Rifugi della Sede Centrale (`9300100`).
- Esportazione dell'elenco.

## 6. Design

Vincolante `docs/project/design/fase-10/rifugi-del-gr.dc.html` (stili, testi, logica: `base`, `renderVals`, cluster, stato
del contratto). Stessi token e componenti della 10.4.

## 7. Considerazioni tecniche

- Nessuna chiamata all'API rifugi: tutto da `cai_huts`.
- Verifiche in browser solo con utenti di prova creati e poi cancellati.
