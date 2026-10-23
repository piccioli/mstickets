<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Policies;

use App\Domain\CaiDirectory\Models\CaiSicaiStage;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;

/**
 * Tappe SICAI di sola lettura (US-1091): la fonte è osm2cai, nessuna scrittura da interfaccia. Per ora le vede lo staff
 * con `CaiDirectoryView`; il GR per lettera SICAI della sua regione arriva con le pagine della 10.9.
 */
class CaiSicaiStagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::CaiDirectoryView);
    }

    public function view(User $user, CaiSicaiStage $caiSicaiStage): bool
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
