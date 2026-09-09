<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiRuntsPresenceStatus;
use App\Domain\CaiDirectory\Models\CaiSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('cai_sections has runts_presence_status and runts_presence_checked_at columns, both nullable, mass-assignable and cast', function (): void {
    expect(Schema::hasColumn('cai_sections', 'runts_presence_status'))->toBeTrue();
    expect(Schema::hasColumn('cai_sections', 'runts_presence_checked_at'))->toBeTrue();
    expect(Schema::hasColumn('cai_sections', 'runts_registered'))->toBeFalse();

    $checkedAt = Carbon::parse('2026-09-09 10:00:00');

    $section = CaiSection::create([
        'codice_cai' => 'CAI-PRESENCE-1',
        'name' => 'Sezione di test',
        'region' => 'LOMBARDIA',
    ])->fresh();

    expect($section->runts_presence_status)->toBeNull();
    expect($section->runts_presence_checked_at)->toBeNull();

    $section->update(['runts_presence_status' => CaiRuntsPresenceStatus::Timeout, 'runts_presence_checked_at' => $checkedAt]);
    $section->refresh();

    expect($section->runts_presence_status)->toBe(CaiRuntsPresenceStatus::Timeout);
    expect($section->runts_presence_checked_at)->toBeInstanceOf(Carbon::class);
    expect($section->runts_presence_checked_at->equalTo($checkedAt))->toBeTrue();
});
