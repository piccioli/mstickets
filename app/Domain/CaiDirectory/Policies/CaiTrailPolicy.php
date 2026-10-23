<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Policies;

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSicaiStage;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupDashboardMetrics;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupTrails;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Sentieri di sola lettura (US-1052): nessuna scrittura da interfaccia, la fonte è osm2cai. Un sentiero lo vedono lo
 * staff con `CaiDirectoryView`, il customer collegato a una delle Sezioni del sentiero e il customer GR il cui prefisso
 * (regola della 10.2) contiene almeno una di quelle Sezioni, oppure nella cui regione il sentiero è **presente** (≥ 50%
 * della lunghezza, 10.7): l'elenco "Sentieri del GR" mostra anche sentieri senza Sezione o di Sezioni di altri GR. Il GR
 * vede anche le tappe SICAI delle lettere della sua regione (US-1096, {@see Region::sicaiLetters()}), anche se il
 * sentiero non ha Sezioni del GR né è nella regione.
 */
class CaiTrailPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::CaiDirectoryView);
    }

    public function view(User $user, CaiTrail $caiTrail): bool
    {
        if ($user->can(Permission::CaiDirectoryView)) {
            return true;
        }

        $codes = DB::table('cai_trail_section')->where('cai_trail_id', $caiTrail->id)->pluck('section_code');

        if ($codes->isNotEmpty() && CaiSection::query()->whereKey($codes->all())->where('user_id', $user->id)->exists()) {
            return true;
        }

        if ($user->customer_type !== CustomerType::GruppoRegionale || $user->region === null) {
            return false;
        }

        $prefix = CaiRegionalGroupDashboardMetrics::prefixOf($user->region);

        if ($prefix !== null && $codes->contains(fn (string $code): bool => str_starts_with($code, $prefix))) {
            return true;
        }

        if (CaiSicaiStage::query()->whereKey($caiTrail->id)->whereIn('letter', $user->region->sicaiLetters())->exists()) {
            return true;
        }

        $regionId = CaiRegionalGroupTrails::regionId($user->region);

        return $regionId !== null && CaiRegionalGroupTrails::presentTrails($regionId)->whereKey($caiTrail->id)->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CaiTrail $caiTrail): bool
    {
        return false;
    }

    public function delete(User $user, CaiTrail $caiTrail): bool
    {
        return false;
    }
}
