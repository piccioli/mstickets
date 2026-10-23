<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Policies;

use App\Domain\CaiDirectory\Models\CaiTrailTerritory;
use App\Domain\CaiDirectory\Policies\Concerns\AuthorizesTrailRegion;
use App\Domain\Identity\Models\User;

/**
 * Sola lettura (US-1062): staff con `CaiDirectoryView` e customer GR della regione, vedi {@see AuthorizesTrailRegion}.
 */
class CaiTrailTerritoryPolicy
{
    use AuthorizesTrailRegion;

    public function view(User $user, CaiTrailTerritory $caiTrailTerritory): bool
    {
        return $this->canViewRegion($user, $caiTrailTerritory->region_id);
    }
}
