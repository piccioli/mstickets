<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Events;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;

final readonly class TicketTesterAssigned
{
    public function __construct(
        public Ticket $ticket,
        public ?int $previousTesterId,
        public int $testerId,
        public User $actor,
    ) {}
}
