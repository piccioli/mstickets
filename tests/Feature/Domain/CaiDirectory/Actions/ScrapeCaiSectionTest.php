<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Actions\ScrapeCaiSection;
use App\Domain\CaiDirectory\Models\CaiSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('run fetches the national list, isolates the requested section and syncs it', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216049', 'name' => 'Sezione di Como', 'region' => 'lombardia'],
            ['code' => '9216050', 'name' => 'Sezione di Pisa', 'region' => 'toscana'],
        ]),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216049/sub-sections-list*' => Http::response([]),
    ]);

    $results = app(ScrapeCaiSection::class)->run('9216049');

    expect($results['cai_sections']->created)->toBe(1);

    $section = CaiSection::query()->findOrFail('9216049');
    expect($section->name)->toBe('Sezione di Como');
    expect($section->region)->toBe('LOMBARDIA');

    expect(CaiSection::query()->find('9216050'))->toBeNull();
});

test('run only fetches subsections for the requested section, never for other sections in the national list', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216049', 'name' => 'Sezione di Como', 'region' => 'lombardia'],
            ['code' => '9216050', 'name' => 'Sezione di Pisa', 'region' => 'toscana'],
        ]),
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216049/sub-sections-list*' => Http::response([]),
    ]);

    app(ScrapeCaiSection::class)->run('9216049');

    Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/sections/9216050/'));
});

test('run throws when the requested section code is not present in the national list', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216050', 'name' => 'Sezione di Pisa', 'region' => 'toscana'],
        ]),
    ]);

    expect(fn () => app(ScrapeCaiSection::class)->run('9216049'))->toThrow(RuntimeException::class);
});
