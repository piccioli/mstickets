<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Models\CaiSicaiStage;
use App\Domain\CaiDirectory\Policies\CaiSicaiStagePolicy;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\CaiTrailDetail;
use App\Filament\Pages\RegionalGroupSicaiStages;
use App\Filament\Pages\RegionalGroupTrails;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);

    // Piemonte: E01 (due punti vicini), E02 (non percorribile, con variante E02S e un punto), E03 (nessun punto).
    $this->e01 = grSicaiStage('E01', ['from_place' => 'Limone Piemonte', 'to_place' => 'Palanfrè', 'length_km' => 10.0]);
    $this->e02 = grSicaiStage('E02', ['from_place' => 'Palanfrè', 'to_place' => 'Entracque', 'length_km' => 8.0, 'practicability' => CaiTrailPracticability::NotPracticable]);
    $this->e02s = grSicaiStage('E02S', ['from_place' => 'Palanfrè', 'to_place' => 'Trinità', 'length_km' => 9.0]);
    $this->e03 = grSicaiStage('E03', ['from_place' => 'Entracque', 'to_place' => 'Terme di Valdieri', 'length_km' => 7.0]);
    // Toscana/Emilia-Romagna: L01 senza Sezioni né territori; Lombardia: D01 senza punti in tutta la lettera.
    $this->l01 = grSicaiStage('L01', ['from_place' => 'Passo dei Due Santi', 'to_place' => 'Lago Santo']);
    $this->d01 = grSicaiStage('D01', ['from_place' => 'Maccagno', 'to_place' => 'Curiglia']);

    $hut = caiHut(['id_cai' => 921201004, 'name' => 'Rifugio Garelli']);
    // Ordine di inserimento opposto a quello di percorrenza: la scheda ordina per posizione.
    grSicaiRestPoint(1, 'Albergo del Colle', 'Piemonte', 'Hotel', [], ['municipality' => 'Limone Piemonte', 'website' => 'www.albergocolle.it',
        'fields' => ['type' => 'Hotel', 'codice_fiscale' => 'RSSMRA80A01H501U']]);
    grSicaiRestPoint(2, 'Rifugio Garelli', 'Piemonte', 'Rifugio', [], ['municipality' => 'Chiusa di Pesio', 'operator' => 'CAI Mondovì',
        'phone' => '0171 738078', 'cai_hut_id_cai' => $hut->id_cai]);
    grSicaiRestPoint(3, 'B&B Palanfrè', 'Piemonte', 'B&B', [$this->e02]);
    DB::table('cai_sicai_stage_rest_point')->insert([
        ['cai_trail_id' => $this->e01->cai_trail_id, 'rest_point_id' => 1, 'distance_m' => 820, 'position' => 7400],
        ['cai_trail_id' => $this->e01->cai_trail_id, 'rest_point_id' => 2, 'distance_m' => 40, 'position' => 2500],
    ]);

    $this->piemonte = sicaiSheetCustomer(CustomerType::GruppoRegionale, Region::Piemonte);
});

function sicaiSheetCustomer(CustomerType $type, ?Region $region = null): User
{
    $user = withRole(User::factory()->create(), UserRole::Customer);
    $user->forceFill(['customer_type' => $type, 'region' => $region])->save();

    return $user;
}

/**
 * Testo della risposta senza tag né commenti Livewire, con gli spazi compattati: il testo **desktop**, senza le varianti
 * brevi `ms-trail__mobile-inline` ("← Precedente") né le card mobile dei punti.
 */
function sicaiSheetText(TestResponse $response): string
{
    $html = (string) preg_replace([
        '/<!--.*?-->/s',
        '/<script\b.*?<\/script>/s',
        '/<style\b.*?<\/style>/s',
        '/<span class="[^"]*ms-trail__mobile-inline[^"]*">[^<]*<\/span>/',
        '/<ul class="ms-trail-sicai__cards.*?<\/ul>/s',
    ], '', (string) $response->getContent());

    return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)));
}

function sicaiSheetUrl(CaiSicaiStage $stage, bool $fromSicai = true): string
{
    return CaiTrailDetail::getUrl(['trailId' => $stage->cai_trail_id, ...($fromSicai ? ['da' => CaiTrailDetail::FROM_SICAI] : [])]);
}

test('a stage sheet shows the stage code in the header and goes back to "Tappe SICAI" with ?da=sicai', function (): void {
    $response = $this->actingAs($this->piemonte)->get(sicaiSheetUrl($this->e02))->assertOk();
    $text = sicaiSheetText($response);

    $response->assertSee('ms-cai-trail-sign--sicai', escape: false)
        ->assertSee('href="'.RegionalGroupSicaiStages::getUrl().'"', escape: false);
    expect($text)->toContain('Tappe SICAI / Tappa E02')
        ->toContain('← Tappe SICAI')
        ->not->toContain('Sentieri del GR');

    // Senza ?da=sicai il GR torna a "Sentieri del GR", come per ogni sentiero.
    $this->actingAs($this->piemonte)->get(sicaiSheetUrl($this->e02, fromSicai: false))
        ->assertOk()
        ->assertSee('href="'.RegionalGroupTrails::getUrl().'"', escape: false);
});

test('the navigation links previous and next main stages of the same letter, with the variants', function (): void {
    $response = $this->actingAs($this->piemonte)->get(sicaiSheetUrl($this->e02))->assertOk();
    $text = sicaiSheetText($response);

    expect($text)->toContain('Tappa precedente E01 · Limone Piemonte → Palanfrè')
        ->toContain('Tappa successiva E03 · Entracque → Terme di Valdieri')
        ->toContain('Tappa 2 di 3 · Sentiero Italia CAI in Piemonte')
        ->toContain('Varianti E02S')
        ->not->toContain('D01')
        ->not->toContain('L01');

    $response->assertSee('href="'.e(sicaiSheetUrl($this->e01)).'"', escape: false)
        ->assertSee('href="'.e(sicaiSheetUrl($this->e03)).'"', escape: false)
        ->assertSee('href="'.e(sicaiSheetUrl($this->e02s)).'"', escape: false);
});

test('a variant navigates like its main stage and links back to it', function (): void {
    $text = sicaiSheetText($this->actingAs($this->piemonte)->get(sicaiSheetUrl($this->e02s))->assertOk());

    expect($text)->toContain('Variante della tappa E02 · Sentiero Italia CAI in Piemonte')
        ->toContain('Tappa precedente E01')
        ->toContain('Tappa successiva E03')
        ->toContain('Torna alla tappa E02')
        ->not->toContain('Varianti');
});

test('the first and the last stage of the letter say so instead of a link', function (): void {
    expect(sicaiSheetText($this->actingAs($this->piemonte)->get(sicaiSheetUrl($this->e01))))
        ->toContain('Tappa precedente E01 è la prima tappa in Piemonte')
        ->toContain('Tappa 1 di 3');

    expect(sicaiSheetText($this->actingAs($this->piemonte)->get(sicaiSheetUrl($this->e03))))
        ->toContain('Tappa successiva E03 è l’ultima tappa in Piemonte')
        ->toContain('Tappa 3 di 3');
});

test('outside "Tappe SICAI" the navigation links keep no ?da parameter', function (): void {
    $this->actingAs($this->piemonte)->get(sicaiSheetUrl($this->e02, fromSicai: false))
        ->assertOk()
        ->assertSee('href="'.e(sicaiSheetUrl($this->e01, fromSicai: false)).'"', escape: false);
});

test('the near rest points are listed in travel order with km, distance, contacts and the rifugi.cai.it link', function (): void {
    $response = $this->actingAs($this->piemonte)->get(sicaiSheetUrl($this->e01))->assertOk();
    $text = sicaiSheetText($response);

    expect($text)->toContain('Punti di appoggio vicini 2 strutture entro 1,5 km dal tracciato, in ordine di percorrenza')
        ->toContain('2,5 Rifugio Garelli Rifugio · sul tracciato Chiusa di Pesio CAI Mondovì 0171 738078 Vedi su rifugi.cai.it ↗')
        ->toContain('7,4 Albergo del Colle Hotel · a 0,8 km dal tracciato Limone Piemonte — — www.albergocolle.it')
        ->toContain('Limone Piemonte · km 0')
        ->toContain('Palanfrè · km 10,0')
        ->not->toContain('RSSMRA80A01H501U');

    expect(strpos($text, 'Rifugio Garelli'))->toBeLessThan(strpos($text, 'Albergo del Colle'));

    $response->assertSee('href="https://rifugi.cai.it/shelters/921201004"', escape: false)
        ->assertSee('href="https://www.albergocolle.it"', escape: false)
        ->assertSee('left: 25.0%', escape: false)
        ->assertSee('left: 74.0%', escape: false);
});

test('a stage without near points points to the adjacent stages that have some', function (): void {
    $response = $this->actingAs($this->piemonte)->get(sicaiSheetUrl($this->e03))->assertOk();

    expect(sicaiSheetText($response))
        ->toContain('Punti di appoggio vicini entro 1,5 km dal tracciato')
        ->toContain('Nessun punto di appoggio entro 1,5 km da questa tappa')
        ->toContain('I più vicini sono nelle tappe adiacenti (E02).');
    $response->assertSee('<a href="'.e(sicaiSheetUrl($this->e02)).'">E02</a>', escape: false);
});

test('a letter without any near point says the database has none in the region', function (): void {
    $lombardia = sicaiSheetCustomer(CustomerType::GruppoRegionale, Region::Lombardia);

    expect(sicaiSheetText($this->actingAs($lombardia)->get(sicaiSheetUrl($this->d01))->assertOk()))
        ->toContain('Nessun punto di appoggio censito in Lombardia')
        ->toContain('Quando la banca dati del Sentiero Italia CAI li riporterà, compariranno qui.')
        ->toContain('D01 è la prima tappa in Lombardia')
        ->toContain('D01 è l’ultima tappa in Lombardia');
});

test('both regional groups of a shared letter open its stages, even without sections of theirs', function (): void {
    $toscana = sicaiSheetCustomer(CustomerType::GruppoRegionale, Region::Toscana);
    $emilia = sicaiSheetCustomer(CustomerType::GruppoRegionale, Region::EmiliaRomagna);

    expect(sicaiSheetText($this->actingAs($toscana)->get(sicaiSheetUrl($this->l01))->assertOk()))
        ->toContain('Tappa 1 di 1 · Sentiero Italia CAI in Toscana');
    expect(sicaiSheetText($this->actingAs($emilia)->get(sicaiSheetUrl($this->l01))->assertOk()))
        ->toContain('Tappa 1 di 1 · Sentiero Italia CAI in Emilia-Romagna');
});

test('regional groups of other letters, sections and generic customers get 403 on a stage', function (): void {
    $lombardia = sicaiSheetCustomer(CustomerType::GruppoRegionale, Region::Lombardia);
    $withoutRegion = sicaiSheetCustomer(CustomerType::GruppoRegionale);
    $section = sicaiSheetCustomer(CustomerType::Sezione);

    $this->actingAs($lombardia)->get(sicaiSheetUrl($this->e01))->assertForbidden();
    $this->actingAs($withoutRegion)->get(sicaiSheetUrl($this->e01))->assertForbidden();
    $this->actingAs($section)->get(sicaiSheetUrl($this->e01))->assertForbidden();
    $this->actingAs($this->piemonte)->get(sicaiSheetUrl($this->l01))->assertForbidden();
});

test('staff opens any stage, with the regions of the letter in the position and no link to "Tappe SICAI"', function (): void {
    $staff = withRole(userWithPermissions(Permission::CaiDirectoryView), UserRole::Manager);

    $response = $this->actingAs($staff)->get(sicaiSheetUrl($this->l01))->assertOk();

    expect(sicaiSheetText($response))->toContain('Tappa 1 di 1 · Sentiero Italia CAI in Emilia-Romagna e Toscana')
        ->not->toContain('Tappe SICAI /');
});

test('a trail that is not a SICAI stage has neither navigation nor near points', function (): void {
    $trail = caiTrail(['ref' => '151C', 'name' => 'Sentiero della Val Brenta']);
    $staff = withRole(userWithPermissions(Permission::CaiDirectoryView), UserRole::Manager);

    $response = $this->actingAs($staff)->get(CaiTrailDetail::getUrl(['trailId' => $trail->id]))->assertOk();

    $response->assertDontSee('ms-trail-sicai__nav', escape: false)
        ->assertDontSee('ms-cai-trail-sign--sicai', escape: false)
        ->assertDontSeeText('Punti di appoggio vicini');
    expect(sicaiSheetText($response))->toContain('Sentiero 151C');
});

test('the stage policy lets the regional groups of the letter view a stage', function (): void {
    $policy = new CaiSicaiStagePolicy;
    $staff = withRole(userWithPermissions(Permission::CaiDirectoryView), UserRole::Manager);

    expect($policy->view($this->piemonte, $this->e01))->toBeTrue()
        ->and($policy->view($this->piemonte, $this->l01))->toBeFalse()
        ->and($policy->view(sicaiSheetCustomer(CustomerType::GruppoRegionale, Region::Toscana), $this->l01))->toBeTrue()
        ->and($policy->view(sicaiSheetCustomer(CustomerType::Sezione, Region::Piemonte), $this->e01))->toBeFalse()
        ->and($policy->view($staff, $this->d01))->toBeTrue();
});
