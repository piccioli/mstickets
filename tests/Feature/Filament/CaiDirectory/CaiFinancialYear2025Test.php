<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Filament\Pages\CaiFinancialYear2025;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

function caiFinancialYearPageFixture(): array
{
    $a = caiSection(['codice_cai' => 'A', 'name' => 'Alfa', 'region' => 'LOMBARDIA']);
    caiDocument(['cai_runts_registration_id' => null, 'cai_section_id' => 'A', 'year' => 2025,
        'document_type' => 'altro', 'title' => 'Conto economico']);
    CaiFinancialStatement::create(['cai_section_id' => 'A', 'year' => 2025, 'net_result' => 5]);

    $b = caiSection(['codice_cai' => 'B', 'name' => 'Beta', 'region' => 'LOMBARDIA']);
    caiDocument(['cai_runts_registration_id' => null, 'cai_section_id' => 'B', 'year' => 2025,
        'document_type' => 'altro', 'title' => 'Stato Patrimoniale']);

    $c = caiSection(['codice_cai' => 'C', 'name' => 'Gamma', 'region' => 'VENETO']);
    CaiFinancialStatement::create(['cai_section_id' => 'C', 'year' => 2025, 'total_assets' => 9]);

    $d = caiSection(['codice_cai' => 'D', 'name' => 'Delta', 'region' => 'VENETO']);

    return compact('a', 'b', 'c', 'd');
}

test('a user without cai-directory.view is denied (403)', function (): void {
    $this->actingAs(userWithPermissions());

    $this->get(CaiFinancialYear2025::getUrl())->assertForbidden();
    expect(CaiFinancialYear2025::canAccess())->toBeFalse();
});

test('a user with cai-directory.view sees all sections ordered by region then name', function (): void {
    $s = caiFinancialYearPageFixture();
    $this->actingAs(userWithPermissions(PermissionEnum::CaiDirectoryView));

    Livewire::test(CaiFinancialYear2025::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$s['a'], $s['b'], $s['d'], $s['c']], inOrder: true);
});

test('each ternary filter narrows the list', function (string $filter, array $expected): void {
    $s = caiFinancialYearPageFixture();
    $this->actingAs(userWithPermissions(PermissionEnum::CaiDirectoryView));

    $keys = collect($expected)->map(fn (string $k) => $s[$k]);
    $others = collect($s)->except($expected);

    Livewire::test(CaiFinancialYear2025::class)
        ->filterTable($filter, true)
        ->assertCanSeeTableRecords($keys)
        ->assertCanNotSeeTableRecords($others);
})->with([
    'income file' => ['has_income_statement_file', ['a']],
    'balance file' => ['has_balance_sheet_file', ['b']],
    'income parsed' => ['income_statement_parsed', ['a']],
    'balance parsed' => ['balance_sheet_parsed', ['c']],
]);

test('the "no" branch of a ternary filter inverts the result', function (): void {
    $s = caiFinancialYearPageFixture();
    $this->actingAs(userWithPermissions(PermissionEnum::CaiDirectoryView));

    Livewire::test(CaiFinancialYear2025::class)
        ->filterTable('has_income_statement_file', false)
        ->assertCanSeeTableRecords([$s['b'], $s['c'], $s['d']])
        ->assertCanNotSeeTableRecords([$s['a']]);
});

test('region and income-parsed filters combine with AND', function (): void {
    $s = caiFinancialYearPageFixture();
    $this->actingAs(userWithPermissions(PermissionEnum::CaiDirectoryView));

    Livewire::test(CaiFinancialYear2025::class)
        ->filterTable('region', 'LOMBARDIA')
        ->filterTable('income_statement_parsed', true)
        ->assertCanSeeTableRecords([$s['a']])
        ->assertCanNotSeeTableRecords([$s['b'], $s['c'], $s['d']]);
});

test('search covers name and codice cai', function (): void {
    $s = caiFinancialYearPageFixture();
    $this->actingAs(userWithPermissions(PermissionEnum::CaiDirectoryView));

    Livewire::test(CaiFinancialYear2025::class)
        ->searchTable('Gamma')
        ->assertCanSeeTableRecords([$s['c']])
        ->assertCanNotSeeTableRecords([$s['a']])
        ->searchTable('D')
        ->assertCanSeeTableRecords([$s['d']]);
});

test('filters with no match show the empty state and the open action targets the allegati tab', function (): void {
    $s = caiFinancialYearPageFixture();
    $this->actingAs(userWithPermissions(PermissionEnum::CaiDirectoryView));

    Livewire::test(CaiFinancialYear2025::class)
        ->filterTable('region', 'VENETO')
        ->filterTable('has_income_statement_file', true)
        ->assertCountTableRecords(0)
        ->assertSee('Nessuna sezione corrisponde ai filtri');

    Livewire::test(CaiFinancialYear2025::class)
        ->assertTableActionHasUrl('open', route('filament.admin.resources.cai-sections.view', ['record' => 'A', 'tab' => 'allegati']), $s['a']);
});
