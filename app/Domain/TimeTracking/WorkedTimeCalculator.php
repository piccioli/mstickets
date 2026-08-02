<?php

declare(strict_types=1);

namespace App\Domain\TimeTracking;

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\TicketLog;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Service puro (§6.2.2 del PRD): deriva le ore lavorate di un ticket dai suoi
 * `ticket_logs`, senza eseguire alcuna query — riceve i log già caricati e
 * restituisce dei {@see WorkedTimeSegment}, usati SIA per popolare
 * `tickets.worked_minutes` SIA per l'aggregato `ticket_work_logs` (DECISIONE Q15:
 * un'unica politica, non le due divergenti del v1).
 *
 * Algoritmo: si considerano gli intervalli tra un log con `to_status = 'progress'`
 * e il successivo log con `from_status = 'progress'`; si contano solo lunedì-venerdì,
 * nella finestra oraria configurata, con arrotondamento per difetto alla granularità
 * configurata. Un intervallo ancora aperto (il ticket è tuttora in `progress`, nessun
 * log di chiusura) non viene proiettato indefinitamente fino a `now()`: il totale
 * calcolato per quell'intervallo viene limitato al tetto configurato
 * (`non_status_change_cap_minutes`), attribuito al giorno più recente toccato
 * dall'intervallo aperto.
 */
final class WorkedTimeCalculator
{
    public function __construct(
        private readonly int $workdayStart,
        private readonly int $workdayEnd,
        private readonly int $granularityMinutes,
        private readonly int $nonStatusChangeCapMinutes,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            workdayStart: (int) config('timetracking.workday_start'),
            workdayEnd: (int) config('timetracking.workday_end'),
            granularityMinutes: (int) config('timetracking.granularity_minutes'),
            nonStatusChangeCapMinutes: (int) config('timetracking.non_status_change_cap_minutes'),
        );
    }

    /**
     * @param  iterable<int, TicketLog>  $logs  Ordinati per `occurred_at` crescente.
     * @return list<WorkedTimeSegment>
     */
    public function segmentsFor(iterable $logs, ?CarbonInterface $asOf = null): array
    {
        $asOf = CarbonImmutable::instance($asOf ?? now());

        $minutesByKey = [];

        foreach ($this->progressIntervals($logs, $asOf) as $interval) {
            $daily = $this->splitAcrossWorkdays($interval['start'], $interval['end']);

            if ($interval['open']) {
                $daily = $this->capOpenInterval($daily);
            }

            foreach ($daily as $workDate => $minutes) {
                if ($minutes <= 0) {
                    continue;
                }

                $key = $workDate.'|'.$interval['userId'];
                $minutesByKey[$key] = ($minutesByKey[$key] ?? 0) + $minutes;
            }
        }

        $segments = [];

        foreach ($minutesByKey as $key => $minutes) {
            [$workDate, $userId] = explode('|', $key);

            $segments[] = new WorkedTimeSegment(
                workDate: CarbonImmutable::parse($workDate),
                userId: (int) $userId,
                minutes: $minutes,
            );
        }

        return $segments;
    }

    /**
     * @param  iterable<int, TicketLog>  $logs  Ordinati per `occurred_at` crescente.
     */
    public function totalMinutesFor(iterable $logs, ?CarbonInterface $asOf = null): int
    {
        return array_sum(array_map(
            static fn (WorkedTimeSegment $segment): int => $segment->minutes,
            $this->segmentsFor($logs, $asOf),
        ));
    }

    /**
     * @param  iterable<int, TicketLog>  $logs
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable, userId: int, open: bool}>
     */
    private function progressIntervals(iterable $logs, CarbonImmutable $asOf): array
    {
        $intervals = [];
        $openStart = null;
        $openUserId = null;

        foreach ($logs as $log) {
            // Va controllata PRIMA la chiusura, poi l'apertura, SENZA un `continue`
            // fra le due: un log "progress -> progress" (nessun cambio di stato
            // intermedio, frequente sui dati reali v1) ha contemporaneamente
            // `from_status = Progress` e `to_status = Progress` e deve sia chiudere
            // l'intervallo già aperto sia aprirne uno nuovo alla stessa istante (zero
            // minuti persi, zero doppio conteggio). Un ordine invertito, o un
            // `continue` dopo l'apertura, fa perdere silenziosamente l'intero
            // intervallo precedente (bug reale trovato importando il dump v1: un
            // intervallo di 27 giorni azzerato da un singolo log di questo tipo).
            if ($openStart !== null && $openUserId !== null && $log->from_status === TicketStatus::Progress) {
                $intervals[] = [
                    'start' => $openStart,
                    'end' => CarbonImmutable::instance($log->occurred_at),
                    'userId' => $openUserId,
                    'open' => false,
                ];

                $openStart = null;
                $openUserId = null;
            }

            if ($log->to_status === TicketStatus::Progress) {
                $openStart = CarbonImmutable::instance($log->occurred_at);
                $openUserId = $log->user_id;
            }
        }

        if ($openStart !== null && $openUserId !== null && $openStart->lessThan($asOf)) {
            $intervals[] = [
                'start' => $openStart,
                'end' => $asOf,
                'userId' => $openUserId,
                'open' => true,
            ];
        }

        return $intervals;
    }

    /**
     * @return array<string, int> Minuti per giorno (`Y-m-d`), solo lunedì-venerdì,
     *                            ritagliati sulla finestra oraria configurata.
     */
    private function splitAcrossWorkdays(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $minutesByDate = [];
        $cursor = $start->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            if (! $cursor->isWeekend()) {
                $windowStart = $cursor->setTime($this->workdayStart, 0);
                $windowEnd = $cursor->setTime($this->workdayEnd, 0);

                $segmentStart = $start->greaterThan($windowStart) ? $start : $windowStart;
                $segmentEnd = $end->lessThan($windowEnd) ? $end : $windowEnd;

                if ($segmentEnd->greaterThan($segmentStart)) {
                    $minutes = (int) $segmentStart->diffInMinutes($segmentEnd);
                    $minutes = intdiv($minutes, $this->granularityMinutes) * $this->granularityMinutes;

                    if ($minutes > 0) {
                        $minutesByDate[$cursor->toDateString()] = $minutes;
                    }
                }
            }

            $cursor = $cursor->addDay();
        }

        return $minutesByDate;
    }

    /**
     * @param  array<string, int>  $daily
     * @return array<string, int>
     */
    private function capOpenInterval(array $daily): array
    {
        if ($daily === []) {
            return $daily;
        }

        $total = array_sum($daily);

        if ($total <= $this->nonStatusChangeCapMinutes) {
            return $daily;
        }

        $lastDate = array_key_last($daily);

        return [$lastDate => $this->nonStatusChangeCapMinutes];
    }
}
