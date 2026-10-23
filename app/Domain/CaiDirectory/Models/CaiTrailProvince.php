<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Provincia di osm2cai (US-1062), sola lettura. `code` = sigla (es. `SO`), può mancare.
 */
#[Fillable(['id', 'region_id', 'name', 'code'])]
class CaiTrailProvince extends Model
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
            'region_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CaiTrailRegion, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(CaiTrailRegion::class, 'region_id');
    }

    /**
     * @return HasMany<CaiTrailSector, $this>
     */
    public function sectors(): HasMany
    {
        return $this->hasMany(CaiTrailSector::class, 'province_id');
    }
}
