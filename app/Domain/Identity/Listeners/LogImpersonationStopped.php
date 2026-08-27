<?php

declare(strict_types=1);

namespace App\Domain\Identity\Listeners;

use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Log;
use STS\FilamentImpersonate\Events\LeaveImpersonation;

/**
 * Controparte di {@see LogImpersonationStarted} per l'uscita dall'impersonation
 * (§6.7.2, US-607). `$event->impersonated` è nullable nell'evento del pacchetto
 * (può mancare se la sessione è stata ripulita da un guard esterno, es. logout),
 * quindi va gestito come opzionale nel log.
 */
final class LogImpersonationStopped
{
    public function handle(LeaveImpersonation $event): void
    {
        $impersonator = $event->impersonator;
        $impersonated = $event->impersonated;

        Log::info('identity.impersonation.stopped', [
            'impersonator_id' => $impersonator->getAuthIdentifier(),
            'impersonator_email' => $impersonator instanceof User ? $impersonator->email : null,
            'impersonated_id' => $impersonated?->getAuthIdentifier(),
            'impersonated_email' => $impersonated instanceof User ? $impersonated->email : null,
        ]);
    }
}
