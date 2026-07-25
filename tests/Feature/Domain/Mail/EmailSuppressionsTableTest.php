<?php

declare(strict_types=1);

use App\Domain\Mail\Enums\SuppressionReason;
use App\Domain\Mail\Models\EmailSuppression;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('email_suppressions table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('email_suppressions', [
        'id', 'email', 'reason', 'bounce_count', 'notes', 'expires_at', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('reason is cast to its backed enum, bounce_count defaults to 0', function (): void {
    $suppression = EmailSuppression::create([
        'email' => 'destinatario@example.com',
        'reason' => SuppressionReason::HardBounce,
    ]);

    $fresh = $suppression->fresh();

    expect($fresh->reason)->toBe(SuppressionReason::HardBounce)
        ->and($fresh->bounce_count)->toBe(0);
});

test('email is unique', function (): void {
    EmailSuppression::create(['email' => 'destinatario@example.com', 'reason' => SuppressionReason::Manual]);

    expect(fn () => EmailSuppression::create([
        'email' => 'destinatario@example.com', 'reason' => SuppressionReason::Complaint,
    ]))->toThrow(QueryException::class);
});
