# PRD: Fase 10.2 — Dashboard V2 del Gruppo Regionale CAI

> Fase 10, feature 10.2 (`docs/project/tasks/fase-10-roadmap.md`). Design di riferimento: progetto Claude Design
> "Piattaforma Montagna Servizi", file `Dashboard Gruppo Regionale CAI V2.dc.html` + componente aggiornato
> `Badge Metrica.dc.html`. Le misure sono riportate in §6: chi implementa non deve accedere a Claude Design.
> Riusa tutto ciò che la 10.1 ha costruito (`prd-fase-10-dashboard-sezione-v2.md`): componente `x-cai.metric-badge`,
> `ItalianNumberFormatter`, `CaiSectionDashboardMetrics`, pattern delle pagine "in arrivo" e dell'atterraggio.

## 1. Introduzione

L'utente cliente di un Gruppo Regionale (GR) oggi atterra su `CustomerDashboard`, sotto il gruppo di navigazione "GR".
La Dashboard V2 del GR diventa la sua prima pagina, con la stessa struttura della dashboard Sezione:

- intestazione con nome del GR, codice CAI e regione;
- bilancio proprio del GR (conto economico e stato patrimoniale);
- bilancio **consolidato** delle Sezioni del GR (somma dei bilanci delle Sezioni, un solo esercizio);
- badge semplici consolidati: numero di Sezioni, soci, rifugi, sentieri, tappe SICAI.

Il GR **non esiste come ente nel database**: è un `User` con `customer_type = GruppoRegionale` e `region`. In
questa feature:

- il **codice CAI del GR** si ricava dalle Sezioni: è il prefisso comune dei codici più `000` (decisione del committente);
- il **bilancio proprio** e l'**iscrizione RUNTS** del GR non si raccolgono ora (decisione del committente): i due
  badge di bilancio del GR sono "Non disponibile" e la pillola Terzo Settore non compare.

## 2. Obiettivi

- L'utente GR, dopo il login, atterra sulla Dashboard V2 del GR.
- I numeri consolidati vengono da dati reali già presenti (anagrafica CAI e bilanci delle Sezioni), quindi
  funzionano su UAT senza un nuovo datapack.
- Ogni valore consolidato dice da quante Sezioni viene ("Somma di N Sezioni").
- Nessun badge mostra mai "0" per un dato mancante.

## 3. User story

### US-1011: Metriche del Gruppo Regionale
**Descrizione:** come sviluppatore, voglio una classe di sola lettura che calcoli codice, Sezioni e metriche
consolidate di un GR, così che la pagina non contenga logica sui dati.

**Criteri di accettazione:**
- [ ] Nuova classe `App\Domain\CaiDirectory\Queries\CaiRegionalGroupDashboardMetrics` con un metodo per una
      `Region` che restituisce un value object readonly (es. `Support\CaiRegionalGroupDashboardData`) con: codice CAI
      del GR, numero di Sezioni, soci consolidati e numero di Sezioni sommate, conto economico consolidato, stato
      patrimoniale consolidato, rifugi, km sentieri, tappe SICAI.
- [ ] **Prefisso del GR**: fra le `CaiSection` la cui `region` corrisponde alla `Region` (stesso confronto
      `mb_strtoupper(label())` di `CaiSectionDashboardMetrics`/`CaiRegionalGroupsQuery`), il prefisso di 4 caratteri
      di `codice_cai` più frequente. A parità vince il prefisso numericamente più alto. Codice CAI del GR =
      prefisso + `000` (Toscana `9226000`, Lombardia `9216000`, Trentino-Alto Adige `9219000`, FVG `9222000`).
- [ ] **Sezioni del GR** = tutte le `CaiSection` il cui `codice_cai` inizia con il prefisso del GR, **qualunque sia
      la loro `region`**. Quindi la SAT (`9218…`, Trentino-Alto Adige) non rientra nel GR `9219` e Sappada (`9220…`,
      regione FVG) rientra nel GR Veneto (`9220`). Le Sottosezioni non si contano mai.
- [ ] Regione senza Sezioni: codice `null`, numero di Sezioni `null`, tutto il resto `null`.
- [ ] **Soci** = somma dei `members_count` non nulli delle Sezioni del GR. Numero di Sezioni sommate = quante hanno
      `members_count` non nullo. Se nessuna ce l'ha, soci `null`.
- [ ] **Bilancio di una Sezione per un anno**: stesse regole della 10.1 (bilancio diretto o via iscrizione RUNTS; se
      due bilanci della stessa Sezione hanno lo stesso anno, vince quello RUNTS). Riusare o estrarre la logica di
      `CaiSectionDashboardMetrics`, senza duplicarla. Una sola query per tutti i bilanci del GR (nessun N+1).
- [ ] **Conto economico consolidato**: esercizio = l'anno più alto in cui almeno una Sezione del GR ha un bilancio con
      `hasIncomeStatementData()`. Si sommano solo le Sezioni con dati di conto economico in quell'anno: ricavi = somma
      dei `total_revenues` non nulli, costi = somma dei `total_expenses` non nulli, risultato netto = somma dei
      `net_result` non nulli. Un totale senza alcun valore non nullo resta `null`, mai 0. Numero di Sezioni sommate =
      Sezioni con dati di conto economico nell'anno.
- [ ] **Stato patrimoniale consolidato**: stessa regola con `hasBalanceSheetData()`, scelto indipendentemente
      (l'esercizio può essere diverso). Patrimonio netto e totale attivo sommati sui valori non nulli; numero di Sezioni
      sommate e numero totale di Sezioni del GR.
- [ ] Bilancio proprio del GR: sempre `null`. Rifugi, km sentieri, tappe SICAI: sempre `null`.
- [ ] Test Pest: prefisso più frequente; regione mista (SAT esclusa, Sappada al Veneto); regione senza Sezioni;
      somma dei soci con Sezioni senza soci; consolidato su un solo anno (le Sezioni ferme a un anno precedente non
      entrano); valori parziali (un totale senza valori resta `null`); parità di anno in una Sezione (vince RUNTS);
      bilancio via iscrizione; conto economico e stato patrimoniale su esercizi diversi; le Sottosezioni non contano.
- [ ] `composer run lint` e `composer run analyse` puliti.

### US-1012: Badge metrica — nota sotto il valore
**Descrizione:** come utente GR, voglio sapere da quante Sezioni viene ogni numero consolidato.

**Criteri di accettazione:**
- [ ] Variante `semplice` di `x-cai.metric-badge`: nuova prop facoltativa `note` (es. "Somma di 42 Sezioni"),
      mostrata sotto il numero solo quando il valore c'è. Nello stato "Non disponibile" la nota non compare mai.
- [ ] Variante `bilancio`: nuova prop facoltativa `yearNote`, accodata alla riga dell'esercizio con " · " (es.
      "Esercizio 2025 · Somma di 11 Sezioni"). Se l'esercizio non c'è, la nota non compare.
- [ ] Senza le nuove prop il rendering è identico a oggi: la Dashboard Sezione (10.1) non cambia e i suoi test
      restano verdi senza modifiche.
- [ ] Test Pest del componente per i nuovi casi (nota con valore, nota nascosta se non disponibile, nota
      dell'esercizio).
- [ ] `npm run build` se servono classi nuove; lint e analyse puliti.

### US-1013: Pagine "in arrivo" del GR
**Descrizione:** come utente GR, voglio che ogni badge porti a una pagina, anche se il contenuto arriverà più avanti.

**Criteri di accettazione:**
- [ ] Generalizzare la base `SectionComingSoonPage` (10.1) in modo che il tipo cliente ammesso sia un parametro della
      sottoclasse, senza cambiare il comportamento delle 5 pagine Sezione (i loro test restano verdi).
- [ ] Cinque pagine GR, slug sotto `gr/`: "Soci", "Bilancio" (usata da tutti e 4 i badge di bilancio), "Rifugi del
      GR", "Sentieri del GR", "Tappe SICAI". Stesso avviso "Questa sezione è in arrivo." e link "Torna alla dashboard".
- [ ] Fuori navigazione; `canAccess()`: solo customer con `customer_type = GruppoRegionale`.
- [ ] Test Pest: un GR vede ogni pagina (200); staff, Sezione e altri tipi ricevono 403.
- [ ] Lint e analyse puliti.

### US-1014: Pagina Dashboard V2 del GR
**Descrizione:** come utente che gestisce un Gruppo Regionale, voglio una dashboard semplice con i numeri chiave
del GR e delle sue Sezioni.

**Criteri di accettazione:**
- [ ] Nuova pagina Filament `App\Filament\Pages\RegionalGroupDashboard`: etichetta "Dashboard", icona home, gruppo
      di navigazione "GR", in testa al gruppo. `canAccess()`: solo customer con `customer_type = GruppoRegionale`.
- [ ] Intestazione: nome dell'utente GR (es. "GR Toscana") e, sotto, "Codice CAI {codice GR} · {regione}". Il codice
      è omesso se `null`. Nessuna pillola Terzo Settore.
- [ ] Riga 1: "Conto economico" e "Stato patrimoniale" del GR, come nella 10.1. In questa feature sono sempre nello
      stato "nessun dato".
- [ ] Riga 2: "Conto economico consolidato Sezioni" (risultato netto principale, ricavi e costi secondari, Avanzo o
      Disavanzo sul risultato) e "Stato patrimoniale consolidato Sezioni" (patrimonio netto principale, totale attivo
      secondario). Riga dell'esercizio: "Esercizio {anno} · Somma di {N} Sezioni" per il conto economico, "Esercizio
      {anno} · Somma di {N} Sezioni su {M}" per lo stato patrimoniale.
- [ ] Riga 3 (5 badge semplici): "Sezioni" (numero di Sezioni del GR, link "Sezioni del GR" →
      `CustomerRegionalSectionsDashboard`); "Soci" (nota "Somma di {N} Sezioni", link "Soci"); "Rifugi" (link
      "Rifugi del GR"); "Sentieri" (km, link "Sentieri del GR"); "Tappe SICAI" (link "Tappe SICAI"). Tutti i badge di
      bilancio portano a "Bilancio" (pagina GR di US-1013).
- [ ] Il singolare va gestito: "Somma di 1 Sezione", "Somma di 1 Sezione su 27".
- [ ] Utente GR senza `region` (es. "GR Lombardia/SOLROA"): intestazione col nome e avviso "Nessuna regione associata
      al tuo account: contatta Montagna Servizi.", con tutti i badge "Non disponibile".
- [ ] Responsive: desktop righe 2 / 2 / 5 colonne; mobile (< 640 px) badge di bilancio in colonna, poi "Sezioni" a
      tutta larghezza, poi gli altri 4 su 2 colonne.
- [ ] Test Livewire: GR con Sezioni, bilanci e soci (valori e note corretti, singolare); GR la cui regione non ha
      Sezioni; GR senza regione; link dei badge; prima voce del gruppo "GR"; 403 per staff, Sezione e altri tipi.
- [ ] Verifica in browser (Playwright, **impersonando** un utente GR esistente da `manager@oc.test` oppure con un
      utente di prova creato e poi cancellato: mai modificare password o collegamenti di utenti reali), screenshot
      desktop 1440 px e mobile 390 px per GR Toscana.
- [ ] Lint e analyse puliti.

### US-1015: Atterraggio del GR e "Ticket e documenti"
**Descrizione:** come utente GR, voglio atterrare sulla V2 e ritrovare ticket e documenti in una pagina dedicata.

**Criteri di accettazione:**
- [ ] `Dashboard::mount()`: un customer `GruppoRegionale` viene reindirizzato a `RegionalGroupDashboard`. Sezione →
      `SectionDashboard` come oggi; ogni altro customer → `CustomerDashboard`.
- [ ] Per l'utente GR `CustomerDashboard` diventa "Ticket e documenti" (etichetta, titolo, stessa icona usata per la
      Sezione), subito dopo "Dashboard" nel gruppo "GR". Blocchi, autorizzazioni e azioni invariati. Per Organo
      Tecnico e clienti senza tipo non cambia nulla.
- [ ] Test esistenti che si aspettano il vecchio atterraggio del GR (`DashboardTest`, `CustomerDashboardTest`,
      `Fase10DashboardSezioneEndToEndTest`, checkpoint delle Fasi 6-9) aggiornati solo per questo e verdi.
- [ ] Test nuovi: atterraggio GR → V2; Organo Tecnico → `CustomerDashboard`; "Ticket e documenti" per il GR.
- [ ] Verifica in browser: sidebar completa dell'utente GR (gruppi "GR" e "Sezioni").
- [ ] Lint e analyse puliti.

### US-1016: Collaudo, note per gli agenti e roadmap
**Descrizione:** come committente, voglio la feature tracciata nel collaudo e verificabile su UAT.

**Criteri di accettazione:**
- [ ] `docs/collaudo/fase-10.php`: nuovo argomento "Dashboard V2 del Gruppo Regionale (10.2)" con ID successivi
      all'ultimo esistente (F10-52…), collegati ai test reali di US-1011..US-1015; `collaudo:verify-manifest 10` passa.
- [ ] Test end-to-end `tests/Feature/EndToEnd/Fase10DashboardGruppoRegionaleEndToEndTest.php`: utente GR → login →
      V2 con valori consolidati → badge "Sezioni" → elenco Sezioni del GR. Va nel manifest con ID dedicati.
- [ ] `docs/project/tasks/fase-10-roadmap.md`: riga 10.2 con il link a questo PRD e stato "in sviluppo".
- [ ] `app/Domain/CaiDirectory/CLAUDE.md`: regole del GR (prefisso, Sezioni per prefisso e non per regione, codice
      derivato, consolidato su un solo esercizio, somme dei soli valori non nulli). La 10.3 (pagina del bilancio
      consolidato) deve riusare le stesse regole.
- [ ] Lint, analyse e i test toccati verdi (con l'isolamento dal database di sviluppo).

## 4. Requisiti funzionali

- FR-1: dopo il login un customer `GruppoRegionale` atterra su `RegionalGroupDashboard`.
- FR-2: il codice CAI del GR è il prefisso più frequente fra le Sezioni della sua regione più `000`.
- FR-3: le Sezioni del GR sono quelle con quel prefisso di codice, indipendentemente dalla loro regione.
- FR-4: il consolidato somma un solo esercizio, il più recente con dati, e solo le Sezioni con dati in quell'anno.
- FR-5: ogni valore consolidato mostra da quante Sezioni viene; lo stato patrimoniale mostra anche il totale.
- FR-6: un totale senza alcun valore resta "Non disponibile", mai 0.
- FR-7: il bilancio proprio del GR è "Non disponibile"; nessuna pillola Terzo Settore per il GR.
- FR-8: l'utente GR ritrova tutti i blocchi della dashboard attuale in "Ticket e documenti".
- FR-9: per Sezioni, Organi Tecnici e clienti senza tipo nulla cambia.

## 5. Fuori scope

- Anagrafica propria dei GR (tabella, codice fiscale), iscrizione RUNTS e bilancio proprio del GR.
- Pagina di approfondimento del bilancio consolidato (feature 10.3) e contenuto delle altre pagine "in arrivo".
- Rifugi, sentieri e tappe SICAI (10.5, 10.7, 10.9).
- Correzione dei bilanci delle Sezioni con valori anomali (vedi §7).
- Modifica della pagina "Sezioni del gruppo regionale", che continua a elencare gli utenti Sezione per `users.region`.

## 6. Design (dal file Claude Design — vincolante)

Tutte le misure di intestazione, badge semplice e badge di bilancio sono quelle della 10.1 (§6 di
`prd-fase-10-dashboard-sezione-v2.md`), già implementate in `x-cai.metric-badge` e `section-dashboard.css`: riusarle.
Differenze del GR:

- **Desktop:** tre griglie in colonna con gap 28 px: bilancio GR (2 colonne, gap 20 px), bilancio consolidato
  (2 colonne, gap 20 px), badge semplici (**5 colonne**, gap 20 px).
- **Mobile (390 px):** i 4 badge di bilancio uno sotto l'altro (gap 16 px), poi "Sezioni" a tutta larghezza, poi
  Soci/Rifugi/Sentieri/Tappe SICAI su 2 colonne (gap 12 px).
- **Nota del badge semplice:** sotto il numero, 12,5 px, `#8b93a3`, margin-top 2 px. Solo con valore.
- **Nota dell'esercizio nei badge di bilancio:** stessa riga e stesso stile dell'esercizio (12,5 px `#8b93a3`),
  testo "Esercizio 2025 · Somma di 11 Sezioni".
- Titoli dei badge consolidati: "Conto economico consolidato Sezioni", "Stato patrimoniale consolidato Sezioni".

## 7. Considerazioni tecniche

- Sola lettura: nessuna Action, nessuna migrazione, nessun nuovo dato nel datapack. Su UAT funziona con lo snapshot
  della Fase 9.
- Nessuna query per Sezione: il consolidato va calcolato con una query sui bilanci di tutte le Sezioni del GR (o con
  un'aggregazione SQL). Se si usa SQL, `havingRaw` e mai `having` su alias (gotcha Postgres del `CLAUDE.md` radice).
- **Numeri attesi dal database di sviluppo** (conto economico consolidato, ultimo esercizio), utili per la verifica
  in browser: Toscana 2025, 11 Sezioni su 27, ricavi ≈ 2.205.590 €, risultato netto ≈ +119.867 €, stato patrimoniale
  su 8 Sezioni; Lombardia 2025, 31 su 152; Veneto 2025, 24 su 66 (Sappada compresa).
- **Qualità del dato (non si corregge qui):** GR Sicilia 2025 ha ricavi consolidati ≈ 203 milioni di euro su 5 Sezioni,
  quasi certamente per un valore letto male da un bilancio. Va segnalato nelle note e verificato a parte.
- La pagina esistente "Sezioni del gruppo regionale" filtra gli utenti per `users.region`, mentre questa dashboard
  conta le `CaiSection` per prefisso: i due numeri possono differire. È atteso e va scritto nel `CLAUDE.md` del modulo.

## 8. Metriche di successo

- L'utente GR vede i numeri chiave del GR e delle sue Sezioni senza scorrere su desktop.
- Ogni numero consolidato dichiara quante Sezioni lo compongono.
- Nessun "0" per un dato mancante e nessun link rotto (coperti dai test).

## 9. Domande aperte

- Quando raccoglieremo i dati propri dei GR (RUNTS e bilanci)? Per ora sono fuori scope.
- Il valore anomalo dei bilanci del GR Sicilia: quale Sezione e quale bilancio lo causano?
