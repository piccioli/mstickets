<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;

/**
 * E8 (§7.5.2 del PRD, US-614): riepilogo aggregato dell'attività di un
 * singolo ticket nella finestra del digest — mai un evento raw per riga, un
 * conteggio/stato per ticket. `previousStatus`/`currentStatus` sono
 * entrambi `null` quando nella finestra non c'è stato nessun cambio di
 * stato (solo nuovi messaggi), mai usati per dedurre "nessuna attività":
 * quello lo decide {@see hasActivity()}.
 */
final readonly class TicketDigestEntry
{
    public function __construct(
        public Ticket $ticket,
        public int $newMessagesCount,
        public ?TicketStatus $previousStatus,
        public ?TicketStatus $currentStatus,
    ) {}

    public function hasStatusChange(): bool
    {
        return $this->previousStatus !== null && $this->currentStatus !== null;
    }

    public function hasActivity(): bool
    {
        return $this->newMessagesCount > 0 || $this->hasStatusChange();
    }
}
