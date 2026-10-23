<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Stato del rifugio di rifugi.cai.it (campo `status`, US-1031): i valori sono il testo della fonte, colori della
 * pillola come nella tabella `S` del design. Un valore della fonte non elencato qui (es. "Rudere") resta `null` sulla
 * colonna e si legge in `cai_huts.fields`.
 */
enum CaiHutStatus: string implements HasLabel
{
    case Active = 'In attività';
    case TemporarilyClosed = 'Momentaneamente chiuso';
    case UnderRenovation = 'In ristrutturazione';
    case Unusable = 'Immobile non agibile';
    case CustodyEnded = 'Cessazione affido';
    case Sold = 'Alienato';

    public function getLabel(): string
    {
        return $this->value;
    }

    /** Sfondo della pillola di stato. */
    public function background(): string
    {
        return match ($this) {
            self::Active => '#e3f4ee',
            self::TemporarilyClosed => '#fdf1dc',
            self::UnderRenovation => '#e5eef8',
            self::Unusable => '#fbe7e5',
            self::CustodyEnded => '#f0ebf7',
            self::Sold => '#eef0f3',
        };
    }

    /** Colore del testo della pillola di stato. */
    public function foreground(): string
    {
        return match ($this) {
            self::Active => '#0f7d63',
            self::TemporarilyClosed => '#8a5600',
            self::UnderRenovation => '#2f5f93',
            self::Unusable => '#b42318',
            self::CustodyEnded => '#6a4a99',
            self::Sold => '#5b6475',
        };
    }
}
