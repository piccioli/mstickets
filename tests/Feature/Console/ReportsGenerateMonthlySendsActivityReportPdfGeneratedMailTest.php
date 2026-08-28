<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Mailables\ActivityReportPdfGeneratedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * US-615 (E10): a differenza di ReportsGenerateMonthlyCommandTest.php (che fa
 * `Queue::fake()` per verificare solo il dispatch del job), qui il job gira
 * per davvero (QUEUE_CONNECTION=sync in phpunit.xml) per verificare l'intera
 * catena reale comando -> job -> GenerateActivityReportPdf -> evento di
 * dominio -> listener -> Mailable accodato.
 */
test('reports:generate-monthly ends up queuing the E10 mail for the report owner', function (): void {
    Storage::fake('activity-report-pdfs');
    Mail::fake();
    $this->travelTo('2026-03-01 12:00:00');

    $customer = grantTicketPanelRole(User::factory()->create(), UserRole::Customer);
    ticket(['requester_id' => $customer->id, 'done_at' => '2026-02-15 10:00:00']);

    $this->artisan('reports:generate-monthly')->assertSuccessful();

    Mail::assertQueued(ActivityReportPdfGeneratedMail::class);
});
