<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Support;

use App\Domain\Identity\Enums\Permission;
use Illuminate\Support\Facades\Auth;

/**
 * Punto unico per decidere se l'utente corrente vede/può scrivere i campi "interni"
 * del ticket (§9.3: assignee/tester/type/priority/estimated_hours/worked_minutes/
 * description/staging_url/production_url). `Permission::TicketManageInternalFields`
 * è concesso ad admin/manager/developer e negato a customer/fundraising
 * (RolePermissionSeeder, §9.4): esprime esattamente la distinzione "staff vs
 * cliente" richiesta dall'AC di US-110 senza introdurre un secondo meccanismo
 * (`hasRole`) per lo stesso concetto.
 */
final class TicketFieldAccess
{
    public static function canManageInternalFields(): bool
    {
        return (bool) Auth::user()?->can(Permission::TicketManageInternalFields);
    }
}
