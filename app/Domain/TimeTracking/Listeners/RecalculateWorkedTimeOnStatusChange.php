<?php

declare(strict_types=1);

namespace App\Domain\TimeTracking\Listeners;

use App\Domain\Ticketing\Events\TicketStatusChanged;
use App\Domain\TimeTracking\Jobs\RecalculateTicketWorkedTimeJob;
use Illuminate\Support\Facades\Cache;

/**
 * Innesca il ricalcolo delle ore lavorate (§6.2.2 del PRD) ad ogni cambio di stato,
 * con debounce per ticket: se una transizione per lo stesso ticket ha già messo in
 * coda un ricalcolo negli ultimi {@see self::DEBOUNCE_SECONDS} secondi, questa non ne
 * accoda un altro (una raffica di transizioni sullo stesso ticket produce un solo job,
 * che comunque rilegge lo stato corrente quando gira). Il listener resta sincrono
 * apposta (NON implementa ShouldQueue): il controllo del debounce deve avvenire
 * subito, al momento dell'evento, non quando un worker lo preleva dalla coda.
 */
final class RecalculateWorkedTimeOnStatusChange
{
    private const int DEBOUNCE_SECONDS = 5;

    public function handle(TicketStatusChanged $event): void
    {
        $lockKey = 'timetracking:recalculate-debounce:'.$event->ticket->id;

        if (Cache::has($lockKey)) {
            return;
        }

        Cache::put($lockKey, true, self::DEBOUNCE_SECONDS);

        RecalculateTicketWorkedTimeJob::dispatch($event->ticket->id)
            ->delay(now()->addSeconds(self::DEBOUNCE_SECONDS));
    }
}
