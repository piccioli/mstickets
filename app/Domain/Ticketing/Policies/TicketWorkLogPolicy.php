<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\TicketWorkLog;

/**
 * Nessun permesso `timetracking.*` dedicato nel catalogo §9.3 (il modulo TimeTracking non ha
 * ancora modelli/permessi propri in questa fase): la registrazione delle ore lavorate su un
 * ticket è trattata come parte dell'aggiornamento del ticket stesso.
 */
class TicketWorkLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([Permission::TicketViewAny, Permission::TicketViewOwn, Permission::TicketViewAssigned]);
    }

    public function view(User $user, TicketWorkLog $ticketWorkLog): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->canAny([Permission::TicketUpdateAny, Permission::TicketUpdateOwn, Permission::TicketUpdateAssigned]);
    }

    public function update(User $user, TicketWorkLog $ticketWorkLog): bool
    {
        return $user->canAny([Permission::TicketUpdateAny, Permission::TicketUpdateOwn, Permission::TicketUpdateAssigned]);
    }

    public function delete(User $user, TicketWorkLog $ticketWorkLog): bool
    {
        return $user->canAny([Permission::TicketUpdateAny, Permission::TicketUpdateOwn, Permission::TicketUpdateAssigned]);
    }
}
