<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Identificazione del mittente di un'email inbound già classificata (§7.3.6,
 * US-305): risolve `email_messages.from_email` a un `users.id` in modo
 * affidabile, senza MAI inferire l'utente dal solo dominio del mittente
 * (esplicitamente vietato dal PRD: rischio di attribuzione errata a un
 * account diverso che condivide lo stesso dominio aziendale).
 *
 * Se il mittente non è identificabile con nessuno dei due metodi ammessi
 * (match esatto, poi sub-address), il messaggio NON è scartato: passa a
 * `status = quarantined` per la gestione manuale di US-308 (associazione a un
 * utente esistente o creazione di uno nuovo, poi riprocessamento dalla
 * pipeline a partire da questa stessa Action).
 *
 * Idempotente rispetto allo stato di partenza (US-308): richiamata su un
 * messaggio già `quarantined` che ora risolve un mittente (es. dopo l'azione
 * amministrativa "associa a utente esistente", US-322), riporta esplicitamente
 * lo `status` a `classified` — senza questo reset la pipeline (`ApplyInboundEmail`)
 * lo tratterebbe ancora come quarantena nonostante `user_id` sia stato risolto.
 */
final class ResolveEmailSender
{
    public static function run(EmailMessage $emailMessage): EmailMessage
    {
        try {
            $user = self::findByExactEmail($emailMessage->from_email)
                ?? self::findBySubAddress($emailMessage->from_email);

            if ($user === null) {
                $emailMessage->forceFill(['status' => EmailStatus::Quarantined])->save();

                return $emailMessage;
            }

            $emailMessage->forceFill(['user_id' => $user->id, 'status' => EmailStatus::Classified])->save();
        } catch (Throwable $exception) {
            Log::warning('mail.resolve_sender.failed', [
                'email_message_id' => $emailMessage->id,
                'error' => $exception->getMessage(),
            ]);

            $emailMessage->forceFill([
                'status' => EmailStatus::Failed,
                'failure_reason' => $exception->getMessage(),
            ])->save();
        }

        return $emailMessage;
    }

    /**
     * Match case-insensitive su `users.email`, riusando l'indice funzionale
     * `lower(email)` già presente da US-010 (nessuna nuova migrazione).
     */
    private static function findByExactEmail(string $email): ?User
    {
        if ($email === '') {
            return null;
        }

        return User::query()->whereRaw('lower(email) = ?', [strtolower($email)])->first();
    }

    /**
     * Sub-address (plus-addressing) `nome+tag@dominio → nome@dominio`: rimuove
     * solo il tag dalla local part a parità di dominio del mittente originale,
     * mai un'inferenza dal dominio da solo.
     */
    private static function findBySubAddress(string $email): ?User
    {
        if (! str_contains($email, '@') || ! str_contains(Str::before($email, '@'), '+')) {
            return null;
        }

        [$localPart, $domain] = explode('@', $email, 2);

        return self::findByExactEmail(Str::before($localPart, '+').'@'.$domain);
    }
}
