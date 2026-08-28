<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiBoardMember;
use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiFinancialStatement;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('cai_sections table has the columns required by US-801', function (): void {
    expect(Schema::hasColumns('cai_sections', [
        'codice_cai', 'name', 'tax_code', 'vat_number', 'email', 'pec', 'phone_office', 'phone', 'fax',
        'address', 'postal_address', 'website', 'office_hours', 'notices', 'founded_year',
        'members_count', 'latitude', 'longitude', 'region', 'user_id', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('cai_subsections table has the columns required by US-801', function (): void {
    expect(Schema::hasColumns('cai_subsections', [
        'cai_codice', 'cai_section_id', 'name', 'email', 'phone_office', 'phone', 'address', 'website',
        'office_hours', 'notices', 'founded_year', 'members_count', 'latitude', 'longitude', 'user_id',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('cai_runts_registrations table has the columns required by US-801', function (): void {
    expect(Schema::hasColumns('cai_runts_registrations', [
        'id_runts', 'cai_section_id', 'tax_code', 'name', 'legal_form', 'legal_nature', 'address',
        'street_number', 'municipality', 'province', 'region', 'postal_code', 'latitude', 'longitude',
        'registration_date', 'register_section', 'activity_sectors', 'legal_representative', 'website',
        'pec', 'official_page_url', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('cai_financial_statements table has the columns required by US-801', function (): void {
    expect(Schema::hasColumns('cai_financial_statements', [
        'id', 'cai_runts_registration_id', 'year', 'general_interest_expenses',
        'other_activities_expenses', 'fundraising_expenses', 'financial_expenses', 'overhead_expenses',
        'total_expenses', 'general_interest_revenues', 'other_activities_revenues',
        'fundraising_revenues', 'financial_revenues', 'overhead_revenues', 'total_revenues',
        'pre_tax_result', 'taxes', 'net_result', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('cai_board_members table has the columns required by US-801', function (): void {
    expect(Schema::hasColumns('cai_board_members', [
        'id', 'cai_runts_registration_id', 'role', 'full_name', 'tax_code', 'valid_from', 'valid_to',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('cai_documents table has the columns required by US-801', function (): void {
    expect(Schema::hasColumns('cai_documents', [
        'id', 'cai_runts_registration_id', 'document_type', 'year', 'title', 'file_path', 'file_name',
        'mime_type', 'size', 'hash', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('cai_sections uses codice_cai as a natural, non-incrementing primary key', function (): void {
    $section = CaiSection::create([
        'codice_cai' => '9216049',
        'name' => 'Sez. Abbiategrasso',
        'region' => 'LOMBARDIA',
    ]);

    expect($section->getKey())->toBe('9216049')
        ->and($section->getIncrementing())->toBeFalse();
});

test('a section has many subsections and belongs to a user', function (): void {
    $user = User::factory()->create();

    $section = CaiSection::create([
        'codice_cai' => '9216049',
        'name' => 'Sez. Abbiategrasso',
        'region' => 'LOMBARDIA',
        'user_id' => $user->id,
    ]);

    $subsection = CaiSubsection::create([
        'cai_codice' => 'SUB-001',
        'cai_section_id' => $section->codice_cai,
        'name' => 'Sottosezione test',
    ]);

    expect($section->subsections)->toHaveCount(1)
        ->and($section->subsections->first()->is($subsection))->toBeTrue()
        ->and($section->user->is($user))->toBeTrue()
        ->and($subsection->section->is($section))->toBeTrue();
});

test('deleting the linked user leaves the section user_id null', function (): void {
    $user = User::factory()->create();

    $section = CaiSection::create([
        'codice_cai' => '9216049',
        'name' => 'Sez. Abbiategrasso',
        'region' => 'LOMBARDIA',
        'user_id' => $user->id,
    ]);

    $user->forceDelete();

    expect($section->fresh()->user_id)->toBeNull();
});

test('a runts registration belongs to a section and has many statements, board members and documents', function (): void {
    $section = CaiSection::create([
        'codice_cai' => '9216049',
        'name' => 'Sez. Abbiategrasso',
        'region' => 'LOMBARDIA',
    ]);

    $registration = CaiRuntsRegistration::create([
        'id_runts' => '166339',
        'cai_section_id' => $section->codice_cai,
        'name' => 'Sez. Abbiategrasso',
    ]);

    $statement = CaiFinancialStatement::create([
        'cai_runts_registration_id' => $registration->id_runts,
        'year' => 2025,
        'net_result' => 1234.56,
    ]);

    $boardMember = CaiBoardMember::create([
        'cai_runts_registration_id' => $registration->id_runts,
        'role' => 'Presidente',
        'full_name' => 'Mario Rossi',
    ]);

    $document = CaiDocument::create([
        'cai_runts_registration_id' => $registration->id_runts,
        'document_type' => 'bilancio',
        'file_path' => 'cai-documents/166339/bilancio-2025.pdf',
    ]);

    expect($registration->section->is($section))->toBeTrue()
        ->and($registration->financialStatements->first()->is($statement))->toBeTrue()
        ->and($registration->boardMembers->first()->is($boardMember))->toBeTrue()
        ->and($registration->documents->first()->is($document))->toBeTrue()
        ->and($statement->runtsRegistration->is($registration))->toBeTrue()
        ->and($boardMember->runtsRegistration->is($registration))->toBeTrue()
        ->and($document->runtsRegistration->is($registration))->toBeTrue();
});
