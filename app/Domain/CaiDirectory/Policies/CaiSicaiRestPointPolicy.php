<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Policies;

use App\Domain\CaiDirectory\Models\CaiSicaiRestPoint;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;

/**
 * Punti di appoggio SICAI di sola lettura (US-1092): la fonte è rifugi.cai.it, nessuna scrittura da interfaccia. Per
 * ora li vede lo staff con `CaiDirectoryView`; il GR arriva con le pagine della 10.9.
 */
class CaiSicaiRestPointPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::CaiDirectoryView);
    }

    public function view(User $user, CaiSicaiRestPoint $caiSicaiRestPoint): bool
    {
        return $user->can(Permission::CaiDirectoryView);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user): bool
    {
        return false;
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
