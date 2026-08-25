<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\SendOutboundTicketMail;

/**
 * Unico punto di verità per la lingua di una comunicazione (§7.6, problema
 * 14, US-320): `users.locale` (sempre valorizzato, default `it` da schema),
 * fallback `organizations.locale` della prima organizzazione dell'utente, poi
 * `config('app.locale')`. Chiamato sia da
 * {@see SendOutboundTicketMail::run()} (locale del
 * Mailable/vista) sia da ogni Action/Listener che costruisce il subject
 * (stesso destinatario, stessa lingua per oggetto e corpo).
 */
final class RecipientLocale
{
    public static function resolve(User $user): string
    {
        $locale = trim((string) $user->locale);

        if ($locale !== '') {
            return $locale;
        }

        $organizationLocale = trim((string) $user->organizations()->first()?->locale);

        if ($organizationLocale !== '') {
            return $organizationLocale;
        }

        return (string) config('app.locale');
    }
}
