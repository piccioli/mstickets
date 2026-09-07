<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Support\CaiApiClient;
use Illuminate\Support\Facades\Http;

test('fetchNationalSections returns the decoded JSON array from the sections-list-simple endpoint', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response([
            ['code' => '9216049', 'name' => 'Sezione di Como'],
            ['code' => '9216050', 'name' => 'Sezione di Pisa'],
        ]),
    ]);

    $sections = app(CaiApiClient::class)->fetchNationalSections();

    expect($sections)->toHaveCount(2);
    expect($sections[0]['code'])->toBe('9216049');
});

test('fetchSubsections returns the decoded JSON array from the per-section endpoint', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections/9216049/sub-sections-list*' => Http::response([
            ['code' => 'SUB-1', 'name' => 'Sottosezione Erba'],
        ]),
    ]);

    $subsections = app(CaiApiClient::class)->fetchSubsections('9216049');

    expect($subsections)->toHaveCount(1);
    expect($subsections[0]['code'])->toBe('SUB-1');
});

test('fetchNationalSections retries up to 3 times on connection failure then throws', function (): void {
    Http::fake([
        'https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*' => Http::response(null, 503),
    ]);

    expect(fn () => app(CaiApiClient::class)->fetchNationalSections())->toThrow(RuntimeException::class);

    Http::assertSentCount(3);
});

test('fetchNationalSections succeeds if a later attempt recovers', function (): void {
    Http::fakeSequence('https://www.cai.it/wp-json/cai-section/v2/sections-list-simple*')
        ->push(null, 503)
        ->push([['code' => '9216049', 'name' => 'Sezione di Como']], 200);

    $sections = app(CaiApiClient::class)->fetchNationalSections();

    expect($sections)->toHaveCount(1);
});
