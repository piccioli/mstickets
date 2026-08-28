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
}
