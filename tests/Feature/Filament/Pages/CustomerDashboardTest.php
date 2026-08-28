<?php

declare(strict_types=1);

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

test('the regional group sections card lists only sections in the same region, with their open ticket count', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $groupLeader = withRole(User::factory()->create(), UserRole::Customer);
    $groupLeader->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => Region::Lombardia])->save();

    $sameRegionSection = withRole(User::factory()->create(['name' => 'Sezione di Milano']), UserRole::Customer);
    $sameRegionSection->forceFill(['customer_type' => CustomerType::Sezione, 'region' => Region::Lombardia])->save();
    ticket(['requester_id' => $sameRegionSection->id, 'status' => TicketStatus::Todo]);
    ticket(['requester_id' => $sameRegionSection->id, 'status' => TicketStatus::Done]);

    $otherRegionSection = withRole(User::factory()->create(['name' => 'Sezione di Roma']), UserRole::Customer);
    $otherRegionSection->forceFill(['customer_type' => CustomerType::Sezione, 'region' => Region::Lazio])->save();

    $this->actingAs($groupLeader);

    $sections = Livewire::test(CustomerDashboard::class)->instance()->regionalGroupSections();

    expect($sections->pluck('id'))->toContain($sameRegionSection->id)
        ->not->toContain($otherRegionSection->id);

    $this->get(CustomerDashboard::getUrl())
        ->assertSee('Sezioni del gruppo regionale')
        ->assertSee('Sezione di Milano')
        ->assertSee('1 ticket aperti')
        ->assertDontSee('Sezione di Roma');
});

test('the regional group sections card shows an explicit empty state when the region has no sections yet', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $groupLeader = withRole(User::factory()->create(), UserRole::Customer);
    $groupLeader->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => Region::Molise])->save();

    $this->actingAs($groupLeader)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Sezioni del gruppo regionale')
        ->assertSee('Nessuna sezione classificata in questa regione');
});

test('the regional group sections card shows an explicit empty state when the group has no region', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $groupLeader = withRole(User::factory()->create(), UserRole::Customer);
    $groupLeader->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => null])->save();

    $this->actingAs($groupLeader)
        ->get(CustomerDashboard::getUrl())
        ->assertSee('Sezioni del gruppo regionale')
        ->assertSee('Nessuna sezione classificata in questa regione');
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

test('the regional group sections card is absent for sezione, organo tecnico/struttura operativa, and generico customers', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $sezione = withRole(User::factory()->create(), UserRole::Customer);
    $sezione->forceFill(['customer_type' => CustomerType::Sezione, 'region' => Region::Lombardia])->save();

    $otcoSo = withRole(User::factory()->create(), UserRole::Customer);
    $otcoSo->forceFill(['customer_type' => CustomerType::OrganoTecnicoStrutturaOperativa, 'region' => null])->save();

    $generico = withRole(User::factory()->create(), UserRole::Customer);
    $generico->forceFill(['customer_type' => CustomerType::Generico, 'region' => null])->save();

    foreach ([$sezione, $otcoSo, $generico] as $customer) {
        $this->actingAs($customer)
            ->get(CustomerDashboard::getUrl())
            ->assertDontSee('Sezioni del gruppo regionale');
    }
});
