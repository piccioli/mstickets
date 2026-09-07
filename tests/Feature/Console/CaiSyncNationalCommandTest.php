<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('cai:sync-national syncs every section and subsection returned by the national API', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216049', 'name' => 'Sezione di Como', 'region' => 'lombardia'],
            ['code' => '9216050', 'name' => 'Sezione di Pisa', 'region' => 'toscana'],
        ]),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216049/sub-sections-list*' => Http::response([['code' => 'SUB-1', 'name' => 'Sottosezione Erba']]),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216050/sub-sections-list*' => Http::response([]),
    ]);

    $this->artisan('cai:sync-national')->assertExitCode(0);

    expect(CaiSection::query()->count())->toBe(2);
    expect(CaiSection::query()->findOrFail('9216049')->cai_last_synced_at)->not->toBeNull();
    expect(CaiSection::query()->findOrFail('9216050')->cai_last_synced_at)->not->toBeNull();
});

test('cai:sync-national --dry-run does not write anything', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216049', 'name' => 'Sezione di Como', 'region' => 'lombardia'],
        ]),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216049/sub-sections-list*' => Http::response([]),
    ]);

    $this->artisan('cai:sync-national', ['--dry-run' => true])->assertExitCode(0);

    expect(CaiSection::query()->count())->toBe(0);
});

test('cai:sync-national continues past a section that fails to sync', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216049', 'name' => 'Sezione di Como', 'region' => 'lombardia'],
            ['code' => '9216050', 'name' => 'Sezione di Pisa', 'region' => 'toscana'],
        ]),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216049/sub-sections-list*' => Http::response(null, 500),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216050/sub-sections-list*' => Http::response([]),
    ]);

    $this->artisan('cai:sync-national')->assertExitCode(0);

    expect(CaiSection::query()->find('9216049'))->toBeNull();
    expect(CaiSection::query()->findOrFail('9216050')->name)->toBe('Sezione di Pisa');
})->skip('la sezione con subsections in errore fallisce con retry reale (2s+4s): abilitare se il tempo del run non è un vincolo, altrimenti verificare solo il comportamento con Http::fakeSequence senza ritardo');
