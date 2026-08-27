<?php

declare(strict_types=1);

use App\Domain\Documentation\Actions\CreateDocumentationPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // La coda è fake per creare le pagine di prova senza innescare la generazione
    // automatica alla creazione: ogni test governa esplicitamente quando il PDF
    // viene generato, chiamando il comando.
    Queue::fake();
    Storage::fake('documentation-pdfs');
});

test('regenerates the pdf of every documentation page', function (): void {
    $first = CreateDocumentationPage::run(['title' => 'Guida uno', 'body' => 'Contenuto uno']);
    $second = CreateDocumentationPage::run(['title' => 'Guida due', 'body' => 'Contenuto due']);

    expect($first->pdf_path)->toBeNull()
        ->and($second->pdf_path)->toBeNull();

    $this->artisan('documentation:regenerate-pdfs')->assertSuccessful();

    $first->refresh();
    $second->refresh();

    expect($first->pdf_path)->toBe("documentation-pages/{$first->id}.pdf")
        ->and($first->pdf_generated_at)->not->toBeNull()
        ->and($second->pdf_path)->toBe("documentation-pages/{$second->id}.pdf")
        ->and($second->pdf_generated_at)->not->toBeNull();

    Storage::disk('documentation-pdfs')->assertExists($first->pdf_path);
    Storage::disk('documentation-pdfs')->assertExists($second->pdf_path);
});

test('--dry-run examines pages without generating or writing any pdf', function (): void {
    $page = CreateDocumentationPage::run(['title' => 'Guida', 'body' => 'Contenuto']);

    $this->artisan('documentation:regenerate-pdfs', ['--dry-run' => true])->assertSuccessful();

    $page->refresh();

    expect($page->pdf_path)->toBeNull()
        ->and($page->pdf_generated_at)->toBeNull();

    Storage::disk('documentation-pdfs')->assertDirectoryEmpty('documentation-pages');
});
