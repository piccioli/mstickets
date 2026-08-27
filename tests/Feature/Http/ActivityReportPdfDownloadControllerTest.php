<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Models\ActivityReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Non interessa il contenuto reale del PDF qui, solo l'autorizzazione della
    // rotta: coda fake per non dipendere da Chromium (già coperto per davvero da
    // ActivityReportPdfTest), disco fake per poter scrivere un file fittizio.
    Queue::fake();
    Storage::fake('activity-report-pdfs');
});

function reportWithFakePdf(array $attributes): ActivityReport
{
    $report = ActivityReport::create(array_merge(['locale' => 'it'], $attributes));
    $path = "activity-reports/{$report->id}.pdf";
    Storage::disk('activity-report-pdfs')->put($path, '%PDF-1.4 fake content');
    $report->update(['pdf_path' => $path, 'pdf_generated_at' => now()]);

    return $report->fresh();
}

test('a user with activity-report.view.any can download any report', function (): void {
    $owner = User::factory()->create();
    $report = reportWithFakePdf([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
    ]);

    $staff = userWithPermissions(Permission::ActivityReportViewAny);

    $response = $this->actingAs($staff)->get(route('activity-reports.pdf-download', $report));

    $response->assertOk();
});

test('a user with only activity-report.view.own can download their own report', function (): void {
    $owner = userWithPermissions(Permission::ActivityReportViewOwn);
    $report = reportWithFakePdf([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
    ]);

    $response = $this->actingAs($owner)->get(route('activity-reports.pdf-download', $report));

    $response->assertOk();
});

test('a user with only activity-report.view.own is denied another owner\'s report, even by direct id access', function (): void {
    $owner = User::factory()->create();
    $report = reportWithFakePdf([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
    ]);

    $otherCustomer = userWithPermissions(Permission::ActivityReportViewOwn);

    $response = $this->actingAs($otherCustomer)->get(route('activity-reports.pdf-download', $report));

    $response->assertForbidden();
});

test('a member of the owner organization with activity-report.view.own can download the organization report', function (): void {
    $organization = Organization::create(['name' => 'CAI Sezione Test', 'locale' => 'it']);
    $member = userWithPermissions(Permission::ActivityReportViewOwn);
    $organization->users()->attach($member);

    $report = reportWithFakePdf([
        'owner_kind' => ActivityReportOwnerKind::Organization,
        'owner_organization_id' => $organization->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
    ]);

    $response = $this->actingAs($member)->get(route('activity-reports.pdf-download', $report));

    $response->assertOk();
});

test('a non-member of the owner organization with activity-report.view.own is denied', function (): void {
    $organization = Organization::create(['name' => 'CAI Sezione Test', 'locale' => 'it']);
    $outsider = userWithPermissions(Permission::ActivityReportViewOwn);

    $report = reportWithFakePdf([
        'owner_kind' => ActivityReportOwnerKind::Organization,
        'owner_organization_id' => $organization->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
    ]);

    $response = $this->actingAs($outsider)->get(route('activity-reports.pdf-download', $report));

    $response->assertForbidden();
});

test('a user without any activity-report permission is denied', function (): void {
    $owner = User::factory()->create();
    $report = reportWithFakePdf([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
    ]);

    $response = $this->actingAs(userWithPermissions())->get(route('activity-reports.pdf-download', $report));

    $response->assertForbidden();
});

test('a report whose pdf has not been generated yet returns a 404', function (): void {
    $owner = userWithPermissions(Permission::ActivityReportViewOwn);
    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Annual,
        'year' => 2026,
        'locale' => 'it',
    ]);

    $response = $this->actingAs($owner)->get(route('activity-reports.pdf-download', $report));

    $response->assertNotFound();
});
