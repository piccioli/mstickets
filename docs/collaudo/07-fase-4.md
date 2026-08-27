# Fase 4 (Tag/commesse, Documentation, Activity Report/Organizations) — Casi di test dettagliati

> Torna a [`README.md`](README.md) · Istruzioni generali: [`00-istruzioni-generali.md`](00-istruzioni-generali.md) · Matrice di tracciabilità: [`01-matrice-tracciabilita.md`](01-matrice-tracciabilita.md)

42 casi di test (F4-01 — F4-42) su 4 argomenti. Prima di eseguire un test, leggi le convenzioni comuni
in `00-istruzioni-generali.md` (in particolare le sezioni 9 "Credenziali" e 12 "Prerequisiti generali").
A differenza di Fase 3 (un argomento per user story), qui gli argomenti sono raggruppati per area
funzionale del PRD: §6.3 Tag/commesse (US-401..US-403), §6.4 Documentation (US-404..US-406), §6.5
Activity Report/Organizations (US-407..US-410), più un ultimo argomento dedicato al checkpoint di fine
fase (US-411, F4-40..F4-42), che replica in automatico — con dati sintetici ma rappresentativi — la
verifica manuale eseguita su dati reali importati da v1 (`v1:import --anonymize`) in ambiente Docker
durante lo sviluppo di questa story (vedi `scripts/ralph/progress.txt`, sezione US-411).

## Tag / commesse — modello SAL, azione "crea commessa", vista elenco (US-401..US-403)

### F4-01 — sal() è null quando la commessa non ha ore stimate

**Obiettivo**
Verificare che `Tag::sal()` restituisca `null` (invece di dividere per zero o restituire un valore
fuorviante) quando la commessa non ha `estimated_hours` valorizzato, così che la UI possa mostrare un
placeholder invece di una percentuale priva di senso.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-401, AC "SAL = ore lavorate / ore stimate × 100, null se
  ore stimate assenti/zero".
- Test automatico: `tests/Unit/Domain/Tags/TagSalTest.php` — `sal() is null when estimated_hours is null`.
- File/componente applicativo rilevante: `App\Domain\Tags\Models\Tag::sal()`.
- Test correlato: F4-02, F4-09.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un `Tag` creato senza `estimated_hours`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sal\(\) is null when estimated_hours is null"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
`$tag->sal()` restituisce `null`.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-02 — sal() somma i minuti lavorati di tutti i ticket collegati e arrotonda a 2 decimali

**Obiettivo**
Verificare che `Tag::sal()` calcoli il SAL sommando `worked_minutes` di TUTTI i ticket collegati alla
commessa (non solo l'ultimo/il primo) e arrotondi il risultato a 2 decimali, con la formula
`(ore lavorate / ore stimate) × 100`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-401, AC "SAL calcolato dalla somma dei minuti lavorati di
  ogni ticket collegato, arrotondato a 2 decimali".
- Test automatico: `tests/Unit/Domain/Tags/TagSalTest.php` — `sal() rounds to two decimal places`.
- File/componente applicativo rilevante: `App\Domain\Tags\Models\Tag::sal()`, `Tag::workedMinutes()`.
- Test correlato: F4-01, F4-08.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un `Tag` con `estimated_hours = 3`, un ticket collegato con `worked_minutes = 100`
(atteso: `100/60 = 1.6666…` ore lavorate, SAL = `1.6666…/3 * 100 = 55.56`).

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "sal\(\) rounds to two decimal places"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
`$tag->sal()` restituisce `55.56`.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-03 — La commessa risulta chiusa solo quando ogni ticket collegato è rilasciato o completato

**Obiettivo**
Verificare che `Tag::isClosed()` restituisca `true` solo quando OGNI ticket collegato alla commessa è
in stato `released` o `done`, `false` se almeno un ticket è ancora in un altro stato o se la commessa
non ha ticket.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-401, AC "commessa chiusa quando tutti i ticket collegati
  sono rilasciati/completati".
- Test automatico: `tests/Unit/Domain/Tags/TagSalTest.php` — `isClosed() is true when every linked
  ticket is released or done`.
- File/componente applicativo rilevante: `App\Domain\Tags\Models\Tag::isClosed()`.
- Test correlato: F4-08.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un `Tag` con due ticket collegati, entrambi in stato `Done`/`Released`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "isClosed\(\) is true when every linked ticket is released or done"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
`$tag->isClosed()` restituisce `true`.

**Controlli negativi**
- `tests/Unit/Domain/Tags/TagSalTest.php` copre anche il caso opposto ("is false when at least one
  linked ticket is not released or done") e il caso senza ticket collegati.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-04 — Creare una commessa da un ticket precompila le ore stimate dal ticket e li collega

**Obiettivo**
Verificare che l'Action di dominio `CreateTagFromTicket::run()` crei una nuova commessa precompilando
`estimated_hours` dal ticket sorgente (quando non esplicitamente sovrascritto) e colleghi il ticket alla
commessa appena creata via la pivot `ticket_tag`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-402, AC "azione 'crea commessa' precompila nome/ore stimate
  dal ticket, collega il ticket alla commessa".
- Test automatico: `tests/Feature/Domain/Tags/Actions/CreateTagFromTicketTest.php` — `creating a tag
  from a ticket precompiles estimated_hours from the ticket and links it`.
- File/componente applicativo rilevante: `App\Domain\Tags\Actions\CreateTagFromTicket`.
- Test correlato: F4-05, F4-06.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un ticket con `estimated_hours` valorizzato.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "creating a tag from a ticket precompiles estimated_hours from the ticket and links it"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La commessa creata ha le stesse `estimated_hours` del ticket ed è collegata ad esso in `ticket_tag`.

**Controlli negativi**
- `CreateTagFromTicketTest.php` copre anche il caso di override esplicito e il caso `estimated_hours`
  assente su ticket e input.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-05 — Lo slug generato riceve un suffisso numerico in caso di collisione, incluse le commesse soft-deleted

**Obiettivo**
Verificare che `Tag::uniqueSlug()` generi uno slug univoco aggiungendo un suffisso numerico progressivo
quando lo slug base collide con una commessa esistente, controllando anche le righe soft-deleted (mai un
duplicato invisibile solo perché la commessa collidente è stata cancellata).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-402, AC "slug univoco lato DB, incluse le righe soft-deleted".
- Test automatico: `tests/Feature/Domain/Tags/Actions/CreateTagFromTicketTest.php` — `the generated slug
  gets a numeric suffix when it collides with an existing tag, including soft-deleted ones`.
- File/componente applicativo rilevante: `App\Domain\Tags\Models\Tag::uniqueSlug()`.
- Test correlato: F4-04.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Due commesse con lo stesso nome/slug base, una delle quali soft-deleted.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "the generated slug gets a numeric suffix when it collides with an existing tag, including soft-deleted ones"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il nuovo slug ha un suffisso numerico progressivo e resta univoco anche contando le righe soft-deleted.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-06 — Un utente con tag.create può trasformare un ticket in commessa dalla pagina di visualizzazione

**Obiettivo**
Verificare che un membro dello staff con il permesso `tag.create` possa usare l'azione Filament "Crea
commessa" dalla pagina di visualizzazione di un ticket, che la modale precompili nome/ore stimate dal
ticket e che la commessa venga creata e collegata al submit.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-402, AC "azione 'crea commessa' visibile su
  ViewTicket/EditTicket, gated su tag.create".
- Test automatico: `tests/Feature/Filament/Ticketing/CreateCommessaActionTest.php` — `a user with
  tag.create can turn a ticket into a commessa from the view page`.
- File/componente applicativo rilevante: `App\Filament\Resources\Tickets\Support\CreateCommessaAction`,
  `App\Filament\Resources\Tickets\Pages\ViewTicket`.
- Test correlato: F4-04, F4-07.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Utente Developer `lorena.sava@montagnaservizi.com` con `tag.create` (verificare in
  `RolePermissionSeeder`; se assente, usare Admin `info@montagnaservizi.com`).
- Un ticket esistente con ore stimate valorizzate.

**Dati di test**
Un ticket reale con `estimated_hours` valorizzato (es. un ticket "helpdesk" del catalogo UAT).

**Stato iniziale**
Nessuna commessa collegata al ticket scelto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer/Admin | `lorena.sava@montagnaservizi.com` / `info@montagnaservizi.com` | Login riuscito |
| 2 | Apri il ticket scelto (`Ticket` → dettaglio) | Ticket con ore stimate valorizzate | Pagina di dettaglio caricata, azione "Crea commessa" visibile |
| 3 | Esegui l'azione "Crea commessa" | — | Modale con "Nome" e "Ore stimate" già precompilati dal ticket |
| 4 | Conferma l'invio della modale | — | Notifica "Commessa creata" |
| 5 | Verifica in "Commesse" che la nuova commessa esista e sia collegata al ticket | — | La commessa compare nell'elenco con lo stesso nome/ore stimate |

**Risultato finale atteso**
La commessa è creata con nome/ore stimate del ticket ed è collegata ad esso.

**Controlli negativi**
Vedi F4-07 (nessun accesso all'azione senza `tag.create`).

**Evidenze da acquisire**
- Screenshot della modale precompilata.
- Screenshot dell'elenco commesse con la nuova riga.

**Criterio di superamento**

PASS: la commessa è creata correttamente e collegata al ticket.
FAIL: la modale non precompila i valori attesi, o la commessa non viene creata/collegata.
BLOCKED: ambiente UAT non raggiungibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Se la commessa creata è solo di test, rimuoverla (soft-delete) al termine del test, a meno che non sia
già una commessa reale attesa nel catalogo.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-07 — Un utente senza tag.create non vede l'azione "crea commessa"

**Obiettivo**
Verificare che un membro dello staff privo del permesso `tag.create` non veda l'azione "Crea commessa"
sulla pagina di visualizzazione del ticket.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-402, AC "azione gated su tag.create".
- Test automatico: `tests/Feature/Filament/Ticketing/CreateCommessaActionTest.php` — `a user without
  tag.create cannot see the create commessa action`.
- File/componente applicativo rilevante: `App\Filament\Resources\Tickets\Support\CreateCommessaAction`.
- Test correlato: F4-06.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Fundraising (o altro ruolo privo di `tag.create` per catalogo permessi reale)

**Prerequisiti**
- Utente Fundraising `sara.mariani@montagnaservizi.com` (privo di `tag.create`).
- Un ticket esistente qualsiasi.

**Dati di test**
Nessuno specifico.

**Stato iniziale**
Nessuno.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Fundraising | `sara.mariani@montagnaservizi.com` | Login riuscito |
| 2 | Apri un ticket qualsiasi | — | Pagina di dettaglio caricata, azione "Crea commessa" assente |

**Risultato finale atteso**
L'azione "Crea commessa" non è visibile né eseguibile.

**Controlli negativi**
Nessuno applicabile (è già il controllo negativo di F4-06).

**Evidenze da acquisire**
- Screenshot della pagina di dettaglio senza l'azione.

**Criterio di superamento**

PASS: l'azione non è visibile.
FAIL: l'azione è visibile o eseguibile.
BLOCKED: ambiente UAT non raggiungibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-08 — L'elenco commesse mostra ore stimate/lavorate, barra SAL e conteggio ticket aperti/chiusi

**Obiettivo**
Verificare che l'elenco "Commesse" (`TagResource`) mostri, per ciascuna riga, le ore stimate, le ore
lavorate calcolate, la barra SAL (percentuale) e i conteggi di ticket aperti/chiusi collegati, oltre al
badge Aperta/Chiusa.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-403, AC "colonne nome/ore stimate/ore lavorate/barra
  SAL/ticket aperti/chiusi/badge".
- Test automatico: `tests/Feature/Filament/Tags/TagResourceTest.php` — `the list shows estimated/worked
  hours, the SAL bar and the open/closed ticket counts`.
- File/componente applicativo rilevante: `App\Filament\Resources\Tags\Tables\TagsTable`,
  `resources/views/filament/tables/columns/tag-sal-bar.blade.php`.
- Test correlato: F4-09, F4-10.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Utente Developer/Admin con `tag.view`.
- Almeno una commessa reale con ticket aperti e chiusi collegati (usare una commessa del catalogo UAT
  importato dal v1, es. "SOAD/Gestione Rimborsi").

**Dati di test**
Commessa reale con ticket collegati misti (aperti/chiusi).

**Stato iniziale**
Nessuno (dati reali già presenti dopo `v1:import`).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Developer/Admin | `lorena.sava@montagnaservizi.com` / `info@montagnaservizi.com` | Login riuscito |
| 2 | Apri "Commesse" dal menu "Ticket" | — | Elenco commesse caricato |
| 3 | Individua una commessa con ticket collegati | Es. "SOAD/Gestione Rimborsi" | Colonne ore stimate/lavorate, barra SAL, conteggi aperti/chiusi valorizzate coerentemente |

**Risultato finale atteso**
Ogni colonna mostra un valore coerente con i ticket realmente collegati alla commessa.

**Controlli negativi**
Vedi F4-09 (commessa senza ore stimate: placeholder invece di errore).

**Evidenze da acquisire**
- Screenshot dell'elenco commesse con almeno una riga con barra SAL visibile.

**Criterio di superamento**

PASS: tutte le colonne sono coerenti con i dati reali.
FAIL: una colonna manca o mostra un valore incoerente.
BLOCKED: ambiente UAT non raggiungibile, o CSS non ricompilato (barra SAL non renderizzata — vedi
`CLAUDE.md`, nota su `ViewColumn`/rebuild CSS).
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno (nessuna scrittura).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-09 — Una commessa senza ore stimate mostra un placeholder SAL invece di un errore di divisione

**Obiettivo**
Verificare che una commessa priva di `estimated_hours` non generi un errore di divisione per zero
nell'elenco, ma mostri un placeholder ("—" o equivalente) nella colonna SAL.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-403, AC "nessun errore quando le ore stimate sono assenti".
- Test automatico: `tests/Feature/Filament/Tags/TagResourceTest.php` — `a tag with no estimated hours
  shows a SAL placeholder instead of a division error`.
- File/componente applicativo rilevante: `resources/views/filament/tables/columns/tag-sal-bar.blade.php`.
- Test correlato: F4-01, F4-08.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un `Tag` senza `estimated_hours`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a tag with no estimated hours shows a SAL placeholder instead of a division error"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La pagina viene renderizzata senza errori (200 OK) e `$tag->sal()` resta `null`.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-10 — Un utente senza tag.view non accede all'elenco commesse

**Obiettivo**
Verificare che un utente privo del permesso `tag.view` non possa accedere all'elenco "Commesse"
(`TagResource`), né dal menu né navigando direttamente all'URL.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-403, AC "elenco gated su tag.view".
- Test automatico: `tests/Feature/Filament/Tags/TagResourceTest.php` — `a user without tag.view is
  denied access to the tags resource`.
- File/componente applicativo rilevante: `App\Filament\Resources\Tags\TagResource`,
  `App\Domain\Tags\Policies\TagPolicy`.
- Test correlato: F4-08.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Fundraising (o altro ruolo privo di `tag.view` per catalogo permessi reale)

**Prerequisiti**
- Utente Fundraising `sara.mariani@montagnaservizi.com` (verificare in `RolePermissionSeeder` se privo
  di `tag.view`; in caso contrario, usare un ruolo confermato privo del permesso).

**Dati di test**
Nessuno specifico.

**Stato iniziale**
Nessuno.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi con l'utente privo di `tag.view` | `sara.mariani@montagnaservizi.com` | Login riuscito |
| 2 | Verifica che la voce "Commesse" non compaia nel menu | — | Voce assente dal menu |
| 3 | Naviga direttamente all'URL dell'elenco commesse | `/admin/tags` | Accesso negato (403) |

**Risultato finale atteso**
L'accesso è negato sia da menu sia da URL diretto.

**Controlli negativi**
Nessuno applicabile (è già il controllo negativo di F4-08).

**Evidenze da acquisire**
- Screenshot del menu senza la voce "Commesse".
- Screenshot della pagina 403.

**Criterio di superamento**

PASS: l'accesso è negato sia da menu sia da URL diretto.
FAIL: l'utente riesce ad accedere all'elenco.
BLOCKED: ambiente UAT non raggiungibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

## Documentation — modello, visibilità, Resource, generazione PDF (US-404..US-406)

### F4-11 — Lo scope di visibilità esclude le pagine interne per chi non ha documentation.view.internal

**Obiettivo**
Verificare che `DocumentationPage::scopeVisibleTo()` escluda le pagine di categoria `internal` per un
utente privo del permesso `documentation.view.internal`, anche se ha `documentation.view.customer`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-404, AC "visibilità per categoria, due permessi
  indipendenti, mai una gerarchia".
- Test automatico: `tests/Feature/Domain/Documentation/DocumentationPageVisibilityTest.php` —
  `scopeVisibleTo excludes internal pages for a user without documentation.view.internal`.
- File/componente applicativo rilevante: `App\Domain\Documentation\Models\DocumentationPage::scopeVisibleTo()`.
- Test correlato: F4-12, F4-15.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Due pagine, una `customer` e una `internal`; un utente con solo `documentation.view.customer`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "scopeVisibleTo excludes internal pages for a user without documentation.view.internal"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La query filtrata restituisce solo la pagina `customer`.

**Controlli negativi**
- `DocumentationPageVisibilityTest.php` copre anche il caso simmetrico (esclude `customer` per chi ha
  solo `.view.internal`) e il caso "nessun permesso → nessuna pagina".

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-12 — Un cliente non può visualizzare una pagina interna nemmeno richiedendone direttamente l'id

**Obiettivo**
Verificare che `DocumentationPagePolicy::view()` neghi l'accesso a una pagina `internal` per un cliente,
anche quando questi conosce e richiede direttamente l'id della pagina (mai un accesso bypassabile via
URL manipolato).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-404, AC "policy delega allo scope di visibilità".
- Test automatico: `tests/Feature/Domain/Documentation/DocumentationPageVisibilityTest.php` — `a customer
  cannot view an internal page even by requesting its id directly`.
- File/componente applicativo rilevante: `App\Domain\Documentation\Policies\DocumentationPagePolicy::view()`.
- Test correlato: F4-11.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Una pagina `internal`, un utente cliente con solo `documentation.view.customer`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a customer cannot view an internal page even by requesting its id directly"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
`$user->can('view', $internalPage)` restituisce `false`.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-13 — Creare una pagina di documentazione crea un tag collegato "Documentation: <titolo>"

**Obiettivo**
Verificare che creare una pagina di documentazione emetta l'evento `DocumentationPageCreated` gestito
dal listener `CreateTagForDocumentationPage`, che crea automaticamente una commessa collegata di nome
`Documentation: <titolo della pagina>` (mai un hook Eloquent diretto, per rispettare l'AC esplicito).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-405, AC "auto-tag da evento di dominio, mai da hook Eloquent".
- Test automatico: `tests/Feature/Domain/Documentation/DocumentationPageAutoTagTest.php` — `creating a
  documentation page creates a linked tag named "Documentation: <title>"`.
- File/componente applicativo rilevante: `App\Domain\Documentation\Events\DocumentationPageCreated`,
  `App\Domain\Tags\Listeners\CreateTagForDocumentationPage`.
- Test correlato: F4-14.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Titolo di pagina di prova, es. "Guida al portale".

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "creating a documentation page creates a linked tag named"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Esiste un `Tag` con nome `Documentation: <titolo>` collegato alla pagina.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-14 — Rinominare una pagina rinomina il tag collegato esistente senza crearne un duplicato

**Obiettivo**
Verificare che rinominare il titolo di una pagina di documentazione già esistente aggiorni il nome del
tag collegato (`Documentation: <nuovo titolo>`) invece di crearne uno nuovo, evitando duplicati
nell'elenco commesse.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-405, AC "rinomina il tag esistente sulla rinomina della pagina".
- Test automatico: `tests/Feature/Domain/Documentation/DocumentationPageAutoTagTest.php` — `renaming a
  documentation page renames the existing linked tag without creating a duplicate`.
- File/componente applicativo rilevante: `App\Domain\Documentation\Events\DocumentationPageRenamed`,
  `App\Domain\Tags\Listeners\RenameTagForDocumentationPage`.
- Test correlato: F4-13.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Una pagina esistente, rinominata a un nuovo titolo.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "renaming a documentation page renames the existing linked tag without creating a duplicate"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Esiste ancora un solo `Tag` collegato, con il nome aggiornato al nuovo titolo.

**Controlli negativi**
- `DocumentationPageAutoTagTest.php` copre anche "nessuna scrittura sul tag se il titolo non cambia" e
  slug distinti su titoli collidenti.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-15 — Un utente con documentation.view.customer accede al registro e vede solo le pagine cliente

**Obiettivo**
Verificare che un utente con `documentation.view.customer` (privo di `.view.internal`) acceda al
registro "Documentazione" e veda in elenco solo le pagine di categoria `customer`, mai quelle `internal`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-405, AC "elenco filtrato per categoria in base ai permessi".
- Test automatico: `tests/Feature/Filament/Documentation/DocumentationPageResourceTest.php` — `a user
  with documentation.view.customer can access the registry and see only customer pages`.
- File/componente applicativo rilevante: `App\Filament\Resources\DocumentationPages\DocumentationPageResource`.
- Test correlato: F4-11.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Utente Customer `infosentieroitalia@cai.it` (verificare che abbia `documentation.view.customer` da
  `RolePermissionSeeder`).
- Almeno una pagina `customer` e una `internal` reali (dal catalogo importato dal v1) o create per il test.

**Dati di test**
Pagine reali del catalogo UAT (es. "Guida all'archivio Generale", categoria cliente; "Manuale Operativo
per dipendenti e collaboratori", categoria interna).

**Stato iniziale**
Nessuno.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | `infosentieroitalia@cai.it` | Login riuscito |
| 2 | Apri "Documentazione" dal menu | — | Elenco caricato, solo pagine di categoria cliente visibili |
| 3 | Verifica che nessuna pagina interna compaia | — | Nessuna pagina interna in elenco |

**Risultato finale atteso**
L'elenco mostra solo pagine cliente.

**Controlli negativi**
Vedi F4-12 (accesso diretto per id a una pagina interna negato).

**Evidenze da acquisire**
- Screenshot dell'elenco documentazione lato cliente.

**Criterio di superamento**

PASS: l'elenco mostra solo pagine cliente.
FAIL: una pagina interna compare in elenco.
BLOCKED: ambiente UAT non raggiungibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-16 — La ricerca full-text trova una pagina da un termine presente solo nel corpo

**Obiettivo**
Verificare che la ricerca full-text del registro "Documentazione" trovi una pagina anche quando il
termine cercato compare solo nel corpo (`body`), non nel titolo — coerente con l'indice GIN dedicato
(Postgres-only) introdotto per questa story.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-405, AC "ricerca full-text su title e body".
- Test automatico: `tests/Feature/Filament/Documentation/DocumentationPageResourceTest.php` — `full-text
  search finds a page by a term only present in the body`.
- File/componente applicativo rilevante: `database/migrations/..._add_fulltext_index_to_documentation_pages_table.php`.
- Test correlato: F4-15.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer

**Prerequisiti**
- Come F4-15.

**Dati di test**
Una parola presente solo nel corpo di una pagina reale nota (es. un termine specifico del testo di
"Servizio di Ticketing").

**Stato iniziale**
Nessuno.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | `infosentieroitalia@cai.it` | Login riuscito |
| 2 | Apri "Documentazione" e usa il campo di ricerca | Un termine presente solo nel corpo | La pagina attesa compare in elenco |

**Risultato finale atteso**
La ricerca trova la pagina anche se il termine non è nel titolo.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot del risultato di ricerca.

**Criterio di superamento**

PASS: la pagina compare nei risultati.
FAIL: la ricerca non trova la pagina.
BLOCKED: ambiente UAT non raggiungibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-17 — Creare una pagina genera un PDF non vuoto e valorizza pdf_path/pdf_generated_at

**Obiettivo**
Verificare che creare una pagina di documentazione dispatchi la generazione reale (Chromium via
`spatie/laravel-pdf`) del suo PDF, producendo un file non vuoto (`%PDF` come primi byte) e valorizzando
`pdf_path`/`pdf_generated_at` sul record.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-406, AC "PDF generato automaticamente alla creazione/modifica".
- Test automatico: `tests/Feature/Domain/Documentation/DocumentationPagePdfTest.php` — `creating a
  documentation page generates a non-empty PDF and stamps pdf_path/pdf_generated_at`.
- File/componente applicativo rilevante: `App\Domain\Documentation\Actions\GenerateDocumentationPagePdf`,
  `App\Domain\Documentation\Jobs\GenerateDocumentationPagePdfJob`.
- Test correlato: F4-18, F4-19, F4-20, F4-41.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate, Chromium disponibile (auto-scoperto da chrome-php) e
  suite Pest funzionante. Coda `sync` (default in `phpunit.xml`): il job gira per davvero nel test, non
  fake.

**Dati di test**
Titolo/corpo di prova.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "creating a documentation page generates a non-empty PDF and stamps pdf_path/pdf_generated_at"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
`pdf_path`/`pdf_generated_at` sono valorizzati; il contenuto sul disco inizia con `%PDF` e non è vuoto.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: Chromium non disponibile nell'ambiente locale/CI.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-18 — Modificare il titolo rigenera il PDF con un timestamp più recente

**Obiettivo**
Verificare che modificare il titolo (o il corpo) di una pagina di documentazione già esistente
rigeneri il PDF, aggiornando `pdf_generated_at` a un valore più recente rispetto alla generazione
precedente.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-406, AC "PDF rigenerato al cambio di titolo o corpo".
- Test automatico: `tests/Feature/Domain/Documentation/DocumentationPagePdfTest.php` — `changing the
  title regenerates the PDF with a newer timestamp`.
- File/componente applicativo rilevante: `App\Domain\Documentation\Events\DocumentationPageContentChanged`,
  `App\Domain\Documentation\Listeners\GenerateDocumentationPagePdfOnChange`.
- Test correlato: F4-17.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Come F4-17.

**Dati di test**
Una pagina esistente, con titolo modificato dopo un breve avanzamento di tempo simulato (`travel`).

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "changing the title regenerates the PDF with a newer timestamp"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
`pdf_path` resta lo stesso, `pdf_generated_at` è più recente del valore precedente.

**Controlli negativi**
- `DocumentationPagePdfTest.php` copre anche "saving without changing title or body does not regenerate
  the PDF" (nessuna rigenerazione superflua).

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: Chromium non disponibile nell'ambiente locale/CI.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-19 — Il comando documentation:regenerate-pdfs rigenera il PDF di ogni pagina

**Obiettivo**
Verificare che il comando `documentation:regenerate-pdfs` (rigenerazione batch) esamini ogni pagina
esistente e rigeneri il relativo PDF, riportando conteggi corretti (esaminate/rigenerate/saltate/errori).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-406, AC "comando per rigenerazione batch di tutte le pagine".
- Test automatico: `tests/Feature/Console/DocumentationRegeneratePdfsCommandTest.php` — `regenerates the
  pdf of every documentation page`.
- File/componente applicativo rilevante: `App\Console\Commands\DocumentationRegeneratePdfsCommand`.
- Test correlato: F4-17.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Come F4-17.

**Dati di test**
Più pagine di documentazione esistenti.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "regenerates the pdf of every documentation page"` | Il comando termina con exit code 0, test passed |
| 3 (facoltativo, verifica su dati reali) | Eseguire il comando reale nel container `app` | `docker compose exec app php artisan documentation:regenerate-pdfs` | Ogni pagina reale riporta "PDF rigenerato", conteggio finale coerente |

**Risultato finale atteso**
Ogni pagina esistente ha un PDF rigenerato.

**Controlli negativi**
- `DocumentationRegeneratePdfsCommandTest.php` copre anche `--dry-run` (esamina senza scrivere).

**Evidenze da acquisire**
- Output completo del comando Pest eseguito (e, se eseguito il passo 3, l'output del comando artisan reale).

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: Chromium non disponibile nell'ambiente locale/CI.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno (il comando reale eseguito al passo 3 è idempotente e non richiede ripristino).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-20 — Un utente che può visualizzare la pagina può scaricarne il PDF

**Obiettivo**
Verificare che la rotta autenticata di download PDF (`documentation-pages.pdf-download`) restituisca il
file per un utente che ha il permesso di visualizzare quella pagina, con `content-type: application/pdf`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-406, AC "download PDF autorizzato dalla stessa Policy della
  pagina".
- Test automatico: `tests/Feature/Http/DocumentationPagePdfDownloadControllerTest.php` — `a user who can
  view the documentation page can download its pdf`.
- File/componente applicativo rilevante: `App\Http\Controllers\DocumentationPagePdfDownloadController`.
- Test correlato: F4-21, F4-41.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Customer

**Prerequisiti**
- Utente Customer `infosentieroitalia@cai.it`.
- Una pagina di categoria cliente con PDF già generato (vedi F4-19, oppure una pagina reale del catalogo
  UAT dopo `documentation:regenerate-pdfs`).

**Dati di test**
Pagina reale "Servizio di Ticketing" (categoria cliente).

**Stato iniziale**
PDF già generato per la pagina scelta.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | `infosentieroitalia@cai.it` | Login riuscito |
| 2 | Apri "Documentazione" e seleziona la pagina | "Servizio di Ticketing" | Pagina di dettaglio/elenco con azione "Scarica PDF" |
| 3 | Esegui "Scarica PDF" | — | Download avviato, file `.pdf` valido |
| 4 | Apri il PDF scaricato | — | Carta intestata Montagna Servizi (logo in alto, footer con ragione sociale/P.IVA/SDI), contenuto della pagina |

**Risultato finale atteso**
Il PDF scaricato è valido e mostra la carta intestata corretta.

**Controlli negativi**
Vedi F4-21 (permesso di categoria mancante negato).

**Evidenze da acquisire**
- Il file PDF scaricato (o uno screenshot della prima pagina).

**Criterio di superamento**

PASS: il download riesce e il PDF mostra la carta intestata corretta.
FAIL: il download fallisce, o il PDF non ha la carta intestata attesa.
BLOCKED: ambiente UAT non raggiungibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-21 — Un utente senza il permesso di categoria corrispondente è negato, anche via accesso diretto per id

**Obiettivo**
Verificare che un utente privo del permesso di categoria della pagina (es. solo `.view.customer` su una
pagina `internal`) riceva un 403 dalla rotta di download PDF, anche navigando direttamente all'URL con
l'id della pagina.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-406, AC "download mai bypassabile via id diretto".
- Test automatico: `tests/Feature/Http/DocumentationPagePdfDownloadControllerTest.php` — `a user without
  the matching category permission is denied, even by direct id access`.
- File/componente applicativo rilevante: `App\Http\Controllers\DocumentationPagePdfDownloadController`.
- Test correlato: F4-20.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Una pagina `internal` con PDF fittizio su disco fake, un utente con solo `documentation.view.customer`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a user without the matching category permission is denied, even by direct id access"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La risposta HTTP è 403.

**Controlli negativi**
- `DocumentationPagePdfDownloadControllerTest.php` copre anche "a page whose pdf has not been generated
  yet returns a 404".

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

## Activity Report e Organizations — modello, sync, PDF, comando mensile (US-407..US-410)

### F4-22 — Un utente con organization.view accede al registro organizzazioni

**Obiettivo**
Verificare che un utente con `organization.view` acceda al registro "Organizzazioni"
(`OrganizationResource`).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-407, AC "registro organizzazioni gated su organization.view".
- Test automatico: `tests/Feature/Filament/Organizations/OrganizationResourceTest.php` — `a user with
  organization.view can access the organizations registry`.
- File/componente applicativo rilevante: `App\Filament\Resources\Organizations\OrganizationResource`.
- Test correlato: F4-23.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Utente Admin `info@montagnaservizi.com`.

**Dati di test**
Nessuno specifico (organizzazioni reali già importate dal v1).

**Stato iniziale**
Nessuno.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | `info@montagnaservizi.com` | Login riuscito |
| 2 | Apri "Organizzazioni" dal menu | — | Elenco organizzazioni caricato, con nome/lingua/numero membri per riga |

**Risultato finale atteso**
L'elenco è visibile e mostra le organizzazioni reali importate.

**Controlli negativi**
Nessuno applicabile per questo test (vedi la variante "senza organization.view" coperta dallo stesso
file `OrganizationResourceTest.php`, non ripetuta qui come test a sé — copertura equivalente a F4-10 per
un'altra Resource).

**Evidenze da acquisire**
- Screenshot dell'elenco organizzazioni.

**Criterio di superamento**

PASS: l'elenco è visibile con i dati attesi.
FAIL: l'accesso è negato o l'elenco è vuoto/incoerente.
BLOCKED: ambiente UAT non raggiungibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-23 — Collegare un utente tramite il relation manager "Membri" lo collega all'organizzazione

**Obiettivo**
Verificare che, dalla pagina di modifica di un'organizzazione, l'azione "Collega" del relation manager
"Membri" colleghi realmente l'utente selezionato alla relazione N-N `organization_user`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-407, AC "gestione membri via relation manager Attach/Detach".
- Test automatico: `tests/Feature/Filament/Organizations/OrganizationResourceTest.php` — `adding a user
  via the members relation manager attaches it to the organization`.
- File/componente applicativo rilevante: `App\Filament\Resources\Organizations\RelationManagers\UsersRelationManager`.
- Test correlato: F4-22.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Utente Admin `info@montagnaservizi.com`.
- Un'organizzazione esistente e un utente non ancora membro.

**Dati di test**
Un'organizzazione reale (o creata per il test) e un utente reale da collegare.

**Stato iniziale**
Utente non membro dell'organizzazione scelta.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Admin | `info@montagnaservizi.com` | Login riuscito |
| 2 | Apri "Organizzazioni" → modifica organizzazione scelta → tab "Membri" | — | Tab "Membri" caricata |
| 3 | Esegui "Collega" e seleziona l'utente | Utente scelto | Modale con select ricercabile |
| 4 | Conferma | — | Notifica "Collegato", utente visibile in tabella con azione "Scollega" |

**Risultato finale atteso**
L'utente compare come membro dell'organizzazione.

**Controlli negativi**
- `OrganizationResourceTest.php` copre anche "removing a user via the members relation manager detaches
  it from the organization" (azione "Scollega").

**Evidenze da acquisire**
- Screenshot prima e dopo il collegamento.

**Criterio di superamento**

PASS: l'utente risulta collegato dopo l'azione.
FAIL: il collegamento non avviene o genera un errore.
BLOCKED: ambiente UAT non raggiungibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Se il collegamento è stato fatto solo per il test, "Scollega" al termine per non alterare i dati reali.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-24 — periodStart/periodEnd coprono l'intero mese per un report mensile

**Obiettivo**
Verificare che `ActivityReport::periodStart()`/`periodEnd()` calcolino correttamente l'inizio e la fine
del mese (00:00:00 del primo giorno, 23:59:59 dell'ultimo) per un report di tipo mensile.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-408, AC "periodo derivato da period_type+year+month".
- Test automatico: `tests/Unit/Domain/Reporting/ActivityReportPeriodTest.php` — `periodStart/periodEnd
  span the full month for a monthly report`.
- File/componente applicativo rilevante: `App\Domain\Reporting\Models\ActivityReport::periodStart()`,
  `periodEnd()`.
- Test correlato: F4-25, F4-26.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un `ActivityReport` mensile per un mese/anno noto.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "periodStart/periodEnd span the full month for a monthly report"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
`periodStart()`/`periodEnd()` coprono esattamente il mese richiesto.

**Controlli negativi**
- `ActivityReportPeriodTest.php` copre anche il caso annuale.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-25 — periodLabel è il nome del mese localizzato e capitalizzato più l'anno per un report mensile

**Obiettivo**
Verificare che `ActivityReport::periodLabel()` restituisca il nome del mese localizzato nella lingua
del report, con l'iniziale maiuscola, seguito dall'anno (es. "Luglio 2026" in italiano).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-408, AC "etichetta periodo leggibile e localizzata".
- Test automatico: `tests/Unit/Domain/Reporting/ActivityReportPeriodTest.php` — `periodLabel is the
  localized capitalized month name and year for a monthly report`.
- File/componente applicativo rilevante: `App\Domain\Reporting\Models\ActivityReport::periodLabel()`.
- Test correlato: F4-24, F4-42.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Bassa

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un `ActivityReport` mensile con `locale = 'it'`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "periodLabel is the localized capitalized month name and year for a monthly report"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
`periodLabel()` restituisce, ad esempio, "Luglio 2026".

**Controlli negativi**
- `ActivityReportPeriodTest.php` copre anche il caso annuale ("solo l'anno").

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-26 — syncTickets seleziona solo i ticket del proprietario utente completati nel periodo

**Obiettivo**
Verificare che `ActivityReportSyncService::syncTickets()`, per un report con owner utente, colleghi solo
i ticket richiesti da quell'utente con `done_at` nel periodo del report, escludendo ticket fuori periodo
o di altri richiedenti.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-408, AC "ticket selezionati per owner+periodo su done_at".
- Test automatico: `tests/Feature/Domain/Reporting/Services/ActivityReportSyncServiceTest.php` —
  `syncTickets selects only the owner user tickets done within the period`.
- File/componente applicativo rilevante: `App\Domain\Reporting\Services\ActivityReportSyncService`.
- Test correlato: F4-27, F4-28, F4-42.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un utente owner con ticket dentro e fuori periodo, e un ticket di un altro richiedente nello stesso periodo.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "syncTickets selects only the owner user tickets done within the period"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Solo il ticket del richiedente corretto, con `done_at` nel periodo, risulta collegato al report.

**Controlli negativi**
- `ActivityReportSyncServiceTest.php` copre anche il caso organizzazione, l'idempotenza, un ticket che
  esce dal periodo su re-sync, e l'owner non risolvibile (soft-deleted).

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-27 — syncTickets seleziona i ticket richiesti da ogni membro dell'organizzazione proprietaria

**Obiettivo**
Verificare che, per un report con owner organizzazione, `syncTickets()` colleghi i ticket richiesti da
QUALUNQUE membro dell'organizzazione (non solo un membro specifico), sempre filtrati per periodo.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-408, AC "owner organizzazione: ticket di ogni membro".
- Test automatico: `tests/Feature/Domain/Reporting/Services/ActivityReportSyncServiceTest.php` —
  `syncTickets selects tickets requested by any member of the owner organization`.
- File/componente applicativo rilevante: `App\Domain\Reporting\Services\ActivityReportSyncService`.
- Test correlato: F4-26.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un'organizzazione con più membri, ciascuno con ticket nel periodo.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "syncTickets selects tickets requested by any member of the owner organization"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
I ticket di ogni membro nel periodo risultano collegati al report dell'organizzazione.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-28 — syncTickets è idempotente se invocato due volte di seguito sullo stesso report

**Obiettivo**
Verificare che invocare `syncTickets()` più volte di seguito sullo stesso report produca sempre lo
stesso insieme di ticket collegati, senza duplicati né side-effect aggiuntivi.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-408, AC "sync idempotente".
- Test automatico: `tests/Feature/Domain/Reporting/Services/ActivityReportSyncServiceTest.php` —
  `syncTickets is idempotent when invoked twice in a row`.
- File/componente applicativo rilevante: `App\Domain\Reporting\Services\ActivityReportSyncService`.
- Test correlato: F4-26, F4-36.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un report con alcuni ticket nel periodo.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "syncTickets is idempotent when invoked twice in a row"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
L'insieme di ticket collegati è identico dopo la seconda chiamata.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-29 — Creare il report sincronizza i suoi ticket in un'unica chiamata

**Obiettivo**
Verificare che `CreateActivityReport::run()` crei il record e sincronizzi immediatamente i ticket del
periodo in un'unica chiamata, senza richiedere un secondo passo esplicito.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-408, AC "azione di creazione crea+sincronizza in un colpo".
- Test automatico: `tests/Feature/Domain/Reporting/Actions/CreateActivityReportTest.php` — `creates the
  report and syncs its tickets in one call`.
- File/componente applicativo rilevante: `App\Domain\Reporting\Actions\CreateActivityReport`.
- Test correlato: F4-30, F4-42.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un owner utente con un ticket completato nel periodo richiesto.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "creates the report and syncs its tickets in one call"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il report creato ha già il ticket atteso collegato, senza chiamare `syncTickets()` a parte.

**Controlli negativi**
- `CreateActivityReportTest.php` copre anche la derivazione del `locale` dall'owner (utente e
  organizzazione), sempre ignorando un `locale` passato esplicitamente dal chiamante.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-30 — Un duplicato proprietario/periodo viene rifiutato con un errore leggibile invece della QueryException grezza

**Obiettivo**
Verificare che tentare di creare un secondo `ActivityReport` per lo stesso owner e lo stesso periodo
sollevi un'eccezione applicativa leggibile (`RuntimeException`), non la `QueryException` grezza del
vincolo DB.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-408, AC "un solo report per owner+periodo, errore leggibile
  sul duplicato".
- Test automatico: `tests/Feature/Domain/Reporting/Actions/CreateActivityReportTest.php` — `rejects a
  duplicate owner/period with a readable error instead of the raw QueryException`.
- File/componente applicativo rilevante: `App\Domain\Reporting\Actions\CreateActivityReport`.
- Test correlato: F4-29, F4-36.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un report già esistente per un owner/periodo, un secondo tentativo di creazione identico.

**Stato iniziale**
Un `ActivityReport` già esistente per l'owner/periodo scelto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "rejects a duplicate owner/period with a readable error instead of the raw QueryException"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Viene sollevata una `RuntimeException` con un messaggio leggibile, non la `QueryException` grezza.

**Controlli negativi**
- `CreateActivityReportTest.php` copre anche "does not treat a different period for the same owner as a
  duplicate" (nessun falso positivo).

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-31 — Generare il PDF del report produce un file non vuoto e valorizza pdf_path/pdf_generated_at

**Obiettivo**
Verificare che `GenerateActivityReportPdf::run()` produca un PDF reale (Chromium via
`spatie/laravel-pdf`) non vuoto, con i ticket e i totali del report, valorizzando `pdf_path`/`pdf_generated_at`.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-409, AC "PDF con intestazione owner/periodo, tabella ticket,
  totale ore".
- Test automatico: `tests/Feature/Domain/Reporting/ActivityReportPdfTest.php` — `generates a non-empty
  PDF and stamps pdf_path/pdf_generated_at`.
- File/componente applicativo rilevante: `App\Domain\Reporting\Actions\GenerateActivityReportPdf`.
- Test correlato: F4-32, F4-42.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate, Chromium disponibile e suite Pest funzionante.

**Dati di test**
Un owner con ticket completati nel periodo (`worked_minutes` valorizzato).

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "generates a non-empty PDF and stamps pdf_path/pdf_generated_at" tests/Feature/Domain/Reporting/ActivityReportPdfTest.php` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
`pdf_path`/`pdf_generated_at` sono valorizzati; il contenuto sul disco inizia con `%PDF` e non è vuoto.

**Controlli negativi**
- `ActivityReportPdfTest.php` copre anche "rendering does not leak the report locale into the
  surrounding application locale" (isolamento `App::setLocale()`/`finally`).

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: Chromium non disponibile nell'ambiente locale/CI.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-32 — Cancellare il report rimuove il PDF generato dallo storage, nessun file orfano

**Obiettivo**
Verificare che cancellare un `ActivityReport` con PDF già generato rimuova anche il file dal disco
(`activity-report-pdfs`), senza lasciare file orfani.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-409, AC "cancellazione del record cancella anche il PDF".
- Test automatico: `tests/Feature/Domain/Reporting/ActivityReportPdfTest.php` — `deleting the report
  removes its generated PDF from storage`.
- File/componente applicativo rilevante: `App\Domain\Reporting\Models\ActivityReport::booted()`
  (hook `deleting`).
- Test correlato: F4-31.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un report con PDF già generato.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "deleting the report removes its generated PDF from storage"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il file PDF non esiste più sul disco dopo la cancellazione del record.

**Controlli negativi**
- `ActivityReportPdfTest.php` copre anche "deleting a report without a generated PDF does not error".

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-33 — activity-report.view.own autorizza un membro dell'organizzazione proprietaria ma non un non-membro

**Obiettivo**
Verificare che `ActivityReportPolicy::view()`, per un utente con solo `activity-report.view.own`, conceda
l'accesso al report di un'organizzazione di cui l'utente è membro, ma lo neghi per un'organizzazione di
cui non è membro (mai un accesso più ampio del proprio perimetro).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-409, AC "cliente/organizzazione scaricano solo i propri
  report, mai quelli di altri owner".
- Test automatico: `tests/Feature/Domain/Reporting/ActivityReportPolicyTest.php` —
  `activity-report.view.own authorizes a member of the owner organization but not a non-member (US-409)`.
- File/componente applicativo rilevante: `App\Domain\Reporting\Models\ActivityReport::isOwnedBy()`,
  `App\Domain\Reporting\Policies\ActivityReportPolicy::view()`.
- Test correlato: F4-34, F4-38.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un report di un'organizzazione, un utente membro e uno non membro, entrambi con solo
`activity-report.view.own`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "activity-report.view.own authorizes a member of the owner organization but not a non-member"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il membro può vedere il report, il non membro no.

**Controlli negativi**
- `ActivityReportPolicyTest.php` copre anche il caso simmetrico per owner utente (mai il report di un
  altro utente).

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-34 — Un utente con solo activity-report.view.own può scaricare il proprio report

**Obiettivo**
Verificare che un cliente con solo `activity-report.view.own` possa scaricare il PDF del proprio report
tramite la rotta autenticata di download.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-409, AC "download PDF autorizzato dalla stessa Policy del
  report".
- Test automatico: `tests/Feature/Http/ActivityReportPdfDownloadControllerTest.php` — `a user with only
  activity-report.view.own can download their own report`.
- File/componente applicativo rilevante: `App\Http\Controllers\ActivityReportPdfDownloadController`.
- Test correlato: F4-33, F4-42.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Customer

**Prerequisiti**
- Utente Customer `infosentieroitalia@cai.it` con `activity-report.view.own`.
- Un report reale del proprio owner con PDF già generato (vedi F4-31, oppure via
  `reports:generate-monthly` sul mese precedente).

**Dati di test**
Un report reale del proprio owner.

**Stato iniziale**
PDF già generato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | `infosentieroitalia@cai.it` | Login riuscito |
| 2 | Apri "Report Attività" dal menu | — | Elenco con il proprio report |
| 3 | Esegui "Scarica PDF" sul proprio report | — | Download avviato, file `.pdf` valido |
| 4 | Apri il PDF scaricato | — | Carta intestata Montagna Servizi, tabella ticket, totale ore coerente |

**Risultato finale atteso**
Il PDF scaricato è valido e mostra i dati corretti del proprio report.

**Controlli negativi**
Vedi F4-33 (accesso negato a un report non proprio, anche via id diretto — coperto dallo stesso file
`ActivityReportPdfDownloadControllerTest.php`).

**Evidenze da acquisire**
- Il file PDF scaricato (o uno screenshot della prima pagina).

**Criterio di superamento**

PASS: il download riesce e il PDF mostra i dati corretti.
FAIL: il download fallisce o mostra dati incoerenti.
BLOCKED: ambiente UAT non raggiungibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-35 — Il comando reports:generate-monthly crea il report per un cliente con un ticket completato nel mese precedente e accoda il PDF

**Obiettivo**
Verificare che il comando `reports:generate-monthly` individui i clienti attivi (con almeno un ticket
`done_at` nel mese precedente), crei il relativo `ActivityReport` e accodi il job di generazione PDF.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-410, AC "comando mensile individua owner attivi, crea report,
  accoda PDF".
- Test automatico: `tests/Feature/Console/ReportsGenerateMonthlyCommandTest.php` — `creates a monthly
  report for a customer with a ticket done in the previous month and queues its pdf`.
- File/componente applicativo rilevante: `App\Console\Commands\ReportsGenerateMonthlyCommand`.
- Test correlato: F4-36, F4-37.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Un cliente con un ticket `done_at` nel mese precedente rispetto alla data simulata (`travelTo`).

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "creates a monthly report for a customer with a ticket done in the previous month and queues its pdf"` | Il comando termina con exit code 0, test passed |
| 3 (facoltativo, verifica su dati reali) | Eseguire il comando reale nel container `app` | `docker compose exec app php artisan reports:generate-monthly --dry-run` poi senza `--dry-run` | Log strutturato di inizio/fine, conteggi coerenti con gli owner attivi reali del mese precedente |

**Risultato finale atteso**
Un `ActivityReport` è creato per il cliente, con `GenerateActivityReportPdfJob` accodato per quel report.

**Controlli negativi**
- `ReportsGenerateMonthlyCommandTest.php` copre anche "creates a monthly report for an organization
  whose member has a ticket done in the previous month" e "a customer without any ticket done in the
  previous month is not considered an active owner".

**Evidenze da acquisire**
- Output completo del comando Pest eseguito (e, se eseguito il passo 3, l'output del comando artisan reale).

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Se eseguito il passo 3 su dati reali con un vero report creato per la verifica, valutare se rimuoverlo
(`forceDelete()`) o lasciarlo come report reale legittimo (dipende se il mese precedente reale ha
effettivamente owner attivi genuini).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-36 — Rieseguire il comando non duplica un report già creato per lo stesso proprietario e periodo

**Obiettivo**
Verificare che rieseguire `reports:generate-monthly` più volte non produca un secondo `ActivityReport`
per lo stesso owner/periodo già coperto, e non riaccodi un secondo job PDF per lo stesso report.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-410, AC "comando idempotente, nessun duplicato".
- Test automatico: `tests/Feature/Console/ReportsGenerateMonthlyCommandTest.php` — `re-running the
  command does not duplicate a report already created for the same owner and period`.
- File/componente applicativo rilevante: `App\Console\Commands\ReportsGenerateMonthlyCommand`.
- Test correlato: F4-28, F4-30, F4-35.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Come F4-35, con il comando eseguito due volte di seguito.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "re-running the command does not duplicate a report already created for the same owner and period"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Un solo `ActivityReport` esiste dopo le due esecuzioni; un solo job PDF risulta accodato.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-37 — --dry-run esamina i proprietari attivi senza creare report né accodare PDF

**Obiettivo**
Verificare che l'opzione `--dry-run` del comando `reports:generate-monthly` esamini gli owner attivi e
logghi i conteggi attesi, senza scrivere alcun `ActivityReport` né accodare alcun job PDF.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-410, AC "--dry-run non scrive nulla (§10.1)".
- Test automatico: `tests/Feature/Console/ReportsGenerateMonthlyCommandTest.php` — `--dry-run examines
  active owners without creating any report or queuing any pdf`.
- File/componente applicativo rilevante: `App\Console\Commands\ReportsGenerateMonthlyCommand`.
- Test correlato: F4-35.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Come F4-35.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "\-\-dry-run examines active owners without creating any report or queuing any pdf"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Nessun `ActivityReport` creato, nessun job accodato.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-38 — view.own vede solo il proprio report come proprietario diretto, mai quello di un altro owner

**Obiettivo**
Verificare che lo scope query `ActivityReport::scopeVisibleTo()`, per un utente con solo
`activity-report.view.own`, restituisca solo il proprio report come owner utente diretto, mai quello di
un altro owner.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-410, AC "vista 'i miei report' filtrata lato query".
- Test automatico: `tests/Feature/Domain/Reporting/ActivityReportScopeVisibleToTest.php` — `view.own sees
  only its own report as a direct user owner, never another owner`.
- File/componente applicativo rilevante: `App\Domain\Reporting\Models\ActivityReport::scopeVisibleTo()`.
- Test correlato: F4-33, F4-39.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Due report di owner utente distinti, un utente con `activity-report.view.own` proprietario di uno solo.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "view.own sees only its own report as a direct user owner, never another owner"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
La query filtrata restituisce solo il report del proprio owner.

**Controlli negativi**
- `ActivityReportScopeVisibleToTest.php` copre anche `view.any` (vede tutto), il caso organizzazione, e
  "nessun permesso → nessun report".

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-39 — Un cliente con activity-report.view.own vede solo il proprio report nell'elenco "Report Attività"

**Obiettivo**
Verificare che la vista Filament "Report Attività" (`ActivityReportResource`, sola lettura) mostri, per
un cliente con `activity-report.view.own`, solo il proprio report, mai quelli di altri owner.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-410, AC "vista cliente 'i miei report attività'".
- Test automatico: `tests/Feature/Filament/ActivityReports/ActivityReportResourceTest.php` — `a customer
  with activity-report.view.own sees only its own report in the list`.
- File/componente applicativo rilevante: `App\Filament\Resources\ActivityReports\ActivityReportResource`.
- Test correlato: F4-38.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Utente Customer `infosentieroitalia@cai.it` con `activity-report.view.own`.
- Almeno un report reale del proprio owner (dal catalogo importato dal v1, o generato via
  `reports:generate-monthly`).

**Dati di test**
Report reale del proprio owner.

**Stato iniziale**
Nessuno.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi come Customer | `infosentieroitalia@cai.it` | Login riuscito |
| 2 | Apri "Report Attività" dal menu "Rendicontazione" | — | Elenco con titolare/periodo/data PDF, solo il proprio report |

**Risultato finale atteso**
L'elenco mostra solo il report del proprio owner.

**Controlli negativi**
- `ActivityReportResourceTest.php` copre anche "a user without any activity-report permission is denied
  access to the resource".

**Evidenze da acquisire**
- Screenshot dell'elenco "Report Attività" lato cliente.

**Criterio di superamento**

PASS: l'elenco mostra solo il report del proprio owner.
FAIL: compare anche un report di un altro owner, o l'elenco è vuoto/inaccessibile.
BLOCKED: ambiente UAT non raggiungibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

## Checkpoint di fine fase — verifica end-to-end su dati reali (US-411)

Questo argomento replica in automatico, con dati sintetici ma rappresentativi, la verifica manuale già
eseguita su dati reali importati da v1 durante lo sviluppo di questa story: `docker compose exec app php
artisan v1:import --anonymize` (40 commesse, 3996 ticket, 550 report attività, 6 pagine di documentazione,
23 organizzazioni reali) su un ambiente Docker locale, poi ispezione diretta via `tinker`/Playwright.
Sintesi della verifica manuale eseguita durante lo sviluppo (dettagli completi in `scripts/ralph/progress.txt`,
sezione US-411):

- SAL: impostate temporaneamente `estimated_hours = 2` sulla commessa reale "SOAD/Pagamenti online" (3
  ticket collegati, somma `worked_minutes = 10`) → `sal() = 8.33` (`10/120*100`), coerente con il calcolo
  atteso; valore ripristinato a `null` subito dopo la verifica.
- Documentation: rigenerato per davvero il PDF (`documentation:regenerate-pdfs`, Chromium reale) di tutte
  le 6 pagine reali importate; scaricato via Playwright (login reale come Admin) il PDF della pagina
  "Servizio di Ticketing" e ispezionato con lo strumento di lettura PDF — logo Montagna Servizi in alto,
  footer con ragione sociale/P.IVA/SDI, contenuto della pagina.
- ActivityReport: creato un report reale (`CreateActivityReport::run()`) per l'owner utente reale "OTCO/SO
  CCTAM" (id 34), periodo Luglio 2026 — 5 ticket collegati, somma `worked_minutes = 160` (2.67 ore),
  verificati a campione uno per uno contro `tickets` (`#3968`, `#3970`, `#4109`, `#4144`, `#4156`); PDF
  generato e ispezionato, stesso totale "2.67" mostrato nel documento; report di verifica rimosso
  (`forceDelete()`) al termine, il suo PDF cancellato automaticamente dall'hook `deleting` (US-409).

### F4-40 — Il SAL è calcolato correttamente su una commessa con ticket collegati (replica automatica della verifica manuale su dati reali v1:import)

**Obiettivo**
Fornire una regressione automatica, eseguibile in CI, del flusso end-to-end "SAL su una commessa con
ticket collegati" già verificato manualmente su dati reali importati dal v1 durante questo checkpoint.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-411, AC1 ("SAL calcolato correttamente su almeno una
  commessa (Tag) reale con ticket collegati").
- Test automatico: `tests/Feature/EndToEnd/Fase4CheckpointEndToEndTest.php` — `SAL is computed correctly
  on a real commessa with linked tickets`.
- File/componente applicativo rilevante: `App\Domain\Tags\Models\Tag::sal()`, `workedMinutes()`.
- Test correlato: F4-01, F4-02, F4-08.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate e suite Pest funzionante.

**Dati di test**
Una commessa con `estimated_hours = 2` e tre ticket collegati con `worked_minutes` totali `120`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "SAL is computed correctly on a real commessa with linked tickets"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
`workedMinutes() = 120`, `sal() = 100.0`.

**Controlli negativi**
Nessuno applicabile (già coperto da F4-01/F4-02/F4-03 a livello unitario).

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: l'ambiente locale/CI non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-41 — Una pagina di documentazione genera un PDF scaricabile con la carta intestata Montagna Servizi corretta

**Obiettivo**
Fornire una regressione automatica del flusso end-to-end "creazione pagina → generazione PDF reale →
download via rotta autenticata", già verificato visivamente su dati reali durante questo checkpoint
(logo/footer corretti sul PDF scaricato via Playwright).

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-411, AC1 ("PDF documentazione generato e scaricabile con la
  carta intestata corretta").
- Test automatico: `tests/Feature/EndToEnd/Fase4CheckpointEndToEndTest.php` — `a documentation page pdf
  is generated and downloadable with the correct letterhead content`.
- File/componente applicativo rilevante: `App\Domain\Documentation\Actions\GenerateDocumentationPagePdf`,
  `App\Http\Controllers\DocumentationPagePdfDownloadController`.
- Test correlato: F4-17, F4-20.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate, Chromium disponibile e suite Pest funzionante.

**Dati di test**
Una pagina di documentazione di categoria cliente, creata via `CreateDocumentationPage::run()`.

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "a documentation page pdf is generated and downloadable with the correct letterhead content"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il PDF generato è non vuoto (`%PDF`), e la rotta di download restituisce 200 con
`content-type: application/pdf` per un utente autorizzato.

**Controlli negativi**
Nessuno applicabile (già coperto da F4-21 a livello unitario).

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.
- (Verifica manuale già acquisita durante lo sviluppo: PDF scaricato via Playwright dalla pagina
  "Servizio di Ticketing", con logo/footer Montagna Servizi corretti — vedi progress.txt, US-411.)

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: Chromium non disponibile nell'ambiente locale/CI.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

### F4-42 — Un report attività è generato per un proprietario reale con ticket e totali verificati contro i ticket sorgente

**Obiettivo**
Fornire una regressione automatica del flusso end-to-end "creazione report → sincronizzazione ticket del
periodo → generazione PDF reale", con i totali verificati esattamente contro i ticket sorgente — replica
del controllo a campione eseguito manualmente su dati reali (owner "OTCO/SO CCTAM", Luglio 2026, 5
ticket, 160 minuti) durante questo checkpoint.

**Riferimenti**
- Requisito/regola di dominio: PRD Fase 4 US-411, AC1 ("almeno un ActivityReport generato per un owner
  reale con ticket e totali verificati a campione").
- Test automatico: `tests/Feature/EndToEnd/Fase4CheckpointEndToEndTest.php` — `an activity report is
  generated for a real owner with tickets and totals verified against the source tickets`.
- File/componente applicativo rilevante: `App\Domain\Reporting\Actions\CreateActivityReport`,
  `App\Domain\Reporting\Services\ActivityReportSyncService`,
  `App\Domain\Reporting\Actions\GenerateActivityReportPdf`.
- Test correlato: F4-26, F4-29, F4-31.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore

**Prerequisiti**
- Ambiente locale/CI con dipendenze installate, Chromium disponibile e suite Pest funzionante.

**Dati di test**
Un owner utente con 3 ticket nel periodo (`worked_minutes` totali `140`), un ticket fuori periodo e un
ticket di un altro richiedente nello stesso periodo (entrambi da escludere).

**Stato iniziale**
Non applicabile.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Posizionarsi nella root del repository | `cd` alla directory del progetto | Directory corrente = root del progetto |
| 2 | Eseguire il test automatico mirato | `vendor/bin/pest --filter "an activity report is generated for a real owner with tickets and totals verified against the source tickets"` | Il comando termina con exit code 0, test passed |

**Risultato finale atteso**
Il report collega esattamente i 3 ticket nel periodo (mai quello fuori periodo né quello di un altro
richiedente), somma `worked_minutes = 140`, `ownerName()`/`periodLabel()` corretti, PDF generato non vuoto.

**Controlli negativi**
Nessuno applicabile (già coperto da F4-26/F4-30 a livello unitario).

**Evidenze da acquisire**
- Output completo del comando Pest eseguito.
- (Verifica manuale già acquisita durante lo sviluppo: report reale "OTCO/SO CCTAM" Luglio 2026, PDF con
  totale "2.67" ore coerente con la somma dei 5 ticket sorgente — vedi progress.txt, US-411.)

**Criterio di superamento**

PASS: il comando Pest termina con exit code 0 e il test indicato risulta passed.
FAIL: il test fallisce o il comando termina con errore.
BLOCKED: Chromium non disponibile nell'ambiente locale/CI.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

---

## Gap e compromessi emersi durante il checkpoint

- Nessun comando `tickets:backfill-dates` dedicato esiste in questo repository: il backfill di
  `released_at`/`done_at` mancanti dai `ticket_logs` importati è già parte integrante dello stage
  `derive` di `v1:import` (`App\Import\Stages\DeriveStage::backfillTimestamps()`), eseguito
  automaticamente ad ogni `v1:import`. Nessuna azione separata è quindi necessaria; questo manuale non
  referenzia un comando inesistente.
- Nessuna commessa reale importata dal v1 ha `estimated_hours` valorizzato (il concetto di SAL/ore
  stimate è introdotto da questa fase, non presente nello schema v1): la verifica SAL su dati reali ha
  richiesto di valorizzare temporaneamente il campo su una commessa esistente, poi ripristinato a `null`
  (vedi sintesi sopra). Non è un difetto applicativo: è atteso che gli utenti reali valorizzino
  `estimated_hours` dopo il go-live di questa fase per le commesse su cui vogliono tracciare il SAL.
- Le 550 righe `activity_reports` importate direttamente dal v1 (con i propri ticket collegati storici)
  non sono mai state ri-sincronizzate con `ActivityReportSyncService::syncTickets()` (quella logica è
  nuova di questa fase, applicata prospetticamente ai report creati da `CreateActivityReport`/
  `reports:generate-monthly` da qui in avanti): i loro conteggi restano quelli originali del v1, non
  quelli che risulterebbero da un ricalcolo con la nuova regola `done_at`-based. Comportamento atteso e
  non un'anomalia: nessun AC di questa fase richiede una migrazione retroattiva dei report storici.
