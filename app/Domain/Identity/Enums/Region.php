<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

enum Region: string
{
    case Abruzzo = 'abruzzo';
    case Basilicata = 'basilicata';
    case Calabria = 'calabria';
    case Campania = 'campania';
    case EmiliaRomagna = 'emilia_romagna';
    case FriuliVeneziaGiulia = 'friuli_venezia_giulia';
    case Lazio = 'lazio';
    case Liguria = 'liguria';
    case Lombardia = 'lombardia';
    case Marche = 'marche';
    case Molise = 'molise';
    case Piemonte = 'piemonte';
    case Puglia = 'puglia';
    case Sardegna = 'sardegna';
    case Sicilia = 'sicilia';
    case Toscana = 'toscana';
    case TrentinoAltoAdige = 'trentino_alto_adige';
    case Umbria = 'umbria';
    case ValleDAosta = 'valle_d_aosta';
    case Veneto = 'veneto';

    public function label(): string
    {
        return match ($this) {
            self::Abruzzo => 'Abruzzo',
            self::Basilicata => 'Basilicata',
            self::Calabria => 'Calabria',
            self::Campania => 'Campania',
            self::EmiliaRomagna => 'Emilia-Romagna',
            self::FriuliVeneziaGiulia => 'Friuli-Venezia Giulia',
            self::Lazio => 'Lazio',
            self::Liguria => 'Liguria',
            self::Lombardia => 'Lombardia',
            self::Marche => 'Marche',
            self::Molise => 'Molise',
            self::Piemonte => 'Piemonte',
            self::Puglia => 'Puglia',
            self::Sardegna => 'Sardegna',
            self::Sicilia => 'Sicilia',
            self::Toscana => 'Toscana',
            self::TrentinoAltoAdige => 'Trentino-Alto Adige',
            self::Umbria => 'Umbria',
            self::ValleDAosta => "Valle d'Aosta",
            self::Veneto => 'Veneto',
        };
    }

    /**
     * Lettere delle tappe del Sentiero Italia CAI della regione (US-1094). L, N e C sono condivise: per decisione del
     * committente Toscana ed Emilia-Romagna vedono entrambe tutte le tappe L, Umbria e Marche tutte le N.
     *
     * @return list<string>
     */
    public function sicaiLetters(): array
    {
        return match ($this) {
            self::FriuliVeneziaGiulia => ['A'],
            self::Veneto => ['B'],
            self::TrentinoAltoAdige => ['C'],
            self::Lombardia => ['D'],
            self::Piemonte => ['E'],
            self::ValleDAosta => ['F'],
            self::Liguria => ['G'],
            self::Toscana, self::EmiliaRomagna => ['L'],
            self::Umbria, self::Marche => ['N'],
            self::Lazio => ['O'],
            self::Abruzzo => ['P'],
            self::Molise => ['Q'],
            self::Puglia => ['R'],
            self::Campania => ['S'],
            self::Basilicata => ['T'],
            self::Calabria => ['U'],
            self::Sicilia => ['V'],
            self::Sardegna => ['Z'],
        };
    }
}
