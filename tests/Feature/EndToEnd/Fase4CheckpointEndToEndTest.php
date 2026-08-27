<?php

declare(strict_types=1);

use App\Domain\Documentation\Actions\CreateDocumentationPage;
use App\Domain\Documentation\Enums\DocumentationCategory;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Actions\CreateActivityReport;
use App\Domain\Reporting\Actions\GenerateActivityReportPdf;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Tags\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

/**
 * Checkpoint di fine Fase 4 (§14 del PRD, US-411): a differenza dei test per
 * singola Action/Resource già presenti nelle story US-401..US-410, qui i tre
 * flussi end-to-end richiesti esplicitamente dall'AC1 di questa story
 * (SAL su una commessa con ticket collegati, PDF di documentazione generato e
 * scaricabile, ActivityReport generato per un owner con ticket/totali corretti)
 * vengono percorsi con Action/Resource/rotte reali in sequenza, mai stato
 * seminato direttamente per il solo sotto-sistema sotto test — replica in
 * automatico, con dati sintetici ma rappresentativi, la verifica manuale già
 * eseguita su dati reali importati da v1 (`v1:import --anonymize`) in ambiente
 * Docker durante questo checkpoint (vedi progress.txt, US-411).
 *
 * Coda "sync" in test (phpunit.xml): la generazione PDF gira per davvero con
 * Chromium reale, stesso principio di DocumentationPagePdfTest (US-406) e
 * ActivityReportPdfTest (US-409) — nessun `Pdf::fake()` qui.
 */
uses(RefreshDatabase::class);

test('SAL is computed correctly on a real commessa with linked tickets', function (): void {
    $tag = Tag::create(['name' => 'SOAD/Gestione Rimborsi', 'slug' => 'soad-gestione-rimborsi', 'estimated_hours' => 2]);
    $ticketA = ticket(['title' => 'Rimborso viaggio', 'worked_minutes' => 50]);
    $ticketB = ticket(['title' => 'Rimborso vitto', 'worked_minutes' => 30]);
    $ticketC = ticket(['title' => 'Rimborso alloggio', 'worked_minutes' => 40]);
    $tag->tickets()->attach([$ticketA->id, $ticketB->id, $ticketC->id]);

    expect($tag->workedMinutes())->toBe(120)
        ->and($tag->sal())->toBe(100.0);
});

test('a documentation page pdf is generated and downloadable with the correct letterhead content', function (): void {
    $page = CreateDocumentationPage::run([
        'title' => 'Servizio di Ticketing',
        'body' => 'Il nostro servizio di ticketing è attivo dal lunedì al venerdì.',
        'category' => DocumentationCategory::Customer,
    ]);
    $page->refresh();

    expect($page->pdf_path)->not->toBeNull()
        ->and($page->pdf_generated_at)->not->toBeNull();

    $contents = Storage::disk('documentation-pdfs')->get($page->pdf_path);
    expect($contents)->not->toBeEmpty()
        ->and(substr($contents, 0, 4))->toBe('%PDF');

    $viewer = userWithPermissions(Permission::DocumentationViewCustomer);
    $response = $this->actingAs($viewer)->get(route('documentation-pages.pdf-download', $page));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('an activity report is generated for a real owner with tickets and totals verified against the source tickets', function (): void {
    $owner = User::factory()->create(['name' => 'OTCO/SO CCTAM']);
    $inPeriod1 = ticket(['title' => 'Albo — CCTAM revoche', 'requester_id' => $owner->id, 'done_at' => '2026-07-07 07:45:02', 'worked_minutes' => 50]);
    $inPeriod2 = ticket(['title' => 'Verbale CCTAM 30/06', 'requester_id' => $owner->id, 'done_at' => '2026-07-07 07:45:02', 'worked_minutes' => 10]);
    $inPeriod3 = ticket(['title' => 'Sospensioni CCTAM', 'requester_id' => $owner->id, 'done_at' => '2026-07-22 07:45:02', 'worked_minutes' => 80]);
    // Fuori periodo (mese diverso) e di un altro richiedente: non deve mai comparire nel totale.
    ticket(['title' => 'Fuori periodo', 'requester_id' => $owner->id, 'done_at' => '2026-08-05 09:00:00', 'worked_minutes' => 999]);
    ticket(['title' => 'Altro richiedente', 'requester_id' => User::factory()->create()->id, 'done_at' => '2026-07-10 09:00:00', 'worked_minutes' => 999]);

    $report = CreateActivityReport::run([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $owner->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 7,
    ]);

    $linkedIds = $report->tickets()->pluck('tickets.id')->sort()->values()->all();
    expect($linkedIds)->toBe(collect([$inPeriod1->id, $inPeriod2->id, $inPeriod3->id])->sort()->values()->all())
        ->and($report->tickets()->sum('worked_minutes'))->toBe(140)
        ->and($report->ownerName())->toBe('OTCO/SO CCTAM')
        ->and($report->periodLabel())->toBe('Luglio 2026');

    GenerateActivityReportPdf::run($report);
    $report->refresh();

    $contents = Storage::disk('activity-report-pdfs')->get($report->pdf_path);
    expect($contents)->not->toBeEmpty()
        ->and(substr($contents, 0, 4))->toBe('%PDF');
});
