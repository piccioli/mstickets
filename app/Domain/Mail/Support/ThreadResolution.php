<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

use App\Domain\Mail\Enums\ThreadMatchLevel;

/**
 * Risultato di `App\Domain\Mail\Actions\ResolveEmailThread::run()` (§7.3.6,
 * US-306): puramente informativo, non scrive nulla su `email_messages` — è
 * `App\Domain\Mail\Actions\ApplyInboundEmail` (US-307) a decidere, in base a
 * questo risultato, se aggiornare un ticket esistente o crearne uno nuovo.
 * `ticketId` è `null` quando nessuno dei quattro livelli produce un match:
 * il chiamante deve allora generare un nuovo ticket (US-307).
 */
final readonly class ThreadResolution
{
    public function __construct(
        public ?int $ticketId,
        public ?ThreadMatchLevel $matchLevel,
    ) {}

    public static function none(): self
    {
        return new self(null, null);
    }

    public function isMatch(): bool
    {
        return $this->ticketId !== null;
    }

    public function isHeuristic(): bool
    {
        return $this->matchLevel === ThreadMatchLevel::Heuristic;
    }
}
