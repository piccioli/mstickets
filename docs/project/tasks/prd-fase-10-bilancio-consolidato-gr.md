# PRD: Fase 10.3 — Bilancio consolidato delle Sezioni del GR

> Fase 10, feature 10.3 (`docs/project/tasks/fase-10-roadmap.md`). Design di riferimento: progetto Claude Design
> "Piattaforma Montagna Servizi", file `Bilancio consolidato Sezioni.dc.html` (sei schermate: desktop e mobile per
> copertura bassa con valore anomalo, copertura buona, esercizio senza dati). Le misure sono in §6: chi implementa non
> deve accedere a Claude Design. Riusa quanto costruito in 10.1 e 10.2 (`ItalianNumberFormatter`, stili dei badge,
> `CaiSectionStatementsQuery`, `CaiRegionalGroupDashboardMetrics`, regole di prefisso del GR).

## 1. Introduzione

È la pagina di approfondimento aperta dai badge di bilancio della Dashboard V2 del GR. Per un esercizio scelto mostra:

- il conto economico e lo stato patrimoniale consolidati delle Sezioni del GR;
- quante Sezioni hanno fornito il bilancio con i dati, quante solo il PDF e quante nulla;
- una tabella delle Sezioni con i loro valori, la fonte, lo stato e il link al PDF;
- i valori "da verificare", cioè fuori scala rispetto alle altre Sezioni (caso reale: SEZ. RAGUSA 2025, ricavi ≈ 202,5 M€).

Sostituisce la pagina "in arrivo" `RegionalGroupFinancialStatements` creata nella 10.2.

## 2. Obiettivi

- Il GR capisce in una schermata la situazione economica delle sue Sezioni per un esercizio e quante mancano.
- I numeri della pagina e della Dashboard GR seguono le stesse regole (prefisso, un bilancio per Sezione e anno).
- Un valore anomalo resta visibile ma segnalato, sia nella cella sia nella card consolidata che lo include.
- Il GR può aprire il PDF di ogni bilancio delle sue Sezioni.

## 3. User story

### US-1021: Dati del bilancio consolidato per esercizio
**Descrizione:** come sviluppatore, voglio una classe di sola lettura che restituisca tutto ciò che la pagina mostra
per un GR e un esercizio.

**Criteri di accettazione:**
- [ ] Nuova classe `App\Domain\CaiDirectory\Queries\CaiRegionalGroupConsolidatedStatements` con un metodo per
      (`Region`, anno) che restituisce un value object readonly con: codice del GR, numero di Sezioni M, righe per
      Sezione, totali, copertura, conteggio dei valori da verificare. Sezioni del GR e codice: **le stesse regole di
      `CaiRegionalGroupDashboardMetrics`** (prefisso), riusate e non duplicate.
- [ ] **Esercizi disponibili**: i 5 anni che finiscono con l'anno più alto presente in `cai_financial_statements` o in
      `cai_documents` (oggi 2021–2025). **Esercizio di default**: il più recente in cui almeno una Sezione del GR è in
      stato "Dati presenti"; se nessuno, il più recente dei 5.
- [ ] **Una riga per ogni Sezione del GR** (tutte le M, anche quelle senza nulla), con nome, codice, stato, fonte,
      ricavi, costi, risultato netto, patrimonio netto, totale attivo, id del documento PDF (o `null`).
- [ ] **Stato** della Sezione per l'anno:
      - "Dati presenti" se il bilancio dell'anno (stessa scelta della 10.1: diretto o via RUNTS, a parità vince RUNTS)
        ha `hasIncomeStatementData()` o `hasBalanceSheetData()`;
      - altrimenti "Solo PDF" se la Sezione ha un file di bilancio per l'anno, con **la stessa definizione** di
        `has_income_statement_file`/`has_balance_sheet_file` di `CaiSectionFinancialYearQuery` (riusata, non copiata);
      - altrimenti "Nessun bilancio".
- [ ] **Fonte**: "RUNTS" se il bilancio (o, per "Solo PDF", il documento) viene dall'iscrizione RUNTS, "Caricato a mano"
      se è collegato direttamente alla Sezione o ha `source = manual`; `null` per "Nessun bilancio".
- [ ] **PDF**: il documento di bilancio dell'anno con file presente, scelto con la stessa definizione dei file di
      bilancio (a parità: prima RUNTS, poi l'id più basso). `null` se non c'è.
- [ ] **Totali** (riga Totale e card consolidate): somma dei valori non nulli delle sole Sezioni in "Dati presenti";
      un totale senza valori resta `null`. Conto economico: N = Sezioni "Dati presenti" con almeno uno fra ricavi,
      costi, risultato. Stato patrimoniale: N = Sezioni "Dati presenti" con patrimonio netto o totale attivo.
- [ ] **Copertura**: conteggi "Dati presenti", "Solo PDF", "Nessun bilancio" (sommano a M).
- [ ] **Valore da verificare**: per ricavi, costi, patrimonio netto e totale attivo (mai per il risultato netto), un
      valore è da verificare se il suo valore assoluto è più di 10 volte la mediana dei valori assoluti non nulli di
      quella voce fra le Sezioni "Dati presenti" del GR nello stesso anno, **solo se quei valori sono almeno 3**. Ogni
      riga sa quali voci sono da verificare; il conteggio per card considera ricavi+costi (conto economico) e patrimonio
      netto+totale attivo (stato patrimoniale).
- [ ] Le query sono costanti nel numero di Sezioni (nessun N+1): una per i bilanci, una per i documenti, una per le Sezioni.
- [ ] Test Pest: stati (dati / solo PDF / nessuno), fonte RUNTS e manuale, PDF scelto, totali con valori parziali,
      copertura, esercizio di default (anche quando l'anno più recente non ha dati), mediana con meno di 3 valori (nessun
      flag), un valore oltre 10× mediana segnalato, risultato netto mai segnalato, Sezione fuori prefisso esclusa.
- [ ] `composer run lint` e `composer run analyse` puliti.

### US-1022: Il GR può scaricare i PDF delle sue Sezioni
**Descrizione:** come utente GR, voglio aprire il PDF di bilancio di qualunque Sezione del mio GR.

**Criteri di accettazione:**
- [ ] `CaiDocumentDownloadController`: un cliente Gruppo Regionale è autorizzato anche quando la `CaiSection` del
      documento (via iscrizione RUNTS o diretta) appartiene al suo GR secondo la regola del prefisso (stessa classe/metodo
      usato da US-1021, nessuna regola duplicata). La regola esistente (regione dell'utente Sezione collegato) resta valida.
- [ ] Un GR non scarica mai documenti di Sezioni di un altro GR (403), nemmeno manipolando l'id.
- [ ] Test Pest: GR della stessa regione-prefisso (200), Sezione con prefisso del GR ma senza utente collegato (200),
      Sezione di un altro GR (403), Sappada (`9220…`) scaricabile dal GR Veneto e non dal GR FVG.
- [ ] Lint e analyse puliti.

### US-1023: Pagina "Bilancio consolidato Sezioni" — intestazione, esercizio, card e copertura
**Descrizione:** come utente GR, voglio scegliere l'esercizio e vedere subito il consolidato e la copertura.

**Criteri di accettazione:**
- [ ] `RegionalGroupFinancialStatements` smette di essere una pagina "in arrivo" (non estende più la base coming soon) e
      diventa la pagina reale: titolo "Bilancio consolidato Sezioni", stesso slug, fuori navigazione, `canAccess()` solo
      customer `GruppoRegionale`.
- [ ] Link "← Dashboard" verso `RegionalGroupDashboard`. Sotto il titolo "{nome utente GR} · Codice CAI {codice}".
- [ ] Selettore dell'esercizio a pulsanti (i 5 anni di US-1021), stato in proprietà Livewire legata all'URL
      (`?esercizio=2024`); anno fuori dai 5 → esercizio di default.
- [ ] Due card: "Conto economico consolidato" (risultato netto principale con Avanzo/Disavanzo, ricavi e costi
      secondari) e "Stato patrimoniale consolidato" (patrimonio netto principale, totale attivo secondario), "Esercizio
      {anno}" sotto il principale. Card senza Sezioni con dati: stato grigio con "Non disponibile · Esercizio {anno}".
- [ ] Sotto ogni card: "Somma di N Sezioni su M" (singolare "1 Sezione") oppure "Nessuna Sezione con dati su M"; se la
      card include valori da verificare, l'avviso "Include 1 valore da verificare" / "Include N valori da verificare".
- [ ] Card "Copertura": "{N} su {M} Sezioni hanno il bilancio con i dati", barra in tre segmenti proporzionali
      (dati / solo PDF / nessuno), legenda con i tre conteggi e una nota: "Per l'esercizio {anno} nessuna Sezione ha un
      bilancio con i dati." / "Tutte le Sezioni hanno fornito il bilancio con i dati." / "I totali consolidati sono
      parziali: mancano i dati di {X} Sezioni, {Y} delle quali con il solo PDF." (singolari gestiti; la parte sul PDF
      solo se Y > 0).
- [ ] Utente GR senza regione: avviso "Nessuna regione associata al tuo account: contatta Montagna Servizi." e nient'altro.
- [ ] Test Livewire: valori delle card e note, avviso valori da verificare, copertura e nota nei tre casi, cambio
      esercizio via proprietà e via URL, anno non valido, GR senza regione, 403 per staff/Sezione/altri tipi.
- [ ] Lint e analyse puliti.

### US-1024: Tabella delle Sezioni (desktop) ed elenco (mobile)
**Descrizione:** come utente GR, voglio vedere e confrontare le Sezioni, filtrarle per stato e aprire i loro PDF.

**Criteri di accettazione:**
- [ ] Card "Sezioni del GR" con il conteggio ("27 Sezioni", oppure "{n} di 27 Sezioni" con un filtro attivo) e i
      filtri a pillola "Tutte", "Dati presenti", "Solo PDF", "Nessun bilancio", ciascuno con il proprio conteggio.
- [ ] Desktop: colonne Sezione, Stato (pillola), Fonte, Ricavi, Costi, Risultato netto, Patrimonio netto, Totale attivo,
      PDF (icona che apre `route('cai-documents.download')` in una nuova scheda, solo se c'è il documento). Valori
      mancanti "—"; risultato netto con segno, rosso se negativo.
- [ ] Ordinamento cliccando l'intestazione (tutte tranne PDF): primo clic crescente per Sezione/Stato/Fonte e
      decrescente per i numeri, secondo clic inverte; freccia sulla colonna attiva. I valori mancanti vanno sempre in
      fondo; a parità si ordina per nome. Default: nome crescente. Ordinamento e filtro in proprietà Livewire nell'URL.
- [ ] Cella da verificare: valore in grassetto su fondo ambra con icona di avviso e, sotto, "valore da verificare".
- [ ] Riga "Totale" in fondo (fondo menta) con "Somma di N Sezioni con dati" o "Nessuna Sezione con dati" e i totali.
- [ ] Filtro senza righe: "Nessuna Sezione in questo stato per l'esercizio {anno}."
- [ ] Mobile (< 640 px): la tabella diventa un elenco di card: nome, "Fonte: …", pillola di stato; per "Dati presenti"
      i valori su 2 colonne con il risultato netto a tutta riga; per "Solo PDF" la frase "Dati non ancora estratti dal
      PDF."; link "Apri PDF". Al posto delle intestazioni cliccabili un menu "Ordina per" con le opzioni: Nome (A–Z),
      Stato, Ricavi (dal più alto), Costi (dal più alto), Risultato netto (dal più alto), Risultato netto (dal più basso),
      Patrimonio netto (dal più alto), Totale attivo (dal più alto). Card "Totale" in fondo.
- [ ] Test Livewire: righe e valori, filtro per stato con conteggi, ordinamento per nome e per un numero (mancanti in
      fondo), inversione, link PDF presente solo con documento, cella da verificare, riga totale.
- [ ] Verifica in browser (Playwright con un utente GR di prova creato e poi cancellato, mai modificare utenti reali):
      GR Toscana desktop e mobile su 2025 e su un esercizio senza dati; GR Sicilia 2025 con SEZ. RAGUSA segnalata; apertura
      di un PDF.
- [ ] Lint e analyse puliti.

### US-1025: Collegamento dalla Dashboard GR
**Descrizione:** come utente GR, voglio arrivare dalla dashboard alla pagina sull'esercizio che stavo guardando.

**Criteri di accettazione:**
- [ ] Nella Dashboard V2 del GR i badge "Conto economico consolidato Sezioni" e "Stato patrimoniale consolidato
      Sezioni" portano a `RegionalGroupFinancialStatements` con `?esercizio={anno della card}` (senza parametro se la
      card non ha esercizio). Link "Bilancio" invariato nel testo.
- [ ] I due badge del bilancio proprio del GR portano alla stessa pagina senza parametro (non esiste ancora una pagina
      del bilancio proprio).
- [ ] Test aggiornati della Dashboard GR verdi; un test nuovo sui link con l'esercizio.
- [ ] Lint e analyse puliti.

### US-1026: Collaudo, note per gli agenti e roadmap
**Descrizione:** come committente, voglio la feature tracciata nel collaudo e verificabile su UAT.

**Criteri di accettazione:**
- [ ] `docs/collaudo/fase-10.php`: argomento "Bilancio consolidato Sezioni del GR (10.3)" con ID successivi all'ultimo
      esistente, collegati ai test reali di US-1021..US-1025; `collaudo:verify-manifest 10` passa.
- [ ] Test end-to-end `tests/Feature/EndToEnd/Fase10BilancioConsolidatoEndToEndTest.php`: utente GR → login → Dashboard
      → badge consolidato → pagina sull'esercizio giusto → filtro "Solo PDF" → download di un PDF (200) → PDF di un altro
      GR (403). Va nel manifest con ID dedicati.
- [ ] `docs/project/tasks/fase-10-roadmap.md`: riga 10.3 con il link a questo PRD e stato "in sviluppo".
- [ ] `app/Domain/CaiDirectory/CLAUDE.md`: stati della Sezione per esercizio, regola "da verificare" (10× mediana, almeno
      3 valori, mai sul risultato), esercizi disponibili e di default, autorizzazione di download per prefisso.
- [ ] Lint, analyse e i test toccati verdi (con l'isolamento dal database di sviluppo).

## 4. Requisiti funzionali

- FR-1: la pagina mostra le Sezioni del GR secondo la regola del prefisso, tutte, per l'esercizio scelto.
- FR-2: l'esercizio di default è il più recente con almeno una Sezione in "Dati presenti".
- FR-3: ogni Sezione ha uno stato fra "Dati presenti", "Solo PDF", "Nessun bilancio".
- FR-4: totali e card sommano solo i valori presenti delle Sezioni con dati e dichiarano quante Sezioni sommano.
- FR-5: un valore oltre 10 volte la mediana della sua voce (con almeno 3 valori) è "da verificare", visibile e segnalato.
- FR-6: la tabella si filtra per stato e si ordina per colonna; su mobile diventa un elenco di card con un menu di ordinamento.
- FR-7: il GR scarica i PDF dei bilanci delle sole Sezioni del suo GR.
- FR-8: dai badge consolidati della Dashboard GR si arriva alla pagina sullo stesso esercizio.

## 5. Fuori scope

- Grafici per area di attività (i dati A–E quasi non esistono) e serie storiche.
- Correzione dei valori anomali (la pagina li segnala soltanto) e nuova analisi dei PDF senza dati.
- Bilancio proprio del GR e sua pagina.
- Export della tabella (CSV/PDF).
- Pagina "Bilancio" della Sezione (resta "in arrivo").

## 6. Design (dal file Claude Design — vincolante)

Token già in `resources/css/theme.css`; nuovi colori (ambra) come token `--ms-*` prima dell'uso.

**Area contenuti desktop:** padding 36 px 40 px 56 px, colonna con gap 24 px.
- Link "← Dashboard" 13 px / 700 / teal; gap 14 px con il blocco titolo.
- Titolo 28 px / 800 / letter-spacing −0.01em; sottotitolo 13,5 px `#8b93a3`. A destra, allineato in basso:
  "Esercizio" 13 px / 700 / `#8b93a3` + gruppo di pulsanti su sfondo bianco, bordo `#eceef1`, raggio 8 px, padding 3 px,
  gap 2 px. Pulsante attivo: sfondo `#17a180`, testo bianco 13,5 px / 800, raggio 6 px, padding 6 px 14 px; inattivo:
  trasparente, `#5a6375` 13,5 px / 700, hover sfondo `#f4f6f8`.
- Card consolidate: 2 colonne, gap 20 px, stesso stile dei badge di bilancio della 10.1 (titolo 15 px / 800, principale
  38 px / 800, secondari 18 px / 700, bordo superiore `#f0f2f5`) ma **senza** link in fondo. Stato vuoto: sfondo
  `#f9fafb`, altezza minima 206 px. Sotto la card (padding 0 4 px, gap 6 px 14 px, 13 px `#6b7486`) la nota "Somma di…"
  e l'eventuale avviso: icona triangolo 14 px tratto `#c77e2a`, testo `#9a5f1c` / 700.
- Card "Copertura": bianca, bordo `#eceef1`, raggio 10 px, padding 20 px 24 px, gap 14 px. Riga titolo: "Copertura"
  15 px / 800, poi numero 22 px / 800 + "su M Sezioni hanno il bilancio con i dati" 13,5 px `#6b7486`; nota a destra
  13 px `#6b7486`. Barra alta 10 px, raggio 99 px, gap 2 px: dati `#17a180`, solo PDF `#e2b47a`, nessuno `#e4e7eb`.
  Legenda 13 px `#5a6375` con quadratini 10 px raggio 3 px e conteggi in grassetto `#3c4557`, gap 8 px 28 px.
- Card "Sezioni del GR": bianca con overflow hidden. Testata padding 16 px 20 px: "Sezioni del GR" 15 px / 800 +
  conteggio 13 px `#8b93a3`; a destra i filtri a pillola (gap 6 px). Pillola attiva: sfondo `#eef7f3`, bordo
  `#9fdcc9`, testo `#128a6c` / 800; inattiva: bianca, bordo `#e4e7eb`, testo `#5a6375` / 700, numero `#8b93a3`, hover
  bordo `#bfe3d6`; 13 px, raggio 99 px, padding 5 px 12 px.
- Griglia colonne della tabella: `minmax(0,1.1fr) 124px 108px repeat(5,minmax(0,1fr)) 32px`, gap 14 px. Intestazione:
  padding 10 px 20 px, sfondo `#f9fafb`, bordi `#eceef1`, 12 px / 800 / maiuscolo / letter-spacing .04em, `#8b93a3`
  (attiva `#128a6c`, freccia `#17a180`; inattiva freccia "↕" `#c4cad3`); colonne numeriche allineate a destra.
- Riga: padding 11 px 20 px, altezza minima 46 px, bordo inferiore `#f0f2f5`, 14 px, hover `#fafbfc`; nome 700.
  Pillole di stato 12 px / 800, raggio 99 px, padding 2 px 9 px: "Dati presenti" (`#eef7f3`/`#128a6c`/`#cfe8de`),
  "Solo PDF" (`#fbf3e8`/`#9a5f1c`/`#f0dcc0`), "Nessun bilancio" (`#f1f3f5`/`#6b7486`/`#e4e7eb`). Fonte 13 px
  `#5a6375`. Numeri a destra, cifre tabulari; negativi `#c0412e` / 700; mancanti "—" `#c4cad3` / 700. Cella da verificare:
  valore 800 su fondo `#fbf3e8` (raggio 5 px, padding 1 px 3 px) con icona, sotto "valore da verificare" 11,5 px / 700
  `#9a5f1c`. Icona PDF 17 px in un pulsante 30×30 raggio 7 px, teal, hover sfondo `#eef7f3`.
- Riga Totale: padding 14 px 20 px, sfondo `#f6fbf9`, bordo superiore `#e3efe9`, "Totale" 800 e nota 12 px
  `#8b93a3`; valori 800.

**Mobile (390 px):** padding 20 px 16 px 32 px, gap 16 px. Titolo 22 px. Selettore esercizio sotto il titolo, a tutta
larghezza in griglia di 5, pulsanti alti 40 px, 14 px. Card consolidate in colonna (padding 18 px, principale 32 px,
secondari 16 px). Copertura con legenda in colonna e nota sotto un filetto. "Sezioni del GR" 17 px / 800; filtri
alti 36 px; menu "Ordina per" alto 40 px. Card Sezione: padding 14 px 16 px, gap 12 px; nome 15 px / 800; valori
etichetta 12 px / valore 15 px / 700 su 2 colonne (gap 10 px 12 px), risultato netto a tutta riga; "Apri PDF" 13,5 px
/ 700 teal con icona 16 px. Card Totale sfondo `#f6fbf9`, bordo `#e3efe9`.

## 7. Considerazioni tecniche

- Sola lettura per i dati; nessuna migrazione, nessun nuovo dato nel datapack.
- Stato in proprietà Livewire con `#[Url]` (`esercizio`, `ordina`, `verso`, `stato`): ricaricare o condividere il link
  ripristina la vista.
- Ordinamento e filtro in PHP sulla collezione già calcolata (al massimo 152 righe per la Lombardia).
- Numeri attesi dal database di sviluppo: GR Toscana 2025 conto economico 11 Sezioni con dati, +90.144 €, stato
  patrimoniale 8 Sezioni; GR Sicilia 2025 SEZ. RAGUSA ricavi e costi segnalati da verificare.
- `npm run build` dopo nuove classi; selettori Playwright come da `CLAUDE.md` della radice.

## 8. Metriche di successo

- Il GR vede consolidato, copertura e Sezioni mancanti senza aprire altre pagine.
- Il caso di Ragusa è segnalato sia nella tabella sia nella card del conto economico.
- Nessun PDF di un altro GR è scaricabile (coperto dai test).

## 9. Domande aperte

- Quando correggere i valori anomali (Ragusa) e chi lo fa: Montagna Servizi o la Sezione?
- In futuro: pagina del bilancio proprio del GR quando ne avremo i dati.
