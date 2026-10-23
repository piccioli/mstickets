<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Filtro per difficoltà dell'elenco dei sentieri (Fase 10.6, US-1054), voci del design "Sentieri di Sezione". La scala
 * `cai_scale` di osm2cai ha anche varianti ("EEA:PD", "EEA*", "T/E", "E;EE"): conta la prima sigla, quindi "EEA:PD" è
 * EEA e "T/E" è T; sigle fuori dalle quattro (es. "AT") compaiono solo senza filtro. Il valore è quello dell'URL.
 */
enum CaiTrailDifficulty: string implements HasLabel
{
    case Tourist = 't';
    case Hiking = 'e';
    case Expert = 'ee';
    case ExpertEquipped = 'eea';
    case NotSpecified = 'nd';

    public function getLabel(): string
    {
        return match ($this) {
            self::Tourist => 'T · Turistico',
            self::Hiking => 'E · Escursionistico',
            self::Expert => 'EE · Escursionisti esperti',
            self::ExpertEquipped => 'EEA · Esperti con attrezzatura',
            self::NotSpecified => 'Non indicata',
        };
    }

    public function matches(?string $scale): bool
    {
        $code = mb_strtolower(preg_split('/[\s:;\/*]+/', trim((string) $scale))[0] ?? '');

        return $this === self::NotSpecified ? $code === '' : $code === $this->value;
    }
}
