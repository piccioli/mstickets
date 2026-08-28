# Differenze di comportamento rispetto al v1 e bug corretti

Fonte: commenti nel codice applicativo (`app/Domain/Mail/*`, richiamati esplicitamente con "problema
N del v1"), `CLAUDE.md` (sezioni ETL, US-219, US-606..US-616), `docs/*.md` esistenti. Il v1 (Nova) non
è più eseguibile in questo repository: ogni comportamento descritto qui è ricostruito dal dump
`v1dumps/orchestrator-v1-backup-20260726.tar.gz` (schema, dati, codice v1 quando incluso nel backup) e
dai commenti che citano un numero di "problema" — non dal PRD (fuori dal repository), quindi questo
elenco è volutamente **quello effettivamente verificabile nel codice**, non un catalogo esaustivo dei
20 problemi complessivi citati in `docs/email.md`.

## Perché questo documento esiste

`docs/architecture.md` (principi A1-A9) e la maggior parte delle decisioni di design di questo
progetto esistono per correggere un problema specifico osservato nel v1. Questo documento raccoglie i
casi **concreti e verificati** (bug reali riprodotti sul dump, o comportamento esplicitamente diverso
per decisione di prodotto), con il riferimento a dove la correzione vive nel codice.

## Sottosistema email — bug di sicurezza e affidabilità corretti (D7: riscritto da zero)

L'intero modulo Mail è una riscrittura completa rispetto al v1 (decisione D7): persistenza su DB
prima di ogni effetto collaterale, threading reale, idempotenza. Non è un porting: i problemi sotto
non sono "patch" su codice v1 portato in v2, sono comportamenti che il nuovo design rende strutturalmente
impossibili.

| # | Problema nel v1 | Comportamento v2 | Dove |
|---|---|---|---|
| 2 | Un fallimento SMTP durante la notifica impediva di marcare l'email come elaborata: duplicati infiniti ad ogni fetch successivo | `InboundEmailApplied` è dispatchato **dopo** che `DB::transaction()` è già tornato (mai dentro), in un try/catch che si limita a loggare; un fallimento di notifica non tocca mai il ticket/messaggio già committati | `app/Domain/Mail/Actions/ApplyInboundEmail.php`, test `tests/Feature/Domain/Mail/Actions/ApplyInboundEmailTest.php` |
| 8 | Rimozione del testo citato/firme con una regex generica non testata sui casi reali | `QuotedTextRemover::stripHtml()` usa `DOMDocument`/`DOMXPath` sui nodi `<blockquote>` (semantica reale di Gmail/Outlook/Apple Mail); `strip()` (plain text) riconosce riga per riga citazioni/firme/blocchi header Outlook, verificando il contesto (le righe successive) prima di decidere | `app/Domain/Mail/Parsers/QuotedTextRemover.php` |
| 9 | Corpo HTML grezzo dell'email scritto sul record senza sanitizzazione (XSS stored) | `EmailBodyParser` passa sempre dal sanitizer allowlist (`symfony/html-sanitizer`, la stessa istanza riusata da `TicketMessageSanitizer`) prima di persistere | `app/Domain/Mail/Parsers/EmailBodyParser.php` |
| 10 | Nessun `foreach` sincrono/notifica quando un cliente apriva un ticket via email o web: lo staff non veniva mai avvisato di un nuovo ticket cliente | E3: notifica al gruppo staff configurato (`staff_notification_group`), Mailable + notifica in-app dedicati | `app/Domain/Mail/Mailables/NewCustomerTicketStaffMail.php`, `app/Domain/Mail/Support/StaffNotificationGroup.php` |
| 11 | `$recipient->role` (proprietà inesistente) nel calcolo dei destinatari di un cambio di stato: sempre `null`, un solo template email indifferenziato per ruolo | E4: destinatari risolti da una tabella dichiarativa `{from, to, roles}` (`NotificationRecipientResolver`), un `NotificationType`/template per ruolo pertinente | `app/Domain/Mail/Mailables/TicketStatusChangedMail.php`, `app/Domain/Mail/Support/NotificationRecipientResolver.php` |
| 12 | Espressione booleana `$a && $b && $c || $d` per decidere i destinatari: precedenza operatori errata, email spurie inviate ad ogni salvataggio del ticket (non solo ai cambi di stato rilevanti) | Lista **ordinata** di righe dichiarative, stesso principio di "prima riga che matcha vince" già usato da `TicketStateMachine::findTransition()` — mai un'espressione booleana composta | `app/Domain/Mail/Support/NotificationRecipientResolver.php` |
| 15 | Allegati salvati su disco con il nome file originale non sanitizzato: path traversal | Il file fisico usa sempre un nome generato (`Str::ulid()` + estensione sniffata); il nome originale sopravvive solo come metadato (`email_attachments.filename`), mai come path | `app/Domain/Mail/Actions/ImportInboundEmailAttachments.php` |

## Ticketing — decisioni di prodotto esplicitamente diverse dal v1

| Decisione | v1 | v2 | Dove |
|---|---|---|---|
| **Q4** | Un cambio di stato manuale su un ticket `new` azzerava `assignee_id` | Nessuna riga di transizione per questo effetto: un cambio di stato manuale su `new` non tocca mai `assignee_id` | `App\Domain\Ticketing\StateMachine\TicketStateMachine::transitions()`, `docs/ticket-lifecycle.md` |
| **Q5** | La propagazione di un cambio di stato ai ticket figli era automatica (hook implicito) | Propagazione esplicita: solo `App\Domain\Ticketing\Actions\ApplyStatusToChildren::run()`, mai richiamata da `ChangeTicketStatus` di default | `app/Domain/Ticketing/Actions/ApplyStatusToChildren.php` |
| **Q14/T7** | — (comportamento non replicato 1:1, il v1 non aveva questa regola in forma dichiarativa) | Quando il richiedente posta un messaggio: `waiting → previous_status`, o `assigned`/`progress → todo` (attesa implicita di risposta cliente) | `App\Domain\Ticketing\Listeners\RestoreTicketStatusOnRequesterMessage`, `docs/ticket-lifecycle.md` |
| **Q15** | Due politiche di calcolo delle ore lavorate divergenti: "totale ticket" (scarto secco fuori finestra oraria) e "aggregato giornaliero" (clamp + tetto sui soli log senza cambio di stato) | Una sola politica unificata (clamp sempre, tetto configurabile su un intervallo ancora aperto) — i numeri storici confrontati col v1 possono divergere per questa scelta, misurato da `v1:validate` | `App\Domain\TimeTracking\WorkedTimeCalculator`, `docs/time-tracking.md` |
| **D14** | Ruolo `editor` (permessi documentazione) | Ruolo eliminato: un utente v1 con `editor` riceve permessi diretti `documentation.create`/`documentation.update`, nessun ruolo applicativo `editor` in v2 | `app/Domain/Identity/Enums/UserRole.php`, `app/Import/Stages/RolesPermissionsStage.php` |
| **A8** (anti-pattern esplicito) | `Documentation::creator()` puntava a una colonna `creator_id` inesistente (ghost column) | `DocumentationPage` non ha alcuna colonna/relazione autore finché non serve davvero — se servirà, va aggiunta con una migrazione esplicita, mai preventivamente | `docs/architecture.md` (A8) |

## Bug reale nell'algoritmo di calcolo ore lavorate (US-219, non un problema del v1, ma scoperto sul dato v1)

Durante la scrittura del test di idempotenza (US-219) è emerso un bug nel primo porting
dell'algoritmo di `WorkedTimeCalculator::progressIntervals()`: due `if` separati con un `continue`
dopo il primo facevano sì che un log "progress → progress" (nessun cambio di stato intermedio — es.
una riassegnazione dentro `progress`, **frequente sui dati reali**) chiudesse l'`if` di apertura e
uscisse prima che il codice riconoscesse che lo stesso log doveva anche chiudere l'intervallo già
aperto. Su un caso reale del dump v1 (ticket #2855) un intervallo di 27 giorni spariva silenziosamente.
Fix: controllare prima la chiusura, poi senza `continue` l'apertura — un log che è sia chiusura sia
apertura fa entrambe le cose alla stessa istante. Vedi `docs/time-tracking.md` per il dettaglio
dell'algoritmo e i test di regressione (`tests/Unit/Domain/TimeTracking/WorkedTimeCalculatorTest.php`).

## Automazioni schedulate: comandi mancanti o mai avviati nel v1 (§10.2, Fase 6)

Il v1 aveva, in alcuni casi, il codice del job ma non la sua schedulazione effettiva — un gap
strutturale diverso da un bug applicativo: la logica esisteva ma non veniva mai eseguita in
produzione.

| Automazione | Nel v1 | In v2 |
|---|---|---|
| T3/T4 (`tickets:progress-to-todo`/`tickets:auto-close-released`) | — | Comandi schedulati nuovi (US-610); la riga `progress → todo` con attore `System` esisteva già dalla macchina a stati di Fase 1 |
| T5/`tickets:archive-scrum` | Nessun comando né colonna di archiviazione nel codice applicativo v1: solo viste Nova "Archived\*" in sola lettura filtrate per `status`, nessuna mutazione reale da riprodurre. Comportamento non recuperabile con certezza dal dump disponibile (`v1dumps/orchestrator-v1-backup-20260726.tar.gz`) | Lettura conservativa adottata (da confermare col committente): archivia (`tickets.archived_at`, mai una cancellazione né un cambio di `status`) i ticket `scrum` `done` da ≥ `threshold_days` giorni di calendario (US-611) |
| T6/`tickets:restore-waiting` | Il job esisteva concettualmente ma nessuna cadenza lo eseguiva | Comando schedulato nuovo (US-612); la riga `waiting → previous_status` con attore `System` esisteva già (serviva anche a T7) |
| `timetracking:aggregate-daily` | "Il job esiste ma non ha alcuna cadenza schedulata" | Comando schedulato nuovo (US-613), 23:30, stessa Action (`RecalculateWorkedTime`) di `timetracking:recalculate` |
| E7 (`tickets:remind-waiting`) | Comando esistente ma mai schedulato (gap del v1) | Schedulato dietro feature flag (US-316) |
| E8 (`mail:send-digest`) | **Dead code con 4 bug noti**, mai realmente funzionante in produzione | Riscritto da zero (US-614) |
| **E11 (`tickets:notify-idle-developers`)** | **Job ritardato di 30 minuti lanciato da un `observer` Eloquent**, attivo solo prima delle 15:30 | **Comando schedulato** (`*/30 9-15 * * *`), mai un job ritardato da observer — correzione esplicita v1→v2 (US-616): un observer che innesca un job ritardato rende il comportamento dipendente da quando/se l'evento che lo innesca si verifica, un comando schedulato gira comunque, indipendentemente da eventi applicativi |

## Autorizzazione — modelli senza alcuna Policy nel v1

`Tag`, `Organization`, `StoryLog` e `ActivityReport` non avevano alcuna Policy nel v1: erano di fatto
aperti a chiunque avesse accesso al pannello Nova. In v2, un modello **senza** Policy nega già tutto
di default (Laravel risolve le Policy per convenzione di naming; l'assenza di una classe risolta nega,
non concede) — verificato empiricamente in US-019. Ogni modello di dominio rilevante ha oggi una
Policy propria in `App\Domain\<Modulo>\Policies\`. Vedi `docs/authorization.md`.

## Meccanismi nuovi in v2, senza equivalente nel v1

- **MFA (autenticazione a due fattori)**: nessun equivalente nel v1. Nuova in v2 (US-606), opzionale
  per ruolo (`config('mfa.required_roles')`, vuoto di default). Vedi `docs/operations.md`.
- **Impersonation** (`stechstudio/filament-impersonate`): nessun equivalente nel v1. Nuova in v2
  (US-607), riservata a `Permission::UserImpersonate`. Vedi `docs/operations.md`.
- **E2/E9/E10** (conferma apertura ticket da web, mittente non riconosciuto, report di attività
  pronto): comunicazioni nuove, senza equivalente diretto nel v1. Vedi `docs/email.md`.
- **Disattivazione utente** (`users.deactivated_at`): un solo choke point
  (`SendOutboundTicketMail::blockedReason()`) sopprime ogni comunicazione verso un utente disattivato,
  meccanismo applicativo nuovo rispetto al v1 (US-608).

## Anonimizzazione dell'ETL — ridefinita in corsa (US-R08)

Il design originale di `--anonymize` (§11.8 del PRD) sostituiva nome/email/corpo messaggi con dati
fittizi deterministici (`App\Import\Anonymization\Anonymizer`, poi rimosso). Il committente ha
richiesto il contrario a metà Fase 2: nome, email, ruoli e contenuti restano **sempre** quelli reali
del dump v1, con o senza il flag. L'unica cosa che `--anonymize` continua a cambiare è la password
(hash fisso noto `uat`, mai l'hash v1 reale). Non è un problema del v1 corretto, ma un cambio di
requisito che ha lasciato un nome (`--anonymize`) semanticamente diverso dal suo comportamento
originale — chiunque legga solo il nome dell'opzione senza `docs/import-v1.md` rischia di assumere
che anonimizzi anche l'identità, cosa che oggi non fa.

## Bug tecnico v2 scoperto e corretto in questa fase (non un problema ereditato dal v1)

La colonna `notifications.data` (tabella standard Laravel per le notifiche in-app Filament) era
`text`: Filament genera però una query con l'operatore JSON Postgres `->>`, incompatibile con quel
tipo. Scoperto in Fase 3, corretto con la migrazione
`2026_08_26_120000_change_notifications_data_column_to_json.php` (colonna ora `json`). Non è un
comportamento ereditato dal v1 (le notifiche in-app Filament sono un meccanismo nuovo di v2), ma è
documentato qui perché il pattern — "un tipo di colonna scelto senza verificare come il framework la
interroga davvero" — è lo stesso errore di categoria di A8 (colonna/schema non verificato contro
l'uso reale). Vedi `docs/data-model.md`.

## Documenti correlati

- `docs/architecture.md` — principi A1-A9 (ognuno esiste per correggere una classe di problema del
  v1).
- `docs/email.md` — catalogo E1-E11 e sottosistema email.
- `docs/ticket-lifecycle.md` — macchina a stati, decisioni Q4/Q5/Q14/Q15.
- `docs/time-tracking.md` — algoritmo di calcolo ore lavorate e bug corretto.
- `docs/import-v1.md` — mappa dei nomi v1→v2, regole di mapping, procedura ETL.
- `docs/authorization.md` — i tre livelli di autorizzazione, MFA, impersonation.
- `docs/operations.md` — dettagli operativi di MFA/impersonation/scheduler.
