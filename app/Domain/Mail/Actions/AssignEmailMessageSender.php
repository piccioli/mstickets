<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailMessageLogEvent;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailMessageLog;
use RuntimeException;

/**
 * Azione amministrativa "assegna a utente"/"associa a utente esistente"
 * (§7.3.8, US-322): un messaggio in quarantena (mittente non identificato da
 * {@see ResolveEmailSender}) viene associato manualmente a un `User` già
 * esistente, poi riprocessato via {@see ApplyInboundEmail::runForResolvedSender()}
 * — MAI `ApplyInboundEmail::run()`, che rilancerebbe `ResolveEmailSender` e
 * vanificherebbe l'assegnazione manuale — nessuna Action duplicata per la
 * creazione del ticket, coerente con la scelta già fatta in US-308.
 */
final class AssignEmailMessageSender
{
    public static function run(EmailMessage $emailMessage, User $sender, User $actor): EmailMessage
    {
        if ($emailMessage->direction !== EmailDirection::Inbound || $emailMessage->status !== EmailStatus::Quarantined) {
            throw new RuntimeException('Solo un messaggio in quarantena può essere associato a un mittente.');
        }

        $emailMessage->forceFill(['user_id' => $sender->id, 'status' => EmailStatus::Classified])->save();

        $emailMessage = ApplyInboundEmail::runForResolvedSender($emailMessage, $sender);

        EmailMessageLog::create([
            'email_message_id' => $emailMessage->id,
            'user_id' => $actor->id,
            'action' => EmailMessageLogEvent::SenderAssigned,
            'notes' => "Mittente associato: {$sender->email}",
            'occurred_at' => now(),
        ]);

        return $emailMessage;
    }
}
