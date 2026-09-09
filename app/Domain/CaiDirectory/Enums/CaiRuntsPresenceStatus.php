<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Esito dell'ultima verifica leggera di presenza RUNTS (Fase 9, `cai:check-runts-presence`).
 * `null` sulla colonna `cai_sections.runts_presence_status` (nessun case per questo) significa
 * "mai verificata" — distinto sia da `Timeout` (una verifica è stata tentata ma non ha ottenuto
 * una risposta definitiva in tempo) sia dagli esiti definitivi `Registered`/`NotRegistered`. Un
 * booleano non basta a rappresentare questi 4 stati (3 case + null), da qui l'enum.
 */
enum CaiRuntsPresenceStatus: string implements HasColor, HasLabel
{
    case Registered = 'registered';
    case NotRegistered = 'not_registered';
    case Timeout = 'timeout';

    public function getLabel(): string
    {
        return match ($this) {
            self::Registered => 'Presente su RUNTS',
            self::NotRegistered => 'Non presente su RUNTS',
            self::Timeout => 'Verifica in timeout',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Registered => 'success',
            self::NotRegistered => 'danger',
            self::Timeout => 'warning',
        };
    }
}
