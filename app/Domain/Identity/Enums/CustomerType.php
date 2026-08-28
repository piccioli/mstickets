<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

enum CustomerType: string
{
    case Sezione = 'sezione';
    case GruppoRegionale = 'gruppo_regionale';
    case OrganoTecnicoStrutturaOperativa = 'organo_tecnico_struttura_operativa';
    case Generico = 'generico';
}
