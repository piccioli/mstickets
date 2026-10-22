<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiRuntsPresenceStatus;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\SectionDashboard;
use App\Filament\Pages\SectionFinancialStatements;
use App\Filament\Pages\SectionHuts;
use App\Filament\Pages\SectionMembers;
use App\Filament\Pages\SectionSicaiStages;
use App\Filament\Pages\SectionTrails;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

function sectionDashboardCustomer(?CustomerType $type = CustomerType::Sezione, string $name = 'Utente Sezione'): User
{
    $customer = withRole(User::factory()->create(['name' => $name]), UserRole::Customer);
    $customer->forceFill(['customer_type' => $type])->save();

    return $customer;
}

test('a complete section shows identity, statements and members', function (): void {
    $user = sectionDashboardCustomer();
    $section = caiSection(['codice_cai' => '9226003', 'name' => 'SEZ. PISA', 'region' => 'TOSCANA',
        'members_count' => 1248, 'user_id' => $user->id]);
    CaiFinancialStatement::create(['cai_section_id' => $section->codice_cai, 'year' => 2025,
        'total_revenues' => 186420, 'total_expenses' => 172310, 'net_result' => 14110,
        'net_equity' => 95300.4, 'total_assets' => 240100]);

    $this->actingAs($user);

    Livewire::test(SectionDashboard::class)
        ->assertSuccessful()
        ->assertSeeText('SEZ. PISA')
        ->assertSeeText('Codice CAI 9226003 · Toscana')
        ->assertSeeText('Conto economico')
        ->assertSeeText('Risultato netto')
        ->assertSeeText('Avanzo')
        ->assertSeeText('+14.110 €')
        ->assertSeeText('186.420 €')
        ->assertSeeText('172.310 €')
        ->assertSeeText('Esercizio 2025')
        ->assertSeeText('Stato patrimoniale')
        ->assertSeeText('Patrimonio netto')
        ->assertSeeText('95.300 €')
        ->assertSeeText('Totale attivo')
        ->assertSeeText('240.100 €')
        ->assertSeeText('1.248')
        ->assertDontSeeText('Nessuna Sezione CAI collegata');
});

test('the typical section shows members and income statement, the rest is not available', function (): void {
    $user = sectionDashboardCustomer();
    $section = caiSection(['name' => 'SEZ. TIPICA', 'members_count' => 312, 'user_id' => $user->id]);
    CaiFinancialStatement::create(['cai_section_id' => $section->codice_cai, 'year' => 2025,
        'total_revenues' => 20000, 'total_expenses' => 32340, 'net_result' => -12340]);

    $this->actingAs($user);

    $html = Livewire::test(SectionDashboard::class)
        ->assertSeeText('312')
        ->assertSeeText('Disavanzo')
        ->assertSeeText('−12.340 €')
        ->assertSeeText('Esercizio 2025')
        ->html();

    // Stato patrimoniale + rifugi + sentieri + tappe SICAI.
    expect(substr_count($html, 'ms-metric-badge--unavailable'))->toBe(4)
        ->and(substr_count($html, 'Esercizio '))->toBe(1)
        ->and($html)->not->toContain('>0<');
});

test('an empty section shows every badge as not available, never 0', function (): void {
    $user = sectionDashboardCustomer();
    caiSection(['name' => 'SEZ. VUOTA', 'region' => '', 'codice_cai' => '9999', 'members_count' => null, 'user_id' => $user->id]);

    $this->actingAs($user);

    $html = Livewire::test(SectionDashboard::class)
        ->assertSeeText('SEZ. VUOTA')
        ->assertSeeText('Codice CAI 9999')
        ->assertDontSeeText('·')
        ->assertDontSeeText('Esercizio')
        ->html();

    expect(substr_count($html, 'ms-metric-badge--unavailable'))->toBe(6)
        ->and(substr_count($html, 'Non disponibile'))->toBe(6);
});

test('a subsection user sees the subsection name, code and members with the parent region', function (): void {
    $user = sectionDashboardCustomer();
    $section = caiSection(['region' => 'TOSCANA', 'members_count' => 5000]);
    CaiFinancialStatement::create(['cai_section_id' => $section->codice_cai, 'year' => 2025, 'net_result' => 999]);
    CaiSubsection::create(['cai_codice' => '9226003001', 'cai_section_id' => $section->codice_cai,
        'name' => 'SOTTOSEZ. CASCINA', 'members_count' => 87, 'user_id' => $user->id]);

    $this->actingAs($user);

    $html = Livewire::test(SectionDashboard::class)
        ->assertSeeText('SOTTOSEZ. CASCINA')
        ->assertSeeText('Codice CAI 9226003001 · Toscana')
        ->assertSeeText('87')
        ->assertDontSeeText('5.000')
        ->assertDontSeeText('+999 €')
        ->html();

    expect(substr_count($html, 'ms-metric-badge--unavailable'))->toBe(5);
});

test('a user without a linked section sees its own name, a notice and no data', function (): void {
    $this->actingAs(sectionDashboardCustomer(name: 'Mario Rossi'));

    $html = Livewire::test(SectionDashboard::class)
        ->assertSeeText('Mario Rossi')
        ->assertSeeText('Nessuna Sezione CAI collegata al tuo account: contatta Montagna Servizi.')
        ->assertDontSeeText('Codice CAI')
        ->html();

    expect(substr_count($html, 'ms-metric-badge--unavailable'))->toBe(6);
});

test('the badges link to their detail pages', function (): void {
    $user = sectionDashboardCustomer();
    caiSection(['user_id' => $user->id]);

    $this->actingAs($user);

    $html = Livewire::test(SectionDashboard::class)->html();

    expect(substr_count($html, 'href="'.SectionFinancialStatements::getUrl().'"'))->toBe(2);

    foreach ([SectionMembers::class, SectionHuts::class, SectionTrails::class, SectionSicaiStages::class] as $page) {
        expect(substr_count($html, 'href="'.$page::getUrl().'"'))->toBe(1);
    }
});

test('a section customer opens the dashboard and finds it first in the Area cliente group', function (): void {
    $this->actingAs(sectionDashboardCustomer())
        ->get(SectionDashboard::getUrl())
        ->assertSuccessful();

    expect(SectionDashboard::getNavigationLabel())->toBe('Dashboard')
        ->and(SectionDashboard::getNavigationGroup())->toBe('Area cliente')
        ->and(SectionDashboard::getNavigationSort())->toBeLessThan(-2);
});

test('a staff user cannot access the section dashboard', function (): void {
    $this->actingAs(withRole(User::factory()->create(), UserRole::Developer))
        ->get(SectionDashboard::getUrl())
        ->assertForbidden();
});

test('a customer of another type cannot access the section dashboard', function (?CustomerType $type): void {
    $this->actingAs(sectionDashboardCustomer($type))
        ->get(SectionDashboard::getUrl())
        ->assertForbidden();
})->with([
    'Gruppo Regionale' => [CustomerType::GruppoRegionale],
    'Organo Tecnico' => [CustomerType::OrganoTecnicoStrutturaOperativa],
    'nessun tipo' => [null],
]);

test('the subtitle shows the third sector registration pill', function (?CaiRuntsPresenceStatus $status, string $pill, string $absent): void {
    $user = sectionDashboardCustomer();
    caiSection(['user_id' => $user->id, 'runts_presence_status' => $status]);

    $this->actingAs($user);

    Livewire::test(SectionDashboard::class)
        ->assertSeeText($pill)
        ->assertDontSeeText($absent);
})->with([
    'iscritto' => [CaiRuntsPresenceStatus::Registered, 'Terzo Settore: iscritto', 'Terzo Settore: non iscritto'],
    'non iscritto' => [CaiRuntsPresenceStatus::Timeout, 'Terzo Settore: non iscritto', 'Terzo Settore: iscritto'],
]);

test('a subsection user and a user without a section see no third sector pill', function (bool $withSubsection): void {
    $user = sectionDashboardCustomer();
    if ($withSubsection) {
        $section = caiSection(['runts_presence_status' => CaiRuntsPresenceStatus::Registered]);
        CaiSubsection::create(['cai_codice' => 'SUB-PILL', 'cai_section_id' => $section->codice_cai,
            'name' => 'Sub', 'user_id' => $user->id]);
    }

    $this->actingAs($user);

    Livewire::test(SectionDashboard::class)->assertDontSeeText('Terzo Settore');
})->with(['sottosezione' => [true], 'nessuna sezione' => [false]]);

test('each badge link is labelled with the name of its detail page', function (): void {
    $user = sectionDashboardCustomer();
    caiSection(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(SectionDashboard::class)
        ->assertSeeTextInOrder(['Conto economico', 'Bilancio', 'Stato patrimoniale', 'Bilancio',
            'Soci', 'Soci', 'Rifugi', 'Rifugi di Sezione', 'Sentieri', 'Sentieri di Sezione', 'Tappe SICAI', 'Tappe SICAI'])
        ->assertDontSeeText('Vedi dettaglio');
});
