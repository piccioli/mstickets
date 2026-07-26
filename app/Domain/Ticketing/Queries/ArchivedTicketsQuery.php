<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Queries;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Archivio" (§8.5, vista staff): ticket conclusi o rifiutati, qualunque richiedente.
 */
final class ArchivedTicketsQuery
{
    /**
     * @return Builder<Ticket>
     */
    public static function for(User $user): Builder
    {
        return Ticket::query()->visibleTo($user)
            ->whereIn('status', [TicketStatus::Done, TicketStatus::Rejected]);
    }
}
