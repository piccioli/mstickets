# Fase 1 (Ticketing core) — Casi di test dettagliati

> Torna a [`README.md`](README.md) · Istruzioni generali: [`00-istruzioni-generali.md`](00-istruzioni-generali.md) · Matrice di tracciabilità: [`01-matrice-tracciabilita.md`](01-matrice-tracciabilita.md)

74 casi di test (F1-01 — F1-74) su 14 argomenti. Prima di eseguire un test, leggi le convenzioni comuni in `00-istruzioni-generali.md` (in particolare le sezioni 9 "Credenziali", 13 "Preparazione e ripristino dei dati" e 14 "Convenzioni per nominare i dati di test").

## Macchina a stati del ticket

### F1-01 — Percorso principale completo: da "Nuovo" a "Completato" passando per ogni stato

**Obiettivo**
Verificare che un ticket possa percorrere l'intero percorso principale del ciclo di vita (new → assigned → todo → progress → testing → tested → released → done), una transizione alla volta, ciascuna eseguita dall'attore ammesso e con i campi contestuali richiesti. Ogni transizione deve aggiornare il badge di stato e registrare il proprio evento nello storico. Il calcolo esatto delle ore lavorate (che dipende da timestamp deterministici) resta demandato al test automatico.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.3 (tabella delle transizioni), §6.1.4 (single-progress), righe della macchina a stati `new→assigned`, `assigned→todo`, `todo→progress`, `progress→testing`, `testing→tested`, `tested→released`, `released→done`.
- Test automatico: `tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php` — `the main path takes a ticket from new to done through every state with coherent worked minutes` (asserisce stato finale `done`, `previous_status` null, `released_at`/`done_at` valorizzati, 8 log totali di cui 7 `status_changed`, `worked_minutes` = 120 su un unico work log per il developer).
- File/componente applicativo rilevante: `app/Domain/Ticketing/StateMachine/TicketStateMachine.php`, `app/Domain/Ticketing/Actions/ChangeTicketStatus.php`, `app/Filament/Resources/Tickets/Support/TicketTransitionActions.php`.
- Test correlato: F1-02 (percorso senza collaudo interno).

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso al pannello `/admin` come info@montagnaservizi.com (password "uat").
- Esistono gli utenti di collaudo "Lorena Sava" (dall'ETL reale, `v1:import --anonymize`) e "Manager Collaudo" (da `collaudo:ensure-manager-account`, eseguito da `make setup`/deploy subito dopo l'import).

**Dati di test**
- Nuovo ticket con titolo `COLL-F1-01-20260726-01`.
- Assegnatario: "Lorena Sava". Tester: "Lorena Sava" (un solo utente può ricoprire entrambi i ruoli nel percorso).

**Stato iniziale**
Nessun ticket `COLL-F1-01-20260726-01` presente. Il tester è autenticato come admin.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Crea un nuovo ticket dalla lista Ticket | Titolo `COLL-F1-01-20260726-01` | Il ticket viene creato con badge di stato "Nuovo"; nello storico compare un evento di creazione |
| 2 | Apri il dettaglio e usa il bottone di transizione verso "Assegnato" | Modale: Assegnatario = "Lorena Sava" | Badge passa a "Assegnato"; storico registra il cambio `Nuovo → Assegnato` |
| 3 | Esegui la transizione verso "Da fare" | Nessun campo aggiuntivo (solo checkbox "Applica ai figli", lasciare deselezionato) | Badge passa a "Da fare"; storico registra `Assegnato → Da fare` |
| 4 | Esegui la transizione verso "In lavorazione" | Nessun campo aggiuntivo | Badge passa a "In lavorazione"; storico registra `Da fare → In lavorazione` (transizione che dichiara l'effetto di retrocessione degli altri ticket in lavorazione dello stesso assegnatario) |
| 5 | Esegui la transizione verso "In test" | Modale: Tester = "Lorena Sava" | Badge passa a "In test"; storico registra `In lavorazione → In test` |
| 6 | Esegui la transizione verso "Testato" | Nessun campo aggiuntivo | Badge passa a "Testato"; storico registra `In test → Testato` |
| 7 | Esegui la transizione verso "Rilasciato" | Nessun campo aggiuntivo | Badge passa a "Rilasciato"; il campo data di rilascio (`released_at`) viene valorizzato |
| 8 | Esegui la transizione verso "Completato" | Nessun campo aggiuntivo | Badge passa a "Completato"; il campo data di completamento (`done_at`) viene valorizzato; nessun bottone di transizione ulteriore è più disponibile (stato terminale) |

**Risultato finale atteso**
Il ticket è in stato "Completato", con `previous_status` vuoto, `released_at` e `done_at` valorizzati. Lo storico contiene 1 evento di creazione + 7 eventi di cambio stato (uno per ciascuna transizione dei passi 2-8), senza retrocessioni intermedie.

**Controlli negativi**
Ai passi 5 e 2, tentare di confermare la transizione lasciando vuoto il campo obbligatorio (Tester / Assegnatario): il modale non deve consentire l'invio (campo obbligatorio) e, se forzato, la transizione viene rifiutata con messaggio localizzato (vedi F1-05/F1-04).

**Evidenze da acquisire**
- Screenshot del badge di stato dopo ogni transizione (o almeno stato iniziale "Nuovo" e finale "Completato").
- Screenshot dello storico completo del ticket con gli 8 eventi.

**Criterio di superamento**

PASS: il ticket raggiunge lo stato "Completato" attraversando nell'ordine tutti gli stati elencati e lo storico contiene i 7 cambi di stato più la creazione.
FAIL: una qualunque transizione del percorso è indisponibile, produce un errore, salta uno stato, oppure lo stato finale/lo storico non corrispondono.
BLOCKED: impossibile creare il ticket o accedere al pannello.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy (il ticket `COLL-F1-01-...` è aggiuntivo e non altera i ticket importati dall'ETL).

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

### F1-02 — Percorso senza collaudo interno: da "Nuovo" a "Completato" saltando "In test" e "Testato"

**Obiettivo**
Verificare che sia ammesso il percorso che salta la fase di collaudo interno (new → assigned → todo → progress → released → done), cioè che da "In lavorazione" si possa passare direttamente a "Rilasciato" senza transitare per "In test"/"Testato". Nessun evento verso "In test" o "Testato" deve comparire nello storico.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.3, righe `progress→released` (con effetto data di rilascio) e `released→done`.
- Test automatico: `tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php` — `the path without testing takes a ticket from new to done skipping testing and tested` (asserisce stato finale `done`, zero log verso `testing` e verso `tested`, `worked_minutes` = 90).
- File/componente applicativo rilevante: `app/Domain/Ticketing/StateMachine/TicketStateMachine.php` (riga `Progress → Released`), `app/Domain/Ticketing/Actions/ChangeTicketStatus.php`.
- Test correlato: F1-01 (percorso principale completo).

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso al pannello `/admin` come info@montagnaservizi.com.
- Esiste l'utente "Lorena Sava".

**Dati di test**
- Nuovo ticket con titolo `COLL-F1-02-20260726-01`.
- Assegnatario: "Lorena Sava".

**Stato iniziale**
Nessun ticket `COLL-F1-02-20260726-01` presente. Tester autenticato come admin.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Crea un nuovo ticket | Titolo `COLL-F1-02-20260726-01` | Ticket creato in stato "Nuovo" |
| 2 | Transizione verso "Assegnato" | Assegnatario = "Lorena Sava" | Badge "Assegnato" |
| 3 | Transizione verso "Da fare" | Nessun campo aggiuntivo | Badge "Da fare" |
| 4 | Transizione verso "In lavorazione" | Nessun campo aggiuntivo | Badge "In lavorazione" |
| 5 | Verifica i bottoni di transizione disponibili in "In lavorazione" | — | Sono presenti sia "Rilasciato" sia "In test" (percorsi alternativi); scegliere "Rilasciato" |
| 6 | Transizione verso "Rilasciato" | Nessun campo aggiuntivo | Badge "Rilasciato"; data di rilascio valorizzata; nello storico NON compare alcun passaggio per "In test"/"Testato" |
| 7 | Transizione verso "Completato" | Nessun campo aggiuntivo | Badge "Completato"; data di completamento valorizzata |

**Risultato finale atteso**
Il ticket è "Completato". Lo storico non contiene alcun evento con stato di arrivo "In test" o "Testato". Il percorso ha attraversato solo new → assigned → todo → progress → released → done.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dello storico che mostra l'assenza di "In test"/"Testato".
- Screenshot del badge finale "Completato".

**Criterio di superamento**

PASS: il ticket raggiunge "Completato" senza mai passare per "In test"/"Testato".
FAIL: la transizione diretta `In lavorazione → Rilasciato` non è disponibile, oppure lo storico contiene passaggi per "In test"/"Testato".
BLOCKED: impossibile creare il ticket o accedere al pannello.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-03 — Matrice completa delle transizioni: ammesse e vietate per stato, attore e condizioni

**Obiettivo**
Verificare che la macchina a stati ammetta esattamente le transizioni previste dalla tabella dichiarativa, con gli attori e le guardie corretti, e rifiuti tutte le altre con un errore di validazione localizzato. La visibilità del bottone in UI riflette attore + tabella; la guardia (campo richiesto) è verificata solo al submit. La verifica copre sia la UI (presenza/assenza del bottone) sia il livello tecnico (chiamata diretta all'azione rifiutata).

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.3, §9.4/§9.5. Fonte di verità: `TicketStateMachine::transitions()` e `TransitionActor::authorize()`.
- Test automatico: `tests/Feature/Domain/Ticketing/TicketStateMachineTest.php` — `admin can move a new ticket to assigned when assignee_id is provided in context` (più l'intera suite dello stesso file, che copre riga per riga transizioni ammesse/vietate per attore e guardie).
- File/componente applicativo rilevante: `app/Domain/Ticketing/StateMachine/TicketStateMachine.php`, `app/Domain/Ticketing/StateMachine/Transition.php`, `app/Domain/Ticketing/StateMachine/TransitionActor.php`, `app/Filament/Resources/Tickets/Support/TicketTransitionActions.php`.
- Test correlato: F1-01, F1-02, F1-04, F1-05, F1-06, F1-07.

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Admin (con verifiche eseguite anche impersonando Developer e Manager)

**Prerequisiti**
- Accesso al pannello come admin, developer e manager di collaudo.
- Conoscenza degli attori: "Admin/Manager" = chi ha il permesso `ticket.transition.any` (admin e manager); "Assegnatario" = developer con `ticket.update.assigned` che è l'`assignee_id` del ticket; "Tester" = developer con `ticket.update.assigned` che è il `tester_id` del ticket; "Developer auto-assegnante" = developer che nel modale assegna il ticket a sé stesso; "Sistema" = utente di sistema (mai da UI).

**Dati di test**

Matrice completa delle transizioni (20 righe, ricostruita da `TicketStateMachine::transitions()`):

| # | Stato di partenza | Stato di arrivo | Attori ammessi | Guardia (campo richiesto e messaggio) | Effetti collaterali |
|--:|-------------------|-----------------|----------------|----------------------------------------|---------------------|
| 1 | Nuovo | Assegnato | Admin/Manager, Developer auto-assegnante | `assignee_id` valorizzato — "La transizione richiede di specificare un assegnatario." | — |
| 2 | Nuovo | Backlog | Admin/Manager, Developer (nessun rapporto richiesto) | — | — |
| 3 | Nuovo | Rifiutato | Admin/Manager | — | — |
| 4 | Backlog | Assegnato | Admin/Manager, Developer auto-assegnante | `assignee_id` valorizzato — "La transizione richiede di specificare un assegnatario." | — |
| 5 | Backlog | Da fare | Admin/Manager, Developer auto-assegnante | `assignee_id` valorizzato — "La transizione richiede di specificare un assegnatario." | — |
| 6 | Assegnato | Da fare | Admin/Manager, Assegnatario, Sistema | — | — |
| 7 | Da fare | In lavorazione | Admin/Manager, Assegnatario | — | Retrocede gli altri ticket "In lavorazione" dello stesso assegnatario a "Da fare" |
| 8 | In lavorazione | In test | Admin/Manager, Assegnatario | `tester_id` valorizzato — "La transizione richiede di specificare un tester." | — |
| 9 | In lavorazione | Rilasciato | Admin/Manager, Assegnatario | — | Imposta data di rilascio (`released_at`) |
| 10 | In lavorazione | Da fare | Admin/Manager, Assegnatario, Sistema | — | — |
| 11 | In test | Testato | Admin/Manager, Tester | — | — |
| 12 | In test | Da fare | Admin/Manager, Tester | — | — |
| 13 | In test | Rifiutato | Admin/Manager, Tester | — | — |
| 14 | Testato | Rilasciato | Admin/Manager, Assegnatario | — | Imposta data di rilascio (`released_at`) |
| 15 | Rilasciato | Completato | Admin/Manager, Assegnatario | — | Imposta data di completamento (`done_at`) |
| 16 | Nuovo, Backlog, Assegnato, Da fare, In lavorazione | In attesa | Admin/Manager, Assegnatario | `waiting_reason` non vuoto — "Il motivo dell'attesa è obbligatorio." | Salva lo stato precedente |
| 17 | Nuovo, Backlog, Assegnato, Da fare, In lavorazione | Problema | Admin/Manager, Assegnatario | `problem_reason` non vuoto — "Il motivo del blocco è obbligatorio." | Salva lo stato precedente |
| 18 | In attesa | stato precedente (target dinamico) | Admin/Manager, Assegnatario, Sistema | `previous_status` valorizzato — "Non c'è uno stato precedente a cui tornare." | Ripristina lo stato precedente (azzera `previous_status`) |
| 19 | Problema | stato precedente (target dinamico) | Admin/Manager, Assegnatario | `previous_status` valorizzato — "Non c'è uno stato precedente a cui tornare." | Ripristina lo stato precedente (azzera `previous_status`) |
| 20 | Backlog, Assegnato, Da fare, In lavorazione, Testato, Rilasciato, In attesa, Problema (catch-all: ogni stato tranne Nuovo/In test/Completato/Rifiutato) | Rifiutato | Admin/Manager | — | — |

Note aggiuntive sulla matrice, da usare come attese per i casi vietati:
- "Completato" e "Rifiutato" sono stati terminali: nessuna riga parte da essi.
- "Nuovo" e "In test" NON sono coperti dalla riga catch-all #20 verso "Rifiutato": "Nuovo" ha la sua riga dedicata #3 (Admin/Manager), mentre "In test" viene rifiutato solo dal Tester/Admin/Manager tramite la riga #13.
- Il target dinamico delle righe #18/#19 coincide solo con `previous_status`: chiedere un target diverso da `previous_status` mentre si è in "In attesa"/"Problema" risulta "non ammesso" (non un guard fallito).

**Stato iniziale**
Nessun ticket precedente coinvolto: il passo 1 crea un ticket nuovo dedicato (`COLL-F1-03-20260726-01`), in stato "Nuovo", requester = "Sentiero Italia CAI - SICAI". I passi successivi riusano quel ticket o ne identificano altri già presenti nel dataset importato dall'ETL reale (filtrando l'elenco Ticket per "Stato" in Filament) per i casi che richiedono uno stato di partenza diverso (es. "In test" per il passo 3); se un dato stato non è rappresentato nel dump caricato, portare un ticket qualunque in quello stato con i bottoni di transizione già testati in F1-01/F1-02.

**Procedura di esecuzione**

La procedura copre un sottoinsieme rappresentativo della matrice sopra: 3 transizioni ammesse (una per attore — Admin/Manager, Assegnatario, Tester) e 3 vietate (una per attore — developer generico, assegnatario fuori ruolo, sistema).

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | (Ammessa, Admin/Manager) Come admin, crea `COLL-F1-03-20260726-01`, aprilo e transiziona verso "Assegnato" | Assegnatario = "Lorena Sava" | Bottone "Assegnato" presente; transizione riuscita; badge "Assegnato" (riga #1) |
| 2 | (Ammessa, Assegnatario) Autenticati come "Lorena Sava" (assegnatario del ticket del passo 1) e transiziona verso "Da fare" | Nessun campo aggiuntivo | Bottone "Da fare" presente per l'assegnatario; transizione riuscita (riga #6) |
| 3 | (Ammessa, Tester) Su un ticket in stato "In test" con tester = "Lorena Sava" (individuato filtrando l'elenco Ticket per Stato = "In test" e Tester = "Lorena Sava", oppure portando un ticket fino a "In test" con tester dedicato), autenticato come quel tester, transiziona verso "Testato" | Nessun campo aggiuntivo | Bottone "Testato" presente per il tester; transizione riuscita (riga #11) |
| 4 | (Vietata, attore) Autenticato come "Lorena Sava" (developer, senza `ticket.transition.any`), apri un ticket in stato "Nuovo" | — | Il bottone verso "Rifiutato" NON è presente (riga #3 riservata ad Admin/Manager). A livello tecnico, invocare direttamente l'azione di cambio stato verso "Rifiutato" produce un errore di validazione localizzato, senza scrivere nulla |
| 5 | (Vietata, assegnatario su fase di test) Autenticato come assegnatario (non tester) di un ticket in "In test", apri il dettaglio | — | Il bottone "Testato" NON è presente per l'assegnatario (riga #11 riservata a Tester/Admin/Manager); tentativo tecnico rifiutato |
| 6 | (Vietata, sistema) Verifica che l'utente di sistema NON possa eseguire `Rilasciato → Completato` | Verifica tecnica: chiamata all'azione con l'utente di sistema | La transizione è rifiutata con errore localizzato: l'attore "Sistema" non è tra gli attori ammessi della riga #15 (automazione riservata a una fase futura) |

**Risultato finale atteso**
Tutte e tre le transizioni ammesse del sottoinsieme si completano; tutte e tre le vietate non espongono il bottone in UI e vengono rifiutate a livello tecnico con messaggio localizzato, senza scrivere alcuno storico. La matrice sopra è coerente con il comportamento osservato.

**Controlli negativi**
I passi 4-6 sono i controlli negativi. In aggiunta: su un ticket "Completato" o "Rifiutato" nessun bottone di transizione deve comparire.

**Evidenze da acquisire**
- Screenshot dei bottoni di transizione presenti per admin vs developer sullo stesso ticket "Nuovo".
- Screenshot/registrazione dell'errore localizzato su un tentativo vietato.
- Estratto dello storico che dimostra l'assenza di scritture per i tentativi rifiutati.

**Criterio di superamento**

PASS: le transizioni ammesse riescono e le vietate sono bloccate (bottone assente in UI e azione rifiutata a livello tecnico con messaggio localizzato), coerenti con la matrice.
FAIL: una transizione ammessa è bloccata o una vietata riesce/scrive nello storico.
BLOCKED: impossibile autenticarsi con i ruoli richiesti.
NOT APPLICABLE: la parte "Sistema" (passo 6) è verificabile solo tecnicamente; se l'ambiente non consente la verifica tecnica, marcare quella singola voce come NOT APPLICABLE e valutare il resto.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-04 — Auto-assegnazione: un developer si assegna un ticket nuovo, ma non può assegnarlo a un collega

**Obiettivo**
Verificare la regola dell'attore "Developer auto-assegnante": un developer (con solo `ticket.update.assigned`, senza `ticket.transition.any`) può portare un ticket "Nuovo" in "Assegnato" solo se assegna il ticket a sé stesso; non può invece assegnarlo a un collega. La difesa vale sia in UI (il modale precompila l'assegnatario con l'utente corrente e non mostra il campo di scelta) sia a livello di macchina a stati (contesto manipolato rifiutato).

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.3 (attore "Chi"), §9.5. Riga `new→assigned`; attore `TransitionActor::AutoAssigningDeveloper` (ammesso solo se `context['assignee_id'] === $user->id`).
- Test automatico: `tests/Feature/Domain/Ticketing/TicketStateMachineTest.php` — `a developer can self-assign a new ticket (auto-assignment)` (positivo) e `a developer cannot assign a new ticket to somebody else` (negativo).
- File/componente applicativo rilevante: `app/Domain/Ticketing/StateMachine/TransitionActor.php`, `app/Filament/Resources/Tickets/Support/TicketTransitionActions.php` (precompilazione `assignee_id` e assenza del campo quando l'attore si auto-assegna).
- Test correlato: F1-03.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Developer (Lorena Sava)

**Prerequisiti**
- Accesso al pannello come lorena.sava@montagnaservizi.com.
- Esiste almeno un secondo utente-collega (es. "Manager Collaudo") da usare come tentativo di assegnazione errata.
- Esiste un ticket in stato "Nuovo" e senza assegnatario. Nota: filtrare l'elenco Ticket per Stato = "Nuovo" e verificare la colonna Assegnatario; se ogni ticket "Nuovo" del dump importato ha già un assegnatario, creare un ticket nuovo dedicato senza assegnatario per un test pulito.

**Dati di test**
- Ticket `COLL-F1-04-20260726-01` in stato "Nuovo", senza assegnatario.
- Utente corrente: "Lorena Sava". Collega: "Manager Collaudo".

**Stato iniziale**
Ticket `COLL-F1-04-20260726-01` "Nuovo", `assignee_id` vuoto. Tester autenticato come developer.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | (Positivo) Apri il ticket e avvia la transizione verso "Assegnato" | — | Il modale NON mostra il campo "Assegnatario" (auto-assegnazione silenziosa): il developer sta assegnando implicitamente a sé stesso |
| 2 | Conferma la transizione | Solo checkbox "Applica ai figli", deselezionato | Badge passa a "Assegnato"; l'assegnatario del ticket è "Lorena Sava" (l'utente corrente) |
| 3 | (Negativo, livello dati) Simula un payload manipolato che inietta `assignee_id` di un collega nella stessa transizione | `assignee_id` = id di "Manager Collaudo" | Il campo iniettato ma non dichiarato nello schema viene ignorato da Filament: il ticket resta assegnato all'utente corrente, mai al collega |
| 4 | (Negativo, livello macchina a stati) Verifica tecnica: invoca direttamente il cambio stato `Nuovo → Assegnato` con `assignee_id` di un collega, come developer | Contesto `assignee_id` = collega | La macchina a stati rifiuta con errore di validazione localizzato ("Non hai i permessi per eseguire questa transizione su questo ticket."); il ticket resta "Nuovo" e senza assegnatario; nessuno storico scritto |

**Risultato finale atteso**
Il developer riesce ad assegnarsi il ticket (badge "Assegnato", assegnatario = sé stesso), ma ogni tentativo di assegnarlo a un collega viene neutralizzato: dalla UI perché il campo non esiste, dalla macchina a stati perché il contesto impersonato è rifiutato.

**Controlli negativi**
I passi 3 e 4 sono i controlli negativi.

**Evidenze da acquisire**
- Screenshot del modale di transizione senza campo "Assegnatario".
- Screenshot dell'assegnatario finale = "Lorena Sava".
- Registrazione dell'errore/rifiuto sul tentativo con collega.

**Criterio di superamento**

PASS: l'auto-assegnazione riesce e l'assegnazione a un collega è impedita in entrambi i livelli.
FAIL: l'auto-assegnazione fallisce, oppure il ticket risulta assegnato al collega in un qualunque tentativo.
BLOCKED: impossibile autenticarsi come developer o creare il ticket.
NOT APPLICABLE: se il passo 4 (verifica tecnica) non è eseguibile nell'ambiente, marcarlo singolarmente NOT APPLICABLE.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

## Validazioni di dominio del ticket

### F1-05 — La transizione verso "In test" richiede un tester assegnato

**Obiettivo**
Verificare che non sia possibile portare un ticket in "In test" senza aver indicato un tester. La regola di dominio blocca la transizione e riporta il messaggio italiano esatto "La transizione richiede di specificare un tester."

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.3 (guardia della riga `progress→testing`), PRD A3. Regola `TicketTesterRequiredRule` (costante `MESSAGE = "La transizione richiede di specificare un tester."`).
- Test automatico: `tests/Unit/Domain/Ticketing/Rules/TicketTesterRequiredRuleTest.php` — `a null tester_id fails the rule with the italian message` (verifica che un `tester_id` nullo produca esattamente `TicketTesterRequiredRule::MESSAGE`).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Rules/TicketTesterRequiredRule.php`, guardia in `TicketStateMachine.php`, campo obbligatorio "Tester" in `TicketTransitionActions.php`.
- Test correlato: F1-01 (passo verso "In test"), F1-03.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Admin (oppure Developer assegnatario del ticket)

**Prerequisiti**
- Un ticket in stato "In lavorazione" senza tester valorizzato (es. crearne uno e portarlo fino a "In lavorazione").

**Dati di test**
- Ticket `COLL-F1-05-20260726-01` in stato "In lavorazione", `tester_id` vuoto.

**Stato iniziale**
Ticket in "In lavorazione", senza tester.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Apri il ticket e avvia la transizione verso "In test" | — | Il modale mostra il campo obbligatorio "Tester" |
| 2 | Tenta di confermare lasciando "Tester" vuoto | Tester = (vuoto) | La conferma è bloccata: il campo obbligatorio impedisce l'invio. Se l'invio viene forzato/bypassato, la transizione è rifiutata con il messaggio "La transizione richiede di specificare un tester." e il ticket resta "In lavorazione" |
| 3 | Seleziona un tester valido e conferma | Tester = "Lorena Sava" | La transizione riesce; badge passa a "In test" |

**Risultato finale atteso**
Senza tester la transizione verso "In test" è impedita con il messaggio italiano esatto; con tester valorizzato riesce.

**Controlli negativi**
Il passo 2 è il controllo negativo.

**Evidenze da acquisire**
- Screenshot del messaggio di errore/campo obbligatorio.
- Screenshot del badge "In test" dopo l'inserimento del tester.

**Criterio di superamento**

PASS: la transizione senza tester è bloccata con il messaggio esatto; con tester riesce.
FAIL: la transizione riesce senza tester, o il messaggio è diverso da quello atteso.
BLOCKED: impossibile portare il ticket in "In lavorazione".
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-06 — La transizione verso "In attesa" richiede un motivo di attesa non vuoto

**Obiettivo**
Verificare che portare un ticket in "In attesa" richieda un motivo di attesa non vuoto: valori nulli, stringa vuota o composta di soli spazi devono essere rifiutati con il messaggio italiano esatto "Il motivo dell'attesa è obbligatorio."

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.3 (guardia riga `*→waiting`), PRD A3. Regola `TicketWaitingReasonRequiredRule` (`MESSAGE = "Il motivo dell'attesa è obbligatorio."`; fallisce se il valore non è stringa o è vuoto dopo `trim`).
- Test automatico: `tests/Unit/Domain/Ticketing/Rules/TicketWaitingReasonRequiredRuleTest.php` — `null, empty and blank waiting_reason all fail the rule` (verifica che `null`, `''` e `'   '` falliscano tutti).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Rules/TicketWaitingReasonRequiredRule.php`, guardia in `TicketStateMachine.php`, campo obbligatorio "Motivo dell'attesa" in `TicketTransitionActions.php`.
- Test correlato: F1-07 (regola analoga per "Problema").

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Admin (oppure assegnatario del ticket)

**Prerequisiti**
- Un ticket in uno stato dal quale è ammessa la transizione verso "In attesa" (Nuovo, Backlog, Assegnato, Da fare, In lavorazione).

**Dati di test**
- Ticket `COLL-F1-06-20260726-01` in stato "In lavorazione".
- Motivi di prova: `""` (vuoto), `"   "` (soli spazi), poi `"In attesa di riscontro dal socio."` (valido).

**Stato iniziale**
Ticket in "In lavorazione".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Avvia la transizione verso "In attesa" | — | Il modale mostra il campo obbligatorio "Motivo dell'attesa" |
| 2 | Tenta di confermare con motivo vuoto | Motivo = "" | Conferma bloccata; se forzata, rifiuto con "Il motivo dell'attesa è obbligatorio." |
| 3 | Tenta di confermare con soli spazi | Motivo = "   " | Rifiuto con lo stesso messaggio (gli spazi non contano come motivo valido) |
| 4 | Inserisci un motivo valido e conferma | Motivo = "In attesa di riscontro dal socio." | Transizione riuscita; badge "In attesa"; lo stato precedente viene salvato per il successivo ripristino |

**Risultato finale atteso**
La transizione verso "In attesa" è possibile solo con un motivo non vuoto; valori vuoti o di soli spazi sono rifiutati con il messaggio esatto.

**Controlli negativi**
Passi 2 e 3.

**Evidenze da acquisire**
- Screenshot del messaggio di errore su motivo vuoto/di soli spazi.
- Screenshot del badge "In attesa" dopo motivo valido.

**Criterio di superamento**

PASS: motivi vuoti/di soli spazi sono rifiutati con il messaggio esatto; motivo valido consente la transizione.
FAIL: la transizione riesce con motivo vuoto/di soli spazi, o il messaggio differisce.
BLOCKED: impossibile predisporre il ticket in uno stato idoneo.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-07 — La transizione verso "Problema" richiede un motivo del problema non vuoto

**Obiettivo**
Verificare che portare un ticket in "Problema" richieda un motivo del blocco non vuoto: valori nulli, vuoti o di soli spazi devono essere rifiutati con il messaggio italiano esatto "Il motivo del blocco è obbligatorio."

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.3 (guardia riga `*→problem`), PRD A3. Regola `TicketProblemReasonRequiredRule` (`MESSAGE = "Il motivo del blocco è obbligatorio."`).
- Test automatico: `tests/Unit/Domain/Ticketing/Rules/TicketProblemReasonRequiredRuleTest.php` — `null, empty and blank problem_reason all fail the rule`.
- File/componente applicativo rilevante: `app/Domain/Ticketing/Rules/TicketProblemReasonRequiredRule.php`, guardia in `TicketStateMachine.php`, campo obbligatorio "Motivo del blocco" in `TicketTransitionActions.php`.
- Test correlato: F1-06 (regola analoga per "In attesa").

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Admin (oppure assegnatario del ticket)

**Prerequisiti**
- Un ticket in uno stato dal quale è ammessa la transizione verso "Problema" (Nuovo, Backlog, Assegnato, Da fare, In lavorazione).

**Dati di test**
- Ticket `COLL-F1-07-20260726-01` in stato "In lavorazione".
- Motivi di prova: `""`, `"   "`, poi `"Bloccato da un problema tecnico da chiarire."` (valido).

**Stato iniziale**
Ticket in "In lavorazione".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Avvia la transizione verso "Problema" | — | Il modale mostra il campo obbligatorio "Motivo del blocco" |
| 2 | Tenta di confermare con motivo vuoto | Motivo = "" | Conferma bloccata; se forzata, rifiuto con "Il motivo del blocco è obbligatorio." |
| 3 | Tenta di confermare con soli spazi | Motivo = "   " | Rifiuto con lo stesso messaggio |
| 4 | Inserisci un motivo valido e conferma | Motivo = "Bloccato da un problema tecnico da chiarire." | Transizione riuscita; badge "Problema"; lo stato precedente viene salvato |

**Risultato finale atteso**
La transizione verso "Problema" è possibile solo con un motivo non vuoto; valori vuoti/di soli spazi sono rifiutati con il messaggio esatto.

**Controlli negativi**
Passi 2 e 3.

**Evidenze da acquisire**
- Screenshot del messaggio di errore.
- Screenshot del badge "Problema" dopo motivo valido.

**Criterio di superamento**

PASS: motivi vuoti/di soli spazi rifiutati con il messaggio esatto; motivo valido consente la transizione.
FAIL: transizione riuscita con motivo vuoto, o messaggio diverso.
BLOCKED: impossibile predisporre il ticket.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-08 — Un ticket che ha già dei figli non può diventare figlio di un altro ticket

**Obiettivo**
Verificare la regola di profondità massima 1 della gerarchia ticket (PRD §6.1.6): un ticket che possiede già dei figli non può a sua volta essere collegato come figlio di un altro ticket. Il tentativo deve essere rifiutato con il messaggio italiano esatto "Non è ammessa una gerarchia di ticket a più di un livello."

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.6, A3. Regola `TicketParentDepthRule` (`MESSAGE = "Non è ammessa una gerarchia di ticket a più di un livello."`); fallisce se il ticket in modifica ha già figli, oppure se il padre scelto ha già a sua volta un padre.
- Test automatico: `tests/Feature/Domain/Ticketing/Rules/TicketParentDepthRuleTest.php` — `a ticket that already has children cannot itself become a child` (costruisce un ticket con un figlio esistente e verifica che assegnargli un padre faccia fallire la regola).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Rules/TicketParentDepthRule.php`, campo "Ticket padre" (`parent_id`) in `app/Filament/Resources/Tickets/Schemas/TicketForm.php` (la regola è applicata via `->rules()`; la select propone solo ticket senza padre).
- Test correlato: Nessuno.

**Modalità di esecuzione**
MISTO

**Priorità**
Media

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso al pannello come admin (il campo "Ticket padre" è nella sezione principale del form, ma le altre sezioni interne richiedono `ticket.manage-internal-fields`: usare admin).
- Esistono: un ticket A che ha già almeno un figlio, e un ticket root B (senza padre) da usare come tentato padre.

**Dati di test**
- Ticket padre-con-figlio: `COLL-F1-08-20260726-01` (ticket A), a cui è stato collegato un figlio `COLL-F1-08-20260726-02`.
- Ticket root da usare come tentato padre: `COLL-F1-08-20260726-03` (ticket B).

**Stato iniziale**
Ticket A ha un figlio; ticket A non ha padre; ticket B è root senza padre.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Apri in modifica il ticket A (che ha già un figlio) | — | Il form mostra il campo "Ticket padre" |
| 2 | Seleziona come "Ticket padre" il ticket root B e salva | Ticket padre = `COLL-F1-08-20260726-03` | Il salvataggio è rifiutato con l'errore di validazione "Non è ammessa una gerarchia di ticket a più di un livello."; il ticket A NON viene collegato ad alcun padre |
| 3 | (Verifica complementare) Apri in modifica un ticket senza figli e collega lo stesso ticket root B come padre | Ticket senza figli → padre = B | Il salvataggio riesce (profondità 1 rispettata): un ticket foglia può diventare figlio di un root |

**Risultato finale atteso**
Un ticket con figli non può assumere un padre (errore con messaggio esatto); un ticket senza figli può diventare figlio di un root.

**Controlli negativi**
Il passo 2 è il controllo negativo.

**Evidenze da acquisire**
- Screenshot dell'errore di validazione sul campo "Ticket padre".
- Screenshot che mostra il ticket A ancora senza padre dopo il tentativo.

**Criterio di superamento**

PASS: il tentativo del passo 2 è rifiutato con il messaggio esatto; il passo 3 riesce.
FAIL: il ticket A con figli viene collegato a un padre, o il messaggio differisce.
BLOCKED: impossibile predisporre la gerarchia di test.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

## Creazione e cambio di stato del ticket: log ed eventi

### F1-09 — La creazione di un ticket lo porta in stato "Nuovo" e registra uno storico con l'utente autore

**Obiettivo**
Verificare che la creazione di un ticket forzi sempre lo stato iniziale "Nuovo" (indipendentemente da qualunque stato indicato in input) e scriva un evento di storico "creazione" attribuito all'utente autore, mai a un id fisso.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1, A1 (ogni mutazione da Action esplicita, mai hook Eloquent). Action `CreateTicket` (forza `status = new`, scrive log `created`).
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/CreateTicketTest.php` — `creates a ticket in new status regardless of the status attribute passed in` (crea passando `status = done` e verifica che risulti `new`). Test correlati nello stesso file verificano che il log `created` porti `user_id` dell'autore e `is_system = false`.
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/CreateTicket.php`, `app/Filament/Resources/Tickets/Pages/CreateTicket.php`.
- Test correlato: F1-13, F1-01.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Admin (oppure Customer, che ha `ticket.create`)

**Prerequisiti**
- Accesso al pannello come utente con permesso di creazione ticket.

**Dati di test**
- Ticket `COLL-F1-09-20260726-01`.

**Stato iniziale**
Nessun ticket `COLL-F1-09-...` presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Crea un nuovo ticket | Titolo `COLL-F1-09-20260726-01` | Il ticket viene creato con badge di stato "Nuovo" |
| 2 | Apri il dettaglio del ticket e consulta lo "Storico" | — | È presente un evento di creazione; l'autore corrisponde all'utente autenticato che ha creato il ticket |
| 3 | (Verifica tecnica facoltativa) Interroga `ticket_logs` per il ticket creato | Query sul ticket_id | Esiste una riga con evento `created`, `user_id` dell'autore reale e `is_system = false` |

**Risultato finale atteso**
Il ticket è in stato "Nuovo" (anche se in input fosse stato indicato un altro stato) e lo storico riporta un evento di creazione con l'autore corretto.

**Controlli negativi**
Nessuno applicabile (il forzamento dello stato è verificabile a livello tecnico/automatico passando uno stato diverso da "Nuovo").

**Evidenze da acquisire**
- Screenshot del badge "Nuovo" del ticket appena creato.
- Screenshot dello storico con l'evento di creazione e l'autore.

**Criterio di superamento**

PASS: il ticket nasce "Nuovo" e lo storico riporta la creazione con l'autore corretto.
FAIL: il ticket nasce in uno stato diverso, o lo storico manca/attribuisce l'autore errato.
BLOCKED: impossibile creare ticket.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-10 — Una transizione vietata non scrive nulla e restituisce un errore leggibile

**Obiettivo**
Verificare che un tentativo di transizione non ammessa dalla tabella (es. da "Completato" verso "Nuovo") venga rifiutato con un errore di validazione localizzato e non produca alcuna scrittura: né cambio di stato né riga di storico.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.3, A2 (errore di validazione localizzato, mai eccezione generica). Action `ChangeTicketStatus` (avvolge tutto in transazione; la transizione non ammessa è respinta prima di scrivere).
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/ChangeTicketStatusTest.php` — `a forbidden transition writes nothing and raises a localized validation error` (da "Completato" verso "Nuovo": lancia `ValidationException`, il ticket resta "Completato" e ha zero log).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/ChangeTicketStatus.php`, `app/Domain/Ticketing/StateMachine/TicketStateMachine.php`, gestione notifica di errore in `TicketTransitionActions.php`.
- Test correlato: F1-03.

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Admin

**Prerequisiti**
- Un ticket in stato "Completato" (filtrare l'elenco Ticket per Stato = "Completato" e scegliere un qualunque risultato, oppure crearne uno e portarlo a "Completato" con i bottoni di transizione).

**Dati di test**
- Ticket in stato "Completato".

**Stato iniziale**
Ticket "Completato" con il proprio storico esistente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Apri il dettaglio del ticket "Completato" | — | Nessun bottone di transizione è presente (stato terminale): la transizione vietata non è nemmeno offerta in UI |
| 2 | (Verifica tecnica) Forza una richiesta di transizione `Completato → Nuovo` invocando direttamente l'azione di cambio stato | Target = "Nuovo" | La richiesta è rifiutata con errore di validazione localizzato (messaggio del tipo "La transizione da ... a ... non è ammessa."); non viene mostrata alcuna eccezione applicativa non gestita |
| 3 | Verifica lo stato e lo storico del ticket | — | Il ticket è ancora "Completato"; lo storico non contiene nuove righe aggiunte dal tentativo |

**Risultato finale atteso**
Il tentativo di transizione vietata non modifica il ticket né aggiunge righe di storico; l'errore è leggibile e localizzato.

**Controlli negativi**
L'intero test è un controllo negativo sulla transizione vietata.

**Evidenze da acquisire**
- Screenshot che mostra l'assenza di bottoni di transizione su un ticket "Completato".
- Se eseguita la verifica tecnica: registrazione del messaggio di errore e conteggio invariato dello storico.

**Criterio di superamento**

PASS: la transizione vietata è impedita, con errore localizzato e senza alcuna scrittura.
FAIL: la transizione riesce, o produce un'eccezione non gestita, o scrive nello storico.
BLOCKED: impossibile predisporre un ticket "Completato".
NOT APPLICABLE: se la verifica tecnica del passo 2 non è eseguibile, marcarla singolarmente NOT APPLICABLE (il passo 1 resta verificabile in UI).

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-11 — Portare un ticket "In lavorazione" retrocede automaticamente gli altri ticket in lavorazione dello stesso assegnatario

**Obiettivo**
Verificare la regola "un solo ticket in lavorazione per assegnatario" (PRD §6.1.4): quando un ticket dello stesso assegnatario passa a "In lavorazione", gli altri ticket dello stesso assegnatario già "In lavorazione" vengono retrocessi a "Da fare", ciascuno con il proprio evento di storico. I ticket di altri assegnatari non vengono toccati.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.4. Effetto `DemoteOtherProgressTickets` sulla riga `todo→progress`; eseguito da `ChangeTicketStatus`.
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/ChangeTicketStatusTest.php` — `moving a ticket to progress demotes the assignee's other in-progress tickets to todo, each with its own log` (due ticket in lavorazione dello stesso developer vengono retrocessi; il ticket in lavorazione di un altro assegnatario resta invariato; ogni retrocessione scrive il proprio log verso "Da fare").
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/ChangeTicketStatus.php` (metodo di retrocessione), `app/Domain/Ticketing/StateMachine/TransitionEffect.php`.
- Test correlato: F1-12 (rollback in caso di fallimento della retrocessione).

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Admin (predispone gli stati); l'assegnatario è "Lorena Sava"

**Prerequisiti**
- Almeno due ticket assegnati allo stesso developer già in stato "In lavorazione", più un terzo ticket dello stesso developer in "Da fare" da promuovere.
- Un ulteriore ticket in "In lavorazione" assegnato a un altro utente (per verificare che non venga toccato).

**Dati di test**
- Ticket in lavorazione dello stesso developer: `COLL-F1-11-20260726-01`, `COLL-F1-11-20260726-02`.
- Ticket dello stesso developer in "Da fare" da promuovere: `COLL-F1-11-20260726-03`.
- Ticket in lavorazione di altro assegnatario (es. "Manager Collaudo"): `COLL-F1-11-20260726-04`.

**Stato iniziale**
`01` e `02` "In lavorazione" (assegnatario developer); `03` "Da fare" (assegnatario developer); `04` "In lavorazione" (assegnatario diverso).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Porta il ticket `03` a "In lavorazione" | Target = "In lavorazione" | `03` passa a "In lavorazione" |
| 2 | Verifica lo stato di `01` e `02` | — | Entrambi sono retrocessi a "Da fare" |
| 3 | Verifica lo stato di `04` | — | `04` resta "In lavorazione" (assegnatario diverso, non toccato) |
| 4 | Consulta lo storico di `01` e `02` | — | Ciascuno ha un nuovo evento di cambio stato verso "Da fare" |

**Risultato finale atteso**
Un solo ticket dell'assegnatario è "In lavorazione" (il `03`); gli altri suoi ticket precedentemente in lavorazione sono "Da fare", ciascuno con il proprio evento di storico; i ticket di altri assegnatari restano invariati.

**Controlli negativi**
Il passo 3 (ticket di altro assegnatario non toccato) è il controllo negativo.

**Evidenze da acquisire**
- Screenshot degli stati dei ticket `01`, `02`, `03`, `04` dopo l'operazione.
- Screenshot dello storico di `01`/`02` con l'evento di retrocessione.

**Criterio di superamento**

PASS: gli altri ticket in lavorazione dello stesso assegnatario passano a "Da fare" con log dedicato; quelli di altri assegnatari restano invariati.
FAIL: un altro ticket dello stesso assegnatario resta "In lavorazione", oppure un ticket di altro assegnatario viene retrocesso, oppure manca il log di retrocessione.
BLOCKED: impossibile predisporre gli stati iniziali.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-12 — Se la retrocessione automatica di un altro ticket fallisce, l'intera operazione viene annullata

**Obiettivo**
Verificare la transazionalità del cambio di stato (PRD §6.1.4): se durante la promozione di un ticket a "In lavorazione" la retrocessione automatica di un altro ticket dello stesso assegnatario fallisce, l'intera operazione viene annullata (rollback): né il ticket promosso né quello da retrocedere cambiano stato, e non viene scritta alcuna riga di storico.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.4 (atomicità della demozione). `ChangeTicketStatus::run()` avvolge tutto in `DB::transaction()`; un fallimento durante la retrocessione fa rollback dell'intera mutazione.
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/ChangeTicketStatusTest.php` — `the whole transition rolls back if demoting another in-progress ticket fails` (un listener simula un'eccezione durante la retrocessione; dopo l'errore il ticket target è ancora "Da fare", l'altro ancora "In lavorazione", entrambi con zero log).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/ChangeTicketStatus.php`.
- Test correlato: F1-11.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Alta

**Ruolo del tester**
Amministratore di sistema (esecuzione del test automatico)

**Prerequisiti**
- Ambiente di test con la suite automatica disponibile. La condizione di fallimento è simulata tramite un listener che lancia un'eccezione durante la retrocessione: non è riproducibile in modo naturale dall'interfaccia (nessuna azione UI provoca il fallimento della retrocessione), quindi la verifica è affidata al test automatico.

**Dati di test**
- Come da test automatico: un ticket "In lavorazione" e un ticket "Da fare" dello stesso assegnatario; un listener che solleva un'eccezione quando si tenta di retrocedere il primo.

**Stato iniziale**
Un ticket "In lavorazione" e uno "Da fare" per lo stesso assegnatario; listener di fallimento registrato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Esegui il test automatico di riferimento | `php -d memory_limit=1G vendor/bin/pest --filter="the whole transition rolls back if demoting another in-progress ticket fails"` | Il test passa (verde) |
| 2 | Rileggi le asserzioni del test | — | Dopo l'errore: il ticket target è ancora "Da fare", l'altro ancora "In lavorazione", entrambi con zero righe di storico aggiunte |

**Risultato finale atteso**
Il fallimento della retrocessione annulla completamente l'operazione: nessun cambio di stato e nessun log su alcuno dei due ticket.

**Controlli negativi**
Nessuno applicabile (il caso di fallimento è esso stesso il controllo negativo).

**Evidenze da acquisire**
- Output del comando di test con l'esito verde del test filtrato.

**Criterio di superamento**

PASS: il test automatico passa e le asserzioni di rollback (stati invariati, zero log) sono soddisfatte.
FAIL: il test fallisce, oppure a rollback avvenuto restano cambi di stato o righe di storico.
BLOCKED: impossibile eseguire la suite automatica.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: la suite usa un database di test isolato.

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

### F1-13 — L'assegnazione di un ticket a un utente registra uno storico con l'assegnatario precedente e quello nuovo

**Obiettivo**
Verificare che l'assegnazione (o riassegnazione) di un ticket scriva un evento di storico "assegnazione" che riporta, tramite un DTO tipizzato, sia l'assegnatario precedente sia quello nuovo.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.1. Action `AssignTicket` (scrive log `assigned` con `TicketLogChanges::assigneeChanged(previous, new)`).
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/AssignTicketTest.php` — `writes an assigned ticket_log with a typed changes DTO recording the previous and new assignee` (riassegna da un primo a un secondo utente e verifica che `changes` sia `['assignee_id' => ['from' => idPrecedente, 'to' => idNuovo]]`, evento `assigned`, autore = attore).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/AssignTicket.php`, `app/Domain/Ticketing/DTO/TicketLogChanges.php`, gancio in `app/Filament/Resources/Tickets/Pages/EditTicket.php`.
- Test correlato: F1-14 (formato del campo `changes`).

**Modalità di esecuzione**
MISTO

**Priorità**
Media

**Ruolo del tester**
Admin (oppure Manager, che ha `ticket.assign`)

**Prerequisiti**
- Un ticket con un assegnatario iniziale, da riassegnare a un secondo utente.

**Dati di test**
- Ticket `COLL-F1-13-20260726-01`, assegnatario iniziale "Lorena Sava", nuovo assegnatario "Manager Collaudo".

**Stato iniziale**
Ticket con assegnatario = "Lorena Sava".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Apri il ticket in modifica e cambia l'assegnatario | Assegnatario = "Manager Collaudo" | Il ticket risulta assegnato a "Manager Collaudo" |
| 2 | Consulta lo storico | — | Compare un evento di assegnazione che indica il passaggio dal precedente al nuovo assegnatario |
| 3 | (Verifica tecnica facoltativa) Interroga la riga di storico dell'assegnazione | Query su `ticket_logs` | Il campo `changes` contiene `assignee_id` con `from` = id precedente e `to` = id nuovo; l'autore è l'utente che ha eseguito l'operazione |

**Risultato finale atteso**
La riassegnazione produce un evento di storico che traccia esplicitamente assegnatario precedente e nuovo.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot dello storico con l'evento di assegnazione (da → a).

**Criterio di superamento**

PASS: l'evento di assegnazione è presente e riporta correttamente precedente e nuovo assegnatario.
FAIL: l'evento manca o riporta valori errati.
BLOCKED: impossibile riassegnare il ticket.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-14 — Un cambio della descrizione del ticket non salva mai il testo nello storico, solo il fatto che sia cambiato

**Obiettivo**
Verificare che il DTO che popola il campo `changes` dello storico, per la descrizione, registri solo un marcatore "changed" e mai il contenuto del testo della descrizione (comportamento ereditato dal v1, a tutela della privacy/leggerezza dello storico).

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.1 (mai il corpo di `description` nello storico). DTO `TicketLogChanges::descriptionChanged()` (restituisce `['description' => 'changed']`).
- Test automatico: `tests/Unit/Domain/Ticketing/DTO/TicketLogChangesTest.php` — `descriptionChanged never records the field value, only the changed marker` (verifica che il DTO produca esattamente `['description' => 'changed']`).
- File/componente applicativo rilevante: `app/Domain/Ticketing/DTO/TicketLogChanges.php`.
- Test correlato: F1-13.

**Modalità di esecuzione**
AUTOMATICO

**Priorità**
Media

**Ruolo del tester**
Amministratore di sistema (esecuzione del test automatico)

**Prerequisiti**
- Ambiente di test con la suite automatica disponibile. Nota importante: il costruttore `descriptionChanged()` è definito nel DTO ma non è ancora invocato da alcuna Action del codice attuale (nessuna Action registra oggi un cambio di descrizione). Di conseguenza il comportamento non è osservabile dall'interfaccia né dai dati reali di un flusso operativo: la verifica è confinata al test automatico del DTO. Il wiring di una futura Action che logghi il cambio di descrizione è DA VERIFICARE CON IL PRODUCT OWNER.

**Dati di test**
- Come da test automatico: chiamata a `TicketLogChanges::descriptionChanged()`.

**Stato iniziale**
Nessuno stato applicativo richiesto (test unitario puro sul DTO).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Esegui il test automatico di riferimento | `php -d memory_limit=1G vendor/bin/pest --filter="descriptionChanged never records the field value"` | Il test passa (verde) |
| 2 | Rileggi l'asserzione | — | Il DTO produce esattamente `['description' => 'changed']`, senza alcun testo della descrizione |

**Risultato finale atteso**
Il marcatore di cambio descrizione non contiene mai il testo, solo l'indicazione che è cambiato.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output del comando di test con l'esito verde del test filtrato.

**Criterio di superamento**

PASS: il test automatico passa e l'asserzione conferma il solo marcatore.
FAIL: il test fallisce o il DTO include il testo della descrizione.
BLOCKED: impossibile eseguire la suite automatica.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test unitario senza stato persistente.

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

## Propagazione esplicita ai ticket figli

### F1-15 — Il cambio di stato si propaga ai ticket figli diretti solo se richiesto esplicitamente dall'utente

**Obiettivo**
Verificare la decisione Q5 del PRD: il cambio di stato di un ticket padre NON si propaga automaticamente ai figli. I figli cambiano stato solo quando l'utente richiede esplicitamente la propagazione (checkbox "Applica anche ai ticket figli" nel modale di transizione).

**Riferimenti**
- Requisito/regola di dominio: PRD Q5/§6.1. `ChangeTicketStatus` non richiama mai la propagazione; `ApplyStatusToChildren` è l'unico modo di propagare, invocato solo su richiesta esplicita.
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/ApplyStatusToChildrenTest.php` — `changing the parent status alone never propagates to children unless the action is invoked explicitly` (cambiando solo lo stato del padre, il figlio resta invariato e con zero log).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/ApplyStatusToChildren.php`, `app/Domain/Ticketing/Actions/ChangeTicketStatus.php`, checkbox in `app/Filament/Resources/Tickets/Support/TicketTransitionActions.php`.
- Test correlato: F1-16 (figlio saltato quando la transizione non è ammessa).

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Un ticket padre "Nuovo" con almeno un figlio "Nuovo".

**Dati di test**
- Padre `COLL-F1-15-20260726-01` "Nuovo"; figlio `COLL-F1-15-20260726-02` "Nuovo".

**Stato iniziale**
Padre e figlio entrambi "Nuovo".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Sul padre, esegui la transizione verso "Backlog" LASCIANDO deselezionato "Applica anche ai ticket figli" | Checkbox figli = deselezionato | Il padre passa a "Backlog"; il figlio resta "Nuovo"; lo storico del figlio non ha nuove righe |
| 2 | (Propagazione esplicita) Riporta lo scenario e ripeti la transizione del padre SELEZIONANDO "Applica anche ai ticket figli" | Checkbox figli = selezionato | Anche il figlio cambia stato di conseguenza (se la transizione è ammessa per il figlio) |

**Risultato finale atteso**
Senza la richiesta esplicita, il cambio di stato del padre non tocca i figli; con il checkbox selezionato, la propagazione avviene.

**Controlli negativi**
Il passo 1 (nessuna propagazione automatica) è il controllo negativo.

**Evidenze da acquisire**
- Screenshot dello stato del figlio invariato dopo il passo 1.
- Screenshot dello stato del figlio aggiornato dopo il passo 2.

**Criterio di superamento**

PASS: il figlio resta invariato senza checkbox e cambia stato con il checkbox selezionato.
FAIL: il figlio cambia stato senza richiesta esplicita, o non cambia con il checkbox selezionato.
BLOCKED: impossibile predisporre la relazione padre-figlio.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-16 — Un ticket figlio la cui transizione non è ammessa viene saltato, con motivo, senza bloccare gli altri figli

**Obiettivo**
Verificare che, durante la propagazione esplicita ai figli, un figlio per cui la transizione richiesta non è ammessa venga saltato (riportato tra gli "esclusi" con un motivo) senza scrivere nulla su di esso e senza impedire l'applicazione agli altri figli idonei.

**Riferimenti**
- Requisito/regola di dominio: PRD Q5/§6.1. `ApplyStatusToChildren` valuta ogni figlio in isolamento tramite `ChangeTicketStatus`, raccoglie gli applicati e gli esclusi (con motivo), senza transazione unica che blocchi tutto.
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/ApplyStatusToChildrenTest.php` — `a child whose transition is not allowed is skipped, with a reason, without blocking the others` (un figlio "Nuovo" riceve il nuovo stato, un figlio "Completato" viene saltato con un motivo non vuoto e resta invariato con zero log).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/ApplyStatusToChildren.php`, `app/Domain/Ticketing/DTO/ApplyStatusToChildrenResult.php`, notifica di avviso sui figli saltati in `TicketTransitionActions.php`.
- Test correlato: F1-15.

**Modalità di esecuzione**
MISTO

**Priorità**
Media

**Ruolo del tester**
Admin

**Prerequisiti**
- Un ticket padre "Nuovo" con due figli: uno "Nuovo" (transizione ammessa) e uno "Completato" (transizione verso "Backlog" non ammessa, stato terminale).

**Dati di test**
- Padre `COLL-F1-16-20260726-01` "Nuovo".
- Figlio ammesso `COLL-F1-16-20260726-02` "Nuovo".
- Figlio non idoneo `COLL-F1-16-20260726-03` "Completato".

**Stato iniziale**
Padre "Nuovo"; figlio `02` "Nuovo"; figlio `03` "Completato".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Sul padre, esegui la transizione verso "Backlog" SELEZIONANDO "Applica anche ai ticket figli" | Checkbox figli = selezionato; target = "Backlog" | Il figlio `02` passa a "Backlog"; il figlio `03` resta "Completato" |
| 2 | Osserva la notifica/avviso sui figli saltati | — | Viene mostrato un avviso che elenca il figlio `03` con il motivo dello scarto (transizione non ammessa) |
| 3 | Consulta lo storico del figlio `03` | — | Nessuna nuova riga di storico sul figlio saltato |

**Risultato finale atteso**
Il figlio idoneo cambia stato; il figlio non idoneo è saltato con motivo esplicito e resta invariato, senza bloccare l'operazione sugli altri.

**Controlli negativi**
Il figlio `03` saltato senza scritture è il controllo negativo.

**Evidenze da acquisire**
- Screenshot dell'avviso sui figli saltati con il motivo.
- Screenshot degli stati dei figli `02` (aggiornato) e `03` (invariato).

**Criterio di superamento**

PASS: il figlio idoneo è aggiornato, il non idoneo è saltato con motivo e senza scritture, l'operazione non si blocca.
FAIL: l'operazione si interrompe per colpa del figlio non idoneo, o il figlio non idoneo viene modificato, o manca il motivo dello scarto.
BLOCKED: impossibile predisporre i figli negli stati richiesti.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

## Regole sul record — chi vede e modifica quale ticket

### F1-17 — Un developer con permesso limitato agli assegnati non può aggiornare un ticket di cui non è assegnatario né tester

**Obiettivo**
Verificare la regola di record-ownership per la modifica (PRD §9.5): un developer (permesso `ticket.update.assigned`) può modificare un ticket solo se ne è l'assegnatario oppure il tester; su un ticket di cui non è né l'uno né l'altro l'aggiornamento è negato.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.5. `TicketPolicy::update()` (con `ticket.update.assigned` richiede `assignee_id = utente` OPPURE `tester_id = utente`).
- Test automatico: `tests/Feature/Domain/Ticketing/TicketPolicyTest.php` — `a developer (ticket.update.assigned) is denied a ticket they are neither assignee nor tester of` (developer negato su un ticket assegnato ad altri). Il file verifica anche il caso positivo (developer autorizzato se assegnatario o tester).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Policies/TicketPolicy.php`, `app/Domain/Ticketing/Models/Ticket.php`.
- Test correlato: F1-18 (visibilità in lettura).

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Developer (Lorena Sava)

**Prerequisiti**
- Un ticket assegnato a un altro utente (né assegnatario né tester = il developer di prova).
- Un ticket in cui il developer di prova è assegnatario (per il caso positivo).

**Dati di test**
- Ticket di altri: `COLL-F1-17-20260726-01`, assegnatario "Manager Collaudo", tester nessuno.
- Ticket proprio: `COLL-F1-17-20260726-02`, assegnatario "Lorena Sava".

**Stato iniziale**
Ticket `01` assegnato a Manager; ticket `02` assegnato al developer di prova. Tester autenticato come developer.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Prova ad aprire in modifica il ticket `01` (di altri) | — | L'accesso in modifica è negato / l'azione di modifica non è disponibile per questo ticket |
| 2 | Apri in modifica il ticket `02` (proprio, come assegnatario) | — | La modifica è consentita |
| 3 | (Variante positiva) Su un ticket in cui il developer è il tester (non l'assegnatario), verifica l'accesso in modifica | Ticket con tester = developer | La modifica è consentita anche come tester |

**Risultato finale atteso**
Il developer può modificare solo i ticket di cui è assegnatario o tester; è negato sugli altri.

**Controlli negativi**
Il passo 1 (negazione sul ticket di altri) è il controllo negativo.

**Evidenze da acquisire**
- Screenshot della negazione della modifica sul ticket di altri.
- Screenshot della modifica consentita sul ticket proprio.

**Criterio di superamento**

PASS: modifica negata sul ticket di altri, consentita quando il developer è assegnatario o tester.
FAIL: il developer può modificare un ticket di cui non è né assegnatario né tester.
BLOCKED: impossibile predisporre i ticket con le assegnazioni richieste.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-18 — Un cliente vede solo i ticket di cui è il richiedente, mai quelli di altri clienti

**Obiettivo**
Verificare la regola di visibilità in lettura per il ruolo cliente (PRD §9.5): un cliente (permesso `ticket.view.own`) vede solo i ticket di cui è il richiedente, e non quelli di altri clienti, nemmeno quelli in cui risultasse assegnatario.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.5. `Ticket::scopeVisibleTo()` (con `ticket.view.own` restringe a `requester_id = utente`).
- Test automatico: `tests/Feature/Domain/Ticketing/TicketVisibleToScopeTest.php` — `ticket.view.own (customer) sees only their own tickets` (vede il proprio ticket, non quello assegnato a lui ma richiesto da altri, non quello di un altro richiedente).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Models/Ticket.php` (scope `visibleTo`), `app/Domain/Ticketing/Policies/TicketPolicy.php`.
- Test correlato: F1-17 (regola di modifica), F1-19 (visibilità dei messaggi interni).

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Customer (Sentiero Italia CAI - SICAI)

**Prerequisiti**
- Esistono ticket con richiedente diverso dal cliente di prova: filtrare l'elenco Ticket per Richiedente per verificare quanti clienti distinti sono presenti nel dataset importato dall'ETL. Se il dump caricato ha come richiedente solo "Sentiero Italia CAI - SICAI", per il controllo negativo predisporre almeno un ticket con richiedente diverso (es. un secondo cliente creato come admin), oppure verificare via dati/query.

**Dati di test**
- Ticket del cliente di prova: un ticket reale con richiedente "Sentiero Italia CAI - SICAI" (filtrare l'elenco Ticket per Richiedente = "Sentiero Italia CAI - SICAI").
- Ticket di altro richiedente: `COLL-F1-18-20260726-01`, richiedente = un secondo cliente (da creare come admin), assegnatario = "Sentiero Italia CAI - SICAI" (per verificare che l'assegnazione non conceda comunque la visibilità al cliente).

**Stato iniziale**
Almeno un ticket con richiedente = cliente di prova e almeno uno con richiedente diverso. Tester autenticato come "Sentiero Italia CAI - SICAI".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Apri la lista Ticket come cliente | — | Sono elencati solo i ticket di cui il cliente è richiedente |
| 2 | Cerca nella lista il ticket `01` (richiedente diverso) | Titolo `COLL-F1-18-20260726-01` | Il ticket NON compare, anche se il cliente ne è l'assegnatario |
| 3 | (Accesso diretto) Prova ad aprire l'URL di dettaglio del ticket `01` | URL del ticket di altri | L'accesso è negato (il ticket non è visibile al cliente) |

**Risultato finale atteso**
Il cliente vede esclusivamente i ticket di cui è richiedente; i ticket di altri clienti non sono né elencati né raggiungibili per accesso diretto.

**Controlli negativi**
I passi 2 e 3 sono i controlli negativi.

**Evidenze da acquisire**
- Screenshot della lista ticket del cliente (solo i propri).
- Screenshot della negazione all'accesso diretto del ticket di altri.

**Criterio di superamento**

PASS: solo i ticket del cliente sono visibili; quelli di altri clienti non sono elencati né accessibili, nemmeno se il cliente ne è assegnatario.
FAIL: il cliente vede o accede a un ticket di cui non è richiedente.
BLOCKED: impossibile predisporre un ticket con richiedente diverso.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-19 — Un messaggio marcato come interno non è mai raggiungibile da un cliente, nemmeno tramite accesso diretto

**Obiettivo**
Verificare che lo scope di visibilità dei messaggi ticket escluda i messaggi interni per chi non ha il permesso `ticket-message.view.internal`: un cliente non raggiunge mai un messaggio interno, nemmeno con un accesso diretto per identificativo attraverso lo scope. Nota di onestà: in questa release la UI espone SOLO messaggi pubblici in scrittura (non esiste alcun modo di creare un messaggio "interno" dall'interfaccia), quindi il test riguarda principalmente lo scope a livello di query/dati; in UI è verificabile solo indirettamente (un cliente non vede messaggi interni nella conversazione, qualora ne esistessero di importati).

**Riferimenti**
- Requisito/regola di dominio: PRD §9.5. `TicketMessage::scopeVisibleTo()` (esclude i messaggi con visibilità "interna" per chi non ha `ticket-message.view.internal`).
- Test automatico: `tests/Feature/Domain/Ticketing/TicketMessageVisibleToScopeTest.php` — `a customer cannot reach an internal message even via direct by-id access through the scope` (una ricerca per id attraverso lo scope restituisce null per un messaggio interno; un membro dello staff con il permesso lo raggiunge).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Models/TicketMessage.php` (scope `visibleTo`).
- Test correlato: F1-18.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Customer (Sentiero Italia CAI - SICAI) per l'osservazione in UI; Amministratore di sistema per la verifica sui dati/scope

**Prerequisiti**
- Deve esistere almeno un messaggio con visibilità "interna" su un ticket visibile al cliente. Poiché la UI non consente di crearne, il messaggio interno va predisposto a livello dati (inserimento diretto in `ticket_messages` con visibilità interna) oppure la verifica si affida al test automatico. Predisposizione dati diretta: DA VERIFICARE CON IL PRODUCT OWNER se ammessa nell'ambiente UAT.

**Dati di test**
- Un ticket di cui il cliente è richiedente, con: un messaggio pubblico e un messaggio interno (quest'ultimo predisposto a livello dati).

**Stato iniziale**
Ticket del cliente con un messaggio pubblico e un messaggio interno. Cliente autenticato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | (UI) Come cliente, apri il ticket e consulta la conversazione | — | Compare solo il messaggio pubblico; il messaggio interno non è visibile |
| 2 | (Dati/scope) Verifica tramite lo scope di visibilità che una ricerca per identificativo del messaggio interno, applicata per il cliente, non restituisca il record | Id del messaggio interno | La ricerca restituisce "nessun risultato" (il messaggio interno è irraggiungibile per il cliente) |
| 3 | (Contro-prova staff) Ripeti la ricerca per un utente staff con il permesso di vedere i messaggi interni | Utente con `ticket-message.view.internal` | Lo staff raggiunge il messaggio interno |

**Risultato finale atteso**
Il messaggio interno è invisibile e irraggiungibile per il cliente (anche per accesso diretto per id via scope), mentre resta accessibile allo staff autorizzato.

**Controlli negativi**
I passi 1 e 2 sono i controlli negativi lato cliente.

**Evidenze da acquisire**
- Screenshot della conversazione lato cliente (solo messaggio pubblico).
- Esito della verifica su dati/scope (nessun risultato per il cliente; risultato positivo per lo staff).

**Criterio di superamento**

PASS: il cliente non vede né raggiunge il messaggio interno; lo staff autorizzato sì.
FAIL: il cliente vede o raggiunge il messaggio interno tramite lo scope.
BLOCKED: impossibile predisporre un messaggio interno nell'ambiente (in tal caso affidarsi al solo test automatico e annotarlo).
NOT APPLICABLE: se la predisposizione dati diretta non è ammessa in UAT, la parte UI (passi 1) è NOT APPLICABLE e la verifica resta al test automatico.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy (eventuali messaggi interni predisposti a livello dati vengono azzerati).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

## Conversazione del ticket

### F1-20 — Pubblicare un messaggio produce HTML formattato sanitizzato e un corpo testuale derivato

**Obiettivo**
Verificare che un messaggio pubblicato tramite l'azione "Aggiungi messaggio" della pagina di
dettaglio ticket venga sempre salvato con canale `web`, visibilità `public`, un `body_html`
formattato solo con gli elementi di formattazione consentiti dalla RichEditor/dall'allowlist server-side,
e un `body_text` (versione solo-testo) derivato dal corpo già sanitizzato. Questi tre campi
(`channel`, `visibility`, `body_text`) non sono mostrati da nessuna vista Filament: la sola
verifica visiva del messaggio pubblicato in "Conversazione" non basta a dimostrare il comportamento,
serve un controllo tecnico sui dati salvati.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.7 — unico punto di ingresso per la conversazione, canale
  sempre `web`/visibilità sempre `public` in questa fase.
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/PostTicketMessageTest.php` —
  `creates a public web message with sanitized html and a derived plain text body`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/PostTicketMessage.php`;
  `app/Domain/Ticketing/Support/TicketMessageSanitizer.php`; azione `post_message` in
  `app/Filament/Resources/Tickets/Pages/ViewTicket.php`; sezione "Conversazione" in
  `app/Filament/Resources/Tickets/Schemas/TicketInfolist.php`.
- Test correlato: F1-25 (sanitizzazione di un tag `<script>`, caso specifico di sicurezza non
  riproducibile tramite la RichEditor — vedi nota in quel test).

**Modalità di esecuzione**
MISTO (azione da interfaccia Filament + verifica tecnica sui campi non visibili in UI)

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore (Developer)

**Prerequisiti**
- Utente `lorena.sava@montagnaservizi.com` con accesso al pannello `/admin`.
- Accesso a `php artisan tinker` (o equivalente) sull'ambiente collaudato, per il controllo tecnico finale.
- Un ticket reale del dataset importato dall'ETL, tipo "Bug", stato "Nuovo", assegnatario
  "Lorena Sava", con almeno un messaggio già presente in "Conversazione" (individuato
  filtrando l'elenco Ticket per Stato = "Nuovo" e Tipo = "Bug" e verificandone il dettaglio). Se nel
  dump caricato nessun ticket soddisfa questa combinazione, crearne uno con i dati richiesti e
  pubblicare un primo messaggio con la stessa azione "Aggiungi messaggio" prima di eseguire la
  procedura sotto.

**Dati di test**
Testo del messaggio: digitare "Ciao " (testo semplice), poi selezionare la parola "mondo" e applicare
il pulsante Grassetto della toolbar RichEditor, per ottenere "Ciao **mondo**" (marker `COLL-F1-20-DATA-01`
aggiunto in coda al testo per riconoscere il messaggio di collaudo, es. "Ciao mondo COLL-F1-20-DATA-01").

**Stato iniziale**
Il ticket ha già almeno un messaggio di conversazione presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Login come `lorena.sava@montagnaservizi.com`, aprire il ticket individuato sopra | Filtro tabella ticket: Stato = "Nuovo", Tipo = "Bug" | Pagina di dettaglio ticket aperta, sezione "Conversazione" con almeno un messaggio esistente |
| 2 | Cliccare l'azione header "Aggiungi messaggio" | — | Si apre il modale con campo RichEditor "Messaggio" e campo "Allegati" |
| 3 | Digitare "Ciao " nel campo Messaggio, selezionare "mondo" (digitato subito dopo) e cliccare il pulsante Grassetto, poi aggiungere " COLL-F1-20-DATA-01" | "Ciao **mondo** COLL-F1-20-DATA-01" | Il testo "mondo" appare in grassetto nell'editor |
| 4 | Confermare l'invio | — | Notifica di successo "Messaggio pubblicato"; il nuovo messaggio compare in fondo alla sezione "Conversazione" con "mondo" mostrato in grassetto, autore "Lorena Sava", data/ora corrente |
| 5 | Aprire `php artisan tinker` e recuperare l'ultimo messaggio del ticket: `App\Domain\Ticketing\Models\TicketMessage::where('ticket_id', <id>)->latest('posted_at')->first()` | Script tinker | Restituisce il record appena creato |
| 6 | Ispezionare i campi `channel`, `visibility`, `body_html`, `body_text`, `is_legacy_import` del record | — | `channel` = `web`; `visibility` = `public`; `body_html` contiene `<strong>mondo</strong>`; `body_text` = "Ciao mondo COLL-F1-20-DATA-01" (nessun tag HTML); `is_legacy_import` = `false` |

**Risultato finale atteso**
Il messaggio è visibile in UI con la formattazione corretta e, a livello dati, ha canale `web`,
visibilità `public`, `body_html` con solo markup consentito e `body_text` una versione testuale pulita
derivata dal corpo già sanitizzato.

**Controlli negativi**
Nessuno applicabile (il comportamento negativo — rimozione di markup non ammesso — è coperto da F1-25).

**Evidenze da acquisire**
- Screenshot della sezione "Conversazione" con il nuovo messaggio formattato.
- Output testuale del comando tinker del passo 5-6 (valori dei campi).

**Criterio di superamento**

PASS: il messaggio è pubblicato con la formattazione attesa in UI e i campi `channel`/`visibility`/`body_text`/`is_legacy_import` hanno i valori indicati al passo 6.
FAIL: uno qualunque dei campi tecnici non corrisponde, oppure il messaggio non compare in UI.
BLOCKED: l'azione "Aggiungi messaggio" non è disponibile o restituisce un errore imprevisto.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy (l'ETL reale, `v1:import --anonymize`, gira ad ogni deploy su `develop`).

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

### F1-21 — L'autore di un messaggio viene aggiunto ai partecipanti, senza righe duplicate

**Obiettivo**
Verificare che postare un messaggio aggiunga l'autore ai partecipanti del ticket (`ticket_participants`)
se non già presente, e che postare un secondo messaggio dallo stesso autore non crei una seconda riga
duplicata — visibile in UI come un'unica badge col nome dell'autore nella sezione "Partecipanti",
mai due badge identiche.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.7 — `syncWithoutDetaching` sull'autore, mai un duplicato.
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/PostTicketMessageTest.php` —
  `adds the author to ticket participants if not already present` (e, nello stesso file, `does not
  duplicate the participant row if the author already participates`)
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/PostTicketMessage.php` (riga
  `$ticket->participants()->syncWithoutDetaching([$author->id])`); sezione "Partecipanti" in
  `app/Filament/Resources/Tickets/Schemas/TicketInfolist.php`.
- Test correlato: F1-20 (stessa azione "Aggiungi messaggio").

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Sviluppatore (Developer)

**Prerequisiti**
- Utente `lorena.sava@montagnaservizi.com` con accesso al pannello.
- Un ticket con la sezione "Partecipanti" vuota (placeholder "Nessun partecipante"): filtrare
  l'elenco Ticket per Tipo = "Feature" e Stato = "Backlog" e verificare il dettaglio di uno o più
  risultati, oppure un ticket qualunque del dataset importato la cui sezione "Partecipanti" risulti
  vuota (l'ETL importa la pivot `ticket_participants` dal v1 solo dove esisteva già esplicitamente,
  quindi la maggior parte dei ticket reali parte senza partecipanti). Se nessun ticket del dump
  soddisfa il criterio, creare un ticket nuovo dedicato: parte sempre senza partecipanti.

**Dati di test**
Due messaggi di testo semplice: "Prima verifica COLL-F1-21-DATA-01" e "Seconda verifica COLL-F1-21-DATA-02".

**Stato iniziale**
Sezione "Partecipanti" del ticket vuota (placeholder "Nessun partecipante").

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Login come `lorena.sava@montagnaservizi.com`, aprire il ticket individuato sopra | — | Sezione "Partecipanti" mostra "Nessun partecipante" |
| 2 | Cliccare "Aggiungi messaggio", digitare il primo testo, confermare | "Prima verifica COLL-F1-21-DATA-01" | Notifica di successo; sezione "Partecipanti" ora mostra esattamente 1 badge "Lorena Sava" |
| 3 | Ricaricare la pagina del ticket | — | La sezione "Partecipanti" mostra ancora esattamente 1 badge "Lorena Sava" |
| 4 | Cliccare di nuovo "Aggiungi messaggio", digitare il secondo testo, confermare | "Seconda verifica COLL-F1-21-DATA-02" | Notifica di successo; il nuovo messaggio compare in "Conversazione" |
| 5 | Osservare la sezione "Partecipanti" | — | Ancora esattamente 1 badge "Lorena Sava" (mai 2 badge identiche) |

**Risultato finale atteso**
Dopo due messaggi dello stesso autore, la sezione "Partecipanti" mostra esattamente una badge col
nome dell'autore, mai duplicata.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della sezione "Partecipanti" dopo il passo 2 (1 badge) e dopo il passo 5 (ancora 1 badge).

**Criterio di superamento**

PASS: dopo entrambi i messaggi la sezione "Partecipanti" mostra esattamente una badge con il nome dell'autore.
FAIL: la badge non compare dopo il primo messaggio, oppure compaiono due badge identiche dopo il secondo.
BLOCKED: l'azione "Aggiungi messaggio" non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-22 — I destinatari calcolati del messaggio combinano partecipanti/richiedente/assegnatario/tester, deduplicati, escluso l'autore

**Obiettivo**
Verificare che `Ticket::messageRecipients(User $author)` restituisca l'unione di partecipanti,
richiedente, assegnatario e tester del ticket, deduplicata per id ed esclusa sempre la persona che
ha scritto il messaggio. Questo metodo non ha alcuna superficie UI in questa release (nessun invio
email reale, nessuna lista "destinatari" mostrata in Filament: PRD §15.2/CLAUDE.md, "questa fase si
limita a esporlo, nessun invio reale avviene ancora") — è verificabile solo a livello di codice/dati.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.7 — destinatari riusabili da una futura Action di invio email (Fase 3).
- Test automatico: `tests/Feature/Domain/Ticketing/TicketMessageRecipientsTest.php` —
  `recipients are participants plus requester, assignee and tester, deduplicated, excluding the author`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Models/Ticket.php` (metodo `messageRecipients`).
- Test correlato: F1-20/F1-21 (stesso dominio "conversazione", nessuna dipendenza diretta).

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Media

**Ruolo del tester**
Sviluppatore/Amministratore di sistema

**Prerequisiti**
- Accesso a `php artisan tinker` sull'ambiente collaudato.
- Un ticket in stato "In test" (Testing) esistente nel dataset importato dall'ETL: sui dati reali
  `requester_id`/`assignee_id`/`tester_id` sono quelli effettivamente registrati sul ticket in v1
  (identità anonimizzate qualunque, non necessariamente le identità di riferimento del collaudo —
  nessun ticket reale può avere Manager Collaudo come assegnatario, ruolo introdotto solo in v2).
  Scegliere un ticket con tutti e tre i campi valorizzati e distinti tra loro.

**Dati di test**
```php
$ticket = App\Domain\Ticketing\Models\Ticket::where('status', App\Domain\Ticketing\Enums\TicketStatus::Testing)
    ->whereDoesntHave('participants')
    ->whereNotNull('assignee_id')
    ->whereNotNull('tester_id')
    ->first();
$fundraising = App\Domain\Identity\Models\User::where('email', 'sara.mariani@montagnaservizi.com')->first();
$admin = App\Domain\Identity\Models\User::where('email', 'info@montagnaservizi.com')->first();
$ticket->participants()->attach($fundraising->id);
$recipients = $ticket->messageRecipients($admin);
$recipients->pluck('id')->sort()->values()->all();
```
Se la prima riga restituisce `null` (nessun ticket "In test" senza partecipanti con entrambi i
campi valorizzati sul dump caricato), rilassare uno dei filtri o scegliere manualmente un ticket
idoneo dalla lista Filament.

**Stato iniziale**
Ticket in stato "In test" scelto come sopra, senza partecipanti.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Aprire `php artisan tinker` sull'ambiente collaudato | — | Prompt tinker attivo |
| 2 | Recuperare il ticket "In test" e gli utenti Fundraising/Admin | Righe 1-6 dello script sopra | Nessun errore, variabili popolate, `$ticket` non `null` |
| 3 | Aggiungere Fundraising come partecipante | Riga 7 dello script | `$ticket->participants()->count()` restituisce `1` |
| 4 | Calcolare i destinatari con Admin come autore | Riga 8 dello script | `$recipients` è una Collection non vuota |
| 5 | Estrarre e ordinare gli id dei destinatari | Riga 9 dello script | L'array restituito è identico a `[$ticket->requester_id, $ticket->assignee_id, $ticket->tester_id, $fundraising->id]` ordinato, senza l'id di Admin |

**Risultato finale atteso**
I destinatari calcolati sono esattamente i 4 utenti distinti tra richiedente, assegnatario e tester
del ticket scelto (identità reali anonimizzate dal dump) più Sara Mariani come
partecipante aggiunto, mai l'autore (Admin).

**Controlli negativi**
Ripetere il calcolo passando come autore lo stesso utente Fundraising appena aggiunto come
partecipante (`$ticket->messageRecipients($fundraising)`): il risultato deve contenere solo
richiedente/assegnatario/tester (3 id), escludendo Fundraising sia come partecipante duplicato sia
come autore.

**Evidenze da acquisire**
- Output testuale completo della sessione tinker (righe 1-6 e il controllo negativo).

**Criterio di superamento**

PASS: l'array ordinato degli id restituiti al passo 5 coincide esattamente con l'insieme atteso di 4 id.
FAIL: mancano id attesi, ne compaiono di inattesi, oppure l'autore compare tra i destinatari.
BLOCKED: impossibile aprire una sessione tinker sull'ambiente.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere manualmente la riga di partecipazione aggiunta al passo 3 (`$ticket->participants()->detach($fundraising->id);`), oppure nessuna azione: il dataset si rigenera comunque al prossimo deploy.

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

### F1-23 — Un messaggio del richiedente su un ticket "In attesa" lo riporta allo stato precedente

**Obiettivo**
Verificare la regola T7 (§6.1.5, decisione Q14): quando il richiedente scrive un messaggio su un
ticket in stato "In attesa" (`waiting`), il ticket torna automaticamente allo stato precedente
(`previous_status`), con il cambio di stato attribuito all'utente di sistema "Sistema".

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.5, decisione Q14 — regola T7.
- Test automatico: `tests/Feature/Domain/Ticketing/Listeners/RestoreTicketStatusOnRequesterMessageTest.php`
  — `a requester message on a waiting ticket restores it to previous_status, attributed to the system user`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Listeners/RestoreTicketStatusOnRequesterMessage.php`;
  `app/Domain/Ticketing/Actions/ChangeTicketStatus.php`; bottoni di transizione in
  `app/Filament/Resources/Tickets/Support/TicketTransitionActions.php`.
- Test correlato: F1-24 (stessa regola T7, ramo assigned/progress → todo).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore (Developer) per il setup, Sentiero Italia CAI - SICAI (Customer) per l'azione sotto test

**Prerequisiti**
- Utenti `lorena.sava@montagnaservizi.com` e `infosentieroitalia@cai.it`.
- Un ticket reale in stato "In lavorazione", assegnatario "Lorena Sava" e richiedente
  "Sentiero Italia CAI - SICAI" (filtrare l'elenco Ticket per Stato = "In lavorazione", Assegnatario =
  "Lorena Sava" e Richiedente = "Sentiero Italia CAI - SICAI"; se nessun ticket del dump soddisfa
  tutti i criteri, crearne uno e portarlo in quello stato con i bottoni di transizione già testati in
  F1-01). Nota: un ticket già in stato "In attesa" nel dump importato potrebbe non avere
  `previous_status` valorizzato se lo stato "In attesa" risale a prima dell'import (l'ETL ricostruisce
  `status_changed_at`/`previous_status` una tantum dallo storico v1, §11.4 del PRD): per questo test
  serve comunque partire da "In lavorazione" e transitare in "In attesa" con un'azione reale, che
  valorizza `previous_status` correttamente.

**Dati di test**
Motivo dell'attesa: "COLL-F1-23-DATA-01: attesa riscontro socio". Messaggio di risposta del
richiedente: "Ecco la risposta richiesta COLL-F1-23-DATA-02".

**Stato iniziale**
Ticket in stato "In lavorazione" (Progress), `previous_status` nullo.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Login come `lorena.sava@montagnaservizi.com`, aprire il ticket individuato sopra | Filtro: Stato = "In lavorazione", Assegnatario = "Lorena Sava" | Badge di stato "In lavorazione" visibile |
| 2 | Cliccare il bottone di transizione "In attesa", compilare "Motivo dell'attesa", lasciare deselezionato "Applica anche ai ticket figli", confermare | "COLL-F1-23-DATA-01: attesa riscontro socio" | Notifica "Stato del ticket aggiornato"; badge di stato ora "In attesa" |
| 3 | Logout, login come `infosentieroitalia@cai.it`, riaprire lo stesso ticket | — | Ticket visibile (il richiedente del ticket è "Sentiero Italia CAI - SICAI"), badge "In attesa" |
| 4 | Cliccare "Aggiungi messaggio", digitare il testo di risposta, confermare | "Ecco la risposta richiesta COLL-F1-23-DATA-02" | Notifica "Messaggio pubblicato" |
| 5 | Osservare il badge di stato del ticket (aggiornamento reattivo o dopo ricarica pagina) | — | Il badge passa da "In attesa" a "In lavorazione" |
| 6 | Logout, login come `lorena.sava@montagnaservizi.com` (per vedere la sezione "Storico", visibile solo a chi ha il permesso `ticket-log.view`), riaprire il ticket | — | La sezione "Storico" mostra una nuova riga "Cambio di stato" con Utente = "Sistema", relativa al passaggio "In attesa" → "In lavorazione" |

**Risultato finale atteso**
Il ticket è tornato in stato "In lavorazione" (lo stato precedente all'attesa), con il cambio
attribuito a "Sistema" in Storico, senza alcuna azione manuale di transizione da parte dello staff.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot del badge di stato prima (passo 2) e dopo (passo 5) la risposta del cliente.
- Screenshot della riga "Storico" del passo 6 con Utente = "Sistema".

**Criterio di superamento**

PASS: il badge di stato torna a "In lavorazione" dopo il messaggio del richiedente, con la riga di Storico attribuita a "Sistema".
FAIL: lo stato resta "In attesa" dopo la risposta, oppure il cambio risulta attribuito a un utente diverso da "Sistema".
BLOCKED: il bottone "In attesa" non è disponibile al passo 2, o l'azione "Aggiungi messaggio" non è disponibile al passo 4.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy. Se necessario ripetere il test, individuare un altro ticket "In lavorazione" con gli stessi criteri.

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

### F1-24 — Un messaggio del richiedente su un ticket assegnato o in lavorazione lo riporta a "Da fare"

**Obiettivo**
Verificare il secondo ramo della regola T7: quando il richiedente scrive un messaggio su un ticket
in stato "Assegnato" o "In lavorazione", il ticket passa automaticamente a "Da fare" (nessuna
transizione se il ticket è già "Da fare"), attribuita a "Sistema".

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.5, decisione Q14 — regola T7, ramo assigned/progress → todo.
- Test automatico: `tests/Feature/Domain/Ticketing/Listeners/RestoreTicketStatusOnRequesterMessageTest.php`
  — `a requester message on an assigned or in-progress ticket moves it to todo` (data set su entrambi gli stati)
- File/componente applicativo rilevante: `app/Domain/Ticketing/Listeners/RestoreTicketStatusOnRequesterMessage.php`.
- Test correlato: F1-23.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Sentiero Italia CAI - SICAI (Customer) per l'azione, Sviluppatore (Developer) per la verifica in Storico

**Prerequisiti**
- Utenti `infosentieroitalia@cai.it` e `lorena.sava@montagnaservizi.com`.
- Un ticket reale in stato "Assegnato", assegnatario "Lorena Sava" e richiedente
  "Sentiero Italia CAI - SICAI" (filtrare l'elenco Ticket per Stato = "Assegnato", Assegnatario =
  "Lorena Sava" e Richiedente = "Sentiero Italia CAI - SICAI"; se nessun ticket del dump soddisfa
  tutti i criteri, crearne uno e portarlo in quello stato con i bottoni di transizione). Nessun setup
  aggiuntivo necessario oltre a questo: a differenza di F1-23, il ramo "Assegnato/In lavorazione → Da
  fare" non dipende da `previous_status`.

**Dati di test**
Messaggio di risposta del richiedente: "Confermo il mio indirizzo email COLL-F1-24-DATA-01".

**Stato iniziale**
Ticket in stato "Assegnato".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Login come `infosentieroitalia@cai.it`, aprire il ticket individuato sopra | — | Badge di stato "Assegnato" visibile |
| 2 | Cliccare "Aggiungi messaggio", digitare il testo, confermare | "Confermo il mio indirizzo email COLL-F1-24-DATA-01" | Notifica "Messaggio pubblicato" |
| 3 | Osservare il badge di stato (aggiornamento reattivo o dopo ricarica pagina) | — | Il badge passa da "Assegnato" a "Da fare" |
| 4 | Logout, login come `lorena.sava@montagnaservizi.com`, riaprire il ticket | — | Badge "Da fare" confermato |
| 5 | Ispezionare la sezione "Storico" | — | Nuova riga "Cambio di stato" con Utente = "Sistema", da "Assegnato" a "Da fare" |

**Risultato finale atteso**
Il ticket è in stato "Da fare" subito dopo la risposta del richiedente, con il cambio attribuito a "Sistema".

**Controlli negativi**
Ripetere la stessa procedura su un ticket già in stato "Da fare" richiesto da "Sentiero Italia CAI - SICAI" (filtro Stato = "Da fare", Richiedente = "Sentiero Italia CAI - SICAI"): dopo
il messaggio del richiedente, il badge deve restare "Da fare" e la sezione "Storico" non deve
mostrare alcuna nuova riga "Cambio di stato" (nessuna transizione `todo → todo` in tabella).

**Evidenze da acquisire**
- Screenshot del badge di stato prima (passo 1) e dopo (passo 3).
- Screenshot della riga "Storico" del passo 5.
- Screenshot del controllo negativo (badge invariato su un ticket già "Da fare").

**Criterio di superamento**

PASS: il badge passa da "Assegnato"/"In lavorazione" a "Da fare" dopo il messaggio del richiedente, attribuito a "Sistema"; il controllo negativo non produce alcuna transizione.
FAIL: lo stato non cambia, oppure il controllo negativo produce comunque un cambio di stato.
BLOCKED: l'azione "Aggiungi messaggio" non è disponibile.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-25 — Uno script incorporato in un messaggio viene rimosso interamente, mai lasciato inline

**Obiettivo**
Verificare che `TicketMessageSanitizer` (invocato da `PostTicketMessage::run()` prima di salvare
`body_html`) rimuova un tag `<script>` e tutto il suo contenuto, senza lasciarne traccia nel
messaggio pubblicato. Questo è il test di sicurezza più diretto sulla conversazione: un allowlist di
elementi, non una blocklist, quindi ogni tag non elencato (incluso `<script>`) sparisce insieme al
proprio contenuto.

**Nota sulla modalità di esecuzione**: la RichEditor di Filament (basata su Tiptap) impedisce di
norma di digitare un vero tag `<script>` nell'editor visuale — i caratteri `<`/`>` digitati vengono
trattati come testo letterale (mostrato come caratteri visibili, non come markup), quindi un tentativo
di digitare il payload direttamente nella UI non riproduce lo scenario reale testato dal test
automatico (un vero `<script>` in `body_html`). Il sanitizzatore server-side è comunque una difesa
essenziale contro qualunque chiamata diretta a `PostTicketMessage::run()` che non passi dalla
RichEditor (un futuro endpoint API, un client diverso, o una richiesta Livewire manipolata via
browser DevTools): questo test lo verifica al livello dove il rischio è reale, chiamando l'Action
direttamente.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.7/§8.7 — "mai `{!! !!}` su input utente", allowlist esplicito.
- Test automatico: `tests/Unit/Domain/Ticketing/Support/TicketMessageSanitizerTest.php` —
  `strips a script tag and its content entirely, never leaving it inline` (verificare anche
  `tests/Feature/Domain/Ticketing/Actions/PostTicketMessageTest.php::creates a public web message
  with sanitized html and a derived plain text body`, che esercita lo stesso sanitizzatore passando
  dall'Action completa).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Support/TicketMessageSanitizer.php`;
  `app/Domain/Ticketing/Actions/PostTicketMessage.php`.
- Test correlato: F1-20.

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore/Amministratore di sistema

**Prerequisiti**
- Accesso a `php artisan tinker` sull'ambiente collaudato.

**Dati di test**
Payload HTML grezzo: `<script>alert('collaudo')</script>Ciao, ho un problema`.

**Stato iniziale**
Nessuno specifico: un ticket qualsiasi del dataset importato dall'ETL, con un autore diverso dal
richiedente per non innescare la regola T7 (fuori scope di questo test).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Aprire `php artisan tinker` | — | Prompt attivo |
| 2 | Recuperare un ticket e l'utente Sviluppatore (non richiedente): `$ticket = App\Domain\Ticketing\Models\Ticket::first(); $developer = App\Domain\Identity\Models\User::where('email','lorena.sava@montagnaservizi.com')->first();` | — | Variabili popolate |
| 3 | Pubblicare il messaggio con il payload grezzo: `$message = App\Domain\Ticketing\Actions\PostTicketMessage::run($ticket, $developer, "<script>alert('collaudo')</script>Ciao, ho un problema");` | Payload sopra | Nessuna eccezione, `$message` è un'istanza `TicketMessage` |
| 4 | Ispezionare `$message->body_html` | — | Non contiene la stringa `<script` (case-insensitive) né il testo `alert('collaudo')`; contiene "Ciao, ho un problema" |
| 5 | Ispezionare `$message->body_text` | — | È esattamente "Ciao, ho un problema" (nessun residuo del tag, nessuna entità HTML) |
| 6 | Aprire il ticket in `/admin` come qualunque utente autorizzato e verificare la sezione "Conversazione" | — | Il messaggio pubblicato mostra solo il testo "Ciao, ho un problema", nessuno script eseguito, nessun testo `<script>` visibile |

**Risultato finale atteso**
Il messaggio salvato e quello mostrato in UI contengono esclusivamente "Ciao, ho un problema": lo
script è stato rimosso interamente, sia dal markup salvato sia dalla versione testuale derivata.

**Controlli negativi**
Ripetere con un payload che usa un gestore di evento invece di un tag `<script>` (es.
`<img src=x onerror="alert(1)">Ciao`): il risultato atteso è identico, `body_html` non contiene né
`<img` né `onerror`, `body_text` è "Ciao" (coperto anche dal test automatico "strips a disallowed
element but is not in the allowlist for event handler attributes" nello stesso file).

**Evidenze da acquisire**
- Output testuale della sessione tinker (passi 2-5).
- Screenshot della sezione "Conversazione" del passo 6.

**Criterio di superamento**

PASS: né `body_html` né `body_text` né la UI mostrano tracce dello script o del suo contenuto; il testo innocuo è preservato.
FAIL: lo script (o il gestore di evento) compare in una qualunque delle tre verifiche.
BLOCKED: impossibile aprire una sessione tinker sull'ambiente.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

## Allegati sui messaggi

### F1-26 — Un file di tipo ammesso viene caricato correttamente e salvato su disco privato

**Obiettivo**
Verificare che un file di tipo/dimensione ammessi caricato tramite l'azione "Aggiungi messaggio"
venga effettivamente accettato, associato al messaggio e salvato sul disco privato dedicato
`ticket-attachments` (mai un disco pubblico). L'accettazione è visibile in UI (link "Scarica
allegato" col nome del file), ma la verifica che il disco sia realmente quello privato configurato
richiede un controllo tecnico.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.6/§17.2, US-107 — disco sempre privato.
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/AddTicketAttachmentTest.php` —
  `stores an allowed file on the private disk and returns the media`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/AddTicketAttachment.php`;
  `config/ticketing.php` (chiave `attachments.disk` = `ticket-attachments`); azione `post_message`
  in `app/Filament/Resources/Tickets/Pages/ViewTicket.php`.
- Test correlato: F1-29 (riusa l'allegato creato qui per il test di accesso negato).

**Modalità di esecuzione**
MISTO (upload da UI + verifica tecnica del disco/tipo MIME salvato)

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore (Developer)

**Prerequisiti**
- Utente `lorena.sava@montagnaservizi.com`.
- Un file immagine reale in formato JPEG, dimensione contenuta (es. 200 KB), rinominato
  `COLL-F1-26-DATA-01.jpg`.
- Accesso a `php artisan tinker` per la verifica tecnica.
- Un ticket reale in stato "Testato", tipo "Helpdesk", senza allegati sui messaggi esistenti
  (filtrare l'elenco Ticket per Stato = "Testato" e Tipo = "Helpdesk" e verificare la sezione
  "Conversazione" del dettaglio). Se nessun ticket del dump soddisfa il criterio, portarne uno in
  quello stato con i bottoni di transizione già testati (F1-01), il che garantisce anche l'assenza di
  allegati su un ticket appena creato.

**Dati di test**
File: `COLL-F1-26-DATA-01.jpg`, estensione `jpg`, contenuto reale JPEG (non un file vuoto rinominato),
dimensione indicativa 200 KB (ben sotto il limite di 10 MB). Testo messaggio: "Allego screenshot COLL-F1-26-DATA-01".

**Stato iniziale**
Il ticket non ha allegati sui messaggi esistenti.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Login come `lorena.sava@montagnaservizi.com`, aprire il ticket individuato sopra | Filtro: Stato = "Testato", Tipo = "Helpdesk" | Nessun allegato visibile nei messaggi esistenti |
| 2 | Cliccare "Aggiungi messaggio", digitare il testo, trascinare `COLL-F1-26-DATA-01.jpg` nel campo "Allegati", confermare | File e testo sopra | Notifica "Messaggio pubblicato" (nessuna notifica di errore) |
| 3 | Osservare il nuovo messaggio nella sezione "Conversazione" | — | Compare un link "COLL-F1-26-DATA-01.jpg" cliccabile |
| 4 | Cliccare il link e verificare che il file si apra/scarichi correttamente (immagine visibile) | — | Il browser mostra/scarica l'immagine caricata |
| 5 | Aprire `php artisan tinker` e recuperare il media appena creato: `$media = Spatie\MediaLibrary\MediaCollections\Models\Media::latest('id')->first();` | — | Restituisce il record |
| 6 | Ispezionare `$media->disk`, `$media->collection_name`, `$media->mime_type` | — | `disk` = `ticket-attachments`; `collection_name` = `attachments`; `mime_type` = `image/jpeg` |

**Risultato finale atteso**
Il file è accettato, visibile/scaricabile dalla conversazione, e risulta salvato sulla collection
`attachments` del disco privato `ticket-attachments` (mai `public`).

**Controlli negativi**
Nessuno applicabile (coperto da F1-27).

**Evidenze da acquisire**
- Screenshot del link allegato nella conversazione.
- Output tinker del passo 5-6.

**Criterio di superamento**

PASS: il file è accettato, scaricabile, e i campi tecnici del passo 6 corrispondono ai valori attesi.
FAIL: il file viene rifiutato, oppure il disco/collection/mime non corrispondono.
BLOCKED: l'upload non è possibile per un errore imprevisto della piattaforma.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy (il file caricato su `ticket-attachments` viene perso col reset).

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

### F1-27 — Un file con estensione non ammessa o oltre la dimensione massima viene rifiutato

**Obiettivo**
Verificare che l'upload di un allegato venga rifiutato PRIMA di essere scritto su disco quando
l'estensione non è nella lista condivisa ammessa, oppure quando il file supera la dimensione massima
configurata (10 MB di default, `config('ticketing.attachments.max_file_size')`), con un messaggio di
errore localizzato mostrato in UI — mai un'eccezione generica, mai il messaggio pubblicato con
l'allegato comunque agganciato.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.6/§17.2, US-107, decisione A2 (errore di validazione localizzato).
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/AddTicketAttachmentTest.php` —
  `rejects a file whose extension is not in the shared allowed list` e, nello stesso file,
  `rejects a file larger than the configured maximum size`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/AddTicketAttachment.php`
  (metodo `guardAgainstDisallowedFile`); `config/ticketing.php` (`max_file_size` = 10 × 1024 × 1024
  byte di default, confermato anche da `tests/Unit/Domain/Ticketing/Support/TicketAttachmentTypesTest.php::max
  file size defaults to 10 MB`); gestione dell'errore per-file in
  `app/Filament/Resources/Tickets/Pages/ViewTicket.php` (`postMessageAction`).
- Test correlato: F1-26 (stesso ticket, scenario positivo).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore (Developer)

**Prerequisiti**
- Utente `lorena.sava@montagnaservizi.com`.
- File 1: `virus.exe`, estensione `exe` (non presente in nessuna delle tre liste documenti/immagini/audio
  di `config/ticketing.php`), dimensione qualunque (es. 10 KB).
- File 2: `documento-grande.pdf`, estensione ammessa (`pdf`), contenuto PDF reale, dimensione 11 MB
  (11.534.336 byte), superiore al limite di 10 MB.
- DA VERIFICARE CON IL PRODUCT OWNER: i limiti infrastrutturali `upload_max_filesize`/`post_max_size`
  di PHP e il limite lato Livewire (`temporary_file_upload`, default pacchetto ~12 MB, non
  ridefinito in `config/livewire.php` in questo repository) devono essere sufficienti a far
  transitare un file di 11 MB fino al livello applicativo, altrimenti il file verrebbe
  troncato/rifiutato dal server prima ancora di raggiungere `AddTicketAttachment` — non è il
  comportamento sotto test, va escluso come causa di un eventuale BLOCKED.
- Lo stesso ticket individuato in F1-26 (o un altro ticket qualsiasi).

**Dati di test**
- File 1: `virus.exe`, estensione `exe`, ~10 KB, contenuto qualsiasi.
- File 2: `documento-grande.pdf`, estensione `pdf`, 11 MB, contenuto PDF reale.

**Stato iniziale**
Ticket con conversazione esistente, nessun allegato aggiuntivo (oltre a quello eventualmente creato in F1-26).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Login come `lorena.sava@montagnaservizi.com`, aprire il ticket, cliccare "Aggiungi messaggio" | — | Modale aperto |
| 2 | Digitare un testo di messaggio, allegare `virus.exe`, confermare | "Tentativo allegato non ammesso COLL-F1-27-DATA-01" + `virus.exe` | Notifica di successo "Messaggio pubblicato" (il testo viene comunque pubblicato) MA una notifica di errore "Allegato non valido" con testo "Il tipo di file 'exe' non è ammesso come allegato." |
| 3 | Osservare la sezione "Conversazione" per il messaggio appena creato | — | Il messaggio di testo è presente, ma NESSUN link allegato compare |
| 4 | Cliccare di nuovo "Aggiungi messaggio", digitare un nuovo testo, allegare `documento-grande.pdf` (11 MB), confermare | "Tentativo file oltre dimensione COLL-F1-27-DATA-02" + `documento-grande.pdf` | Notifica di successo per il messaggio, MA notifica di errore "Allegato non valido" con testo "Il file supera la dimensione massima consentita per gli allegati." |
| 5 | Osservare la sezione "Conversazione" per questo secondo messaggio | — | Il messaggio di testo è presente, nessun link allegato |

**Risultato finale atteso**
Entrambi i file (estensione non ammessa, dimensione oltre il limite) vengono rifiutati con un
messaggio di errore specifico e non risultano mai allegati ad alcun messaggio, mentre il testo del
messaggio viene comunque pubblicato regolarmente.

**Controlli negativi**
Ripetere il passo 2 con un file `documento.txt` di piccola dimensione (estensione ammessa, `txt` è
nella lista documenti): deve essere accettato senza notifica di errore, a conferma che il rifiuto dei
passi 2/4 dipende esclusivamente dall'estensione/dimensione e non da un problema generale dell'azione.

**Evidenze da acquisire**
- Screenshot delle notifiche di errore dei passi 2 e 4 (testo esatto del messaggio).
- Screenshot della sezione "Conversazione" senza allegati ai passi 3 e 5.

**Criterio di superamento**

PASS: entrambi i file vengono rifiutati con la notifica di errore attesa e non compaiono come allegati; il controllo negativo con file ammesso ha successo.
FAIL: uno dei due file viene accettato, oppure la notifica di errore non compare, oppure il messaggio di testo non viene pubblicato.
BLOCKED: impossibile completare l'upload di 11 MB per un limite infrastrutturale non applicativo (vedi nota sopra) — annotare la causa esatta prima di concludere BLOCKED.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-28 — La rimozione di un allegato che non appartiene al messaggio indicato viene rifiutata

**Obiettivo**
Verificare che `RemoveTicketAttachment` controlli realmente l'appartenenza del `Media` al
`TicketMessage` indicato (collection, model_type, model_id) prima di cancellarlo, rifiutando la
richiesta con un errore di validazione se l'allegato appartiene in realtà a un altro messaggio. In
questa release non esiste alcuna azione UI per rimuovere un allegato (la pagina di dettaglio ticket
espone solo l'azione "Aggiungi messaggio", nessuna rimozione allegati): questa regola di integrità
dei dati è verificabile solo invocando l'Action direttamente.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.6, US-107 — "mai fidarsi solo dell'id passato dal chiamante".
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/RemoveTicketAttachmentTest.php` —
  `refuses to remove a media that does not belong to the given ticket message`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/RemoveTicketAttachment.php`
  (metodo `guardMediaBelongsToMessage`).
- Test correlato: F1-26 (fornisce l'allegato da usare come "media esistente").

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore/Amministratore di sistema

**Prerequisiti**
- Accesso a `php artisan tinker`.
- Un allegato già esistente su un messaggio (riutilizzare quello creato in F1-26, oppure crearne uno
  nuovo ripetendo quella procedura).

**Dati di test**
```php
$messageWithAttachment = App\Domain\Ticketing\Models\TicketMessage::whereHas('media')->first();
$media = $messageWithAttachment->getMedia('attachments')->first();
$otherMessage = App\Domain\Ticketing\Models\TicketMessage::where('id', '!=', $messageWithAttachment->id)->first();
$developer = App\Domain\Identity\Models\User::where('email', 'lorena.sava@montagnaservizi.com')->first();
```

**Stato iniziale**
Un messaggio con esattamente 1 allegato; un secondo messaggio esistente senza alcuna relazione con quell'allegato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Aprire `php artisan tinker` e popolare le variabili | Script sopra | Nessun errore, `$media` appartiene realmente a `$messageWithAttachment` |
| 2 | Confermare il conteggio allegati del messaggio originale prima della prova | `$messageWithAttachment->fresh()->getMedia('attachments')->count()` | `1` |
| 3 | Tentare la rimozione passando il messaggio SBAGLIATO: `App\Domain\Ticketing\Actions\RemoveTicketAttachment::run($otherMessage, $media, $developer);` | Chiamata sopra | Viene lanciata `Illuminate\Validation\ValidationException` con messaggio "Questo allegato non appartiene al messaggio indicato." |
| 4 | Verificare che l'allegato NON sia stato rimosso: `$messageWithAttachment->fresh()->getMedia('attachments')->count()` | — | Ancora `1` (invariato) |

**Risultato finale atteso**
La rimozione viene rifiutata con un errore di validazione esplicito e l'allegato resta intatto sul messaggio a cui appartiene realmente.

**Controlli negativi**
Ripetere la rimozione passando questa volta il messaggio CORRETTO
(`RemoveTicketAttachment::run($messageWithAttachment, $media, $developer)`): deve avere successo
senza eccezioni, e il conteggio allegati deve scendere a `0` — a conferma che il rifiuto del passo 3
dipende esclusivamente dal mismatch messaggio/allegato, non da un problema generale dell'Action.

**Evidenze da acquisire**
- Output testuale completo della sessione tinker (passi 1-4 e controllo negativo).

**Criterio di superamento**

PASS: la rimozione con il messaggio sbagliato viene rifiutata con `ValidationException` e l'allegato resta; la rimozione con il messaggio corretto ha successo.
FAIL: la rimozione con il messaggio sbagliato riesce (l'allegato viene cancellato), oppure non viene lanciata alcuna eccezione.
BLOCKED: impossibile aprire una sessione tinker sull'ambiente.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Il controllo negativo rimuove volutamente l'allegato: nessuna azione ulteriore necessaria, il dataset si rigenera comunque al prossimo deploy.

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

### F1-29 — Un utente che non può vedere il ticket non può scaricarne un allegato, nemmeno conoscendo il link diretto

**Obiettivo**
Verificare che il download di un allegato deleghi sempre l'autorizzazione a `TicketPolicy::view()`
sul ticket proprietario del messaggio, negando l'accesso a un utente che non può vedere quel ticket
anche se prova ad accedere direttamente all'URL della rotta di download (nessun URL medialibrary
pubblico, nessun controllo duplicato o più permissivo).

**Riferimenti**
- Requisito/regola di dominio: PRD §9.5/§9.6 — download autorizzato solo da chi può vedere il ticket.
- Test automatico: `tests/Feature/Http/TicketAttachmentDownloadControllerTest.php` —
  `a user who cannot view the ticket is denied, even by direct id access`
- File/componente applicativo rilevante: `app/Http/Controllers/TicketAttachmentDownloadController.php`;
  rotta `GET /ticket-attachments/{media}` (`routes/web.php`, nome `ticket-attachments.download`,
  middleware `auth`).
- Test correlato: F1-26 (fornisce l'allegato usato come link diretto).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore (Developer) per recuperare il link, Sara Mariani per il tentativo negato

**Prerequisiti**
- Un allegato esistente su un ticket (riutilizzare quello di F1-26).
- Utente `sara.mariani@montagnaservizi.com`: il ruolo Fundraising non ha alcun permesso
  `ticket.view.*` nella matrice di `RolePermissionSeeder` (`database/seeders/RolePermissionSeeder.php`),
  quindi non può vedere NESSUNO dei ticket del dataset importato — è il candidato corretto per "un
  utente che non può vedere il ticket", a differenza del Sentiero Italia CAI - SICAI che è invece richiedente
  di uno o più ticket reali e potrebbe vederli.

**Dati di test**
URL diretto della rotta di download dell'allegato creato in F1-26, es. `https://<host>/ticket-attachments/<id media>`.

**Stato iniziale**
Allegato esistente e scaricabile da chi ha accesso al ticket (verificato in F1-26).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Login come `lorena.sava@montagnaservizi.com`, aprire il ticket con l'allegato di F1-26 | — | Link "COLL-F1-26-DATA-01.jpg" visibile in "Conversazione" |
| 2 | Tasto destro sul link, "Copia indirizzo link" | — | URL del tipo `/ticket-attachments/<id>` copiato |
| 3 | Logout, login come `sara.mariani@montagnaservizi.com` | — | Login riuscito, pannello accessibile (Fundraising ha accesso al pannello per il proprio modulo) |
| 4 | Incollare l'URL copiato al passo 2 direttamente nella barra degli indirizzi del browser e navigare | URL copiato | Il server risponde con una pagina di errore "403 | Questa azione non è autorizzata." (Forbidden), il file NON viene scaricato |

**Risultato finale atteso**
L'utente Fundraising, che non può vedere alcun ticket, riceve un rifiuto (HTTP 403) accedendo
direttamente all'URL dell'allegato, senza alcuna possibilità di scaricarlo.

**Controlli negativi**
Ripetere il passo 4 da NON autenticato (sessione slogata): il middleware `auth` deve reindirizzare
alla pagina di login del pannello, mai servire il file.

**Evidenze da acquisire**
- Screenshot della pagina 403 del passo 4.
- Screenshot del redirect al login per l'utente non autenticato.

**Criterio di superamento**

PASS: l'utente Fundraising riceve un 403 sul link diretto; l'utente non autenticato viene reindirizzato al login.
FAIL: il file viene scaricato/mostrato in uno dei due casi.
BLOCKED: impossibile ottenere l'URL diretto dell'allegato al passo 2.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-30 — Un allegato SVG viene servito sanitizzato: lo script incorporato viene rimosso prima del download

**Obiettivo**
Verificare che un file SVG allegato, se contiene uno `<script>` incorporato, venga sanitizzato al
volo dal controller di download (`TicketAttachmentSvgSanitizer`) PRIMA di essere servito — mai in
fase di upload — così che il contenuto scaricato/visualizzato non contenga mai lo script, pur
mantenendo il markup SVG legittimo (es. le forme geometriche disegnate).

**Riferimenti**
- Requisito/regola di dominio: PRD §17.2 nota — un SVG può contenere `<script>`/gestori `on*`/`javascript:`.
- Test automatico: `tests/Feature/Http/TicketAttachmentDownloadControllerTest.php` —
  `serves a sanitized svg, stripping the embedded script before responding`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Support/TicketAttachmentSvgSanitizer.php`;
  `app/Http/Controllers/TicketAttachmentDownloadController.php` (branch `mime_type === 'image/svg+xml'`).
- Test correlato: F1-25 (stesso principio di allowlist, applicato a un formato diverso).

**Modalità di esecuzione**
MISTO (upload da UI + verifica tecnica del contenuto grezzo servito)

**Priorità**
Critica

**Ruolo del tester**
Sviluppatore (Developer)

**Prerequisiti**
- Utente `lorena.sava@montagnaservizi.com`.
- File SVG malevolo reale, nome `COLL-F1-30-DATA-01.svg`, contenuto testuale esatto:
  `<svg xmlns="http://www.w3.org/2000/svg"><script>alert('collaudo')</script><circle r="4"/></svg>`.
- Un ticket reale qualsiasi, diverso da quello usato in F1-26/27 (per non confondere gli allegati), senza allegati SVG esistenti: filtrare l'elenco Ticket per Stato = "In lavorazione" e scegliere un risultato diverso da quello già usato.

**Dati di test**
File `COLL-F1-30-DATA-01.svg` come sopra; testo messaggio "Allego icona con contenuto sospetto COLL-F1-30-DATA-01".

**Stato iniziale**
Ticket senza allegati SVG.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Login come `lorena.sava@montagnaservizi.com`, aprire il ticket individuato sopra | Filtro: Stato = "In lavorazione" | — |
| 2 | Cliccare "Aggiungi messaggio", digitare il testo, allegare `COLL-F1-30-DATA-01.svg`, confermare | File e testo sopra | Notifica "Messaggio pubblicato" (nessun errore: `svg` è nella lista immagini ammesse) |
| 3 | Cliccare il link "COLL-F1-30-DATA-01.svg" nella conversazione | — | Il browser apre/mostra il file in una nuova scheda: nessun popup/alert JavaScript compare (lo script non viene eseguito) |
| 4 | Salvare il file mostrato ("Salva con nome") e aprirlo con un editor di testo (non un browser) | — | Il contenuto NON contiene la stringa `<script` né `alert('collaudo')`; contiene ancora `<circle` |

**Risultato finale atteso**
Il file SVG servito dal download non contiene mai lo script incorporato, pur conservando il markup
SVG legittimo; nessun alert JavaScript viene mai eseguito aprendo l'allegato.

**Controlli negativi**
Nessuno applicabile (il caso "SVG legittimo senza script" è implicitamente coperto ogni volta che il
markup residuo, es. `<circle>`, sopravvive al passo 4).

**Evidenze da acquisire**
- Screenshot dell'apertura del file al passo 3 (nessun alert).
- Contenuto testuale del file salvato al passo 4 (allegare come evidenza).

**Criterio di superamento**

PASS: nessun alert JavaScript viene eseguito e il contenuto salvato non contiene lo script, ma conserva il markup SVG legittimo.
FAIL: l'alert viene eseguito, oppure il contenuto scaricato contiene ancora il tag `<script>`.
BLOCKED: il file SVG viene rifiutato in upload (non dovrebbe accadere, `svg` è tipo ammesso).
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-31 — La lista di tipi e dimensioni ammessi per gli allegati è unica e condivisa, non duplicata

**Obiettivo**
Verificare che `TicketAttachmentTypes` sia davvero l'unica fonte di verità per estensioni/mime/dimensione
massima ammessi per gli allegati (unione di documenti, immagini e audio da `config/ticketing.php`),
e che non esista una seconda lista duplicata altrove nel codice applicativo. Non è una regola
osservabile tramite un unico comportamento UI: è una proprietà architetturale verificata a livello di
codice/configurazione.

**Riferimenti**
- Requisito/regola di dominio: PRD §17.2 — "unica lista, mai duplicata".
- Test automatico: `tests/Unit/Domain/Ticketing/Support/TicketAttachmentTypesTest.php` —
  `allowed extensions merge documents, images and audio from config`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Support/TicketAttachmentTypes.php`;
  `config/ticketing.php` (chiavi `attachments.documents/images/audio.extensions/mimes`).
- Test correlato: F1-26/F1-27 (usano indirettamente la stessa lista per accettare/rifiutare i file).

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Media

**Ruolo del tester**
Sviluppatore/Amministratore di sistema

**Prerequisiti**
- Accesso a `php artisan tinker` e al codice sorgente del repository.

**Dati di test**
Nessun dato di input: la verifica confronta l'output dei metodi statici con la configurazione.

**Stato iniziale**
Configurazione di default (`config/ticketing.php` non sovrascritta da variabili d'ambiente non standard).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Aprire `php artisan tinker` | — | Prompt attivo |
| 2 | Eseguire `App\Domain\Ticketing\Support\TicketAttachmentTypes::allowedExtensions();` | — | Restituisce un array contenente almeno `pdf, doc, docx, xls, xlsx, ppt, pptx, json, geojson, txt, csv, zip, jpg, jpeg, png, gif, bmp, webp, svg, tiff, heic, mp3, m4a, wav, ogg, aac, flac, wma, mp4`, senza duplicati |
| 3 | Eseguire `App\Domain\Ticketing\Support\TicketAttachmentTypes::maxFileSize();` | — | Restituisce `10485760` (10 MB) |
| 4 | Eseguire `App\Domain\Ticketing\Support\TicketAttachmentTypes::disk();` | — | Restituisce `ticket-attachments` |
| 5 | Cercare nel codice sorgente altre occorrenze letterali della stessa lista di estensioni (es. `grep -rn "pdf.*doc.*docx" app/`) | Comando grep | Nessuna occorrenza al di fuori di `config/ticketing.php` e `TicketAttachmentTypes` stesso |
| 6 | Verificare che sia `AddTicketAttachment` sia la registrazione della media collection (`TicketMessage::registerMediaCollections()`) richiamino `TicketAttachmentTypes::` invece di ridichiarare la lista | Lettura del codice | Confermato: entrambi i punti chiamano i metodi statici di `TicketAttachmentTypes` |

**Risultato finale atteso**
Un'unica classe (`TicketAttachmentTypes`), che legge un'unica configurazione (`config/ticketing.php`),
è la sola fonte delle estensioni/mime/dimensione ammessi in tutto il codice applicativo.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output testuale della sessione tinker (passi 2-4).
- Output del comando di ricerca del passo 5.

**Criterio di superamento**

PASS: gli output tinker corrispondono ai valori attesi e nessuna lista duplicata viene trovata nel codice.
FAIL: viene trovata una seconda lista di estensioni/mime scritta a mano altrove, oppure i valori restituiti non corrispondono alla configurazione.
BLOCKED: impossibile aprire una sessione tinker o accedere al codice sorgente.
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

## Tracciamento visualizzazioni

### F1-32 — La prima visualizzazione di un ticket nel giorno crea un nuovo record di visualizzazione

**Obiettivo**
Verificare che aprire per la prima volta in giornata la pagina di dettaglio di un ticket registri una
riga in `ticket_views` (`ticket_id`, `user_id`, `viewed_on`, `last_viewed_at`, `view_count = 1`).
Non esiste alcuna vista Filament che mostri contatori/visualizzazioni: la registrazione è invisibile
in UI e va verificata a livello dati.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.3, US-108.
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/RecordTicketViewTest.php` —
  `the first view of the day creates a ticket_view row`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/RecordTicketView.php`;
  hook esplicito in `app/Filament/Resources/Tickets/Pages/ViewTicket.php::resolveRecord()`.
- Test correlato: F1-33, F1-34 (stesso ticket riusabile in sequenza).

**Modalità di esecuzione**
MISTO (apertura pagina da UI + verifica tecnica sulla tabella `ticket_views`)

**Priorità**
Media

**Ruolo del tester**
Sentiero Italia CAI - SICAI (Customer)

**Prerequisiti**
- Utente `infosentieroitalia@cai.it`.
- Accesso a `php artisan tinker` per la verifica.
- Un ticket reale in stato "Da fare" con richiedente "Sentiero Italia CAI - SICAI" (filtrare l'elenco Ticket
  per Stato = "Da fare" e Richiedente = "Sentiero Italia CAI - SICAI", scegliendo un risultato non ancora
  usato in altri test di questo pacchetto; se nessuno esiste nel dump caricato, crearne uno con
  richiedente "Sentiero Italia CAI - SICAI" e portarlo in quello stato con i bottoni di transizione).
- Nessuna visualizzazione precedente di questo ticket da parte dell'utente Customer nella giornata
  odierna (verificare prima con la query del passo 3 sotto, oppure usare un ticket diverso se già
  visitato in precedenza durante la sessione di collaudo).

**Dati di test**
Nessun dato di input diretto: l'azione è implicita nell'apertura della pagina.

**Stato iniziale**
Nessuna riga in `ticket_views` per (ticket, utente, oggi).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Aprire `php artisan tinker` e verificare l'assenza di righe pregresse: `App\Domain\Ticketing\Models\TicketView::whereDate('viewed_on', today())->count();` (per il ticket/utente scelti) | — | `0` |
| 2 | Login come `infosentieroitalia@cai.it`, aprire il ticket individuato sopra | Filtro: Stato = "Da fare" | Pagina di dettaglio caricata normalmente |
| 3 | In tinker, recuperare la riga creata: `App\Domain\Ticketing\Models\TicketView::where('ticket_id', <id>)->where('user_id', <id customer>)->first();` | — | Restituisce una riga |
| 4 | Ispezionare i campi della riga | — | `viewed_on` = data odierna; `view_count` = `1`; `last_viewed_at` valorizzato a un istante prossimo all'apertura della pagina |

**Risultato finale atteso**
Una sola riga `ticket_views` viene creata alla prima apertura della pagina, con `view_count = 1`.

**Controlli negativi**
Nessuno applicabile (coperto da F1-33 per le visualizzazioni successive).

**Evidenze da acquisire**
- Output tinker dei passi 1, 3, 4.

**Criterio di superamento**

PASS: la riga viene creata con i valori attesi al primo accesso.
FAIL: nessuna riga viene creata, oppure i valori non corrispondono.
BLOCKED: impossibile verificare la tabella `ticket_views` sull'ambiente.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-33 — Visualizzazioni entro la soglia non aggiornano il contatore, oltre la soglia lo aggiornano

**Obiettivo**
Verificare il comportamento di throttling di `RecordTicketView`: una seconda apertura della stessa
pagina entro `ticketing.views.throttle_minutes` (30 minuti di default) NON tocca `last_viewed_at`/`view_count`
della riga esistente; una visualizzazione oltre quella soglia li aggiorna (`last_viewed_at` avanza,
`view_count` incrementa di 1).

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.3 — soglia di throttling configurabile, 30 minuti di default.
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/RecordTicketViewTest.php` —
  `a second view within the throttle window does not touch last_viewed_at/view_count` e, nello
  stesso file, `a view beyond the throttle window updates last_viewed_at and increments view_count`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/RecordTicketView.php`;
  `config/ticketing.php` (`views.throttle_minutes` = 30).
- Test correlato: F1-32 (stesso ticket, in sequenza).

**Modalità di esecuzione**
MISTO (apertura pagina da UI + manipolazione/verifica tecnica su `ticket_views`, per evitare un'attesa reale di 30+ minuti)

**Priorità**
Media

**Ruolo del tester**
Sentiero Italia CAI - SICAI (Customer), con supporto tecnico di Sviluppatore/Amministratore di sistema

**Prerequisiti**
- Esecuzione di F1-32 completata sullo stesso ticket (riga `ticket_views` con `view_count = 1` esistente).
- Accesso a `php artisan tinker`.

**Dati di test**
Nessun dato di input diretto.

**Stato iniziale**
Riga `ticket_views` per (ticket, Customer, oggi) con `view_count = 1`, creata in F1-32.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Da Customer, ricaricare subito la stessa pagina del ticket (entro pochi minuti dalla prima apertura) | — | Pagina ricaricata normalmente |
| 2 | In tinker, rileggere la riga: valori `view_count`/`last_viewed_at` | — | `view_count` ancora `1`; `last_viewed_at` invariato rispetto a F1-32 |
| 3 | In tinker (Sviluppatore/Amministratore di sistema), retrodatare artificialmente la riga per simulare il superamento della soglia: `$view = App\Domain\Ticketing\Models\TicketView::where('ticket_id', <id>)->where('user_id', <id customer>)->first(); $view->update(['last_viewed_at' => now()->subMinutes(31)]);` | Script sopra | Riga aggiornata con `last_viewed_at` 31 minuti nel passato |
| 4 | Da Customer, ricaricare di nuovo la pagina del ticket | — | Pagina ricaricata normalmente |
| 5 | In tinker, rileggere la riga | — | `view_count` = `2`; `last_viewed_at` aggiornato a un istante prossimo all'apertura del passo 4 (non più quello retrodatato) |

**Risultato finale atteso**
Entro la soglia di 30 minuti la riga esistente non viene toccata; oltre la soglia, `last_viewed_at`
avanza e `view_count` incrementa di 1 — mai una nuova riga separata per lo stesso (ticket, utente, giorno).

**Controlli negativi**
Verificare al passo 5 che `App\Domain\Ticketing\Models\TicketView::where('ticket_id', <id>)->where('user_id', <id customer>)->count()` resti `1` (nessuna riga duplicata creata dal secondo/terzo accesso).

**Evidenze da acquisire**
- Output tinker dei passi 2, 3, 5.

**Criterio di superamento**

PASS: entro la soglia la riga non cambia; dopo la retrodatazione simulata, la riga si aggiorna correttamente (`view_count = 2`, `last_viewed_at` avanzato); una sola riga esiste sempre.
FAIL: la riga cambia entro la soglia, oppure non cambia oltre la soglia, oppure viene creata una seconda riga.
BLOCKED: impossibile eseguire la manipolazione tecnica del passo 3.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-34 — La registrazione di una visualizzazione non produce mai una voce nello storico del ticket

**Obiettivo**
Verificare che aprire ripetutamente la pagina di un ticket non scriva mai alcuna riga in
`ticket_logs`: le visualizzazioni restano una tabella separata dai log di dominio (§6.2.1), e la
sezione "Storico" dell'infolist non deve mai mostrare un evento legato a una semplice visualizzazione.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.1/§6.2.3 — tabelle separate.
- Test automatico: `tests/Feature/Domain/Ticketing/Actions/RecordTicketViewTest.php` —
  `recording a view never writes to ticket_logs`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/RecordTicketView.php`
  (nessuna istanza di `TicketLog` nell'Action); sezione "Storico" in
  `app/Filament/Resources/Tickets/Schemas/TicketInfolist.php`.
- Test correlato: F1-32/F1-33 (stesso ticket).

**Modalità di esecuzione**
MISTO (apertura pagina da UI + verifica tecnica sulla tabella `ticket_logs`)

**Priorità**
Bassa

**Ruolo del tester**
Sentiero Italia CAI - SICAI (Customer), con verifica di Sviluppatore/Amministratore di sistema

**Prerequisiti**
- Accesso a `php artisan tinker`.
- Il ticket scelto in F1-32/33 (o un altro), richiesto da `infosentieroitalia@cai.it`. A differenza del
  vecchio seed fittizio, un ticket importato dall'ETL può già avere righe reali in `ticket_logs`
  (la sua storia v1 ricostruita): questo test non richiede quindi un conteggio iniziale a `0`, ma
  verifica che il conteggio NON aumenti a seguito delle sole visualizzazioni — annotare il conteggio
  di partenza prima di procedere, qualunque esso sia.

**Dati di test**
Nessun dato di input diretto.

**Stato iniziale**
Conteggio `ticket_logs` per il ticket scelto annotato PRIMA di aprire la pagina (valore qualunque, dipende dal dump).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | In tinker, annotare il conteggio log iniziale: `App\Domain\Ticketing\Models\TicketLog::where('ticket_id', <id>)->count();` | — | Un numero (`$prima`), qualunque esso sia |
| 2 | Login come `infosentieroitalia@cai.it`, aprire il ticket, ricaricare la pagina 2-3 volte | — | Pagina caricata normalmente ogni volta |
| 3 | In tinker, rileggere il conteggio log | — | Identico a `$prima` |
| 4 | Sul pannello, come Sviluppatore o Manager (permesso `ticket-log.view`), aprire lo stesso ticket e ispezionare la sezione "Storico" | — | Il numero di eventi mostrati non è cambiato rispetto a prima dell'apertura del passo 2 (nessun nuovo evento di visualizzazione aggiunto) |

**Risultato finale atteso**
Nessuna riga viene mai scritta in `ticket_logs` a seguito di una o più visualizzazioni del ticket:
il conteggio resta invariato rispetto al valore annotato al passo 1, quale esso sia.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output tinker dei passi 1 e 3.
- Screenshot della sezione "Storico" del passo 4.

**Criterio di superamento**

PASS: il conteggio `ticket_logs` resta identico al valore annotato al passo 1 dopo le visualizzazioni, e "Storico" non mostra alcun nuovo evento correlato.
FAIL: compare una qualunque riga in `ticket_logs` attribuibile alla sola apertura della pagina.
BLOCKED: impossibile verificare la tabella `ticket_logs` sull'ambiente.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy (l'ETL reale, `v1:import --anonymize`).

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

## Calcolo delle ore lavorate

### F1-35 — Il calcolo dei minuti lavorati su un intervallo chiuso rispetta la finestra oraria configurata

**Obiettivo**
Verificare che `WorkedTimeCalculator` calcoli correttamente i minuti lavorati per un intervallo
`progress` chiuso (aperto e richiuso nello stesso giorno feriale, dentro la finestra oraria
configurata), arrotondando per difetto alla granularità configurata. `WorkedTimeCalculator` è un
service PHP puro (nessuna query, nessun side effect, §6.2.2): non esiste una superficie UI che
permetta di impostare orari precisi per osservarne il comportamento — la Filament UI non consente di
retrodatare i log di stato con un orario a scelta. Il test va eseguito direttamente sul service, con
gli stessi valori di configurazione di produzione (`workday_start=9`, `workday_end=18`,
`granularity_minutes=10`).

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.2, decisione Q15.
- Test automatico: `tests/Unit/Domain/TimeTracking/WorkedTimeCalculatorTest.php` —
  `computes minutes for a closed interval within a single day window` (verificare anche, nello
  stesso file, `rounds down to the configured granularity`)
- File/componente applicativo rilevante: `app/Domain/TimeTracking/WorkedTimeCalculator.php`;
  `config/timetracking.php`.
- Test correlato: F1-36, F1-37 (stesso service, scenari diversi).

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore/Amministratore di sistema

**Prerequisiti**
- Accesso a `php artisan tinker` sull'ambiente collaudato.
- Valori di configurazione correnti confermati: `config('timetracking.workday_start')` = `9`,
  `config('timetracking.workday_end')` = `18`, `config('timetracking.granularity_minutes')` = `10`.

**Dati di test**
Due log in memoria (mai persistiti, il service non legge/scrive dal DB): apertura `to_status =
progress` il lunedì 2026-01-05 alle 10:00:00, chiusura `from_status = progress` lo stesso giorno alle
12:00:00. Calcolo atteso: 120 minuti (2 ore piene dentro la finestra 9-18, nessun arrotondamento necessario).

**Stato iniziale**
Nessuno (calcolo puro in memoria).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Aprire `php artisan tinker` | — | Prompt attivo |
| 2 | Costruire il calcolatore con i valori di config correnti: `$calc = App\Domain\TimeTracking\WorkedTimeCalculator::fromConfig();` | — | Istanza creata |
| 3 | Costruire i due log in memoria: `$logs = [new App\Domain\Ticketing\Models\TicketLog(['event' => App\Domain\Ticketing\Enums\TicketLogEvent::StatusChanged, 'to_status' => App\Domain\Ticketing\Enums\TicketStatus::Progress, 'occurred_at' => '2026-01-05 10:00:00', 'user_id' => 1]), new App\Domain\Ticketing\Models\TicketLog(['event' => App\Domain\Ticketing\Enums\TicketLogEvent::StatusChanged, 'from_status' => App\Domain\Ticketing\Enums\TicketStatus::Progress, 'to_status' => App\Domain\Ticketing\Enums\TicketStatus::Todo, 'occurred_at' => '2026-01-05 12:00:00', 'user_id' => 1])];` | Script sopra | Array di 2 `TicketLog` in memoria |
| 4 | Calcolare il totale: `$calc->totalMinutesFor($logs);` | — | Restituisce `120` |
| 5 | Ripetere il calcolo con chiusura alle 10:37:00 invece che alle 12:00:00 (37 minuti reali) | Stesso script con `occurred_at` modificato | Restituisce `30` (arrotondamento per difetto a multipli di 10 minuti: 37 → 30) |

**Risultato finale atteso**
Il calcolatore restituisce esattamente 120 minuti per un intervallo di 2 ore piene dentro la finestra
lavorativa, e 30 minuti (arrotondati per difetto da 37) per l'intervallo del passo 5.

**Controlli negativi**
Nessuno applicabile (i casi limite finestra/weekend sono coperti da F1-36).

**Evidenze da acquisire**
- Output testuale completo della sessione tinker (passi 2-5).

**Criterio di superamento**

PASS: entrambi i calcoli restituiscono i valori esatti indicati (120 e 30).
FAIL: uno dei due valori non corrisponde.
BLOCKED: impossibile aprire una sessione tinker sull'ambiente.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuna scrittura persistente.

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

### F1-36 — Il weekend viene escluso dal calcolo e le ore vengono limitate alla finestra lavorativa

**Obiettivo**
Verificare che un intervallo `progress` che attraversa un weekend conteggi solo i minuti nei giorni
feriali, ciascuno ritagliato (clamp, non scartato) alla finestra oraria configurata (9:00-18:00).

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.2 — solo lunedì-venerdì, clamp alla finestra oraria.
- Test automatico: `tests/Unit/Domain/TimeTracking/WorkedTimeCalculatorTest.php` —
  `excludes the weekend and clamps to the workday window`
- File/componente applicativo rilevante: `app/Domain/TimeTracking/WorkedTimeCalculator.php`
  (metodo `splitAcrossWorkdays`).
- Test correlato: F1-35, F1-37.

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore/Amministratore di sistema

**Prerequisiti**
- Accesso a `php artisan tinker`.
- Stessi valori di configurazione di F1-35 (`workday_start=9`, `workday_end=18`).

**Dati di test**
Log di apertura venerdì 2026-01-02 alle 16:00:00 (`to_status = progress`), log di chiusura lunedì
2026-01-05 alle 10:00:00 (`from_status = progress`). Calcolo atteso: venerdì 16:00-18:00 = 120 minuti,
sabato/domenica 2026-01-03/04 esclusi interamente, lunedì 9:00-10:00 = 60 minuti; totale 180 minuti.

**Stato iniziale**
Nessuno (calcolo puro in memoria).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Aprire `php artisan tinker`, costruire il calcolatore come in F1-35 | `WorkedTimeCalculator::fromConfig()` | Istanza creata |
| 2 | Costruire i due log: apertura venerdì 2026-01-02 16:00:00, chiusura lunedì 2026-01-05 10:00:00 | Log analoghi a F1-35 con questi orari | Array di 2 `TicketLog` |
| 3 | Calcolare il totale: `$calc->totalMinutesFor($logs);` | — | Restituisce `180` |
| 4 | Calcolare i segmenti giornalieri: `$calc->segmentsFor($logs);` | — | 2 segmenti: venerdì 2026-01-02 con 120 minuti, lunedì 2026-01-05 con 60 minuti; NESSUN segmento per sabato/domenica |

**Risultato finale atteso**
Il totale è 180 minuti, distribuiti su 2 soli segmenti giornalieri (venerdì e lunedì), con
sabato/domenica completamente esclusi dal calcolo.

**Controlli negativi**
Verificare che nessuno dei segmenti restituiti al passo 4 abbia `workDate` pari a 2026-01-03 o 2026-01-04 (il weekend).

**Evidenze da acquisire**
- Output testuale della sessione tinker (passi 2-4).

**Criterio di superamento**

PASS: il totale è esattamente 180 minuti e i segmenti corrispondono a venerdì (120') e lunedì (60'), senza righe weekend.
FAIL: il totale non è 180, oppure compare un segmento per sabato/domenica.
BLOCKED: impossibile aprire una sessione tinker sull'ambiente.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuna scrittura persistente.

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

### F1-37 — Un ticket ancora in lavorazione ha le ore limitate a un tetto configurato, non proiettate a oggi

**Obiettivo**
Verificare che un intervallo `progress` ancora aperto (nessun log di chiusura, il ticket è tuttora in
lavorazione) NON venga proiettato fino a "adesso" indefinitamente: il totale calcolato per
quell'intervallo viene limitato al tetto configurato (`non_status_change_cap_minutes`, 30 minuti di
default), attribuito al giorno più recente toccato dall'intervallo aperto.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.2/§17.1, decisione Q15 — tetto sull'intervallo aperto.
- Test automatico: `tests/Unit/Domain/TimeTracking/WorkedTimeCalculatorTest.php` —
  `caps a still-open interval instead of projecting it indefinitely` (verificare anche, nello stesso
  file, `does not cap an open interval that has not yet reached the cap`)
- File/componente applicativo rilevante: `app/Domain/TimeTracking/WorkedTimeCalculator.php`
  (metodo `capOpenInterval`); `config/timetracking.php` (`non_status_change_cap_minutes` = 30).
- Test correlato: F1-35, F1-36.

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore/Amministratore di sistema

**Prerequisiti**
- Accesso a `php artisan tinker`.

**Dati di test**
Un solo log di apertura `to_status = progress` lunedì 2026-01-05 alle 10:00:00, nessun log di
chiusura (il ticket è ancora "In lavorazione"). Istante di riferimento (`asOf`) passato esplicitamente
al calcolatore: giovedì 2026-01-09 alle 15:00:00 (diversi giorni lavorativi dopo). Calcolo atteso: 30
minuti (il tetto configurato), attribuiti al giorno 2026-01-09 (il più recente toccato
dall'intervallo), non un conteggio proiettato di più giorni.

**Stato iniziale**
Nessuno (calcolo puro in memoria).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Aprire `php artisan tinker`, costruire il calcolatore | `WorkedTimeCalculator::fromConfig()` | Istanza creata |
| 2 | Costruire il singolo log di apertura (2026-01-05 10:00:00), nessun log di chiusura | `$logs = [ ... to_status Progress ... ]` (un solo elemento) | Array di 1 `TicketLog` |
| 3 | Costruire l'istante di riferimento: `$asOf = Carbon\CarbonImmutable::parse('2026-01-09 15:00:00');` | — | Istanza creata |
| 4 | Calcolare il totale passando `$asOf`: `$calc->totalMinutesFor($logs, $asOf);` | — | Restituisce `30` (il tetto), NON un valore proporzionale ai giorni trascorsi |
| 5 | Calcolare i segmenti: `$calc->segmentsFor($logs, $asOf);` | — | 1 solo segmento, con `workDate` = `2026-01-09` (il giorno più recente, non il giorno di apertura) |
| 6 | Ripetere con `$asOf = Carbon\CarbonImmutable::parse('2026-01-05 10:15:00');` (15 minuti dopo l'apertura, sotto il tetto) | Stesso script con `asOf` diverso | Restituisce `10` (arrotondato alla granularità, non ancora limitato dal tetto perché sotto i 30 minuti) |

**Risultato finale atteso**
Un intervallo aperto da diversi giorni lavorativi non supera mai il tetto di 30 minuti configurato,
attribuito al giorno più recente; un intervallo aperto da poco (sotto il tetto) riflette invece il
tempo realmente trascorso.

**Controlli negativi**
Verificare che il totale del passo 4 NON sia un multiplo dei minuti lavorativi trascorsi tra il
2026-01-05 e il 2026-01-09 (che sarebbe un valore molto più alto se il calcolo proiettasse
indefinitamente): un valore diverso da 30 indicherebbe una regressione della cap.

**Evidenze da acquisire**
- Output testuale della sessione tinker (passi 2-6).

**Criterio di superamento**

PASS: il totale con `asOf` lontano è esattamente 30 (attribuito al 2026-01-09); il totale con `asOf` vicino è 10.
FAIL: il totale con `asOf` lontano supera 30, o non è attribuito al giorno corretto.
BLOCKED: impossibile aprire una sessione tinker sull'ambiente.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuna scrittura persistente.

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

### F1-38 — Il ricalcolo massivo aggiorna le ore lavorate del ticket in modo idempotente

**Obiettivo**
Verificare che `RecalculateWorkedTime` (invocata dal comando `timetracking:recalculate`) sia
idempotente: eseguirla due volte sullo stesso ticket non duplica le righe di `ticket_work_logs` né
altera il totale `tickets.worked_minutes` rispetto alla prima esecuzione, perché ogni ricalcolo
cancella e ricrea da zero le righe dell'aggregato invece di fare un upsert differenziale.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.2, decisione Q15 — "mai un upsert differenziale".
- Test automatico: `tests/Feature/Domain/TimeTracking/Actions/RecalculateWorkedTimeTest.php` —
  `is idempotent: running it twice does not duplicate ticket_work_logs rows`
- File/componente applicativo rilevante: `app/Domain/TimeTracking/Actions/RecalculateWorkedTime.php`;
  `app/Console/Commands/TimeTrackingRecalculateCommand.php`.
- Test correlato: F1-40 (stesso comando CLI, opzioni diverse).

**Modalità di esecuzione**
MISTO (transizione di stato da UI per creare l'intervallo "In lavorazione" + comando CLI + verifica tecnica su DB)

**Priorità**
Alta

**Ruolo del tester**
Sviluppatore (Developer)

**Prerequisiti**
- Utente `lorena.sava@montagnaservizi.com`.
- Accesso SSH/shell al container applicativo per eseguire `php artisan timetracking:recalculate`.
- Un ticket senza righe `ticket_work_logs` pregresse, in uno stato da cui sia raggiungibile "In
  lavorazione" con transizioni valide. Nota: un ticket reale già importato dall'ETL può avere già
  `worked_minutes`/`ticket_work_logs` popolati dallo stage `derive` a partire dalla sua storia v1 —
  per partire da uno stato pulito, creare un ticket nuovo dedicato (`COLL-F1-38-...`) e assegnarlo a
  "Lorena Sava" con i bottoni di transizione già testati in F1-01, invece di riusare un
  ticket del dump.

**Dati di test**
Nessun orario preciso richiesto per questo test (l'idempotenza non dipende dai minuti esatti, solo
dalla stabilità tra due esecuzioni consecutive): un singolo passaggio a "In lavorazione" e poi a un
altro stato è sufficiente per generare un intervallo `progress` chiuso nei `ticket_logs` reali del ticket.

**Stato iniziale**
Ticket nuovo dedicato in stato "Da fare", `worked_minutes` = 0, nessuna riga `ticket_work_logs` per questo ticket.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Login come `lorena.sava@montagnaservizi.com`, aprire il ticket, transizione a "In lavorazione" | Bottone "In lavorazione" | Notifica "Stato del ticket aggiornato" |
| 2 | Attendere qualche minuto, poi transizione a "Testato" (o altro stato che chiuda l'intervallo `progress`) con il campo Tester compilato | Bottone "In test" poi "Testato", oppure altra transizione valida che esca da `progress` | Stato aggiornato, un intervallo `progress` chiuso ora esiste nei `ticket_logs` del ticket |
| 3 | Da shell, eseguire: `php artisan timetracking:recalculate --ticket=<id>` | Comando sopra | Output "Ricalcolate le ore lavorate per 1 ticket."; comando conclude con successo |
| 4 | Verificare via tinker/DB: `App\Domain\Ticketing\Models\TicketWorkLog::where('ticket_id', <id>)->count();` e `Ticket::find(<id>)->worked_minutes;` | — | Conteggio righe = numero di segmenti giornalieri prodotti (tipicamente 1); `worked_minutes` coerente coi minuti dell'intervallo |
| 5 | Rieseguire lo stesso comando una seconda volta: `php artisan timetracking:recalculate --ticket=<id>` | Stesso comando | Output identico, nessun errore |
| 6 | Ripetere la verifica del passo 4 | — | Stesso conteggio righe (nessuna duplicazione) e stesso valore `worked_minutes` della prima esecuzione |

**Risultato finale atteso**
Il conteggio delle righe `ticket_work_logs` e il valore `worked_minutes` restano identici dopo la
seconda esecuzione del comando: nessuna riga duplicata, nessuna deriva del totale.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Output shell dei passi 3 e 5.
- Output tinker/DB dei passi 4 e 6 (a confronto).

**Criterio di superamento**

PASS: il conteggio righe e `worked_minutes` sono identici prima e dopo la seconda esecuzione.
FAIL: il conteggio righe aumenta, oppure `worked_minutes` cambia tra le due esecuzioni.
BLOCKED: impossibile eseguire il comando da shell sull'ambiente.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-39 — Un cambio di stato accoda il ricalcolo delle ore, unendo più cambi ravvicinati in un solo ricalcolo

**Obiettivo**
Verificare il debounce del listener `RecalculateWorkedTimeOnStatusChange`: una raffica di transizioni
di stato sullo stesso ticket entro la finestra di debounce (5 secondi, `DEBOUNCE_SECONDS` nel
listener) produce un solo job `RecalculateTicketWorkedTimeJob` accodato, non uno per transizione. Non
è osservabile da UI: richiede l'ispezione della coda (Horizon o la tabella `jobs`), e la finestra di
5 secondi è troppo breve per essere riprodotta in modo affidabile cliccando bottoni in un browser —
va riprodotta da codice/CLI, che può eseguire tre chiamate in sequenza immediata.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.2 — debounce per ticket, un solo ricalcolo per raffica.
- Test automatico: `tests/Feature/Domain/TimeTracking/Listeners/RecalculateWorkedTimeOnStatusChangeTest.php`
  — `debounces a burst of transitions on the same ticket into a single queued job`
- File/componente applicativo rilevante: `app/Domain/TimeTracking/Listeners/RecalculateWorkedTimeOnStatusChange.php`
  (`DEBOUNCE_SECONDS = 5`, lock in `Cache`); `app/Domain/TimeTracking/Jobs/RecalculateTicketWorkedTimeJob.php`.
- Test correlato: F1-38 (stesso job di ricalcolo, invocato qui indirettamente).

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Media

**Ruolo del tester**
Sviluppatore/Amministratore di sistema

**Prerequisiti**
- Accesso a `php artisan tinker` sull'ambiente collaudato.
- Accesso al backend della coda (Horizon dashboard, oppure `php artisan queue:failed`/ispezione Redis)
  per contare i job effettivamente accodati per il ticket.
- Un utente con permesso `ticket.transition.any` (es. Admin) per poter eseguire transizioni multiple
  senza vincoli di attore/rapporto col record.

**Dati di test**
Tre transizioni di stato eseguite in rapida sequenza (nello stesso processo tinker, senza pause) sullo stesso ticket.

**Stato iniziale**
Ticket in uno stato di partenza noto (es. "Nuovo"); nessun job `RecalculateTicketWorkedTimeJob` in coda per questo ticket.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Annotare il timestamp/ID di partenza e verificare che non ci siano job pendenti per il ticket in Horizon (tab "Pending"/"Delayed") | — | Nessun job per questo `ticket_id` |
| 2 | Aprire `php artisan tinker`, recuperare ticket e utente Admin | `$ticket = ...; $admin = App\Domain\Identity\Models\User::where('email','info@montagnaservizi.com')->first();` | Variabili popolate |
| 3 | Eseguire in sequenza immediata 3 transizioni valide sullo stesso ticket, es.: `App\Domain\Ticketing\Actions\ChangeTicketStatus::run($ticket, App\Domain\Ticketing\Enums\TicketStatus::Backlog, $admin); App\Domain\Ticketing\Actions\ChangeTicketStatus::run($ticket->fresh(), App\Domain\Ticketing\Enums\TicketStatus::Assigned, $admin, ['assignee_id' => $admin->id]); App\Domain\Ticketing\Actions\ChangeTicketStatus::run($ticket->fresh(), App\Domain\Ticketing\Enums\TicketStatus::Todo, $admin);` | Script sopra, eseguito come un unico blocco (senza pause manuali) | Le 3 transizioni completano senza errori |
| 4 | Entro pochi secondi, controllare in Horizon (tab "Pending"/"Delayed") i job accodati per questo `ticket_id` | — | Esattamente 1 job `RecalculateTicketWorkedTimeJob` per il ticket, non 3 |
| 5 | Attendere che il job venga eseguito (delay configurato di 5 secondi) e verificare in Horizon che sia passato a "Completed" | — | 1 solo job completato per questo ticket |

**Risultato finale atteso**
Nonostante 3 transizioni ravvicinate, un solo job di ricalcolo viene accodato ed eseguito per il ticket.

**Controlli negativi**
Ripetere una singola transizione aggiuntiva dopo un'attesa di almeno 6 secondi (oltre la finestra di
debounce): deve comparire un SECONDO job distinto in coda, a conferma che il debounce si applica solo
entro la finestra e non blocca ricalcoli legittimi successivi (coperto anche dal test automatico
"queues a new job again once the debounce window has elapsed" nello stesso file).

**Evidenze da acquisire**
- Screenshot della dashboard Horizon dei passi 4 e 5 (elenco job filtrato per il ticket).
- Output tinker dei passi 2-3.

**Criterio di superamento**

PASS: esattamente 1 job viene accodato/eseguito per le 3 transizioni ravvicinate; un secondo job compare solo dopo la finestra di debounce.
FAIL: compaiono più job per la stessa raffica ravvicinata.
BLOCKED: impossibile ispezionare la coda (Horizon non raggiungibile) sull'ambiente.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-40 — Il comando di ricalcolo massivo permette di ricalcolare un singolo ticket o un intervallo di date

**Obiettivo**
Verificare che `php artisan timetracking:recalculate` supporti l'opzione `--ticket` per limitare il
ricalcolo a un solo ticket (ignorando `--from`/`--to`), lasciando invariati gli altri ticket.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.2.2 — comando di ribuild massivo, opzioni componibili.
- Test automatico: `tests/Feature/Console/TimeTrackingRecalculateCommandTest.php` —
  `--ticket limits the recalculation to a single ticket`
- File/componente applicativo rilevante: `app/Console/Commands/TimeTrackingRecalculateCommand.php`.
- Test correlato: F1-38 (stesso comando, verifica di idempotenza).

**Modalità di esecuzione**
TECNICO CLI

**Priorità**
Media

**Ruolo del tester**
Sviluppatore/Amministratore di sistema

**Prerequisiti**
- Accesso shell al container applicativo.
- Due ticket esistenti: uno (`T1`) con un intervallo `progress` reale nei `ticket_logs` (creabile
  come al passo 1-2 di F1-38), un secondo (`T2`) con `worked_minutes` impostato manualmente a un
  valore sentinella riconoscibile (es. `999`) via tinker: `App\Domain\Ticketing\Models\Ticket::find(<id T2>)->update(['worked_minutes' => 999]);`.

**Dati di test**
`--ticket=<id di T1>`.

**Stato iniziale**
`T1`: intervallo `progress` chiuso nei log, `worked_minutes` non ancora ricalcolato. `T2`:
`worked_minutes` = `999` (valore sentinella, nessuna relazione con l'algoritmo di calcolo).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Annotare `worked_minutes` di `T2` prima del comando | — | `999` |
| 2 | Da shell, eseguire: `php artisan timetracking:recalculate --ticket=<id T1>` | Comando sopra | Output "Ricalcolate le ore lavorate per 1 ticket."; comando conclude con successo |
| 3 | Verificare `worked_minutes` di `T1` dopo il comando | `Ticket::find(<id T1>)->worked_minutes` (tinker) | Valore coerente con l'intervallo `progress` reale di `T1` (diverso dal valore pre-comando) |
| 4 | Verificare `worked_minutes` di `T2` dopo il comando | `Ticket::find(<id T2>)->worked_minutes` (tinker) | Ancora `999`, invariato |

**Risultato finale atteso**
Solo `T1` viene ricalcolato; `T2` resta esattamente al valore sentinella `999`, a dimostrazione che
`--ticket` limita realmente il ricalcolo al singolo ticket indicato.

**Controlli negativi**
Eseguire `php artisan timetracking:recalculate --from=<data futura lontana> --to=<data futura lontana
+1 giorno>` (un intervallo di date che non include né `T1` né `T2`): l'output deve riportare "0
ticket" ricalcolati e `worked_minutes` di entrambi i ticket deve restare invariato rispetto al passo 4.

**Evidenze da acquisire**
- Output shell del comando del passo 2 e del controllo negativo.
- Output tinker dei passi 1, 3, 4.

**Criterio di superamento**

PASS: dopo `--ticket=<id T1>`, solo `T1` cambia e `T2` resta a `999`; il controllo negativo con date fuori range non tocca nessuno dei due.
FAIL: `T2` viene ricalcolato nonostante `--ticket`, oppure `T1` non viene ricalcolato.
BLOCKED: impossibile eseguire il comando da shell sull'ambiente.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

## Scheda ticket — campi e comportamenti

**Nota trasversale valida per tutti i test di questa sezione (verificata su `.env`/`config/app.php`)**: `APP_LOCALE=en` e non esiste nel repository alcuna directory `lang/it` o `resources/lang/it` pubblicata. Questo significa che le etichette/testi che l'applicazione definisce come stringa PHP letterale nel codice Orchestrator (nomi di stato, titoli di sezione, testo dei bottoni di transizione, titoli delle notifiche di dominio) restano **sempre in italiano indipendentemente dalla lingua**, perché non passano mai dal traduttore Laravel — sono citati alla lettera nei passi che seguono e sono garantiti dal codice sorgente. Gli elementi di interfaccia **generici** di Filament non personalizzati in questo repo (il pulsante standard di modifica/visualizzazione in testata, il pulsante di salvataggio del form, i messaggi di validazione generici tipo "campo obbligatorio" su un `Select`/`TextInput` senza un messaggio custom) non hanno invece alcuna garanzia di comparire in italiano: per questi elementi la procedura descrive l'azione/icona/posizione invece di citare una didascalia esatta. Se il collaudo reale mostra questi elementi in inglese, non è un'anomalia dei test di questa sezione: è annotato puntualmente dove rilevante (F1-44) come **DA VERIFICARE CON IL PRODUCT OWNER**.

---

### F1-41 — Un cliente che manipola direttamente il modulo non può alterare alcun campo riservato allo staff

**Obiettivo**
Verifica che i campi "interni" del ticket (Tipo, Priorità, Assegnatario, Tester, Ore stimate, Descrizione interna, URL staging, URL produzione) non siano alterabili da un Customer né onestamente da UI (i campi non sono nemmeno visibili) né tramite un tentativo tecnico di forzare un payload con quei campi: sono implementati come componenti `->hidden()` (non solo `->disabled()`), quindi non vengono mai dehydratati dal form e un salvataggio che li includesse verrebbe comunque ignorato senza errore.

**Riferimenti**
- Requisito/regola di dominio: nota CLAUDE.md "TicketResource — prima Resource Filament 'reale' del repo (US-110)" — `->hidden()` (non `->disabled()`) per ogni campo che un cliente non deve mai poter scrivere, verificato anche contro una `fillForm()` manipolata.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketResourceTest.php` — `a customer manipulating the edit form cannot alter any internal field`
- File/componente applicativo rilevante: `app/Filament/Resources/Tickets/Schemas/TicketForm.php` (sezioni "Assegnazione e classificazione"/"Link ambienti"/"Tempo", campo "Descrizione interna"), `app/Filament/Resources/Tickets/Support/TicketFieldAccess.php`
- Test correlato: F1-42

**Modalità di esecuzione**
MISTO

**Priorità**
Critica

**Ruolo del tester**
Customer (parte di verifica onesta da UI) + Developer/Amministratore di sistema (parte di tentativo tecnico di bypass: richiede strumenti di sviluppo del browser o un client HTTP diretto sulla sessione autenticata del Customer)

**Prerequisiti**
- L'utente `info@montagnaservizi.com` è disponibile per predisporre il ticket di test con valori interni noti (Admin ha tutti i permessi, incluso `ticket.manage-internal-fields`).
- L'utente `infosentieroitalia@cai.it` ha i permessi di ruolo `ticket.update.own`/`ticket.view.own` (matrice Customer, `RolePermissionSeeder`).
- Per la parte tecnica del passo 9: un tester con accesso agli strumenti di sviluppo del browser (tab Network/Console) o a un client HTTP (es. Postman/curl) in grado di riutilizzare i cookie di sessione del Customer.

**Dati di test**
Ticket da creare: titolo `COLL-F1-41-20260726-01 — Verifica campi riservati`, Richiedente = "Sentiero Italia CAI - SICAI", Tipo = Bug, Priorità = Bassa, Descrizione interna = `descrizione interna originale`, Ore stimate = 3, Assegnatario = (vuoto), Tester = (vuoto), URL staging/produzione = (vuoti).
Valori del tentativo di manomissione (identici a quelli del test automatico): Tipo = Feature, Priorità = Alta, Descrizione interna = `descrizione manomessa`, Ore stimate = 99, URL staging = `https://staging.example.test`, URL produzione = `https://prod.example.test`, Assegnatario e Tester = "Manager Collaudo".

**Stato iniziale**
Il ticket `COLL-F1-41-20260726-01` non esiste ancora. Nessuna sessione Customer attiva.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Aprire `https://ticket-uat.montagnaservizi.com/admin/login` e accedere come Admin | `info@montagnaservizi.com` / `uat` | Login riuscito, pannello raggiunto |
| 2 | Da "Ticket", aprire "Nuovo" e compilare Titolo, Richiedente, Tipo, Priorità, Descrizione interna, Ore stimate come da Dati di test, lasciando vuoti Assegnatario/Tester/URL staging/URL produzione | valori indicati sopra | Il ticket viene creato senza errori di validazione |
| 3 | Osservare il badge "Stato" nella scheda appena creata | — | Il badge mostra "Nuovo" |
| 4 | Disconnettersi e accedere come Customer | `infosentieroitalia@cai.it` / `uat` | Login riuscito |
| 5 | Cercare nell'elenco Ticket il titolo `COLL-F1-41-20260726-01` (campo di ricerca sulla colonna "Titolo") e aprirne il dettaglio | testo di ricerca `COLL-F1-41` | Si apre la pagina di dettaglio del ticket |
| 6 | Aprire il ticket in modifica (pulsante di modifica in testata) | — | Si apre il form di modifica |
| 7 | Osservare le sezioni del form presenti | — | È visibile solo la sezione "Ticket" con i campi "Titolo" (in sola lettura), "Stato" (badge) e "Ticket padre"; le sezioni "Assegnazione e classificazione", "Link ambienti", "Tempo" e il campo "Descrizione interna" NON sono presenti in nessuna forma |
| 8 | Confermare il salvataggio del form senza alcuna modifica | — | Il salvataggio va a buon fine, nessun errore di validazione mostrato |
| 9 | (Passo tecnico, tester con strumenti di sviluppo) Con la sessione Customer ancora attiva, riaprire il form di modifica e, prima di confermare il salvataggio, iniettare tramite gli strumenti di sviluppo del browser (o una richiesta diretta all'endpoint Livewire del componente `EditTicket`) i valori di manomissione elencati in "Dati di test" per `type`/`priority`/`description`/`estimated_hours`/`staging_url`/`production_url`/`assignee_id`/`tester_id`, poi confermare il salvataggio | payload di manomissione sopra | Il salvataggio termina comunque senza errori di validazione (i campi iniettati vengono semplicemente ignorati, non genera un errore) |
| 10 | Riaprire il ticket come Admin (o Manager/Developer) e osservare i valori di Tipo, Priorità, Descrizione interna, Ore stimate, URL staging, URL produzione, Assegnatario, Tester | — | Tutti i valori coincidono esattamente con quelli impostati al passo 2 (Bug/Bassa/`descrizione interna originale`/3/vuoto/vuoto/vuoto/vuoto), nessuno è stato alterato dal passo 9 |

**Risultato finale atteso**
Nessuno degli 8 campi interni del ticket cambia valore in seguito al salvataggio del Customer, né nel tentativo onesto (passo 8, dove i campi non sono nemmeno presenti nel form) né nel tentativo tecnico di bypass (passo 9, dove i campi sono presenti nel payload ma vengono ignorati perché non dehydratati).

**Controlli negativi**
Il payload di manomissione del passo 9 non deve mai alterare i valori persistiti, anche quando il salvataggio va a buon fine senza errori: questo è il comportamento corretto (componente nascosto ⇒ non dehydratato ⇒ ignorato in silenzio), non un errore di validazione da attendersi.

**Evidenze da acquisire**
- Screenshot del form di modifica visto dal Customer al passo 7 (sezioni assenti).
- Cattura della richiesta HTTP/payload usato al passo 9 (tab Network del browser o log del client HTTP).
- Screenshot/estratto dei valori finali osservati al passo 10.

**Criterio di superamento**

PASS: tutti gli 8 campi interni restano invariati dopo i passi 8 e 9.
FAIL: almeno uno degli 8 campi interni risulta modificato dopo il salvataggio del Customer (in particolare dopo il passo 9).
BLOCKED: non è possibile predisporre il ticket di test come Admin, o non è disponibile un tester con strumenti di sviluppo del browser per il passo 9.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuna azione di eliminazione ticket disponibile in UI in questa release (`TicketResource` non espone un `DeleteAction`). Il ticket di test resta nel dataset; se necessario, un tester tecnico può rimuoverlo con `\App\Domain\Ticketing\Models\Ticket::where('title','COLL-F1-41-20260726-01 — Verifica campi riservati')->first()->forceDelete();` via `php artisan tinker` (il modello usa `SoftDeletes`). In alternativa, il ticket resta come dato residuo non bloccante: l'ETL (`v1:import`) importa/aggiorna solo i ticket derivati dal dump v1 e non tocca né rimuove ticket creati manualmente durante il collaudo.

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

### F1-42 — Le sezioni riservate allo staff sono nascoste a un cliente nella vista di dettaglio del ticket

**Obiettivo**
Verifica che la vista di dettaglio (sola lettura) del ticket nasconda per un Customer tutti i campi/sezioni riservati allo staff (Tipo, Priorità, Assegnatario, Tester, Descrizione interna, Ore lavorate, Ore stimate, l'intera sezione "Link ambienti", l'intera sezione "Storico"), non solo il form di modifica già coperto da F1-41.

**Riferimenti**
- Requisito/regola di dominio: `TicketFieldAccess::canManageInternalFields()` come gate unico per i campi interni (US-110); `Permission::TicketLogView` come gate per la sezione "Storico".
- Test automatico: `tests/Feature/Filament/Ticketing/TicketResourceTest.php` — `internal sections are hidden from a customer on the view page`
- File/componente applicativo rilevante: `app/Filament/Resources/Tickets/Schemas/TicketInfolist.php`
- Test correlato: F1-41

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Esiste almeno un ticket reale importato dall'ETL con richiedente `infosentieroitalia@cai.it` ("Sentiero Italia CAI - SICAI"): filtrare l'elenco Ticket per Richiedente = "Sentiero Italia CAI - SICAI" per trovarne uno, non serve crearne uno nuovo.

**Dati di test**
Nessun dato nuovo da creare: usare un qualunque ticket esistente con richiedente "Sentiero Italia CAI - SICAI".

**Stato iniziale**
Il dataset importato dall'ETL reale (`v1:import --anonymize`) è già popolato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Aprire `https://ticket-uat.montagnaservizi.com/admin/login` e accedere come Customer | `infosentieroitalia@cai.it` / `uat` | Login riuscito |
| 2 | Aprire l'elenco Ticket e aprire in visualizzazione un qualunque ticket proprio | un ticket qualunque dell'elenco | Si apre la pagina di dettaglio del ticket |
| 3 | Osservare la sezione "Ticket" in alto | — | Sono visibili solo "Titolo", "Stato" (badge) e "Richiedente"; i campi "Tipo" e "Priorità" NON sono presenti |
| 4 | Osservare la sezione "Riepilogo" | — | Sono visibili solo "Creato il", "Aggiornato il", "Ultimo cambio di stato", "Rilasciato il", "Completato il"; i campi "Ore lavorate" e "Ore stimate" NON sono presenti |
| 5 | Cercare nella pagina l'etichetta "URL staging" (testo esatto) | testo di ricerca "URL staging" | Il testo non compare in nessun punto della pagina |
| 6 | Osservare se è presente una sezione intitolata "Link ambienti" | — | La sezione non è presente affatto (non solo i suoi campi) |
| 7 | Cercare nella pagina l'etichetta "Descrizione interna" | testo di ricerca "Descrizione interna" | Il testo non compare |
| 8 | Cercare nella pagina l'etichetta "Storico" | testo di ricerca "Storico" | Il testo non compare in nessun punto della pagina (sezione assente) |
| 9 | Osservare che le sezioni "Gerarchia", "Partecipanti" e "Conversazione" restino visibili | — | Tutte e tre le sezioni sono presenti (non riservate allo staff) |

**Risultato finale atteso**
La vista di dettaglio del Customer non mostra mai Tipo, Priorità, Assegnatario, Tester, Descrizione interna, Ore lavorate, Ore stimate, la sezione "Link ambienti" né la sezione "Storico"; le sezioni non riservate (Gerarchia/Partecipanti/Conversazione) restano visibili.

**Controlli negativi**
Non previsto per questo test: nessun tentativo di bypass tecnico è descritto dal test automatico referenziato (a differenza di F1-41, qui non c'è un form da manomettere, solo una vista di sola lettura).

**Evidenze da acquisire**
- Screenshot dell'intera pagina di dettaglio vista dal Customer (per documentare l'assenza delle sezioni/campi elencati).

**Criterio di superamento**

PASS: nessuna delle etichette/sezioni riservate elencate compare nella pagina.
FAIL: almeno una tra Tipo/Priorità/Assegnatario/Tester/Descrizione interna/Ore lavorate/Ore stimate/"Link ambienti"/"Storico" è visibile al Customer.
BLOCKED: nessun ticket disponibile per il Customer sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test di sola lettura, nessuna scrittura effettuata.

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

### F1-43 — Un developer che porta un ticket nuovo ad "assegnato" si auto-assegna silenziosamente, senza dover scegliere sé stesso

**Obiettivo**
Verifica che un Developer (attore `AutoAssigningDeveloper`, privo del permesso `ticket.transition.any`) che esegue la transizione "Nuovo → Assegnato" venga assegnato automaticamente al ticket senza che il modale dell'azione mostri alcun campo "Assegnatario" da compilare: l'assegnazione avviene silenziosamente all'utente che esegue l'azione.

**Riferimenti**
- Requisito/regola di dominio: nota CLAUDE.md "TicketResource — viste come query object (US-111, §8.5)" — attore `AutoAssigningDeveloper`: se l'utente non ha `ticket.transition.any`, il context `assignee_id` viene precompilato con l'id dell'utente corrente e nessun campo di scelta va mostrato.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketResourceTest.php` — `a developer transitioning new to assigned is silently self-assigned without an assignee field`
- File/componente applicativo rilevante: `app/Filament/Resources/Tickets/Support/TicketTransitionActions.php` (metodi `build()`/`requiresAssigneeField()`/`buildAction()`)
- Test correlato: F1-44

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- L'utente `info@montagnaservizi.com` predispone il ticket di test (Admin per semplicità, ma qualunque utente con `ticket.create`+`ticket.manage-internal-fields` andrebbe bene, incluso lo stesso Developer).
- L'utente `lorena.sava@montagnaservizi.com` ("Lorena Sava") ha i permessi di ruolo `ticket.update.assigned`/`ticket.view.any`/`ticket.manage-internal-fields`, ma NON `ticket.transition.any` (matrice Developer, `RolePermissionSeeder`): condizione necessaria perché l'attore risolto sia `AutoAssigningDeveloper` e non l'admin/manager generico.

**Dati di test**
Ticket da creare: titolo `COLL-F1-43-20260726-01 — Verifica auto-assegnazione silenziosa`, Richiedente = "Sentiero Italia CAI - SICAI", Assegnatario e Tester lasciati vuoti.

**Stato iniziale**
Il ticket `COLL-F1-43-20260726-01` non esiste ancora.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere come Admin e creare il ticket con i Dati di test indicati, lasciando vuoti Assegnatario/Tester | vedi Dati di test | Ticket creato, badge "Stato" = "Nuovo" |
| 2 | Disconnettersi e accedere come Developer | `lorena.sava@montagnaservizi.com` / `uat` | Login riuscito |
| 3 | Aprire il ticket `COLL-F1-43-20260726-01` in visualizzazione | — | Si apre la scheda di dettaglio, campo "Assegnatario" mostra "Nessuno" |
| 4 | Individuare tra i pulsanti di testata quello con etichetta "Assegnato" e cliccarlo | pulsante "Assegnato" | Si apre un modale con titolo `Cambia stato in "Assegnato"` |
| 5 | Osservare i campi presenti nel modale | — | È presente solo la checkbox "Applica anche ai ticket figli"; NON è presente alcun campo "Assegnatario" |
| 6 | Confermare l'azione dal modale | — | Compare una notifica di successo con titolo "Stato del ticket aggiornato" |
| 7 | Osservare il badge "Stato" nella scheda | — | Il badge mostra "Assegnato" |
| 8 | Osservare il campo "Assegnatario" nella sezione "Assegnazione e classificazione" | — | Il campo mostra "Lorena Sava" (il Developer che ha eseguito l'azione), senza che nessuno lo abbia scelto esplicitamente |

**Risultato finale atteso**
Il ticket passa a "Assegnato" con Assegnatario impostato automaticamente allo Lorena Sava che ha eseguito la transizione, senza che il modale abbia mai richiesto di scegliere un assegnatario.

**Controlli negativi**
Non previsto per questo test: il test automatico referenziato verifica l'assenza del campo nello schema del modale (`assertSchemaComponentDoesNotExist('assignee_id')`), non un tentativo di forzare comunque un valore diverso.

**Evidenze da acquisire**
- Screenshot del modale aperto al passo 5 (assenza del campo Assegnatario).
- Screenshot della notifica di successo al passo 6.
- Screenshot del campo Assegnatario valorizzato al passo 8.

**Criterio di superamento**

PASS: il modale non mostra il campo Assegnatario e il ticket risulta assegnato al Developer che ha eseguito l'azione.
FAIL: il modale mostra un campo Assegnatario da compilare, oppure il ticket non risulta assegnato al Developer dopo l'azione.
BLOCKED: non è possibile predisporre il ticket di test o accedere come Developer sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuna azione di eliminazione ticket disponibile in UI. Un tester tecnico può rimuovere il ticket con `\App\Domain\Ticketing\Models\Ticket::where('title','COLL-F1-43-20260726-01 — Verifica auto-assegnazione silenziosa')->first()->forceDelete();` via `php artisan tinker`. In alternativa resta come dato residuo non bloccante (vedi nota sull'ETL in F1-41).

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

### F1-44 — La transizione verso "in test" richiede la scelta di un tester e fallisce leggibilmente se assente

**Obiettivo**
Verifica che la transizione "In lavorazione → In test" richieda sempre di specificare un Tester nel modale dell'azione, e che un tentativo di conferma senza Tester venga bloccato con un errore di validazione sul campo, senza modificare lo stato del ticket; verifica inoltre che, fornendo un Tester valido, la transizione vada a buon fine.

**Riferimenti**
- Requisito/regola di dominio: `App\Domain\Ticketing\Rules\TicketTesterRequiredRule` (guard `Progress → Testing` della macchina a stati, PRD §6.1.3).
- Test automatico: `tests/Feature/Filament/Ticketing/TicketResourceTest.php` — `transitioning to testing requires a tester and fails without one`
- File/componente applicativo rilevante: `app/Filament/Resources/Tickets/Support/TicketTransitionActions.php` (metodo `buildAction()`, campo `tester_id`), `app/Domain/Ticketing/StateMachine/TicketStateMachine.php` (transizione `Progress → Testing`)
- Test correlato: F1-43

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Admin

**Prerequisiti**
- L'utente `info@montagnaservizi.com` ha `ticket.transition.any` (matrice Admin, tutti i permessi).
- Esiste un ticket in stato "In lavorazione" nel dataset importato dall'ETL: filtrare l'elenco Ticket per "Stato" = "In lavorazione" e scegliere un qualunque risultato (il dataset reale non ha un titolo/assegnatario fisso e predicibile, a differenza del vecchio seed fittizio).

**Dati di test**
Un qualunque ticket in stato "In lavorazione" (vedi Prerequisiti). Tester da assegnare nel secondo tentativo: "Manager Collaudo".

**Stato iniziale**
Il ticket individuato è in stato "In lavorazione", campo Tester vuoto.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere come Admin | `info@montagnaservizi.com` / `uat` | Login riuscito |
| 2 | Nell'elenco Ticket, filtrare per "Stato" = "In lavorazione" e aprire in visualizzazione il ticket individuato nei Prerequisiti | filtro "Stato" = "In lavorazione" | Si apre la scheda di dettaglio, badge "Stato" = "In lavorazione" |
| 3 | Cliccare il pulsante di testata con etichetta "In test" | pulsante "In test" | Si apre un modale con titolo `Cambia stato in "In test"`, contenente il campo "Tester" e la checkbox "Applica anche ai ticket figli" |
| 4 | Lasciare vuoto il campo "Tester" e confermare l'azione | Tester: (vuoto) | Il modale non si chiude: viene mostrato un errore di validazione sotto il campo "Tester" che ne impedisce l'invio |
| 5 | Chiudere il modale e ricontrollare il badge "Stato" del ticket | — | Il badge mostra ancora "In lavorazione" (nessuna transizione applicata) |
| 6 | Riaprire il modale "In test", selezionare "Manager Collaudo" nel campo "Tester" e confermare l'azione | Tester: "Manager Collaudo" | Compare una notifica di successo con titolo "Stato del ticket aggiornato" |
| 7 | Osservare il badge "Stato" e il campo "Tester" nella scheda | — | Badge = "In test"; campo "Tester" = "Manager Collaudo" |

**Risultato finale atteso**
Senza un Tester selezionato la transizione non viene mai applicata (stato invariato, errore visibile sul campo); con un Tester selezionato la transizione va a buon fine e il campo Tester viene valorizzato di conseguenza.

**Controlli negativi**
Il tentativo di confermare l'azione con il campo "Tester" vuoto (passo 4) deve essere sempre bloccato lato form, senza mai produrre uno stato "In test" privo di tester.

**Evidenze da acquisire**
- Screenshot del modale con l'errore di validazione sul campo Tester (passo 4).
- Screenshot della notifica "Stato del ticket aggiornato" (passo 6).
- Screenshot del badge/campo Tester finali (passo 7).

**Criterio di superamento**

PASS: il passo 4 blocca la transizione e il passo 6-7 la completa con Tester valorizzato.
FAIL: la transizione viene applicata anche senza Tester, oppure non viene applicata affatto al passo 6 con Tester selezionato.
BLOCKED: nessun ticket in stato "In lavorazione" reperibile sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Riportare il ticket allo stato originale se necessario per collaudi successivi: dalla scheda del ticket, usare il pulsante di transizione "Da fare" (In test → Da fare, ammessa per Admin/Tester) per tornare a uno stato precedente al ciclo di test, oppure lasciare il ticket in "In test": il dataset non viene ripristinato automaticamente da un nuovo deploy se la tabella ticket non è vuota (vedi nota in F1-41).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note: il testo esatto del messaggio di errore mostrato al passo 4 non è verificabile con certezza dal codice sorgente: l'applicazione ha `APP_LOCALE=en` (`.env`) e nessun file di traduzione `it/validation.php` pubblicato, quindi il messaggio di validazione generico "campo obbligatorio" di Filament (non un messaggio custom dell'applicazione) potrebbe comparire in inglese anche in un'interfaccia altrimenti in italiano. DA VERIFICARE CON IL PRODUCT OWNER se questo è accettabile per il collaudo in produzione o se va richiesta la pubblicazione delle traduzioni italiane.

---

### F1-45 — Una transizione di stato vietata mostra all'utente il messaggio di errore leggibile tramite notifica

**Obiettivo**
Verifica che un ticket in uno stato terminale/senza transizioni verso un determinato target non esponga mai, tra i pulsanti di testata, un'azione per una transizione non presente nella tabella della macchina a stati: l'interfaccia impedisce strutturalmente di tentare (anche per errore) una transizione vietata, perché il pulsante corrispondente non viene mai costruito.

**Riferimenti**
- Requisito/regola di dominio: `App\Domain\Ticketing\StateMachine\TicketStateMachine::transitions()` — lo stato "Completato" non compare mai come `from` in nessuna riga della tabella (nemmeno nella riga jolly `* → Rifiutato`, che esclude esplicitamente `New`/`Testing`/`Done`/`Rejected`): da "Completato" non esiste alcuna transizione manuale ammessa.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketResourceTest.php` — `a forbidden status transition surfaces the localized state machine message via a notification`
- File/componente applicativo rilevante: `app/Filament/Resources/Tickets/Support/TicketTransitionActions.php` (costruzione dinamica dei pulsanti, blocco `catch (ValidationException)` con notifica "Transizione non riuscita"), `app/Domain/Ticketing/StateMachine/TicketStateMachine.php` (metodo `transitions()`/`authorize()`)
- Test correlato: Nessuno

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Admin (parte UI) + Amministratore di sistema (parte tecnica di verifica del messaggio a livello di dominio)

**Prerequisiti**
- L'utente `info@montagnaservizi.com` ha `ticket.transition.any` (tutti i permessi).
- Esiste un ticket in stato "Completato" nel dataset importato dall'ETL: filtrare l'elenco Ticket per "Stato" = "Completato" e scegliere un qualunque risultato (se nessuno esiste nel dump caricato, portarne uno in quello stato con i bottoni di transizione già testati in F1-01), e annotarne l'id per il passo tecnico 4.
- Per il passo tecnico 4: accesso a `php artisan tinker` sull'ambiente da collaudare.

**Dati di test**
Il ticket in stato "Completato" individuato nei Prerequisiti.

**Stato iniziale**
Il ticket individuato è in stato "Completato".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere come Admin e aprire il ticket in stato "Completato" individuato nei Prerequisiti | — | Si apre la scheda di dettaglio, badge "Stato" = "Completato" |
| 2 | Osservare l'intera testata della pagina, elencando tutti i pulsanti di transizione presenti | — | Nessun pulsante di cambio di stato è disponibile (in particolare, nessun pulsante "Assegnato"): lo stato "Completato" non ha transizioni manuali uscenti definite in tabella |
| 3 | Tentare di raggiungere una qualunque transizione tramite la tastiera/scorrimento della pagina (per escludere che un pulsante sia semplicemente fuori vista) | — | Conferma che nessun pulsante di transizione esiste nel DOM della pagina |
| 4 | (Passo tecnico) In `php artisan tinker`, eseguire direttamente la macchina a stati bypassando l'interfaccia: `$ticket = \App\Domain\Ticketing\Models\Ticket::find(<id del ticket individuato nei Prerequisiti>); $admin = \App\Domain\Identity\Models\User::where('email','info@montagnaservizi.com')->first(); \App\Domain\Ticketing\Actions\ChangeTicketStatus::run($ticket, \App\Domain\Ticketing\Enums\TicketStatus::Assigned, $admin);` | comando tinker sopra | Il comando lancia una `Illuminate\Validation\ValidationException` con messaggio `La transizione da "Completato" a "Assegnato" non è ammessa.` |

**Risultato finale atteso**
Da un ticket "Completato" non è raggiungibile da UI onesta alcuna transizione di stato (nessun pulsante esiste); un tentativo tecnico diretto sulla macchina a stati conferma che la stessa transizione è respinta con un messaggio di errore leggibile in italiano.

**Controlli negativi**
Nessun pulsante di transizione deve mai comparire per un target non presente in tabella per lo stato corrente del ticket, indipendentemente dal ruolo dell'utente collegato (anche l'Admin, che ha il permesso più ampio, non vede pulsanti per transizioni inesistenti).

**Evidenze da acquisire**
- Screenshot dell'intera testata della pagina al passo 2 (assenza di pulsanti di transizione).
- Output del comando tinker al passo 4.

**Criterio di superamento**

PASS: nessun pulsante di transizione compare in UI per il ticket "Completato" (passi 2-3) e il tentativo tecnico diretto (passo 4) restituisce l'errore atteso.
FAIL: un pulsante di transizione verso uno stato non ammesso compare in UI, oppure il comando tinker del passo 4 non solleva alcun errore.
BLOCKED: nessun ticket in stato "Completato" reperibile, o `tinker` non eseguibile sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il passo 4 lancia un'eccezione e non scrive nulla (nessuna colonna del ticket viene modificata da una transizione respinta).

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note: il test automatico referenziato verifica solo l'assenza del pulsante `transition_assigned` per un ticket "Completato" (passi 1-3 di questa procedura); non esiste, in tutta la suite di test Filament di questo repository, alcuna asserzione automatica sul testo o sulla comparsa effettiva della notifica "Transizione non riuscita" tramite l'interfaccia utente (nessun uso di `assertNotified` nel file dei test). La notifica e il suo testo esatto sono comunque presenti nel codice sorgente (`TicketTransitionActions.php`, blocco `catch`) e il messaggio di dominio sottostante è verificato a livello di Action nel test `tests/Feature/Domain/Ticketing/Actions/ChangeTicketStatusTest.php::a forbidden transition writes nothing and raises a localized validation error` (passo tecnico 4 di questa procedura). DA VERIFICARE CON IL PRODUCT OWNER se è richiesto un caso concreto in cui la notifica "Transizione non riuscita" sia raggiungibile e verificabile da un'interfaccia onesta (allo stato attuale del codice, ogni campo guardato dalla macchina a stati è anche un campo richiesto lato form, che blocca il submit prima ancora di raggiungere quella notifica).

---

### F1-46 — Postare un nuovo messaggio dalla scheda ticket lo fa comparire nella conversazione

**Obiettivo**
Verifica che l'azione "Aggiungi messaggio" della scheda ticket pubblichi realmente un nuovo messaggio (tramite l'Action di dominio `PostTicketMessage`) e che il messaggio compaia subito nella sezione "Conversazione" della stessa pagina, con l'autore corretto.

**Riferimenti**
- Requisito/regola di dominio: `App\Domain\Ticketing\Actions\PostTicketMessage::run()` (US-106, §6.1.7) — unico modo per pubblicare un messaggio.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketResourceTest.php` — `posting a message via the action calls PostTicketMessage and appears in the conversation`
- File/componente applicativo rilevante: `app/Filament/Resources/Tickets/Pages/ViewTicket.php` (metodo `postMessageAction()`), `app/Filament/Resources/Tickets/Schemas/TicketInfolist.php` (sezione "Conversazione")
- Test correlato: Nessuno

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- L'utente `lorena.sava@montagnaservizi.com` ha i permessi di ruolo `ticket.view.any`/`ticket-message.create`.
- Esiste almeno un ticket nel dataset importato dall'ETL.

**Dati di test**
Ticket: un qualunque ticket dell'elenco. Testo del messaggio: `Ciao, come posso aiutarti?`.

**Stato iniziale**
Il ticket individuato non ha ancora il messaggio di test nella propria conversazione.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere come Developer | `lorena.sava@montagnaservizi.com` / `uat` | Login riuscito |
| 2 | Aprire un qualunque ticket dell'elenco in visualizzazione | — | Si apre la scheda di dettaglio |
| 3 | Cliccare il pulsante di testata "Aggiungi messaggio" | pulsante "Aggiungi messaggio" | Si apre un modale con il campo "Messaggio" (editor di testo ricco) e il campo facoltativo "Allegati" |
| 4 | Digitare il testo nel campo "Messaggio" e confermare l'azione senza allegare file | `Ciao, come posso aiutarti?` | Compare una notifica di successo con titolo "Messaggio pubblicato" |
| 5 | Osservare la sezione "Conversazione" nella stessa pagina | — | Compare una nuova riga di conversazione con "Autore" = "Lorena Sava" e il testo "Ciao, come posso aiutarti?" visibile |

**Risultato finale atteso**
Il messaggio digitato compare nella sezione "Conversazione" del ticket, con l'autore corrispondente all'utente che lo ha pubblicato.

**Controlli negativi**
Non previsto per questo test: il test automatico referenziato non verifica alcun tentativo di manipolazione (es. autore forzato diverso da chi esegue l'azione).

**Evidenze da acquisire**
- Screenshot della notifica "Messaggio pubblicato" (passo 4).
- Screenshot della sezione "Conversazione" con il nuovo messaggio visibile (passo 5).

**Criterio di superamento**

PASS: il messaggio compare nella conversazione con autore e testo corretti.
FAIL: il messaggio non compare, oppure compare con autore o testo diversi da quelli inseriti.
BLOCKED: nessun ticket disponibile o impossibile accedere come Developer sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuna azione di eliminazione messaggio prevista in questa release: il messaggio di test resta permanentemente nella conversazione del ticket scelto (dato residuo non bloccante).

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

### F1-47 — La gestione dei partecipanti al ticket dalla UI è visibile solo a chi ha il permesso di assegnazione

**Obiettivo**
Verifica che i pulsanti di testata "Aggiungi partecipante"/"Rimuovi partecipante" non compaiano affatto nella scheda ticket per un utente privo del permesso `ticket.assign`, a differenza di uno staff che lo possiede.

**Riferimenti**
- Requisito/regola di dominio: `App\Filament\Resources\Tickets\Pages\ViewTicket::participantActions()` — le due action vengono costruite solo se `$user->can(Permission::TicketAssign)`.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketResourceTest.php` — `a user without ticket.assign cannot see participant management actions`
- File/componente applicativo rilevante: `app/Filament/Resources/Tickets/Pages/ViewTicket.php` (metodo `participantActions()`)
- Test correlato: Nessuno

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Customer

**Prerequisiti**
- L'utente `infosentieroitalia@cai.it` non ha `ticket.assign` nella matrice Customer (`RolePermissionSeeder`).
- Esiste almeno un ticket con richiedente "Sentiero Italia CAI - SICAI" nel dataset importato dall'ETL.

**Dati di test**
Ticket: un qualunque ticket dell'elenco proprio del Customer.

**Stato iniziale**
Il dataset importato dall'ETL reale è già popolato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere come Customer | `infosentieroitalia@cai.it` / `uat` | Login riuscito |
| 2 | Aprire un qualunque ticket proprio in visualizzazione | — | Si apre la scheda di dettaglio |
| 3 | Elencare tutti i pulsanti presenti in testata | — | Non è presente alcun pulsante "Aggiungi partecipante" |
| 4 | Confermare che non è presente nemmeno il pulsante "Rimuovi partecipante" | — | Il pulsante non è presente |

**Risultato finale atteso**
Nessun pulsante di gestione dei partecipanti (aggiunta/rimozione) è mai visibile a un Customer, indipendentemente dal ticket aperto.

**Controlli negativi**
Non previsto per questo test: il test automatico referenziato verifica solo l'assenza del pulsante, non un tentativo di richiamare l'azione tramite un identificativo diretto.

**Evidenze da acquisire**
- Screenshot della testata della pagina con l'elenco completo dei pulsanti disponibili al Customer.

**Criterio di superamento**

PASS: nessuno dei due pulsanti di gestione partecipanti compare per il Customer.
FAIL: almeno uno dei due pulsanti compare per il Customer.
BLOCKED: nessun ticket disponibile per il Customer sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: test di sola lettura, nessuna scrittura effettuata.

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

### F1-48 — La selezione di un ticket padre non valido mostra il messaggio leggibile della regola di profondità massima

**Obiettivo**
Verifica che, tentando di impostare come "Ticket padre" di un ticket che ha già dei figli un altro ticket (di per sé valido/top-level), il salvataggio venga bloccato con un errore di validazione leggibile sul campo "Ticket padre", perché violerebbe la regola di profondità massima 1 (un ticket con già figli non può a sua volta diventare figlio).

**Riferimenti**
- Requisito/regola di dominio: `App\Domain\Ticketing\Rules\TicketParentDepthRule` (PRD §6.1.6) — profondità massima 1.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketResourceTest.php` — `an invalid parent selection surfaces the readable TicketParentDepthRule message`
- File/componente applicativo rilevante: `app/Domain/Ticketing/Rules/TicketParentDepthRule.php`, `app/Filament/Resources/Tickets/Schemas/TicketForm.php` (campo `parent_id`)
- Test correlato: Nessuno

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- L'utente `info@montagnaservizi.com` ha `ticket.update.any`/`ticket.view.any` (tutti i permessi).
- L'ETL reale importa la gerarchia padre/figlio esistente in v1 (`ticket_hierarchy`), ma non è garantito trovare a comando la combinazione esatta necessaria per questo test negativo (un ticket con già figli più un candidato padre valido separato): costruire tre ticket freschi ad hoc mantiene il test deterministico indipendentemente dal dump caricato.

**Dati di test**
Tre ticket da creare: `COLL-F1-48-20260726-01 — Padre bersaglio` (A, nessun padre), `COLL-F1-48-20260726-02 — Figlio di A` (B, Ticket padre = A), `COLL-F1-48-20260726-03 — Candidato padre valido` (C, nessun padre, nessun figlio). Tutti con Richiedente = "Sentiero Italia CAI - SICAI".

**Stato iniziale**
Nessuno dei tre ticket A/B/C esiste ancora.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedere come Admin | `info@montagnaservizi.com` / `uat` | Login riuscito |
| 2 | Creare il ticket A: titolo `COLL-F1-48-20260726-01 — Padre bersaglio`, Richiedente = "Sentiero Italia CAI - SICAI", campo "Ticket padre" lasciato vuoto | vedi Dati di test | Ticket A creato senza errori |
| 3 | Creare il ticket C: titolo `COLL-F1-48-20260726-03 — Candidato padre valido`, Richiedente = "Sentiero Italia CAI - SICAI", campo "Ticket padre" lasciato vuoto | vedi Dati di test | Ticket C creato senza errori |
| 4 | Creare il ticket B: titolo `COLL-F1-48-20260726-02 — Figlio di A`, Richiedente = "Sentiero Italia CAI - SICAI", campo "Ticket padre" = ticket A | vedi Dati di test | Ticket B creato senza errori: il ticket A ora ha un figlio (B) |
| 5 | Aprire il ticket A in modifica | — | Si apre il form di modifica di A |
| 6 | Nel campo "Ticket padre", selezionare il ticket C e confermare il salvataggio | Ticket padre = C | Il salvataggio viene bloccato: compare un errore di validazione sotto il campo "Ticket padre" con il testo esatto `Non è ammessa una gerarchia di ticket a più di un livello.` |
| 7 | Ricaricare/riaprire il ticket A in visualizzazione e osservare il campo "Ticket padre" nella sezione "Gerarchia" | — | Il campo mostra ancora "Nessuno" (il salvataggio non ha avuto alcun effetto) |

**Risultato finale atteso**
Il ticket A, avendo già un figlio (B), non può essere reso a sua volta figlio del ticket C: il tentativo viene sempre respinto con il messaggio esatto della regola di dominio, e `parent_id` di A resta non impostato.

**Controlli negativi**
Il tentativo di selezionare un ticket padre di per sé valido/top-level (C non ha né padre né figli) deve comunque essere respinto quando il ticket bersaglio (A) ha già dei figli: la regola non si limita a validare il padre scelto in isolamento, ma anche lo stato del ticket bersaglio.

**Evidenze da acquisire**
- Screenshot dell'errore di validazione al passo 6 (testo esatto visibile).
- Screenshot del campo "Ticket padre" di A rimasto "Nessuno" al passo 7.

**Criterio di superamento**

PASS: il salvataggio al passo 6 viene bloccato con il messaggio esatto atteso, e `parent_id` di A resta vuoto.
FAIL: il salvataggio va a buon fine (A diventa figlio di C), oppure l'errore mostrato ha un testo diverso da quello atteso.
BLOCKED: non è possibile creare i tre ticket di test come Admin sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuna azione di eliminazione ticket disponibile in UI. Un tester tecnico può rimuovere i tre ticket di test con `\App\Domain\Ticketing\Models\Ticket::whereIn('title', ['COLL-F1-48-20260726-01 — Padre bersaglio','COLL-F1-48-20260726-02 — Figlio di A','COLL-F1-48-20260726-03 — Candidato padre valido'])->get()->each->forceDelete();` via `php artisan tinker`. In alternativa restano come dato residuo non bloccante (vedi nota in F1-41).

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

### F1-49 — L'apertura della pagina di dettaglio di un ticket registra una visualizzazione

**Obiettivo**
Verifica che l'apertura della pagina di dettaglio di un ticket registri (con throttling di 30 minuti di default) una riga in `ticket_views` per la coppia (ticket, utente, giorno), con `view_count` incrementato: un comportamento non osservabile da nessun elemento dell'interfaccia (l'infolist del ticket non mostra `view_count`/`last_viewed_at`), quindi verificabile solo con un controllo tecnico complementare sul database.

**Riferimenti**
- Requisito/regola di dominio: `App\Domain\Ticketing\Actions\RecordTicketView::run()` (US-108, §6.2.3), soglia `config('ticketing.views.throttle_minutes')` (default 30, `config/ticketing.php`).
- Test automatico: `tests/Feature/Filament/Ticketing/TicketResourceTest.php` — `opening the view page records a throttled ticket view`
- File/componente applicativo rilevante: `app/Filament/Resources/Tickets/Pages/ViewTicket.php` (metodo `resolveRecord()`), `app/Domain/Ticketing/Actions/RecordTicketView.php`
- Test correlato: Nessuno

**Modalità di esecuzione**
MISTO

**Priorità**
Media

**Ruolo del tester**
Developer (parte UI) + Amministratore di sistema (parte di verifica tecnica sul database)

**Prerequisiti**
- L'utente `lorena.sava@montagnaservizi.com` ha `ticket.view.any` (matrice Developer).
- Scegliere un ticket che il Developer non abbia ancora aperto **oggi** (per non incorrere nella soglia di throttling di 30 minuti, che impedirebbe l'incremento del contatore su una visualizzazione ripetuta): se non è chiaro quale ticket sia idoneo, verificare prima con il passo tecnico 0 sotto.
- Accesso a `php artisan tinker` (o a una query diretta sul database) per il controllo tecnico complementare.

**Dati di test**
Ticket: un qualunque ticket del dataset importato dall'ETL non ancora aperto oggi dal Developer.

**Stato iniziale**
Nessuna riga `ticket_views` esiste per la coppia (ticket scelto, `lorena.sava@montagnaservizi.com`) con data odierna.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 0 | (Facoltativo, verifica preliminare tecnica) In `php artisan tinker`: `\App\Domain\Ticketing\Models\TicketView::where('user_id', \App\Domain\Identity\Models\User::where('email','lorena.sava@montagnaservizi.com')->value('id'))->whereDate('viewed_on', now())->pluck('ticket_id');` | comando tinker sopra | Elenco degli id ticket già visti oggi dal Developer: scegliere un ticket il cui id NON compare in questo elenco |
| 1 | Accedere come Developer | `lorena.sava@montagnaservizi.com` / `uat` | Login riuscito |
| 2 | Aprire il ticket scelto in visualizzazione | ticket individuato al passo 0 | Si apre la scheda di dettaglio |
| 3 | (Passo tecnico) In `php artisan tinker`, eseguire: `\App\Domain\Ticketing\Models\TicketView::where('ticket_id', <id_ticket>)->where('user_id', <id_developer>)->whereDate('viewed_on', now())->first(['view_count','last_viewed_at']);` | comando tinker sopra, con gli id del ticket/utente del passo 2 | Il comando restituisce una riga con `view_count = 1` e `last_viewed_at` valorizzato a un istante recente |

**Risultato finale atteso**
L'apertura della pagina di dettaglio ha creato esattamente una riga `ticket_views` per la coppia (ticket, Developer, oggi), con `view_count = 1`.

**Controlli negativi**
Ripetere immediatamente il passo 2 sullo stesso ticket (entro 30 minuti) e rieseguire la query del passo 3: `view_count` deve restare `1` (non deve incrementare ad ogni apertura, per via del throttling) — comportamento atteso, non un'anomalia.

**Evidenze da acquisire**
- Output del comando tinker del passo 0 (se eseguito).
- Output del comando tinker del passo 3 (`view_count`/`last_viewed_at`).

**Criterio di superamento**

PASS: la query del passo 3 restituisce una riga con `view_count = 1`.
FAIL: nessuna riga viene trovata, oppure `view_count` è diverso da 1 alla prima apertura odierna.
BLOCKED: non è possibile eseguire `tinker`/query dirette sull'ambiente da collaudare.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Per poter ripetere il test lo stesso giorno, rimuovere la riga di test via tinker: `\App\Domain\Ticketing\Models\TicketView::where('ticket_id', <id_ticket>)->where('user_id', <id_developer>)->whereDate('viewed_on', now())->delete();`. In alternativa, scegliere un ticket diverso non ancora visto oggi.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:

## Viste operative della lista ticket

### F1-50 — La vista "Richieste attive" include ed esclude correttamente i ticket attesi

**Obiettivo**
Verificare che la tab "Richieste attive" mostri ogni ticket con un richiedente valorizzato che non sia ancora concluso, rifiutato o in backlog, ed escluda esplicitamente i ticket completati/backlog/rifiutati.

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5 (viste della lista ticket), classe `ActiveRequestsQuery`.
- Test automatico: `tests/Feature/Domain/Ticketing/Queries/ActiveRequestsQueryTest.php` — `includes tickets with a requester in an active status` (e i due test adiacenti `excludes tickets without a requester`, `excludes done, backlog and rejected tickets`).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Queries/ActiveRequestsQuery.php` (`whereNotNull('requester_id')->whereNotIn('status', [Done, Backlog, Rejected])`), `app/Filament/Resources/Tickets/Pages/ListTickets.php` (tab `active_requests`).
- Test correlato: F1-56 (Backlog), F1-57 (Archivio).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com.
- Dataset importato dall'ETL reale (`v1:import --anonymize`) presente e non alterato: a differenza del vecchio seed fittizio, non ha un conteggio o un contenuto fisso — individuare i ticket idonei con i filtri di Filament invece di assumere un titolo/indice predicibile.

**Dati di test**
- Ticket incluso atteso: un ticket reale con richiedente esterno, stato "In lavorazione" (filtrare l'elenco Ticket per Stato = "In lavorazione" e Richiedente valorizzato; se nessuno esiste nel dump caricato, portare un ticket qualunque con richiedente in quello stato con i bottoni di transizione già testati (vedi F1-72/F1-73), mai scrivendo la colonna a mano).
- Ticket escluso atteso: un ticket reale con lo stesso richiedente ma stato "Completato" (filtrare per Stato = "Completato").

**Stato iniziale**
Il dataset importato dall'ETL reale è presente: numero e contenuto dei ticket dipendono dal dump caricato, non un insieme fisso.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` e apri la risorsa Ticket | info@montagnaservizi.com / uat | Lista ticket visibile con le tab dello staff |
| 2 | Apri la tab "Richieste attive" | — | La lista si filtra |
| 3 | Cerca per titolo il ticket incluso individuato sopra | Campo ricerca titolo | Il ticket compare nell'elenco |
| 4 | Cerca per titolo il ticket escluso individuato sopra | Campo ricerca titolo | Il ticket NON compare (nessun risultato nella tab) |
| 5 | Torna alla tab "Tutti i ticket" e verifica che quest'ultimo ticket esista davvero (stato "Completato") | — | Il ticket è presente altrove, confermando che l'esclusione è dovuta al filtro della tab, non alla sua assenza |

**Risultato finale atteso**
La tab "Richieste attive" mostra il ticket in "In lavorazione" e non mostra il ticket "Completato", pur essendo entrambi presenti nel sistema con lo stesso richiedente.

**Controlli negativi**
Verificare che anche un ticket in Backlog non compaia in "Richieste attive" (ha una tab dedicata, F1-56).

**Evidenze da acquisire**
- Screenshot della tab "Richieste attive" con il ticket incluso visibile.
- Screenshot della ricerca che mostra "nessun risultato" per il ticket completato.

**Criterio di superamento**

PASS: il ticket in stato attivo compare, il ticket completato non compare nella tab.
FAIL: uno dei due esiti è invertito.
BLOCKED: impossibile accedere alla lista ticket.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-51 — La vista "In attesa" ordina i ticket dal più vecchio, per giorni di attesa decrescenti

**Obiettivo**
Verificare che la tab "In attesa" mostri solo i ticket nello stato "In attesa" e li ordini per `status_changed_at` crescente, cioè con il ticket in attesa da più tempo in cima all'elenco.

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5, classe `WaitingQuery` (estende `ActiveRequestsQuery`).
- Test automatico: `tests/Feature/Domain/Ticketing/Queries/WaitingQueryTest.php::orders the oldest waiting ticket first (ascending status_changed_at)`.
- File/componente applicativo rilevante: `app/Domain/Ticketing/Queries/WaitingQuery.php` (`->orderBy('status_changed_at', 'asc')`), colonna "Giorni in stato" di `TicketsTable.php`.
- Test correlato: F1-50 (Richieste attive, base condivisa).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com.
- Dataset importato dall'ETL reale presente e non alterato: a differenza del vecchio seed fittizio,
  non ha un conteggio o un contenuto fisso — individuare i ticket idonei con i filtri di Filament.
- Almeno due o tre ticket reali in stato "In attesa", idealmente con richiedente "Sentiero Italia CAI - SICAI",
  con valori distinti della colonna "Giorni in stato" (filtrare l'elenco Ticket per Stato = "In
  attesa" e ordinare/osservare quella colonna). Se il dump caricato ne contiene meno di due con valori
  distinguibili, portarne alcuni in "In attesa" con i bottoni di transizione già testati in F1-01,
  distanziando le transizioni nel tempo (anche di pochi minuti) in modo da ottenere `status_changed_at`
  diversi e quindi un ordine osservabile nella tab.

**Dati di test**
I ticket in stato "In attesa" individuati sopra, annotandone l'ordine per "Giorni in stato" (dal
valore più alto, il più vecchio, al più basso, il più recente).

**Stato iniziale**
I ticket individuati sono in stato "In attesa" con `status_changed_at` distinti tra loro.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come admin | info@montagnaservizi.com | Accesso riuscito |
| 2 | Apri la tab "In attesa" della lista Ticket | — | Elenco filtrato ai soli ticket "In attesa" |
| 3 | Annota l'ordine di comparsa dei ticket individuati sopra e il valore della colonna "Giorni in stato" per ciascuno | — | Il ticket con "Giorni in stato" più alto (il più vecchio) compare per primo, poi gli altri in ordine decrescente di quel valore, fino al più recente per ultimo |
| 4 | Verifica che nessun ticket in altro stato (es. "In lavorazione") compaia nella tab | — | Nessun ticket fuori stato "In attesa" è presente |

**Risultato finale atteso**
I ticket individuati compaiono ordinati dal più vecchio (valore "Giorni in stato" più alto) al più recente (valore più basso), coerente con `status_changed_at` crescente lungo l'elenco.

**Controlli negativi**
Un ticket in stato "In lavorazione" non deve mai comparire nella tab "In attesa".

**Evidenze da acquisire**
- Screenshot della tab "In attesa" con i ticket individuati e la colonna "Giorni in stato" visibile, nell'ordine osservato.

**Criterio di superamento**

PASS: i ticket individuati compaiono solo in questa tab e nell'ordine dal più vecchio al più recente.
FAIL: ordine invertito/casuale, oppure compaiono ticket non in stato "In attesa".
BLOCKED: impossibile accedere alla tab.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-52 — La vista "Assegnati a me" mostra solo i ticket assegnati all'utente corrente

**Obiettivo**
Verificare che la tab "Assegnati a me" mostri solo i ticket il cui `assignee_id` corrisponde all'utente autenticato, escludendo i ticket assegnati ad altri e quelli, pur assegnati all'utente, in stato "Nuovo" o "Completato".

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5, classe `AssignedToMeQuery`.
- Test automatico: `tests/Feature/Domain/Ticketing/Queries/AssignedToMeQueryTest.php::includes tickets assigned to the actor that are neither new nor done` (e `excludes tickets assigned to someone else, and new/done tickets assigned to the actor`).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Queries/AssignedToMeQuery.php` (`where('assignee_id', $user->id)->whereNotIn('status', [New, Done])`).
- Test correlato: F1-62 (In lavorazione, indipendente dall'assegnatario).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Accesso a `/admin` come lorena.sava@montagnaservizi.com.
- Dataset importato dall'ETL reale presente e non alterato: individuare i ticket idonei con i filtri Filament (Stato/Assegnatario) invece di assumere titolo/indice fissi.

**Dati di test**
- Ticket incluso atteso: un ticket reale con assegnatario "Lorena Sava" e stato diverso da "Nuovo"/"Completato" (es. "In lavorazione"); se nessuno esiste, assegnarne uno con l'azione di assegnazione già testata e portarlo in quello stato con i bottoni di transizione (mai scrivendo le colonne a mano).
- Ticket escluso atteso: un ticket reale con lo stesso assegnatario "Lorena Sava" ma stato "Nuovo" (stesso assegnatario, ma stato escluso).
- Ticket escluso atteso (assegnatario diverso): un ticket reale assegnato a "Manager Collaudo" (qualunque stato).

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come Lorena Sava | lorena.sava@montagnaservizi.com | Accesso riuscito |
| 2 | Apri la tab "Assegnati a me" | — | Elenco filtrato |
| 3 | Verifica che il ticket incluso individuato sopra sia presente | — | Presente |
| 4 | Verifica che il ticket escluso individuato sopra (stato Nuovo, stesso assegnatario) NON sia presente | — | Assente |
| 5 | Verifica che il ticket escluso individuato sopra (assegnato a Manager Collaudo) NON sia presente | — | Assente |

**Risultato finale atteso**
Solo i ticket assegnati a "Lorena Sava" in stato diverso da "Nuovo"/"Completato" compaiono nella tab.

**Controlli negativi**
Nessuno applicabile oltre ai due esclusi già verificati ai passi 4-5.

**Evidenze da acquisire**
- Screenshot della tab "Assegnati a me" con il ticket incluso visibile.

**Criterio di superamento**

PASS: solo il ticket atteso compare, i due esclusi non compaiono.
FAIL: uno dei ticket esclusi compare, oppure il ticket atteso è assente.
BLOCKED: impossibile accedere come Lorena Sava.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-53 — La vista "Da testare" mostra solo i ticket in cui l'utente corrente è il tester

**Obiettivo**
Verificare che la tab "Da testare" mostri solo i ticket in stato "In test" il cui `tester_id` corrisponde all'utente autenticato, escludendo i ticket testati da altri e i ticket dove l'utente è tester ma il ticket non è (più) in "In test".

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5, classe `ToTestByMeQuery`.
- Test automatico: `tests/Feature/Domain/Ticketing/Queries/ToTestByMeQueryTest.php::includes tickets in testing where the actor is the tester` (e `excludes tickets tested by someone else, and non-testing tickets tested by the actor`).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Queries/ToTestByMeQuery.php` (`where('tester_id', $user->id)->where('status', Testing)`).
- Test correlato: F1-54 (In test, qualunque tester).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Developer

**Prerequisiti**
- Accesso a `/admin` come lorena.sava@montagnaservizi.com.
- Dataset importato dall'ETL reale presente e non alterato: individuare i ticket idonei con i filtri Filament (Stato/Tester) invece di assumere titolo/indice fissi; se il dump caricato non ha già un ticket con tester "Lorena Sava", impostarlo con l'azione di assegnazione tester già testata nelle story precedenti, mai scrivendo la colonna a mano.

**Dati di test**
- Ticket incluso atteso: un ticket reale in stato "In test" con tester "Lorena Sava".
- Ticket escluso atteso: un ticket reale in stato "Testato" (non più "In test") con tester comunque "Lorena Sava".

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come Lorena Sava | lorena.sava@montagnaservizi.com | Accesso riuscito |
| 2 | Apri la tab "Da testare" | — | Elenco filtrato |
| 3 | Verifica che il ticket incluso individuato sopra (stato "In test") sia presente | — | Presente |
| 4 | Verifica che il ticket escluso individuato sopra (stato "Testato") NON sia presente | — | Assente |

**Risultato finale atteso**
Solo il ticket "In test" con tester = utente corrente compare; il ticket "Testato" con lo stesso tester non compare.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della tab "Da testare" con il ticket incluso visibile.

**Criterio di superamento**

PASS: esito coerente con quanto descritto sopra per entrambi i ticket.
FAIL: uno dei due esiti è invertito.
BLOCKED: impossibile accedere come Lorena Sava.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-54 — La vista "In test" mostra solo i ticket nello stato di collaudo interno

**Obiettivo**
Verificare che la tab "In test" mostri ogni richiesta attiva in stato "In test", indipendentemente da chi ne sia il tester (a differenza di "Da testare", F1-53, che restringe al tester = utente corrente).

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5, classe `InTestingQuery` (estende `ActiveRequestsQuery`).
- Test automatico: `tests/Feature/Domain/Ticketing/Queries/InTestingQueryTest.php::includes any active request in testing, regardless of tester`.
- File/componente applicativo rilevante: `app/Domain/Ticketing/Queries/InTestingQuery.php`.
- Test correlato: F1-53 (Da testare, ristretta al tester corrente).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Manager

**Prerequisiti**
- Accesso a `/admin` come manager@oc.test (verificare con il filtro Tester che non sia lui stesso il tester del ticket incluso individuato sotto — se lo fosse, scegliere un altro ticket idoneo).
- Dataset importato dall'ETL reale presente e non alterato: individuare i ticket idonei con i filtri Filament (Stato) invece di assumere titolo/indice fissi.

**Dati di test**
- Ticket incluso atteso: un ticket reale in stato "In test", con un tester diverso dall'utente che esegue il test (es. "Lorena Sava").
- Ticket escluso atteso: un ticket reale in stato "In lavorazione".

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come Manager Collaudo | manager@oc.test | Accesso riuscito |
| 2 | Apri la tab "In test" | — | Elenco filtrato |
| 3 | Verifica che il ticket incluso individuato sopra sia presente, pur non essendo Manager Collaudo il tester | — | Presente |
| 4 | Verifica che il ticket escluso individuato sopra (In lavorazione) NON sia presente | — | Assente |

**Risultato finale atteso**
Il ticket in "In test" compare indipendentemente da chi sia il tester; il ticket in altro stato non compare.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della tab "In test" con il ticket incluso.

**Criterio di superamento**

PASS: comportamento come descritto.
FAIL: il ticket "In test" non compare oppure il ticket "In lavorazione" compare erroneamente.
BLOCKED: impossibile accedere come Manager Collaudo.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-55 — La vista "Problemi" mostra solo i ticket nello stato problema

**Obiettivo**
Verificare che la tab "Problemi" mostri solo le richieste attive in stato "Problema".

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5, classe `ProblemTicketsQuery` (estende `ActiveRequestsQuery`).
- Test automatico: `tests/Feature/Domain/Ticketing/Queries/ProblemTicketsQueryTest.php::includes active requests in problem status`.
- File/componente applicativo rilevante: `app/Domain/Ticketing/Queries/ProblemTicketsQuery.php`.
- Test correlato: F1-51 (In attesa, stessa base "Richieste attive").

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com.
- Dataset importato dall'ETL reale presente e non alterato: individuare i ticket idonei con i filtri Filament (Stato) invece di assumere titolo/indice fissi; se il dump caricato non ha un ticket in stato "Problema", portarne uno in quello stato con i bottoni di transizione già testati, mai scrivendo la colonna a mano.

**Dati di test**
- Ticket incluso atteso: un ticket reale in stato "Problema".
- Ticket escluso atteso: un ticket reale in stato "In attesa".

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come admin | info@montagnaservizi.com | Accesso riuscito |
| 2 | Apri la tab "Problemi" | — | Elenco filtrato |
| 3 | Verifica che il ticket incluso individuato sopra sia presente | — | Presente |
| 4 | Verifica che il ticket escluso individuato sopra (In attesa) NON sia presente | — | Assente |

**Risultato finale atteso**
Solo il ticket in stato "Problema" compare nella tab.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della tab "Problemi" con il ticket incluso.

**Criterio di superamento**

PASS: comportamento come descritto.
FAIL: esito invertito.
BLOCKED: impossibile accedere alla lista ticket.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-56 — La vista "Backlog" mostra solo i ticket nello stato backlog

**Obiettivo**
Verificare che la tab "Backlog" mostri solo i ticket con richiedente valorizzato in stato "Backlog".

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5, classe `BacklogQuery`.
- Test automatico: `tests/Feature/Domain/Ticketing/Queries/BacklogQueryTest.php::includes backlog tickets with a requester`.
- File/componente applicativo rilevante: `app/Domain/Ticketing/Queries/BacklogQuery.php` (`whereNotNull('requester_id')->where('status', Backlog)`).
- Test correlato: F1-61 (Nuovi).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com.
- Dataset importato dall'ETL reale presente e non alterato: individuare i ticket idonei con i filtri Filament (Stato e Richiedente valorizzato) invece di assumere titolo/indice fissi.

**Dati di test**
- Ticket incluso atteso: un ticket reale con richiedente valorizzato e stato "Backlog".
- Ticket escluso atteso: un ticket reale in stato "Nuovo".

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come admin | info@montagnaservizi.com | Accesso riuscito |
| 2 | Apri la tab "Backlog" | — | Elenco filtrato |
| 3 | Verifica che il ticket incluso individuato sopra sia presente | — | Presente |
| 4 | Verifica che il ticket escluso individuato sopra (Nuovo) NON sia presente | — | Assente |

**Risultato finale atteso**
Solo il ticket in stato "Backlog" compare nella tab.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della tab "Backlog" con il ticket incluso.

**Criterio di superamento**

PASS: comportamento come descritto.
FAIL: esito invertito.
BLOCKED: impossibile accedere alla lista ticket.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-57 — La vista "Archivio" (staff) mostra solo i ticket conclusi

**Obiettivo**
Verificare che la tab "Archivio" (vista staff) mostri i ticket in stato "Completato" o "Rifiutato", qualunque sia il richiedente, ed escluda ogni ticket ancora aperto.

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5, classe `ArchivedTicketsQuery`.
- Test automatico: `tests/Feature/Domain/Ticketing/Queries/ArchivedTicketsQueryTest.php::includes done and rejected tickets, with or without a requester` (e `excludes tickets in any non-archived status`).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Queries/ArchivedTicketsQuery.php` (`whereIn('status', [Done, Rejected])`, nessun vincolo su `requester_id`).
- Test correlato: F1-60 (Archivio, vista cliente — query diversa, `MyArchivedTicketsQuery`).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com.
- Dataset importato dall'ETL reale presente e non alterato: individuare i ticket idonei con i filtri Filament (Stato) invece di assumere titolo/indice fissi.

**Dati di test**
- Ticket incluso atteso: un ticket reale in stato "Completato".
- Ticket incluso atteso: un ticket reale in stato "Rifiutato".
- Ticket escluso atteso: un ticket reale in stato "In lavorazione".

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come admin | info@montagnaservizi.com | Accesso riuscito |
| 2 | Apri la tab "Archivio" | — | Elenco filtrato |
| 3 | Verifica che il primo ticket incluso individuato sopra (Completato) sia presente | — | Presente |
| 4 | Verifica che il secondo ticket incluso individuato sopra (Rifiutato) sia presente | — | Presente |
| 5 | Verifica che il ticket escluso individuato sopra (In lavorazione) NON sia presente | — | Assente |

**Risultato finale atteso**
Entrambi gli stati archiviati ("Completato", "Rifiutato") compaiono nella tab; nessun ticket ancora aperto compare.

**Controlli negativi**
Il caso "ticket concluso senza alcun richiedente" (`requester_id` nullo) è coperto solo dal test automatico citato: non è riproducibile dal form standard di creazione ticket, che richiede sempre un richiedente per lo staff (`Select::make('requester_id')->required()` in `TicketForm.php`). Non blocca l'esito di questo test manuale.

**Evidenze da acquisire**
- Screenshot della tab "Archivio" con i due ticket inclusi visibili.

**Criterio di superamento**

PASS: entrambi i ticket archiviati compaiono, il ticket aperto non compare.
FAIL: uno degli esiti è invertito.
BLOCKED: impossibile accedere alla lista ticket.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-58 — La vista "Interni" mostra solo i ticket senza un richiedente esterno

**Obiettivo**
Verificare che la tab "Interni" mostri i ticket con richiedente valorizzato il cui richiedente NON ha il ruolo "Socio CAI"/cliente (staff che apre un ticket per sé), escludendo i ticket dei clienti reali e quelli già completati. Il dataset importato dall'ETL reale potrebbe non contenere alcun esempio pronto di ticket con richiedente staff (i richiedenti v1 sono tipicamente clienti): verificare prima con il filtro Richiedente, e se assente crearne uno ad hoc come descritto sotto.

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5, classe `InternalTicketsQuery`.
- Test automatico: `tests/Feature/Domain/Ticketing/Queries/InternalTicketsQueryTest.php::includes tickets whose requester has no customer role and are not done` (e `excludes tickets requested by a customer, without a requester, or already done`).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Queries/InternalTicketsQuery.php` (`whereDoesntHave('roles', ... 'customer')`), `app/Filament/Resources/Tickets/Schemas/TicketForm.php` (il campo "Richiedente" è editabile e obbligatorio per lo staff, senza restrizione di ruolo sulle opzioni).
- Test correlato: F1-63 (Tutti i ticket di clienti — lo stesso ticket creato qui va usato come esempio escluso in quel test).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com.
- Esiste l'utente "Lorena Sava" (ruolo Developer, non Customer).

**Dati di test**
- Nuovo ticket con titolo `COLL-F1-58-20260726-01`, Richiedente = "Lorena Sava" (staff, non cliente). Resta in stato "Nuovo" dopo la creazione.
- Ticket escluso atteso: un ticket reale del dataset importato con richiedente esterno (cliente).

**Stato iniziale**
Nessun ticket `COLL-F1-58-20260726-01` presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come admin | info@montagnaservizi.com | Accesso riuscito |
| 2 | Crea un nuovo ticket | Titolo `COLL-F1-58-20260726-01`; sezione "Assegnazione e classificazione" → Richiedente = "Lorena Sava" | Ticket creato in stato "Nuovo" |
| 3 | Apri la tab "Interni" | — | Elenco filtrato |
| 4 | Verifica che `COLL-F1-58-20260726-01` sia presente | — | Presente |
| 5 | Verifica che il ticket escluso individuato sopra (richiedente cliente) NON sia presente | — | Assente |

**Risultato finale atteso**
Solo il ticket con richiedente non-cliente compare nella tab "Interni"; i ticket con richiedente cliente non compaiono.

**Controlli negativi**
Verificare che, se il ticket `COLL-F1-58-20260726-01` venisse portato allo stato "Completato", sparirebbe dalla tab "Interni" (coerente con `->where('status', '!=', Done)`) — verifica facoltativa, non bloccante per il PASS di questo test.

**Evidenze da acquisire**
- Screenshot della tab "Interni" con il ticket creato visibile.
- Screenshot della tab "Interni" senza il ticket del cliente.

**Criterio di superamento**

PASS: il ticket con richiedente staff compare, il ticket con richiedente cliente non compare.
FAIL: uno degli esiti è invertito.
BLOCKED: impossibile creare il ticket o accedere alla tab.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy (il ticket aggiuntivo non altera i ticket importati dall'ETL). Se si preferisce non lasciare il ticket residuo tra un deploy e l'altro, eliminarlo manualmente dalla lista.

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

### F1-59 — La vista "I miei ticket" per un cliente mostra solo le proprie richieste non ancora concluse

**Obiettivo**
Verificare che, per un cliente, la tab "I miei ticket" mostri solo le proprie richieste non ancora completate/rifiutate, senza mai mostrare ticket di altri richiedenti (isolamento cliente-cliente, requisito di sicurezza).

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5, classe `MyTicketsQuery`, §9.5 (`Ticket::scopeVisibleTo`, permesso `ticket.view.own`).
- Test automatico: `tests/Feature/Domain/Ticketing/Queries/MyTicketsQueryTest.php::includes the customer own tickets that are not done or rejected` (e `excludes other requesters tickets and the actor own done/rejected tickets`).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Queries/MyTicketsQuery.php`, `app/Filament/Resources/Tickets/Pages/ListTickets.php` (tab clienti: solo "I miei ticket"/"Archivio").
- Test correlato: F1-60 (Archivio cliente).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Customer

**Prerequisiti**
- Accesso a `/admin` come infosentieroitalia@cai.it.
- Dataset importato dall'ETL reale presente e non alterato: individuare con l'admin, prima di accedere come cliente, un ticket con richiedente "Sentiero Italia CAI - SICAI" in stato "In lavorazione" e uno, stesso richiedente, in stato "Completato" (filtri Filament su Richiedente/Stato).

**Dati di test**
- Ticket incluso atteso: un ticket reale con richiedente "Sentiero Italia CAI - SICAI" (l'utente stesso), stato "In lavorazione".
- Ticket escluso atteso: un ticket reale con lo stesso richiedente, stato "Completato".

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come Sentiero Italia CAI - SICAI | infosentieroitalia@cai.it | Accesso riuscito; solo le tab "I miei ticket"/"Archivio" sono visibili (nessuna tab staff) |
| 2 | Apri la tab "I miei ticket" (di norma già selezionata) | — | Elenco filtrato |
| 3 | Verifica che il ticket incluso individuato sopra sia presente | — | Presente |
| 4 | Verifica che il ticket escluso individuato sopra (Completato) NON sia presente | — | Assente (si trova invece in "Archivio", F1-60) |

**Risultato finale atteso**
Solo le proprie richieste non concluse compaiono in "I miei ticket".

**Controlli negativi**
Nessun ticket di un altro richiedente deve mai comparire: individuare come admin un ticket reale con un richiedente diverso da "Sentiero Italia CAI - SICAI" (il dataset importato dall'ETL reale ne contiene tipicamente molti, a differenza del vecchio seed fittizio che ne aveva uno solo) e verificare che non compaia in "I miei ticket" per l'utente sotto test.

**Evidenze da acquisire**
- Screenshot della tab "I miei ticket" con il ticket incluso.
- Screenshot che conferma l'assenza delle tab riservate allo staff per questo utente.

**Criterio di superamento**

PASS: comportamento come descritto.
FAIL: il ticket completato compare, oppure il ticket in corso è assente, oppure compaiono tab riservate allo staff.
BLOCKED: impossibile accedere come cliente.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-60 — La vista "Archivio" per un cliente mostra solo le proprie richieste concluse o rifiutate

**Obiettivo**
Verificare che, per un cliente, la tab "Archivio" mostri solo le proprie richieste concluse/rifiutate, escludendo quelle ancora aperte.

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5, classe `MyArchivedTicketsQuery`.
- Test automatico: `tests/Feature/Domain/Ticketing/Queries/MyArchivedTicketsQueryTest.php::includes the customer own done and rejected tickets` (e `excludes other requesters archived tickets and the actor own non-archived tickets`).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Queries/MyArchivedTicketsQuery.php`.
- Test correlato: F1-59 (I miei ticket), F1-57 (Archivio staff — query diversa).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Customer

**Prerequisiti**
- Accesso a `/admin` come infosentieroitalia@cai.it.
- Dataset importato dall'ETL reale presente e non alterato: individuare con l'admin, prima di accedere come cliente, un ticket con richiedente "Sentiero Italia CAI - SICAI" in stato "Completato" e uno, stesso richiedente, in stato "In lavorazione" (filtri Filament su Richiedente/Stato).

**Dati di test**
- Ticket incluso atteso: un ticket reale con richiedente "Sentiero Italia CAI - SICAI", stato "Completato".
- Ticket escluso atteso: un ticket reale con lo stesso richiedente, stato "In lavorazione".

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come Sentiero Italia CAI - SICAI | infosentieroitalia@cai.it | Accesso riuscito |
| 2 | Apri la tab "Archivio" | — | Elenco filtrato |
| 3 | Verifica che il ticket incluso individuato sopra sia presente | — | Presente |
| 4 | Verifica che il ticket escluso individuato sopra (In lavorazione) NON sia presente | — | Assente |

**Risultato finale atteso**
Solo le proprie richieste concluse/rifiutate compaiono in "Archivio".

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della tab "Archivio" (cliente) con il ticket incluso.

**Criterio di superamento**

PASS: comportamento come descritto.
FAIL: esito invertito.
BLOCKED: impossibile accedere come cliente.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-61 — La vista "Nuovi" mostra solo i ticket appena creati non ancora assegnati

**Obiettivo**
Verificare che la tab "Nuovi" mostri solo i ticket con richiedente valorizzato in stato "Nuovo".

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5, classe `NewTicketsQuery`.
- Test automatico: `tests/Feature/Domain/Ticketing/Queries/NewTicketsQueryTest.php::includes new tickets with a requester`.
- File/componente applicativo rilevante: `app/Domain/Ticketing/Queries/NewTicketsQuery.php`.
- Test correlato: F1-56 (Backlog).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com.
- Dataset importato dall'ETL reale presente e non alterato: individuare i ticket idonei con i filtri Filament (Stato e Richiedente valorizzato) invece di assumere titolo/indice fissi.

**Dati di test**
- Ticket incluso atteso: un ticket reale con richiedente valorizzato in stato "Nuovo".
- Ticket escluso atteso: un ticket reale in stato "Assegnato".

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come admin | info@montagnaservizi.com | Accesso riuscito |
| 2 | Apri la tab "Nuovi" | — | Elenco filtrato |
| 3 | Verifica che il ticket incluso individuato sopra sia presente | — | Presente |
| 4 | Verifica che il ticket escluso individuato sopra (Assegnato) NON sia presente | — | Assente |

**Risultato finale atteso**
Solo i ticket in stato "Nuovo" compaiono nella tab.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della tab "Nuovi" con il ticket incluso.

**Criterio di superamento**

PASS: comportamento come descritto.
FAIL: esito invertito.
BLOCKED: impossibile accedere alla lista ticket.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-62 — La vista "In lavorazione" mostra solo i ticket nello stato progress

**Obiettivo**
Verificare che la tab "In lavorazione" mostri ogni ticket con richiedente valorizzato in stato "In lavorazione", indipendentemente dall'assegnatario (a differenza di "Assegnati a me", F1-52, ristretta all'utente corrente).

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5, classe `InProgressTicketsQuery`.
- Test automatico: `tests/Feature/Domain/Ticketing/Queries/InProgressTicketsQueryTest.php::includes any in-progress ticket with a requester, regardless of assignee`.
- File/componente applicativo rilevante: `app/Domain/Ticketing/Queries/InProgressTicketsQuery.php`.
- Test correlato: F1-52 (Assegnati a me).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Manager

**Prerequisiti**
- Accesso a `/admin` come manager@oc.test.
- Dataset importato dall'ETL reale presente e non alterato: individuare i ticket idonei con i filtri Filament (Stato e Assegnatario) invece di assumere titolo/indice fissi.

**Dati di test**
- Ticket incluso atteso: un ticket reale con richiedente valorizzato in stato "In lavorazione", assegnato a un utente diverso da chi esegue il test (es. "Lorena Sava").
- Ticket escluso atteso: un ticket reale in stato "Da fare".

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come Manager Collaudo | manager@oc.test | Accesso riuscito |
| 2 | Apri la tab "In lavorazione" | — | Elenco filtrato |
| 3 | Verifica che il ticket incluso individuato sopra sia presente, pur non essendo assegnato al Manager | — | Presente |
| 4 | Verifica che il ticket escluso individuato sopra (Da fare) NON sia presente | — | Assente |

**Risultato finale atteso**
Ogni ticket "In lavorazione" compare a prescindere dall'assegnatario; i ticket in altro stato non compaiono.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della tab "In lavorazione" con il ticket incluso.

**Criterio di superamento**

PASS: comportamento come descritto.
FAIL: esito invertito.
BLOCKED: impossibile accedere alla lista ticket.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-63 — La vista "Tutti i ticket di clienti" mostra tutti i ticket con un richiedente esterno, indipendentemente dallo stato

**Obiettivo**
Verificare che la tab "Tutti i ticket di clienti" mostri ogni ticket il cui richiedente ha il ruolo "Socio CAI"/cliente, qualunque sia lo stato (incluso "Completato", a differenza di "Richieste attive"), ed escluda i ticket il cui richiedente non ha quel ruolo.

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5, classe `AllCustomerTicketsQuery`.
- Test automatico: `tests/Feature/Domain/Ticketing/Queries/AllCustomerTicketsQueryTest.php::includes tickets whose requester has the customer role, regardless of status` (e `excludes tickets whose requester does not have the customer role`).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Queries/AllCustomerTicketsQuery.php`.
- Test correlato: F1-58 (Interni — usa lo stesso ticket creato in quel test come esempio escluso qui).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com.
- Consigliato eseguire questo test dopo F1-58 (riusa lo stesso ticket creato lì come esempio escluso), oppure creare al volo un ticket equivalente se F1-58 non è stato eseguito in questa sessione.

**Dati di test**
- Ticket incluso atteso: un ticket reale con richiedente "Sentiero Italia CAI - SICAI" (cliente) in stato "Completato" (filtrare per Richiedente/Stato) — incluso nonostante lo stato concluso.
- Ticket escluso atteso: `COLL-F1-58-20260726-01` (creato in F1-58) con richiedente "Lorena Sava" (non cliente); se non disponibile, crearne uno equivalente con titolo `COLL-F1-63-20260726-01` e Richiedente = "Manager Collaudo".

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato (più, se presente, il ticket creato in F1-58).

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come admin | info@montagnaservizi.com | Accesso riuscito |
| 2 | Apri la tab "Tutti i ticket di clienti" | — | Elenco filtrato |
| 3 | Verifica che il ticket incluso individuato sopra (Completato) sia presente | — | Presente, nonostante lo stato concluso |
| 4 | Verifica che il ticket con richiedente non-cliente (`COLL-F1-58-...` o equivalente) NON sia presente | — | Assente |

**Risultato finale atteso**
Ogni ticket con richiedente cliente compare qualunque sia lo stato; i ticket con richiedente non-cliente non compaiono mai in questa tab.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot della tab "Tutti i ticket di clienti" con il ticket completato visibile.

**Criterio di superamento**

PASS: comportamento come descritto.
FAIL: esito invertito.
BLOCKED: impossibile accedere alla lista ticket.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy. Se creato per questo test, eliminare `COLL-F1-63-20260726-01`.

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

## Filtri della lista ticket

### F1-64 — Il filtro per stato permette la selezione multipla

**Obiettivo**
Verificare che il filtro "Stato" della tabella ticket permetta di selezionare più valori contemporaneamente e restituisca l'unione dei ticket in quegli stati.

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5.1, `SelectFilter::make('status')->multiple()`.
- Test automatico: `tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php::status filter accepts multiple values`.
- File/componente applicativo rilevante: `app/Filament/Resources/Tickets/Tables/TicketsTable.php` (filtro "Stato").
- Test correlato: F1-68 (composizione filtro + tab).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com.
- Dataset importato dall'ETL reale presente e non alterato: individuare i ticket idonei con i filtri Filament (Stato) invece di assumere titolo/indice fissi.

**Dati di test**
- Ticket inclusi attesi: un ticket reale in stato "Nuovo" e un ticket reale in stato "Backlog".
- Ticket escluso atteso: un ticket reale in stato "Completato".

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato; tab "Tutti i ticket" attiva, nessun filtro applicato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come admin e apri la lista Ticket (tab "Tutti i ticket") | info@montagnaservizi.com | Elenco completo visibile |
| 2 | Apri il pannello filtri e seleziona nel filtro "Stato" i valori "Nuovo" e "Backlog" | Filtro "Stato" = ["Nuovo", "Backlog"] | Il filtro si applica |
| 3 | Verifica che i due ticket dei rispettivi stati compaiano | — | Entrambi presenti |
| 4 | Verifica che il ticket in stato "Completato" non compaia | — | Assente |

**Risultato finale atteso**
Con due valori selezionati nel filtro "Stato", compaiono i ticket di entrambi gli stati e nessun altro.

**Controlli negativi**
Deselezionare uno dei due valori (lasciare solo "Nuovo"): il ticket in "Backlog" deve sparire dall'elenco.

**Evidenze da acquisire**
- Screenshot del filtro "Stato" con i due valori selezionati e l'elenco risultante.

**Criterio di superamento**

PASS: entrambi i ticket attesi compaiono, quello in "Completato" non compare.
FAIL: uno degli esiti è invertito, oppure il filtro non accetta la selezione multipla.
BLOCKED: impossibile accedere alla lista ticket.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-65 — Il filtro per organizzazione del richiedente restringe correttamente la lista

**Obiettivo**
Verificare che il filtro "Organizzazione del richiedente" mostri solo i ticket il cui richiedente è associato all'organizzazione selezionata.

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5.1, filtro `organization_id` (doppio `whereHas` `requester → organizations`).
- Test automatico: `tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php::organization filter narrows the list by the requester organization`.
- File/componente applicativo rilevante: `app/Filament/Resources/Tickets/Tables/TicketsTable.php` (filtro "Organizzazione del richiedente"), relazione `User::organizations(): BelongsToMany`.
- Test correlato: Nessuno.

**Nota importante — gap di dati/UI**
Il dataset importato dall'ETL reale ha un numero variabile di organizzazioni e associazioni
utente↔organizzazione, secondo il dump caricato: potrebbe già contenere naturalmente due utenti
richiedenti associati a organizzazioni diverse, oppure no. Inoltre non esiste, in tutto il pannello
Filament, alcuna schermata per gestire questa associazione (nessuna `OrganizationResource`, nessun
campo "Organizzazione" nel form utente): l'unico punto in cui la relazione compare è, in sola
lettura, questo stesso filtro. Verificare prima con i filtri Filament se la combinazione necessaria
esiste già nel dump; solo se manca, ricorrere allo step tecnico sotto (`$user->organizations()
->syncWithoutDetaching([$org->id])` in tinker, coerente con l'assenza di un'Action di dominio
dedicata a questa associazione). **DA VERIFICARE CON IL PRODUCT OWNER** se è prevista, in una fase
successiva, una schermata di gestione organizzazioni/associazione utente/organizzazione.

**Modalità di esecuzione**
MISTO

**Priorità**
Media

**Ruolo del tester**
Admin (per la parte UI) + Amministratore di sistema (per lo step tecnico di collegamento)

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com.
- Accesso tecnico a `php artisan tinker` sull'ambiente UAT (o equivalente, es. un comando one-off).

**Dati di test**
- Due organizzazioni distinte, una qualunque tra quelle presenti nell'ambiente (dal dump importato).
- Due utenti richiedenti da associare, uno per organizzazione — se non già associati nel dump,
  associarli via tinker come sopra (es. "Sentiero Italia CAI - SICAI" → prima organizzazione; "Manager
  Collaudo" → seconda organizzazione, usato qui solo come secondo richiedente distinto).
- Due nuovi ticket: `COLL-F1-65-20260726-01` (richiedente il primo utente) e `COLL-F1-65-20260726-02` (richiedente il secondo utente).

**Stato iniziale**
Nessun utente aggiuntivo è associato a un'organizzazione oltre a quanto già presente nel dump.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 (tecnico) | Se necessario, da `php artisan tinker`, recupera due organizzazioni e associa i due utenti scelti, una organizzazione ciascuno (`$user->organizations()->syncWithoutDetaching([$org->id])`) | Vedi comando sopra | Le due associazioni sono create senza errori (o già presenti nel dump) |
| 2 | Accedi a `/admin` come admin | info@montagnaservizi.com | Accesso riuscito |
| 3 | Crea `COLL-F1-65-20260726-01` con Richiedente = il primo utente | — | Ticket creato |
| 4 | Crea `COLL-F1-65-20260726-02` con Richiedente = il secondo utente | — | Ticket creato |
| 5 | Applica il filtro "Organizzazione del richiedente" = la prima organizzazione | — | Il filtro si applica |
| 6 | Verifica che `COLL-F1-65-20260726-01` compaia | — | Presente |
| 7 | Verifica che `COLL-F1-65-20260726-02` NON compaia | — | Assente |

**Risultato finale atteso**
Solo il ticket il cui richiedente è associato all'organizzazione selezionata compare nell'elenco filtrato.

**Controlli negativi**
Cambiare il filtro sulla seconda organizzazione: ora deve comparire solo `COLL-F1-65-20260726-02`.

**Evidenze da acquisire**
- Output del comando tecnico di associazione (screenshot/log della sessione tinker).
- Screenshot del filtro applicato con l'unico ticket atteso visibile.

**Criterio di superamento**

PASS: il filtro restituisce esattamente il ticket associato all'organizzazione selezionata.
FAIL: compaiono entrambi i ticket o nessuno, oppure il ticket sbagliato.
BLOCKED: impossibile eseguire lo step tecnico di associazione o accedere alla lista ticket.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Rimuovere le associazioni organizzazione/utente create per il test (`$user->organizations()->detach($org->id)`) ed eliminare i due ticket aggiuntivi, oppure attendere il prossimo deploy (il dataset si rigenera comunque, ma le associazioni create via tinker su un ambiente persistente NON vengono rimosse automaticamente da un semplice redeploy se il volume dati non viene ricreato da zero: verificare con l'amministratore di sistema).

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

### F1-66 — I filtri "senza tag" e "con più di un tag" restituiscono le liste corrette

**Obiettivo**
Verificare che il filtro "Senza tag" mostri solo i ticket privi di qualunque tag e che il filtro "Con più di un tag" mostri solo i ticket con almeno due tag assegnati.

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5.1, filtri `without_tags` (`whereDoesntHave('tags')`) e `multiple_tags` (`has('tags', '>', 1)`).
- Test automatico: `tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php::without tags filter shows only tickets with no tag` (e `multiple tags filter shows only tickets with more than one tag`).
- File/componente applicativo rilevante: `app/Filament/Resources/Tickets/Tables/TicketsTable.php` (filtri "Senza tag"/"Con più di un tag").
- Test correlato: Nessuno.

**Nota importante — gap di dati/UI**
A differenza del vecchio seed fittizio (dove ogni ticket aveva esattamente un tag per costruzione, quindi nessun ticket seed aveva zero tag o più di un tag), il dataset importato dall'ETL reale ha un numero di tag per ticket che dipende dal dump caricato: potrebbe già contenere naturalmente ticket senza tag e ticket con più di un tag, oppure no. **Non esiste alcun campo nel form del ticket (creazione o modifica) per gestire i tag**: l'unico punto in cui la relazione `tags` è raggiungibile da UI è, in sola lettura, questo filtro. Verificare prima con i filtri Filament se le combinazioni necessarie esistono già nel dump; solo se manca la combinazione "2+ tag" ricorrere allo step tecnico sotto (mai una scrittura diretta sulla colonna di stato business-rilevante, ma qui si tratta di una relazione many-to-many pivot senza un'Action di dominio dedicata, quindi `tags()->attach()` in tinker è il mezzo naturale, coerente con l'assenza di un'Action equivalente in questa fase). **DA VERIFICARE CON IL PRODUCT OWNER** se è prevista, in una fase successiva, una gestione dei tag dal form ticket.

**Modalità di esecuzione**
MISTO

**Priorità**
Media

**Ruolo del tester**
Admin (per la parte UI) + Amministratore di sistema (per l'eventuale step tecnico "più di un tag")

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com.
- Accesso tecnico a `php artisan tinker` (necessario solo se il dump caricato non contiene già un ticket con 2+ tag).

**Dati di test**
- Nuovo ticket `COLL-F1-66-20260726-01` (creato senza toccare i tag: 0 tag per costruzione) — usato come esempio "senza tag", a meno che uno già presente nel dump non sia più comodo (filtro "Senza tag" stesso).
- Un ticket reale con almeno un tag già assegnato (individuato col filtro tag della tabella); se nel dump esiste già un ticket con 2+ tag usarlo direttamente, altrimenti aggiungergli tecnicamente un secondo tag per renderlo idoneo al filtro "Con più di un tag".

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato; il numero di tag per ticket dipende dal dump caricato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come admin e crea un nuovo ticket senza toccare alcun campo tag | Titolo `COLL-F1-66-20260726-01` | Ticket creato, 0 tag |
| 2 | Applica il filtro "Senza tag" | — | Il filtro si applica |
| 3 | Verifica che `COLL-F1-66-20260726-01` compaia | — | Presente |
| 4 | Verifica che il ticket con 1 tag individuato sopra NON compaia | — | Assente |
| 5 (tecnico, solo se necessario) | Da `php artisan tinker`, recupera un tag esistente e il ticket individuato sopra, poi esegui `$ticket->tags()->attach($tag->id)` | Vedi comando sopra | Il ticket ha ora 2 tag |
| 6 | Rimuovi il filtro "Senza tag" e applica "Con più di un tag" | — | Il filtro si applica |
| 7 | Verifica che il ticket con 2+ tag (individuato o costruito sopra) compaia | — | Presente |
| 8 | Verifica che `COLL-F1-66-20260726-01` (0 tag) NON compaia | — | Assente |

**Risultato finale atteso**
"Senza tag" restituisce solo ticket senza alcun tag; "Con più di un tag" restituisce solo ticket con almeno due tag.

**Controlli negativi**
Un ticket con esattamente 1 tag non deve comparire in nessuno dei due filtri.

**Evidenze da acquisire**
- Screenshot di entrambi i filtri applicati con i rispettivi risultati.
- Output del comando tecnico di aggiunta tag, se eseguito.

**Criterio di superamento**

PASS: entrambi i filtri restituiscono esattamente gli insiemi attesi.
FAIL: uno dei due filtri include/esclude un ticket in modo errato.
BLOCKED: impossibile creare il ticket o eseguire lo step tecnico.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Se eseguito lo step tecnico, rimuovere il tag aggiunto (`$ticket->tags()->detach($tag->id)`); eliminare `COLL-F1-66-20260726-01`, oppure attendere il prossimo deploy.

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

### F1-67 — Il filtro periodo restringe la lista per intervallo di data di creazione o di completamento

**Obiettivo**
Verificare che il filtro "Periodo" restringa correttamente l'elenco ticket sia scegliendo "Data di creazione" sia scegliendo "Data di completamento" come campo di riferimento, con un intervallo Dal/Al.

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5.1, filtro `period` (campo dinamico `created_at`/`done_at`).
- Test automatico: `tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php::period filter narrows the list by creation date range` (variante correlata nello stesso file: `period filter narrows the list by completion date range`).
- File/componente applicativo rilevante: `app/Filament/Resources/Tickets/Tables/TicketsTable.php` (filtro "Periodo").
- Test correlato: Nessuno.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com.
- Il dataset importato dall'ETL reale è stato caricato in una data precedente a quella odierna del
  collaudo (i ticket già presenti hanno quindi `created_at` antecedente a oggi).
- Almeno due ticket reali in stato "Completato" con date di completamento diverse: filtrare l'elenco
  Ticket per Stato = "Completato" e leggere la colonna "Giorni in stato" (che per un ticket concluso
  riflette i giorni trascorsi da `done_at`) su due o più risultati, scegliendone due con valori
  diversi. Se il dump caricato non offre almeno due valori distinti, va documentata questa limitazione
  e la variante "Data di completamento" può essere eseguita su un solo giorno di riferimento (Dal=Al
  = quella data), verificando solo l'inclusione, non l'esclusione per data diversa.

**Dati di test**
- Variante "Data di creazione": nuovo ticket `COLL-F1-67-20260726-01`, creato oggi.
- Variante "Data di completamento": i due ticket "Completato" individuati sopra (di seguito T1 e T2,
  con T1 il valore "Giorni in stato" più alto, cioè completato prima).

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come admin e crea un nuovo ticket | Titolo `COLL-F1-67-20260726-01` | Ticket creato oggi |
| 2 | Applica il filtro "Periodo": Campo = "Data di creazione", Dal = oggi, Al = oggi | — | Il filtro si applica |
| 3 | Verifica che `COLL-F1-67-20260726-01` compaia | — | Presente |
| 4 | Verifica che un ticket creato in una data precedente (es. uno qualunque del dataset importato) NON compaia | — | Assente |
| 5 | Rimuovi il filtro e apri il dettaglio di T1 e T2 per leggere il numero di "Giorni in stato" di ciascuno, e ricava le rispettive date di completamento (oggi meno quel numero di giorni) | — | Due date di completamento annotate |
| 6 | Applica il filtro "Periodo": Campo = "Data di completamento", Dal/Al = intervallo di un solo giorno attorno alla data di T1 | Date ricavate al passo 5 | Il filtro si applica |
| 7 | Verifica che T1 compaia e che T2 (data di completamento diversa) NON compaia | — | Solo T1 presente |

**Risultato finale atteso**
Il filtro "Periodo" restringe correttamente l'elenco sia sul campo "Data di creazione" sia su "Data di completamento", in entrambi i casi includendo solo i ticket nell'intervallo scelto.

**Controlli negativi**
Allargare l'intervallo del passo 6 per includere entrambe le date: sia T1 sia T2 devono comparire.

**Evidenze da acquisire**
- Screenshot del filtro con Campo = "Data di creazione" e risultato.
- Screenshot del filtro con Campo = "Data di completamento" e risultato.

**Criterio di superamento**

PASS: entrambe le varianti del filtro restituiscono l'insieme atteso.
FAIL: una delle due varianti include/esclude un ticket in modo errato.
BLOCKED: impossibile creare il ticket o accedere alla lista ticket.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Eliminare `COLL-F1-67-20260726-01`, oppure attendere il prossimo deploy.

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

### F1-68 — I filtri si combinano correttamente con una vista/tab già attiva, senza sostituirla

**Obiettivo**
Verificare che applicare un filtro (es. "Assegnatario") mentre una tab (es. "In lavorazione") è già attiva restituisca l'intersezione delle due condizioni, e non sostituisca/annulli il filtro della tab.

**Riferimenti**
- Requisito/regola di dominio: PRD §8.5.1 ("i filtri compongono con i tab senza alcun collegamento esplicito").
- Test automatico: `tests/Feature/Filament/Ticketing/TicketsTableFiltersTest.php::filters compose with an existing view tab instead of replacing it`.
- File/componente applicativo rilevante: `app/Filament/Resources/Tickets/Pages/ListTickets.php` (tab), `app/Filament/Resources/Tickets/Tables/TicketsTable.php` (filtro "Assegnatario").
- Test correlato: F1-62 (In lavorazione), F1-64 (filtro stato).

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Alta

**Ruolo del tester**
Admin

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com.
- Dataset importato dall'ETL reale presente e non alterato: individuare i ticket idonei con i filtri Filament (Stato/Assegnatario) invece di assumere titolo/indice fissi; se manca una combinazione, assegnarla con l'azione di assegnazione/transizione già testata, mai scrivendo le colonne a mano.

**Dati di test**
- Ticket incluso atteso: un ticket reale in stato "In lavorazione" con assegnatario "Lorena Sava" — soddisfa sia la tab sia il filtro.
- Ticket escluso atteso (stato sbagliato, stesso assegnatario): un ticket reale in stato "Nuovo" con lo stesso assegnatario "Lorena Sava" — soddisfa il filtro ma non la tab.
- Ticket escluso atteso (né stato né assegnatario): un ticket reale in stato "Backlog" assegnato a "Manager Collaudo".

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come admin e apri la tab "In lavorazione" | info@montagnaservizi.com | Elenco filtrato per stato "In lavorazione" |
| 2 | Applica il filtro "Assegnatario" = "Lorena Sava", restando sulla tab | — | Il filtro si applica in aggiunta alla tab |
| 3 | Verifica che il ticket incluso individuato sopra compaia | — | Presente |
| 4 | Verifica che il ticket escluso individuato sopra (Nuovo, stesso assegnatario) NON compaia | — | Assente: la tab continua a restringere per stato anche col filtro attivo |
| 5 | Verifica che il ticket escluso individuato sopra (Backlog, altro assegnatario) NON compaia | — | Assente |

**Risultato finale atteso**
Con tab "In lavorazione" e filtro "Assegnatario" entrambi attivi, compare solo il ticket che soddisfa contemporaneamente stato "In lavorazione" e assegnatario "Lorena Sava".

**Controlli negativi**
Rimuovere il filtro "Assegnatario" lasciando solo la tab: il ticket assegnato al Manager in "In lavorazione" (se presente) deve ricomparire, a conferma che il filtro, non la tab, stava escludendolo.

**Evidenze da acquisire**
- Screenshot con tab + filtro attivi e il solo ticket atteso visibile.

**Criterio di superamento**

PASS: solo il ticket che soddisfa entrambe le condizioni compare.
FAIL: la tab viene ignorata/sostituita dal filtro, o viceversa.
BLOCKED: impossibile accedere alla lista ticket.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

## Vista di lavoro e landing per ruolo

### F1-69 — La vista di lavoro raggruppa in colonne i ticket visibili per stato, rispettando la visibilità per ruolo

**Obiettivo**
Verificare che la "Vista di lavoro" (WorkBoard) raggruppi i ticket in una colonna per ciascuno dei 12 stati (comprese le colonne vuote), e che un utente non veda mai, in quelle colonne, ticket al di fuori del proprio ambito di visibilità (`Ticket::scopeVisibleTo`).

**Riferimenti**
- Requisito/regola di dominio: PRD §8.6/§6.7.2, `WorkBoard::columns()`.
- Test automatico: `tests/Feature/Filament/Pages/WorkBoardTest.php::columns group visible tickets by status and hide tickets outside the visibility scope`.
- File/componente applicativo rilevante: `app/Filament/Pages/WorkBoard.php` (`columns()`), `resources/views/filament/pages/work-board.blade.php`, `app/Domain/Ticketing/Models/Ticket.php` (`scopeVisibleTo`).
- Test correlato: F1-70 (selettore assegnatario).

**Nota importante — limite del dataset/ruoli UAT reali**
Il test automatico costruisce un utente sintetico con **solo** il permesso `ticket.view.assigned` (mai `ticket.view.any`) per dimostrare l'occultamento dei ticket fuori scope. Nella matrice reale di `RolePermissionSeeder` (§9.4), però, sia "developer" sia "manager" (e ovviamente "admin") hanno **sempre anche** `ticket.view.any`: nessuno dei tre utenti staff seedati (Sviluppatore/Manager/Montagna Servizi) è quindi mai visibilità-ristretto sulla Vista di lavoro, e con l'interfaccia Filament attuale (nessuna schermata per modificare i permessi di un ruolo — `RoleResource` è di sola lettura) non è possibile costruire, solo da UI, uno staff realmente ristretto a `ticket.view.assigned`. La sola metà "raggruppamento in colonne per stato" dell'AC è quindi pienamente verificabile da UI con gli utenti reali; la metà "occultamento fuori scope" resta verificata in modo affidabile solo dal test automatico citato. **DA VERIFICARE CON IL PRODUCT OWNER** se è prevista, in una fase successiva, la possibilità di creare/modificare ruoli con permessi ridotti dal pannello, utile per un collaudo dal vivo di questo aspetto.

**Modalità di esecuzione**
MISTO

**Priorità**
Alta

**Ruolo del tester**
Developer (per la parte UI) — la parte "occultamento fuori scope" richiede l'esecuzione/lettura del test automatico citato, non riproducibile da un tester funzionale con i ruoli UAT reali

**Prerequisiti**
- Accesso a `/admin` come lorena.sava@montagnaservizi.com.
- Dataset importato dall'ETL reale presente e non alterato.

**Dati di test**
- Osservare quali colonne di stato hanno pochi ticket (buon esempio di colonna con pochi elementi,
  es. tipicamente "Rifiutato") e quali stati risultano vuoti nel dump caricato: uno stato vuoto è
  utile come riferimento incrociato per l'assegnatario selezionato nel test successivo (F1-70). Il
  dataset reale non garantisce quali stati siano vuoti o poco popolati: verificarlo osservando i
  contatori delle colonne al passo 2 invece di assumerlo a priori.

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come Lorena Sava | lorena.sava@montagnaservizi.com | Login riuscito, redirect automatico alla "Vista di lavoro" (vedi anche F1-71) |
| 2 | Osserva la vista di lavoro | — | Sono visibili colonne per ogni stato (Nuovo, Backlog, Assegnato, Da fare, In lavorazione, In test, Testato, Rilasciato, Completato, Problema, In attesa, Rifiutato), ciascuna con un contatore |
| 3 | Verifica che ogni ticket presente nel sistema compaia in una sola colonna, coerente con il proprio stato (es. i ticket "In lavorazione" sono tutti e soli nella colonna "In lavorazione") | — | Nessun ticket duplicato o mancante rispetto al totale visibile nella lista Ticket |
| 4 | (riferimento, non bloccante) Consultare l'esito del test automatico citato per la parte "occultamento fuori scope" | `php artisan test --filter=WorkBoardTest` | Il test PASSA nell'ultima esecuzione in pipeline |

**Risultato finale atteso**
La vista di lavoro mostra una colonna per ciascuno dei 12 stati (anche se vuota) e ogni ticket compare nella colonna del proprio stato corrente.

**Controlli negativi**
Nessuno applicabile da UI con i ruoli reali (vedi nota sopra).

**Evidenze da acquisire**
- Screenshot della vista di lavoro con le colonne visibili.
- Riferimento all'esito dell'ultima esecuzione CI del test automatico citato.

**Criterio di superamento**

PASS: tutte le colonne di stato sono presenti e popolate coerentemente; il test automatico citato risulta verde nell'ultima esecuzione.
FAIL: colonne mancanti, ticket nella colonna sbagliata, o il test automatico citato risulta rosso.
BLOCKED: impossibile accedere alla vista di lavoro.
NOT APPLICABLE: la sotto-verifica "occultamento fuori scope" non è eseguibile da UI con i ruoli UAT reali — annotare NOT APPLICABLE solo per quella parte, non per l'intero test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-70 — Il selettore di assegnatario permette di vedere la vista di lavoro di un collega

**Obiettivo**
Verificare che il selettore "Board di" nella Vista di lavoro, impostato su un collega specifico, restringa tutte le colonne ai soli ticket assegnati a quel collega.

**Riferimenti**
- Requisito/regola di dominio: PRD §8.6, `WorkBoard::$assigneeId`/`columns()`.
- Test automatico: `tests/Feature/Filament/Pages/WorkBoardTest.php::the assignee selector narrows the board to a single colleague`.
- File/componente applicativo rilevante: `app/Filament/Pages/WorkBoard.php`, `resources/views/filament/pages/work-board.blade.php` (select `wire:model.live="assigneeId"`).
- Test correlato: F1-69.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Media

**Ruolo del tester**
Manager

**Prerequisiti**
- Accesso a `/admin` come manager@oc.test.
- Dataset importato dall'ETL reale presente e non alterato: individuare i ticket idonei con i filtri Filament (Assegnatario) invece di assumere titolo/indice fissi; se manca la combinazione, assegnarla con l'azione di assegnazione già testata, mai scrivendo la colonna a mano.

**Dati di test**
- Ticket incluso atteso quando si seleziona "Lorena Sava": un ticket reale assegnato a "Lorena Sava", in stato "In lavorazione".
- Ticket escluso atteso: un ticket reale assegnato a "Manager Collaudo".

**Stato iniziale**
Dataset importato dall'ETL reale presente e non alterato; il selettore "Board di" è su "Tutti".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come Manager Collaudo e apri la Vista di lavoro | manager@oc.test | Board con tutti i ticket visibili (Manager ha `ticket.view.any`) |
| 2 | Nel selettore "Board di" scegli "Lorena Sava" | Select "Board di" = Lorena Sava | Il board si aggiorna |
| 3 | Verifica che il ticket incluso individuato sopra compaia nella colonna "In lavorazione" | — | Presente |
| 4 | Verifica che il ticket escluso individuato sopra (assegnato al Manager) NON compaia in alcuna colonna | — | Assente |
| 5 | Riporta il selettore su "Tutti" | — | Il ticket del Manager ricompare |

**Risultato finale atteso**
Con il selettore impostato su un collega specifico, ogni colonna mostra solo i ticket assegnati a quel collega.

**Controlli negativi**
Nessuno applicabile.

**Evidenze da acquisire**
- Screenshot del board con "Board di" = Lorena Sava.

**Criterio di superamento**

PASS: comportamento come descritto.
FAIL: compaiono ticket di un assegnatario diverso da quello selezionato.
BLOCKED: impossibile accedere alla vista di lavoro.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy.

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

### F1-71 — Staff atterra sulla vista di lavoro dopo il login; un cliente resta sulla propria dashboard

**Obiettivo**
Verificare in entrambi i versi che, subito dopo il login su `/admin`, admin/manager/developer vengano automaticamente reindirizzati alla Vista di lavoro, mentre un cliente resti sulla Dashboard di base (nessun redirect).

**Riferimenti**
- Requisito/regola di dominio: PRD §6.7.2/§8.6, `App\Filament\Pages\Dashboard::mount()`.
- Test automatico: `tests/Feature/Filament/Pages/DashboardTest.php::staff (admin/manager/developer) landing on the dashboard is redirected to the work board` (e `a customer landing on the dashboard is not redirected`).
- File/componente applicativo rilevante: `app/Filament/Pages/Dashboard.php`, `app/Filament/Resources/Tickets/Support/TicketFieldAccess.php`.
- Test correlato: F1-69.

**Modalità di esecuzione**
MANUALE UI

**Priorità**
Critica

**Ruolo del tester**
Admin, Manager, Developer, Customer (login separati)

**Prerequisiti**
- Credenziali dei 4 utenti coinvolti (le 5 identità di riferimento del punto 9 di 00-istruzioni-generali.md, password "uat").

**Dati di test**
Nessuno specifico oltre alle credenziali di collaudo.

**Stato iniziale**
Nessuna sessione attiva sul pannello.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Accedi a `/admin` come Montagna Servizi | info@montagnaservizi.com / uat | Subito dopo il login la pagina mostrata è la "Vista di lavoro" (titolo pagina "Vista di lavoro", colonne per stato visibili), non la Dashboard di base |
| 2 | Disconnetti e accedi come Manager Collaudo | manager@oc.test / uat | Stesso risultato: redirect automatico alla "Vista di lavoro" |
| 3 | Disconnetti e accedi come Lorena Sava | lorena.sava@montagnaservizi.com / uat | Stesso risultato: redirect automatico alla "Vista di lavoro" |
| 4 | Disconnetti e accedi come Sentiero Italia CAI - SICAI | infosentieroitalia@cai.it / uat | La pagina mostrata resta la Dashboard di base di Filament (widget standard), NESSUN redirect verso la Vista di lavoro; il menu di navigazione non mostra nemmeno la voce "Vista di lavoro" |

**Risultato finale atteso**
I tre profili staff atterrano sempre sulla Vista di lavoro; il cliente atterra sempre sulla Dashboard di base, senza redirect.

**Controlli negativi**
Da cliente, tentare di navigare manualmente all'URL della Vista di lavoro (se noto/indovinato): l'accesso deve essere negato (403), coerente con `WorkBoard::canAccess()`.

**Evidenze da acquisire**
- Screenshot post-login per ciascuno dei 4 utenti.

**Criterio di superamento**

PASS: tutti e 4 gli esiti (3 redirect + 1 nessun redirect) sono coerenti con quanto descritto.
FAIL: uno qualunque dei 4 esiti è invertito.
BLOCKED: impossibile accedere con una delle 4 utenze.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Disconnettersi al termine del test.

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

## Verifica end-to-end di Fase 1

### F1-72 — Le ore lavorate calcolate end-to-end su un intero ciclo di vita del ticket sono coerenti con i cambi di stato reali

**Obiettivo**
Verificare che, percorrendo l'intero ciclo di vita principale di un ticket (Nuovo → Assegnato → Da fare → In lavorazione → In test → Testato → Rilasciato → Completato) con azioni reali dell'interfaccia, lo storico registri esattamente una creazione più un cambio di stato per ogni transizione (8 eventi totali su questo percorso) e il valore "Ore lavorate" risulti coerente con l'unico intervallo "in lavorazione" realmente trascorso (da quando il ticket entra in "In lavorazione" a quando passa a "In test"), non con l'intero tempo di vita del ticket. A differenza di F1-01 (che verifica la sequenza di transizioni in sé), questo test si concentra sulla coerenza del calcolo delle ore lavorate rispetto ai log realmente scritti.

**Riferimenti**
- Requisito/regola di dominio: PRD §14 (verifica end-to-end di Fase 1), §6.2.2 (calcolo ore lavorate).
- Test automatico: `tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php::the main path takes a ticket from new to done through every state with coherent worked minutes` (con tempo simulato: asserisce 8 log totali di cui 7 `status_changed`, `worked_minutes` = 120 su un unico `ticket_work_log`).
- File/componente applicativo rilevante: `app/Domain/Ticketing/Actions/ChangeTicketStatus.php`, `app/Domain/TimeTracking/WorkedTimeCalculator.php`, `app/Domain/TimeTracking/Actions/RecalculateWorkedTime.php`, `app/Filament/Resources/Tickets/Support/TicketTransitionActions.php`.
- Test correlato: F1-01 (percorso principale completo, stessa sequenza di transizioni, focus sullo storico), F1-73, F1-74.

**Modalità di esecuzione**
MISTO (percorso interamente eseguibile da UI; la verifica numerica esatta delle "ore lavorate" richiede una nota tecnica, vedi sotto)

**Priorità**
Critica

**Ruolo del tester**
Admin (creazione/assegnazione) + Developer (lavorazione) + Manager (collaudo interno, come tester)

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com, lorena.sava@montagnaservizi.com, manager@oc.test.
- Il worker della coda (`queue:work`) è attivo sull'ambiente UAT, altrimenti il ricalcolo automatico delle "Ore lavorate" (listener asincrono di `TicketStatusChanged`, con debounce) non avviene finché qualcuno non lo forza — in tal caso un tester tecnico può eseguire `php artisan timetracking:recalculate --ticket=<id>` per forzare il ricalcolo.
- Eseguire l'intero percorso in un solo giorno feriale (lun-ven), per evitare lo scarto weekend nel calcolo delle ore lavorate.

**Dati di test**
- Nuovo ticket con titolo `COLL-F1-72-20260726-01`.
- Assegnatario: "Lorena Sava". Tester (collaudo interno): "Manager Collaudo".

**Stato iniziale**
Nessun ticket `COLL-F1-72-20260726-01` presente.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Come admin, crea il ticket | Titolo `COLL-F1-72-20260726-01` | Stato "Nuovo"; Storico: 1 evento di creazione |
| 2 | Come admin, esegui la transizione verso "Assegnato" | Assegnatario = "Lorena Sava" | Stato "Assegnato"; Storico: +1 evento (`Nuovo → Assegnato`) |
| 3 | Come admin, esegui la transizione verso "Da fare" | — | Stato "Da fare"; Storico: +1 evento |
| 4 | Come Lorena Sava, esegui la transizione verso "In lavorazione". Annota l'ora esatta del clic | — | Stato "In lavorazione"; Storico: +1 evento |
| 5 | Attendi almeno alcuni minuti (per avere un intervallo misurabile), poi come Lorena Sava esegui la transizione verso "In test", assegnando Tester = "Manager Collaudo". Annota l'ora esatta del clic | Tester = "Manager Collaudo" | Stato "In test"; Storico: +1 evento |
| 6 | Come Manager Collaudo, esegui la transizione verso "Testato" | — | Stato "Testato"; Storico: +1 evento |
| 7 | Come Lorena Sava (o admin), esegui la transizione verso "Rilasciato" | — | Stato "Rilasciato"; Storico: +1 evento; data di rilascio valorizzata |
| 8 | Esegui la transizione verso "Completato" | — | Stato "Completato"; Storico: +1 evento; data di completamento valorizzata |
| 9 | Apri il ticket in modifica e osserva il campo "Ore lavorate" (sezione "Tempo") | — | Il valore mostrato è coerente con la differenza fra gli orari annotati ai passi 4 e 5, arrotondata per difetto alla granularità configurata (nessun conteggio dell'intero tempo di vita del ticket) |
| 10 | Conta le righe nella sezione "Storico" del ticket | — | Esattamente 8 righe: 1 "Creato" + 7 "Cambio di stato" |

**Risultato finale atteso**
Il ticket raggiunge "Completato" con 8 eventi nello storico (1 creazione + 7 cambi di stato) e un valore "Ore lavorate" coerente con l'unico intervallo realmente trascorso fra "In lavorazione" e "In test" — non con l'intera durata del percorso. Riprodurre l'esatto valore "120 minuti" del test automatico non è significativo in una sessione manuale dal vivo (lì il tempo è simulato con `travelTo()`): il criterio di superamento è la coerenza, non la corrispondenza a quel numero.

**Controlli negativi**
Verificare che nello storico non compaia alcuna retrocessione intermedia (nessun evento verso uno stato già superato).

**Evidenze da acquisire**
- Screenshot dello storico completo (8 righe).
- Screenshot del campo "Ore lavorate" con annotati gli orari dei passi 4 e 5 a fianco per il calcolo di coerenza.

**Criterio di superamento**

PASS: 8 eventi nello storico nell'ordine atteso, "Ore lavorate" coerente con l'intervallo In lavorazione→In test annotato manualmente.
FAIL: numero di eventi errato, stato finale diverso da "Completato", oppure "Ore lavorate" palesemente incoerente (es. conta l'intera durata del ticket invece del solo intervallo).
BLOCKED: una transizione richiesta non è disponibile/eseguibile per l'utente atteso.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy (il ticket aggiuntivo non altera i ticket importati dall'ETL).

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

### F1-73 — Manomettere il contesto di una transizione con auto-assegnazione non permette di assegnare il ticket a un altro utente

**Obiettivo**
Verificare, su due livelli di difesa indipendenti, che un developer che esegue la transizione di auto-assegnazione (Nuovo → Assegnato) non possa mai far assegnare il ticket a un utente diverso da sé stesso: (A) livello UI/Filament — il modale non espone affatto un campo "Assegnatario" quando l'attore si sta auto-assegnando, quindi un valore diverso iniettato lato client viene ignorato; (B) livello macchina a stati — anche bypassando del tutto Filament e chiamando l'Action direttamente con un `context['assignee_id']` impersonato, la transizione viene rifiutata con un errore di validazione e non scrive nulla.

**Riferimenti**
- Requisito/regola di dominio: PRD §9.5, guard `AutoAssigningDeveloper` della macchina a stati (US-101).
- Test automatico: `tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php::the state machine rejects an impersonated self-assignment context regardless of how it reaches ChangeTicketStatus` (livello B); test correlato nello stesso file, `tampering with the hidden assignee_id of a self-assigning transition action still self-assigns, never the injected user` (livello A, verifica che il campo non esista nello schema del modale).
- File/componente applicativo rilevante: `app/Domain/Ticketing/StateMachine/TicketStateMachine.php`, `app/Filament/Resources/Tickets/Support/TicketTransitionActions.php` (azione `transition_assigned`).
- Test correlato: F1-72, F1-74.

**Modalità di esecuzione**
MISTO — percorso UI onesto (livello A, osservabile da un tester funzionale) + tentativo tecnico di bypass diretto dell'Action (livello B, richiede un tester con accesso a `php artisan tinker`/esecuzione del test automatico)

**Priorità**
Critica

**Ruolo del tester**
Developer (per il percorso UI onesto) + Amministratore di sistema (per il tentativo tecnico di bypass)

**Prerequisiti**
- Accesso a `/admin` come lorena.sava@montagnaservizi.com.
- Accesso tecnico a `php artisan tinker` (o alla suite di test) per la parte B.
- Esiste un secondo utente qualunque da usare come "altro utente" nel tentativo (es. "Manager Collaudo").

**Dati di test**
- Nuovo ticket `COLL-F1-73-20260726-01`, stato "Nuovo", nessun assegnatario.

**Stato iniziale**
Il ticket è "Nuovo", `assignee_id` nullo.

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 | Come admin, crea il ticket | Titolo `COLL-F1-73-20260726-01` | Stato "Nuovo" |
| 2 (percorso UI onesto) | Accedi come Lorena Sava, apri il ticket e avvia la transizione verso "Assegnato" | — | Il modale si apre |
| 3 | Osserva i campi del modale | — | NON è presente alcun campo "Assegnatario" (il developer non ha il permesso di transizione libera: l'auto-assegnazione è silenziosa) — solo il checkbox "Applica anche ai ticket figli" |
| 4 | Conferma la transizione | — | Il ticket viene assegnato a "Lorena Sava" stesso (l'utente che ha eseguito l'azione), mai a un altro utente, anche se il modale non offriva alcuna scelta |
| 5 (tentativo tecnico di bypass) | Su un secondo ticket "Nuovo" di prova, da `php artisan tinker` (o eseguendo il test automatico citato), invoca direttamente `ChangeTicketStatus::run($ticket, TicketStatus::Assigned, $developer, ['assignee_id' => $otherUser->id])`, impersonando un `assignee_id` diverso dal developer autenticato | `$otherUser` = un altro utente qualunque, es. Manager Collaudo | La chiamata lancia `Illuminate\Validation\ValidationException` (errore di validazione localizzato, mai un'eccezione generica) |
| 6 | Verifica lo stato del ticket dopo il tentativo | — | Il ticket resta in stato "Nuovo", `assignee_id` ancora nullo, e non è stato scritto alcun nuovo evento nello storico |

**Risultato finale atteso**
Al passo 4 il ticket risulta assegnato esclusivamente all'utente che ha eseguito l'azione (mai a un altro, anche tentando di manomettere la richiesta lato client). Al passo 5 il tentativo di impersonare un `assignee_id` diverso, anche bypassando del tutto l'interfaccia, viene respinto con un errore di validazione e non altera il ticket.

**Controlli negativi**
Ripetere il passo 2-4 da Admin o Manager (che hanno il permesso di transizione libera): in quel caso il modale DEVE mostrare il campo "Assegnatario" con scelta libera (comportamento atteso opposto, a conferma che la restrizione riguarda solo l'auto-assegnazione silenziosa del developer).

**Evidenze da acquisire**
- Screenshot del modale al passo 3 (assenza del campo Assegnatario).
- Output/eccezione del tentativo tecnico al passo 5.

**Criterio di superamento**

PASS: entrambi i livelli di difesa si comportano come descritto.
FAIL: il campo Assegnatario compare per il developer, oppure il ticket risulta assegnato a un utente diverso da chi ha eseguito l'azione, oppure il tentativo tecnico non viene respinto.
BLOCKED: impossibile eseguire il tentativo tecnico (nessun accesso a tinker/test).
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: il dataset si rigenera al prossimo deploy (i ticket aggiuntivi non alterano i ticket importati dall'ETL).

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

### F1-74 — Una transizione vietata tentata direttamente contro l'azione di cambio stato viene rifiutata e non scrive nulla

**Obiettivo**
Verificare, su due livelli, che una transizione non presente nella tabella della macchina a stati (es. da "Completato" verso "Assegnato") non sia mai disponibile dall'interfaccia (percorso UI onesto) e che, tentandola direttamente contro l'Action `ChangeTicketStatus` bypassando del tutto Filament (es. da un controller custom o da codice manomesso), venga respinta con un errore di validazione localizzato senza scrivere alcun log.

**Riferimenti**
- Requisito/regola di dominio: PRD §6.1.3 (tabella delle transizioni: "Completato" non ha alcuna riga verso "Assegnato").
- Test automatico: `tests/Feature/Domain/Ticketing/TicketLifecycleEndToEndTest.php::a forbidden transition attempted directly against the ChangeTicketStatus action is rejected and writes nothing`.
- File/componente applicativo rilevante: `app/Domain/Ticketing/StateMachine/TicketStateMachine.php`, `app/Domain/Ticketing/Actions/ChangeTicketStatus.php`, `app/Filament/Resources/Tickets/Support/TicketTransitionActions.php` (`build()`, mostra solo i bottoni delle transizioni presenti in tabella).
- Test correlato: F1-72, F1-73.

**Modalità di esecuzione**
MISTO — percorso UI onesto (verifica dell'assenza del bottone, eseguibile da un tester funzionale) + tentativo tecnico diretto contro l'Action (richiede un tester con accesso a `php artisan tinker`/alla suite di test)

**Priorità**
Critica

**Ruolo del tester**
Admin (per il percorso UI onesto) + Amministratore di sistema (per il tentativo tecnico)

**Prerequisiti**
- Accesso a `/admin` come info@montagnaservizi.com.
- Accesso tecnico a `php artisan tinker` (o alla suite di test) per la parte tecnica.

**Dati di test**
- Un ticket reale in stato "Completato" con storico non vuoto (un ticket importato dall'ETL reale ha già uno storico genuino di `ticket_logs` proveniente da v1: individuarne uno con il filtro Stato = "Completato" nella lista Ticket, senza bisogno di costruirlo ad hoc).

**Stato iniziale**
Il ticket individuato sopra è in stato "Completato".

**Procedura di esecuzione**

| Passo | Azione del tester | Dato da utilizzare | Risultato atteso |
|------:|-------------------|--------------------|------------------|
| 1 (percorso UI onesto) | Come admin, apri il ticket individuato sopra | — | Il ticket è visualizzato in stato "Completato" |
| 2 | Osserva i bottoni di transizione disponibili nell'header della pagina | — | NESSUN bottone verso "Assegnato" (né verso alcuno stato non ammesso da "Completato": la tabella non ha righe con `from = Completato`) è presente; non è disponibile alcuna transizione, essendo "Completato" uno stato terminale |
| 3 | Annota il numero di righe attualmente presenti nella sezione "Storico" del ticket | — | N righe (storico non vuoto) |
| 4 (tentativo tecnico diretto) | Da `php artisan tinker` (o eseguendo il test automatico citato), invoca direttamente `ChangeTicketStatus::run($ticket, TicketStatus::Assigned, $developer)` sul ticket individuato sopra, bypassando del tutto Filament | `$developer` = un utente qualunque con permesso `ticket.update.assigned` | La chiamata lancia `Illuminate\Validation\ValidationException` |
| 5 | Ricarica il ticket e verifica stato e storico | — | Lo stato resta "Completato"; il numero di righe nello storico è invariato rispetto al passo 3 (nessun nuovo log scritto) |

**Risultato finale atteso**
Nessun bottone per la transizione vietata è mai mostrato in UI; il tentativo tecnico diretto contro l'Action viene respinto con un errore di validazione e lo stato/storico del ticket restano invariati.

**Controlli negativi**
Ripetere il tentativo tecnico con un altro stato di destinazione altrettanto non ammesso da "Completato" (es. verso "In lavorazione"): stesso esito atteso (eccezione, nessuna scrittura).

**Evidenze da acquisire**
- Screenshot dell'header del ticket "Completato" senza bottoni di transizione.
- Output/eccezione del tentativo tecnico al passo 4.
- Conteggio delle righe di Storico prima e dopo (invariato).

**Criterio di superamento**

PASS: nessun bottone disponibile in UI; il tentativo tecnico viene respinto e non altera stato/storico.
FAIL: un bottone verso uno stato non ammesso è disponibile, oppure il tentativo tecnico ha successo o altera comunque il ticket.
BLOCKED: impossibile eseguire il tentativo tecnico.
NOT APPLICABLE: Non previsto per questo test.

**Ripristino**
Nessuno: nessuna modifica persistente prevista (il tentativo, se il sistema si comporta correttamente, non scrive nulla). Il dataset si rigenera comunque al prossimo deploy.

**Campi di consuntivazione**

- Esito: [PASS / FAIL / BLOCKED / NOT APPLICABLE]
- Data:
- Tester:
- Ambiente/versione:
- Risultato effettivo:
- Evidenze:
- ID anomalia:
- Note:
