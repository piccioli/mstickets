<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CustomerType: string implements HasColor, HasLabel
{
    case Sezione = 'sezione';
    case GruppoRegionale = 'gruppo_regionale';
    case OrganoTecnicoStrutturaOperativa = 'organo_tecnico_struttura_operativa';
    case Generico = 'generico';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sezione => 'Sezione',
            self::GruppoRegionale => 'Gruppo Regionale',
            self::OrganoTecnicoStrutturaOperativa => 'Organo Tecnico Centrale / Struttura Operativa',
            self::Generico => 'Cliente generico',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Sezione => 'primary',
            self::GruppoRegionale => 'info',
            self::OrganoTecnicoStrutturaOperativa => 'warning',
            self::Generico => 'gray',
        };
    }
}
