<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\SectionTrails;
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

function sectionTrailsCustomer(?CustomerType $type = CustomerType::Sezione, string $name = 'Utente Parma'): User
{
    $customer = withRole(User::factory()->create(['name' => $name]), UserRole::Customer);
    $customer->forceFill(['customer_type' => $type])->save();

    return $customer;
}

/** HTML della pagina senza i commenti `<!--[if BLOCK]>` che Livewire mette attorno a ogni `@if`. */
function sectionTrailsHtml(Testable $component): string
{
    return (string) preg_replace('/<!--.*?-->/s', '', $component->html());
}

/**
 * Verifica che i testi compaiano in quest'ordine nell'HTML reso (prima occorrenza: la tabella desktop). Dopo un `call()`
 * `assertSeeTextInOrder` lavora sulla risposta JSON di Livewire, dove "→" è codificato: si usa l'HTML del componente.
 *
 * @param  list<string>  $texts
 */
function assertSectionTrailsOrder(Testable $component, array $texts): void
{
    $text = strip_tags(sectionTrailsHtml($component));
    $positions = array_map(fn (string $needle): int|false => mb_strpos($text, $needle), $texts);

    expect($positions)->not->toContain(false);

    $sorted = $positions;
    sort($sorted);

    expect($positions)->toBe($sorted);
}

/** @return list<int> id delle linee passate alla mappa */
function sectionTrailsMapIds(Testable $component): array
{
    preg_match('/data-cai-map-payload="([^"]*)"/', $component->html(), $matches);
    $payload = json_decode(html_entity_decode($matches[1] ?? '', ENT_QUOTES | ENT_HTML5), true, flags: JSON_THROW_ON_ERROR);

    return array_column($payload['lines'], 'id');
}

/**
 * Parma con quattro sentieri di stati, percorribilità, difficoltà e lunghezze diverse (24,0 km in tutto), uno condiviso
 * con Reggio Emilia e la SAT; più un sentiero della sola Reggio Emilia.
 */
function sectionTrailsFixture(User $user): void
{
    caiSection(['codice_cai' => '9224001', 'name' => 'SEZ. PARMA', 'user_id' => $user->id]);
    caiSection(['codice_cai' => '9224002', 'name' => 'SEZ. REGGIO EMILIA']);
    caiSection(['codice_cai' => '9218001', 'name' => 'SEZ. S.A.T.']);

    caiTrail(['id' => 1, 'ref' => '10', 'name' => 'Sentiero dei Fiori', 'from_place' => 'Lagdei', 'to_place' => 'Lago Santo',
        'length_km' => 12.3, 'cai_scale' => 'E', 'status' => CaiTrailStatus::Validated,
        'practicability' => CaiTrailPracticability::Practicable, 'issues_last_update' => '2026-09-18'],
        ['9224001', '9224002', '9218001', '9299999']);
    caiTrail(['id' => 2, 'ref' => '2', 'name' => 'Sentiero delle Malghe', 'from_place' => 'Bosco di Corniglio', 'to_place' => null,
        'length_km' => 3.1, 'cai_scale' => null, 'status' => CaiTrailStatus::Difficulty,
        'practicability' => CaiTrailPracticability::Unknown], ['9224001']);
    caiTrail(['id' => 3, 'ref' => '101', 'name' => 'Via ferrata del Monte Orsaro', 'length_km' => 7.6, 'cai_scale' => 'EEA:PD',
        'status' => CaiTrailStatus::PreValidated, 'practicability' => CaiTrailPracticability::NotPracticable], ['9224001']);
    caiTrail(['id' => 4, 'ref' => null, 'name' => null, 'from_place' => 'Prato Spilla', 'to_place' => 'Lago Verde',
        'length_km' => 1.0, 'cai_scale' => 'EE', 'status' => CaiTrailStatus::Reconnaissance,
        'practicability' => CaiTrailPracticability::WithDifficulty], ['9224001']);
    caiTrail(['id' => 5, 'ref' => '600', 'name' => 'Sentiero di Reggio'], ['9224002']);
}

test('the page shows the header, the totals, the summary and every trail of the section', function (): void {
    $user = sectionTrailsCustomer();
    sectionTrailsFixture($user);
    $this->actingAs($user);

    $component = Livewire::test(SectionTrails::class)
        ->assertSuccessful()
        ->assertSeeText('← Dashboard')
        ->assertSee('href="'.Dashboard::getUrl().'"', escape: false)
        ->assertSeeText('Sentieri di Sezione')
        ->assertSeeText('SEZ. PARMA')
        ->assertSeeText('Km per stato di accatastamento')
        ->assertSeeText('Percorribilità')
        ->assertSeeText('Sentiero dei Fiori')
        ->assertSeeText('Lagdei → Lago Santo')
        ->assertSeeText('Bosco di Corniglio → —')
        ->assertSeeText('12,3')
        ->assertSeeText('EEA:PD')
        ->assertSeeText('agg. 18/09/2026')
        ->assertSeeText('4 sentieri · 24,0 km')
        ->assertSeeText('Fonte: osm2cai · catasto sentieri CAI')
        ->assertDontSeeText('Sentiero di Reggio');

    $html = sectionTrailsHtml($component);

    expect($html)
        ->toMatch('/Km totali<\/span>\s*<span class="ms-trails__kpi-value">24,0</')
        ->toMatch('/Sentieri<\/span>\s*<span class="ms-trails__kpi-value">4</')
        // Barra: segmento con l'etichetta solo oltre il 9% (Ricognizione 1,0 km = 4%, senza etichetta).
        ->toContain('title="1 · Difficoltà · 3,1 km" style="width: 12.92%; background: #e8b500; color: #3c3000">3,1 km</div>')
        ->toContain('title="2 · Ricognizione · 1,0 km" style="width: 4.17%; background: #f07c22; color: #ffffff"></div>')
        ->toContain('title="4 · Validato · 12,3 km" style="width: 51.25%; background: #2563c9; color: #ffffff">12,3 km</div>')
        // Legenda: km, quota, numero di sentieri.
        ->toMatch('/4 · Validato<\/span>\s*<span class="ms-trails__legend-value">12,3 km <span class="ms-trails__legend-note">51%<span class="ms-trails__legend-count"> · 1 sentiero</')
        ->toMatch('/3 · Pre-validato<\/span>\s*<span class="ms-trails__legend-value">7,6 km <span class="ms-trails__legend-note">32%/')
        // Percorribilità: km e numero per ognuna delle quattro.
        ->toMatch('/>Percorribile<\/span>\s*<span class="ms-trails__legend-value">12,3 km <span class="ms-trails__legend-note ms-trails__legend-count">1 sentiero</')
        ->toMatch('/>Non percorribile<\/span>\s*<span class="ms-trails__legend-value">7,6 km/')
        ->toMatch('/>Sconosciuto<\/span>\s*<span class="ms-trails__legend-value">3,1 km/')
        // Chip per stato con il numero di sentieri.
        ->toMatch('/4 · Validato\s*<span class="ms-trails__chip-count">1</')
        // Senza numero il segnavia mostra "—", senza nome il nome è "—" in grigio.
        ->toContain('ms-cai-trail-sign--md">—</span>')
        ->toContain('<span class="ms-trails__name ms-trails__missing">—</span>')
        ->toContain('--ms-cai-trail-status-color: #a24cc7">3 · Pre-validato</span>');

    expect(sectionTrailsMapIds($component))->toEqualCanonicalizing([1, 2, 3, 4]);
});

test('a shared trail names the other sections in short form, ignoring codes without a section', function (): void {
    $user = sectionTrailsCustomer();
    sectionTrailsFixture($user);
    $this->actingAs($user);

    $component = Livewire::test(SectionTrails::class)
        ->assertSeeText('Condiviso con CAI Reggio Emilia, SAT');

    // Una sola volta su desktop e una su mobile: gli altri sentieri non sono condivisi.
    expect(substr_count(sectionTrailsHtml($component), 'Condiviso con'))->toBe(2);
});

test('the trails are sorted by number in natural order by default, with the missing number last', function (): void {
    $user = sectionTrailsCustomer();
    sectionTrailsFixture($user);
    $this->actingAs($user);

    $component = Livewire::test(SectionTrails::class);
    assertSectionTrailsOrder($component, ['Sentiero delle Malghe', 'Sentiero dei Fiori', 'Via ferrata del Monte Orsaro', 'Prato Spilla → Lago Verde']);

    $component->call('sortBy', 'numero')->assertSet('sort', 'numero-desc');
    assertSectionTrailsOrder($component, ['Via ferrata del Monte Orsaro', 'Sentiero dei Fiori', 'Sentiero delle Malghe', 'Prato Spilla → Lago Verde']);

    $component->call('sortBy', 'numero')->assertSet('sort', null);
});

test('sorting by km and by status starts from the longest and from validated, and toggles', function (): void {
    $user = sectionTrailsCustomer();
    sectionTrailsFixture($user);
    $this->actingAs($user);

    $component = Livewire::test(SectionTrails::class)->call('sortBy', 'km')->assertSet('sort', 'km-desc');
    assertSectionTrailsOrder($component, ['Sentiero dei Fiori', 'Via ferrata del Monte Orsaro', 'Sentiero delle Malghe', 'Prato Spilla → Lago Verde']);

    $component->call('sortBy', 'km')->assertSet('sort', 'km');
    assertSectionTrailsOrder($component, ['Prato Spilla → Lago Verde', 'Sentiero delle Malghe', 'Via ferrata del Monte Orsaro', 'Sentiero dei Fiori']);

    $component->call('sortBy', 'stato')->assertSet('sort', 'stato-desc');
    assertSectionTrailsOrder($component, ['Sentiero dei Fiori', 'Via ferrata del Monte Orsaro', 'Prato Spilla → Lago Verde', 'Sentiero delle Malghe']);

    $component->call('applySortOption', 'stato')->assertSet('sort', 'stato');
    assertSectionTrailsOrder($component, ['Sentiero delle Malghe', 'Prato Spilla → Lago Verde', 'Via ferrata del Monte Orsaro', 'Sentiero dei Fiori']);

    $component->call('applySortOption', 'sconosciuto')->assertSet('sort', null);
});

test('the status chips combine, and the list, the count and the map follow the filters', function (): void {
    $user = sectionTrailsCustomer();
    sectionTrailsFixture($user);
    $this->actingAs($user);

    $component = Livewire::test(SectionTrails::class)
        ->call('toggleStatus', 4)
        ->call('toggleStatus', 3)
        ->assertSet('statuses', ['3', '4'])
        ->assertSeeText('Sentiero dei Fiori')
        ->assertSeeText('Via ferrata del Monte Orsaro')
        ->assertDontSeeText('Sentiero delle Malghe')
        ->assertSeeText('2 di 4 sentieri · 19,9 km');

    expect(sectionTrailsMapIds($component))->toEqualCanonicalizing([1, 3]);

    $component->call('toggleStatus', 4)
        ->assertSet('statuses', ['3'])
        ->call('toggleStatus', 9)
        ->assertSet('statuses', ['3'])
        ->call('clearStatuses')
        ->assertSet('statuses', [])
        ->assertSeeText('4 sentieri · 24,0 km');
});

test('the difficulty filter groups the osm2cai variants and finds the trails without a difficulty', function (): void {
    $user = sectionTrailsCustomer();
    sectionTrailsFixture($user);
    $this->actingAs($user);

    Livewire::test(SectionTrails::class)
        ->call('filterDifficulty', 'eea')
        ->assertSet('difficulty', 'eea')
        ->assertSeeText('Via ferrata del Monte Orsaro')
        ->assertSeeText('1 di 4 sentieri · 7,6 km')
        ->call('filterDifficulty', 'nd')
        ->assertSeeText('Sentiero delle Malghe')
        ->assertDontSeeText('Sentiero dei Fiori')
        ->call('filterDifficulty', '')
        ->assertSet('difficulty', null)
        ->assertSeeText('4 sentieri · 24,0 km');
});

test('the practicability filter keeps only the trails with that practicability', function (): void {
    $user = sectionTrailsCustomer();
    sectionTrailsFixture($user);
    $this->actingAs($user);

    Livewire::test(SectionTrails::class)
        ->call('filterPracticability', 'con-difficolta')
        ->assertSet('practicability', 'con-difficolta')
        ->assertSeeText('Prato Spilla → Lago Verde')
        ->assertDontSeeText('Sentiero dei Fiori')
        ->call('filterPracticability', 'boh')
        ->assertSet('practicability', null);
});

test('filters without results show the reset link, which clears them', function (): void {
    $user = sectionTrailsCustomer();
    sectionTrailsFixture($user);
    $this->actingAs($user);

    $component = Livewire::test(SectionTrails::class)
        ->call('toggleStatus', 4)
        ->call('filterDifficulty', 'eea')
        ->assertSeeText('Nessun sentiero corrisponde ai filtri selezionati.')
        ->assertSeeText('Azzera filtri')
        ->assertSeeText('0 di 4 sentieri · 0,0 km');

    expect(sectionTrailsMapIds($component))->toBe([]);

    $component->call('resetFilters')
        ->assertSet('statuses', [])
        ->assertSet('difficulty', null)
        ->assertSet('practicability', null)
        ->assertDontSeeText('Nessun sentiero corrisponde ai filtri selezionati.');
});

test('filters and sort are read from the URL and unknown values count as absent', function (): void {
    $user = sectionTrailsCustomer();
    sectionTrailsFixture($user);
    $this->actingAs($user);

    Livewire::withQueryParams(['stato' => ['4', '3'], 'percorribilita' => 'non-percorribile', 'ordina' => 'km-desc'])
        ->test(SectionTrails::class)
        ->assertSeeText('Via ferrata del Monte Orsaro')
        ->assertDontSeeText('Sentiero dei Fiori')
        ->assertSeeText('1 di 4 sentieri · 7,6 km');

    Livewire::withQueryParams(['stato' => ['9', 'x'], 'difficolta' => 'xx', 'percorribilita' => 'boh', 'ordina' => 'altezza'])
        ->test(SectionTrails::class)
        ->assertSeeText('4 sentieri · 24,0 km')
        ->assertSet('sort', 'altezza')
        ->assertSeeTextInOrder(['Sentiero delle Malghe', 'Sentiero dei Fiori']);
});

test('on mobile the first 12 cards are shown, then "Mostra altri N"', function (): void {
    $user = sectionTrailsCustomer();
    caiSection(['codice_cai' => '9234004', 'name' => 'SEZ. SULMONA', 'user_id' => $user->id]);

    foreach (range(1, 14) as $number) {
        caiTrail(['ref' => (string) $number], ['9234004']);
    }

    $this->actingAs($user);

    Livewire::test(SectionTrails::class)
        ->assertSeeText('Mostra altri 2')
        ->set('showAll', true)
        ->assertDontSeeText('Mostra altri');
});

test('a section without trails sees the empty state and no summary', function (): void {
    $user = sectionTrailsCustomer();
    caiSection(['codice_cai' => '9226021', 'name' => 'SEZ. LIVORNO', 'user_id' => $user->id]);
    $this->actingAs($user);

    $component = Livewire::test(SectionTrails::class)
        ->assertSuccessful()
        ->assertSeeText('Nessun sentiero associato a questa Sezione nel catasto osm2cai')
        ->assertSeeText('Vai a osm2cai ↗')
        ->assertDontSeeText('Km per stato di accatastamento')
        ->assertDontSeeText('Nessun sentiero corrisponde ai filtri');

    expect(sectionTrailsHtml($component))
        ->toMatch('/Km totali<\/span>\s*<span class="ms-trails__kpi-value">—</')
        ->toContain('ms-trails__body--hidden');
});

test('a subsection user and a user without a section see a notice and no error', function (bool $withSubsection): void {
    $user = sectionTrailsCustomer(name: 'Utente senza Sezione');

    if ($withSubsection) {
        $section = caiSection(['codice_cai' => '9224001']);
        caiTrail(['name' => 'Sentiero della Sezione madre'], ['9224001']);
        CaiSubsection::create(['cai_codice' => '9224001001', 'cai_section_id' => $section->codice_cai,
            'name' => 'SOTTOSEZ. LANGHIRANO', 'user_id' => $user->id]);
    }

    $this->actingAs($user);

    Livewire::test(SectionTrails::class)
        ->assertSuccessful()
        ->assertSeeText($withSubsection ? 'SOTTOSEZ. LANGHIRANO' : 'Utente senza Sezione')
        ->assertSeeText($withSubsection
            ? 'I sentieri sono intestati alle Sezioni: per la Sottosezione non sono disponibili.'
            : 'Nessuna Sezione CAI collegata al tuo account: contatta Montagna Servizi.')
        ->assertDontSeeText('Sentiero della Sezione madre')
        ->assertDontSeeText('Nessun sentiero associato a questa Sezione');
})->with(['Sottosezione' => true, 'senza Sezione' => false]);

test('only a sezione customer can open the page, which is not in the navigation', function (?CustomerType $type): void {
    $this->actingAs(sectionTrailsCustomer($type))
        ->get(SectionTrails::getUrl())
        ->assertForbidden();

    expect(SectionTrails::shouldRegisterNavigation())->toBeFalse();
})->with([
    'Gruppo Regionale' => [CustomerType::GruppoRegionale],
    'Organo Tecnico' => [CustomerType::OrganoTecnicoStrutturaOperativa],
    'nessun tipo' => [null],
]);

test('staff cannot open the page and a sezione customer can, at the same slug as the coming soon page', function (): void {
    expect(SectionTrails::getUrl())->toEndWith('/sezione/sentieri');

    $this->actingAs(withRole(User::factory()->create(), UserRole::Developer))
        ->get(SectionTrails::getUrl())
        ->assertForbidden();

    $user = sectionTrailsCustomer();
    sectionTrailsFixture($user);

    $this->actingAs($user)
        ->get(SectionTrails::getUrl())
        ->assertSuccessful()
        ->assertSee('Sentiero dei Fiori')
        ->assertDontSee('Questa sezione è in arrivo.');
});
