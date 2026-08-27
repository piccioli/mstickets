<?php

declare(strict_types=1);

use App\Domain\Documentation\Enums\DocumentationCategory;
use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeVisibilityPage(DocumentationCategory $category, string $slug): DocumentationPage
{
    return DocumentationPage::create([
        'title' => 'Guida',
        'slug' => $slug,
        'body' => 'Contenuto',
        'category' => $category,
    ]);
}

test('scopeVisibleTo excludes internal pages for a user without documentation.view.internal', function (): void {
    makeVisibilityPage(DocumentationCategory::Customer, 'guida-cliente');
    makeVisibilityPage(DocumentationCategory::Internal, 'guida-interna');

    $customer = userWithPermissions(PermissionEnum::DocumentationViewCustomer);

    $visibleSlugs = DocumentationPage::query()->visibleTo($customer)->pluck('slug')->all();

    expect($visibleSlugs)->toBe(['guida-cliente']);
});

test('scopeVisibleTo returns both categories for a user with both view permissions', function (): void {
    makeVisibilityPage(DocumentationCategory::Customer, 'guida-cliente');
    makeVisibilityPage(DocumentationCategory::Internal, 'guida-interna');

    $staff = userWithPermissions(PermissionEnum::DocumentationViewCustomer, PermissionEnum::DocumentationViewInternal);

    $visibleSlugs = DocumentationPage::query()->visibleTo($staff)->pluck('slug')->sort()->values()->all();

    expect($visibleSlugs)->toBe(['guida-cliente', 'guida-interna']);
});

test('scopeVisibleTo excludes customer pages for a user with only documentation.view.internal', function (): void {
    makeVisibilityPage(DocumentationCategory::Customer, 'guida-cliente');
    makeVisibilityPage(DocumentationCategory::Internal, 'guida-interna');

    $internalOnly = userWithPermissions(PermissionEnum::DocumentationViewInternal);

    $visibleSlugs = DocumentationPage::query()->visibleTo($internalOnly)->pluck('slug')->all();

    expect($visibleSlugs)->toBe(['guida-interna']);
});

test('scopeVisibleTo returns nothing for a user without any documentation.view.* permission', function (): void {
    makeVisibilityPage(DocumentationCategory::Customer, 'guida-cliente');
    makeVisibilityPage(DocumentationCategory::Internal, 'guida-interna');

    $stranger = userWithPermissions();

    expect(DocumentationPage::query()->visibleTo($stranger)->count())->toBe(0);
});

test('a customer cannot view an internal page even by requesting its id directly', function (): void {
    $internalPage = makeVisibilityPage(DocumentationCategory::Internal, 'guida-interna');

    $customer = userWithPermissions(PermissionEnum::DocumentationViewCustomer);

    expect($customer->can('view', $internalPage))->toBeFalse()
        ->and(DocumentationPage::query()->whereKey($internalPage->getKey())->visibleTo($customer)->exists())->toBeFalse();
});
