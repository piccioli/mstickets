<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiDocument;
use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('cai-documents');
    Storage::disk('cai-documents')->put('bilanci/2025.pdf', '%PDF-1.4 fake content');
});

function regionalGroupCustomer(Region $region): User
{
    $user = withRole(User::factory()->create(), UserRole::Customer);
    $user->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => $region])->save();

    return $user;
}

function sectionStatementPdf(CaiSection $section, bool $viaRunts = false): CaiDocument
{
    $registration = $viaRunts
        ? CaiRuntsRegistration::create(['id_runts' => 'RUNTS-'.$section->codice_cai, 'cai_section_id' => $section->codice_cai])
        : null;

    return caiDocument([
        'cai_runts_registration_id' => $registration?->id_runts,
        'cai_section_id' => $registration === null ? $section->codice_cai : null,
        'year' => 2025,
        'title' => 'Rendiconto gestionale',
        'file_path' => 'bilanci/2025.pdf',
        'file_name' => '2025.pdf',
    ]);
}

test('a regional group downloads the statement of a section of its group with no linked user', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $section = caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);

    $this->actingAs(regionalGroupCustomer(Region::Toscana))
        ->get(route('cai-documents.download', sectionStatementPdf($section)))
        ->assertOk();
});

test('a regional group downloads a statement reached through the runts registration of its section', function (): void {
    $section = caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);

    $this->actingAs(regionalGroupCustomer(Region::Toscana))
        ->get(route('cai-documents.download', sectionStatementPdf($section, viaRunts: true)))
        ->assertOk();
});

test('a regional group never downloads the statement of a section of another group', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $other = caiSection(['codice_cai' => '9213001', 'region' => 'LIGURIA']);
    $otherViaRunts = caiSection(['codice_cai' => '9213002', 'region' => 'LIGURIA']);
    $group = regionalGroupCustomer(Region::Toscana);

    $this->actingAs($group)
        ->get(route('cai-documents.download', sectionStatementPdf($other)))
        ->assertForbidden();

    $this->actingAs($group)
        ->get(route('cai-documents.download', sectionStatementPdf($otherViaRunts, viaRunts: true)))
        ->assertForbidden();
});

test('a section with the prefix of another group is not downloadable even if its region matches', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    $outside = caiSection(['codice_cai' => '9213001', 'region' => 'TOSCANA']);

    $this->actingAs(regionalGroupCustomer(Region::Toscana))
        ->get(route('cai-documents.download', sectionStatementPdf($outside)))
        ->assertForbidden();
});

test('sappada is downloadable by the veneto group and not by the friuli venezia giulia group', function (): void {
    caiSection(['codice_cai' => '9220001', 'region' => 'VENETO']);
    caiSection(['codice_cai' => '9220002', 'region' => 'VENETO']);
    caiSection(['codice_cai' => '9221001', 'region' => 'FRIULI-VENEZIA GIULIA']);
    caiSection(['codice_cai' => '9221002', 'region' => 'FRIULI-VENEZIA GIULIA']);
    $sappada = caiSection(['codice_cai' => '9220099', 'name' => 'SEZ. SAPPADA', 'region' => 'FRIULI-VENEZIA GIULIA']);
    $document = sectionStatementPdf($sappada);

    $this->actingAs(regionalGroupCustomer(Region::Veneto))
        ->get(route('cai-documents.download', $document))
        ->assertOk();

    $this->actingAs(regionalGroupCustomer(Region::FriuliVeneziaGiulia))
        ->get(route('cai-documents.download', $document))
        ->assertForbidden();
});

test('a section customer is not granted access to other sections by the group prefix rule', function (): void {
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $section = caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA']);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => CustomerType::Sezione, 'region' => Region::Toscana])->save();

    $this->actingAs($customer)
        ->get(route('cai-documents.download', sectionStatementPdf($section)))
        ->assertForbidden();
});
