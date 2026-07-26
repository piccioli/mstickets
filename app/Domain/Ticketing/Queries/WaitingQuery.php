<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Queries;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * "In attesa" (§8.5): {@see ActiveRequestsQuery} ristretta allo stato waiting, ordinata
 * per `status_changed_at` CRESCENTE (i ticket in attesa da più tempo in cima) — colonna
 * diretta, nessuna subquery sul JSON come nel v1.
 */
final class WaitingQuery
{
    /**
     * @return Builder<Ticket>
     */
    public static function for(User $user): Builder
    {
        return ActiveRequestsQuery::apply(Ticket::query()->visibleTo($user))
            ->where('status', TicketStatus::Waiting)
            ->orderBy('status_changed_at', 'asc');
    }
}
