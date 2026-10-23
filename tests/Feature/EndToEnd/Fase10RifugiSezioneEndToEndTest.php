<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiHutType;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Auth\Pages\Login;
use App\Filament\Pages\CaiHutDetail;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\SectionDashboard;
use App\Filament\Pages\SectionHuts;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/**
 * Checkpoint della feature 10.4 (Rifugi di Sezione): percorre il flusso come lo farebbe un tester UAT con
 * un utente Sezione reale — login, Dashboard V2 con il badge "Rifugi" valorizzato, click sul badge, elenco dei
 * rifugi della Sezione, click su un rifugio, scheda con la sezione "Gestione" — e verifica che la scheda di un
 * rifugio di un'altra Sezione risponda 403. I dettagli (filtri, ordinamento, mappa, servizi, scadenza del
 * contratto) sono coperti dai test mirati elencati in docs/collaudo/fase-10.php.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
    Carbon::setTestNow('2026-10-23 10:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('a Sezione user follows the huts badge to the list and opens the sheet of a hut with its management, never the sheet of another section hut', function (): void {
    $user = withRole(User::factory()->create(['name' => 'Utente Varallo', 'password' => bcrypt('password')]), UserRole::Customer);
    $user->forceFill(['customer_type' => CustomerType::Sezione])->save();

    caiSection(['codice_cai' => '9212002', 'name' => 'SEZ. VARALLO', 'region' => 'PIEMONTE', 'user_id' => $user->id]);
    caiSection(['codice_cai' => '9226019', 'name' => 'SEZ. LUCCA', 'region' => 'TOSCANA']);

    $gnifetti = caiHut(['id_cai' => 501, 'branch_code' => '9212002', 'name' => 'Rifugio Gnifetti', 'altitude' => 3647,
        'beds' => 176, 'management' => [
            'manager' => ['company' => 'Gnifetti Srl', 'name' => 'Mario', 'surname' => 'Rossi'],
            'contract' => ['start_date' => '2020-01-01', 'end_date' => '2027-01-31', 'fee' => 30000.0, 'fee_text' => '30000'],
        ]]);
    caiHut(['id_cai' => 502, 'branch_code' => '9212002', 'name' => 'Bivacco Resegotti', 'type' => CaiHutType::Bivouac,
        'status' => CaiHutStatus::TemporarilyClosed, 'beds' => 6]);
    $lucca = caiHut(['id_cai' => 503, 'branch_code' => '9226019', 'name' => 'Rifugio Rossi alla Pania']);

    // --- Login ------------------------------------------------------------------------
    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect();

    expect(auth()->id())->toBe($user->id);

    // --- Dashboard V2 → badge "Rifugi" ---------------------------------------------------
    Livewire::test(Dashboard::class)->assertRedirect(SectionDashboard::getUrl());

    $dashboard = $this->get(SectionDashboard::getUrl())
        ->assertSuccessful()
        ->assertSeeText('Rifugi')
        ->assertSee('href="'.SectionHuts::getUrl().'"', escape: false);

    expect(substr_count($dashboard->getContent(), 'ms-metric-badge__number">2<'))->toBe(1);

    // --- Elenco dei rifugi della Sezione ---------------------------------------------------
    $sheetUrl = CaiHutDetail::getUrl(['idCai' => $gnifetti->id_cai]);

    $this->get(SectionHuts::getUrl())
        ->assertSuccessful()
        ->assertSeeText('SEZ. VARALLO')
        ->assertSeeText('Rifugio Gnifetti')
        ->assertSeeText('Bivacco Resegotti')
        ->assertDontSeeText('Rifugio Rossi alla Pania')
        ->assertSee($sheetUrl, escape: false);

    // --- Scheda del rifugio con la gestione ------------------------------------------------
    $this->get($sheetUrl)
        ->assertSuccessful()
        ->assertSeeText('Rifugio Gnifetti')
        ->assertSeeText('3.647 m')
        ->assertSeeText('← Rifugi di Sezione')
        ->assertSeeText('Gestione')
        ->assertSeeText('Gnifetti Srl')
        ->assertSeeText('31/01/2027');

    // --- Scheda di un rifugio di un'altra Sezione → 403 -------------------------------------
    $this->get(CaiHutDetail::getUrl(['idCai' => $lucca->id_cai]))->assertForbidden();
});
