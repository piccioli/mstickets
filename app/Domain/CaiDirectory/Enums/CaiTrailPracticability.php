<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Percorribilità del sentiero (US-1052), ricavata da `issues_status` di osm2cai con {@see fromIssuesStatus()}; colori
 * della tabella `PER` del design "Sentieri di Sezione".
 */
enum CaiTrailPracticability: string implements HasLabel
{
    case Practicable = 'percorribile';
    case WithDifficulty = 'percorribile con difficoltà';
    case NotPracticable = 'non percorribile';
    case Unknown = 'sconosciuto';

    /**
     * osm2cai scrive "percorribile parzialmente" per la percorribilità con difficoltà; vuoto o valore non
     * riconosciuto → sconosciuto.
     */
    public static function fromIssuesStatus(?string $issuesStatus): self
    {
        return match (mb_strtolower(trim((string) $issuesStatus))) {
            'percorribile' => self::Practicable,
            'percorribile parzialmente', 'percorribile con difficoltà', 'percorribile con difficolta' => self::WithDifficulty,
            'non percorribile' => self::NotPracticable,
            default => self::Unknown,
        };
    }

    /** Chiave nell'URL del filtro dell'elenco (`?percorribilita=con-difficolta`, US-1054). */
    public function key(): string
    {
        return match ($this) {
            self::Practicable => 'percorribile',
            self::WithDifficulty => 'con-difficolta',
            self::NotPracticable => 'non-percorribile',
            self::Unknown => 'sconosciuto',
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

    public function getLabel(): string
    {
        return match ($this) {
            self::Practicable => 'Percorribile',
            self::WithDifficulty => 'Percorribile con difficoltà',
            self::NotPracticable => 'Non percorribile',
            self::Unknown => 'Sconosciuto',
        };
    }

    /** Sfondo della pillola. */
    public function background(): string
    {
        return match ($this) {
            self::Practicable => '#e3f4ee',
            self::WithDifficulty => '#fdf1dc',
            self::NotPracticable => '#fbe7e5',
            self::Unknown => '#eef0f3',
        };
    }

    /** Testo della pillola. */
    public function foreground(): string
    {
        return match ($this) {
            self::Practicable => '#0f7d63',
            self::WithDifficulty => '#8a5600',
            self::NotPracticable => '#b42318',
            self::Unknown => '#5b6475',
        };
    }

    /** Pallino della pillola e della legenda. */
    public function dot(): string
    {
        return match ($this) {
            self::Practicable => '#17a180',
            self::WithDifficulty => '#e0a526',
            self::NotPracticable => '#d64545',
            self::Unknown => '#a6aebb',
        };
    }

    /** Titolo dell'avviso della scheda del sentiero (US-1055): solo non percorribile o con difficoltà, altrimenti `null`. */
    public function alertTitle(): ?string
    {
        return match ($this) {
            self::NotPracticable => 'Sentiero non percorribile',
            self::WithDifficulty => 'Sentiero percorribile con difficoltà',
            default => null,
        };
    }

    /** Bordo dell'avviso della scheda (sfondo e testo: {@see background()} / {@see foreground()}). */
    public function alertBorder(): string
    {
        return match ($this) {
            self::NotPracticable => '#f3c4bf',
            self::WithDifficulty => '#f3d9a6',
            default => '#dfe3e8',
        };
    }
}
