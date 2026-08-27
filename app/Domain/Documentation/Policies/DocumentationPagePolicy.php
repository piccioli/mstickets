<?php

declare(strict_types=1);

namespace App\Domain\Documentation\Policies;

use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;

class DocumentationPagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([Permission::DocumentationViewCustomer, Permission::DocumentationViewInternal]);
    }

    public function view(User $user, DocumentationPage $documentationPage): bool
    {
        return DocumentationPage::query()->whereKey($documentationPage->getKey())->visibleTo($user)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::DocumentationCreate);
    }

    public function update(User $user, DocumentationPage $documentationPage): bool
    {
        return $user->can(Permission::DocumentationUpdate);
    }

    public function delete(User $user, DocumentationPage $documentationPage): bool
    {
        return $user->can(Permission::DocumentationDelete);
    }

    public function restore(User $user, DocumentationPage $documentationPage): bool
    {
        return $user->can(Permission::DocumentationDelete);
    }

    public function forceDelete(User $user, DocumentationPage $documentationPage): bool
    {
        return $user->can(Permission::DocumentationDelete);
    }
}
