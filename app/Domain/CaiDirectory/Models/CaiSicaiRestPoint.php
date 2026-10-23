<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Punto di appoggio del Sentiero Italia CAI di rifugi.cai.it (US-1092), sola lettura: si scrive solo da
 * `cai:import-datapack` (tabella `snap_cai_sicai_rest_points`). `type` è il testo della fonte (Rifugio, Hotel, B&B, …);
 * `hut` solo se il punto è un rifugio importato. La vicinanza alle tappe si calcola dalla geometria (`stages()`, US-1093).
 */
#[Fillable([
    'id', 'name', 'type', 'latitude', 'longitude', 'region', 'municipality', 'address', 'operator', 'website',
    'email', 'phone', 'cai_hut_id_cai', 'fields', 'source_updated_at',
])]
class CaiSicaiRestPoint extends Model
{
    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'cai_hut_id_cai' => 'integer',
            'fields' => 'array',
            'source_updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CaiHut, $this>
     */
    public function hut(): BelongsTo
    {
        return $this->belongsTo(CaiHut::class, 'cai_hut_id_cai');
    }

    /**
     * Tappe SICAI il cui tracciato passa entro 1,5 km dal punto (US-1093); pivot `distance_m`, `position`.
     *
     * @return BelongsToMany<CaiSicaiStage, $this>
     */
    public function stages(): BelongsToMany
    {
        return $this->belongsToMany(CaiSicaiStage::class, 'cai_sicai_stage_rest_point', 'rest_point_id', 'cai_trail_id')
            ->withPivot(['distance_m', 'position']);
    }
}
