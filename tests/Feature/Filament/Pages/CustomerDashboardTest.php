<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\Documentation\Enums\DocumentationCategory;
use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Actions\CreateActivityReport;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Filament\Pages\CustomerDashboard;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    Queue::fake();
});

test('a non-customer cannot access the customer dashboard', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $developer = withRole(User::factory()->create(), UserRole::Developer);

    $this->actingAs($developer)->get(CustomerDashboard::getUrl())->assertForbidden();
});

test('a customer can access the customer dashboard', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);

    $this->actingAs($customer)->get(CustomerDashboard::getUrl())->assertSuccessful();
});

test('the open tickets card shows the correct count for the current customer, scoped to own tickets', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $otherCustomer = withRole(User::factory()->create(), UserRole::Customer);

    ticket(['requester_id' => $customer->id, 'status' => TicketStatus::Todo]);
    ticket(['requester_id' => $customer->id, 'status' => TicketStatus::Progress]);
    ticket(['requester_id' => $customer->id, 'status' => TicketStatus::Done]);
    ticket(['requester_id' => $otherCustomer->id, 'status' => TicketStatus::Todo]);

    $this->actingAs($customer);

    expect(Livewire::test(CustomerDashboard::class)->instance()->openTicketsCount())->toBe(2);
});

test('the tickets awaiting response card lists only own tickets in waiting/problem status', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);

    $waiting = ticket(['requester_id' => $customer->id, 'status' => TicketStatus::Waiting]);
    $problem = ticket(['requester_id' => $customer->id, 'status' => TicketStatus::Problem]);
    $notAwaiting = ticket(['requester_id' => $customer->id, 'status' => TicketStatus::Progress]);

    $this->actingAs($customer);

    $tickets = Livewire::test(CustomerDashboard::class)->instance()->ticketsAwaitingResponse();

    expect($tickets->pluck('id'))->toContain($waiting->id, $problem->id)
        ->not->toContain($notAwaiting->id);
});

test('a customer with no open tickets and no tickets awaiting response sees explicit empty states', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Nessun ticket aperto')
        ->assertSee('Nessun ticket in attesa di una tua risposta');
});

test('the documentation card shows recent customer documentation, empty state when none', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Nessuna documentazione disponibile');

    DocumentationPage::create([
        'title' => 'Guida al portale',
        'slug' => 'guida-al-portale',
        'body' => 'Contenuto',
        'category' => DocumentationCategory::Customer,
    ]);
    DocumentationPage::create([
        'title' => 'Guida interna riservata',
        'slug' => 'guida-interna-riservata',
        'body' => 'Contenuto interno',
        'category' => DocumentationCategory::Internal,
    ]);

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Guida al portale')
        ->assertDontSee('Guida interna riservata');
});

test('the drive links appear only when valued on the authenticated user', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertDontSee('drive_url_placeholder');

    $customer->forceFill([
        'drive_url' => 'https://drive.example.com/my-folder',
        'drive_budget_url' => 'https://drive.example.com/my-budget',
    ])->save();

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('https://drive.example.com/my-folder', false)
        ->assertSee('https://drive.example.com/my-budget', false);
});

test('the activity reports card shows the customer own reports, empty state when none', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Nessun report attività');

    CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $customer->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 3,
    ]);

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Marzo 2026');
});

test('the fundraising projects card shows involved projects, empty state when none', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Nessun progetto fundraising');

    $staff = User::factory()->create();
    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando test',
        'deadline' => today()->addMonth()->toDateString(),
        'created_by' => $staff->id,
        'responsible_user_id' => $staff->id,
    ]);
    $project = FundraisingProject::create([
        'title' => 'Progetto coinvolto',
        'fundraising_opportunity_id' => $opportunity->id,
        'created_by' => $staff->id,
        'lead_user_id' => $customer->id,
    ]);

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Progetto coinvolto');

    expect(FundraisingProject::query()->involvingAsCustomer($customer)->pluck('id'))->toContain($project->id);
});

test('a customer with real data across every card sees all of it scoped to themselves', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(['drive_url' => 'https://drive.example.com/x']), UserRole::Customer);

    ticket(['requester_id' => $customer->id, 'status' => TicketStatus::Waiting]);

    DocumentationPage::create([
        'title' => 'Guida completa',
        'slug' => 'guida-completa',
        'body' => 'Contenuto',
        'category' => DocumentationCategory::Customer,
    ]);

    CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $customer->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 4,
    ]);

    $staff = User::factory()->create();
    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando completo',
        'deadline' => today()->addMonth()->toDateString(),
        'created_by' => $staff->id,
        'responsible_user_id' => $staff->id,
    ]);
    FundraisingProject::create([
        'title' => 'Progetto completo',
        'fundraising_opportunity_id' => $opportunity->id,
        'created_by' => $staff->id,
        'lead_user_id' => $customer->id,
    ]);

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSuccessful()
        ->assertSee('Guida completa')
        ->assertSee('Aprile 2026')
        ->assertSee('Progetto completo')
        ->assertSee('https://drive.example.com/x', false)
        ->assertDontSee('Nessun ticket aperto')
        ->assertDontSee('Nessun ticket in attesa di una tua risposta')
        ->assertDontSee('Nessuna documentazione disponibile')
        ->assertDontSee('Nessun report attività')
        ->assertDontSee('Nessun progetto fundraising');
});

test('the customer type badge shows the correct label with region for a sezione customer', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => CustomerType::Sezione, 'region' => Region::Lombardia])->save();

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Sezione — Lombardia');
});

test('the customer type badge shows just the type for a sezione customer without a region', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => CustomerType::Sezione, 'region' => null])->save();

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Sezione')
        ->assertDontSee('Sezione —', false);
});

test('the customer type badge shows the correct label with region for a gruppo regionale customer', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => Region::Abruzzo])->save();

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Gruppo Regionale — Abruzzo');
});

test('the customer type badge shows only the type for an organo tecnico/struttura operativa customer', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => CustomerType::OrganoTecnicoStrutturaOperativa, 'region' => null])->save();

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Organo Tecnico Centrale / Struttura Operativa');
});

test('the customer type badge shows only the type for a generico customer', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => CustomerType::Generico, 'region' => null])->save();

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Cliente generico');
});

test('the customer type badge is absent when the customer has no customer_type classified', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => null, 'region' => null])->save();

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertDontSee('Sezione')
        ->assertDontSee('Gruppo Regionale')
        ->assertDontSee('Organo Tecnico Centrale')
        ->assertDontSee('Cliente generico');
});

test('no reference to a support chat link is ever shown on the customer dashboard', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertDontSee('help_desk_chat')
        ->assertDontSeeText('chat di supporto');
});

test('the cai directory card shows the linked cai section data for a sezione customer', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => CustomerType::Sezione])->save();

    CaiSection::create([
        'codice_cai' => 'CAI-001',
        'name' => 'Sezione di Abbiategrasso',
        'region' => 'LOMBARDIA',
        'founded_year' => 1975,
        'user_id' => $customer->id,
    ]);

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSuccessful()
        ->assertSee('I miei dati CAI/RUNTS')
        ->assertSee('Sezione di Abbiategrasso')
        ->assertDontSee('Nessun dato CAI/RUNTS disponibile per la tua sezione');
});

test('the cai directory card never leaks another sezione\'s data', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => CustomerType::Sezione])->save();

    CaiSection::create([
        'codice_cai' => 'CAI-001',
        'name' => 'Sezione propria',
        'region' => 'LOMBARDIA',
        'user_id' => $customer->id,
    ]);
    CaiSection::create([
        'codice_cai' => 'CAI-002',
        'name' => 'Sezione altrui',
        'region' => 'LAZIO',
    ]);

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Sezione propria')
        ->assertDontSee('Sezione altrui');
});

test('the cai directory card shows the linked cai subsection data when no cai section is linked', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => CustomerType::Sezione])->save();

    $parentSection = CaiSection::create([
        'codice_cai' => 'CAI-001',
        'name' => 'Sezione madre',
        'region' => 'LOMBARDIA',
    ]);
    CaiSubsection::create([
        'cai_codice' => 'SUB-001',
        'cai_section_id' => $parentSection->codice_cai,
        'name' => 'Sottosezione propria',
        'email' => 'sub@example.com',
        'user_id' => $customer->id,
    ]);

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSuccessful()
        ->assertSee('I miei dati CAI/RUNTS')
        ->assertSee('Sottosezione propria')
        ->assertSee('Sezione madre')
        ->assertDontSee('Nessun dato CAI/RUNTS disponibile per la tua sezione');
});

test('the cai directory card shows an explicit empty state for a sezione customer without a linked cai section or subsection', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => CustomerType::Sezione])->save();

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertSuccessful()
        ->assertSee('I miei dati CAI/RUNTS')
        ->assertSee('Nessun dato CAI/RUNTS disponibile per la tua sezione');
});

test('the cai directory card is absent for non-sezione customers', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $groupLeader = withRole(User::factory()->create(), UserRole::Customer);
    $groupLeader->forceFill(['customer_type' => CustomerType::GruppoRegionale])->save();

    $generico = withRole(User::factory()->create(), UserRole::Customer);
    $generico->forceFill(['customer_type' => CustomerType::Generico])->save();

    foreach ([$groupLeader, $generico] as $customer) {
        $this->actingAs($customer)
            ->get(CustomerDashboard::getUrl())
            ->assertDontSee('I miei dati CAI/RUNTS');
    }
});

test('the regional group sections card is never shown on this page, not even for a gruppo regionale customer (it moved to its own navigation entry)', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $groupLeader = withRole(User::factory()->create(), UserRole::Customer);
    $groupLeader->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => Region::Lombardia])->save();

    $sezione = withRole(User::factory()->create(), UserRole::Customer);
    $sezione->forceFill(['customer_type' => CustomerType::Sezione, 'region' => Region::Lombardia])->save();

    foreach ([$groupLeader, $sezione] as $customer) {
        $this->actingAs($customer)
            ->get(CustomerDashboard::getUrl())
            ->assertDontSee('Sezioni del gruppo regionale');
    }
});

test('the navigation group is "GR" for a gruppo regionale customer and "Area cliente" for any other customer type', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $groupLeader = withRole(User::factory()->create(), UserRole::Customer);
    $groupLeader->forceFill(['customer_type' => CustomerType::GruppoRegionale])->save();
    $this->actingAs($groupLeader);
    expect(CustomerDashboard::getNavigationGroup())->toBe('GR');

    $sezione = withRole(User::factory()->create(), UserRole::Customer);
    $sezione->forceFill(['customer_type' => CustomerType::Sezione])->save();
    $this->actingAs($sezione);
    expect(CustomerDashboard::getNavigationGroup())->toBe('Area cliente');
});

test('the sync cai data action is visible only for a sezione customer with a linked cai section', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $withSection = withRole(User::factory()->create(), UserRole::Customer);
    $withSection->forceFill(['customer_type' => CustomerType::Sezione])->save();
    CaiSection::create(['codice_cai' => 'CAI-001', 'name' => 'Sezione propria', 'region' => 'LOMBARDIA', 'user_id' => $withSection->id]);

    $withoutSection = withRole(User::factory()->create(), UserRole::Customer);
    $withoutSection->forceFill(['customer_type' => CustomerType::Sezione])->save();

    $this->actingAs($withSection);
    Livewire::test(CustomerDashboard::class)->assertActionVisible('sync_cai_data');

    $this->actingAs($withoutSection);
    Livewire::test(CustomerDashboard::class)->assertActionHidden('sync_cai_data');
});

test('the sync cai data action live-scrapes only the current customer\'s own section from the CAI API', function (): void {
    $this->seed(RolePermissionSeeder::class);

    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216049', 'name' => 'Sezione di Como (aggiornata)', 'region' => 'lombardia'],
        ]),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216049/sub-sections-list*' => Http::response([]),
    ]);

    $customer = withRole(User::factory()->create(['email' => 'sezione@example.com']), UserRole::Customer);
    $customer->forceFill(['customer_type' => CustomerType::Sezione])->save();
    caiSection(['codice_cai' => '9216049', 'name' => 'Sezione di Como', 'user_id' => $customer->id]);

    $this->actingAs($customer);

    Livewire::test(CustomerDashboard::class)
        ->callAction('sync_cai_data')
        ->assertHasNoActionErrors()
        ->assertNotified();

    $section = CaiSection::query()->findOrFail('9216049');
    expect($section->name)->toBe('Sezione di Como (aggiornata)');
    expect($section->cai_last_synced_at)->not->toBeNull();
});

test('the sync runts data action is visible only for a sezione customer with a linked cai section', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $withSection = withRole(User::factory()->create(), UserRole::Customer);
    $withSection->forceFill(['customer_type' => CustomerType::Sezione])->save();
    CaiSection::create(['codice_cai' => 'CAI-001', 'name' => 'Sezione propria', 'region' => 'LOMBARDIA', 'user_id' => $withSection->id]);

    $withoutSection = withRole(User::factory()->create(), UserRole::Customer);
    $withoutSection->forceFill(['customer_type' => CustomerType::Sezione])->save();

    $this->actingAs($withSection);
    Livewire::test(CustomerDashboard::class)->assertActionVisible('sync_runts_data');

    $this->actingAs($withoutSection);
    Livewire::test(CustomerDashboard::class)->assertActionHidden('sync_runts_data');
});

test('the sync runts data action live-scrapes the current customer\'s section via the cai-runts-scraper service', function (): void {
    $this->seed(RolePermissionSeeder::class);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response([
            'found' => true,
            'entity' => [
                'id_runts' => '12345', 'codice_fiscale' => '01234567890',
                'denominazione' => 'Sezione di Como (RUNTS)', 'forma_giuridica' => null,
                'natura_giuridica' => null, 'sede_indirizzo' => null, 'sede_civico' => null,
                'sede_comune' => null, 'sede_provincia' => null, 'sede_regione' => null,
                'sede_cap' => null, 'data_iscrizione' => null, 'sezione_registro' => null,
                'settori_attivita' => null, 'rappresentante_legale' => null, 'sito_web' => null,
                'pec' => null, 'url_dettaglio' => null,
            ],
            'board_members' => [],
            'documents' => [],
        ]),
    ]);

    $customer = withRole(User::factory()->create(['email' => 'sezione@example.com']), UserRole::Customer);
    $customer->forceFill(['customer_type' => CustomerType::Sezione])->save();
    caiSection(['codice_cai' => '9216049', 'tax_code' => '01234567890', 'user_id' => $customer->id]);

    $this->actingAs($customer);

    Livewire::test(CustomerDashboard::class)
        ->callAction('sync_runts_data')
        ->assertHasNoActionErrors()
        ->assertNotified();

    $registration = CaiRuntsRegistration::query()->findOrFail('12345');
    expect($registration->name)->toBe('Sezione di Como (RUNTS)');
    expect($registration->runts_last_synced_at)->not->toBeNull();
});

test('the sync runts data action shows an informative notification when no RUNTS registration is found', function (): void {
    $this->seed(RolePermissionSeeder::class);

    Http::fake([
        'http://cai-runts-scraper:8000/scrape/runts-entity*' => Http::response(['found' => false]),
    ]);

    $customer = withRole(User::factory()->create(['email' => 'sezione@example.com']), UserRole::Customer);
    $customer->forceFill(['customer_type' => CustomerType::Sezione])->save();
    caiSection(['codice_cai' => '9216049', 'tax_code' => '01234567890', 'user_id' => $customer->id]);

    $this->actingAs($customer);

    Livewire::test(CustomerDashboard::class)
        ->callAction('sync_runts_data')
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect(CaiRuntsRegistration::query()->count())->toBe(0);
});
