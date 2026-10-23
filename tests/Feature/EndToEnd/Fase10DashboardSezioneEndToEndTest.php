<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Auth\Pages\Login;
use App\Filament\Pages\CustomerDashboard;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\RegionalGroupDashboard;
use App\Filament\Pages\SectionDashboard;
use App\Filament\Pages\SectionMembers;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * Checkpoint della feature 10.1 (Dashboard V2 della Sezione): percorre il flusso come lo farebbe un
 * tester UAT con un utente Sezione reale — login, atterraggio sulla V2, lettura dei valori, click su
 * un badge, pagina "in arrivo", poi "Ticket e documenti". I dettagli (scelta dell'esercizio, stati
 * dei badge, autorizzazioni) sono coperti dai test mirati elencati in docs/collaudo/fase-10.php.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

test('a Sezione user logs in, lands on the Dashboard V2 with real values and follows a badge to the coming soon page', function (): void {
    $user = withRole(User::factory()->create(['name' => 'Utente Pisa', 'password' => bcrypt('password')]), UserRole::Customer);
    $user->forceFill(['customer_type' => CustomerType::Sezione])->save();

    $section = caiSection(['codice_cai' => '9226003', 'name' => 'SEZ. PISA', 'region' => 'TOSCANA',
        'members_count' => 963, 'user_id' => $user->id]);
    $registration = caiRuntsRegistration(['cai_section_id' => $section->codice_cai]);
    // Conto economico 2025 via iscrizione RUNTS, stato patrimoniale solo sull'esercizio 2024.
    CaiFinancialStatement::create(['cai_runts_registration_id' => $registration->id_runts, 'year' => 2025,
        'total_revenues' => 120400, 'total_expenses' => 92847, 'net_result' => 27553]);
    CaiFinancialStatement::create(['cai_section_id' => $section->codice_cai, 'year' => 2024,
        'net_result' => 1000, 'net_equity' => 210500, 'total_assets' => 318200]);

    // --- Login ------------------------------------------------------------------------
    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect();

    expect(auth()->id())->toBe($user->id);

    // --- Atterraggio sulla V2 ------------------------------------------------------------
    Livewire::test(Dashboard::class)->assertRedirect(SectionDashboard::getUrl());

    // --- Valori ------------------------------------------------------------------------
    $response = $this->get(SectionDashboard::getUrl())
        ->assertSuccessful()
        ->assertSeeText('SEZ. PISA')
        ->assertSeeText('Codice CAI 9226003 · Toscana')
        ->assertSeeText('+27.553 €')
        ->assertSeeText('Avanzo')
        ->assertSeeText('Esercizio 2025')
        ->assertSeeText('120.400 €')
        ->assertSeeText('92.847 €')
        ->assertSeeText('210.500 €')
        ->assertSeeText('318.200 €')
        ->assertSeeText('Esercizio 2024')
        ->assertSeeText('963')
        ->assertSeeText('Ticket e documenti');

    // Sentieri e tappe SICAI non hanno ancora una sorgente dati: mai "0". I rifugi sì (10.4): 0 è un dato noto.
    expect(substr_count((string) $response->getContent(), 'ms-metric-badge--unavailable'))->toBe(2);

    // --- Click sul badge "Soci" → pagina "in arrivo" ------------------------------------
    $response->assertSee('href="'.SectionMembers::getUrl().'"', escape: false);

    $this->get(SectionMembers::getUrl())
        ->assertSuccessful()
        ->assertSeeText('Soci')
        ->assertSeeText('Questa sezione è in arrivo.')
        ->assertSeeText('Torna alla dashboard');

    // --- "Ticket e documenti" conserva la dashboard cliente --------------------------------
    $this->get(CustomerDashboard::getUrl())->assertSuccessful();

    expect(CustomerDashboard::getNavigationLabel())->toBe('Ticket e documenti');
});

test('a Gruppo Regionale user lands on the regional group dashboard V2 and cannot open the Section Dashboard V2', function (): void {
    $user = withRole(User::factory()->create(), UserRole::Customer);
    $user->forceFill(['customer_type' => CustomerType::GruppoRegionale])->save();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)->assertRedirect(RegionalGroupDashboard::getUrl());

    $this->get(SectionDashboard::getUrl())->assertForbidden();
    $this->get(SectionMembers::getUrl())->assertForbidden();
});
