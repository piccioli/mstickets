<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\Region;
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

test('the CAI directory tab links to the official cai.it section page, built from codice_cai, regardless of the website field', function (): void {
    $user = grantCaiDirectoryPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    $section = caiSection(['codice_cai' => '9216006', 'name' => 'Sezione Senza Sito Proprio', 'website' => null]);

    $this->actingAs($user);

    Livewire::test(ViewCaiSection::class, ['record' => $section->getKey()])
        ->assertOk()
        ->assertSee('https://www.cai.it/sezioni-territoriali/sezioni-e-sottosezioni/sezione/?codice=9216006', false);
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

test('a customer can download a document belonging to their own cai section', function (): void {
    Storage::fake('cai-documents');

    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $section = caiSection(['user_id' => $customer->id]);
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

    $response = $this->actingAs($customer)->get(route('cai-documents.download', $document));

    $response->assertOk();
});

test('a customer cannot download a document belonging to another cai section', function (): void {
    Storage::fake('cai-documents');

    $customer = withRole(User::factory()->create(), UserRole::Customer);
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

    $response = $this->actingAs($customer)->get(route('cai-documents.download', $document));

    $response->assertForbidden();
});

test('a gruppo regionale customer can download a document belonging to a section in their own region', function (): void {
    Storage::fake('cai-documents');

    $sectionOwner = withRole(User::factory()->create(), UserRole::Customer);
    $sectionOwner->forceFill(['customer_type' => CustomerType::Sezione, 'region' => Region::Lombardia])->save();
    $section = caiSection(['user_id' => $sectionOwner->id]);
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

    $groupLeader = withRole(User::factory()->create(), UserRole::Customer);
    $groupLeader->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => Region::Lombardia])->save();

    $response = $this->actingAs($groupLeader)->get(route('cai-documents.download', $document));

    $response->assertOk();
});

test('a gruppo regionale customer cannot download a document belonging to a section in another region', function (): void {
    Storage::fake('cai-documents');

    $sectionOwner = withRole(User::factory()->create(), UserRole::Customer);
    $sectionOwner->forceFill(['customer_type' => CustomerType::Sezione, 'region' => Region::Lazio])->save();
    $section = caiSection(['user_id' => $sectionOwner->id]);
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

    $groupLeader = withRole(User::factory()->create(), UserRole::Customer);
    $groupLeader->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => Region::Lombardia])->save();

    $response = $this->actingAs($groupLeader)->get(route('cai-documents.download', $document));

    $response->assertForbidden();
});

test('office hours and notices are shown as formatted text, not raw HTML source', function (): void {
    $user = grantCaiDirectoryPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    $section = caiSection([
        'name' => 'Sezione Orari',
        'office_hours' => "\n                        Martedi dalle 18 alle 19&nbsp;<span style=\"background-color: rgb(254, 251, 243);\">(da ottobre a maggio, restanti mesi chiuso)</span><br>Venerdi dalle 21 alle 22:30\n                    ",
        'notices' => '<script>alert(1)</script><p>Sede chiusa per lavori.</p>',
    ]);

    $this->actingAs($user);

    $test = Livewire::test(ViewCaiSection::class, ['record' => $section->getKey()])
        ->assertOk()
        ->assertSee('Martedi dalle 18 alle 19', escape: false)
        ->assertSee('(da ottobre a maggio, restanti mesi chiuso)', escape: false)
        ->assertSee('Venerdi dalle 21 alle 22:30', escape: false)
        ->assertSee('Sede chiusa per lavori.', escape: false)
        ->assertDontSee('&lt;span', escape: false)
        ->assertDontSee('<script>', escape: false);

    expect($test->html())->not->toContain('background-color');
});

test('the differences tab shows the comparison between CaiSection and its RUNTS registration', function (): void {
    $user = grantCaiDirectoryPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    $section = caiSection(['name' => 'Sezione Confronto', 'pec' => 'sezione@pec.example.com']);
    CaiRuntsRegistration::create([
        'id_runts' => 'RUNTS-'.$section->codice_cai,
        'cai_section_id' => $section->codice_cai,
        'name' => 'Denominazione RUNTS diversa',
        'pec' => 'sezione@pec.example.com',
    ]);

    $this->actingAs($user);

    Livewire::test(ViewCaiSection::class, ['record' => $section->getKey()])
        ->assertOk()
        ->assertSee('Denominazione')
        ->assertSee('Sezione Confronto')
        ->assertSee('Denominazione RUNTS diversa')
        ->assertSee('Diverso')
        ->assertSee('Uguale');
});

test('the differences tab shows an explicit empty state when no RUNTS registration is linked', function (): void {
    $user = grantCaiDirectoryPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    $section = caiSection(['name' => 'Sezione Senza RUNTS']);

    $this->actingAs($user);

    Livewire::test(ViewCaiSection::class, ['record' => $section->getKey()])
        ->assertOk()
        ->assertSee('Nessuna registrazione RUNTS collegata: nessun confronto possibile');
});

test('the financial statements tab lists years from most recent to oldest', function (): void {
    $user = grantCaiDirectoryPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    $section = caiSection(['name' => 'Sezione Bilanci Ordinati']);
    $registration = CaiRuntsRegistration::create([
        'id_runts' => 'RUNTS-'.$section->codice_cai,
        'cai_section_id' => $section->codice_cai,
        'name' => 'Ente Bilanci',
    ]);
    foreach ([2022, 2024, 2023] as $year) {
        CaiFinancialStatement::create([
            'cai_runts_registration_id' => $registration->id_runts,
            'year' => $year,
        ]);
    }

    $this->actingAs($user);

    $html = Livewire::test(ViewCaiSection::class, ['record' => $section->getKey()])->assertOk()->html();

    expect(strpos($html, '2024'))->toBeLessThan(strpos($html, '2023'))
        ->and(strpos($html, '2023'))->toBeLessThan(strpos($html, '2022'));
});

test('the documents tab lists attachments from most recent to oldest year', function (): void {
    Storage::fake('cai-documents');

    $user = grantCaiDirectoryPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    $section = caiSection(['name' => 'Sezione Allegati Ordinati']);
    $registration = CaiRuntsRegistration::create([
        'id_runts' => 'RUNTS-'.$section->codice_cai,
        'cai_section_id' => $section->codice_cai,
        'name' => 'Ente Allegati',
    ]);
    foreach ([2022, 2024, 2023] as $year) {
        Storage::disk('cai-documents')->put("bilanci/{$year}.pdf", '%PDF-1.4 fake content');
        CaiDocument::create([
            'cai_runts_registration_id' => $registration->id_runts,
            'document_type' => 'bilancio',
            'year' => $year,
            'title' => "Bilancio {$year}",
            'file_path' => "bilanci/{$year}.pdf",
            'file_name' => "{$year}.pdf",
            'mime_type' => 'application/pdf',
        ]);
    }

    $this->actingAs($user);

    $html = Livewire::test(ViewCaiSection::class, ['record' => $section->getKey()])->assertOk()->html();

    expect(strpos($html, 'Bilancio 2024'))->toBeLessThan(strpos($html, 'Bilancio 2023'))
        ->and(strpos($html, 'Bilancio 2023'))->toBeLessThan(strpos($html, 'Bilancio 2022'));
});
