<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Pages;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\AddTicketAttachment;
use App\Domain\Ticketing\Actions\PostTicketMessage;
use App\Domain\Ticketing\Actions\RecordTicketView;
use App\Domain\Ticketing\Models\Ticket;
use App\Filament\Resources\Tickets\Support\CreateCommessaAction;
use App\Filament\Resources\Tickets\Support\TicketTransitionActions;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Pagina di dettaglio del ticket (US-110). Tre famiglie di header action, tutte
 * costruite dinamicamente sul record corrente:
 * - transizioni di stato ({@see TicketTransitionActions}, condivisa con `EditTicket`);
 * - pubblicazione di un messaggio (chiama {@see PostTicketMessage} +
 *   {@see AddTicketAttachment} per ciascun allegato, mai una scrittura diretta);
 * - gestione partecipanti (`ticket_participants`), ristretta a chi ha
 *   `ticket.assign` — nessuna Action dedicata introdotta per questo (AC #8,
 *   scelta esplicita "più semplice"): sync/detach diretti sulla relazione
 *   `Ticket::participants()`, stesso pattern già usato inline da
 *   `PostTicketMessage::run()` per l'autore.
 */
class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    /**
     * Wiring esplicito di RecordTicketView (US-108/US-110, AC #13): ogni apertura
     * del dettaglio registra (con throttling) la visualizzazione, mai una
     * scrittura diretta su `ticket_views`.
     */
    protected function resolveRecord(int|string $key): Model
    {
        $record = parent::resolveRecord($key);

        $user = Auth::user();

        if ($record instanceof Ticket && $user instanceof User) {
            RecordTicketView::run($record, $user);
        }

        return $record;
    }

    protected function getHeaderActions(): array
    {
        $ticket = $this->getRecord();

        abort_unless($ticket instanceof Ticket, 404);

        $createCommessaAction = CreateCommessaAction::build($ticket);

        return [
            EditAction::make(),
            ...TicketTransitionActions::build($ticket),
            $this->postMessageAction($ticket),
            ...$this->participantActions($ticket),
            ...($createCommessaAction !== null ? [$createCommessaAction] : []),
        ];
    }

    private function postMessageAction(Ticket $ticket): Action
    {
        return Action::make('post_message')
            ->label('Aggiungi messaggio')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->schema([
                RichEditor::make('body_html')
                    ->label('Messaggio')
                    ->required(),
                FileUpload::make('attachments')
                    ->label('Allegati')
                    ->multiple()
                    ->storeFiles(false),
            ])
            ->action(function (array $data) use ($ticket): void {
                $user = Auth::user();

                if (! $user instanceof User) {
                    return;
                }

                $message = PostTicketMessage::run($ticket, $user, (string) $data['body_html']);

                foreach (($data['attachments'] ?? []) as $file) {
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

                Notification::make()->success()->title('Messaggio pubblicato')->send();
            });
    }

    /**
     * @return list<Action>
     */
    private function participantActions(Ticket $ticket): array
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->can(Permission::TicketAssign)) {
            return [];
        }

        $addAction = Action::make('add_participant')
            ->label('Aggiungi partecipante')
            ->icon('heroicon-o-user-plus')
            ->schema([
                Select::make('user_id')
                    ->label('Utente')
                    ->options(fn () => User::query()->active()
                        ->whereNotIn('id', $ticket->participants()->pluck('users.id'))
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data) use ($ticket): void {
                $ticket->participants()->syncWithoutDetaching([(int) $data['user_id']]);

                Notification::make()->success()->title('Partecipante aggiunto')->send();
            });

        $removeAction = Action::make('remove_participant')
            ->label('Rimuovi partecipante')
            ->icon('heroicon-o-user-minus')
            ->color('danger')
            ->schema([
                Select::make('user_id')
                    ->label('Utente')
                    ->options(fn () => $ticket->participants()->orderBy('users.name')->pluck('users.name', 'users.id'))
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data) use ($ticket): void {
                $ticket->participants()->detach((int) $data['user_id']);

                Notification::make()->success()->title('Partecipante rimosso')->send();
            });

        return [$addAction, $removeAction];
    }
}
