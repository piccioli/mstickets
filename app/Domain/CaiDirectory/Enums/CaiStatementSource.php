<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Da dove viene il bilancio (o, per "Solo PDF", il documento) di una Sezione nel consolidato del GR (Fase 10.3):
 * dall'iscrizione RUNTS oppure caricato a mano (collegato direttamente alla Sezione o con `source = manual`).
 */
enum CaiStatementSource: string implements HasLabel
{
    case Runts = 'runts';
    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Runts => 'RUNTS',
            self::Manual => 'Caricato a mano',
        };
    }
}
