<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupsQuery;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\CaiRegionalGroups;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

function caiRegionalGroupsFixture(): array
{
    $gr = fn (string $name, Region $region): User => User::factory()->create([
        'name' => $name, 'email' => strtolower($name).'@example.test',
        'customer_type' => CustomerType::GruppoRegionale, 'region' => $region,
    ]);

    $lombardia = $gr('GR Lombardia', Region::Lombardia);
    $toscana = $gr('GR Toscana', Region::Toscana);
    $umbria = $gr('GR Umbria', Region::Umbria);

    // Lombardia: 2 sezioni, 1 con file CE interpretato, 1 con solo SP (file)
    caiSection(['codice_cai' => 'L1', 'name' => 'L1', 'region' => 'LOMBARDIA']);
    caiDocument(['cai_runts_registration_id' => null, 'cai_section_id' => 'L1', 'year' => 2025,
        'document_type' => 'altro', 'title' => 'Conto economico']);
    CaiFinancialStatement::create(['cai_section_id' => 'L1', 'year' => 2025, 'net_result' => 5]);
    caiSection(['codice_cai' => 'L2', 'name' => 'L2', 'region' => 'LOMBARDIA']);
    caiDocument(['cai_runts_registration_id' => null, 'cai_section_id' => 'L2', 'year' => 2025,
        'document_type' => 'altro', 'title' => 'Stato Patrimoniale']);

    // Toscana: 3 sezioni, nessun documento 2025 (documento 2024 ignorato)
    foreach (['T1', 'T2', 'T3'] as $code) {
        caiSection(['codice_cai' => $code, 'name' => $code, 'region' => 'TOSCANA']);
    }
    caiDocument(['cai_runts_registration_id' => null, 'cai_section_id' => 'T1', 'year' => 2024,
        'document_type' => 'altro', 'title' => 'Conto economico']);

    // Umbria: nessuna sezione. EXTRA REGIONE: appartiene a nessun GR.
    caiSection(['codice_cai' => 'X1', 'name' => 'X1', 'region' => 'EXTRA REGIONE']);
    caiDocument(['cai_runts_registration_id' => null, 'cai_section_id' => 'X1', 'year' => 2025,
        'document_type' => 'altro', 'title' => 'Conto economico']);

    return compact('lombardia', 'toscana', 'umbria');
}

test('a user without cai-directory.view is denied (403)', function (): void {
    $this->actingAs(userWithPermissions());

    $this->get(CaiRegionalGroups::getUrl())->assertForbidden();
});

test('counts are computed per region and extra-region sections belong to no group', function (): void {
    $g = caiRegionalGroupsFixture();
    $this->actingAs(userWithPermissions(PermissionEnum::CaiDirectoryView));

    Livewire::test(CaiRegionalGroups::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$g['lombardia'], $g['toscana'], $g['umbria']], inOrder: true);

    $rows = caiRegionalGroupsRows();
    expect($rows['GR Lombardia'])->toBe([2, 2, 1, 100])
        ->and($rows['GR Toscana'])->toBe([3, 0, 0, 0])
        ->and($rows['GR Umbria'])->toBe([0, 0, 0, null]);
});

function caiRegionalGroupsRows(): array
{
    return CaiRegionalGroupsQuery::forYear(2025)->get()
        ->mapWithKeys(fn (User $u): array => [$u->name => [
            (int) $u->sections_count, (int) $u->with_financials_count, (int) $u->income_parsed_count,
            $u->coverage_percent === null ? null : (int) $u->coverage_percent,
        ]])->all();
}

test('the number of queries does not grow with the number of groups', function (): void {
    caiRegionalGroupsFixture();

    DB::enableQueryLog();
    CaiRegionalGroupsQuery::forYear(2025)->get();
    $few = count(DB::getQueryLog());
    DB::flushQueryLog();

    foreach ([Region::Veneto, Region::Piemonte, Region::Lazio] as $region) {
        User::factory()->create(['customer_type' => CustomerType::GruppoRegionale, 'region' => $region]);
    }
    DB::flushQueryLog();
    CaiRegionalGroupsQuery::forYear(2025)->get();

    expect(count(DB::getQueryLog()))->toBe($few)->toBe(1);
});

test('only gruppo regionale users are listed and the region filter narrows the list', function (): void {
    $g = caiRegionalGroupsFixture();
    $other = User::factory()->create(['customer_type' => CustomerType::Sezione, 'region' => Region::Toscana]);
    $this->actingAs(userWithPermissions(PermissionEnum::CaiDirectoryView));

    Livewire::test(CaiRegionalGroups::class)
        ->assertCanNotSeeTableRecords([$other])
        ->filterTable('region', Region::Toscana->value)
        ->assertCanSeeTableRecords([$g['toscana']])
        ->assertCanNotSeeTableRecords([$g['lombardia'], $g['umbria']]);
});

test('numeric columns are sortable', function (): void {
    $g = caiRegionalGroupsFixture();
    $this->actingAs(userWithPermissions(PermissionEnum::CaiDirectoryView));

    Livewire::test(CaiRegionalGroups::class)
        ->sortTable('sections_count', 'desc')
        ->assertCanSeeTableRecords([$g['toscana'], $g['lombardia'], $g['umbria']], inOrder: true)
        ->sortTable('coverage_percent', 'desc')
        ->assertCanSeeTableRecords([$g['lombardia'], $g['toscana']], inOrder: true);
});

test('the open-user action is shown only with user.view', function (): void {
    $g = caiRegionalGroupsFixture();

    $this->actingAs(userWithPermissions(PermissionEnum::CaiDirectoryView));
    Livewire::test(CaiRegionalGroups::class)
        ->assertTableActionHidden('openUser', $g['toscana']);

    $this->actingAs(userWithPermissions(PermissionEnum::CaiDirectoryView, PermissionEnum::UserView));
    Livewire::test(CaiRegionalGroups::class)
        ->assertTableActionVisible('openUser', $g['toscana'])
        ->assertTableActionHasUrl('openUser', route('filament.admin.resources.users.view', ['record' => $g['toscana']]), $g['toscana']);
});

test('the empty state is shown when there are no regional groups', function (): void {
    $this->actingAs(userWithPermissions(PermissionEnum::CaiDirectoryView));

    Livewire::test(CaiRegionalGroups::class)
        ->assertCountTableRecords(0)
        ->assertSee('Nessun gruppo regionale');
});
