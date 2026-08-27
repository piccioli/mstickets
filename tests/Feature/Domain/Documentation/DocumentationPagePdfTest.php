<?php

declare(strict_types=1);

use App\Domain\Documentation\Actions\CreateDocumentationPage;
use App\Domain\Documentation\Actions\UpdateDocumentationPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Coda "sync" in test (phpunit.xml): il job di generazione PDF gira per davvero,
 * con Chromium reale (auto-scoperto da chrome-php in base al sistema operativo,
 * §6.4.3 del PRD). Deliberatamente niente `Pdf::fake()`/`Queue::fake()` qui — a
 * differenza di DocumentationPageAutoTagTest/DocumentationPageResourceTest, che
 * non riguardano il PDF e per cui la coda è fake, questo file esiste apposta per
 * verificare che il contenuto generato sia un PDF reale e non vuoto (US-406 AC:
 * "contenuto/non-vuoto, non il rendering pixel-per-pixel").
 */
beforeEach(fn () => Storage::fake('documentation-pdfs'));

test('creating a documentation page generates a non-empty PDF and stamps pdf_path/pdf_generated_at', function (): void {
    $page = CreateDocumentationPage::run(['title' => 'Guida al portale', 'body' => 'Contenuto di prova.']);
    $page->refresh();

    expect($page->pdf_path)->toBe("documentation-pages/{$page->id}.pdf")
        ->and($page->pdf_generated_at)->not->toBeNull();

    Storage::disk('documentation-pdfs')->assertExists($page->pdf_path);

    $contents = Storage::disk('documentation-pdfs')->get($page->pdf_path);

    expect($contents)->not->toBeEmpty()
        ->and(substr($contents, 0, 4))->toBe('%PDF');
});

test('changing the title regenerates the PDF with a newer timestamp', function (): void {
    $page = CreateDocumentationPage::run(['title' => 'Guida al portale', 'body' => 'Contenuto di prova.']);
    $page->refresh();
    $firstGeneratedAt = $page->pdf_generated_at;
    $firstPath = $page->pdf_path;

    // Avanza il tempo esplicitamente (§ granularità `timestamp` a secondi):
    // senza questo, due generazioni reali potrebbero cadere nello stesso secondo
    // e rendere il confronto sotto un falso negativo intermittente.
    $this->travel(5)->seconds();
    UpdateDocumentationPage::run($page, ['title' => 'Guida al portale (v2)', 'body' => 'Contenuto di prova.']);
    $page->refresh();

    expect($page->pdf_path)->toBe($firstPath)
        ->and($page->pdf_generated_at)->not->toEqual($firstGeneratedAt);

    Storage::disk('documentation-pdfs')->assertExists($page->pdf_path);
});

test('changing only the body regenerates the PDF', function (): void {
    $page = CreateDocumentationPage::run(['title' => 'Guida al portale', 'body' => 'Contenuto iniziale.']);
    $page->refresh();
    $firstGeneratedAt = $page->pdf_generated_at;

    $this->travel(5)->seconds();
    UpdateDocumentationPage::run($page, ['title' => 'Guida al portale', 'body' => 'Contenuto aggiornato.']);
    $page->refresh();

    expect($page->pdf_generated_at)->not->toEqual($firstGeneratedAt);
});

test('saving without changing title or body does not regenerate the PDF', function (): void {
    $page = CreateDocumentationPage::run(['title' => 'Guida al portale', 'body' => 'Contenuto di prova.']);
    $page->refresh();
    $firstGeneratedAt = $page->pdf_generated_at;

    UpdateDocumentationPage::run($page, ['title' => 'Guida al portale', 'body' => 'Contenuto di prova.']);
    $page->refresh();

    expect($page->pdf_generated_at)->toEqual($firstGeneratedAt);
});
