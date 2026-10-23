<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Models;

use App\Domain\CaiDirectory\Support\CaiSicaiStageCode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Tappa del Sentiero Italia CAI (US-1091), sola lettura: si scrive solo da `cai:import-datapack`
 * (`snap_osm2cai_sicai_stages`). La tappa È un sentiero di `cai_trails` (chiave `cai_trail_id`): tracciato, km,
 * dislivelli e percorribilità stanno lì. `letter`/`number`/`variant` nulli se il codice non è nel nome del sentiero
 * (vedi {@see CaiSicaiStageCode}); ordinare per `sort_key`, poi `cai_trail_id`.
 */
#[Fillable(['cai_trail_id', 'itinerary_name', 'letter', 'number', 'variant', 'code', 'sort_key'])]
class CaiSicaiStage extends Model
{
    protected $primaryKey = 'cai_trail_id';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cai_trail_id' => 'integer',
            'number' => 'integer',
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
     * Punti di appoggio entro 1,5 km dal tracciato (US-1093), in ordine di percorrenza; pivot `distance_m`, `position`.
     *
     * @return BelongsToMany<CaiSicaiRestPoint, $this>
     */
    public function restPoints(): BelongsToMany
    {
        return $this->belongsToMany(CaiSicaiRestPoint::class, 'cai_sicai_stage_rest_point', 'cai_trail_id', 'rest_point_id')
            ->withPivot(['distance_m', 'position'])
            ->orderByPivot('position')
            ->orderBy('cai_sicai_rest_points.id');
    }
}
