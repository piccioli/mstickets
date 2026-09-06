<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Pages;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\AddTicketAttachment;
use App\Domain\Ticketing\Actions\CreateTicket as CreateTicketAction;
use App\Domain\Ticketing\Actions\PostTicketMessage;
use App\Filament\Resources\Tickets\Support\TicketFieldAccess;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Non usa il flusso di default `Model::create()` di Filament: la creazione passa
 * SEMPRE da {@see CreateTicketAction} (A1 del PRD), che forza lo stato iniziale
 * `new` e scrive il `ticket_log` `created`. Il campo `requester_id` è nascosto dal
 * form per chi non ha `ticket.manage-internal-fields` (un cliente, AC #4): per
 * quell'utente il richiedente è forzato qui all'utente autenticato, mai lasciato
 * `null`.
 *
 * `richiesta`/`allegati` (US-Fase9, Storia 1) non sono colonne di `Ticket`: sono
 * estratte da `$data` PRIMA di {@see CreateTicketAction::run()} e usate per
 * pubblicare il primo messaggio pubblico della conversazione ({@see PostTicketMessage})
 * più i relativi allegati ({@see AddTicketAttachment}) — stesso schema già usato da
 * `ViewTicket::postMessageAction()` per "Aggiungi messaggio".
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

        $richiesta = (string) ($data['richiesta'] ?? '');
        $allegati = $data['allegati'] ?? [];
        unset($data['richiesta'], $data['allegati']);

        $ticket = CreateTicketAction::run($data, $user);

        $message = PostTicketMessage::run($ticket, $user, $richiesta);

        foreach ($allegati as $file) {
            try {
                AddTicketAttachment::run($message, $file, $user);
            } catch (ValidationException $exception) {
                Notification::make()
                    ->danger()
                    ->title('Allegato non valido')
                    ->body($exception->errors()['file'][0] ?? $exception->getMessage())
                    ->send();
            }
        }

        return $ticket;
    }
}
