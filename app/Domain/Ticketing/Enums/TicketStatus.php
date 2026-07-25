<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Valori identici al v1 (§5.3 del PRD): non complicano l'ETL e mantengono la continuità dei
 * riferimenti. Il case si chiama `Testing` (nel v1 era `Test`), il valore resta `testing`.
 */
enum TicketStatus: string implements HasColor, HasIcon, HasLabel
{
    case New = 'new';
    case Backlog = 'backlog';
    case Assigned = 'assigned';
    case Todo = 'todo';
    case Progress = 'progress';
    case Testing = 'testing';
    case Tested = 'tested';
    case Released = 'released';
    case Done = 'done';
    case Problem = 'problem';
    case Waiting = 'waiting';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Nuovo',
            self::Backlog => 'Backlog',
            self::Assigned => 'Assegnato',
            self::Todo => 'Da fare',
            self::Progress => 'In lavorazione',
            self::Testing => 'In test',
            self::Tested => 'Testato',
            self::Released => 'Rilasciato',
            self::Done => 'Completato',
            self::Problem => 'Problema',
            self::Waiting => 'In attesa',
            self::Rejected => 'Rifiutato',
        };
    }

    /**
     * Nomi di colore Filament registrati in AdminPanelProvider (§8.3), non hex grezzi.
     * Il mockup importato (US-004/docs/design-system.md) definisce un badge dedicato solo
     * per 6 dei 12 stati: per gli altri si sceglie la categoria semantica più vicina, come
     * indicato nel gap esplicito di design-system.md (nessun colore inventato).
     */
    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Backlog => 'gray',
            self::Assigned, self::Todo, self::Progress, self::Testing, self::Waiting => 'warning',
            self::Tested, self::Released, self::Done => 'success',
            self::Problem, self::Rejected => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::New => 'heroicon-o-sparkles',
            self::Backlog => 'heroicon-o-clock',
            self::Assigned => 'heroicon-o-user',
            self::Todo => 'heroicon-o-clipboard-document-list',
            self::Progress => 'heroicon-o-bolt',
            self::Testing => 'heroicon-o-beaker',
            self::Tested => 'heroicon-o-check-circle',
            self::Released => 'heroicon-o-globe-alt',
            self::Done => 'heroicon-o-check',
            self::Problem => 'heroicon-o-exclamation-triangle',
            self::Waiting => 'heroicon-o-pause-circle',
            self::Rejected => 'heroicon-o-x-circle',
        };
    }
}
