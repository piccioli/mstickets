<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Auth\Pages\Login;
use App\Filament\Pages\CaiTrailDetail;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\SectionDashboard;
use App\Filament\Pages\SectionTrails;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * Checkpoint della feature 10.6 (Sentieri di Sezione): percorre il flusso come lo farebbe un tester UAT con un
 * utente Sezione reale — login, Dashboard V2 con il badge "Sentieri" in km, click sul badge, elenco dei sentieri
 * della Sezione (anche quello condiviso con un'altra Sezione), click su un sentiero, scheda con le Sezioni che lo
 * gestiscono — e verifica che la scheda di un sentiero di un'altra Sezione risponda 403. I dettagli (filtri,
 * ordinamento, mappa, avvisi di percorribilità) sono coperti dai test mirati elencati in docs/collaudo/fase-10.php.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

test('a Sezione user follows the trails badge in km to the list and opens the sheet of a shared trail, never the sheet of another section trail', function (): void {
    $user = withRole(User::factory()->create(['name' => 'Utente Parma', 'password' => bcrypt('password')]), UserRole::Customer);
    $user->forceFill(['customer_type' => CustomerType::Sezione])->save();

    caiSection(['codice_cai' => '9224001', 'name' => 'SEZ. PARMA', 'region' => 'EMILIA-ROMAGNA', 'user_id' => $user->id]);
    caiSection(['codice_cai' => '9224002', 'name' => 'SEZ. REGGIO EMILIA', 'region' => 'EMILIA-ROMAGNA']);
    caiSection(['codice_cai' => '9226019', 'name' => 'SEZ. LUCCA', 'region' => 'TOSCANA']);

    $crinale = caiTrail(['id' => 2001, 'ref' => '00', 'name' => 'Sentiero del Crinale', 'from_place' => 'Passo della Cisa',
        'to_place' => 'Passo del Cerreto', 'length_km' => 12.25, 'status' => CaiTrailStatus::Validated], ['9224001', '9224002']);
    caiTrail(['id' => 2002, 'ref' => '700E', 'name' => 'Sentiero di Parma', 'length_km' => 3.5,
        'status' => CaiTrailStatus::Reconnaissance, 'issues_status' => 'non percorribile',
        'practicability' => CaiTrailPracticability::NotPracticable], ['9224001']);
    $lucca = caiTrail(['id' => 2003, 'ref' => '7', 'name' => 'Sentiero della Pania', 'length_km' => 8.0], ['9226019']);

    // --- Login ------------------------------------------------------------------------
    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect();

    expect(auth()->id())->toBe($user->id);

    // --- Dashboard V2 → badge "Sentieri" in km ------------------------------------------
    Livewire::test(Dashboard::class)->assertRedirect(SectionDashboard::getUrl());

    $dashboard = $this->get(SectionDashboard::getUrl())
        ->assertSuccessful()
        ->assertSeeText('Sentieri')
        ->assertSee('href="'.SectionTrails::getUrl().'"', escape: false);

    expect($dashboard->getContent())->toContain('ms-metric-badge__number">15,8<');

    // --- Elenco dei sentieri della Sezione ------------------------------------------------
    $sheetUrl = CaiTrailDetail::getUrl(['trailId' => $crinale->id]);

    $this->get(SectionTrails::getUrl())
        ->assertSuccessful()
        ->assertSeeText('SEZ. PARMA')
        ->assertSeeText('Sentiero del Crinale')
        ->assertSeeText('Sentiero di Parma')
        ->assertSeeText('Condiviso con')
        ->assertDontSeeText('Sentiero della Pania')
        ->assertSee($sheetUrl, escape: false);

    // --- Scheda del sentiero con le Sezioni che lo gestiscono -------------------------------
    $this->get($sheetUrl)
        ->assertSuccessful()
        ->assertSeeText('Sentiero del Crinale')
        ->assertSeeText('Passo della Cisa')
        ->assertSeeText('← Sentieri di Sezione')
        ->assertSeeText('Sezioni che lo gestiscono')
        ->assertSeeText('SEZ. REGGIO EMILIA')
        ->assertSeeText('Questa Sezione');

    // --- Scheda di un sentiero di un'altra Sezione → 403 -------------------------------------
    $this->get(CaiTrailDetail::getUrl(['trailId' => $lucca->id]))->assertForbidden();
});
