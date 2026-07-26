<?php

declare(strict_types=1);

namespace App\Domain\TimeTracking;

use Carbon\CarbonImmutable;

/**
 * Un blocco di minuti lavorati attribuiti a (giorno, utente), pronto per essere
 * sommato in `tickets.worked_minutes` o scritto come riga di `ticket_work_logs`
 * (che aggrega esattamente per work_date/user_id/ticket_id, §6.2.2 del PRD).
 */
final readonly class WorkedTimeSegment
{
    public function __construct(
        public CarbonImmutable $workDate,
        public int $userId,
        public int $minutes,
    ) {}
}
