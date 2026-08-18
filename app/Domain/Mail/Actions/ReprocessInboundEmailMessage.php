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
 * Azione amministrativa "riprocessa" (§7.3.8/§7.7, US-322): rilancia
 * {@see ApplyInboundEmail} su un messaggio inbound già classificato in
 * precedenza (`applied`/`discarded`/`failed`/`quarantined`), senza ripetere
 * il parsing/la classificazione anti-loop (già fatti a monte). Un messaggio
 * `quarantined` resta tale in ingresso (voluto: {@see ApplyInboundEmail}
 * riprova {@see ResolveEmailSender} da sé); qualunque altro stato ammesso
 * viene riportato a `classified` prima di rilanciare la pipeline.
 */
final class ReprocessInboundEmailMessage
{
    /**
     * @var list<EmailStatus>
     */
    private const REPROCESSABLE_STATUSES = [
        EmailStatus::Classified,
        EmailStatus::Quarantined,
        EmailStatus::Applied,
        EmailStatus::Discarded,
        EmailStatus::Failed,
    ];

    public static function run(EmailMessage $emailMessage, User $actor): EmailMessage
    {
        if ($emailMessage->direction !== EmailDirection::Inbound
            || ! in_array($emailMessage->status, self::REPROCESSABLE_STATUSES, true)) {
            throw new RuntimeException('Il messaggio non può essere riprocessato dallo stato attuale.');
        }

        if ($emailMessage->status !== EmailStatus::Quarantined) {
            $emailMessage->forceFill(['status' => EmailStatus::Classified, 'failure_reason' => null])->save();
        }

        $emailMessage = ApplyInboundEmail::run($emailMessage);

        EmailMessageLog::create([
            'email_message_id' => $emailMessage->id,
            'user_id' => $actor->id,
            'action' => EmailMessageLogEvent::Reprocessed,
            'occurred_at' => now(),
        ]);

        return $emailMessage;
    }
}
