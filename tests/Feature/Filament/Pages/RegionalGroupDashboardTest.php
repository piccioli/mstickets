<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\CustomerDashboard;
use App\Filament\Pages\CustomerRegionalSectionsDashboard;
use App\Filament\Pages\RegionalGroupDashboard;
use App\Filament\Pages\RegionalGroupFinancialStatements;
use App\Filament\Pages\RegionalGroupHuts;
use App\Filament\Pages\RegionalGroupMembers;
use App\Filament\Pages\RegionalGroupSicaiStages;
use App\Filament\Pages\RegionalGroupTrails;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

function regionalGroupDashboardCustomer(
    ?Region $region = Region::Toscana,
    ?CustomerType $type = CustomerType::GruppoRegionale,
    string $name = 'GR Toscana',
): User {
    $customer = withRole(User::factory()->create(['name' => $name]), UserRole::Customer);
    $customer->forceFill(['customer_type' => $type, 'region' => $region])->save();

    return $customer;
}

test('a regional group with sections shows the GR code, consolidated statements and members', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA', 'members_count' => 1000]);
    caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA', 'members_count' => 248]);
    caiSection(['codice_cai' => '9226003', 'region' => 'TOSCANA', 'members_count' => null]);
    CaiFinancialStatement::create(['cai_section_id' => '9226001', 'year' => 2025,
        'total_revenues' => 100000, 'total_expenses' => 90000, 'net_result' => 10000,
        'net_equity' => 50000, 'total_assets' => 120000]);
    CaiFinancialStatement::create(['cai_section_id' => '9226002', 'year' => 2025,
        'total_revenues' => 86420, 'total_expenses' => 82310, 'net_result' => 4110]);

    $this->actingAs(regionalGroupDashboardCustomer());

    Livewire::test(RegionalGroupDashboard::class)
        ->assertSuccessful()
        ->assertSeeText('GR Toscana')
        ->assertSeeText('Codice CAI 9226000 · Toscana')
        ->assertSeeText('Conto economico consolidato Sezioni')
        ->assertSeeText('Avanzo')
        ->assertSeeText('+14.110 €')
        ->assertSeeText('186.420 €')
        ->assertSeeText('172.310 €')
        ->assertSeeText('Esercizio 2025 · Somma di 2 Sezioni')
        ->assertSeeText('Stato patrimoniale consolidato Sezioni')
        ->assertSeeText('50.000 €')
        ->assertSeeText('120.000 €')
        ->assertSeeText('Esercizio 2025 · Somma di 1 Sezione su 3')
        ->assertSeeText('1.248')
        ->assertSeeText('Somma di 2 Sezioni')
        ->assertDontSeeText('Terzo Settore')
        ->assertDontSeeText('Nessuna regione associata');
});

test('the GR own statements are not available, never 0', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA', 'members_count' => 1]);
    CaiFinancialStatement::create(['cai_section_id' => '9226001', 'year' => 2024,
        'net_result' => -500, 'net_equity' => 10, 'total_assets' => 20]);
    caiHut(['branch_code' => '9226001']);

    $this->actingAs(regionalGroupDashboardCustomer());

    $html = Livewire::test(RegionalGroupDashboard::class)
        ->assertSeeText('Disavanzo')
        ->assertSeeText('Esercizio 2024 · Somma di 1 Sezione')
        ->assertSeeText('Esercizio 2024 · Somma di 1 Sezione su 1')
        ->html();

    // Conto economico + stato patrimoniale del GR (sentieri 0,0 km e tappe SICAI 0 sono dati noti).
    expect(substr_count($html, 'ms-metric-badge--unavailable'))->toBe(2);
});

test('the SICAI stages badge shows the number of stages and the km of the main ones', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    grSicaiStage('L01', ['length_km' => 12.25]);
    grSicaiStage('L01S', ['length_km' => 4]);
    grSicaiStage('L02', ['length_km' => 10.5]);

    $this->actingAs(regionalGroupDashboardCustomer());

    Livewire::test(RegionalGroupDashboard::class)
        ->assertSeeTextInOrder(['Tappe SICAI', '3', '22,8 km', 'Tappe SICAI']);
});

test('the SICAI stages badge shows 0 without a km note when the region has no stages', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);

    $this->actingAs(regionalGroupDashboardCustomer());

    $text = (string) preg_replace('/\s+/u', ' ', strip_tags(Livewire::test(RegionalGroupDashboard::class)->html()));
    $badge = str($text)->after('Sentieri del GR')->value();

    expect($badge)->toContain('Tappe SICAI 0 Tappe SICAI')
        ->and($badge)->not->toContain('km');
});

test('the huts badge counts the huts of the sections and those owned by the GR', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226003', 'region' => 'TOSCANA']);
    caiHut(['branch_code' => '9226001']);
    caiHut(['branch_code' => '9226001']);
    caiHut(['branch_code' => '9226002']);
    caiHut(['branch_code' => '9500010', 'branch_name' => 'GR TOSCANA']);
    caiHut(['branch_code' => '9216001']);

    $this->actingAs(regionalGroupDashboardCustomer());

    Livewire::test(RegionalGroupDashboard::class)
        ->assertSeeTextInOrder(['Rifugi', '4', 'Somma di 2 Sezioni', 'Rifugi del GR']);
});

test('the huts badge uses the singular for a single section', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    caiHut(['branch_code' => '9226002']);

    $this->actingAs(regionalGroupDashboardCustomer());

    Livewire::test(RegionalGroupDashboard::class)
        ->assertSeeTextInOrder(['Rifugi', '1', 'Somma di 1 Sezione', 'Rifugi del GR']);
});

test('a regional group without huts shows 0 as a known value, without the note', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);

    $this->actingAs(regionalGroupDashboardCustomer());

    $html = Livewire::test(RegionalGroupDashboard::class)
        ->assertSeeTextInOrder(['Rifugi', '0', 'Rifugi del GR'])
        ->assertDontSeeText('Somma di')
        ->html();

    // Senza i marcatori di Livewire (`<!--[if BLOCK]>…`) fra gli elementi.
    expect(preg_replace('/<!--.*?-->/s', '', $html))->toMatch('/ms-metric-badge__label">Rifugi<\/span>\s*<span class="ms-metric-badge__value">\s*<span class="ms-metric-badge__number">0<\/span>/');
});

test('the trails badge shows the km in the regional territory', function (): void {
    grTrailsFixture();

    $this->actingAs(regionalGroupDashboardCustomer(region: Region::Lombardia, name: 'GR Lombardia'));

    Livewire::test(RegionalGroupDashboard::class)
        ->assertSeeTextInOrder(['Sentieri', '30,7', 'km', 'nel territorio regionale', 'Sentieri del GR']);
});

test('a regional group whose region has no trails data shows 0,0 km', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);

    $this->actingAs(regionalGroupDashboardCustomer());

    Livewire::test(RegionalGroupDashboard::class)
        ->assertSeeTextInOrder(['Sentieri', '0,0', 'km', 'nel territorio regionale', 'Sentieri del GR']);
});

test('a regional group whose region has no sections shows only the region and no data', function (): void {
    caiSection(['codice_cai' => '9216001', 'region' => 'LOMBARDIA', 'members_count' => 10]);

    $this->actingAs(regionalGroupDashboardCustomer());

    $html = Livewire::test(RegionalGroupDashboard::class)
        ->assertSeeText('GR Toscana')
        ->assertSeeText('Toscana')
        ->assertDontSeeText('Codice CAI')
        ->assertDontSeeText('Esercizio')
        ->assertDontSeeText('Somma di')
        ->assertDontSeeText('Nessuna regione associata')
        ->html();

    expect(substr_count($html, 'ms-metric-badge--unavailable'))->toBe(9)
        ->and(substr_count($html, 'Non disponibile'))->toBe(9);
});

test('a regional group without a region sees its name, a notice and every badge not available', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA', 'members_count' => 10]);

    $this->actingAs(regionalGroupDashboardCustomer(region: null, name: 'GR Lombardia/SOLROA'));

    $html = Livewire::test(RegionalGroupDashboard::class)
        ->assertSeeText('GR Lombardia/SOLROA')
        ->assertSeeText('Nessuna regione associata al tuo account: contatta Montagna Servizi.')
        ->assertDontSeeText('Codice CAI')
        ->assertDontSeeText('Somma di')
        ->html();

    expect(substr_count($html, 'ms-metric-badge--unavailable'))->toBe(9);
});

test('the badges link to their detail pages', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);

    $this->actingAs(regionalGroupDashboardCustomer());

    $html = Livewire::test(RegionalGroupDashboard::class)
        ->assertSeeTextInOrder(['Conto economico', 'Bilancio', 'Stato patrimoniale', 'Bilancio',
            'Conto economico consolidato Sezioni', 'Bilancio', 'Stato patrimoniale consolidato Sezioni', 'Bilancio',
            'Sezioni', 'Sezioni del GR', 'Soci', 'Soci', 'Rifugi', 'Rifugi del GR', 'Sentieri', 'Sentieri del GR',
            'Tappe SICAI', 'Tappe SICAI'])
        ->assertDontSeeText('Vedi dettaglio')
        ->html();

    expect(substr_count($html, 'href="'.RegionalGroupFinancialStatements::getUrl().'"'))->toBe(4);

    foreach ([CustomerRegionalSectionsDashboard::class, RegionalGroupMembers::class, RegionalGroupHuts::class,
        RegionalGroupTrails::class, RegionalGroupSicaiStages::class] as $page) {
        expect(substr_count($html, 'href="'.$page::getUrl().'"'))->toBe(1);
    }
});

test('the consolidated badges open the consolidated page on the year of their card', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    CaiFinancialStatement::create(['cai_section_id' => '9226001', 'year' => 2025,
        'total_revenues' => 100000, 'total_expenses' => 90000, 'net_result' => 10000]);
    CaiFinancialStatement::create(['cai_section_id' => '9226002', 'year' => 2024,
        'net_equity' => 50000, 'total_assets' => 120000]);

    $this->actingAs(regionalGroupDashboardCustomer());

    $html = Livewire::test(RegionalGroupDashboard::class)
        ->assertSeeTextInOrder(['Conto economico consolidato Sezioni', 'Esercizio 2025',
            'Stato patrimoniale consolidato Sezioni', 'Esercizio 2024'])
        ->html();

    $plainUrl = RegionalGroupFinancialStatements::getUrl();

    expect(RegionalGroupDashboard::financialStatementsUrl(2025))->toBe($plainUrl.'?esercizio=2025')
        ->and(RegionalGroupDashboard::financialStatementsUrl())->toBe($plainUrl)
        ->and(substr_count($html, 'href="'.$plainUrl.'?esercizio=2025"'))->toBe(1)
        ->and(substr_count($html, 'href="'.$plainUrl.'?esercizio=2024"'))->toBe(1)
        // I due badge del bilancio proprio del GR: stessa pagina, senza esercizio.
        ->and(substr_count($html, 'href="'.$plainUrl.'"'))->toBe(2);
});

test('the sum note handles the singular', function (): void {
    expect(RegionalGroupDashboard::sumNote(1))->toBe('Somma di 1 Sezione')
        ->and(RegionalGroupDashboard::sumNote(1, 27))->toBe('Somma di 1 Sezione su 27')
        ->and(RegionalGroupDashboard::sumNote(11))->toBe('Somma di 11 Sezioni')
        ->and(RegionalGroupDashboard::sumNote(8, 27))->toBe('Somma di 8 Sezioni su 27');
});

test('a regional group customer opens the dashboard and finds it first in the GR group', function (): void {
    $this->actingAs(regionalGroupDashboardCustomer())
        ->get(RegionalGroupDashboard::getUrl())
        ->assertSuccessful();

    expect(RegionalGroupDashboard::getNavigationLabel())->toBe('Dashboard')
        ->and(RegionalGroupDashboard::getNavigationGroup())->toBe('GR')
        ->and(RegionalGroupDashboard::getNavigationSort())->toBeLessThan(CustomerDashboard::getNavigationSort());
});

test('a staff user cannot access the regional group dashboard', function (): void {
    $this->actingAs(withRole(User::factory()->create(), UserRole::Developer))
        ->get(RegionalGroupDashboard::getUrl())
        ->assertForbidden();
});

test('a customer of another type cannot access the regional group dashboard', function (?CustomerType $type): void {
    $this->actingAs(regionalGroupDashboardCustomer(type: $type))
        ->get(RegionalGroupDashboard::getUrl())
        ->assertForbidden();
})->with([
    'Sezione' => [CustomerType::Sezione],
    'Organo Tecnico' => [CustomerType::OrganoTecnicoStrutturaOperativa],
    'nessun tipo' => [null],
]);
