<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Queries;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Nuovi" (§8.5): ticket con richiedente valorizzato appena creati, non ancora assegnati.
 */
final class NewTicketsQuery
{
    /**
     * @return Builder<Ticket>
     */
    public static function for(User $user): Builder
    {
        return Ticket::query()->visibleTo($user)
            ->whereNotNull('requester_id')
            ->where('status', TicketStatus::New);
    }
}
