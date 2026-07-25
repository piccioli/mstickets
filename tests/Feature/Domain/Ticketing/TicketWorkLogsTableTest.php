<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketWorkLog;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('ticket_work_logs table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('ticket_work_logs', [
        'id', 'work_date', 'user_id', 'ticket_id', 'minutes', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('the work_date/user/ticket triple is unique, minutes defaults to 0', function (): void {
    $ticket = Ticket::create(['title' => 'Ticket lavorato', 'status_changed_at' => now()]);
    $user = User::factory()->create();
    $workDate = now()->toDateString();

    $log = TicketWorkLog::create(['work_date' => $workDate, 'user_id' => $user->id, 'ticket_id' => $ticket->id])->fresh();

    expect($log->minutes)->toBe(0)
        ->and(fn () => TicketWorkLog::create([
            'work_date' => $workDate, 'user_id' => $user->id, 'ticket_id' => $ticket->id,
        ]))->toThrow(QueryException::class);
});
