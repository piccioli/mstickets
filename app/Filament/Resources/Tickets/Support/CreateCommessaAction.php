<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Support;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Tags\Actions\CreateTagFromTicket;
use App\Domain\Tags\Policies\TagPolicy;
use App\Domain\Ticketing\Models\Ticket;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * Header action "Crea commessa" (US-402, §6.3), condivisa tra `ViewTicket` ed
 * `EditTicket` come {@see TicketTransitionActions}. Ritorna `null` (nessun
 * bottone) se l'utente non ha `tag.create` ({@see TagPolicy}).
 */
final class CreateCommessaAction
{
    public static function build(Ticket $ticket): ?Action
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->can(Permission::TagCreate)) {
            return null;
        }

        return Action::make('create_commessa')
            ->label('Crea commessa')
            ->icon('heroicon-o-briefcase')
            ->schema([
                TextInput::make('name')
                    ->label('Nome')
                    ->default($ticket->title)
                    ->required(),
                TextInput::make('estimated_hours')
                    ->label('Ore stimate')
                    ->numeric()
                    ->default($ticket->estimated_hours !== null ? (float) $ticket->estimated_hours : null),
            ])
            ->action(function (array $data) use ($ticket): void {
                $tag = CreateTagFromTicket::run(
                    $ticket,
                    (string) $data['name'],
                    isset($data['estimated_hours']) ? (float) $data['estimated_hours'] : null,
                );

                Notification::make()
                    ->success()
                    ->title('Commessa creata')
                    ->body($tag->name)
                    ->send();
            });
    }
}
