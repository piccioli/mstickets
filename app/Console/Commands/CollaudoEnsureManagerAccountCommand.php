<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Import\Anonymization\Anonymizer;
use Illuminate\Console\Command;

/**
 * Garantisce l'esistenza dell'account di riferimento del collaudo per il ruolo
 * "manager" (`manager@oc.test`, docs/collaudo/00-istruzioni-generali.md): a
 * differenza degli altri 4 ruoli (admin/developer/fundraising/customer), nessun
 * utente v1 reale ha mai avuto il ruolo "manager" (introdotto solo in v2, §9.4
 * del PRD) — non esiste quindi un id da mappare in
 * `config('orchestrator.anonymization.reference_users')` ({@see Anonymizer}).
 * Questo comando crea l'account ex novo invece di importarlo dal dump.
 *
 * Idempotente (`updateOrCreate` + `syncRoles`): rilanciabile a ogni `make setup`/
 * deploy senza duplicare l'account né lasciargli ruoli residui. Mai in
 * produzione — stesso principio del reset password di {@see Anonymizer}, questo
 * account esiste solo per il collaudo su ambienti non di produzione.
 */
final class CollaudoEnsureManagerAccountCommand extends Command
{
    private const EMAIL = 'manager@oc.test';

    protected $signature = 'collaudo:ensure-manager-account';

    protected $description = 'Crea/garantisce l\'account di riferimento del collaudo per il ruolo manager (manager@oc.test), inesistente in v1.';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('collaudo:ensure-manager-account non è consentito in ambiente di produzione.');

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Manager Collaudo',
                'password' => Anonymizer::default()->passwordHash(),
                'deactivated_at' => null,
            ],
        );

        $user->syncRoles([UserRole::Manager]);

        $this->info(sprintf('Account manager di collaudo pronto: %s (password: "password").', self::EMAIL));

        return self::SUCCESS;
    }
}
