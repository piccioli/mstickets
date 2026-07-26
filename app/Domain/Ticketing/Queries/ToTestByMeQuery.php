<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Queries;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Da testare (io tester)" (§8.5): ticket in test dove l'utente corrente è il tester
 * assegnato.
 */
final class ToTestByMeQuery
{
    /**
     * @return Builder<Ticket>
     */
    public static function for(User $user): Builder
    {
        return Ticket::query()->visibleTo($user)
            ->where('tester_id', $user->id)
            ->where('status', TicketStatus::Testing);
    }
}
