<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Queries;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Tutti i ticket di clienti" (§8.5): ogni ticket il cui richiedente ha il ruolo customer,
 * qualunque sia lo stato. Usa `whereHas('roles', ...)` invece dello scope `role()` di
 * Spatie: quello scope risolve il ruolo con `findByName()` e lancia `RoleDoesNotExist`
 * se la riga `roles` non esiste ancora (es. nessun cliente mai registrato), mentre qui
 * l'assenza del ruolo deve solo produrre zero righe, mai un'eccezione.
 */
final class AllCustomerTicketsQuery
{
    /**
     * @return Builder<Ticket>
     */
    public static function for(User $user): Builder
    {
        return Ticket::query()->visibleTo($user)
            ->whereHas(
                'requester',
                fn (Builder $query): Builder => $query->whereHas(
                    'roles',
                    fn (Builder $query): Builder => $query->where('name', UserRole::Customer->value),
                ),
            );
    }
}
