<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Support;

use App\Domain\TimeTracking\WorkedTimeCalculator;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Conta i giorni lavorativi (lun-ven, stesso principio di
 * {@see WorkedTimeCalculator}) TRASCORSI PER INTERO
 * tra due istanti, usato da `tickets:remind-waiting` (§7.5.2 E7 del PRD,
 * US-316) per decidere se un ticket `status=waiting` è inattivo da almeno N
 * giorni lavorativi. A differenza di `WorkedTimeCalculator` (che somma minuti
 * lavorati dentro una finestra oraria) qui interessa solo il conteggio di
 * giorni interi non di weekend, a granularità di calendario: il giorno di
 * `$since` non viene mai contato (l'attività è avvenuta quel giorno), un
 * giorno viene contato solo se già completamente trascorso rispetto ad
 * `$asOf`.
 */
final class WorkingDaysCalculator
{
    public static function haveElapsed(CarbonInterface $since, int $workingDays, ?CarbonInterface $asOf = null): bool
    {
        $asOf = CarbonImmutable::instance($asOf ?? now());
        $cursor = CarbonImmutable::instance($since)->startOfDay();
        $limit = $asOf->startOfDay();
        $elapsed = 0;

        while ($cursor->lessThan($limit)) {
            $cursor = $cursor->addDay();

            if (! $cursor->isWeekend()) {
                $elapsed++;
            }

            if ($elapsed >= $workingDays) {
                return true;
            }
        }

        return false;
    }
}
