<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Policies\Concerns;

use App\Domain\CaiDirectory\Models\CaiTrailRegion;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;

/**
 * Territori dei sentieri di sola lettura (US-1062): nessuna scrittura da interfaccia, la fonte è osm2cai. Li vedono lo
 * staff con `CaiDirectoryView` e il customer GR della regione del territorio.
 */
trait AuthorizesTrailRegion
{
    public function viewAny(User $user): bool
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

    private function canViewRegion(User $user, ?int $regionId): bool
    {
        if ($user->can(Permission::CaiDirectoryView)) {
            return true;
        }

        if ($regionId === null || $user->customer_type !== CustomerType::GruppoRegionale || $user->region === null) {
            return false;
        }

        return CaiTrailRegion::query()->whereKey($regionId)->where('region_key', $user->region->value)->exists();
    }
}
