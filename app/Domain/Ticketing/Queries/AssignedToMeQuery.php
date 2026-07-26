<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Queries;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Assegnati a me" (§8.5): ticket dove l'utente corrente è assegnatario, esclusi quelli
 * appena creati (non ancora presi in carico) o già chiusi.
 */
final class AssignedToMeQuery
{
    /**
     * @return Builder<Ticket>
     */
    public static function for(User $user): Builder
    {
        return Ticket::query()->visibleTo($user)
            ->where('assignee_id', $user->id)
            ->whereNotIn('status', [TicketStatus::New, TicketStatus::Done]);
    }
}
