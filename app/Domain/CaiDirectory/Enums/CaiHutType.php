<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Tipo di rifugio di rifugi.cai.it (campo `type`, US-1031): i valori sono il testo della fonte. Colore e lettera del
 * marker come nella tabella `T` del design `docs/project/design/fase-10/rifugi-di-sezione.dc.html`.
 */
enum CaiHutType: string implements HasLabel
{
    case Managed = 'Rifugio custodito';
    case Unmanaged = 'Rifugio incustodito';
    case Bivouac = 'Bivacco';
    case SocialHut = 'Capanna sociale';
    case SupportPoint = "Punto d'appoggio";

    public function getLabel(): string
    {
        return $this->value;
    }

    /** Colore esadecimale del tipo (marker, pallino dei filtri). */
    public function color(): string
    {
        return match ($this) {
            self::Managed => '#17a180',
            self::Unmanaged => '#2f6fb0',
            self::Bivouac => '#d9822b',
            self::SocialHut => '#8a5cc2',
            self::SupportPoint => '#5b6475',
        };
    }

    /** Chiave breve del tipo nell'URL dei filtri (`?tipo[]=bivacco`). */
    public function key(): string
    {
        return match ($this) {
            self::Managed => 'custodito',
            self::Unmanaged => 'incustodito',
            self::Bivouac => 'bivacco',
            self::SocialHut => 'capanna',
            self::SupportPoint => 'appoggio',
        };
    }

    public static function fromKey(?string $key): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->key() === $key) {
                return $case;
            }
        }

        return null;
    }

    /** Lettera nel marker della mappa. */
    public function letter(): string
    {
        return match ($this) {
            self::Managed => 'R',
            self::Unmanaged => 'I',
            self::Bivouac => 'B',
            self::SocialHut => 'C',
            self::SupportPoint => 'P',
        };
    }
}
