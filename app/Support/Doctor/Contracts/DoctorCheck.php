<?php

declare(strict_types=1);

namespace App\Support\Doctor\Contracts;

use App\Support\Doctor\DoctorCheckResult;

/**
 * Un controllo indipendente eseguito da `php artisan orchestrator:doctor` (§12
 * del PRD). Le fasi successive aggiungono nuovi controlli (IMAP/SMTP, logo
 * PDF, stato ultimo import) implementando questa interfaccia e registrandoli
 * nella lista di `OrchestratorDoctorCommand`, senza toccare i controlli
 * esistenti.
 */
interface DoctorCheck
{
    /**
     * @return list<DoctorCheckResult>
     */
    public function run(): array;
}
