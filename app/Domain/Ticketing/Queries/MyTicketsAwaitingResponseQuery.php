<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Queries;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Richiedono una risposta" (§8.6/US-601, dashboard cliente): i propri ticket
 * (`requester_id = $user->id`) attualmente in `waiting`/`problem` — gli stati
 * in cui il cliente è l'attore che deve muovere il ticket in avanti (§6.1.7,
 * T7: solo un messaggio del richiedente riporta lo stato indietro). Stesso
 * schema di {@see MyTicketsQuery}, mai riusato al posto di quest'ultima:
 * "i miei ticket aperti" e "i miei ticket che attendono una mia risposta"
 * sono due insiemi distinti (non annidati) anche se entrambi filtrano su
 * `requester_id`.
 */
final class MyTicketsAwaitingResponseQuery
{
    /**
     * @return Builder<Ticket>
     */
    public static function for(User $user): Builder
    {
        return Ticket::query()->visibleTo($user)
            ->where('requester_id', $user->id)
            ->whereIn('status', [TicketStatus::Waiting, TicketStatus::Problem]);
    }
}
