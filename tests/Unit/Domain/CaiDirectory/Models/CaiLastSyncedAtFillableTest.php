<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('cai_last_synced_at is mass-assignable and cast to a datetime on CaiSection', function (): void {
    $syncedAt = Carbon::parse('2026-09-07 08:30:00');

    $section = CaiSection::create([
        'codice_cai' => 'CAI-FILL-1',
        'name' => 'Sezione',
        'region' => 'LOMBARDIA',
        'cai_last_synced_at' => $syncedAt,
    ])->fresh();

    expect($section->cai_last_synced_at)->toBeInstanceOf(Carbon::class);
    expect($section->cai_last_synced_at->equalTo($syncedAt))->toBeTrue();
});

test('cai_last_synced_at is mass-assignable and cast to a datetime on CaiSubsection', function (): void {
    CaiSection::create(['codice_cai' => 'CAI-FILL-2', 'name' => 'Sezione', 'region' => 'LOMBARDIA']);
    $syncedAt = Carbon::parse('2026-09-07 08:30:00');

    $subsection = CaiSubsection::create([
        'cai_codice' => 'SUB-FILL-1',
        'cai_section_id' => 'CAI-FILL-2',
        'name' => 'Sottosezione',
        'cai_last_synced_at' => $syncedAt,
    ])->fresh();

    expect($subsection->cai_last_synced_at)->toBeInstanceOf(Carbon::class);
    expect($subsection->cai_last_synced_at->equalTo($syncedAt))->toBeTrue();
});
