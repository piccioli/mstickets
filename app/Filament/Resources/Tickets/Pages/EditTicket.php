<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Pages;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\AssignTicket;
use App\Domain\Ticketing\Models\Ticket;
use App\Filament\Resources\Tickets\Support\TicketTransitionActions;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Header actions dinamiche di transizione anche qui (AC #1: "header actions sul
 * View/Edit"), costruite dalla stessa classe condivisa usata da `ViewTicket`.
 * `assignee_id`, se cambiato, passa SEMPRE da {@see AssignTicket} (A1 del PRD:
 * mai una scrittura diretta della colonna per una riassegnazione) — tutti gli
 * altri campi del form (nessuno dei quali ha un'Action di dominio dedicata in
 * questa fase) sono salvati con un update Eloquent diretto.
 */
class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        abort_unless($record instanceof Ticket, 404);

        return [
            ViewAction::make(),
            ...TicketTransitionActions::build($record),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        abort_unless($record instanceof Ticket, 404);

        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        if (array_key_exists('assignee_id', $data)) {
            $assigneeId = $data['assignee_id'];

            // Il valore ORIGINALE persistito (`getOriginal()`), non l'attributo
            // corrente in memoria: il Select `->relationship('assignee', ...)` di
            // Filament associa già la nuova relationship sul modello durante
            // `getState()` (prima che questo metodo sia chiamato), quindi
            // `$record->assignee_id` a questo punto è GIÀ il nuovo valore — un
            // confronto con quello non rileverebbe mai un cambiamento reale.
            $originalAssigneeId = $record->getOriginal('assignee_id');
            unset($data['assignee_id']);

            if ($assigneeId !== null && (int) $assigneeId !== $originalAssigneeId) {
                $assignee = User::query()->findOrFail($assigneeId);

                AssignTicket::run($record, $assignee, $user);
            }
        }

        $record->fill($data);
        $record->save();

        return $record->fresh() ?? $record;
    }
}
