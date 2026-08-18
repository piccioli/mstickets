<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailMessageLogEvent;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailMessageLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Azione amministrativa "crea nuovo utente e ticket" (§7.3.8, US-322): per un
 * mittente sconosciuto che non corrisponde a nessun `User` esistente,
 * l'amministrazione può crearne uno nuovo (ruolo `customer`, §6.7.1) e
 * riprocessare subito il messaggio via
 * {@see ApplyInboundEmail::runForResolvedSender()} (stesso ingresso usato da
 * {@see AssignEmailMessageSender} per il caso "utente già esistente"), mai
 * `ApplyInboundEmail::run()`: il nuovo utente ha volutamente la STESSA email
 * del mittente originale, quindi in teoria sarebbe ritrovabile anche da
 * `ResolveEmailSender`, ma passare dal punto di ingresso esplicito evita una
 * query di lookup ridondante e resta coerente con l'altro ramo "utente
 * esistente", che invece NON può fare affidamento su `ResolveEmailSender`.
 *
 * Nessun flusso di invito/password qui (fuori scope, non esiste ancora
 * nemmeno per la creazione utente "normale" da `UserResource`, US-021): la
 * password è generata casuale e mai comunicata, l'utente la imposterà dal
 * recupero password già esistente (§9.1) quando avrà bisogno di accedere al
 * pannello.
 */
final class CreateEmailSenderAndAssign
{
    public static function run(EmailMessage $emailMessage, string $name, string $email, User $actor): EmailMessage
    {
        if ($emailMessage->direction !== EmailDirection::Inbound || $emailMessage->status !== EmailStatus::Quarantined) {
            throw new RuntimeException('Solo un messaggio in quarantena può generare un nuovo mittente.');
        }

        $sender = DB::transaction(function () use ($name, $email): User {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Str::password(40),
            ]);

            $user->assignRole(UserRole::Customer->value);

            return $user;
        });

        $emailMessage->forceFill(['user_id' => $sender->id, 'status' => EmailStatus::Classified])->save();

        $emailMessage = ApplyInboundEmail::runForResolvedSender($emailMessage, $sender);

        EmailMessageLog::create([
            'email_message_id' => $emailMessage->id,
            'user_id' => $actor->id,
            'action' => EmailMessageLogEvent::SenderCreated,
            'notes' => "Nuovo utente creato: {$sender->email}",
            'occurred_at' => now(),
        ]);

        return $emailMessage;
    }
}
