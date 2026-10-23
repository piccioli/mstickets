<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiHutType;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\SectionHuts;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

function sectionHutsCustomer(?CustomerType $type = CustomerType::Sezione, string $name = 'Utente SAT'): User
{
    $customer = withRole(User::factory()->create(['name' => $name]), UserRole::Customer);
    $customer->forceFill(['customer_type' => $type])->save();

    return $customer;
}

/** HTML della pagina senza i commenti `<!--[if BLOCK]>` che Livewire mette attorno a ogni `@if`. */
function sectionHutsHtml(Testable $component): string
{
    return (string) preg_replace('/<!--.*?-->/s', '', $component->html());
}

/** SAT con quattro rifugi di tipi, stati e quote diversi, più un rifugio di un'altra Sezione. */
function sectionHutsFixture(User $user): void
{
    caiSection(['codice_cai' => '9218001', 'name' => 'SAT - SOCIETÀ DEGLI ALPINISTI TRIDENTINI', 'user_id' => $user->id]);
    caiSection(['codice_cai' => '9226019', 'name' => 'SEZ. LUCCA']);

    caiHut(['id_cai' => 1, 'branch_code' => '9218001', 'name' => 'Rifugio Tosa', 'alias' => 'Rifugio della Tosa',
        'altitude' => 2491, 'beds' => 150, 'municipality' => 'San Lorenzo Dorsino', 'province' => 'TN',
        'photos' => ['https://rifugi.cai.it/storage/tosa.jpg'], 'source_updated_at' => '2026-09-12 10:00:00']);
    caiHut(['id_cai' => 2, 'branch_code' => '9218001', 'name' => 'Bivacco Bonvecchio', 'type' => CaiHutType::Bivouac,
        'altitude' => 2790, 'beds' => 9, 'municipality' => 'Spiazzo', 'source_updated_at' => '2024-07-03 10:00:00']);
    caiHut(['id_cai' => 3, 'branch_code' => '9218001', 'name' => 'Rifugio Peller', 'status' => CaiHutStatus::UnderRenovation,
        'altitude' => 2022, 'beds' => 40, 'municipality' => 'Cles']);
    caiHut(['id_cai' => 4, 'branch_code' => '9218001', 'name' => 'Agostini Capanna', 'type' => CaiHutType::SocialHut,
        'status' => CaiHutStatus::TemporarilyClosed, 'altitude' => null, 'beds' => null, 'municipality' => null]);
    caiHut(['id_cai' => 5, 'branch_code' => '9226019', 'name' => 'Rifugio Rossi alla Pania']);
}

test('the page shows the header, the counters and every hut of the section', function (): void {
    $user = sectionHutsCustomer();
    sectionHutsFixture($user);
    $this->actingAs($user);

    $component = Livewire::test(SectionHuts::class)
        ->assertSuccessful()
        ->assertSeeText('← Dashboard')
        ->assertSee('href="'.Dashboard::getUrl().'"', escape: false)
        ->assertSeeText('Rifugi di Sezione')
        ->assertSeeText('SAT - SOCIETÀ DEGLI ALPINISTI TRIDENTINI')
        ->assertSeeText('Rifugi in attività / totale')
        ->assertSeeText('/ 4')
        ->assertSeeText('Posti letto totali')
        ->assertSeeText('199')
        ->assertSeeText('Rifugio della Tosa')
        ->assertSeeText('San Lorenzo Dorsino (TN)')
        ->assertSeeText('2.491')
        ->assertSee('https://rifugi.cai.it/storage/tosa.jpg', escape: false)
        ->assertSeeText('In ristrutturazione')
        ->assertSeeText('4 strutture')
        ->assertSeeText('dati aggiornati al 12 settembre 2026')
        ->assertDontSeeText('Rifugio Rossi alla Pania');

    $html = sectionHutsHtml($component);

    // Due in attività su 4; i chip contano per tipo, "—" se nessuno.
    expect($html)->toMatch('/class="ms-huts__kpi-value">\s*2\s*<span class="ms-huts__kpi-total">\/ 4</')
        ->and($html)->toMatch('/Rifugio custodito\s*<span class="ms-huts__chip-count">2</')
        ->and($html)->toMatch('/Bivacco\s*<span class="ms-huts__chip-count">1</')
        ->and($html)->toMatch('/Rifugio incustodito\s*<span class="ms-huts__chip-count">—</');
});

test('the map receives a marker for every listed hut', function (): void {
    $user = sectionHutsCustomer();
    sectionHutsFixture($user);
    $this->actingAs($user);

    $html = Livewire::test(SectionHuts::class)->html();

    preg_match('/data-cai-map-payload="([^"]*)"/', $html, $match);
    $payload = json_decode(html_entity_decode($match[1]), true);

    expect(collect($payload['markers'])->pluck('id')->sort()->values()->all())->toBe([1, 2, 3, 4])
        ->and(collect($payload['markers'])->firstWhere('id', 2)['letter'])->toBe('B');
});

test('the default order is by name and the headers toggle name and altitude', function (): void {
    $user = sectionHutsCustomer();
    sectionHutsFixture($user);
    $this->actingAs($user);

    Livewire::test(SectionHuts::class)
        ->assertSeeTextInOrder(['Agostini Capanna', 'Bivacco Bonvecchio', 'Rifugio Peller', 'Rifugio Tosa'])
        ->call('sortByName')
        ->assertSet('sort', 'nome-desc')
        ->assertSeeTextInOrder(['Rifugio Tosa', 'Rifugio Peller', 'Bivacco Bonvecchio', 'Agostini Capanna'])
        ->call('sortByAltitude')
        ->assertSet('sort', 'quota-desc')
        ->assertSeeTextInOrder(['Bivacco Bonvecchio', 'Rifugio Tosa', 'Rifugio Peller', 'Agostini Capanna'])
        ->call('sortByAltitude')
        ->assertSet('sort', 'quota')
        ->assertSeeTextInOrder(['Rifugio Peller', 'Rifugio Tosa', 'Bivacco Bonvecchio', 'Agostini Capanna'])
        ->call('applySortOption', 'nome')
        ->assertSet('sort', null)
        ->call('applySortOption', 'sconosciuto')
        ->assertSet('sort', null);
});

test('type chips are a multiple selection and combine with the status filter', function (): void {
    $user = sectionHutsCustomer();
    sectionHutsFixture($user);
    $this->actingAs($user);

    Livewire::test(SectionHuts::class)
        ->call('toggleType', 'bivacco')
        ->assertSet('types', ['bivacco'])
        ->assertSeeText('1 di 4 strutture')
        ->assertSeeText('Bivacco Bonvecchio')
        ->assertDontSeeText('Rifugio Peller')
        ->call('toggleType', 'custodito')
        ->assertSet('types', ['custodito', 'bivacco'])
        ->assertSeeText('3 di 4 strutture')
        ->call('filterStatus', 'in-ristrutturazione')
        ->assertSet('status', 'in-ristrutturazione')
        ->assertSeeText('1 di 4 strutture')
        ->assertSeeText('Rifugio Peller')
        ->assertDontSeeText('Bivacco Bonvecchio')
        ->call('toggleType', 'bivacco')
        ->assertSet('types', ['custodito'])
        ->call('toggleType', 'inesistente')
        ->assertSet('types', ['custodito'])
        ->call('clearTypes')
        ->call('filterStatus', '')
        ->assertSet('types', [])
        ->assertSet('status', null)
        ->assertSeeText('4 strutture');
});

test('filters are read from the url and an unknown value counts as absent', function (): void {
    $user = sectionHutsCustomer();
    sectionHutsFixture($user);
    $this->actingAs($user);

    Livewire::withQueryParams(['tipo' => ['capanna', 'ignoto'], 'stato' => 'chiuso', 'ordina' => 'quota-desc'])
        ->test(SectionHuts::class)
        ->assertSeeText('1 di 4 strutture')
        ->assertSeeText('Agostini Capanna');

    Livewire::withQueryParams(['stato' => 'ignoto', 'ordina' => 'ignoto'])
        ->test(SectionHuts::class)
        ->assertSeeText('4 strutture')
        ->assertSeeTextInOrder(['Agostini Capanna', 'Bivacco Bonvecchio']);
});

test('filters without results show the message and reset restores the list', function (): void {
    $user = sectionHutsCustomer();
    sectionHutsFixture($user);
    $this->actingAs($user);

    Livewire::test(SectionHuts::class)
        ->call('toggleType', 'appoggio')
        ->assertSeeText('Nessun rifugio corrisponde ai filtri selezionati.')
        ->assertSeeText('0 di 4 strutture')
        ->call('resetFilters')
        ->assertDontSeeText('Nessun rifugio corrisponde ai filtri selezionati.')
        ->assertSeeText('4 strutture');
});

test('on mobile only the first 12 huts are listed until "Mostra altri"', function (): void {
    $user = sectionHutsCustomer();
    caiSection(['codice_cai' => '9218001', 'name' => 'SAT', 'user_id' => $user->id]);

    foreach (range(1, 15) as $i) {
        caiHut(['branch_code' => '9218001', 'name' => sprintf('Rifugio %02d', $i)]);
    }

    $this->actingAs($user);

    $component = Livewire::test(SectionHuts::class)->assertSeeText('Mostra altri 3');
    expect(substr_count($component->html(), 'class="ms-huts__card"'))->toBe(12);

    $component->set('showAll', true)->assertDontSeeText('Mostra altri');
    expect(substr_count($component->html(), 'class="ms-huts__card"'))->toBe(15);
});

test('a section with a single hut shows it with singular labels', function (): void {
    $user = sectionHutsCustomer();
    caiSection(['codice_cai' => '9226019', 'name' => 'SEZ. LUCCA', 'user_id' => $user->id]);
    caiHut(['branch_code' => '9226019', 'name' => 'Rifugio Rossi alla Pania', 'beds' => 26]);
    $this->actingAs($user);

    Livewire::test(SectionHuts::class)
        ->assertSeeText('SEZ. LUCCA')
        ->assertSeeText('Rifugio Rossi alla Pania')
        ->assertSeeText('1 struttura')
        ->assertSeeText('/ 1')
        ->assertDontSeeText('Mostra altri');
});

test('a section without huts shows the empty state and dashes in the counters', function (): void {
    $user = sectionHutsCustomer();
    caiSection(['codice_cai' => '9226026', 'name' => 'SEZ. LIVORNO', 'user_id' => $user->id]);
    $this->actingAs($user);

    $html = sectionHutsHtml(Livewire::test(SectionHuts::class)
        ->assertSuccessful()
        ->assertSeeText('Nessun rifugio associato a questa Sezione nella banca dati rifugi.cai.it')
        ->assertSeeText('Vai a rifugi.cai.it ↗')
        ->assertDontSeeText('Nessun rifugio corrisponde ai filtri'));

    expect($html)->toMatch('/class="ms-huts__kpi-value">\s*—\s*<\/span>/')
        ->and($html)->toContain('ms-huts__body--hidden');
});

test('a subsection user and a user without a section see a notice and no error', function (bool $withSubsection): void {
    $user = sectionHutsCustomer(name: 'Utente senza Sezione');

    if ($withSubsection) {
        $section = caiSection(['codice_cai' => '9226003']);
        caiHut(['branch_code' => '9226003', 'name' => 'Rifugio della Sezione madre']);
        CaiSubsection::create(['cai_codice' => '9226003001', 'cai_section_id' => $section->codice_cai,
            'name' => 'SOTTOSEZ. CASCINA', 'user_id' => $user->id]);
    }

    $this->actingAs($user);

    Livewire::test(SectionHuts::class)
        ->assertSuccessful()
        ->assertSeeText($withSubsection ? 'SOTTOSEZ. CASCINA' : 'Utente senza Sezione')
        ->assertSeeText($withSubsection
            ? 'I rifugi sono intestati alle Sezioni: per la Sottosezione non sono disponibili.'
            : 'Nessuna Sezione CAI collegata al tuo account: contatta Montagna Servizi.')
        ->assertDontSeeText('Rifugio della Sezione madre')
        ->assertDontSeeText('Nessun rifugio associato a questa Sezione');
})->with(['Sottosezione' => true, 'senza Sezione' => false]);

test('only a sezione customer can open the page, which is not in the navigation', function (?CustomerType $type): void {
    $this->actingAs(sectionHutsCustomer($type))
        ->get(SectionHuts::getUrl())
        ->assertForbidden();
})->with([
    'Gruppo Regionale' => [CustomerType::GruppoRegionale],
    'Organo Tecnico' => [CustomerType::OrganoTecnicoStrutturaOperativa],
    'nessun tipo' => [null],
]);

test('staff cannot open the page and a sezione customer can', function (): void {
    $this->actingAs(withRole(User::factory()->create(), UserRole::Developer))
        ->get(SectionHuts::getUrl())
        ->assertForbidden();

    $user = sectionHutsCustomer();
    sectionHutsFixture($user);

    $this->actingAs($user)
        ->get(SectionHuts::getUrl())
        ->assertSuccessful()
        ->assertSeeText('Rifugio Tosa');

    expect(SectionHuts::shouldRegisterNavigation())->toBeFalse();
});
