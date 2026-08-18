<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Mail\Enums\ThreadMatchLevel;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailThread;
use App\Domain\Mail\Parsers\SubjectNormalizer;
use App\Domain\Mail\Support\ThreadResolution;
use App\Domain\Ticketing\Models\TicketMessage;

/**
 * Risolve il thread di un'email inbound già classificata (§7.3.6, US-306):
 * capisce se il messaggio è la risposta a un ticket esistente o l'apertura di
 * uno nuovo, provando IN ORDINE quattro livelli e fermandosi al primo che
 * produce un match — un livello più affidabile non è mai scavalcato da uno
 * meno affidabile, anche quando entrambi produrrebbero un risultato.
 *
 * Puramente in lettura: non scrive nulla su `email_messages`/`email_threads`,
 * l'applicazione del risultato (creazione ticket o nuovo messaggio) è
 * responsabilità di `ApplyInboundEmail` (US-307).
 */
final class ResolveEmailThread
{
    /** Local-part del destinatario VERP: `ticket+<ulid>` (§7.3.6). */
    private const string VERP_LOCAL_PART_PATTERN = '/^ticket\+([0-9a-z]{26})@/i';

    public static function run(EmailMessage $emailMessage): ThreadResolution
    {
        return self::byVerp($emailMessage)
            ?? self::byInReplyToOrReferences($emailMessage)
            ?? self::bySubjectToken($emailMessage)
            ?? self::byHeuristic($emailMessage)
            ?? ThreadResolution::none();
    }

    /**
     * Livello 1: token `ticket+<ulid>` nel destinatario `To`, dove `<ulid>` è
     * quello del `ticket_message` a cui una notifica in uscita si riferiva
     * (plus-addressing/VERP, US-311+). Il livello più affidabile: identifica
     * direttamente il `ticket_message`, quindi il ticket.
     */
    private static function byVerp(EmailMessage $emailMessage): ?ThreadResolution
    {
        foreach ($emailMessage->to ?? [] as $recipient) {
            if (! is_string($recipient) || preg_match(self::VERP_LOCAL_PART_PATTERN, $recipient, $matches) !== 1) {
                continue;
            }

            $ticketMessage = TicketMessage::query()->whereRaw('lower(ulid) = ?', [strtolower($matches[1])])->first();

            if ($ticketMessage !== null) {
                return new ThreadResolution($ticketMessage->ticket_id, ThreadMatchLevel::Verp);
            }
        }

        return null;
    }

    /**
     * Livello 2: `In-Reply-To`/`References` confrontati con
     * `email_messages.message_id` di un messaggio già esistente (in
     * qualunque direzione: tipicamente una nostra notifica in uscita, ma
     * anche un'email precedente dello stesso scambio).
     */
    private static function byInReplyToOrReferences(EmailMessage $emailMessage): ?ThreadResolution
    {
        $candidateIds = array_values(array_filter([
            $emailMessage->in_reply_to,
            ...($emailMessage->references !== null ? explode(' ', (string) $emailMessage->references) : []),
        ]));

        if ($candidateIds === []) {
            return null;
        }

        $referenced = EmailMessage::query()
            ->whereIn('message_id', $candidateIds)
            ->whereKeyNot($emailMessage->getKey())
            ->where(function ($query): void {
                $query->whereNotNull('ticket_id')->orWhereNotNull('thread_id');
            })
            ->with('thread')
            ->get()
            ->first(fn (EmailMessage $message): bool => self::ticketIdOf($message) !== null);

        if ($referenced === null) {
            return null;
        }

        return new ThreadResolution(self::ticketIdOf($referenced), ThreadMatchLevel::InReplyTo);
    }

    /**
     * Livello 3: token `[#<id ticket>]` nel subject normalizzato (US-303),
     * conservato apposta da `SubjectNormalizer::normalize()` per questo uso —
     * funziona anche sui ticket importati dal v1 perché l'ETL conserva gli id
     * originali (§5.1).
     */
    private static function bySubjectToken(EmailMessage $emailMessage): ?ThreadResolution
    {
        $ticketId = SubjectNormalizer::normalize($emailMessage->subject)->ticketId;

        if ($ticketId === null) {
            return null;
        }

        return new ThreadResolution($ticketId, ThreadMatchLevel::SubjectToken);
    }

    /**
     * Livello 4 (euristico, non deterministico): stesso mittente + subject
     * normalizzato identico + thread con attività negli ultimi N giorni
     * (`config('mail_pipeline.threading.heuristic_window_days')`). Confronta
     * `email_threads.subject_normalized` (popolato dall'ETL con la STESSA
     * funzione, {@see SubjectNormalizer::normalizeForThreadMatching()}) e
     * verifica l'appartenenza del mittente a `email_threads.participants`.
     */
    private static function byHeuristic(EmailMessage $emailMessage): ?ThreadResolution
    {
        $fromEmail = mb_strtolower(trim($emailMessage->from_email));

        if ($fromEmail === '') {
            return null;
        }

        $subjectKey = SubjectNormalizer::normalizeForThreadMatching($emailMessage->subject);

        if ($subjectKey === '') {
            return null;
        }

        $windowDays = (int) config('mail_pipeline.threading.heuristic_window_days');

        $thread = EmailThread::query()
            ->whereNotNull('ticket_id')
            ->where('subject_normalized', $subjectKey)
            ->where('last_message_at', '>=', now()->subDays($windowDays))
            ->orderByDesc('last_message_at')
            ->get()
            ->first(function (EmailThread $thread) use ($fromEmail): bool {
                $participants = array_map(
                    static fn (mixed $participant): string => mb_strtolower((string) $participant),
                    $thread->participants ?? [],
                );

                return in_array($fromEmail, $participants, true);
            });

        if ($thread === null) {
            return null;
        }

        return new ThreadResolution($thread->ticket_id, ThreadMatchLevel::Heuristic);
    }

    private static function ticketIdOf(EmailMessage $message): ?int
    {
        return $message->ticket_id ?? $message->thread?->ticket_id;
    }
}
