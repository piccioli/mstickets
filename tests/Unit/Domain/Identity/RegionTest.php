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

test('sicaiLetters maps every region to the letters of its Sentiero Italia CAI stages, with L, N and C shared', function (): void {
    $letters = [];

    foreach (Region::cases() as $region) {
        $letters[$region->value] = $region->sicaiLetters();
    }

    expect($letters)->toBe([
        'abruzzo' => ['P'],
        'basilicata' => ['T'],
        'calabria' => ['U'],
        'campania' => ['S'],
        'emilia_romagna' => ['L'],
        'friuli_venezia_giulia' => ['A'],
        'lazio' => ['O'],
        'liguria' => ['G'],
        'lombardia' => ['D'],
        'marche' => ['N'],
        'molise' => ['Q'],
        'piemonte' => ['E'],
        'puglia' => ['R'],
        'sardegna' => ['Z'],
        'sicilia' => ['V'],
        'toscana' => ['L'],
        'trentino_alto_adige' => ['C'],
        'umbria' => ['N'],
        'valle_d_aosta' => ['F'],
        'veneto' => ['B'],
    ]);
});
