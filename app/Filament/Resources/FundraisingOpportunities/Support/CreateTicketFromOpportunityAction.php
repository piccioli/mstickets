<?php

declare(strict_types=1);

namespace App\Filament\Resources\FundraisingOpportunities\Support;

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\CreateTicket;
use App\Domain\Ticketing\Policies\TicketPolicy;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * Header action "Crea ticket" (US-505, §6.6.1): crea un `Ticket` con `title`
 * precompilato dal nome dell'opportunità. `fundraising_project_id` è valorizzato
 * SOLO se l'opportunità ha già un progetto collegato (creato da
 * {@see CreateFundraisingProjectAction}) — lo schema non prevede un collegamento
 * diretto opportunità->ticket, solo `tickets.fundraising_project_id`. Ritorna
 * `null` (nessun bottone) se l'utente non ha `ticket.create` ({@see TicketPolicy}).
 */
final class CreateTicketFromOpportunityAction
{
    public static function build(FundraisingOpportunity $opportunity): ?Action
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->can(Permission::TicketCreate)) {
            return null;
        }

        return Action::make('create_ticket')
            ->label('Crea ticket')
            ->icon('heroicon-o-ticket')
            ->schema([
                TextInput::make('title')
                    ->label('Titolo ticket')
                    ->default($opportunity->name)
                    ->required(),
            ])
            ->action(function (array $data) use ($opportunity, $user): void {
                $project = $opportunity->projects()->first();

                $ticket = CreateTicket::run([
                    'title' => (string) $data['title'],
                    'fundraising_project_id' => $project?->id,
                ], $user);

                Notification::make()
                    ->success()
                    ->title('Ticket creato')
                    ->body($ticket->title)
                    ->send();
            });
    }
}
