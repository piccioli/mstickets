<?php

declare(strict_types=1);

use App\Domain\Documentation\Enums\DocumentationCategory;
use App\Domain\Documentation\Models\DocumentationPage;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('documentation_pages table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('documentation_pages', [
        'id', 'title', 'slug', 'body', 'category', 'pdf_path', 'pdf_generated_at',
        'created_at', 'updated_at', 'deleted_at',
    ]))->toBeTrue();
});

test('category defaults to customer and is cast to the DocumentationCategory enum', function (): void {
    $page = DocumentationPage::create(['title' => 'Guida cliente', 'slug' => 'guida-cliente', 'body' => 'Contenuto']);

    expect($page->fresh()->category)->toBe(DocumentationCategory::Customer);
});

test('slug is unique', function (): void {
    DocumentationPage::create(['title' => 'Guida', 'slug' => 'guida', 'body' => 'Contenuto']);

    expect(fn () => DocumentationPage::create(['title' => 'Guida bis', 'slug' => 'guida', 'body' => 'Altro contenuto']))
        ->toThrow(QueryException::class);
});

test('a soft-deleted page is excluded from default queries', function (): void {
    $page = DocumentationPage::create(['title' => 'Guida', 'slug' => 'guida', 'body' => 'Contenuto']);

    $page->delete();

    expect(DocumentationPage::count())->toBe(0)
        ->and(DocumentationPage::withTrashed()->count())->toBe(1);
});

test('registers the documents and images media collections on the private documentation-attachments disk', function (): void {
    $page = DocumentationPage::create(['title' => 'Guida', 'slug' => 'guida', 'body' => 'Contenuto']);

    expect($page->getMediaCollection('documents')->diskName)->toBe('documentation-attachments')
        ->and($page->getMediaCollection('images')->diskName)->toBe('documentation-attachments');
});

test('body is sanitized like ticket_messages.body_html on write', function (): void {
    $page = DocumentationPage::create([
        'title' => 'Guida',
        'slug' => 'guida',
        'body' => '<p>Testo</p><script>alert(1)</script>',
    ]);

    expect($page->fresh()->body)->toBe('<p>Testo</p>')
        ->and($page->fresh()->body)->not->toContain('<script>');
});
