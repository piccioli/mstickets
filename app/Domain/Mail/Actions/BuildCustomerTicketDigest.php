<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Support\TicketDigestEntry;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * E8 (§7.5.2 del PRD, US-614): raccoglie, per un cliente, l'attività
 * (nuovi messaggi pubblici, cambi di stato) avvenuta su ciascuno dei suoi
 * ticket dopo `$since` — un {@see TicketDigestEntry} per ticket, SOLO per i
 * ticket che hanno effettivamente attività (mai una riga vuota nel digest).
 * Un messaggio scritto dallo stesso cliente non conta come "attività da
 * segnalargli" (stessa esclusione dell'autore già in uso in
 * {@see SendNewTicketMessageMail} per E5).
 */
final class BuildCustomerTicketDigest
{
    /**
     * @return Collection<int, TicketDigestEntry>
     */
    public static function run(User $customer, Carbon $since): Collection
    {
        return Ticket::query()
            ->where('requester_id', $customer->id)
            ->get()
            ->map(fn (Ticket $ticket): TicketDigestEntry => self::entryFor($ticket, $customer, $since))
            ->filter(fn (TicketDigestEntry $entry): bool => $entry->hasActivity())
            ->values();
    }

    private static function entryFor(Ticket $ticket, User $customer, Carbon $since): TicketDigestEntry
    {
        $newMessagesCount = $ticket->messages()
            ->where('visibility', TicketMessageVisibility::Public)
            ->where('posted_at', '>=', $since)
            ->where(function ($query) use ($customer): void {
                $query->whereNull('author_id')->orWhere('author_id', '!=', $customer->id);
            })
            ->count();

        $statusLogs = $ticket->logs()
            ->where('event', TicketLogEvent::StatusChanged)
            ->where('occurred_at', '>=', $since)
            ->orderBy('occurred_at')
            ->get();

        /** @var TicketLog|null $firstStatusLog */
        $firstStatusLog = $statusLogs->first();

        return new TicketDigestEntry(
            ticket: $ticket,
            newMessagesCount: $newMessagesCount,
            previousStatus: $firstStatusLog?->from_status,
            currentStatus: $statusLogs->isNotEmpty() ? $ticket->status : null,
        );
    }
}
