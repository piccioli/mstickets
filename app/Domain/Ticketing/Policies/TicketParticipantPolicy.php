<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\TicketParticipant;

/**
 * Nessun permesso dedicato ai partecipanti nel catalogo §9.3: aggiungere/rimuovere un
 * partecipante è trattato come parte dell'assegnazione del ticket (`ticket.assign`), la
 * visibilità come parte della visibilità del ticket stesso.
 */
class TicketParticipantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([Permission::TicketViewAny, Permission::TicketViewOwn, Permission::TicketViewAssigned]);
    }

    public function view(User $user, TicketParticipant $ticketParticipant): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::TicketAssign);
    }

    public function update(User $user, TicketParticipant $ticketParticipant): bool
    {
        return $user->can(Permission::TicketAssign);
    }

    public function delete(User $user, TicketParticipant $ticketParticipant): bool
    {
        return $user->can(Permission::TicketAssign);
    }
}
