<?php

declare(strict_types=1);

use App\Domain\Documentation\Enums\DocumentationCategory;
use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeDocumentationPage(DocumentationCategory $category): DocumentationPage
{
    return DocumentationPage::create([
        'title' => 'Guida rapida',
        'slug' => 'guida-rapida-'.$category->value,
        'body' => 'Contenuto',
        'category' => $category,
    ]);
}

test('a user without any documentation.* permission is denied every DocumentationPagePolicy ability', function (): void {
    $actor = userWithPermissions();
    $page = makeDocumentationPage(DocumentationCategory::Customer);

    expect($actor->can('viewAny', DocumentationPage::class))->toBeFalse()
        ->and($actor->can('view', $page))->toBeFalse()
        ->and($actor->can('create', DocumentationPage::class))->toBeFalse()
        ->and($actor->can('update', $page))->toBeFalse()
        ->and($actor->can('delete', $page))->toBeFalse();
});

test('a customer-category page is gated by documentation.view.customer, an internal one by documentation.view.internal', function (): void {
    $customerPage = makeDocumentationPage(DocumentationCategory::Customer);
    $internalPage = makeDocumentationPage(DocumentationCategory::Internal);

    $customerViewer = userWithPermissions(PermissionEnum::DocumentationViewCustomer);
    expect($customerViewer->can('view', $customerPage))->toBeTrue()
        ->and($customerViewer->can('view', $internalPage))->toBeFalse();

    $internalViewer = userWithPermissions(PermissionEnum::DocumentationViewInternal);
    expect($internalViewer->can('view', $internalPage))->toBeTrue()
        ->and($internalViewer->can('view', $customerPage))->toBeFalse();
});

test('a user with the matching documentation.* permission can create/update/delete', function (): void {
    $page = makeDocumentationPage(DocumentationCategory::Customer);

    expect(userWithPermissions(PermissionEnum::DocumentationCreate)->can('create', DocumentationPage::class))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::DocumentationUpdate)->can('update', $page))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::DocumentationDelete)->can('delete', $page))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::DocumentationDelete)->can('restore', $page))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::DocumentationDelete)->can('forceDelete', $page))->toBeTrue();
});
