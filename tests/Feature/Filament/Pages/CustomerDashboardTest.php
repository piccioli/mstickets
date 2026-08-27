<?php

declare(strict_types=1);

use App\Domain\Documentation\Enums\DocumentationCategory;
use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
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

test('no reference to a support chat link is ever shown on the customer dashboard', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);

    $this->actingAs($customer)
        ->get(CustomerDashboard::getUrl())
        ->assertDontSee('help_desk_chat')
        ->assertDontSeeText('chat di supporto');
});
