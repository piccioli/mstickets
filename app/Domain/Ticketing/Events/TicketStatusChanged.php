<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Events;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;

final readonly class TicketStatusChanged
{
    public function __construct(
        public Ticket $ticket,
        public TicketStatus $from,
        public TicketStatus $to,
        public User $actor,
    ) {}
}
