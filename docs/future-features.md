# Nuove feature trovate nel design — non implementate (§15)

Per ognuna delle funzionalità classificate come "nuova feature (fuori scope)" in
`docs/design-inventory.md`: cosa sembra fare, quali entità toccherebbe, e quale punto di estensione
della v2 la accoglierebbe (§15.2). Nessuna tabella, modello, resource, rotta o campo per queste
feature è stata creata in questa fase (§15.1 punto 3).

## Drive Standard — gestione documentale

- **Cosa fa**: espone ai clienti un esploratore di cartelle Google Drive con una struttura standard
  a 8 cartelle (Istituzionali, Riunioni, Pianificazione e budget, Comunicazione, Progetti e
  collaborazione, Archivio, Gerarchia) uguale per ogni sezione CAI, con vista aggiuntiva a
  gerarchia (commissioni/scuole nidificate).
- **Entità che toccherebbe**: nessuna nuova tabella necessaria per il solo link (già coperto da
  `users.drive_url`/`users.drive_budget_url`, §5.2 Identità); un esploratore di cartelle vero
  richiederebbe invece un'integrazione con Google Drive API (credenziali OAuth, cache della
  struttura cartelle) — fuori portata di questo schema.
- **Punto di estensione**: nessuno esplicito in §15.2. Il campo `drive_url` esistente resta il solo
  aggancio: un'eventuale evoluzione partirebbe da lì, non da una migrazione nuova.

## Riunioni e verbali — verbalizzazione automatica

- **Cosa fa**: wizard di convocazione riunione (organo, data/ora, invitati, ODG) con integrazione
  Google Calendar/Meet, trascrizione automatica il giorno dopo e bozza di verbale generata con AI,
  con un flusso di stati (bozza → in revisione → commentabile dai partecipanti → versione
  definitiva approvata al consiglio successivo).
- **Entità che toccherebbe**: nuove tabelle per riunioni/convocazioni, verbali e relativo stato;
  integrazione con Google Calendar (esplicitamente fuori scope, D12, §3.2) e con un servizio di
  trascrizione/AI.
- **Punto di estensione**: nessuno dedicato in §15.2. Corrisponde ai moduli `Secretariat` (Meetings)
  dichiarati nell'indice della documentazione v1 e mai realizzati (§15.3): se mai commissionato, è
  un modulo nuovo (M12+), non un'estensione di un modulo esistente.

## Ricerca CAI (RAG) — ricerca conversazionale sull'archivio

- **Cosa fa**: ricerca in linguaggio naturale su tre ambiti (statuto/regolamenti CAI centrale,
  verbali delle riunioni, knowledge base costruita da webinar), con risposta citata e riferimenti
  scaricabili.
- **Entità che toccherebbe**: un indice vettoriale/full-text sui documenti (statuto, regolamenti,
  verbali) e un servizio di generazione risposte (LLM) — nessuna delle tabelle di §5.2 lo prevede.
- **Punto di estensione**: nessuno in §15.2. Da non confondere con la ricerca globale sui ticket di
  §8.7 (in scope, keyword-based, non conversazionale). Se mai commissionato, andrebbe progettato
  come servizio indipendente, non innestato su `documentation_pages`.

## Monitoraggio bandi multi-livello (filtro Tema + richiesta supporto)

- **Cosa fa**: evoluzione della schermata Bandi con filtri per territorio (comune/provincia/
  regione/nord-centro-sud) e per tema CAI-specifico (sentieristica, Sentiero Italia CAI, rifugi,
  arrampicata sportiva, terzo settore, formazione, ambiente), più una richiesta di supporto di
  progettazione a pagamento (valutazione ammissibilità, progettazione/submission, esecuzione/
  rendicontazione) collegata al bando.
- **Entità che toccherebbe**: un campo/tassonomia "tema" su `fundraising_opportunities` (non
  presente in §5.2) e una nuova entità "richiesta di supporto" collegata a opportunità/progetto e
  utente richiedente.
- **Punto di estensione**: la parte di **filtro territoriale semplice** è già coperta dall'enum
  `TerritorialScope` esistente (§5.2 Fundraising, US-015). La **richiesta di supporto** potrebbe in
  futuro riusare "crea un ticket dall'opportunità" (§6.6.1, già previsto come azione in scope) invece
  di una nuova entità dedicata — è il punto di estensione più vicino già presente nel modello dati.

## ETS Dashboard — controllo qualità RUNTS

- **Cosa fa**: interroga il RUNTS (Registro Unico Nazionale del Terzo Settore) e confronta i dati
  con Agenzia delle Entrate/Ministero del Lavoro e con la piattaforma pubblica CAI, evidenziando
  differenze; analizza i bilanci caricati sul RUNTS con benchmark tra sezioni/gruppi regionali e
  ipotesi di consolidamento secondo le voci standard dei bilanci del terzo settore.
- **Entità che toccherebbe**: integrazione con l'API/scraping del RUNTS, storicizzazione dei dati di
  bilancio per organizzazione, motore di confronto/benchmark — nessuna delle tabelle di
  `organizations`/`activity_reports` (§5.2) lo prevede.
- **Punto di estensione**: nessuno in §15.2. Se mai commissionato, è un modulo nuovo che
  userebbe `organizations` come ancora ma richiederebbe tabelle proprie per i dati RUNTS/bilanci.

## Escursioni — prenotazione eventi

- **Cosa fa**: elenco eventi/escursioni con form di prenotazione (nome/cognome, email, tessera CAI,
  posti disponibili) e avviso ai responsabili alla prenotazione.
- **Entità che toccherebbe**: nuove tabelle `events`/`bookings` (o simili), nessuna delle quali è in
  §5.2.
- **Punto di estensione**: coerente con §15.3 "moduli avanzati... in lavorazione" (insieme a
  rendicontazione sentieristica automatica e crowdfunding, citati nello stesso brief ma non
  prototipati). Un'eventuale notifica "avviso ai responsabili" riuserebbe in futuro il sistema di
  **canali di notifica astratti** già previsto come punto di estensione (§15.2), se il modulo venisse
  commissionato.
