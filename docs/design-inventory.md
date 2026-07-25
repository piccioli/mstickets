# Inventario del design — Piattaforma Montagna Servizi

Fonte: `Piattaforma Montagna Servizi.dc.html` (mockup applicativo importato in US-004 via MCP
`claude_design`/`DesignSync`, progetto `b41c13f4-8321-4716-be35-295d0bdd9d1e`). Analisi condotta
leggendo per intero il sorgente HTML/JS del mockup (12 schermate condizionali `show.*`, i due file
dati `data/mockdata.js`/`data/archivio_cai.js`, e il file `support.js` che ne guida la navigazione),
oltre agli altri file presenti nello stesso progetto Claude Design (vedi "Scoperta importante"
sotto).

Classificazione basata su §3.1 (moduli M1-M11 in scope) e §3.2 (moduli fuori scope: CRM/Preventivi,
Agile legacy, Geografico, Scadenze, Preferiti, Changelog, Google Calendar) del PRD, più §15.3
(feature già note ed escluse: `Secretariat`/Meetings, sincronizzazione Google Calendar, app
mobile/API pubblica, multi-tenancy, fatturazione/abbonamenti, import incrementale).

**Richiede conferma del committente prima di procedere con la Fase 1** (§0.1 punto 4 del PRD): sia
sulla classificazione sotto, sia sulla scoperta riportata nella sezione seguente.

---

## Scoperta importante — il progetto Claude Design è un brief commerciale, non (solo) un mockup del pannello

Il progetto Claude Design importato in US-004 contiene molto più del file `.dc.html` richiesto da
§8.1. Oltre al mockup applicativo, il progetto include:

- `uploads/TIcket_V2/BRIEF_prototipi_e_presentazione.md` — un brief per realizzare **prototipi HTML
  di vendita** e una **presentazione commerciale** di nuovi servizi a pagamento da offrire alle
  sezioni CAI (Club Alpino Italiano), cliente finale di Montagna Servizi. Il brief descrive un
  abbonamento base (250 €/anno, 200 €/anno Early Bird fino al 30/09/2026) e 6 "servizi avanzati":
  Drive Standard, Verbalizzazione automatica riunioni, Ricerca CAI (RAG), Monitoraggio bandi
  multi-livello, ETS Dashboard, Moduli avanzati (escursioni/rendicontazione/crowdfunding).
- `uploads/TIcket_V2/screenshots/*.jpg` — 9 screenshot reali della piattaforma v1 in produzione
  (`ticket.montagnaservizi.it`, vista cliente "GR Lombardia"): dashboard cliente, nuovo ticket,
  i miei ticket, dettaglio ticket, report mensili, ticket archiviati, bandi, progetti fundraising,
  documentazione. Sono materiale di riferimento UX per il brief commerciale, **non** fanno parte del
  mockup `.dc.html` analizzato sotto.
- `uploads/TIcket_V2/01_Statuti_e_Regolamenti/` — 50 PDF reali (statuto e regolamenti CAI) usati
  come corpus di dati per il prototipo di ricerca RAG.
- Trascrizioni di riunioni commerciali/di scoping per ciascuno dei 6 servizi avanzati.
- `Presentazione Montagna Servizi.dc.html` ed `export/` — la presentazione di vendita compilata e i
  suoi export HTML statici.

**Conseguenza per questo inventario**: il mockup `.dc.html` richiesto da §8.1 è in realtà la base
del **prototipo di vendita** dei 6 servizi avanzati, con innestate sopra le schermate reali del
pannello cliente Orchestrator (dashboard, ticket, nuovo ticket) usate come cornice/contesto. Le 12
schermate del mockup (sezione seguente) vanno quindi lette con questa lente: alcune sono il pannello
cliente reale in scope in questa release (M1/M7), altre sono demo di servizi commerciali non
richiesti da nessun modulo M1-M11 del PRD. Questa distinzione, e l'esistenza stessa del brief
commerciale, non erano note prima di questa story e vanno segnalate esplicitamente al committente.

---

## Schermate del mockup (12 stati `show.*`)

| # | Schermata (nome nel mockup) | Descrizione | Classificazione | Resource/pagina Filament |
|---|---|---|---|---|
| 1 | **Dashboard** (`show.dashboard`) | Vista cliente: card "Informazioni Login" (nome/email/ultimo accesso), card "Documentazione e contatti" (link a documentazione + CTA "Crea un nuovo ticket"), card "Ticket da completare" (conteggio + CTA "Vedi tutti i ticket"), più una sezione "SERVIZI AVANZATI ATTIVI" con 5 card di servizi (righe 2-6 sotto) | **In scope in questa release** per le prime 3 card (M7 §6.7.3 Portale cliente, §8.4 "Area cliente → Dashboard"); la sezione "SERVIZI AVANZATI ATTIVI" è **fuori scope** (nessuno dei 5 servizi è in M1-M11) | Non ancora costruita: sarà una Filament Page dedicata per il ruolo `customer` (§8.4), da realizzare quando la Fase 1+ implementerà il portale cliente. Le card dei servizi avanzati NON vanno riprodotte |
| 2 | **I miei ticket** (`show.ticket`) | Lista ticket con ricerca, colonne MAIN INFO (id/badge stato/assegnato)/TIPO/TITOLO/HISTORY (date + ore effettive), paginazione | **In scope** — M1 Ticketing + M7 portale cliente (§6.7.3), corrisponde alla vista "I miei ticket (cliente)" di §8.5 | `TicketResource` (schema in US-012 di questo PRD; tab/vista cliente e UI da costruire in Fase 1) |
| 3 | **Nuovo ticket** (`show.nuovoTicket`) | Form: Titolo*, Stato (select new/todo/backlog), Documents (allegati, con elenco estensioni ammesse), Richiesta* (editor con toolbar H1/H2/H3/codice/link) | **In scope** — M1 Ticketing (creazione ticket, §6.1.9 campi cliente). **Nota**: il copy dell'AC allegati cita esplicitamente "Audio: ... (per verbalizzazione)" — questo riferimento è un residuo del servizio avanzato "Verbalizzazione riunioni" (voce 5) e **non va portato**: gli allegati ticket in v2 non hanno un caso d'uso di verbalizzazione (§6.1.8 Allegati) | `TicketResource` (create page), schema US-012 |
| 4 | **Drive Standard** (`show.drive`) | Esploratore cartelle (8 cartelle standard: Istituzionali, Riunioni, Pianificazione e budget, Comunicazione, Progetti e collaborazione, Archivio, Gerarchia) + vista "Gerarchia" (commissioni/scuole nidificate) | **Nuova feature (fuori scope)** — servizio "Drive Standard" del brief commerciale (voce 1), nessun modulo M1-M11 lo copre. **Nota di continuità**: `users.drive_url`/`users.drive_budget_url` (§5.2 Identità, già a schema) restano un semplice link esterno mostrato nel portale cliente — non implicano un esploratore di cartelle integrato | Nessuna. Punto di estensione: nessuno esplicito nel PRD oltre al link `drive_url` esistente |
| 5 | **Riunioni e verbali** (`show.riunioni`) | Wizard di convocazione (organo, titolo, data/ora, invitati, responsabile, ODG) + tab "Verbali" con timeline di stati (bozza → in revisione → commentabile → versione definitiva) | **Nuova feature (fuori scope)** — corrisponde a §15.3 "moduli `Secretariat` (Meetings...) dichiarati nell'indice della documentazione v1 e mai realizzati". Servizio "Verbalizzazione automatica riunioni" del brief (voce 2), con integrazione Google Calendar/Meet esplicitamente fuori scope (D12, §3.2) | Nessuna. Nessun punto di estensione dedicato in §15.2: andrebbe progettato ex novo se mai commissionato |
| 6 | **Ricerca CAI (RAG)** (`show.ricerca`) | Ricerca conversazionale con suggerimenti, risposta con citazioni e riferimenti scaricabili, filtro per categoria (statuto/regolamenti/codice di comportamento), filtri categoria dinamici | **Nuova feature (fuori scope)** — servizio "RAG — Ricerca avanzata" del brief (voce 3). **Da non confondere** con la "Ricerca globale sui ticket" richiesta da §8.7 (in scope, keyword-based su id/titolo/richiedente/corpo messaggi, non conversazionale/AI) | Nessuna. Nessun punto di estensione in §15.2 la copre esplicitamente (non è una `email_messages`/`ticket_messages` timeline né un evento di dominio) |
| 7 | **Bandi** (`show.bandi`) | Lista opportunità con ricerca, filtro Livello (Locale/Regionale/Nazionale/Europeo), filtro Tema (Sentieristica/Sentiero Italia CAI/Rifugi/Arrampicata sportiva/Terzo Settore/Formazione/Ambiente), checkbox "Solo in scadenza entro 90 giorni", CTA "Richiedi supporto" per riga | **Parzialmente in scope**: la lista sola-lettura di opportunità (nome, URL ufficiale, fondo di dotazione, scadenza) corrisponde a M6 Fundraising §6.6.1/§6.6.4 (vista cliente, sola lettura). **Fuori scope**: il filtro "Tema" con tassonomia CAI-specifica (non presente nell'enum `TerritorialScope` né nel resto di §5.2 Fundraising) e la CTA "Richiedi supporto" (workflow di richiesta consulenza a pagamento) appartengono al servizio "Monitoraggio bandi multi-livello" del brief (voce 4) | `FundraisingOpportunityResource` per la parte in-scope (schema in US-015 di questo PRD); nessuna resource per filtro tema/CTA supporto |
| 8 | **Dettaglio bando** (`show.bandoDettaglio`) | Scheda opportunità: scadenza, requisiti, CTA "Richiedi supporto di progettazione" | Stessa suddivisione della riga precedente: dettaglio in sola lettura in scope (§6.6.4), CTA di supporto fuori scope | Come sopra |
| 9 | **Progetti in corso** (`show.progetti`) | Lista progetti fundraising: titolo, capofila, stato | **In scope** — M6 Fundraising §6.6.3/§6.6.4 (progetti, vista cliente sola lettura se coinvolto come capofila/partner) | `FundraisingProjectResource` (schema in US-015); UI/resource da costruire in fase successiva |
| 10 | **Dettaglio progetto** (`show.progettoDettaglio`) | Scheda progetto: capofila, link al bando di origine, importi, CTA "Richiedi supporto" | Dettaglio in scope (§6.6.4); CTA "Richiedi supporto" fuori scope (stesso servizio commerciale del punto 7) | Come sopra |
| 11 | **ETS Dashboard** (`show.ets`) | Dashboard di confronto dati RUNTS vs Agenzia Entrate/Ministero Lavoro vs piattaforma CAI, sezione "Ipotesi di consolidamento" bilanci, benchmark tra sezioni/gruppi regionali | **Nuova feature (fuori scope)** — servizio "ETS Dashboard — controllo qualità RUNTS" del brief (voce 5). Nessuna entità `organizations`/`activity_reports` del PRD copre l'integrazione RUNTS o il benchmark tra organizzazioni terze | Nessuna. Nessun punto di estensione in §15.2 |
| 12 | **Escursioni** (`show.escursioni`) | Elenco eventi/escursioni con card, form "Prenota un posto" (nome/cognome, email, tessera CAI, posti) | **Nuova feature (fuori scope)** — servizio "Moduli avanzati" del brief (voce 6, prenotazione eventi). Coerente con §15.3 "moduli avanzati... in lavorazione" e con l'assenza di qualunque entità `bookings`/`events` in §5.2 | Nessuna. Nessun punto di estensione in §15.2 |

### Nota sul menu "Servizi" (badge NUOVO)

Il mockup stesso classifica le voci di navigazione Drive Standard, Riunioni e verbali, Ricerca CAI
(RAG), ETS Dashboard ed Escursioni con un badge "NUOVO" (`nuovo: badgeNovita` in `support.js`),
confermando — indipendentemente dall'analisi rispetto al PRD — che il design le tratta esso stesso
come funzionalità di nuova introduzione rispetto al nucleo Dashboard/Ticket/Bandi/Progetti.

---

## Componenti trasversali del mockup

| Componente | Descrizione | Classificazione | Nota |
|---|---|---|---|
| Header (ricerca globale, notifiche, account) | Barra fissa in alto con logo, campo di ricerca, icone notifiche/account | **In scope** — §8.7 richiede ricerca globale sui ticket; il pannello Filament la implementerà con i propri componenti (global search Filament), non riproducendo l'input decorativo del mockup | Nessuna resource dedicata: comportamento del `Panel` Filament |
| Sidebar a 3 gruppi (`navCliente`/`navBandiProgetti`/`navServizi`) | Navigazione cliente: Dashboard/Nuovo ticket/I miei ticket, poi Bandi/Progetti in corso, poi i 5 servizi avanzati | **Parzialmente in scope**: i primi due gruppi rispecchiano l'Area cliente di §8.4; il terzo gruppo (`navServizi`) è interamente fuori scope | La navigazione Filament per il ruolo `customer` riprenderà i primi due gruppi, omettendo `navServizi` |
| Badge di stato ticket (pillola colorata) | Stile badge status usato nella lista ticket | **In scope** — M1/M2, già catalogato in `docs/design-system.md` (copre 6 dei 12 stati `TicketStatus`; gap sugli altri 6 già segnalato in quel documento) | Vedi `docs/design-system.md` per la mappatura completa |
| Editor "Richiesta" con toolbar (H1/H2/H3/codice/link) | Editor di testo ricco nel form nuovo ticket | **In scope** — §6.1.7 Conversazione (D9), il corpo dei messaggi ticket è strutturato | Componente Filament RichEditor o equivalente, da scegliere in Fase 1 |

---

## Schermate reali v1 trovate nel progetto ma NON presenti nel mockup interattivo

Le seguenti schermate esistono solo come screenshot statici (`uploads/TIcket_V2/screenshots/`) della
piattaforma v1 in produzione, non come stati navigabili del mockup `.dc.html`. Sono comunque in
scope per i moduli indicati (già coperti da user story di schema in questo PRD) e vanno usate come
riferimento UX quando la Fase 1+ costruirà le relative pagine Filament, **in assenza di un mockup
interattivo dedicato** (coerente con §8.3: "se una funzionalità in scope non ha una schermata nel
mockup, usa i pattern Filament di default... annotalo qui"):

| Screenshot | Contenuto osservato | Modulo PRD |
|---|---|---|
| `04_dettaglio_ticket.jpg` | Dettaglio di un singolo ticket (non incluso come stato del mockup interattivo, che si ferma alla lista) | M1 Ticketing |
| `05_report_mensili.jpg` | Report mensili con download PDF | M5 Activity Report |
| `06_ticket_archiviati.jpg` | Ticket completati, con history e ore effettive | M1/M2 Ticketing, Log e time tracking |
| `09_documentazione.jpg` | Documentazione PDF scaricabile | M4 Documentation |

---

## Riepilogo classificazione

- **In scope in questa release** (da costruire nelle fasi successive secondo la roadmap §14): Dashboard
  cliente (card base), I miei ticket, Nuovo ticket, Bandi/Dettaglio bando (sola lettura), Progetti/Dettaglio
  progetto (sola lettura), badge di stato, editor richiesta, header/sidebar (comportamento Filament nativo).
- **Nuova feature (fuori scope)**: Drive Standard, Riunioni e verbali, Ricerca CAI (RAG), ETS Dashboard,
  Escursioni, filtro "Tema" e CTA "Richiedi supporto" sulle schermate Bandi/Progetti. Il dettaglio di cosa
  farebbero e quale punto di estensione le accoglierebbe è in `docs/future-features.md`.
- Nessuna schermata è stata inventata: dove uno stato non esisteva nel mockup (dettaglio ticket, report
  mensili, ticket archiviati, documentazione), si è usato lo screenshot reale del v1 trovato nello stesso
  progetto Claude Design come riferimento, segnalandolo esplicitamente sopra invece di ometterlo o inventare
  un layout.

---

## Checkpoint obbligatorio

Come da §0.1 punto 4 e dalla descrizione di questa fase (§14 Fase 0): **prima di iniziare la Fase 1
(Ticketing core)**, questo documento e il report di `v1:inspect` (US-008) vanno presentati al
committente per una conferma esplicita, in particolare su:

1. la classificazione in scope/fuori scope sopra;
2. la scoperta che il progetto Claude Design importato è (anche) un brief commerciale per servizi
   a pagamento rivolti alle sezioni CAI, con un pricing già definito (250 €/anno, 200 €/anno Early
   Bird) — informazione che eccede lo scope tecnico di questa fase ma che il committente potrebbe
   non aspettarsi di trovare nel materiale di design condiviso per il pannello Orchestrator.
