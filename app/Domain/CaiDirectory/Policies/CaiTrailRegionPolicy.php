<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Policies;

use App\Domain\CaiDirectory\Models\CaiTrailRegion;
use App\Domain\CaiDirectory\Policies\Concerns\AuthorizesTrailRegion;
use App\Domain\Identity\Models\User;

/**
 * Sola lettura (US-1062): staff con `CaiDirectoryView` e customer GR della regione, vedi {@see AuthorizesTrailRegion}.
 */
class CaiTrailRegionPolicy
{
    use AuthorizesTrailRegion;

    public function view(User $user, CaiTrailRegion $caiTrailRegion): bool
    {
        return $this->canViewRegion($user, $caiTrailRegion->id);
    }
}
