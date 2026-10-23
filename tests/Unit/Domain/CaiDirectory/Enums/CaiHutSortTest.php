<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiHutSort;
use App\Domain\CaiDirectory\Models\CaiHut;
use Illuminate\Support\Collection;

/** @return Collection<int, CaiHut> */
function hutsToSort(): Collection
{
    return collect([
        ['name' => 'Bivacco 10', 'altitude' => 2500],
        ['name' => 'Ècole des Alpes', 'altitude' => null],
        ['name' => 'bivacco 2', 'altitude' => 2500],
        ['name' => 'Rifugio Zeta', 'altitude' => 1200],
    ])->map(fn (array $attributes): CaiHut => new CaiHut($attributes));
}

test('the name order ignores accents and case and is natural', function (): void {
    expect(CaiHutSort::NameAsc->sort(hutsToSort())->pluck('name')->all())
        ->toBe(['bivacco 2', 'Bivacco 10', 'Ècole des Alpes', 'Rifugio Zeta'])
        ->and(CaiHutSort::NameDesc->sort(hutsToSort())->pluck('name')->all())
        ->toBe(['Rifugio Zeta', 'Ècole des Alpes', 'Bivacco 10', 'bivacco 2']);
});

test('the altitude order puts missing altitudes last and breaks ties by name', function (): void {
    expect(CaiHutSort::AltitudeDesc->sort(hutsToSort())->pluck('name')->all())
        ->toBe(['bivacco 2', 'Bivacco 10', 'Rifugio Zeta', 'Ècole des Alpes'])
        ->and(CaiHutSort::AltitudeAsc->sort(hutsToSort())->pluck('name')->all())
        ->toBe(['Rifugio Zeta', 'bivacco 2', 'Bivacco 10', 'Ècole des Alpes']);
});

test('the headers toggle name and altitude', function (): void {
    expect(CaiHutSort::NameAsc->toggledByName())->toBe(CaiHutSort::NameDesc)
        ->and(CaiHutSort::NameDesc->toggledByName())->toBe(CaiHutSort::NameAsc)
        ->and(CaiHutSort::AltitudeAsc->toggledByName())->toBe(CaiHutSort::NameAsc)
        ->and(CaiHutSort::NameAsc->toggledByAltitude())->toBe(CaiHutSort::AltitudeDesc)
        ->and(CaiHutSort::AltitudeDesc->toggledByAltitude())->toBe(CaiHutSort::AltitudeAsc);
});
