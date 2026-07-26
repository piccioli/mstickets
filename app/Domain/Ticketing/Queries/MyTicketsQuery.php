<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Queries;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * "I miei ticket" (§8.5, vista cliente): i propri ticket non ancora conclusi/rifiutati.
 */
final class MyTicketsQuery
{
    /**
     * @return Builder<Ticket>
     */
    public static function for(User $user): Builder
    {
        return Ticket::query()->visibleTo($user)
            ->where('requester_id', $user->id)
            ->whereNotIn('status', [TicketStatus::Done, TicketStatus::Rejected]);
    }
}
