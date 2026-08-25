<?php

declare(strict_types=1);

namespace App\Domain\Mail\Listeners;

use App\Domain\Mail\Actions\SendNewTicketMessageMail;
use App\Domain\Ticketing\Actions\PostTicketMessage;
use App\Domain\Ticketing\Events\TicketMessagePosted;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * E5 (§7.5.2 del PRD, US-314): trigger "nuovo messaggio pubblicato sul ticket",
 * per qualunque messaggio pubblicato da {@see PostTicketMessage}, canale web o
 * email. Implementa ShouldQueue perché l'evento è dispatchato sincronamente
 * (`PostTicketMessage::run()`): l'asincronia della notifica sta qui, non nel
 * dispatch, stesso principio già applicato agli altri listener E1-E4/E9. Il
 * filtro "solo visibility=public" vive in {@see SendNewTicketMessageMail}, non
 * qui.
 */
final class SendNewTicketMessageNotification implements ShouldQueue
{
    public function handle(TicketMessagePosted $event): void
    {
        SendNewTicketMessageMail::run($event);
    }
}
