<?php

declare(strict_types=1);

namespace App\Domain\Mail\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Models\EmailAttachment;

class EmailAttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::EmailView);
    }

    public function view(User $user, EmailAttachment $emailAttachment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::EmailManage);
    }

    public function update(User $user, EmailAttachment $emailAttachment): bool
    {
        return $user->can(Permission::EmailManage);
    }

    public function delete(User $user, EmailAttachment $emailAttachment): bool
    {
        return $user->can(Permission::EmailManage);
    }
}
