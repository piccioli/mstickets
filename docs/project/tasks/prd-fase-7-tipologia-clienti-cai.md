# PRD: Fase 7 — Tipologia di cliente CAI (Sezione / Gruppo Regionale / Organo Tecnico-Struttura Operativa)

> Riferimento completo: `PRD-ORCHESTRATOR-V2.md` §14 (Roadmap, Fase 7, riservata a questa fase dopo la
> rinumerazione del 2026-08-28 — il vecchio contenuto "Cutover" è ora Fase 10) e
> `orchestrator/docs/superpowers/specs/2026-08-28-tipologia-clienti-cai-design.md` (design approvato col
> committente in sessione di brainstorming, stessa data). Numerazione story `US-70x` per restare nel range
> riservato a questa fase.

## 1. Introduzione/Overview

Prima fase funzionale dopo la chiusura di Fase 6. Il committente ha chiesto di rappresentare correttamente,
nel modello e in UI, i diversi ruoli istituzionali che un cliente CAI può avere nella gerarchia
dell'associazione: **Sezione** (include le sottosezioni, mai distinte nel dato reale), **Gruppo Regionale**,
**Organo Tecnico Centrale / Struttura Operativa** (un solo tipo: il dato v1 non li distingue mai — prefisso
comune "OTCO/SO") e **Cliente generico** (fallback quando il tipo non è deducibile dal nome).

**Verificato sui dati reali prima di scrivere le story** (596 utenti con ruolo `customer` nel dump v1
corrente): 503 seguono il pattern `<nome> | <Regione>` (con o senza prefisso "C.A.I. SEZ.") → Sezione; ~20
seguono il pattern `GR <Regione>`/`GP <Regione>` → Gruppo Regionale; ~21 seguono il pattern `OTCO/SO <sigla>`
→ Organo Tecnico Centrale/Struttura Operativa; il resto (es. "Cai Centrale", "Montagna Servizi", "Sentiero
Italia CAI - SICAI", "Comune di Quartu Sant'Elena") non rientra in nessun pattern → Cliente generico. Un solo
caso di Sezione con testo dopo `|` vuoto ("Orvieto |"): classificata Sezione con regione non impostata, non
Generico (il fallback a Generico riguarda solo il tipo non deducibile, mai la sola regione mancante).

**Scope confermato col committente in brainstorming (2026-08-28)**: la differenziazione si limita a (1) un
badge/etichetta col tipo (+ regione) visibile su ogni dashboard cliente, e (2) una card sulla dashboard del
Gruppo Regionale con l'elenco delle Sezioni della propria regione. Nessun altro comportamento differenziato
(permessi, contenuti, viste ticket/fundraising) per questa fase.

## 2. Goals

- Ogni cliente esistente (importato dal v1) ha un `customer_type` corretto dedotto automaticamente
  dall'ETL, senza intervento manuale per i pattern noti.
- Un admin può assegnare/correggere tipo e regione di un cliente dalla stessa area del form utente dove già
  assegna il ruolo — nessun meccanismo separato da imparare.
- Ogni dashboard cliente mostra in modo evidente il proprio tipo (e regione, se pertinente).
- Un cliente Gruppo Regionale vede sulla propria dashboard l'elenco delle Sezioni della propria regione.
- Nessuna regressione sul comportamento di Fase 6 (dashboard cliente, navigazione, WorkBoard) per i clienti
  di tipo diverso da Gruppo Regionale.

## 3. User Stories

### US-701: Schema e cataloghi `CustomerType`/`Region`
**Description:** As a developer, ho bisogno di persistere il tipo cliente e la regione di appartenenza per
poterli usare in UI e nell'ETL.

**Acceptance Criteria:**
- [ ] Migrazione additiva su `users`: colonna `customer_type` (string, nullable) e `region` (string,
      nullable).
- [ ] Nuovo enum backed `App\Domain\Identity\Enums\CustomerType`: `Sezione`, `GruppoRegionale`,
      `OrganoTecnicoStrutturaOperativa`, `Generico`. Cast sul model `User`.
- [ ] Nuovo enum backed `App\Domain\Identity\Enums\Region` con le 20 regioni italiane ufficiali (Trentino-Alto
      Adige unificato, non split Trentino/Alto Adige), ognuna con un metodo `label(): string` per la UI (es.
      "Valle d'Aosta", "Friuli-Venezia Giulia"). Cast sul model `User`.
- [ ] `region` è concettualmente pertinente solo per `Sezione`/`GruppoRegionale` (nessun vincolo DB, solo
      convenzione applicativa rispettata da ETL e form — vedi US-702/US-703).
- [ ] Test unit: cast enum funzionano nei due sensi (lettura/scrittura), un utente senza `customer_type`
      resta `null` senza errori.
- [ ] Typecheck passa.
- [ ] Tests pass.

### US-702: Stage ETL `CustomerClassificationStage`
**Description:** As a sistema, all'import dal v1 devo dedurre automaticamente il tipo cliente e la regione
dal nome, per non dover riclassificare manualmente 596 utenti già esistenti.

**Acceptance Criteria:**
- [ ] Nuovo stage `App\Import\Stages\CustomerClassificationStage`, dipendenze `['users',
      'roles_permissions']` (opera solo su utenti con ruolo `customer` già assegnato).
- [ ] Regole di inferenza sul nome, verificate in quest'ordine (il primo pattern che matcha vince):
      1. `/^(GR|GP)\s+(.+)$/ui` → `GruppoRegionale`, regione dal gruppo 2 (normalizzata).
      2. `/^OTCO\s*\/\s*SO\b/ui` → `OrganoTecnicoStrutturaOperativa`, regione `null`.
      3. `/\|\s*(.+)$/u` con testo non vuoto dopo il separatore → `Sezione`, regione dal testo dopo `|`
         (normalizzata). Se il testo dopo `|` è vuoto → `Sezione` con regione `null` (non Generico).
      4. Nessun pattern → `Generico`, regione `null`.
- [ ] Normalizzazione regione: mappa varianti note del dump (maiuscole/minuscole, apostrofi,
      "TRENTINO-ALTO ADIGE"/"ALTO ADIGE" → stesso case enum, "VALLE D'AOSTA"/varianti apostrofo, ecc.) ai
      case dell'enum `Region`. Una stringa non normalizzabile (nessuna nota oggi) logga
      `Log::warning(...)` e lascia `region = null`, mai un'eccezione che blocca l'import.
- [ ] Idempotente: due esecuzioni consecutive sullo stesso dataset non modificano nulla alla seconda
      (stesso pattern diff/update di `OrganizationsStage`).
- [ ] Non tocca `customer_type`/`region` di utenti senza ruolo `customer` (restano sempre `null`).
- [ ] Wiring in `v1:import`: nuovo stage eseguito nell'ordine di dipendenza corretto, riportato nell'output
      del comando come gli altri stage (`letti X, creati Y, aggiornati Z, saltati W`).
- [ ] Verificato sui dati reali importati: 503 Sezione (1 con regione `null`), ~20 GruppoRegionale, ~21
      OrganoTecnicoStrutturaOperativa, resto Generico — numeri esatti confermati su questo dataset in un
      test di integrazione con fixture o sul dump di test ridotto già usato dalla suite ETL.
- [ ] Test unit: ogni pattern (GR/GP, OTCO/SO, sezione con/senza prefisso "C.A.I. SEZ.", sezione senza
      regione, nessun pattern → Generico), normalizzazione regione per le varianti note, idempotenza.
- [ ] Typecheck passa.
- [ ] Tests pass.

### US-703: UI Admin — assegnazione tipo cliente e regione
**Description:** As an admin, voglio assegnare o correggere il tipo cliente e la regione di un utente dallo
stesso punto in cui già ne gestisco il ruolo, per non dover imparare un meccanismo separato.

**Acceptance Criteria:**
- [ ] Due `Select` (`customer_type`, `region`) nella sezione ruoli/permessi di `UserResource` (stesso file
      del form già esistente), `->visible()` solo quando il ruolo selezionato nel form è `customer` (stesso
      pattern reattivo già in uso altrove nel form per campi condizionati dal ruolo).
- [ ] `region` visibile solo quando `customer_type` è `Sezione` o `GruppoRegionale`; nascosto (e azzerato in
      dehydration) per `OrganoTecnicoStrutturaOperativa`/`Generico`.
- [ ] Gated dallo stesso permesso che già governa l'assegnazione ruoli su questo form — nessun nuovo
      permesso nel catalogo.
- [ ] Colonna `customer_type` (badge colorato, stesso componente Filament badge già in uso altrove) aggiunta
      a `UsersTable` per vista rapida/filtro.
- [ ] Test feature: i campi appaiono/spariscono correttamente al cambio di ruolo/tipo nel form; il
      salvataggio persiste `customer_type`/`region` correttamente; un utente senza permesso non vede/non può
      modificare questi campi (riuso della stessa asserzione già in test esistenti per l'assegnazione ruoli).
- [ ] Typecheck passa.
- [ ] Tests pass.
- [ ] Verifica in browser (screenshot Chrome headless) dell'assegnazione tipo/regione su un utente reale.

### US-704: Badge tipo cliente sulla dashboard
**Description:** As a cliente, voglio vedere subito il mio tipo (Sezione, Gruppo Regionale, Organo Tecnico
Centrale / Struttura Operativa, o generico) sulla mia dashboard, per avere conferma di come sono
rappresentato nel sistema.

**Acceptance Criteria:**
- [ ] Badge/etichetta in testa a `CustomerDashboard` (Fase 6) con la label italiana del `customer_type`
      corrente + regione se presente (es. "Sezione — Lombardia", "Gruppo Regionale — Abruzzo", "Organo
      Tecnico Centrale / Struttura Operativa", "Cliente generico").
- [ ] Visibile per **tutti** i tipi, incluso Generico (mostra solo il tipo, senza regione).
- [ ] Test feature: il badge mostra il testo corretto per ciascuno dei 4 tipi, con e senza regione.
- [ ] Typecheck passa.
- [ ] Tests pass.
- [ ] Verifica in browser (screenshot Chrome headless) del badge per un cliente Sezione, uno Gruppo
      Regionale e uno Generico.

### US-705: Card "Sezioni del gruppo regionale" sulla dashboard
**Description:** As a cliente Gruppo Regionale, voglio vedere l'elenco delle Sezioni della mia regione sulla
mia dashboard, per avere una visione d'insieme del territorio che rappresento.

**Acceptance Criteria:**
- [ ] Nuova card "Sezioni del gruppo regionale" su `CustomerDashboard`, visibile **solo** quando
      `customer_type === GruppoRegionale`.
- [ ] Nuovo query object (stesso stile §8.5 già in uso per le viste ticket) che restituisce gli utenti
      `customer_type = Sezione` con la stessa `region` del Gruppo Regionale corrente — **mai** una query
      sull'`organizations`/`organization_user` esistente (concetto distinto, introdotto in Fase 4 per il
      possesso degli Activity Report — non va sovraccaricato con questo significato).
- [ ] Ogni riga: nome sezione, conteggio ticket aperti, link (coerente con lo stile delle altre card della
      dashboard, Fase 6).
- [ ] Stato vuoto esplicito se la regione non ha ancora nessuna sezione classificata, o se il Gruppo
      Regionale ha `region = null` (mai un errore o una sezione vuota silenziosa, stesso principio già
      stabilito in Fase 6).
- [ ] Nessuna card per Sezione/Organo Tecnico Centrale-Struttura Operativa/Generico.
- [ ] Test feature: la card mostra solo le sezioni della stessa regione (mai di un'altra regione anche se
      esistono sezioni classificate altrove); stato vuoto quando pertinente; assente per gli altri 3 tipi.
- [ ] Typecheck passa.
- [ ] Tests pass.
- [ ] Verifica in browser (screenshot Chrome headless) della card popolata con dati reali per un Gruppo
      Regionale con più sezioni nella propria regione.

### US-706: Checkpoint di fine fase — verifica end-to-end e pacchetto di collaudo
**Description:** As a team, prima di considerare la fase conclusa, voglio un test end-to-end che replichi il
flusso completo (import → classificazione → assegnazione admin → dashboard) e un pacchetto di collaudo
aggiornato.

**Acceptance Criteria:**
- [ ] Nuovo test end-to-end in `tests/Feature/EndToEnd/` che copre: import di un dataset con almeno un
      utente per ciascuno dei 4 tipi → verifica `customer_type`/`region` dedotti correttamente → un admin
      corregge manualmente il tipo di un utente → la dashboard del cliente corretto riflette il nuovo tipo.
- [ ] `docs/collaudo/fase-7.php` (manifest di tracciabilità, stesso formato delle fasi precedenti) e
      manuale dettagliato `docs/collaudo/14-fase-7.md`, con un topic per ciascuna user story di questa fase.
- [ ] `php artisan collaudo:verify-manifest 7` passa.
- [ ] `php artisan collaudo:generate 7` genera il PDF, verificato visivamente.
- [ ] `docs/data-model.md`/`docs/architecture.md` (Fase 6) aggiornati per riflettere lo schema/enum nuovi,
      se pertinente.
- [ ] Typecheck passa.
- [ ] Tests pass (suite completa, nessuna regressione sulle fasi precedenti).

## 4. Functional Requirements

- FR-1: `users.customer_type`/`users.region` nullable, popolati solo per ruolo `customer`.
- FR-2: L'ETL classifica automaticamente ogni cliente importato secondo le regole di US-702, in modo
  idempotente.
- FR-3: Un admin può assegnare/correggere tipo e regione dal form `UserResource`, dietro lo stesso permesso
  già usato per l'assegnazione ruoli.
- FR-4: Ogni dashboard cliente mostra il proprio tipo (+ regione se pertinente).
- FR-5: Solo la dashboard di un cliente Gruppo Regionale mostra l'elenco delle Sezioni della propria
  regione, filtrato per `region` esatta.

## 5. Non-Goals (Out of Scope)

- Nessuna distinzione automatica o manuale strutturata fra Organo Tecnico Centrale e Struttura Operativa in
  questa fase (unico valore enum — il dato v1 non la supporta).
- Nessun comportamento differenziato oltre al badge (US-704) e alla card sezioni (US-705): niente permessi,
  contenuti o viste ticket/fundraising diversi per tipo.
- Nessuna gestione runtime del catalogo regioni (enum backed fisso, non una Resource/tabella editabile).
- Nessuna modifica al concetto esistente `organizations`/`organization_user` (Fase 4).

## 6. Design Considerations

Vedi `orchestrator/docs/superpowers/specs/2026-08-28-tipologia-clienti-cai-design.md` per il design completo
approvato, incluse le regole di normalizzazione regione e i numeri verificati sui dati reali.

## 7. Technical Considerations

- Nuovo stage ETL segue lo stile modulare esistente (un file per concetto, `ImportStage` contract).
- Enum backed semplici (`: string`), stesso stile di `UserRole`/`TicketStatus` — nessuna tabella catalogo.
- Il query object per "sezioni del gruppo regionale" resta indipendente da `Organization` (concetti
  distinti, vedi Non-Goals).

## 8. Success Metrics

- 0 utenti con ruolo `customer` e `customer_type = null` dopo un `v1:import` completo sul dump reale.
- Un admin assegna/corregge tipo e regione di un utente in meno di 30 secondi dal form esistente.

## 9. Open Questions

- Se un futuro dump v1 introducesse pattern di nome non ancora visti (es. una vera distinzione OTC/SO),
  andrà rivalutato se separare i due tipi — richiederà una mappatura manuale dedicata, non automatica.
