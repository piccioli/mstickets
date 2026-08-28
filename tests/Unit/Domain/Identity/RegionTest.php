<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Region;

test('contains exactly le 20 regioni italiane ufficiali (Trentino-Alto Adige unificato)', function (): void {
    expect(Region::cases())->toHaveCount(20);
});

test('every case has a non-empty label', function (): void {
    foreach (Region::cases() as $region) {
        expect($region->label())->not->toBeEmpty();
    }
});

test('label restituisce il nome italiano corretto per i casi con grafia particolare', function (): void {
    expect(Region::EmiliaRomagna->label())->toBe('Emilia-Romagna')
        ->and(Region::FriuliVeneziaGiulia->label())->toBe('Friuli-Venezia Giulia')
        ->and(Region::TrentinoAltoAdige->label())->toBe('Trentino-Alto Adige')
        ->and(Region::ValleDAosta->label())->toBe("Valle d'Aosta");
});
