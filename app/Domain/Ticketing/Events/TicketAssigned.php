<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Events;

use App\Domain\Ticketing\Models\Ticket;

final readonly class TicketAssigned
{
    public function __construct(
        public Ticket $ticket,
        public ?int $previousAssigneeId,
        public int $assigneeId,
    ) {}
}
