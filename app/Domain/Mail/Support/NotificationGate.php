<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\SendOutboundTicketMail;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Models\NotificationPreference;

/**
 * Unico punto di verità per "l'utente vuole ricevere questo tipo di
 * notifica via email?" (§7.5.1, US-317). Chiamato da
 * {@see SendOutboundTicketMail::run()}, l'unico
 * punto di invio del catalogo E1-E9 — nessun Mailable/Action invia
 * direttamente senza passare da qui, quindi nessun altro punto deve
 * ripetere questa lettura.
 *
 * Un utente senza righe in `notification_preferences` per il tipo dato
 * riceve la notifica: il default è "abilitato", coerente con la colonna
 * `enabled` di schema (default `true`, Fase 0/US-016) — l'assenza di una
 * riga non è mai trattata come "disabilitato".
 */
final class NotificationGate
{
    public static function allows(User $user, NotificationType $notificationType): bool
    {
        $preference = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('notification_type', $notificationType->value)
            ->where('channel', 'email')
            ->first();

        return $preference === null || $preference->enabled;
    }
}
