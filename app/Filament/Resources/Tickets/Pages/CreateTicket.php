<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Pages;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\CreateTicket as CreateTicketAction;
use App\Filament\Resources\Tickets\Support\TicketFieldAccess;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Non usa il flusso di default `Model::create()` di Filament: la creazione passa
 * SEMPRE da {@see CreateTicketAction} (A1 del PRD), che forza lo stato iniziale
 * `new` e scrive il `ticket_log` `created`. Il campo `requester_id` è nascosto dal
 * form per chi non ha `ticket.manage-internal-fields` (un cliente, AC #4): per
 * quell'utente il richiedente è forzato qui all'utente autenticato, mai lasciato
 * `null`.
 */
class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        if (! TicketFieldAccess::canManageInternalFields()) {
            $data['requester_id'] = $user->id;
        }

        return CreateTicketAction::run($data, $user);
    }
}
