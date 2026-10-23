<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Enums\CaiTrailTerritoryType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Km dei sentieri in un territorio per stato (US-1062), sola lettura: `km` = lunghezza intersecata con il territorio,
 * `present_routes` = sentieri con almeno il 50% nel territorio. Righe solo per i territori con almeno un sentiero.
 * Chiave composta (`territory_type`, `territory_id`, `status`): nessun `find()`, solo query.
 */
#[Fillable(['territory_type', 'territory_id', 'status', 'km', 'present_routes'])]
class CaiTrailTerritoryKm extends Model
{
    protected $table = 'cai_trail_territory_km';

    protected $primaryKey = 'territory_id';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'territory_type' => CaiTrailTerritoryType::class,
            'territory_id' => 'integer',
            'status' => CaiTrailStatus::class,
            'km' => 'decimal:3',
            'present_routes' => 'integer',
        ];
    }

    /** Id della regione osm2cai del territorio, per l'autorizzazione. */
    public function regionId(): ?int
    {
        $regionId = match ($this->territory_type) {
            CaiTrailTerritoryType::Region => $this->territory_id,
            CaiTrailTerritoryType::Province => CaiTrailProvince::query()->whereKey($this->territory_id)->value('region_id'),
            CaiTrailTerritoryType::Sector => CaiTrailSector::query()->whereKey($this->territory_id)
                ->join('cai_trail_provinces', 'cai_trail_provinces.id', '=', 'cai_trail_sectors.province_id')
                ->value('cai_trail_provinces.region_id'),
        };

        return $regionId === null ? null : (int) $regionId;
    }
}
