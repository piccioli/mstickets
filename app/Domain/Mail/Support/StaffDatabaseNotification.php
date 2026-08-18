<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

use App\Domain\Identity\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Notifica in-app Filament (`Illuminate\Notifications\DatabaseNotification`,
 * §7.5.2, US-312): unico punto di invio per E3/E9, così ogni comunicazione
 * staff di questa fase produce sia l'email (via {@see
 * \App\Domain\Mail\Actions\SendOutboundTicketMail}) sia il pallino/voce nella
 * campanella del pannello per ciascun destinatario interno, mai una delle
 * due sola. `$url` è opzionale (es. E9 prima che US-322 costruisca la
 * pagina di quarantena): senza URL la notifica resta comunque utile (titolo +
 * corpo), senza un pulsante che punta a una pagina inesistente.
 */
final class StaffDatabaseNotification
{
    public static function send(User $recipient, string $title, string $body, ?string $url = null): void
    {
        $notification = Notification::make()
            ->title($title)
            ->body($body);

        if ($url !== null) {
            $notification->actions([
                Action::make('view')->label('Apri')->url($url),
            ]);
        }

        $notification->sendToDatabase($recipient);
    }
}
