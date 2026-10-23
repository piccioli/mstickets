<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Policies;

use App\Domain\CaiDirectory\Models\CaiTrailProvince;
use App\Domain\CaiDirectory\Policies\Concerns\AuthorizesTrailRegion;
use App\Domain\Identity\Models\User;

/**
 * Sola lettura (US-1062): staff con `CaiDirectoryView` e customer GR della regione, vedi {@see AuthorizesTrailRegion}.
 */
class CaiTrailProvincePolicy
{
    use AuthorizesTrailRegion;

    public function view(User $user, CaiTrailProvince $caiTrailProvince): bool
    {
        return $this->canViewRegion($user, $caiTrailProvince->region_id);
    }
}
