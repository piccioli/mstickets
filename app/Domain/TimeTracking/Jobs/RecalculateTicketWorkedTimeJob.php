<?php

declare(strict_types=1);

namespace App\Domain\TimeTracking\Jobs;

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\TimeTracking\Actions\RecalculateWorkedTime;
use App\Domain\TimeTracking\Listeners\RecalculateWorkedTimeOnStatusChange;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job in coda che esegue davvero il ricalcolo (§6.2.2 del PRD): il debounce per
 * ticket avviene PRIMA di questo dispatch, nel listener
 * {@see RecalculateWorkedTimeOnStatusChange}, non
 * qui — questo job si limita a rileggere lo stato attuale del ticket ed eseguire
 * {@see RecalculateWorkedTime}, quindi produce sempre il risultato corretto anche se
 * più transizioni sono state "assorbite" dal debounce prima che girasse.
 */
final class RecalculateTicketWorkedTimeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $ticketId) {}

    public function handle(): void
    {
        $ticket = Ticket::query()->find($this->ticketId);

        if ($ticket === null) {
            return;
        }

        RecalculateWorkedTime::run($ticket);
    }
}
