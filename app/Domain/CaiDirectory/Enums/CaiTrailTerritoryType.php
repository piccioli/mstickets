<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Enums;

/**
 * Livello di territorio delle statistiche dei sentieri (`cai_trail_territory_km.territory_type`, US-1062): stessi valori
 * della colonna `territory_type` di `snap_osm2cai_territory_km`.
 */
enum CaiTrailTerritoryType: string
{
    case Region = 'region';
    case Province = 'province';
    case Sector = 'sector';
}
