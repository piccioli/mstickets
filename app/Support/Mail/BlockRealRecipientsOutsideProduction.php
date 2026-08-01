<?php

declare(strict_types=1);

namespace App\Support\Mail;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;

/**
 * Guard applicativo (§11.8 del PRD, US-217): fuori produzione, blocca l'invio
 * di QUALUNQUE email dell'applicazione verso un indirizzo il cui dominio non è
 * nell'allowlist (`config('orchestrator.anonymization.mail_test_domains')`) —
 * non un vincolo dell'ETL, vale per ogni mailer/mailable, indipendentemente da
 * dove/come l'invio viene innescato. Restituire `false` da un listener di
 * `MessageSending` annulla l'invio (`Mailer::shouldSendMessage()`).
 */
final class BlockRealRecipientsOutsideProduction
{
    public function handle(MessageSending $event): bool
    {
        if (app()->environment('production')) {
            return true;
        }

        $allowedDomains = array_map(
            static fn (string $domain): string => strtolower($domain),
            (array) config('orchestrator.anonymization.mail_test_domains', []),
        );

        foreach ($this->recipientAddresses($event) as $address) {
            $domain = strtolower(Str::after($address, '@'));

            if (! in_array($domain, $allowedDomains, true)) {
                logger()->warning("Invio email bloccato fuori produzione: destinatario \"{$address}\" non in allowlist domini di test.");

                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function recipientAddresses(MessageSending $event): array
    {
        $addresses = [
            ...$event->message->getTo(),
            ...$event->message->getCc(),
            ...$event->message->getBcc(),
        ];

        return array_map(static fn (Address $address): string => $address->getAddress(), $addresses);
    }
}
