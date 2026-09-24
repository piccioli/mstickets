<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiDocumentAnalysisStatus;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\CaiUnparsedFinancialStatements;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

function grantCaiUnparsedDocumentsPanelAccess(User $user): User
{
    Role::query()->firstOrCreate(['name' => UserRole::Developer->value, 'guard_name' => 'web']);
    $user->assignRole(UserRole::Developer->value);

    return $user->fresh();
}

test('a user without cai-directory.review-unparsed-documents is denied access to the page', function (): void {
    grantCaiUnparsedDocumentsPanelAccess(userWithPermissions());

    expect(CaiUnparsedFinancialStatements::canAccess())->toBeFalse();
});

test('a user with cai-directory.review-unparsed-documents can access the page', function (): void {
    $user = grantCaiUnparsedDocumentsPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryReviewUnparsedDocuments));

    $this->actingAs($user);

    expect(CaiUnparsedFinancialStatements::canAccess())->toBeTrue();
});

test('the table lists only bilancio_esercizio documents with no data extracted, showing section, year and a fixed RUNTS source', function (): void {
    $user = grantCaiUnparsedDocumentsPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryReviewUnparsedDocuments));

    $section = caiSection(['name' => 'Sezione di Prova']);
    $registration = caiRuntsRegistration(['cai_section_id' => $section->codice_cai]);

    $unparsed = caiDocument([
        'cai_runts_registration_id' => $registration->id_runts,
        'document_type' => 'bilancio_esercizio',
        'year' => 2024,
        'financial_analysis_status' => CaiDocumentAnalysisStatus::NoDataExtracted,
        'raw_text_excerpt' => 'STATO PATRIMONIALE (layout non riconosciuto)',
        'extracted_via_ocr' => false,
    ]);

    // Non deve comparire: stesso tipo di documento ma già interpretato con successo.
    caiDocument([
        'cai_runts_registration_id' => $registration->id_runts,
        'document_type' => 'bilancio_esercizio',
        'year' => 2023,
        'financial_analysis_status' => CaiDocumentAnalysisStatus::Extracted,
    ]);

    // Non deve comparire: non è un bilancio (anche se per assurdo avesse lo stesso stato).
    caiDocument([
        'cai_runts_registration_id' => $registration->id_runts,
        'document_type' => 'statuto',
        'year' => 2024,
        'financial_analysis_status' => CaiDocumentAnalysisStatus::NoDataExtracted,
    ]);

    // Non deve comparire: mai analizzato.
    caiDocument([
        'cai_runts_registration_id' => $registration->id_runts,
        'document_type' => 'bilancio_esercizio',
        'year' => 2022,
        'financial_analysis_status' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(CaiUnparsedFinancialStatements::class)
        ->assertCanSeeTableRecords([$unparsed])
        ->assertCountTableRecords(1)
        ->assertTableColumnStateSet('runtsRegistration.section.name', 'Sezione di Prova', record: $unparsed)
        ->assertTableColumnStateSet('year', 2024, record: $unparsed)
        ->assertTableColumnStateSet('source', 'RUNTS', record: $unparsed);
});

test('the download and view-raw-text actions are visible for an unparsed document', function (): void {
    $user = grantCaiUnparsedDocumentsPanelAccess(userWithPermissions(PermissionEnum::CaiDirectoryReviewUnparsedDocuments));

    $section = caiSection();
    $registration = caiRuntsRegistration(['cai_section_id' => $section->codice_cai]);
    $document = caiDocument([
        'cai_runts_registration_id' => $registration->id_runts,
        'document_type' => 'bilancio_esercizio',
        'financial_analysis_status' => CaiDocumentAnalysisStatus::NoDataExtracted,
    ]);

    $this->actingAs($user);

    Livewire::test(CaiUnparsedFinancialStatements::class)
        ->assertTableActionVisible('download', record: $document)
        ->assertTableActionVisible('view_raw_text', record: $document);
});
