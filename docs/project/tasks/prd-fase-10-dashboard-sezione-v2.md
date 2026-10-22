# PRD: Fase 10.1 — Dashboard V2 della Sezione CAI

> Fase 10, feature 10.1 (`docs/project/tasks/fase-10-roadmap.md`). Design di riferimento: progetto Claude Design
> "Piattaforma Montagna Servizi", file `Dashboard Sezione CAI V2.dc.html` + componente `Badge Metrica.dc.html`
> (https://claude.ai/design/p/b41c13f4-8321-4716-be35-295d0bdd9d1e?file=Dashboard+Sezione+CAI+V2.dc.html).
> Le misure del design sono riportate per intero in §6: chi implementa non deve accedere a Claude Design.

## 1. Introduzione

Oggi l'utente cliente di una Sezione CAI atterra su `CustomerDashboard`: una pagina ricca (dati CAI/RUNTS,
ticket, documentazione, report, fundraising) che non dà a colpo d'occhio il quadro della Sezione.
La Dashboard V2 diventa la **prima pagina** dell'utente Sezione: l'intestazione con il nome della Sezione e
sei **badge metrica**, ciascuno con un solo numero e il link a una pagina di approfondimento. Fanno eccezione
i due badge di bilancio, che hanno 2 o 3 numeri.

I contenuti della dashboard attuale non spariscono: per l'utente Sezione passano a una pagina a parte,
"Ticket e documenti".

## 2. Obiettivi

- L'utente Sezione, dopo il login, atterra sulla Dashboard V2.
- La pagina mostra solo dati reali già presenti nel database (soci, conto economico, stato patrimoniale).
  Rifugi, sentieri e tappe SICAI risultano "Non disponibile" finché le feature 10.4, 10.6 e 10.8 non li portano.
- Nessun badge mostra mai "0" per un dato mancante.
- Ogni badge porta a una pagina di approfondimento che esiste già (anche solo come pagina "in arrivo").
- UAT mostra la V2 con i dati del datapack attuale, senza nuovi dati da caricare.

## 3. User story

### US-1001: Lettura delle metriche della Sezione
**Descrizione:** come sviluppatore, voglio una classe di sola lettura che calcoli le metriche della dashboard
per una Sezione o Sottosezione, così che la pagina non contenga logica sui dati.

**Criteri di accettazione:**
- [ ] Nuova classe `App\Domain\CaiDirectory\Queries\CaiSectionDashboardMetrics` con un metodo per una
      `CaiSection` e uno per una `CaiSubsection`, che restituiscono un value object (readonly) con: nome, codice
      CAI, regione (etichetta italiana), soci, conto economico (esercizio, ricavi, costi, risultato netto),
      stato patrimoniale (esercizio, patrimonio netto, totale attivo), rifugi, km sentieri, tappe SICAI.
- [ ] Soci = `members_count` della Sezione (o della Sottosezione). **Mai** sommare i soci delle Sottosezioni.
- [ ] Un bilancio "della Sezione" è sia quello collegato direttamente (`cai_financial_statements.cai_section_id`)
      sia quello collegato a una sua `CaiRuntsRegistration`: stessa regola di `CaiSectionFinancialYearQuery`.
- [ ] Conto economico = il bilancio con l'anno più alto fra quelli con `hasIncomeStatementData()`. Ricavi =
      `total_revenues`, costi = `total_expenses`, risultato netto = `net_result`. Ogni valore assente resta `null`:
      il risultato netto **non** si ricalcola da ricavi e costi.
- [ ] Stato patrimoniale = il bilancio con l'anno più alto fra quelli con `hasBalanceSheetData()`, scelto in
      modo indipendente dal conto economico (i due esercizi possono essere diversi). Patrimonio netto = `net_equity`,
      totale attivo = `total_assets`.
- [ ] Se due bilanci della stessa Sezione hanno lo stesso anno, vince quello collegato all'iscrizione RUNTS.
- [ ] Sottosezione: bilanci sempre `null` (le iscrizioni RUNTS sono collegate solo alle Sezioni).
- [ ] Rifugi, km sentieri e tappe SICAI: sempre `null` in questa feature (non esiste ancora una sorgente dati).
- [ ] Test Pest: Sezione completa; Sezione senza bilanci; bilancio con un solo valore; conto economico e
      stato patrimoniale su esercizi diversi; parità di anno (vince RUNTS); bilancio collegato via iscrizione;
      Sottosezione; Sezione con Sottosezioni (soci non sommati).
- [ ] `composer run lint` e `composer run analyse` puliti.

### US-1002: Componente Blade "Badge metrica"
**Descrizione:** come utente Sezione, voglio badge leggibili a colpo d'occhio, così da capire subito lo stato
della mia Sezione.

**Criteri di accettazione:**
- [ ] Componente Blade anonimo `resources/views/components/cai/metric-badge.blade.php` con due varianti:
      `semplice` (etichetta, un numero, unità facoltativa, link) e `bilancio` (titolo, valore principale con
      etichetta, esercizio, 1 o 2 valori secondari, link). Tutta la card è un link `<a>`.
- [ ] Stati della variante `semplice`: con valore; non disponibile (trattino "—" e scritta "Non disponibile",
      fondo grigio chiaro, nessuna ombra, link attivo ma grigio). Hover e click come da §6.
- [ ] Stati della variante `bilancio`: tutti i valori; risultato negativo (valore rosso + etichetta "Disavanzo");
      risultato ≥ 0 (etichetta "Avanzo"); un singolo valore mancante ("—" + "Non disponibile" solo su quel
      valore); nessun dato (card grigia, solo titolo, etichetta principale, "—", "Non disponibile").
      Le etichette Avanzo/Disavanzo valgono solo per il conto economico. Il patrimonio netto negativo si mostra
      con il segno meno, senza etichetta e senza colore rosso.
- [ ] Formati: interi con punto delle migliaia (`1.248`); km con un decimale e virgola (`412,6 km`); euro
      interi arrotondati con punto delle migliaia e simbolo dopo (`186.420 €`); risultato netto con segno
      (`+14.110 €`, `−12.340 €`, con il segno meno tipografico U+2212). Zero si mostra come `0`, mai come non disponibile.
- [ ] Formattazione in una classe PHP testata (es. `App\Support\Formatting\ItalianNumberFormatter`), non
      nella vista.
- [ ] Test Pest della formattazione (interi, km, euro, segno, zero) e del rendering del componente per ogni stato
      (es. `$this->blade(...)` con asserzioni sul testo).
- [ ] `npm run build` eseguito dopo aver aggiunto le classi Tailwind (vedi `app/Filament/CLAUDE.md`).
- [ ] Lint e analyse puliti.

### US-1003: Pagine di approfondimento "in arrivo"
**Descrizione:** come utente Sezione, voglio che ogni badge porti a una pagina, anche se il contenuto arriverà
più avanti, così da non trovare link rotti.

**Criteri di accettazione:**
- [ ] Cinque pagine Filament custom con titoli: "Soci", "Bilancio", "Rifugi di Sezione", "Sentieri di Sezione",
      "Tappe SICAI". Ciascuna mostra un solo avviso: "Questa sezione è in arrivo." e un link "Torna alla dashboard".
- [ ] Una sola vista Blade condivisa (per esempio una classe base astratta con il titolo come unica differenza).
- [ ] `$shouldRegisterNavigation = false`: si raggiungono solo dai badge.
- [ ] `canAccess()`: solo utenti con ruolo customer e `customer_type = Sezione`.
- [ ] Test Pest: un utente Sezione vede ciascuna pagina (200); un utente staff e un cliente di altro tipo ricevono 403.
- [ ] Lint e analyse puliti.

### US-1004: Pagina Dashboard V2 della Sezione
**Descrizione:** come utente che gestisce una Sezione, voglio una dashboard semplice con i numeri chiave della
mia Sezione.

**Criteri di accettazione:**
- [ ] Nuova pagina Filament `App\Filament\Pages\SectionDashboard`, titolo di navigazione "Dashboard", icona
      home, gruppo "Area cliente", in testa al gruppo.
- [ ] `canAccess()`: solo utenti con ruolo customer e `customer_type = Sezione`.
- [ ] Intestazione: nome della Sezione (28 px, extra-bold) e, sotto, "Codice CAI {codice} · {regione}".
- [ ] *(Aggiunta del 2026-10-22, design aggiornato.)* Nel sottotitolo, dopo codice e regione, una pillola "Terzo Settore:
      iscritto" (verde) se la Sezione ha un'iscrizione RUNTS collegata o `runts_presence_status = Registered`, altrimenti
      "Terzo Settore: non iscritto" (grigia), anche quando la verifica è in timeout o mai eseguita (scelta del committente).
      Nessuna pillola per Sottosezioni e utenti senza Sezione.
- [ ] Il link di ogni badge riporta il nome della pagina di destinazione ("Bilancio", "Soci", "Rifugi di Sezione",
      "Sentieri di Sezione", "Tappe SICAI"), come nel design.
- [ ] Riga 1: due badge `bilancio` affiancati (Conto economico, Stato patrimoniale), link alla pagina "Bilancio".
      Il conto economico mostra il risultato netto come valore principale e Ricavi/Costi come secondari. Lo
      stato patrimoniale mostra il patrimonio netto come principale e il totale attivo come secondario. Sotto il
      principale compare "Esercizio {anno}", e non compare se manca il dato.
- [ ] Riga 2: quattro badge `semplice`: Soci → "Soci"; Rifugi → "Rifugi di Sezione"; Sentieri (km) →
      "Sentieri di Sezione"; Tappe SICAI → "Tappe SICAI".
- [ ] Utente collegato a una Sottosezione (nessuna `CaiSection` con il suo `user_id`, ma una `CaiSubsection`):
      stessa pagina con nome, codice e soci della Sottosezione; bilanci, rifugi, sentieri e SICAI "Non disponibile".
      La regione si legge dalla Sezione padre.
- [ ] Utente Sezione senza Sezione né Sottosezione collegata: intestazione con il nome dell'utente e un avviso
      "Nessuna Sezione CAI collegata al tuo account: contatta Montagna Servizi.", con tutti i badge "Non disponibile".
- [ ] Responsive: desktop 2 + 4 colonne; mobile (< 640 px) i badge di bilancio in colonna e quelli semplici su 2 colonne.
- [ ] Test Livewire: valori visibili per una Sezione completa; stato tipico (soci e conto economico sì, il resto
      no); Sezione vuota; Sottosezione; utente senza Sezione; i link dei badge puntano alle pagine di US-1003;
      403 per staff e per clienti di altro tipo.
- [ ] Verifica in browser (Playwright, vedi `CLAUDE.md` della radice) con screenshot desktop 1440 px e mobile
      390 px, su una Sezione reale del database di sviluppo con conto economico 2025.
- [ ] Lint e analyse puliti.

### US-1005: Atterraggio sulla V2 e pagina "Ticket e documenti"
**Descrizione:** come utente Sezione, voglio atterrare sulla V2 dopo il login e ritrovare ticket e documenti in
una pagina dedicata.

**Criteri di accettazione:**
- [ ] `App\Filament\Pages\Dashboard::mount()`: un customer con `customer_type = Sezione` viene reindirizzato a
      `SectionDashboard`; ogni altro customer resta reindirizzato a `CustomerDashboard` come oggi.
- [ ] Per l'utente Sezione, `CustomerDashboard` diventa "Ticket e documenti": stessa pagina e stessi blocchi
      (dati CAI/RUNTS, pulsanti di sincronizzazione, ticket, documentazione, report, fundraising), con
      etichetta di navigazione e titolo "Ticket e documenti", icona diversa dalla home, subito dopo
      "Dashboard" nel gruppo "Area cliente". Per gli altri tipi di cliente (Gruppo Regionale, Organo
      Tecnico/Struttura Operativa, nessun tipo) etichetta, titolo, gruppo e posizione restano identici a oggi.
- [ ] Nessuna modifica alle autorizzazioni di `CustomerDashboard`.
- [ ] Test esistenti (`DashboardTest`, `CustomerDashboardTest`, checkpoint end-to-end delle Fasi 6-9) aggiornati
      solo dove si aspettano il vecchio atterraggio dell'utente Sezione, e verdi.
- [ ] Test nuovi: atterraggio Sezione → V2; atterraggio Gruppo Regionale → `CustomerDashboard`; etichetta
      "Ticket e documenti" solo per l'utente Sezione.
- [ ] Verifica in browser: login come utente Sezione → atterra sulla V2; la sidebar mostra "Dashboard" e "Ticket
      e documenti"; controllare l'intera sidebar per quel ruolo.
- [ ] Lint e analyse puliti.

### US-1006: Collaudo, note per gli agenti e roadmap
**Descrizione:** come committente, voglio che la feature sia tracciata nel collaudo e verificabile su UAT.

**Criteri di accettazione:**
- [ ] Nuovo `docs/collaudo/fase-10.php` con l'argomento "Dashboard V2 della Sezione (10.1)", test numerati
      `F10-01…` collegati ai test reali di US-1001..US-1005; `php artisan collaudo:verify-manifest 10` passa.
- [ ] Test end-to-end `tests/Feature/EndToEnd/Fase10DashboardSezioneEndToEndTest.php`: utente Sezione con dati
      → login → atterraggio V2 → valori → click su un badge → pagina "in arrivo". Va nel manifest con ID dedicati.
- [ ] Manuale narrativo e PDF di collaudo **non** si scrivono ora: si fanno a fine Fase 10.
- [ ] `docs/project/tasks/fase-10-roadmap.md`: riga 10.1 con il link a questo PRD e stato "in sviluppo".
      Diventa "su UAT" dopo la verifica sul server.
- [ ] `app/Domain/CaiDirectory/CLAUDE.md` (e `app/Filament/CLAUDE.md` se serve): regole di scelta
      dell'esercizio, niente somma dei soci, Sottosezione senza bilancio, componente badge riusabile per la
      Dashboard GR (10.2).
- [ ] Lint, analyse e `--filter=Cai` / `--filter=Dashboard` verdi (con l'isolamento dal database di sviluppo).

## 4. Requisiti funzionali

- FR-1: dopo il login un customer `Sezione` atterra su `SectionDashboard`; gli altri customer su `CustomerDashboard`.
- FR-2: la Dashboard V2 mostra intestazione, 2 badge di bilancio e 4 badge semplici, nell'ordine di US-1004.
- FR-3: un dato `null` si mostra sempre come "—" + "Non disponibile", mai come 0. Un dato uguale a 0 si mostra come 0.
- FR-4: il conto economico e lo stato patrimoniale usano ciascuno l'ultimo esercizio che ha almeno un loro
  valore, con l'anno mostrato nella card.
- FR-5: il risultato netto ha il segno, l'etichetta Avanzo (≥ 0) o Disavanzo (< 0) e il colore rosso se negativo.
- FR-6: ogni badge è interamente cliccabile e porta alla sua pagina di approfondimento, anche quando il dato non
  è disponibile.
- FR-7: le cinque pagine di approfondimento esistono, sono accessibili solo all'utente Sezione e mostrano
  "Questa sezione è in arrivo.".
- FR-8: l'utente Sezione ritrova tutti i blocchi della dashboard attuale nella pagina "Ticket e documenti".
- FR-9: per i clienti non Sezione nulla cambia.

## 5. Fuori scope

- Contenuto delle pagine di approfondimento (Soci, Bilancio, Rifugi, Sentieri, Tappe SICAI): arriva con le
  rispettive feature della Fase 10.
- Dati di rifugi, sentieri e tappe SICAI.
- Dashboard V2 del Gruppo Regionale (feature 10.2) e di qualunque altro tipo di cliente.
- Somma dei soci delle Sottosezioni e storico dei soci.
- Grafici, confronti fra esercizi, selettore dell'esercizio.
- Modifiche alle autorizzazioni esistenti, alla sincronizzazione CAI/RUNTS o al datapack.
- Manuale narrativo e PDF di collaudo (a fine Fase 10).

## 6. Design (dal file Claude Design — vincolante)

Token del pannello già in `resources/css/theme.css` / `docs/design-system.md`: teal `#17a180`, Nunito Sans,
sfondo pagina `#f4f6f8`, card `#ffffff`. Se un colore sotto non esiste come token `--ms-*`, aggiungerlo a
`theme.css` prima di usarlo (regola di `app/Filament/CLAUDE.md`).

**Layout desktop (area contenuti):** padding 36 px 40 px 56 px, colonna con gap 28 px. Intestazione: nome 28 px /
800 / letter-spacing −0.01em / `#3c4557`; sotto 13,5 px `#8b93a3`, gap 4 px. Griglia bilancio 2 colonne, gap
20 px, card alte almeno 248 px. Griglia semplici 4 colonne, gap 20 px, card alte almeno 164 px.
**Mobile (390 px):** padding 24 px 16 px 32 px, gap 16 px; nome 22 px; badge di bilancio in colonna; badge
semplici su 2 colonne, gap 12 px.

**Pillola Terzo Settore (sottotitolo):** sottotitolo in flex con gap 10 px (mobile 8 px, 13 px). Pillola 11,5 px / 800 / raggio
99 px / padding 1 px 9 px. Iscritto: sfondo `#eef7f3`, testo `#128a6c`, bordo `#cfe8de`. Non iscritto: sfondo `#f1f3f5`,
testo `#6b7486`, bordo `#e4e7eb`.

**Badge semplice — con valore:** sfondo `#ffffff`, bordo 1 px `#eceef1`, raggio 10 px, ombra `0 1px 2px
rgba(30,50,40,.05)`, padding 20 px 22 px. Etichetta 14 px / 700 / `#5a6375`. Numero 38 px / 800 / line-height
1.1 / cifre tabulari / `#3c4557`, margin-top 10 px; unità (`km`) 16 px / 700 / `#8b93a3` affiancata in baseline
con gap 6 px. Link in fondo (margin-top auto, padding-top 18 px): 13 px / 700 / `#17a180`, testo + "→" con gap 6 px.
**Hover:** bordo `#bfe3d6`, ombra `0 6px 18px rgba(23,161,128,.12)`, `translateY(-2px)`, link `#0f7d63`
sottolineato, freccia `translateX(3px)`. **Active:** `translateY(0)`, sfondo `#f6fbf9`, ombra di riposo.
Transizioni 0,18 s ease-out su ombra, bordo, transform e sfondo.
**Badge semplice — non disponibile:** sfondo `#f9fafb`, nessuna ombra, etichetta `#8b93a3`; "—" 38 px / 800 /
`#c4cad3`; "Non disponibile" 13 px `#8b93a3` margin-top 2 px; link `#8b93a3`; hover solo bordo `#dfe3e8`.

**Badge bilancio — con dati:** come il semplice ma padding 22 px 24 px. Titolo 15 px / 800 / `#3c4557`. Riga
etichetta principale (margin-top 14 px, gap 8 px): 12,5 px / 700 / `#8b93a3` + pill facoltativa 10,5 px / 800 /
maiuscolo / letter-spacing .04em / raggio 99 px / padding 1 px 8 px. "Avanzo": sfondo `#eef7f3`, testo `#128a6c`,
bordo `#cfe8de`. "Disavanzo": sfondo `#fdf0ee`, testo `#b83c2a`, bordo `#f4d3cd`. Valore principale 38 px / 800 /
line-height 1.15 / cifre tabulari, `#3c4557` (negativo: `#c0412e`), margin-top 4 px. Esercizio 12,5 px `#8b93a3`.
Secondari: griglia 2 colonne, gap 16 px, bordo superiore 1 px `#f0f2f5`, margin-top 18 px, padding-top 14 px;
etichetta 12,5 px / 700 / `#8b93a3`, valore 18 px / 700 / cifre tabulari / `#3c4557`. Mancante: "—" 18 px
`#c4cad3` + "Non disponibile" 12,5 px.
**Badge bilancio — nessun dato:** sfondo `#f9fafb`, nessuna ombra, titolo `#8b93a3`, etichetta principale
`#a8b0bd`, "—" 38 px `#c4cad3`, "Non disponibile", link grigio.

## 7. Considerazioni tecniche

- Seguire `app/Domain/CLAUDE.md`: niente logica nei modelli o nelle viste, sola lettura (nessuna Action, perché
  non ci sono mutazioni).
- Nessuna query N+1: la pagina carica una Sezione, i suoi bilanci (diretti + via iscrizione) e nient'altro.
- Riutilizzo previsto: il componente badge e la classe di formattazione serviranno alla Dashboard GR (10.2).
- Test: sintassi Pest; `->set('data.campo', …)` invece di `->fillForm()`; isolamento dal database di sviluppo
  come da `CLAUDE.md` della radice.
- Dati UAT: soci e bilanci sono già nello snapshot del datapack (Fase 9), quindi non serve un nuovo datapack. A
  fine feature bastano merge su `develop` → deploy UAT e una verifica con un utente Sezione reale.
- Dato di sviluppo: 505 Sezioni hanno un utente collegato, 166 hanno almeno un conto economico, poche hanno lo
  stato patrimoniale. Nessuna Sottosezione ha oggi un utente proprio (il caso va comunque gestito e testato).

## 8. Metriche di successo

- Un utente Sezione vede i numeri chiave della propria Sezione senza scorrere la pagina su desktop.
- Nessun "0" mostrato per un dato mancante (coperto dai test).
- Nessun link dei badge rotto (coperto dai test).

## 9. Domande aperte

- Il `members_count` CAI di una Sezione include già i soci delle Sottosezioni? Per ora mostriamo il valore così
  com'è. Da chiarire con il CAI quando si farà la pagina "Soci".
