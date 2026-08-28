# Time tracking

Fonte: PRD-ORCHESTRATOR-V2.md §6.2 (M2 — Log e time tracking), §6.2.2 (calcolo ore lavorate,
decisione Q15), `CLAUDE.md` (sezioni US-109, US-613, bug fix US-219). Implementazione:
`App\Domain\TimeTracking\WorkedTimeCalculator`.

## Il servizio: `WorkedTimeCalculator`

È un servizio **puro**: nessuna query, nessun side-effect. Riceve i `ticket_logs` di un ticket già
caricati e ordinati per `occurred_at` e restituisce una lista di `WorkedTimeSegment`
(`workDate`/`userId`/`minutes`). La configurazione (`workday_start`/`workday_end`/
`granularity_minutes`/`non_status_change_cap_minutes`) è sempre iniettata nel costruttore, mai letta
da `config()` dentro l'algoritmo: `fromConfig()` (usato dall'Action) è l'unico punto che legge
`config/timetracking.php`; i test unitari istanziano il servizio con valori noti.

### Algoritmo

1. Si individuano gli **intervalli** tra un `ticket_log` con `to_status = progress` (apertura) e il
   successivo con `from_status = progress` (chiusura) per lo stesso ticket.
2. Per ciascun intervallo si itera giorno per giorno:
   - si scartano sabato e domenica;
   - si ritaglia (clamp, non discard) alla finestra oraria configurata (`workday_start`/`workday_end`);
   - si arrotonda per difetto alla granularità configurata (`granularity_minutes`).
3. Un intervallo ancora **aperto** (il ticket è tuttora in `progress`, nessun log di chiusura) non
   proietta fino a `now()` indefinitamente: il totale calcolato per l'intero intervallo aperto è
   limitato a `min(minuti, non_status_change_cap_minutes)`, attribuito al giorno più recente toccato.
   Questa è la scelta di unificazione della **decisione Q15**: il v1 aveva due politiche divergenti
   ("totale ticket" con scarto secco fuori finestra e "aggregato giornaliero" con clamp+tetto sui
   soli log senza cambio di stato) — qui una sola politica, quindi i numeri storici confrontati col
   v1 possono divergere per questa scelta (misurato nel report `v1:validate`, vedi `docs/import-v1.md`).
4. L'utente attribuito a un intervallo è lo `user_id` del log che lo ha **aperto** (`to_status = progress`),
   non l'assegnatario corrente del ticket: copre correttamente il caso "più assegnatari nel tempo".

### Configurazione (`config/timetracking.php`)

| Chiave | Env | Default | Significato |
|---|---|---|---|
| `workday_start` | `TIMETRACKING_WORKDAY_START` | 9 | ora di inizio finestra lavorativa |
| `workday_end` | `TIMETRACKING_WORKDAY_END` | 18 | ora di fine finestra lavorativa |
| `granularity_minutes` | `TIMETRACKING_GRANULARITY_MINUTES` | 10 | arrotondamento per difetto |
| `non_status_change_cap_minutes` | `TIMETRACKING_NON_STATUS_CHANGE_CAP_MINUTES` | 30 | tetto sull'intervallo ancora aperto |
| `aggregate_daily.schedule_cron` | `TIMETRACKING_AGGREGATE_SCHEDULE_CRON` | `30 23 * * *` | cadenza di `timetracking:aggregate-daily` |

Qualunque cambio di politica sul tempo è quindi configurazione, non codice (punto di estensione
esplicito, §15.2 del PRD).

## Bug reale corretto (US-219)

Il codice originale di `progressIntervals()` aveva due `if` separati con un `continue` dopo il
primo (`if to_status===Progress { apri; continue; } if from_status===Progress { chiudi; }`). Un log
"progress → progress" (nessun cambio di stato intermedio — es. una riassegnazione dentro `progress`,
**frequente sui dati reali**) ha contemporaneamente `from_status = Progress` e `to_status = Progress`:
il primo `if` scattava per primo e usciva con `continue` prima che il codice potesse riconoscere che
lo stesso log doveva anche chiudere l'intervallo già aperto. Risultato: l'intero intervallo
precedente spariva silenziosamente (caso reale sul dump v1: un intervallo di 27 giorni azzerato da
un singolo log di questo tipo).

Fix: controllare **prima** la chiusura (`from_status === Progress`) e **poi**, senza `continue` fra
le due, l'apertura (`to_status === Progress`) — un log che è sia chiusura sia apertura fa entrambe le
cose alla stessa istante. Pattern generale: in un parser di eventi sequenziali dove un singolo evento
può soddisfare due condizioni contemporaneamente, i due controlli non vanno mai messi in rami
mutuamente esclusivi. Test di regressione in `tests/Unit/Domain/TimeTracking/WorkedTimeCalculatorTest.php`.

## Comandi

| Comando | Uso | Note |
|---|---|---|
| `timetracking:recalculate {--from=} {--to=} {--ticket=}` | ricalcolo massivo manuale | riusa `RecalculateWorkedTime`; `--ticket` ricalcola un solo ticket (ignora `--from`/`--to`); senza `--ticket` i due filtrano su `created_at` |
| `timetracking:aggregate-daily` | schedulato **23:30** (`ENABLE_TIMETRACKING_AGGREGATE`) | consolida `ticket_work_logs` per i ticket con almeno un `ticket_log` nella giornata odierna (query su `TicketLog::occurred_at`, non su `Ticket::updated_at`) — colma il gap v1 ("il job esiste ma non ha alcuna cadenza schedulata") |

Entrambi delegano interamente a `App\Domain\TimeTracking\Actions\RecalculateWorkedTime::run(Ticket $ticket)`
(nessuna seconda implementazione dell'algoritmo): dentro una transazione, ricalcola il totale,
**cancella e ricrea da zero** tutte le righe `ticket_work_logs` del ticket (mai un upsert
differenziale) — è ciò che rende il ricalcolo idempotente a prescindere da chi lo invoca. `--dry-run`
non scrive nulla; log strutturato (`started`/`finished`).

`timetracking:aggregate-daily` produce per costruzione **gli stessi aggregati** di
`timetracking:recalculate` sullo stesso giorno, perché entrambi chiamano la stessa Action — provato
da un test che esegue prima `aggregate-daily`, poi azzera lo stato e richiama `recalculate`
confrontando i risultati.

## Ricalcolo "live"

Un listener su `TicketStatusChanged` fa debounce per ticket con un lock in `Cache` (chiave
`timetracking:recalculate-debounce:{ticket_id}`, TTL breve) verificato **sincronamente** nel
listener PRIMA di accodare `App\Domain\TimeTracking\Jobs\RecalculateTicketWorkedTimeJob`: il listener
stesso non implementa `ShouldQueue` (se lo facesse, il controllo del lock avverrebbe solo quando un
worker preleva il job, troppo tardi per un debounce reale su una raffica di transizioni).

## Tolleranza nel confronto v1/v2

`v1:validate` (§11.7) confronta le ore lavorate v1/v2 per ticket con una tolleranza **combinata**:
percentuale (default 5%) **oppure** soglia assoluta in minuti (default 15) — un ticket è entro
tolleranza se soddisfa almeno una delle due. La sola percentuale non è sensata sui ticket con poche
ore v1 (un ticket da 10 minuti che in v2 arrotonda a 0 per la granularità configurata risulterebbe
"100% di scostamento" pur essendo rumore di arrotondamento). Il report espone anche
`deviation_minutes` per ogni ticket oltre tolleranza, non solo la percentuale.
