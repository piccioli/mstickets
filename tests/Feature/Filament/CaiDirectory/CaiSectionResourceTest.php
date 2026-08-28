<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\CaiSections\CaiSectionResource;
use App\Filament\Resources\CaiSections\Pages\ListCaiSections;
use App\Filament\Resources\CaiSections\Pages\ViewCaiSection;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

/**
 * Assegna un ruolo applicativo "vuoto" solo per superare il gate d'accesso al pannello
 * (§9.1, US-020): isola i test sui soli permessi diretti concessi da userWithPermissions(),
 * stessa convenzione già in uso in RoleAndPermissionManagementTest.php/EmailMessageResourceTest.php.
 */
function grantCaiDirectoryPanelAccess(User $user): User
{
    Role::query()->firstOrCreate(['name' => UserRole::Developer->value, 'guard_name' => 'web']);
    $user->assignRole(UserRole::Developer->value);

    return $user->fresh();
}

/**
 * @param  array<string, mixed>  $attributes
 */
function caiSection(array $attributes = []): CaiSection
{
    static $sequence = 0;
    $sequence++;

    return CaiSection::create(array_merge([
        'codice_cai' => 'CAI-'.$sequence,
        'name' => 'Sezione CAI '.$sequence,
        'region' => 'LOMBARDIA',
    ], $attributes))->fresh();
}

test('a user without cai-directory.view is denied access to the list and detail pages', function (): void {
    $section = caiSection();
    $user = grantCaiDirectoryPanelAccess(userWithPermissions());

    expect(CaiSectionResource::canViewAny())->toBeFalse();

    $this->actingAs($user);

    $this->get(CaiSectionResource::getUrl('index'))->assertForbidden();
    $this->get(CaiSectionResource::getUrl('view', ['record' => $section]))->assertForbidden();
});

test('a user with cai-directory.view can access the list page and sees the expected columns', function (): void {
    $user = grantCaiDirectoryPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    $linkedUser = User::factory()->create(['name' => 'Mario Rossi']);
    $section = caiSection(['name' => 'Sezione di Abbiategrasso', 'region' => 'LOMBARDIA', 'user_id' => $linkedUser->id]);
    CaiRuntsRegistration::create([
        'id_runts' => 'RUNTS-'.$section->codice_cai,
        'cai_section_id' => $section->codice_cai,
        'municipality' => 'Abbiategrasso',
    ]);

    $this->actingAs($user);

    expect(CaiSectionResource::canViewAny())->toBeTrue();

    Livewire::test(ListCaiSections::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$section->fresh()])
        ->assertSee('Sezione di Abbiategrasso')
        ->assertSee('Abbiategrasso')
        ->assertSee('LOMBARDIA')
        ->assertSee('Mario Rossi');
});

test('the resource has no create, edit or delete function', function (): void {
    $section = caiSection();
    $user = grantCaiDirectoryPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));

    expect(CaiSectionResource::canCreate())->toBeFalse()
        ->and(CaiSectionResource::canEdit($section))->toBeFalse()
        ->and(CaiSectionResource::canDelete($section))->toBeFalse()
        ->and(CaiSectionResource::canDeleteAny())->toBeFalse()
        ->and(Route::has('filament.admin.resources.cai-sections.create'))->toBeFalse()
        ->and(Route::has('filament.admin.resources.cai-sections.edit'))->toBeFalse();

    $this->actingAs($user);

    Livewire::test(ListCaiSections::class)->assertOk();
});

test('the table is filterable by region', function (): void {
    $user = grantCaiDirectoryPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    $lombardia = caiSection(['region' => 'LOMBARDIA']);
    $piemonte = caiSection(['region' => 'PIEMONTE']);

    $this->actingAs($user);

    Livewire::test(ListCaiSections::class)
        ->filterTable('region', 'LOMBARDIA')
        ->assertCanSeeTableRecords([$lombardia])
        ->assertCanNotSeeTableRecords([$piemonte]);
});

test('the table is filterable by presence of a linked user', function (): void {
    $user = grantCaiDirectoryPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    $linkedUser = User::factory()->create();
    $withUser = caiSection(['user_id' => $linkedUser->id]);
    $withoutUser = caiSection();

    $this->actingAs($user);

    Livewire::test(ListCaiSections::class)
        ->filterTable('user_id', true)
        ->assertCanSeeTableRecords([$withUser])
        ->assertCanNotSeeTableRecords([$withoutUser]);

    Livewire::test(ListCaiSections::class)
        ->filterTable('user_id', false)
        ->assertCanSeeTableRecords([$withoutUser])
        ->assertCanNotSeeTableRecords([$withUser]);
});

test('viewing a section with runts data, statements and attachments shows the expected data', function (): void {
    Storage::fake('cai-documents');

    $user = grantCaiDirectoryPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    $section = caiSection([
        'name' => 'Sezione Completa',
        'email' => 'sezione@example.com',
        'founded_year' => 1950,
        'members_count' => 120,
    ]);
    $subsection = CaiSubsection::create([
        'cai_codice' => 'SUB-'.$section->codice_cai,
        'cai_section_id' => $section->codice_cai,
        'name' => 'Sottosezione Completa',
    ]);
    $registration = CaiRuntsRegistration::create([
        'id_runts' => 'RUNTS-'.$section->codice_cai,
        'cai_section_id' => $section->codice_cai,
        'name' => 'Ente Completo APS',
        'legal_nature' => 'Associazione',
        'legal_representative' => 'Luigi Bianchi',
        'pec' => 'ente@pec.example.com',
        'official_page_url' => 'https://runts.lavoro.gov.it/ente/123',
        'registration_date' => '2020-05-10',
    ]);
    CaiFinancialStatement::create([
        'cai_runts_registration_id' => $registration->id_runts,
        'year' => 2024,
        'total_revenues' => 15000.50,
        'total_expenses' => 12000.25,
        'net_result' => 3000.25,
    ]);
    Storage::disk('cai-documents')->put('bilanci/2024.pdf', '%PDF-1.4 fake content');
    CaiDocument::create([
        'cai_runts_registration_id' => $registration->id_runts,
        'document_type' => 'bilancio',
        'year' => 2024,
        'title' => 'Bilancio 2024',
        'file_path' => 'bilanci/2024.pdf',
        'file_name' => '2024.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1024,
    ]);

    $this->actingAs($user);

    Livewire::test(ViewCaiSection::class, ['record' => $section->getKey()])
        ->assertOk()
        ->assertSee('Sezione Completa')
        ->assertSee('sezione@example.com')
        ->assertSee('Ente Completo APS')
        ->assertSee('Associazione')
        ->assertSee('Luigi Bianchi')
        ->assertSee('Bilancio 2024')
        ->assertSee('Sottosezione Completa');
});

test('viewing a section without runts data, statements or attachments does not crash and shows empty states', function (): void {
    $user = grantCaiDirectoryPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    $section = caiSection(['name' => 'Sezione Minima']);

    $this->actingAs($user);

    Livewire::test(ViewCaiSection::class, ['record' => $section->getKey()])
        ->assertOk()
        ->assertSee('Sezione Minima')
        ->assertSee('Nessuna registrazione RUNTS collegata')
        ->assertSee('Nessun bilancio disponibile')
        ->assertSee('Nessun allegato disponibile')
        ->assertSee('Nessuna sottosezione collegata');
});

test('an authorized user can download a cai document', function (): void {
    Storage::fake('cai-documents');

    $user = userWithPermissions(PermissionEnum::CaiDirectoryView);
    $section = caiSection();
    $registration = CaiRuntsRegistration::create([
        'id_runts' => 'RUNTS-'.$section->codice_cai,
        'cai_section_id' => $section->codice_cai,
    ]);
    Storage::disk('cai-documents')->put('bilanci/2024.pdf', '%PDF-1.4 fake content');
    $document = CaiDocument::create([
        'cai_runts_registration_id' => $registration->id_runts,
        'document_type' => 'bilancio',
        'year' => 2024,
        'title' => 'Bilancio 2024',
        'file_path' => 'bilanci/2024.pdf',
        'file_name' => '2024.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1024,
    ]);

    $response = $this->actingAs($user)->get(route('cai-documents.download', $document));

    $response->assertOk();
});

test('a user without cai-directory.view is denied downloading a cai document', function (): void {
    Storage::fake('cai-documents');

    $user = userWithPermissions();
    $section = caiSection();
    $registration = CaiRuntsRegistration::create([
        'id_runts' => 'RUNTS-'.$section->codice_cai,
        'cai_section_id' => $section->codice_cai,
    ]);
    Storage::disk('cai-documents')->put('bilanci/2024.pdf', '%PDF-1.4 fake content');
    $document = CaiDocument::create([
        'cai_runts_registration_id' => $registration->id_runts,
        'document_type' => 'bilancio',
        'year' => 2024,
        'title' => 'Bilancio 2024',
        'file_path' => 'bilanci/2024.pdf',
        'file_name' => '2024.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1024,
    ]);

    $response = $this->actingAs($user)->get(route('cai-documents.download', $document));

    $response->assertForbidden();
});
