<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Queries;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Problemi" (§8.5): {@see ActiveRequestsQuery} ristretta allo stato problem.
 */
final class ProblemTicketsQuery
{
    /**
     * @return Builder<Ticket>
     */
    public static function for(User $user): Builder
    {
        return ActiveRequestsQuery::apply(Ticket::query()->visibleTo($user))
            ->where('status', TicketStatus::Problem);
    }
}
