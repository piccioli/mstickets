<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Queries;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Richieste attive" (§8.5): ogni ticket con un richiedente, non ancora concluso/rifiutato
 * né in backlog. Base riusata (via {@see self::apply()}) da {@see InTestingQuery},
 * {@see WaitingQuery} e {@see ProblemTicketsQuery}, che aggiungono solo il filtro di stato
 * specifico invece di duplicare questa condizione.
 */
final class ActiveRequestsQuery
{
    /**
     * @return Builder<Ticket>
     */
    public static function for(User $user): Builder
    {
        return self::apply(Ticket::query()->visibleTo($user));
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public static function apply(Builder $query): Builder
    {
        return $query
            ->whereNotNull('requester_id')
            ->whereNotIn('status', [TicketStatus::Done, TicketStatus::Backlog, TicketStatus::Rejected]);
    }
}
