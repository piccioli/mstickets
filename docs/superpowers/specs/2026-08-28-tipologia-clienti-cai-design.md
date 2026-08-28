# Tipologia di cliente CAI (Sezione / Gruppo Regionale / Organo Tecnico-Struttura Operativa) — Design

**Data**: 2026-08-28
**Contesto**: Fase 7 del roadmap (PRD-ORCHESTRATOR-V2.md §14, rinumerata — il vecchio contenuto "Fase 7 —
Cutover" è ora Fase 10). Prima nuova fase funzionale dopo la chiusura di Fase 6. Richiesta del committente:
rappresentare correttamente, nel modello e in UI, i diversi ruoli istituzionali che un cliente CAI può avere
nella gerarchia dell'associazione.

## 1. Obiettivo

Introdurre una classificazione del cliente (`customer_type`) con quattro valori — **Sezione** (include le
sottosezioni, mai distinte nel dato reale), **Gruppo Regionale**, **Organo Tecnico Centrale / Struttura
Operativa** (unico tipo: il dato v1 reale non li distingue mai, prefisso comune "OTCO/SO") e **Generico**
(fallback quando il tipo non è deducibile) — e una **regione** di appartenenza (solo per Sezione e Gruppo
Regionale), per:

1. Rendere visibile il tipo (e la regione) sulla dashboard cliente (Fase 6, `CustomerDashboard`) per ogni
   cliente.
2. Mostrare sulla dashboard di un cliente Gruppo Regionale l'elenco delle Sezioni della propria regione.
3. Permettere a un admin di assegnare/correggere tipo e regione di un cliente dalla stessa area del form
   utente dove già assegna il ruolo.
4. Dedurre automaticamente tipo e regione per ogni cliente già importato dal v1, in un nuovo stage ETL,
   idempotente come tutti gli altri.

**Non obiettivi** (esplicitamente esclusi da questa fase, confermati col committente):

- Nessun comportamento differenziato oltre ai due punti 1-2 sopra: niente permessi, contenuti, viste ticket
  o fundraising diversi per tipo. Il resto della UI cliente costruita in Fase 6 resta invariato.
- Nessuna distinzione automatica o manuale strutturata fra Organo Tecnico Centrale e Struttura Operativa in
  questa fase: sono un unico valore dell'enum. Se in futuro servisse separarli, richiederà una mappatura
  manuale dedicata (il dato v1 non la supporta).
- Nessuna gestione runtime del catalogo regioni (è un enum backed fisso, coerente con `UserRole`/
  `Permission`/altri cataloghi chiusi del progetto — aggiungerne una richiede un rilascio, non una UI).

## 2. Schema

Migrazione additiva su `users`:

| Colonna | Tipo | Note |
|---|---|---|
| `customer_type` | `string`, nullable | cast a enum backed `App\Domain\Identity\Enums\CustomerType`: `Sezione`, `GruppoRegionale`, `OrganoTecnicoStrutturaOperativa`, `Generico`. `null` per utenti senza ruolo `customer` (staff/admin/manager/developer/fundraising) |
| `region` | `string`, nullable | cast a enum backed `App\Domain\Identity\Enums\Region` (le 20 regioni italiane ufficiali, Trentino-Alto Adige unificato — vedi §3). Valorizzato solo per `Sezione`/`GruppoRegionale`; sempre `null` per `OrganoTecnicoStrutturaOperativa`/`Generico`, e può essere `null` anche per una `Sezione`/`GruppoRegionale` il cui nome non permette di dedurre la regione (§3) |

Nessuna tabella separata (valutata e scartata in fase di brainstorming: over-engineering per due colonne
opzionali legate a un solo ruolo, nessun precedente nel progetto per questo pattern).

`CustomerType`/`Region` sono enum PHP backed semplici (`: string`), nessuna tabella catalogo — stesso stile di
`UserRole`. `Region` espone un metodo `label(): string` per la UI (nome regione in italiano corretto,
es. "Valle d'Aosta", "Friuli-Venezia Giulia").

## 3. Stage ETL: `CustomerClassificationStage`

Nuovo file in `app/Import/Stages/`, dipendenze `['users', 'roles_permissions']` (deve girare dopo
l'assegnazione ruoli, opera solo su utenti con ruolo `customer`). Idempotente: rieseguibile, aggiorna solo se
il valore calcolato differisce da quello già presente (stesso pattern diff/update di `OrganizationsStage`).

Regole di inferenza sul nome (`users.name`), verificate in quest'ordine — il primo pattern che matcha vince:

1. **Gruppo Regionale**: `/^(GR|GP)\s+(.+)$/ui` → `customer_type = GruppoRegionale`, `region` = normalizzazione
   del gruppo 2 (§normalizzazione sotto).
2. **Organo Tecnico Centrale / Struttura Operativa**: `/^OTCO\s*\/\s*SO\b/ui` → `customer_type =
   OrganoTecnicoStrutturaOperativa`, `region = null`.
3. **Sezione**: nome contiene un separatore `|` con del testo non vuoto dopo (`/\|\s*(.+)$/u`, indipendente
   dalla presenza del prefisso "C.A.I. SEZ."/"SEZ.") → `customer_type = Sezione`, `region` = normalizzazione
   del testo dopo `|`. Se il testo dopo `|` è vuoto (unico caso oggi noto: "Orvieto |") → `customer_type =
   Sezione`, `region = null` (confermato col committente: il fallback a Generico riguarda solo il TIPO non
   deducibile, non la regione mancante su un tipo già dedotto).
4. **Nessun pattern** → `customer_type = Generico`, `region = null`.

**Normalizzazione regione**: mappa case-insensitive/accenti/varianti note ai case dell'enum `Region` — es.
"TRENTINO-ALTO ADIGE" e "ALTO ADIGE" → `Region::TrentinoAltoAdige`; "VALLE D'AOSTA"/"VALLE D AOSTA" →
`Region::ValleDAosta`; "EMILIA-ROMAGNA"/"EMILIA ROMAGNA" → `Region::EmiliaRomagna`, ecc. Se una stringa non
normalizzabile comparisse in un futuro dump (nessuna nei dati verificati ora), lo stage logga
`Log::warning('CustomerClassificationStage: regione non riconosciuta', [...])` e lascia `region = null` — mai
un'eccezione che blocca l'import.

Verificato sui dati reali (596 utenti con ruolo `customer` nel dump corrente): 503 Sezione (di cui 1 con
regione non impostata), ~20 GruppoRegionale, ~21 OrganoTecnicoStrutturaOperativa, resto Generico (es. "Cai
Centrale", "Montagna Servizi", "Sentiero Italia CAI - SICAI", "Comune di Quartu Sant'Elena" — entità reali che
non seguono nessuno dei pattern CAI).

## 4. UI Admin — assegnazione

In `UserResource` (`app/Filament/Resources/Users/Schemas/UserForm.php` o equivalente), nella stessa sezione
già esistente per l'assegnazione ruoli:

- Due `Select` (`customer_type`, `region`), `->visible()` solo quando il ruolo selezionato nel form è
  `customer` (stesso pattern reattivo già in uso nel form per altri campi condizionati dal ruolo).
- `region` ulteriormente ristretto/rilevante solo per `customer_type` in
  `[Sezione, GruppoRegionale]` — per `OrganoTecnicoStrutturaOperativa`/`Generico` il campo si nasconde (e si
  azzera in dehydration, stesso principio già stabilito in Fase 1 per campi non pertinenti).
- Nessun nuovo permesso nel catalogo: gated dallo stesso permesso che già governa l'assegnazione ruoli su
  questo form (la distinzione "chi può cambiare il ruolo di un utente" copre già "chi può cambiare la sua
  classificazione cliente").
- Colonna `customer_type` (con badge colorato, stesso componente Filament badge già in uso altrove) aggiunta
  a `UsersTable` per filtro/vista rapida.

## 5. Dashboard cliente (`CustomerDashboard`, Fase 6)

- **Per ogni cliente**: badge/etichetta col tipo cliente (label italiana, es. "Sezione", "Gruppo Regionale",
  "Organo Tecnico Centrale / Struttura Operativa", "Cliente generico") + regione se presente, visibile in
  testa alla pagina, sempre — nessuna eccezione per `Generico` (mostra solo il tipo, senza regione).
- **Solo se `customer_type === GruppoRegionale`**: nuova card "Sezioni del gruppo regionale" con l'elenco
  delle Sezioni (`customer_type = Sezione`) che condividono la stessa `region` del gruppo regionale corrente
  — query scope esplicito (nuovo query object, stesso stile §8.5 già in uso per le viste ticket), MAI
  un'unione implicita con l'`organizations`/`organization_user` esistente (che modella un concetto diverso,
  introdotto in Fase 4 per il possesso degli Activity Report — non va sovraccaricato con questo significato).
  Ogni riga: nome sezione, conteggio ticket aperti, link. Stato vuoto esplicito se la regione non ha ancora
  nessuna sezione classificata (mai visto nei dati reali, ma va comunque gestito secondo il principio già
  stabilito in Fase 6: mai una sezione vuota silenziosa).
- Un Gruppo Regionale con `region = null` (non dovrebbe accadere per i dati reali attuali, ma è comunque
  possibile per un utente creato manualmente senza regione) mostra la card con lo stato vuoto, non un errore.

## 6. Test previsti

- Unit: `CustomerClassificationStage` — ogni pattern (GR/GP, OTCO/SO, sezione con e senza prefisso, sezione
  senza regione, nessun pattern → Generico), normalizzazione regione (varianti note), idempotenza (due
  esecuzioni consecutive non modificano nulla), non tocca utenti non-customer.
- Feature: form `UserResource` — i campi `customer_type`/`region` appaiono solo per ruolo `customer`,
  `region` si nasconde per Organo Tecnico/Generico, salvataggio persiste correttamente.
- Feature: `CustomerDashboard` — badge tipo/regione visibile per ogni tipo; card "Sezioni del gruppo
  regionale" mostra solo le sezioni della stessa regione (mai di un'altra regione), stato vuoto esplicito se
  nessuna; nessuna card per Sezione/Organo Tecnico/Generico.
- Verifica in browser (screenshot Chrome headless) della dashboard per un cliente Sezione, uno Gruppo
  Regionale (con la card elenco sezioni popolata su dati reali) e uno Generico.

## 7. Story previste (bozza, da rifinire in `tasks/prd-fase-7-*.md`)

1. Schema + enum `CustomerType`/`Region`.
2. `CustomerClassificationStage` + wiring in `v1:import`.
3. UI Admin — assegnazione tipo/regione su `UserResource`.
4. Badge tipo cliente su `CustomerDashboard` (tutti i tipi).
5. Card "Sezioni del gruppo regionale" su `CustomerDashboard`.
6. Checkpoint di fine fase — collaudo.
