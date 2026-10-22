<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Auth\Pages\Login;
use App\Filament\Pages\CustomerDashboard;
use App\Filament\Pages\CustomerRegionalSectionsDashboard;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\RegionalGroupDashboard;
use App\Filament\Pages\SectionDashboard;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * Checkpoint della feature 10.2 (Dashboard V2 del Gruppo Regionale): percorre il flusso come lo farebbe
 * un tester UAT con un utente GR reale — login, atterraggio sulla V2, lettura dei valori consolidati,
 * click sul badge "Sezioni", elenco delle Sezioni del GR, poi "Ticket e documenti". I dettagli (codice
 * del GR, scelta dell'esercizio, stati dei badge, autorizzazioni) sono coperti dai test mirati elencati
 * in docs/collaudo/fase-10.php.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

function fase10RegionalGroupSectionUser(string $name, Region $region): User
{
    $user = withRole(User::factory()->create(['name' => $name]), UserRole::Customer);
    $user->forceFill(['customer_type' => CustomerType::Sezione, 'region' => $region])->save();

    return $user;
}

test('a Gruppo Regionale user logs in, lands on the Dashboard V2 with the consolidated values and follows the Sezioni badge to the sections of the group', function (): void {
    $user = withRole(User::factory()->create(['name' => 'GR Toscana', 'password' => bcrypt('password')]), UserRole::Customer);
    $user->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => Region::Toscana])->save();

    $pisa = fase10RegionalGroupSectionUser('Utente Pisa', Region::Toscana);
    $lucca = fase10RegionalGroupSectionUser('Utente Lucca', Region::Toscana);
    $perugia = fase10RegionalGroupSectionUser('Utente Perugia', Region::Umbria);

    caiSection(['codice_cai' => '9226003', 'name' => 'SEZ. PISA', 'region' => 'TOSCANA',
        'members_count' => 963, 'user_id' => $pisa->id]);
    caiSection(['codice_cai' => '9226004', 'name' => 'SEZ. LUCCA', 'region' => 'TOSCANA',
        'members_count' => 512, 'user_id' => $lucca->id]);
    caiSection(['codice_cai' => '9226005', 'name' => 'SEZ. SIENA', 'region' => 'TOSCANA', 'members_count' => null]);
    caiSection(['codice_cai' => '9228001', 'name' => 'SEZ. PERUGIA', 'region' => 'UMBRIA',
        'members_count' => 700, 'user_id' => $perugia->id]);

    // Pisa via iscrizione RUNTS, Lucca diretto: entrambi nel consolidato 2025. Siena ha solo il 2024,
    // che resta fuori perché il consolidato somma un solo esercizio, il più recente con dati.
    $registration = caiRuntsRegistration(['cai_section_id' => '9226003']);
    CaiFinancialStatement::create(['cai_runts_registration_id' => $registration->id_runts, 'year' => 2025,
        'total_revenues' => 120400, 'total_expenses' => 92847, 'net_result' => 27553,
        'net_equity' => 210500, 'total_assets' => 318200]);
    CaiFinancialStatement::create(['cai_section_id' => '9226004', 'year' => 2025,
        'total_revenues' => 80000, 'total_expenses' => 85000, 'net_result' => -5000]);
    CaiFinancialStatement::create(['cai_section_id' => '9226005', 'year' => 2024,
        'total_revenues' => 999999, 'total_expenses' => 1, 'net_result' => 999998]);
    // Una Sezione di un altro GR non entra mai nel consolidato.
    CaiFinancialStatement::create(['cai_section_id' => '9228001', 'year' => 2025,
        'total_revenues' => 50000, 'total_expenses' => 40000, 'net_result' => 10000]);

    // --- Login ------------------------------------------------------------------------
    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect();

    expect(auth()->id())->toBe($user->id);

    // --- Atterraggio sulla V2 ------------------------------------------------------------
    Livewire::test(Dashboard::class)->assertRedirect(RegionalGroupDashboard::getUrl());

    // --- Valori consolidati ----------------------------------------------------------------
    $response = $this->get(RegionalGroupDashboard::getUrl())
        ->assertSuccessful()
        ->assertSeeText('GR Toscana')
        ->assertSeeText('Codice CAI 9226000 · Toscana')
        ->assertSeeText('Conto economico consolidato Sezioni')
        ->assertSeeText('+22.553 €')
        ->assertSeeText('Avanzo')
        ->assertSeeText('200.400 €')
        ->assertSeeText('177.847 €')
        ->assertSeeText('Esercizio 2025 · Somma di 2 Sezioni')
        ->assertSeeText('Stato patrimoniale consolidato Sezioni')
        ->assertSeeText('210.500 €')
        ->assertSeeText('318.200 €')
        ->assertSeeText('Esercizio 2025 · Somma di 1 Sezione su 3')
        ->assertSeeText('1.475')
        ->assertDontSeeText('999.998')
        ->assertDontSeeText('Terzo Settore')
        ->assertSeeText('Ticket e documenti');

    // Bilancio proprio del GR (2), rifugi, sentieri e tappe SICAI: senza sorgente dati, mai "0".
    expect(substr_count((string) $response->getContent(), 'ms-metric-badge--unavailable'))->toBe(5);

    // --- Click sul badge "Sezioni" → elenco delle Sezioni del GR --------------------------
    $response->assertSee('href="'.CustomerRegionalSectionsDashboard::getUrl().'"', escape: false);

    $this->get(CustomerRegionalSectionsDashboard::getUrl())
        ->assertSuccessful()
        ->assertSeeText('Sezioni del gruppo regionale')
        ->assertSeeText('Utente Pisa')
        ->assertSeeText('Utente Lucca')
        ->assertDontSeeText('Utente Perugia');

    // --- "Ticket e documenti" conserva la dashboard cliente, la V2 della Sezione resta chiusa -----
    $this->get(CustomerDashboard::getUrl())->assertSuccessful();

    expect(CustomerDashboard::getNavigationLabel())->toBe('Ticket e documenti');

    $this->get(SectionDashboard::getUrl())->assertForbidden();
});
