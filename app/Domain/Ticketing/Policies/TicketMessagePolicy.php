<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use App\Domain\Ticketing\Models\TicketMessage;

class TicketMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([Permission::TicketViewAny, Permission::TicketViewOwn, Permission::TicketViewAssigned]);
    }

    public function view(User $user, TicketMessage $ticketMessage): bool
    {
        return match ($ticketMessage->visibility) {
            TicketMessageVisibility::Internal => $user->can(Permission::TicketMessageViewInternal),
            TicketMessageVisibility::Public => $this->viewAny($user),
        };
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::TicketMessageCreate);
    }

    /**
     * Nessun permesso `ticket-message.update`/`ticket-message.delete` nel catalogo §9.3: i
     * messaggi sono un log immutabile una volta pubblicati (§5.2), negato a chiunque.
     */
    public function update(User $user, TicketMessage $ticketMessage): bool
    {
        return false;
    }

    public function delete(User $user, TicketMessage $ticketMessage): bool
    {
        return false;
    }
}
