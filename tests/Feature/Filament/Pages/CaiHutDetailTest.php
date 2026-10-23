<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiHutType;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\CaiHutDetail;
use App\Filament\Pages\RegionalGroupHuts;
use App\Filament\Pages\SectionHuts;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
    Carbon::setTestNow('2026-10-23 10:00:00');

    $this->owner = hutDetailCustomer(CustomerType::Sezione);
    caiSection(['codice_cai' => '9218001', 'name' => 'SAT', 'region' => 'TRENTINO-ALTO ADIGE', 'user_id' => $this->owner->id]);
    caiSection(['codice_cai' => '9226019', 'name' => 'SEZ. LUCCA', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226005', 'name' => 'SEZ. CARRARA', 'region' => 'TOSCANA']);

    $this->tosa = caiHut([
        'id_cai' => 1, 'branch_code' => '9218001', 'name' => 'Rifugio Tosa e Tommaso Pedrotti', 'alias' => 'Rifugio della Tosa',
        'altitude' => 2491, 'latitude' => 46.1697, 'longitude' => 10.8764, 'locality' => 'Bocca di Brenta',
        'municipality' => 'San Lorenzo Dorsino', 'province' => 'TN', 'region' => 'Trentino-Alto Adige', 'valley' => 'Val Brenta',
        'massif' => 'Dolomiti di Brenta', 'protected_area' => 'Parco Naturale Adamello Brenta', 'beds' => 150, 'winter_beds' => 12,
        'photos' => ['https://rifugi.cai.it/storage/tosa.jpg'],
        'services' => ['acqua_in_rifugio' => 1, 'wifi' => 0, 'elettricita' => 1],
        'contacts' => ['phone' => '+39 0465 441244', 'email' => 'info@rifugiotosa.it', 'website' => 'https://www.rifugiotosa.it/',
            'facebook' => 'https://www.facebook.com/rifugiotosa/', 'instagram' => '@rifugio_tosa'],
        'opening_times' => [['types' => ['estiva'], 'start_date' => '2026-06-20', 'end_date' => '2026-09-20']],
        'management' => [
            'manager' => ['company' => 'Rifugi Brenta Snc', 'name' => 'Luca', 'surname' => 'Dallape', 'mobile_phone' => '+39 347 5512093',
                'email' => 'luca@rifugibrenta.it', 'pec' => 'rifugibrenta@pec.it'],
            'possession_type' => "Affitto ramo d'azienda",
            'contract' => ['start_date' => '2021-04-01', 'end_date' => '2027-03-31', 'fee' => 48564.05, 'fee_text' => '48564,05'],
            'property' => ['name' => 'Societa degli Alpinisti Tridentini', 'tax_code' => '80003770222'],
            'certifications' => ['iso14001' => 1, 'photovoltaic' => 0],
            'cin' => 'IT022165B4K7TQ2ZP9',
        ],
        'source_updated_at' => '2026-09-12 10:00:00',
    ]);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function hutDetailCustomer(CustomerType $type, ?Region $region = null): User
{
    $user = withRole(User::factory()->create(), UserRole::Customer);
    $user->forceFill(['customer_type' => $type, 'region' => $region])->save();

    return $user;
}

/** HTML della pagina senza i commenti `<!--[if BLOCK]>` che Livewire mette attorno a ogni `@if`. */
function hutDetailHtml(Testable $component): string
{
    return (string) preg_replace('/<!--.*?-->/s', '', $component->html());
}

/** Numero di servizi/certificazioni resi con un certo valore ("Sì", "No", "—"). */
function hutDetailFlagCount(string $html, string $modifier): int
{
    return preg_match_all('/class="ms-hut__(?:service|certification) ms-hut__service--'.$modifier.'"/', $html);
}

test('the owner section sees the full sheet of a managed hut', function (): void {
    $this->actingAs($this->owner);

    Livewire::test(CaiHutDetail::class, ['idCai' => '1'])
        ->assertSuccessful()
        ->assertSeeText('Rifugio Tosa e Tommaso Pedrotti')
        ->assertSeeText('Rifugio della Tosa')
        ->assertSeeText('Rifugio custodito')
        ->assertSeeText('In attività')
        ->assertSeeText('2.491 m')
        ->assertSee('https://rifugi.cai.it/storage/tosa.jpg', escape: false)
        ->assertSeeText('Dove si trova')
        ->assertSeeText('Bocca di Brenta')
        ->assertSeeText('Val Brenta')
        ->assertSeeText('Dolomiti di Brenta')
        ->assertSeeText('Parco Naturale Adamello Brenta')
        ->assertSeeText('46,16970° N · 10,87640° E')
        ->assertSeeText('Posti letto totali')
        ->assertSeeText('150')
        ->assertSeeText('Posti invernali')
        ->assertSee('href="tel:+390465441244"', escape: false)
        ->assertSee('href="mailto:info@rifugiotosa.it"', escape: false)
        ->assertSee('href="https://www.rifugiotosa.it/"', escape: false)
        ->assertSeeText('rifugiotosa.it')
        ->assertSee('href="https://www.facebook.com/rifugiotosa/"', escape: false)
        ->assertSee('href="https://instagram.com/rifugio_tosa"', escape: false)
        ->assertSeeText('Estiva: 20 giugno – 20 settembre 2026')
        ->assertSee('href="https://rifugi.cai.it/shelters/1"', escape: false)
        ->assertSeeText('Vedi su rifugi.cai.it ↗')
        ->assertSeeText('Ultimo aggiornamento del dato: 12 settembre 2026')
        ->assertSeeText('← Rifugi di Sezione')
        ->assertSee('href="'.SectionHuts::getUrl().'"', escape: false);
});

test('the 15 services show yes, no or a dash', function (): void {
    $this->actingAs($this->owner);

    $html = hutDetailHtml(Livewire::test(CaiHutDetail::class, ['idCai' => '1']));

    expect(hutDetailFlagCount($html, 'yes'))->toBe(2 + 1)   // acqua, elettricità + ISO 14001
        ->and(hutDetailFlagCount($html, 'no'))->toBe(1 + 1)  // wifi + fotovoltaico
        ->and(hutDetailFlagCount($html, 'unknown'))->toBe(12 + 6)
        ->and($html)->toMatch('/data-service="Acqua in rifugio">.*?ms-hut__service-value">Sì</s')
        ->and($html)->toMatch('/data-service="Wi-Fi">.*?ms-hut__service-value">No</s')
        ->and($html)->toMatch('/data-service="Docce">.*?ms-hut__service-value">—</s');
});

test('the management section shows manager, contract, property, certifications and cin', function (): void {
    $this->actingAs($this->owner);

    Livewire::test(CaiHutDetail::class, ['idCai' => '1'])
        ->assertSeeText('Gestione')
        ->assertSeeText('Riservato alla Sezione')
        ->assertSeeText('Rifugi Brenta Snc')
        ->assertSeeText('Luca Dallape')
        ->assertSeeText('+39 347 5512093')
        ->assertSeeText('rifugibrenta@pec.it')
        ->assertSeeText("Affitto ramo d'azienda")
        ->assertSeeText('01/04/2021')
        ->assertSeeText('31/03/2027')
        ->assertSeeText('€ 48.564,05')
        ->assertSeeText('Societa degli Alpinisti Tridentini')
        ->assertSeeText('80003770222')
        ->assertSeeText('IT022165B4K7TQ2ZP9')
        ->assertSeeText('Certificazioni e impianti')
        ->assertSeeText('ISO 14001');
});

test('a contract expiring within six months is highlighted in amber', function (): void {
    $this->actingAs($this->owner);

    $html = hutDetailHtml(Livewire::test(CaiHutDetail::class, ['idCai' => '1']));

    expect($html)->toContain('Contratto in scadenza')
        ->and($html)->toContain('Scade tra 5 mesi')
        ->and($html)->toContain('ms-hut__alert--expiring')
        ->and($html)->not->toContain('Contratto scaduto');
});

test('an expired contract is highlighted in red with the elapsed time', function (): void {
    $this->tosa->update(['management' => array_replace_recursive($this->tosa->management ?? [], [
        'contract' => ['end_date' => '2026-06-30'],
    ])]);
    $this->actingAs($this->owner);

    $html = hutDetailHtml(Livewire::test(CaiHutDetail::class, ['idCai' => '1']));

    expect($html)->toContain('Contratto scaduto')
        ->and($html)->toContain('Scaduto da 4 mesi')
        ->and($html)->toContain('30/06/2026')
        ->and($html)->toContain('ms-hut__alert--expired');
});

test('a contract far from expiry has no highlight', function (): void {
    $this->tosa->update(['management' => array_replace_recursive($this->tosa->management ?? [], [
        'contract' => ['end_date' => '2030-12-31'],
    ])]);
    $this->actingAs($this->owner);

    $html = hutDetailHtml(Livewire::test(CaiHutDetail::class, ['idCai' => '1']));

    expect($html)->toContain('31/12/2030')
        ->and($html)->not->toContain('ms-hut__alert--')
        ->and($html)->not->toContain('Contratto scaduto')
        ->and($html)->not->toContain('Contratto in scadenza');
});

test('a bivouac with missing data shows dashes and the photo placeholder', function (): void {
    caiHut(['id_cai' => 2, 'branch_code' => '9218001', 'name' => 'Bivacco Fratelli Bonvecchio', 'type' => CaiHutType::Bivouac,
        'alias' => null, 'altitude' => 2790, 'municipality' => null, 'province' => null, 'beds' => null, 'photos' => [],
        'services' => [], 'contacts' => [], 'management' => ['property' => ['name' => 'SAT']]]);
    $this->actingAs($this->owner);

    $html = hutDetailHtml(Livewire::test(CaiHutDetail::class, ['idCai' => '2'])->assertSuccessful());

    expect($html)->toContain('Bivacco Fratelli Bonvecchio')
        ->and($html)->toContain('ms-hut__hero--nophoto')
        ->and($html)->toContain('Nessuna foto disponibile su rifugi.cai.it')
        ->and($html)->toContain('Nessun gestore registrato nella banca dati.')
        ->and(hutDetailFlagCount($html, 'unknown'))->toBe(15 + 8)
        ->and($html)->toMatch('/<dt>Comune<\/dt>\s*<dd class="ms-hut__missing">—<\/dd>/')
        ->and($html)->toMatch('/<dt>Telefono<\/dt>\s*<dd class="ms-hut__missing">—<\/dd>/')
        ->and($html)->toContain('Ultimo aggiornamento del dato: <strong>—</strong>');
});

test('the regional group of the owner section and staff can open the sheet', function (): void {
    $group = hutDetailCustomer(CustomerType::GruppoRegionale, Region::TrentinoAltoAdige);
    $staff = withRole(userWithPermissions(Permission::CaiDirectoryView), UserRole::Manager);

    $this->actingAs($group)->get(CaiHutDetail::getUrl(['idCai' => 1]))
        ->assertOk()
        ->assertSeeText('Luca Dallape')
        ->assertSeeText('← Rifugi del GR');

    $this->actingAs($staff)->get(CaiHutDetail::getUrl(['idCai' => 1]))
        ->assertOk()
        ->assertSeeText('Luca Dallape');
});

test('the link back points to the regional group list when opened from it', function (): void {
    $group = hutDetailCustomer(CustomerType::GruppoRegionale, Region::TrentinoAltoAdige);
    $this->actingAs($group);

    Livewire::withQueryParams(['da' => 'gr'])->test(CaiHutDetail::class, ['idCai' => '1'])
        ->assertSeeText('← Rifugi del GR')
        ->assertSee('href="'.RegionalGroupHuts::getUrl().'"', escape: false)
        ->assertDontSeeText('Rifugi di Sezione');
});

test('another section, another regional group and other customers get 403, also with a manipulated id', function (): void {
    $other = hutDetailCustomer(CustomerType::Sezione);
    caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA', 'user_id' => $other->id]);
    caiSection(['codice_cai' => '9203001', 'region' => 'LOMBARDIA']);
    $otherGroup = hutDetailCustomer(CustomerType::GruppoRegionale, Region::Toscana);
    caiHut(['id_cai' => 3, 'branch_code' => '9226019', 'name' => 'Rifugio Rossi alla Pania']);

    foreach ([$other, $otherGroup] as $user) {
        $this->actingAs($user)->get(CaiHutDetail::getUrl(['idCai' => 1]))
            ->assertForbidden()
            ->assertDontSeeText('Luca Dallape');
    }

    // Il proprietario della SAT non apre il rifugio di Lucca cambiando l'id nell'URL.
    $this->actingAs($this->owner)->get(CaiHutDetail::getUrl(['idCai' => 3]))->assertForbidden();

    $plainCustomer = hutDetailCustomer(CustomerType::Generico);
    $this->actingAs($plainCustomer)->get(CaiHutDetail::getUrl(['idCai' => 1]))->assertForbidden();
});

test('an unknown hut id is a 404', function (): void {
    $this->actingAs($this->owner)->get(CaiHutDetail::getUrl(['idCai' => 999]))->assertNotFound();
    $this->actingAs($this->owner)->get(CaiHutDetail::getUrl(['idCai' => 'abc']))->assertNotFound();
});

test('without the management permission no management data is in the markup', function (): void {
    Gate::before(fn (User $user, string $ability): ?bool => $ability === 'viewManagement' ? false : null);
    $this->actingAs($this->owner);

    $html = hutDetailHtml(Livewire::test(CaiHutDetail::class, ['idCai' => '1'])->assertSuccessful());

    expect($html)->toContain('Rifugio Tosa e Tommaso Pedrotti')
        ->and($html)->not->toContain('Gestione')
        ->and($html)->not->toContain('Rifugi Brenta Snc')
        ->and($html)->not->toContain('Dallape')
        ->and($html)->not->toContain('rifugibrenta@pec.it')
        ->and($html)->not->toContain('48.564')
        ->and($html)->not->toContain('31/03/2027')
        ->and($html)->not->toContain('80003770222')
        ->and($html)->not->toContain('IT022165B4K7TQ2ZP9')
        ->and($html)->not->toContain('Contratto');
});

test('the section huts list links every row to its sheet', function (): void {
    $this->actingAs($this->owner);

    Livewire::test(SectionHuts::class)
        ->assertSee('href="'.CaiHutDetail::getUrl(['idCai' => 1]).'"', escape: false);
});
