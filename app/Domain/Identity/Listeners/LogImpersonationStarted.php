<?php

declare(strict_types=1);

namespace App\Domain\Identity\Listeners;

use App\Console\Commands\DocumentationRegeneratePdfsCommand;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Log;
use STS\FilamentImpersonate\Events\EnterImpersonation;

/**
 * Log strutturato per l'impersonation (§6.7.2, US-607): non esiste una tabella
 * ticket-specifica adatta (l'impersonation non è legata a un ticket), quindi si
 * riusa il pattern `Log::info('dominio.azione.evento', [...])` già in uso dai
 * comandi schedulati (es. {@see DocumentationRegeneratePdfsCommand}),
 * non una nuova tabella dedicata.
 */
final class LogImpersonationStarted
{
    public function handle(EnterImpersonation $event): void
    {
        $impersonator = $event->impersonator;
        $impersonated = $event->impersonated;

        Log::info('identity.impersonation.started', [
            'impersonator_id' => $impersonator->getAuthIdentifier(),
            'impersonator_email' => $impersonator instanceof User ? $impersonator->email : null,
            'impersonated_id' => $impersonated->getAuthIdentifier(),
            'impersonated_email' => $impersonated instanceof User ? $impersonated->email : null,
        ]);
    }
}
