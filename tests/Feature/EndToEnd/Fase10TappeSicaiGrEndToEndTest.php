<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Auth\Pages\Login;
use App\Filament\Pages\CaiTrailDetail;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\RegionalGroupDashboard;
use App\Filament\Pages\RegionalGroupSicaiStages;
use App\Filament\Pages\SectionDashboard;
use App\Filament\Pages\SectionSicaiStages;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * Checkpoint della feature 10.9 (Tappe SICAI del GR): percorre il flusso come lo farebbe un tester UAT con un utente
 * GR reale — login, Dashboard V2 con il badge "Tappe SICAI" (numero e km), click sul badge, pagina con riepilogo ed
 * elenco, scheda di una tappa, tappa successiva, punto di appoggio vicino con il link a rifugi.cai.it — poi verifica
 * che i GR Toscana ed Emilia-Romagna vedano le stesse tappe L e che la Sezione abbia il badge "In attesa". I dettagli
 * (mappa, filtri, varianti, stati vuoti, autorizzazioni) sono coperti dai test mirati elencati in docs/collaudo/fase-10.php.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

function fase10SicaiUser(string $name, CustomerType $type, ?Region $region = null): User
{
    $user = withRole(User::factory()->create(['name' => $name, 'password' => bcrypt('password')]), UserRole::Customer);
    $user->forceFill(['customer_type' => $type, 'region' => $region])->save();

    return $user;
}

/** Testo della risposta senza tag, script né commenti Livewire, con gli spazi compattati. */
function fase10SicaiText(string $html): string
{
    $html = (string) preg_replace(['/<!--.*?-->/s', '/<script\b.*?<\/script>/s', '/<style\b.*?<\/style>/s'], '', $html);

    return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)));
}

test('a Gruppo Regionale user follows the SICAI badge to the page, opens a stage, the next one and its rest point, while Toscana and Emilia-Romagna share the L stages and a Sezione sees "In attesa"', function (): void {
    // Piemonte: E01 → E02 (con la variante E02S); un rifugio vicino a E02. Toscana/Emilia-Romagna: L01, L02.
    caiSection(['codice_cai' => '9212003', 'region' => 'PIEMONTE']);
    caiSection(['codice_cai' => '9226003', 'region' => 'TOSCANA']);
    $e01 = grSicaiStage('E01', ['from_place' => 'Limone Piemonte', 'to_place' => 'Palanfrè', 'length_km' => 10.0]);
    $e02 = grSicaiStage('E02', ['from_place' => 'Palanfrè', 'to_place' => 'Entracque', 'length_km' => 8.5]);
    grSicaiStage('E02S', ['from_place' => 'Palanfrè', 'to_place' => 'Trinità', 'length_km' => 9.0]);
    grSicaiStage('L01', ['from_place' => 'Passo dei Due Santi', 'to_place' => 'Lago Santo']);
    grSicaiStage('L02', ['from_place' => 'Lago Santo', 'to_place' => 'Passo del Cerreto']);

    $hut = caiHut(['id_cai' => 921201004, 'name' => 'Rifugio Mondovì']);
    grSicaiRestPoint(1, 'Rifugio Mondovì', 'Piemonte', 'Rifugio', [], ['municipality' => 'Roccaforte Mondovì',
        'operator' => 'CAI Mondovì', 'cai_hut_id_cai' => $hut->id_cai]);
    DB::table('cai_sicai_stage_rest_point')->insert(['cai_trail_id' => $e02->cai_trail_id, 'rest_point_id' => 1,
        'distance_m' => 40, 'position' => 3000]);

    $user = fase10SicaiUser('GR Piemonte', CustomerType::GruppoRegionale, Region::Piemonte);
    $e01Url = CaiTrailDetail::getUrl(['trailId' => $e01->cai_trail_id, 'da' => CaiTrailDetail::FROM_SICAI]);
    $e02Url = CaiTrailDetail::getUrl(['trailId' => $e02->cai_trail_id, 'da' => CaiTrailDetail::FROM_SICAI]);

    // --- Login ------------------------------------------------------------------------
    Livewire::test(Login::class)
        ->set('data.email', $user->email)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect();

    expect(auth()->id())->toBe($user->id);

    // --- Dashboard V2 → badge "Tappe SICAI": 3 tappe, km delle sole principali ---------------
    Livewire::test(Dashboard::class)->assertRedirect(RegionalGroupDashboard::getUrl());

    $this->get(RegionalGroupDashboard::getUrl())
        ->assertSuccessful()
        ->assertSeeTextInOrder(['Tappe SICAI', '3', '18,5 km', 'Tappe SICAI'])
        ->assertSee('href="'.RegionalGroupSicaiStages::getUrl().'"', escape: false);

    // --- Pagina "Tappe SICAI": riepilogo, elenco, punto di appoggio ----------------------------
    $page = $this->get(RegionalGroupSicaiStages::getUrl())->assertSuccessful();

    expect(fase10SicaiText((string) $page->getContent()))
        ->toContain('E01')
        ->toContain('E02S')
        ->toContain('Rifugio Mondovì')
        ->not->toContain('L01');
    $page->assertSee(e($e01Url), escape: false);

    // --- Scheda della tappa E01 → tappa successiva E02 ------------------------------------------
    $sheet = $this->get($e01Url)->assertSuccessful();

    expect(fase10SicaiText((string) $sheet->getContent()))
        ->toContain('Tappe SICAI / Tappa E01')
        ->toContain('Tappa 1 di 2 · Sentiero Italia CAI in Piemonte');
    $sheet->assertSee('href="'.e($e02Url).'"', escape: false);

    // --- Tappa E02 → punto di appoggio vicino, con il link a rifugi.cai.it -----------------------
    $next = $this->get($e02Url)->assertSuccessful();

    expect(fase10SicaiText((string) $next->getContent()))
        ->toContain('Tappa 2 di 2 · Sentiero Italia CAI in Piemonte')
        ->toContain('Punti di appoggio vicini')
        ->toContain('Rifugio Mondovì')
        ->toContain('Vedi su rifugi.cai.it');
    $next->assertSee('href="https://rifugi.cai.it/shelters/921201004"', escape: false)
        ->assertSee('href="'.RegionalGroupSicaiStages::getUrl().'"', escape: false);

    // --- GR Toscana ed Emilia-Romagna: le stesse tappe L, nessuna tappa E -----------------------
    // Sessione nuova a ogni utente: quella precedente conserva l'hash della password di un altro utente.
    foreach ([Region::Toscana, Region::EmiliaRomagna] as $region) {
        $this->flushSession();
        $group = fase10SicaiUser('GR '.$region->name, CustomerType::GruppoRegionale, $region);

        expect(fase10SicaiText((string) $this->actingAs($group)->get(RegionalGroupSicaiStages::getUrl())->assertSuccessful()->getContent()))
            ->toContain('L01')
            ->toContain('L02')
            ->not->toContain('E01');
        $this->get($e01Url)->assertForbidden();
    }

    // --- Utente Sezione: badge "In attesa" e pagina "in arrivo" ----------------------------------
    $this->flushSession();
    $sectionUser = fase10SicaiUser('Utente Pisa', CustomerType::Sezione);
    caiSection(['codice_cai' => '9226004', 'region' => 'TOSCANA', 'user_id' => $sectionUser->id]);

    $dashboard = $this->actingAs($sectionUser)->get(SectionDashboard::getUrl())->assertSuccessful();

    expect(str(fase10SicaiText((string) $dashboard->getContent()))->after('Sentieri di Sezione')->value())
        ->toContain('Tappe SICAI — In attesa');
    $dashboard->assertSee('href="'.SectionSicaiStages::getUrl().'"', escape: false);

    $this->get(SectionSicaiStages::getUrl())->assertSuccessful();
    $this->get(RegionalGroupSicaiStages::getUrl())->assertForbidden();
});
