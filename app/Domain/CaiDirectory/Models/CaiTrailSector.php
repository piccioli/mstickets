<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Settore di osm2cai (US-1062), sola lettura. `name` = `human_name` della fonte (o il nome tecnico), `geometry` = GeoJSON
 * MultiPolygon 2D semplificato, per la mappa per settori.
 *
 * @property array<string, mixed>|null $geometry
 */
#[Fillable(['id', 'province_id', 'code', 'full_code', 'name', 'geometry'])]
class CaiTrailSector extends Model
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
            'province_id' => 'integer',
            'geometry' => 'array',
        ];
    }

    /**
     * @return BelongsTo<CaiTrailProvince, $this>
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(CaiTrailProvince::class, 'province_id');
    }
}
