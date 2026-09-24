<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiRuntsRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('cai_runts_registrations has a nullable runts_last_synced_at column, mass-assignable and cast to datetime', function (): void {
    expect(Schema::hasColumn('cai_runts_registrations', 'runts_last_synced_at'))->toBeTrue();

    $syncedAt = Carbon::parse('2026-09-08 09:00:00');

    $registration = CaiRuntsRegistration::create([
        'id_runts' => 'RUNTS-TEST-1',
        'runts_last_synced_at' => $syncedAt,
    ])->fresh();

    expect($registration->runts_last_synced_at)->toBeInstanceOf(Carbon::class);
    expect($registration->runts_last_synced_at->equalTo($syncedAt))->toBeTrue();
});
