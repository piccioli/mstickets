<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Stato di accatastamento osm2cai del sentiero (`osm2cai_status`, US-1052): nomi del committente e colori della
 * tabella `SDA` del design "Sentieri di Sezione". Si importano solo i sentieri 1–4.
 */
enum CaiTrailStatus: int implements HasLabel
{
    case Difficulty = 1;
    case Reconnaissance = 2;
    case PreValidated = 3;
    case Validated = 4;

    public function getLabel(): string
    {
        return match ($this) {
            self::Difficulty => '1 · Difficoltà',
            self::Reconnaissance => '2 · Ricognizione',
            self::PreValidated => '3 · Pre-validato',
            self::Validated => '4 · Validato',
        };
    }

    /** Colore della linea sulla mappa, del segmento della barra e del pallino della pillola. */
    public function color(): string
    {
        return match ($this) {
            self::Difficulty => '#e8b500',
            self::Reconnaissance => '#f07c22',
            self::PreValidated => '#a24cc7',
            self::Validated => '#2563c9',
        };
    }

    /** Testo sopra {@see color()} (barra dei km): scuro sul giallo, bianco sugli altri. */
    public function foreground(): string
    {
        return $this === self::Difficulty ? '#3c3000' : '#ffffff';
    }
}
