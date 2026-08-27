<?php

declare(strict_types=1);

use App\Domain\Documentation\Actions\CreateDocumentationPage;
use App\Domain\Documentation\Enums\DocumentationCategory;
use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Filament\Resources\DocumentationPages\DocumentationPageResource;
use App\Filament\Resources\DocumentationPages\Pages\CreateDocumentationPage as CreateDocumentationPagePage;
use App\Filament\Resources\DocumentationPages\Pages\ListDocumentationPages;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

function createTestDocumentationPage(string $title, string $body, DocumentationCategory $category = DocumentationCategory::Customer): DocumentationPage
{
    return CreateDocumentationPage::run([
        'title' => $title,
        'body' => $body,
        'category' => $category,
    ]);
}

test('a user without any documentation.view.* permission is denied access to the resource', function (): void {
    $user = grantTicketPanelRole(userWithPermissions());

    expect(DocumentationPageResource::canViewAny())->toBeFalse();

    $this->actingAs($user)->get(DocumentationPageResource::getUrl('index'))->assertForbidden();
});

test('a user with documentation.view.customer can access the registry and see only customer pages', function (): void {
    $user = grantTicketPanelRole(userWithPermissions(PermissionEnum::DocumentationViewCustomer));
    $customerPage = createTestDocumentationPage('Guida cliente', 'Contenuto cliente', DocumentationCategory::Customer);
    $internalPage = createTestDocumentationPage('Guida interna', 'Contenuto interno', DocumentationCategory::Internal);

    $this->actingAs($user);

    Livewire::test(ListDocumentationPages::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$customerPage])
        ->assertCanNotSeeTableRecords([$internalPage]);
});

test('a user without documentation.create does not get the create page action', function (): void {
    $user = grantTicketPanelRole(userWithPermissions(PermissionEnum::DocumentationViewCustomer));

    $this->actingAs($user);

    expect(DocumentationPageResource::canCreate())->toBeFalse();
});

test('a user with documentation.create can create a page and it appears with the linked tag', function (): void {
    $user = grantTicketPanelRole(userWithPermissions(
        PermissionEnum::DocumentationViewCustomer,
        PermissionEnum::DocumentationCreate,
    ));

    $this->actingAs($user);

    Livewire::test(CreateDocumentationPagePage::class)
        ->fillForm([
            'title' => 'Guida rapida',
            'body' => 'Testo introduttivo',
            'category' => DocumentationCategory::Customer->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $page = DocumentationPage::query()->where('title', 'Guida rapida')->firstOrFail();

    expect($page->tags()->count())->toBe(1)
        ->and($page->tags()->first()->name)->toBe('Documentation: Guida rapida');
});

test('full-text search finds a page by a term in the title', function (): void {
    $user = grantTicketPanelRole(userWithPermissions(PermissionEnum::DocumentationViewCustomer));
    $matching = createTestDocumentationPage('Configurazione avanzata del portale', 'Contenuto generico');
    $other = createTestDocumentationPage('Guida introduttiva', 'Contenuto generico');

    $this->actingAs($user);

    Livewire::test(ListDocumentationPages::class)
        ->searchTable('avanzata')
        ->assertCanSeeTableRecords([$matching])
        ->assertCanNotSeeTableRecords([$other]);
});

test('full-text search finds a page by a term only present in the body', function (): void {
    $user = grantTicketPanelRole(userWithPermissions(PermissionEnum::DocumentationViewCustomer));
    $matching = createTestDocumentationPage('Guida', 'Il paragrafo spiega la fatturazione elettronica');
    $other = createTestDocumentationPage('Altra guida', 'Contenuto senza corrispondenze');

    $this->actingAs($user);

    Livewire::test(ListDocumentationPages::class)
        ->searchTable('fatturazione')
        ->assertCanSeeTableRecords([$matching])
        ->assertCanNotSeeTableRecords([$other]);
});
