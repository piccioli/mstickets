<?php

declare(strict_types=1);

use App\Domain\Documentation\Actions\CreateDocumentationPage;
use App\Domain\Documentation\Actions\UpdateDocumentationPage;
use App\Domain\Tags\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

// Questi test riguardano solo il Tag auto-generato, non il PDF (US-406): la coda
// è fake per non dipendere da un vero avvio di Chromium qui, che viene invece
// esercitato per davvero da DocumentationPagePdfTest.
beforeEach(fn () => Queue::fake());

test('creating a documentation page creates a linked tag named "Documentation: <title>"', function (): void {
    $page = CreateDocumentationPage::run(['title' => 'Guida al portale', 'body' => 'Contenuto']);

    $tag = Tag::query()->where('documentation_id', $page->id)->first();

    expect($tag)->not->toBeNull()
        ->and($tag->name)->toBe('Documentation: Guida al portale')
        ->and($tag->slug)->toBe('documentation-guida-al-portale')
        ->and($page->fresh()->tags()->count())->toBe(1);
});

test('renaming a documentation page renames the existing linked tag without creating a duplicate', function (): void {
    $page = CreateDocumentationPage::run(['title' => 'Guida al portale', 'body' => 'Contenuto']);
    $originalTagId = Tag::query()->where('documentation_id', $page->id)->first()->id;

    UpdateDocumentationPage::run($page, ['title' => 'Guida al portale (v2)', 'body' => 'Contenuto']);

    expect(Tag::query()->where('documentation_id', $page->id)->count())->toBe(1);

    $tag = Tag::query()->where('documentation_id', $page->id)->first();

    expect($tag->id)->toBe($originalTagId)
        ->and($tag->name)->toBe('Documentation: Guida al portale (v2)');
});

test('updating a documentation page without changing the title does not touch the linked tag', function (): void {
    $page = CreateDocumentationPage::run(['title' => 'Guida al portale', 'body' => 'Contenuto']);
    $tag = Tag::query()->where('documentation_id', $page->id)->first();
    $originalUpdatedAt = $tag->updated_at;

    UpdateDocumentationPage::run($page, ['title' => 'Guida al portale', 'body' => 'Contenuto aggiornato']);

    expect($tag->fresh()->updated_at)->toEqual($originalUpdatedAt)
        ->and($tag->fresh()->name)->toBe('Documentation: Guida al portale');
});

test('two pages with colliding titles get distinct tag slugs', function (): void {
    $firstPage = CreateDocumentationPage::run(['title' => 'Guida', 'body' => 'Contenuto']);
    $secondPage = CreateDocumentationPage::run(['title' => 'Guida', 'body' => 'Altro contenuto']);

    $firstTag = Tag::query()->where('documentation_id', $firstPage->id)->first();
    $secondTag = Tag::query()->where('documentation_id', $secondPage->id)->first();

    expect($firstTag->slug)->toBe('documentation-guida')
        ->and($secondTag->slug)->toBe('documentation-guida-2');
});
