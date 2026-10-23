<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Policies;

use App\Domain\CaiDirectory\Models\CaiSicaiStage;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Models\User;

/**
 * Tappe SICAI di sola lettura (US-1091): la fonte è osm2cai, nessuna scrittura da interfaccia. Per ora le vede lo staff
 * con `CaiDirectoryView` e il customer GR la cui regione ha la lettera della tappa ({@see Region::sicaiLetters()}).
 */
class CaiSicaiStagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::CaiDirectoryView);
    }

    public function view(User $user, CaiSicaiStage $caiSicaiStage): bool
    {
        if ($user->can(Permission::CaiDirectoryView)) {
            return true;
        }

        return $user->customer_type === CustomerType::GruppoRegionale
            && $user->region !== null
            && in_array($caiSicaiStage->letter, $user->region->sicaiLetters(), true);
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
