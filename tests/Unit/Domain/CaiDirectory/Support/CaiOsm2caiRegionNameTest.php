<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Support\CaiOsm2caiRegionName;
use App\Domain\Identity\Enums\Region;

test('matches every Italian region name of the osm2cai dump to the Region enum', function (string $name, Region $region): void {
    expect(CaiOsm2caiRegionName::toRegion($name))->toBe($region);
})->with([
    // `regions.name` del dump di settembre 2026 (le 20 regioni con `code` SICAI valorizzato).
    ['Abruzzo', Region::Abruzzo],
    ['Basilicata', Region::Basilicata],
    ['Calabria', Region::Calabria],
    ['Campania', Region::Campania],
    ['Emilia-Romagna', Region::EmiliaRomagna],
    ['Friuli-Venezia Giulia', Region::FriuliVeneziaGiulia],
    ['Lazio', Region::Lazio],
    ['Liguria', Region::Liguria],
    ['Lombardia', Region::Lombardia],
    ['Marche', Region::Marche],
    ['Molise', Region::Molise],
    ['Piemonte', Region::Piemonte],
    ['Puglia', Region::Puglia],
    ['Sardigna/Sardegna', Region::Sardegna],
    ['Sicilia', Region::Sicilia],
    ['Toscana', Region::Toscana],
    ['Trentino-Alto Adige/Südtirol', Region::TrentinoAltoAdige],
    ['Umbria', Region::Umbria],
    ["Valle d'Aosta / Vallée d'Aoste", Region::ValleDAosta],
    ['Veneto', Region::Veneto],
    // Varianti di scrittura: maiuscole, spazi e trattini, apostrofo tipografico.
    ['Friuli Venezia Giulia', Region::FriuliVeneziaGiulia],
    ['EMILIA ROMAGNA', Region::EmiliaRomagna],
    ['Trentino Alto Adige', Region::TrentinoAltoAdige],
    ['Valle d’Aosta', Region::ValleDAosta],
    [' Sardegna ', Region::Sardegna],
]);

test('does not match the foreign regions of the dump', function (string $name): void {
    expect(CaiOsm2caiRegionName::toRegion($name))->toBeNull();
})->with([
    'Auvergne-Rhône-Alpes', 'Corse', 'Graubünden/Grischun/Grigioni', 'Istarska županija', 'Kärnten',
    "Provence-Alpes-Côte d'Azur", 'Salzburg', 'Splitsko-dalmatinska županija', 'Ticino', 'Tirol', 'Valais/Wallis', '',
]);

test('matches each Region label to itself', function (): void {
    foreach (Region::cases() as $region) {
        expect(CaiOsm2caiRegionName::toRegion($region->label()))->toBe($region);
    }
});
