<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\DTO;

/**
 * DTO tipizzato per il campo `ticket_logs.changes` (§6.2.1, US-103): i chiamanti usano
 * sempre uno dei costruttori nominati qui sotto, mai un array libero costruito ad-hoc.
 * `descriptionChanged()` registra solo il marker 'changed', mai il corpo del campo
 * (comportamento v1 da mantenere): qualunque futura Action che logghi un cambio di
 * `description` deve passare da qui, non reintrodurre il valore per esteso.
 */
final readonly class TicketLogChanges
{
    /**
     * @param  array<string, mixed>  $fields
     */
    private function __construct(private array $fields) {}

    public static function assigneeChanged(?int $from, ?int $to): self
    {
        return new self(['assignee_id' => ['from' => $from, 'to' => $to]]);
    }

    public static function descriptionChanged(): self
    {
        return new self(['description' => 'changed']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->fields;
    }
}
