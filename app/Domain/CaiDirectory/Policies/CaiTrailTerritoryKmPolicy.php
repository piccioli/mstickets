<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Policies;

use App\Domain\CaiDirectory\Models\CaiTrailTerritoryKm;
use App\Domain\CaiDirectory\Policies\Concerns\AuthorizesTrailRegion;
use App\Domain\Identity\Models\User;

/**
 * Sola lettura (US-1062): staff con `CaiDirectoryView` e customer GR della regione, vedi {@see AuthorizesTrailRegion}.
 */
class CaiTrailTerritoryKmPolicy
{
    use AuthorizesTrailRegion;

    public function view(User $user, CaiTrailTerritoryKm $caiTrailTerritoryKm): bool
    {
        return $this->canViewRegion($user, $caiTrailTerritoryKm->regionId());
    }
}
