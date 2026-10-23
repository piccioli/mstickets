<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Legame sentiero–territorio con la percentuale di lunghezza che vi ricade (US-1062), sola lettura. Livello = id più
 * profondo valorizzato: regione (`province_id`/`sector_id` null), provincia (`sector_id` null), settore. "Presente" in
 * un territorio = `percentage >= 50`.
 */
#[Fillable(['cai_trail_id', 'region_id', 'province_id', 'sector_id', 'percentage'])]
class CaiTrailTerritory extends Model
{
    protected $table = 'cai_trail_territory';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cai_trail_id' => 'integer',
            'region_id' => 'integer',
            'province_id' => 'integer',
            'sector_id' => 'integer',
            'percentage' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<CaiTrail, $this>
     */
    public function trail(): BelongsTo
    {
        return $this->belongsTo(CaiTrail::class, 'cai_trail_id');
    }

    /**
     * @return BelongsTo<CaiTrailRegion, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(CaiTrailRegion::class, 'region_id');
    }

    /**
     * @return BelongsTo<CaiTrailProvince, $this>
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(CaiTrailProvince::class, 'province_id');
    }

    /**
     * @return BelongsTo<CaiTrailSector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(CaiTrailSector::class, 'sector_id');
    }
}
