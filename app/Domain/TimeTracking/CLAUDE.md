# Dominio TimeTracking

Si carica quando lavori sotto `app/Domain/TimeTracking/*`. Vedi anche `app/Domain/CLAUDE.md` per i pattern
condivisi e `app/Domain/Ticketing/CLAUDE.md` per la macchina a stati che genera i `ticket_logs` da cui questo
calcolo parte.

## Calcolo ore lavorate — WorkedTimeCalculator (US-109, §6.2.2, decisione Q15)

- `App\Domain\TimeTracking\WorkedTimeCalculator` è un service **puro** (nessuna query, nessun side-effect):
  riceve i `ticket_logs` di un ticket già caricati e ordinati per `occurred_at` e restituisce dei
  `WorkedTimeSegment` (`workDate`/`userId`/`minutes`). Configurazione (`workday_start`/`workday_end`/
  `granularity_minutes`/`non_status_change_cap_minutes`) SEMPRE iniettata nel costruttore, mai letta da
  `config()` dentro l'algoritmo: `fromConfig()` è l'unico punto che legge `config/timetracking.php`; i test
  unitari istanziano il service direttamente con valori noti.
- Algoritmo: intervalli tra un log `to_status = 'progress'` e il successivo `from_status = 'progress'`; per
  ciascun intervallo si itera giorno per giorno, si scartano sabato/domenica, si ritaglia (clamp, non
  discard) alla finestra oraria configurata, si arrotonda per difetto alla granularità. Un intervallo ancora
  **aperto** (ticket tuttora in `progress`) non proietta fino a `now()` indefinitamente: il totale è limitato
  al tetto `non_status_change_cap_minutes` (`min($minuti, tetto)`), attribuito al giorno più recente toccato
  — scelta di unificazione della DECISIONE Q15 (il v1 aveva due politiche divergenti): i numeri storici
  confrontati col v1 in Fase 2 potranno divergere per questa scelta.
- L'utente attribuito a un intervallo è lo `user_id` del log che lo ha APERTO (`to_status = 'progress'`), non
  l'assegnatario corrente del ticket: copre correttamente il caso "più assegnatari nel tempo".
- `App\Domain\TimeTracking\Actions\RecalculateWorkedTime::run(Ticket $ticket, ?CarbonInterface $asOf = null)`
  è l'UNICO punto di scrittura per `tickets.worked_minutes`/`ticket_work_logs`: dentro una transazione,
  ricalcola il totale, CANCELLA tutte le righe `ticket_work_logs` esistenti per quel ticket e le ricrea da
  zero (mai un upsert differenziale) — è ciò che rende il ricalcolo idempotente a prescindere da chi lo
  invoca.
- Il ricalcolo "live" (listener di `TicketStatusChanged`) fa debounce per ticket con un lock in `Cache`
  (chiave `timetracking:recalculate-debounce:{ticket_id}`, TTL breve) verificato SINCRONAMENTE nel listener
  PRIMA di accodare `App\Domain\TimeTracking\Jobs\RecalculateTicketWorkedTimeJob`: il listener stesso NON
  implementa `ShouldQueue` (se lo facesse, il controllo del lock avverrebbe solo quando un worker preleva il
  job, troppo tardi per fare da debounce reale). Testabile con `Queue::fake()` +
  `Queue::assertPushed(Job::class, N)` (il fake sostituisce solo il dispatch del Job, non il lock in
  `Cache`).
- Comando `timetracking:recalculate {--from=} {--to=} {--ticket=}` riusa la stessa `RecalculateWorkedTime`
  (mai una seconda implementazione): `--ticket` ricalcola un solo ticket e ignora `--from`/`--to`; senza
  `--ticket` i due filtrano su `created_at` (entrambi opzionali).
- **BUG REALE trovato e corretto (US-219, 2026-08-02) in `progressIntervals()`**: il codice originale aveva
  due `if` separati con un `continue` dopo il primo (`if to_status===Progress { apri; continue; } if
  from_status===Progress { chiudi; }`). Un log "progress -> progress" (nessun cambio di stato intermedio,
  **frequente sui dati reali**) ha CONTEMPORANEAMENTE `from_status=Progress` e `to_status=Progress`: il primo
  `if` scattava per primo e usciva con `continue` PRIMA che il codice potesse riconoscere che quello stesso
  log avrebbe dovuto chiudere l'intervallo già aperto. Risultato: l'intero intervallo precedente spariva
  silenziosamente (caso reale sul dump v1: un intervallo di 27 giorni azzerato da un singolo log, ticket v1
  #2855). Fix: controllare **prima** la chiusura (`from_status===Progress`) e **poi**, senza `continue` fra
  le due, l'apertura (`to_status===Progress`) — un log che è sia chiusura sia apertura fa entrambe le cose
  alla stessa istante. **Pattern generale**: in un parser di eventi sequenziali dove un singolo evento può
  essere SIA la chiusura di uno stato SIA l'apertura del successivo, i due controlli non vanno mai messi in
  rami mutuamente esclusivi (`if`/`continue` o `if`/`else`) se l'evento può soddisfare entrambe le condizioni
  contemporaneamente. Test di regressione dedicati in `WorkedTimeCalculatorTest.php`.
- **Tolleranza del confronto ore v1/v2 in `v1:validate`**: `WorkedHoursDeviationAnalyzer::analyze()` richiede
  un secondo parametro `toleranceAbsoluteMinutes` (default 15) oltre alla tolleranza percentuale (default
  0.05): un ticket è entro tolleranza se lo scostamento è ≤ percentuale **oppure** ≤ soglia assoluta in
  minuti — la sola percentuale non è un criterio sensato sui ticket con poche ore v1 (un arrotondamento a 0
  su un ticket da 10 minuti risulta "100% di scostamento" pur essendo rumore, confermato sul dump reale). Il
  report espone `deviation_minutes` per ogni ticket oltre tolleranza, non solo la percentuale.
