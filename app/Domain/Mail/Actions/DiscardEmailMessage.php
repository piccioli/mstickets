<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailDiscardReason;
use App\Domain\Mail\Enums\EmailMessageLogEvent;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailMessageLog;
use RuntimeException;

/**
 * Azione amministrativa "scarta" (§7.3.8/§7.7, US-322): forza `status =
 * discarded` con un motivo scritto a mano dall'amministrazione (a differenza
 * di {@see EmailDiscardReason}, che copre solo gli
 * scarti automatici della classificazione anti-loop, US-304) — riusa la
 * stessa colonna `failure_reason` già mostrata dal registro (US-321).
 * Applicabile solo a un'email inbound non ancora applicata su un ticket:
 * scartare un messaggio già collegato a un ticket lascerebbe un
 * `ticket_message` orfano rispetto allo stato del messaggio, mai ammesso.
 */
final class DiscardEmailMessage
{
    public static function run(EmailMessage $emailMessage, string $reason, User $actor): EmailMessage
    {
        if ($emailMessage->direction !== EmailDirection::Inbound || $emailMessage->ticket_id !== null) {
            throw new RuntimeException('Solo un\'email inbound non ancora collegata a un ticket può essere scartata.');
        }

        $emailMessage->forceFill([
            'status' => EmailStatus::Discarded,
            'failure_reason' => $reason,
        ])->save();

        EmailMessageLog::create([
            'email_message_id' => $emailMessage->id,
            'user_id' => $actor->id,
            'action' => EmailMessageLogEvent::Discarded,
            'notes' => $reason,
            'occurred_at' => now(),
        ]);

        return $emailMessage;
    }
}
