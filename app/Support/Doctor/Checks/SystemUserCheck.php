<?php

declare(strict_types=1);

namespace App\Support\Doctor\Checks;

use App\Domain\Identity\Models\User;
use App\Support\Doctor\Contracts\DoctorCheck;
use App\Support\Doctor\DoctorCheckResult;

/**
 * Verifica l'esistenza dell'utente di sistema (§12, US-022), usato come
 * fallback per l'autore dei log/eventi generati dal sistema (non da un
 * utente reale). Lo crea se manca: senza password e senza ruoli, così non
 * può autenticarsi né accedere al pannello.
 */
final class SystemUserCheck implements DoctorCheck
{
    public function run(): array
    {
        $email = (string) config('orchestrator.system_user.email');

        $user = User::system();

        return [new DoctorCheckResult(
            'Utente di sistema',
            true,
            $user->wasRecentlyCreated ? "creato ({$email})" : "già presente ({$email})",
        )];
    }
}
