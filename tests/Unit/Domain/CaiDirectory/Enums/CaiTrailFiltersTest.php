<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiTrailDifficulty;
use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailSort;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Models\CaiTrail;
use Illuminate\Support\Collection;

/** @param  array<string, mixed>  $attributes */
function sortableTrail(int $id, array $attributes = []): CaiTrail
{
    return new CaiTrail(array_merge([
        'id' => $id,
        'ref' => (string) $id,
        'length_km' => '1.000',
        'status' => CaiTrailStatus::Validated,
        'practicability' => CaiTrailPracticability::Practicable,
    ], $attributes));
}

/**
 * @param  list<CaiTrail>  $trails
 * @return list<int>
 */
function sortedTrailIds(CaiTrailSort $sort, array $trails): array
{
    return $sort->sort(new Collection($trails))->map(fn (CaiTrail $trail): int => $trail->id)->all();
}

test('the difficulty filter reads the first code of the osm2cai scale', function (CaiTrailDifficulty $difficulty, ?string $scale, bool $matches): void {
    expect($difficulty->matches($scale))->toBe($matches);
})->with([
    'E' => [CaiTrailDifficulty::Hiking, 'E', true],
    'E non è EE' => [CaiTrailDifficulty::Hiking, 'EE', false],
    'EEA:PD è EEA' => [CaiTrailDifficulty::ExpertEquipped, 'EEA:PD', true],
    'EEA* è EEA' => [CaiTrailDifficulty::ExpertEquipped, 'EEA*', true],
    'T/E è T' => [CaiTrailDifficulty::Tourist, 'T/E', true],
    'E;EE è E' => [CaiTrailDifficulty::Hiking, 'E;EE', true],
    'AT non è T' => [CaiTrailDifficulty::Tourist, 'AT', false],
    'minuscolo' => [CaiTrailDifficulty::Expert, 'ee', true],
    'non indicata: null' => [CaiTrailDifficulty::NotSpecified, null, true],
    'non indicata: vuota' => [CaiTrailDifficulty::NotSpecified, '  ', true],
    'non indicata: E' => [CaiTrailDifficulty::NotSpecified, 'E', false],
]);

test('the practicability keys of the URL round trip', function (): void {
    foreach (CaiTrailPracticability::cases() as $case) {
        expect(CaiTrailPracticability::fromKey($case->key()))->toBe($case);
    }

    expect(CaiTrailPracticability::fromKey('boh'))->toBeNull()
        ->and(CaiTrailPracticability::fromKey(null))->toBeNull();
});

test('the number sort is natural, case insensitive and keeps the missing numbers last', function (): void {
    $trails = [
        sortableTrail(1, ['ref' => '10']),
        sortableTrail(2, ['ref' => null]),
        sortableTrail(3, ['ref' => '2']),
        sortableTrail(4, ['ref' => '2b']),
        sortableTrail(5, ['ref' => '2A']),
    ];

    expect(sortedTrailIds(CaiTrailSort::NumberAsc, $trails))->toBe([3, 5, 4, 1, 2])
        ->and(sortedTrailIds(CaiTrailSort::NumberDesc, $trails))->toBe([1, 4, 5, 3, 2]);
});

test('the km and status sorts keep the missing lengths last and break ties by number', function (): void {
    $trails = [
        sortableTrail(1, ['ref' => '9', 'length_km' => '4.200', 'status' => CaiTrailStatus::Difficulty]),
        sortableTrail(2, ['ref' => '3', 'length_km' => null, 'status' => CaiTrailStatus::Validated]),
        sortableTrail(3, ['ref' => '7', 'length_km' => '4.200', 'status' => CaiTrailStatus::Validated]),
        sortableTrail(4, ['ref' => '1', 'length_km' => '0.800', 'status' => CaiTrailStatus::PreValidated]),
    ];

    expect(sortedTrailIds(CaiTrailSort::KmDesc, $trails))->toBe([3, 1, 4, 2])
        ->and(sortedTrailIds(CaiTrailSort::KmAsc, $trails))->toBe([4, 3, 1, 2])
        ->and(sortedTrailIds(CaiTrailSort::StatusDesc, $trails))->toBe([2, 3, 4, 1])
        ->and(sortedTrailIds(CaiTrailSort::StatusAsc, $trails))->toBe([1, 4, 2, 3]);
});

test('a header click toggles the active column and starts a new one in the design direction', function (): void {
    expect(CaiTrailSort::NumberAsc->toggled('numero'))->toBe(CaiTrailSort::NumberDesc)
        ->and(CaiTrailSort::NumberDesc->toggled('numero'))->toBe(CaiTrailSort::NumberAsc)
        ->and(CaiTrailSort::NumberAsc->toggled('km'))->toBe(CaiTrailSort::KmDesc)
        ->and(CaiTrailSort::KmDesc->toggled('km'))->toBe(CaiTrailSort::KmAsc)
        ->and(CaiTrailSort::KmAsc->toggled('stato'))->toBe(CaiTrailSort::StatusDesc)
        ->and(CaiTrailSort::StatusDesc->toggled('stato'))->toBe(CaiTrailSort::StatusAsc)
        ->and(CaiTrailSort::StatusAsc->toggled('altro'))->toBe(CaiTrailSort::NumberAsc);
});
