<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\Identity\Enums\Region;

/**
 * Abbina il nome di una regione del catasto osm2cai (`regions.name`) all'enum {@see Region} (US-1061). I nomi della
 * fonte vengono da OSM: alcuni sono bilingui separati da "/" ("Trentino-Alto Adige/Südtirol", "Sardigna/Sardegna",
 * "Valle d'Aosta / Vallée d'Aoste"); `regions` contiene anche territori esteri (Ticino, Tirol, Corse…), che non si
 * abbinano. Il confronto ignora maiuscole, accenti, trattini, apostrofi e spazi, ed è vero se una qualunque delle parti
 * separate da "/" coincide con l'etichetta della regione.
 */
final class CaiOsm2caiRegionName
{
    public static function toRegion(string $name): ?Region
    {
        $parts = array_map(self::canonical(...), explode('/', $name));

        foreach (Region::cases() as $region) {
            if (in_array(self::canonical($region->label()), $parts, true)) {
                return $region;
            }
        }

        return null;
    }

    private static function canonical(string $value): string
    {
        $ascii = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value);

        return preg_replace('/[^a-z]/', '', is_string($ascii) ? $ascii : mb_strtolower($value)) ?? '';
    }
}
