<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\CaiDatapackImporter;
use App\Domain\CaiDirectory\Models\CaiSicaiRestPoint;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Rifugio di fixture con `id` interno della fonte diverso da `id_cai`, come nell'API reale. */
function hutWithSourceId(int $idCai, int $sourceId, string $title): array
{
    return [...hutApiDetail($idCai, $title), 'id' => $sourceId];
}

test('imports rest points with normalized columns and links shelter_id to the imported hut', function (): void {
    $sqlite = makeHutsDatapack([hutWithSourceId(921600507, 163, 'Bozzi Angelino')]);
    writeSicaiRestPointsSnapshot($sqlite, [
        sicaiRestPointApiDetail(4, 'Rifugio Bozzi', ['address_number' => '3'], shelterId: 163),
        sicaiRestPointApiDetail(19, 'Hotel Uno', ['type' => 'Hotel', 'mobilePhone_management_property' => '', 'fixedPhone_management_property' => '0364 000000']),
    ]);

    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_punti_appoggio_sicai'];

    expect($result->read)->toBe(2)->and($result->created)->toBe(2)->and($result->warnings)->toBe([]);

    $bozzi = CaiSicaiRestPoint::query()->findOrFail(4);
    expect($bozzi->name)->toBe('Rifugio Bozzi')
        ->and($bozzi->type)->toBe('Rifugio')
        ->and($bozzi->latitude)->toBe('46.3031850')
        ->and($bozzi->longitude)->toBe('10.5694910')
        ->and($bozzi->region)->toBe('Lombardia')
        ->and($bozzi->municipality)->toBe('Ponte di Legno')
        ->and($bozzi->address)->toBe('loc. Conca del Montozzo 3')
        ->and($bozzi->operator)->toBe('Michele Carganico - CAI Brescia')
        ->and($bozzi->website)->toBe('https://www.facebook.com/rifugiobozzi/')
        ->and($bozzi->email)->toBe('rifugiobozzi@example.test')
        ->and($bozzi->phone)->toBe('339 0000000')
        ->and($bozzi->cai_hut_id_cai)->toBe(921600507)
        ->and($bozzi->hut?->name)->toBe('Bozzi Angelino')
        ->and($bozzi->fields['stages'])->toBe('')
        ->and($bozzi->source_updated_at?->format('Y-m-d H:i'))->toBe('2025-05-16 08:18');

    $hotel = CaiSicaiRestPoint::query()->findOrFail(19);
    expect($hotel->type)->toBe('Hotel')
        ->and($hotel->phone)->toBe('0364 000000')
        ->and($hotel->cai_hut_id_cai)->toBeNull();
});

test('a shelter_id of a hut not imported leaves the link empty with a warning', function (): void {
    $sqlite = makeHutsDatapack([hutWithSourceId(921600507, 163, 'Bozzi Angelino')]);
    writeSicaiRestPointsSnapshot($sqlite, [sicaiRestPointApiDetail(6, 'Rifugio sconosciuto', shelterId: 999)]);

    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_punti_appoggio_sicai'];

    expect(CaiSicaiRestPoint::query()->findOrFail(6)->cai_hut_id_cai)->toBeNull()
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('1 punti di appoggio con shelter_id');
});

test('updates changed rest points and deletes those no longer in the source', function (): void {
    $sqlite = makeHutsDatapack([]);
    writeSicaiRestPointsSnapshot($sqlite, [sicaiRestPointApiDetail(1, 'Ostello A'), sicaiRestPointApiDetail(2, 'Camping B')]);
    app(CaiDatapackImporter::class)->import($sqlite, false);

    writeSicaiRestPointsSnapshot($sqlite, [sicaiRestPointApiDetail(1, 'Ostello A rinominato')]);
    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_punti_appoggio_sicai'];

    expect($result->updated)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and($result->deleted)->toBe(1)
        ->and(CaiSicaiRestPoint::query()->pluck('name')->all())->toBe(['Ostello A rinominato']);
});

test('a second run creates and updates nothing', function (): void {
    $sqlite = makeHutsDatapack([hutWithSourceId(921600507, 163, 'Bozzi Angelino')]);
    writeSicaiRestPointsSnapshot($sqlite, [sicaiRestPointApiDetail(4, 'Rifugio Bozzi', shelterId: 163), sicaiRestPointApiDetail(19, 'Hotel Uno')]);
    app(CaiDatapackImporter::class)->import($sqlite, false);

    $second = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_punti_appoggio_sicai'];

    expect($second->created)->toBe(0)
        ->and($second->updated)->toBe(0)
        ->and($second->deleted)->toBe(0)
        ->and($second->skipped)->toBe(2);
});

test('dry-run counts without writing or deleting', function (): void {
    CaiSicaiRestPoint::create(['id' => 99, 'name' => 'Punto sparito']);
    $sqlite = makeHutsDatapack([]);
    writeSicaiRestPointsSnapshot($sqlite, [sicaiRestPointApiDetail(1, 'Ostello A')]);

    $result = app(CaiDatapackImporter::class)->import($sqlite, true)['snapshot_punti_appoggio_sicai'];

    expect($result->created)->toBe(1)
        ->and($result->deleted)->toBe(1)
        ->and(CaiSicaiRestPoint::query()->pluck('id')->all())->toBe([99]);
});

test('a datapack without the table leaves the rest points untouched', function (): void {
    CaiSicaiRestPoint::create(['id' => 99, 'name' => 'Punto esistente']);
    $sqlite = makeHutsDatapack([]);

    $results = app(CaiDatapackImporter::class)->import($sqlite, false);

    expect($results)->not->toHaveKey('snapshot_punti_appoggio_sicai')
        ->and(CaiSicaiRestPoint::query()->pluck('id')->all())->toBe([99]);
});

test('deleting a hut clears the link of its rest point', function (): void {
    $sqlite = makeHutsDatapack([hutWithSourceId(921600507, 163, 'Bozzi Angelino')]);
    writeSicaiRestPointsSnapshot($sqlite, [sicaiRestPointApiDetail(4, 'Rifugio Bozzi', shelterId: 163)]);
    app(CaiDatapackImporter::class)->import($sqlite, false);

    makeHutsDatapack([], $sqlite);
    writeSicaiRestPointsSnapshot($sqlite, [sicaiRestPointApiDetail(4, 'Rifugio Bozzi', shelterId: 163)]);
    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_punti_appoggio_sicai'];

    expect(CaiSicaiRestPoint::query()->findOrFail(4)->cai_hut_id_cai)->toBeNull()
        ->and($result->warnings[0])->toContain('shelter_id');
});

test('rest points are read-only: staff see them, nobody writes', function (): void {
    $point = CaiSicaiRestPoint::create(['id' => 1, 'name' => 'Ostello A']);
    $staff = userWithPermissions(Permission::CaiDirectoryView);
    $other = User::factory()->create();

    expect($staff->can('view', $point))->toBeTrue()
        ->and($other->can('view', $point))->toBeFalse()
        ->and($staff->can('update', $point))->toBeFalse()
        ->and($staff->can('delete', $point))->toBeFalse()
        ->and($staff->can('create', CaiSicaiRestPoint::class))->toBeFalse();
});
