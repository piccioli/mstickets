<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('cai_sections has a nullable cai_last_synced_at column', function (): void {
    expect(Schema::hasColumn('cai_sections', 'cai_last_synced_at'))->toBeTrue();

    $section = CaiSection::create([
        'codice_cai' => 'CAI-TEST-1',
        'name' => 'Sezione di test',
        'region' => 'LOMBARDIA',
    ]);

    expect($section->fresh()->cai_last_synced_at)->toBeNull();
});

test('cai_subsections has a nullable cai_last_synced_at column', function (): void {
    expect(Schema::hasColumn('cai_subsections', 'cai_last_synced_at'))->toBeTrue();

    CaiSection::create(['codice_cai' => 'CAI-TEST-2', 'name' => 'Sezione', 'region' => 'LOMBARDIA']);

    $subsection = CaiSubsection::create([
        'cai_codice' => 'SUB-TEST-1',
        'cai_section_id' => 'CAI-TEST-2',
        'name' => 'Sottosezione di test',
    ]);

    expect($subsection->fresh()->cai_last_synced_at)->toBeNull();
});
