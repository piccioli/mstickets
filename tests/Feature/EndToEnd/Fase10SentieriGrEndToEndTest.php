<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Auth\Pages\Login;
use App\Filament\Pages\CaiTrailDetail;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\RegionalGroupDashboard;
use App\Filament\Pages\RegionalGroupSectionTrails;
use App\Filament\Pages\RegionalGroupTrails;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * Checkpoint della feature 10.7 (Sentieri del GR): percorre il flusso come lo farebbe un tester UAT con un utente
 * GR reale — login, Dashboard V2 con il badge "Sentieri" in km nel territorio regionale, click sul badge, pagina
 * con i due totali, apertura di una provincia, elenco dei sentieri di un settore, sentieri di una Sezione del GR,
 * scheda di un sentiero — e verifica che un GR di un'altra regione riceva 403 sulla pagina della Sezione. I
 * dettagli (mappa per settori, filtri, paginazione, ordinamento) sono coperti dai test mirati elencati in
 * docs/collaudo/fase-10.php. Dati: `grTrailsFixture()` (tests/Pest.php).
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

function fase10GrTrailsUser(string $name, Region $region): User
{
    $user = withRole(User::factory()->create(['name' => $name, 'password' => bcrypt('password')]), UserRole::Customer);
    $user->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => $region])->save();

    return $user;
}

test('a Gruppo Regionale user follows the trails badge in km to the page, opens a province, lists a sector, opens a section and a trail, while another group gets 403 on the section', function (): void {
    grTrailsFixture();
    $user = fase10GrTrailsUser('GR Lombardia', Region::Lombardia);
    $molise = fase10GrTrailsUser('GR Molise', Region::Molise);
    $laghi = CaiTrail::query()->where('ref', '2')->firstOrFail();

    // --- Login ------------------------------------------------------------------------
    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect();

    expect(auth()->id())->toBe($user->id);

    // --- Dashboard V2 → badge "Sentieri" in km nel territorio regionale --------------------
    Livewire::test(Dashboard::class)->assertRedirect(RegionalGroupDashboard::getUrl());

    $this->get(RegionalGroupDashboard::getUrl())
        ->assertSuccessful()
        ->assertSeeTextInOrder(['Sentieri', '30,7', 'km', 'nel territorio regionale'])
        ->assertSee('href="'.RegionalGroupTrails::getUrl().'"', escape: false);

    // --- Pagina "Sentieri del GR" con i due totali -------------------------------------------
    $this->get(RegionalGroupTrails::getUrl())
        ->assertSuccessful()
        ->assertSeeText('Sentieri del GR')
        ->assertSeeText('Km nel territorio regionale')
        ->assertSeeText('Km gestiti dalle Sezioni del GR')
        ->assertSeeText('Per provincia')
        ->assertSeeText('Per Sezione');

    // --- Provincia → settori → elenco dei sentieri del settore ------------------------------
    $page = Livewire::test(RegionalGroupTrails::class)
        ->call('toggleProvince', 10)
        ->assertSet('openProvince', '10')
        ->assertSeeText('Settori della provincia di Bergamo')
        ->assertSeeText('Val Brembana')
        ->call('listSector', 'settore-100')
        ->assertSet('province', '10')
        ->assertSet('sector', '100')
        ->assertSeeText('Sentiero delle Orobie')
        ->assertSeeText('Sentiero dei Laghi')
        ->assertDontSeeText('Passo Gavia');

    // --- Sezione del GR → i suoi sentieri visti dal GR ---------------------------------------
    $sectionUrl = RegionalGroupSectionTrails::getUrl(['codice' => '9300001']);
    expect($page->html())->toContain(e($sectionUrl));

    $trailUrl = CaiTrailDetail::getUrl(['trailId' => $laghi->id, 'da' => 'sezione-gr', 'codice' => '9300001']);

    $this->get($sectionUrl)
        ->assertSuccessful()
        ->assertSeeText('← Sentieri del GR')
        ->assertSeeText('SEZ. MILANO')
        ->assertSeeText('Sentiero dei Laghi')
        ->assertDontSeeText('Alta Via')
        ->assertSee(e($trailUrl), escape: false);

    // --- Scheda del sentiero, con il ritorno alla Sezione vista dal GR -------------------------
    $this->get($trailUrl)
        ->assertSuccessful()
        ->assertSeeText('Sentiero dei Laghi')
        ->assertSeeText('Sezioni che lo gestiscono')
        ->assertSeeText('SEZ. BERGAMO')
        ->assertSee(e($sectionUrl), escape: false);

    // --- Un GR di un'altra regione → 403 sulla pagina della Sezione lombarda --------------------
    // Sessione nuova: quella del login conserva l'hash della password del GR Lombardia e non vale per un altro utente.
    $this->flushSession();

    $this->actingAs($molise)->get($sectionUrl)->assertForbidden();
    $this->get(RegionalGroupSectionTrails::getUrl(['codice' => '9720001']))->assertSuccessful();
});
