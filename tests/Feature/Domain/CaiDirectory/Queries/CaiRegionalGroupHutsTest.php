<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiHutContractStatus;
use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupHuts;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupHutRow;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupHutsData;
use App\Domain\Identity\Enums\Region;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

afterEach(fn () => CarbonImmutable::setTestNow());

/** @param  array<string, mixed>  $contract */
function grHut(string $branchCode, string $name, array $attributes = [], array $contract = []): void
{
    caiHut(array_merge([
        'branch_code' => $branchCode,
        'name' => $name,
        'management' => $contract === [] ? null : ['contract' => $contract],
    ], $attributes));
}

/** @return list<string> */
function grHutNames(CaiRegionalGroupHutsData $data): array
{
    return array_map(fn (CaiRegionalGroupHutRow $row): string => $row->hut->name, $data->huts);
}

test('the huts of a group are those of its sections by prefix, so SAT stays out of the Alto Adige group', function (): void {
    caiSection(['codice_cai' => '9219001', 'name' => 'SEZ. BOLZANO', 'region' => 'TRENTINO-ALTO ADIGE']);
    caiSection(['codice_cai' => '9219002', 'name' => 'SEZ. MERANO', 'region' => 'TRENTINO-ALTO ADIGE']);
    caiSection(['codice_cai' => '9218001', 'name' => 'SAT', 'region' => 'TRENTINO-ALTO ADIGE']);
    grHut('9219001', 'Rifugio Bolzano');
    grHut('9219002', 'Rifugio Merano');
    grHut('9218001', 'Rifugio SAT');

    $data = CaiRegionalGroupHuts::forRegion(Region::TrentinoAltoAdige);

    expect(grHutNames($data))->toBe(['Rifugio Bolzano', 'Rifugio Merano'])
        ->and($data->codiceCai)->toBe('9219000')
        ->and($data->sectionsCount)->toBe(2)
        ->and($data->huts[0]->sectionName)->toBe('SEZ. BOLZANO')
        ->and($data->huts[0]->ownedByGroup)->toBeFalse();
});

test('a hut registered to the group is included with "GR {region}" as its section', function (): void {
    caiSection(['codice_cai' => '9228001', 'name' => 'SEZ. PALERMO', 'region' => 'SICILIA']);
    grHut('9228001', 'Rifugio Palermo');
    grHut('9500046', "Punto d'appoggio GR", ['branch_name' => 'gr sicilia']);
    grHut('9500047', 'Rifugio di un altro GR', ['branch_name' => 'GR Toscana']);
    grHut('9300100', 'Rifugio della Sede Centrale', ['branch_name' => 'GR Sicilia']);

    $data = CaiRegionalGroupHuts::forRegion(Region::Sicilia);
    $owned = collect($data->huts)->firstWhere('ownedByGroup', true);

    expect(grHutNames($data))->toBe(["Punto d'appoggio GR", 'Rifugio Palermo'])
        ->and($owned?->sectionName)->toBe('GR Sicilia')
        ->and($data->total)->toBe(2)
        ->and($data->sectionsWithHutsCount)->toBe(1);
});

test('sections without huts are listed by name, and a hut of the group does not count as a section', function (): void {
    caiSection(['codice_cai' => '9228001', 'name' => 'SEZ. PALERMO', 'region' => 'SICILIA']);
    caiSection(['codice_cai' => '9228002', 'name' => 'SEZ. CATANIA', 'region' => 'SICILIA']);
    caiSection(['codice_cai' => '9228003', 'name' => 'SEZ. ACIREALE', 'region' => 'SICILIA']);
    grHut('9228001', 'Rifugio Palermo');
    grHut('9500046', "Punto d'appoggio GR", ['branch_name' => 'GR Sicilia']);

    $data = CaiRegionalGroupHuts::forRegion(Region::Sicilia);

    expect($data->sectionsCount)->toBe(3)
        ->and($data->sectionsWithHutsCount)->toBe(1)
        ->and($data->sectionsWithoutHuts)->toBe([
            ['codiceCai' => '9228003', 'name' => 'SEZ. ACIREALE'],
            ['codiceCai' => '9228002', 'name' => 'SEZ. CATANIA'],
        ]);
});

test('the summary counts huts, active ones, beds and expiring or expired contracts', function (): void {
    CarbonImmutable::setTestNow('2026-10-23 10:00:00');
    caiSection(['codice_cai' => '9226001', 'name' => 'SEZ. PISA', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'name' => 'SEZ. LUCCA', 'region' => 'TOSCANA']);
    grHut('9226001', 'A', ['beds' => 30], ['end_date' => '2026-06-30']);
    grHut('9226001', 'B', ['beds' => null, 'status' => CaiHutStatus::TemporarilyClosed], ['end_date' => '2027-01-31']);
    grHut('9226002', 'C', ['beds' => 12, 'status' => null], ['end_date' => '2030-01-01']);
    grHut('9226002', 'D', ['beds' => 6]);

    $data = CaiRegionalGroupHuts::forRegion(Region::Toscana);

    expect($data->total)->toBe(4)
        ->and($data->active)->toBe(2)
        ->and($data->beds)->toBe(48)
        ->and($data->expiringContracts)->toBe(1)
        ->and($data->expiredContracts)->toBe(1)
        ->and($data->sectionsWithHutsCount)->toBe(2)
        ->and($data->sectionsWithoutHuts)->toBe([])
        ->and($data->groupName)->toBe('GR Toscana');
});

test('the contract status of a row follows the shared thresholds', function (string $end, CaiHutContractStatus $status, ?string $note): void {
    CarbonImmutable::setTestNow('2026-10-23 10:00:00');
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    grHut('9226001', 'Rifugio', [], ['end_date' => $end]);

    $row = CaiRegionalGroupHuts::forRegion(Region::Toscana)->huts[0];

    expect($row->contractStatus)->toBe($status)
        ->and($row->contractNote)->toBe($note)
        ->and($row->contractEnd?->format('Y-m-d'))->toBe($end);
})->with([
    'yesterday' => ['2026-10-22', CaiHutContractStatus::Expired, 'Scaduto da 1 giorno'],
    'today' => ['2026-10-23', CaiHutContractStatus::Expiring, 'Scade oggi'],
    '+183 days' => ['2027-04-24', CaiHutContractStatus::Expiring, 'Scade tra 6 mesi'],
    '+184 days' => ['2027-04-25', CaiHutContractStatus::Valid, null],
]);

test('a region without sections still has no huts and no code', function (): void {
    grHut('9226001', 'Rifugio');

    $data = CaiRegionalGroupHuts::forRegion(Region::Molise);

    expect($data->codiceCai)->toBeNull()
        ->and($data->huts)->toBe([])
        ->and($data->sectionsCount)->toBe(0)
        ->and($data->total)->toBe(0);
});

test('huts and sections are read with a fixed number of queries', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);

    foreach (range(1, 6) as $i) {
        grHut($i % 2 === 0 ? '9226001' : '9226002', 'Rifugio '.$i, [], ['end_date' => '2027-01-01']);
    }

    DB::enableQueryLog();
    CaiRegionalGroupHuts::forRegion(Region::Toscana);

    // prefisso del GR, Sezioni, rifugi
    expect(DB::getQueryLog())->toHaveCount(3);
});
