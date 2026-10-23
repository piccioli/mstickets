<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Support\CaiSicaiStageCode;

test('reads letter, number, variant and code from the OSM name of the trail', function (string $name, string $itinerary, string $letter, int $number, ?string $variant, string $code, string $sortKey): void {
    $stage = CaiSicaiStageCode::parse($name, null, $itinerary, 'SI');

    expect($stage->letter)->toBe($letter)
        ->and($stage->number)->toBe($number)
        ->and($stage->variant)->toBe($variant)
        ->and($stage->code)->toBe($code)
        ->and($stage->sortKey)->toBe($sortKey);
})->with([
    'F05' => ['Sentiero Italia - Tappa F05', "Sentiero Italia - Valle d'Aosta F00", 'F', 5, null, 'F05', 'F005'],
    'D18S' => ['Sentiero Italia - Tappa D18S', 'Sentiero Italia - Lombardia D00', 'D', 18, 'S', 'D18S', 'D018S'],
    'S17S' => ['Sentiero Italia - Tappa S17S', 'Sentiero Italia - Campania S00', 'S', 17, 'S', 'S17S', 'S017S'],
    'SI L01AP' => ['Sentiero Italia - Tappa SI L01AP', 'Sentiero Italia - Toscana/Emilia-Romagna L00', 'L', 1, 'AP', 'L01AP', 'L001AP'],
]);

test('without a name the code is read from the OSM description, the name winning when both have one', function (): void {
    $fromDescription = CaiSicaiStageCode::parse(null, 'Sentiero Italia - Tappa L02N, Poggio Tre Vescovi', 'Sentiero Italia - Toscana/Emilia-Romagna L00', 'SI');
    $nameWins = CaiSicaiStageCode::parse('Sentiero Italia - Tappa Q12', 'Sentiero Italia - Tappa Q13', 'Sentiero Italia - Molise Q00', 'SI');

    expect([$fromDescription->letter, $fromDescription->number, $fromDescription->variant, $fromDescription->code])->toBe(['L', 2, 'N', 'L02N'])
        ->and($nameWins->code)->toBe('Q12');
});

test('without a code in the OSM tags the letter comes from the itinerary and the code from the ref', function (?string $name, ?string $ref, string $code): void {
    $stage = CaiSicaiStageCode::parse($name, 'High Way of the Ligurian Mountains - Stage 16', 'Sentiero Italia - Liguria G00', $ref);

    expect($stage->letter)->toBe('G')
        ->and($stage->number)->toBeNull()
        ->and($stage->variant)->toBeNull()
        ->and($stage->code)->toBe($code)
        ->and($stage->sortKey)->toBe('G999 '.$code);
})->with([
    'nameless trail' => [null, 'SI', 'SI'],
    'Alta Via AV16' => ['Colla di San Giacomo - Colle di Cadibona', 'AV16', 'AV16'],
    'no ref' => ['', null, '—'],
    'blank ref' => [null, '  ', '—'],
]);

test('an itinerary without the "<letter>00" suffix leaves the letter empty and sorts last', function (): void {
    $stage = CaiSicaiStageCode::parse('Sentiero Italia - variante', null, 'Sentiero Italia', null);

    expect($stage->letter)->toBeNull()
        ->and($stage->code)->toBe('—')
        ->and($stage->sortKey)->toBe('~999 —');
});

test('the sort key orders by letter, number and variant with unnumbered stages at the end of their letter', function (): void {
    $keys = [
        CaiSicaiStageCode::parse(null, null, 'Sentiero Italia - Lombardia D00', 'SI')->sortKey,
        CaiSicaiStageCode::parse('Sentiero Italia - Tappa D19', null, 'x', null)->sortKey,
        CaiSicaiStageCode::parse('Sentiero Italia - Tappa E01', null, 'x', null)->sortKey,
        CaiSicaiStageCode::parse('Sentiero Italia - Tappa D18S', null, 'x', null)->sortKey,
        CaiSicaiStageCode::parse('Sentiero Italia - Tappa D18', null, 'x', null)->sortKey,
        CaiSicaiStageCode::parse('Sentiero Italia - Tappa D02', null, 'x', null)->sortKey,
    ];
    sort($keys, SORT_STRING);

    expect($keys)->toBe(['D002', 'D018', 'D018S', 'D019', 'D999 SI', 'E001']);
});
