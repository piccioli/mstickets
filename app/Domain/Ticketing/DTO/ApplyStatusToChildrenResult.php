<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\DTO;

use App\Domain\Ticketing\Actions\ApplyStatusToChildren;
use App\Domain\Ticketing\Models\Ticket;

/**
 * Esito di {@see ApplyStatusToChildren}: un figlio la cui
 * transizione non è ammessa viene riportato in `skipped` (con il motivo) invece di
 * interrompere la propagazione sugli altri figli.
 */
final readonly class ApplyStatusToChildrenResult
{
    /**
     * @param  list<Ticket>  $applied
     * @param  list<array{ticket: Ticket, reason: string}>  $skipped
     */
    public function __construct(
        public array $applied,
        public array $skipped,
    ) {}
}
