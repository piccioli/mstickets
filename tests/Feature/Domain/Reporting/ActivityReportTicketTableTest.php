<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use App\Domain\Reporting\Models\ActivityReport;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('activity_report_ticket table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('activity_report_ticket', [
        'id', 'activity_report_id', 'ticket_id', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('links tickets to an activity report, cascading on delete', function (): void {
    $user = User::factory()->create();
    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $user->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 7,
        'locale' => 'it',
    ]);
    $ticket = Ticket::create(['title' => 'Ticket rendicontato', 'status_changed_at' => now()]);

    $report->tickets()->attach($ticket);

    expect($report->tickets()->pluck('tickets.id')->all())->toBe([$ticket->id]);

    $report->delete();

    expect(DB::table('activity_report_ticket')->count())->toBe(0);
});

test('unique on the activity_report/ticket pair', function (): void {
    $user = User::factory()->create();
    $report = ActivityReport::create([
        'owner_kind' => ActivityReportOwnerKind::User,
        'owner_user_id' => $user->id,
        'period_type' => ActivityReportPeriodType::Monthly,
        'year' => 2026,
        'month' => 7,
        'locale' => 'it',
    ]);
    $ticket = Ticket::create(['title' => 'Ticket rendicontato', 'status_changed_at' => now()]);

    DB::table('activity_report_ticket')->insert([
        'activity_report_id' => $report->id,
        'ticket_id' => $ticket->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('activity_report_ticket')->insert([
        'activity_report_id' => $report->id,
        'ticket_id' => $ticket->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});
