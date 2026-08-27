<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Support;

use App\Domain\Identity\Models\User;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Tables\UsersTable;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Azione unica "Disattiva"/"Riattiva" (US-608, §6.7.5), condivisa fra
 * {@see UsersTable} e
 * {@see ViewUser} (stesso idioma di
 * `TicketTransitionActions`: un solo posto costruisce l'`Action`, mai
 * duplicata fra riga tabella e pagina). Autorizzazione delegata interamente
 * a `UserPolicy::deactivate()` (`Permission::UserDeactivate`) — nessun
 * controllo di ruolo qui.
 *
 * Valorizza/azzera direttamente la proprietà `deactivated_at` e salva
 * (mai `fill()`/`update()`): la colonna non è nel `#[Fillable]` del model
 * (di proposito, per non renderla editabile dal form utente), quindi un
 * assegnamento mass-assignment verrebbe scartato silenziosamente.
 */
final class DeactivateUserAction
{
    public static function make(): Action
    {
        return Action::make('toggleDeactivation')
            ->label(fn (User $record): string => $record->deactivated_at === null ? 'Disattiva' : 'Riattiva')
            ->icon(fn (User $record): string => $record->deactivated_at === null ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
            ->color(fn (User $record): string => $record->deactivated_at === null ? 'danger' : 'success')
            ->requiresConfirmation()
            ->authorize(fn (User $record): bool => auth()->user()?->can('deactivate', $record) ?? false)
            ->action(function (User $record): void {
                $wasActive = $record->deactivated_at === null;

                $record->deactivated_at = $wasActive ? Carbon::now() : null;
                $record->save();

                Notification::make()
                    ->title($wasActive ? 'Utente disattivato' : 'Utente riattivato')
                    ->success()
                    ->send();
            });
    }
}
