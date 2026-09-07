<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Actions\SyncCaiSectionAndSubsections;
use App\Domain\CaiDirectory\Import\CaiApiSectionNormalizer;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function fakeCaiSubsections(string $sectionCode, array $subsections): void
{
    Http::fake([
        "https://www.cai.it/wp-json/cai-section/v2/sections/{$sectionCode}/sub-sections-list*" => Http::response($subsections),
    ]);
}

test('run creates a new CaiSection with cai_last_synced_at set', function (): void {
    fakeCaiSubsections('9216049', []);

    $row = CaiApiSectionNormalizer::normalizeSection([
        'code' => '9216049',
        'name' => 'Sezione di Como',
        'region' => 'lombardia',
    ]);

    $results = app(SyncCaiSectionAndSubsections::class)->run($row);

    expect($results['cai_sections']->created)->toBe(1);
    expect($results['cai_sections']->updated)->toBe(0);

    $section = CaiSection::query()->findOrFail('9216049');
    expect($section->name)->toBe('Sezione di Como');
    expect($section->cai_last_synced_at)->not->toBeNull();
});

test('run updates an existing CaiSection when a field actually changed, and always bumps cai_last_synced_at', function (): void {
    fakeCaiSubsections('9216049', []);

    $existing = caiSection(['codice_cai' => '9216049', 'name' => 'Vecchio nome', 'cai_last_synced_at' => null]);

    $row = CaiApiSectionNormalizer::normalizeSection([
        'code' => '9216049',
        'name' => 'Sezione di Como',
        'region' => 'lombardia',
    ]);

    $results = app(SyncCaiSectionAndSubsections::class)->run($row);

    expect($results['cai_sections']->updated)->toBe(1);
    expect($results['cai_sections']->created)->toBe(0);

    $section = $existing->fresh();
    expect($section->name)->toBe('Sezione di Como');
    expect($section->cai_last_synced_at)->not->toBeNull();
});

test('run counts a section as skipped when nothing actually changed, but still bumps cai_last_synced_at', function (): void {
    fakeCaiSubsections('9216049', []);

    $existing = caiSection([
        'codice_cai' => '9216049',
        'name' => 'Sezione di Como',
        'region' => 'LOMBARDIA',
        'cai_last_synced_at' => null,
    ]);

    $row = CaiApiSectionNormalizer::normalizeSection([
        'code' => '9216049',
        'name' => 'Sezione di Como',
        'region' => 'lombardia',
    ]);

    $results = app(SyncCaiSectionAndSubsections::class)->run($row);

    expect($results['cai_sections']->skipped)->toBe(1);
    expect($results['cai_sections']->updated)->toBe(0);
    expect($existing->fresh()->cai_last_synced_at)->not->toBeNull();
});

test('run creates subsections fetched from the per-section API endpoint', function (): void {
    caiSection(['codice_cai' => '9216049', 'name' => 'Sezione di Como']);

    fakeCaiSubsections('9216049', [
        ['code' => 'SUB-1', 'name' => 'Sottosezione Erba'],
    ]);

    $row = CaiApiSectionNormalizer::normalizeSection([
        'code' => '9216049',
        'name' => 'Sezione di Como',
        'region' => 'lombardia',
    ]);

    $results = app(SyncCaiSectionAndSubsections::class)->run($row);

    expect($results['cai_subsections']->created)->toBe(1);

    $subsection = CaiSubsection::query()->findOrFail('SUB-1');
    expect($subsection->name)->toBe('Sottosezione Erba');
    expect($subsection->cai_section_id)->toBe('9216049');
    expect($subsection->cai_last_synced_at)->not->toBeNull();
});

test('run does not write anything in dry-run mode', function (): void {
    fakeCaiSubsections('9216049', [['code' => 'SUB-1', 'name' => 'Sottosezione Erba']]);

    $row = CaiApiSectionNormalizer::normalizeSection([
        'code' => '9216049',
        'name' => 'Sezione di Como',
        'region' => 'lombardia',
    ]);

    $results = app(SyncCaiSectionAndSubsections::class)->run($row, dryRun: true);

    expect($results['cai_sections']->created)->toBe(1);
    expect(CaiSection::query()->find('9216049'))->toBeNull();
    expect(CaiSubsection::query()->find('SUB-1'))->toBeNull();
});

test('run matches a section email to an existing user, case-insensitively', function (): void {
    fakeCaiSubsections('9216049', []);

    $user = User::factory()->create(['email' => 'como@cai.it']);

    $row = CaiApiSectionNormalizer::normalizeSection([
        'code' => '9216049',
        'name' => 'Sezione di Como',
        'email' => 'Como@Cai.It',
        'region' => 'lombardia',
    ]);

    app(SyncCaiSectionAndSubsections::class)->run($row);

    expect(CaiSection::query()->findOrFail('9216049')->user_id)->toBe($user->id);
});
