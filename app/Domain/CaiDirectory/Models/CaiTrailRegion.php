<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use App\Domain\Identity\Enums\Region;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Regione italiana di osm2cai (US-1062), sola lettura: si scrive solo da `cai:import-datapack`. `id` = id osm2cai,
 * `region_key` = regione della piattaforma (una riga per valore di {@see Region}).
 */
#[Fillable(['id', 'name', 'region_key'])]
class CaiTrailRegion extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'region_key' => Region::class,
        ];
    }

    /**
     * @return HasMany<CaiTrailProvince, $this>
     */
    public function provinces(): HasMany
    {
        return $this->hasMany(CaiTrailProvince::class, 'region_id');
    }
}
