<?php

declare(strict_types=1);

use App\Domain\Documentation\Enums\DocumentationCategory;
use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Identity\Enums\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Non interessa il contenuto reale del PDF qui, solo l'autorizzazione della
    // rotta: coda fake per non dipendere da Chromium (già coperto per davvero da
    // DocumentationPagePdfTest), disco fake per poter scrivere un file fittizio.
    Queue::fake();
    Storage::fake('documentation-pdfs');
});

test('a user who can view the documentation page can download its pdf', function (): void {
    $page = DocumentationPage::create([
        'title' => 'Guida cliente',
        'slug' => 'guida-cliente',
        'body' => 'Contenuto',
        'category' => DocumentationCategory::Customer,
        'pdf_path' => 'documentation-pages/1.pdf',
        'pdf_generated_at' => now(),
    ]);
    Storage::disk('documentation-pdfs')->put($page->pdf_path, '%PDF-1.4 fake content');

    $viewer = userWithPermissions(Permission::DocumentationViewCustomer);

    $response = $this->actingAs($viewer)->get(route('documentation-pages.pdf-download', $page));

    $response->assertOk();
});

test('a user without the matching category permission is denied, even by direct id access', function (): void {
    $page = DocumentationPage::create([
        'title' => 'Guida interna',
        'slug' => 'guida-interna',
        'body' => 'Contenuto',
        'category' => DocumentationCategory::Internal,
        'pdf_path' => 'documentation-pages/2.pdf',
        'pdf_generated_at' => now(),
    ]);
    Storage::disk('documentation-pdfs')->put($page->pdf_path, '%PDF-1.4 fake content');

    $customerOnly = userWithPermissions(Permission::DocumentationViewCustomer);

    $response = $this->actingAs($customerOnly)->get(route('documentation-pages.pdf-download', $page));

    $response->assertForbidden();
});

test('a page whose pdf has not been generated yet returns a 404', function (): void {
    $page = DocumentationPage::create([
        'title' => 'Guida cliente',
        'slug' => 'guida-cliente-2',
        'body' => 'Contenuto',
        'category' => DocumentationCategory::Customer,
    ]);

    $viewer = userWithPermissions(Permission::DocumentationViewCustomer);

    $response = $this->actingAs($viewer)->get(route('documentation-pages.pdf-download', $page));

    $response->assertNotFound();
});
