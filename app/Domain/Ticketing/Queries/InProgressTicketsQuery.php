<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Queries;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * "In lavorazione" (§8.5): ticket con richiedente valorizzato attualmente in progress,
 * indipendentemente dall'assegnatario (a differenza di {@see AssignedToMeQuery}).
 */
final class InProgressTicketsQuery
{
    /**
     * @return Builder<Ticket>
     */
    public static function for(User $user): Builder
    {
        return Ticket::query()->visibleTo($user)
            ->where('status', TicketStatus::Progress)
            ->whereNotNull('requester_id');
    }
}
