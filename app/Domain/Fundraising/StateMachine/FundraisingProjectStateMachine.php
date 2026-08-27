<?php

declare(strict_types=1);

namespace App\Domain\Fundraising\StateMachine;

use App\Domain\Fundraising\Enums\FundraisingProjectStatus;
use App\Domain\Ticketing\StateMachine\TicketStateMachine;
use Illuminate\Validation\ValidationException;

/**
 * Macchina a stati dichiarativa del progetto fundraising (§6.6.3): draft -> submitted
 * -> approved/rejected -> completed, nessuna transizione libera non elencata in tabella
 * (stessa disciplina di {@see TicketStateMachine}).
 */
final class FundraisingProjectStateMachine
{
    /**
     * @return array<string, list<FundraisingProjectStatus>>
     */
    public static function transitions(): array
    {
        return [
            FundraisingProjectStatus::Draft->value => [FundraisingProjectStatus::Submitted],
            FundraisingProjectStatus::Submitted->value => [
                FundraisingProjectStatus::Approved,
                FundraisingProjectStatus::Rejected,
            ],
            FundraisingProjectStatus::Approved->value => [FundraisingProjectStatus::Completed],
            FundraisingProjectStatus::Rejected->value => [],
            FundraisingProjectStatus::Completed->value => [],
        ];
    }

    public static function canTransitionTo(FundraisingProjectStatus $from, FundraisingProjectStatus $to): bool
    {
        return in_array($to, self::transitions()[$from->value], strict: true);
    }

    public static function authorize(FundraisingProjectStatus $from, FundraisingProjectStatus $to): void
    {
        if (! self::canTransitionTo($from, $to)) {
            throw ValidationException::withMessages([
                'status' => ["La transizione da \"{$from->getLabel()}\" a \"{$to->getLabel()}\" non è ammessa."],
            ]);
        }
    }
}
