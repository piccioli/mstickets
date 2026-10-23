<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiHutType;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Auth\Pages\Login;
use App\Filament\Pages\CaiHutDetail;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\RegionalGroupDashboard;
use App\Filament\Pages\RegionalGroupHuts;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/**
 * Checkpoint della feature 10.5 (Rifugi del GR): percorre il flusso come lo farebbe un tester UAT con un utente
 * GR reale — login, Dashboard V2 con il badge "Rifugi" (Sezioni + rifugi intestati al GR), click sul badge, elenco
 * dei rifugi del GR, filtro "Contratti di gestione", scheda di un rifugio con la sezione "Gestione", ritorno
 * all'elenco con il filtro conservato — e verifica che un GR di un'altra regione riceva 403 sulla scheda. I
 * dettagli (cluster, raggruppamento, ordinamento, mobile) sono coperti dai test mirati elencati in
 * docs/collaudo/fase-10.php.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
    Carbon::setTestNow('2026-10-23 10:00:00');
    CarbonImmutable::setTestNow('2026-10-23 10:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function fase10GrHutsUser(string $name, Region $region): User
{
    $user = withRole(User::factory()->create(['name' => $name, 'password' => bcrypt('password')]), UserRole::Customer);
    $user->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => $region])->save();

    return $user;
}

test('a Gruppo Regionale user follows the huts badge to the list, filters the contracts, opens a sheet with its management and goes back with the filter kept, never the sheet of another group', function (): void {
    $user = fase10GrHutsUser('GR Toscana', Region::Toscana);
    $lombardia = fase10GrHutsUser('GR Lombardia', Region::Lombardia);

    caiSection(['codice_cai' => '9226001', 'name' => 'SEZ. LUCCA', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'name' => 'SEZ. PISA', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226003', 'name' => 'SEZ. SIENA', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226004', 'name' => 'SEZ. AREZZO', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9203001', 'name' => 'SEZ. MILANO', 'region' => 'LOMBARDIA']);

    // Rossi: contratto in scadenza (tra 100 giorni); Aronte: senza contratto; Isera: rifugio intestato al GR.
    $rossi = caiHut(['id_cai' => 1, 'branch_code' => '9226001', 'name' => 'Rifugio Rossi', 'altitude' => 1609, 'beds' => 26,
        'management' => [
            'manager' => ['company' => 'Pania Srl', 'name' => 'Mario', 'surname' => 'Rossi'],
            'contract' => ['start_date' => '2021-02-01', 'end_date' => '2027-01-31', 'fee' => 12000.0, 'fee_text' => '12000'],
        ]]);
    caiHut(['id_cai' => 2, 'branch_code' => '9226001', 'name' => 'Bivacco Aronte', 'type' => CaiHutType::Bivouac, 'beds' => 4]);
    caiHut(['id_cai' => 3, 'branch_code' => '9526000', 'branch_name' => 'GR TOSCANA', 'name' => 'Capanna Isera', 'beds' => 10]);
    $milano = caiHut(['id_cai' => 4, 'branch_code' => '9203001', 'name' => 'Rifugio Brioschi']);

    // --- Login ------------------------------------------------------------------------
    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect();

    expect(auth()->id())->toBe($user->id);

    // --- Dashboard V2 → badge "Rifugi" ---------------------------------------------------
    Livewire::test(Dashboard::class)->assertRedirect(RegionalGroupDashboard::getUrl());

    $dashboard = $this->get(RegionalGroupDashboard::getUrl())
        ->assertSuccessful()
        ->assertSeeText('Rifugi')
        ->assertSeeText('Somma di 1 Sezione')
        ->assertSee('href="'.RegionalGroupHuts::getUrl().'"', escape: false);

    expect(substr_count($dashboard->getContent(), 'ms-metric-badge__number">3<'))->toBe(1);

    // --- Elenco dei rifugi del GR ---------------------------------------------------------
    $this->get(RegionalGroupHuts::getUrl())
        ->assertSuccessful()
        ->assertSeeText('Rifugi del GR')
        ->assertSeeText('Rifugio Rossi')
        ->assertSeeText('Bivacco Aronte')
        ->assertSeeText('Capanna Isera')
        ->assertDontSeeText('Rifugio Brioschi');

    // --- Filtro "Contratti di gestione" → link alla scheda con il filtro -------------------
    $sheetUrl = CaiHutDetail::getUrl(['idCai' => $rossi->id_cai, 'da' => 'gr', 'contratti' => '1']);

    Livewire::test(RegionalGroupHuts::class)
        ->call('toggleContracts')
        ->assertSet('contracts', '1')
        ->assertSeeText('Filtro attivo · ×')
        ->assertSeeText('1 di 3 rifugi')
        ->assertSee('href="'.e($sheetUrl).'"', escape: false);

    // --- Scheda del rifugio con la gestione e ritorno con il filtro ------------------------
    $backUrl = RegionalGroupHuts::getUrl(['contratti' => '1']);

    $this->get($sheetUrl)
        ->assertSuccessful()
        ->assertSeeText('Rifugio Rossi')
        ->assertSeeText('Rifugi del GR')
        ->assertSee('href="'.e($backUrl).'"', escape: false)
        ->assertSeeText('Gestione')
        ->assertSeeText('Pania Srl')
        ->assertSeeText('31/01/2027');

    $this->get($backUrl)
        ->assertSuccessful()
        ->assertSeeText('Filtro attivo · ×')
        ->assertSeeText('Rifugio Rossi')
        ->assertDontSeeText('Bivacco Aronte')
        ->assertDontSeeText('Capanna Isera');

    // --- La scheda di un rifugio di un altro GR → 403 ----------------------------------------
    $this->get(CaiHutDetail::getUrl(['idCai' => $milano->id_cai, 'da' => 'gr']))->assertForbidden();

    // --- Un GR di un'altra regione → 403 sulla scheda del rifugio toscano ----------------------
    // Sessione nuova: quella del login conserva l'hash della password del GR Toscana e non vale per un altro utente.
    $this->flushSession();

    $this->actingAs($lombardia)
        ->get(CaiHutDetail::getUrl(['idCai' => $rossi->id_cai, 'da' => 'gr']))
        ->assertForbidden();

    $this->get(CaiHutDetail::getUrl(['idCai' => $milano->id_cai, 'da' => 'gr']))
        ->assertSuccessful()
        ->assertSeeText('Rifugio Brioschi');
});
