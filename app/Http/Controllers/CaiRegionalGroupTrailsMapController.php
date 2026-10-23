<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiTrailRegion;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupTrails;
use App\Domain\CaiDirectory\Support\CaiGeoJsonBounds;
use App\Domain\CaiDirectory\Support\CaiGeoJsonSimplifier;
use App\Domain\CaiDirectory\Support\CaiMapLine;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailRow;
use App\Domain\CaiDirectory\Support\CaiSectionShortName;
use App\Filament\Pages\CaiTrailDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Sentieri della mappa del GR nell'area visibile (US-1064): da zoom 11 `x-cai.map` chiede le linee del riquadro
 * (`?bbox=ovest,sud,est,nord`, gradi WGS84, e `zoom` della mappa) dei sentieri **presenti** nella regione osm2cai, con la
 * geometria semplificata per quello zoom ({@see CaiGeoJsonSimplifier}: ~1/5 dei punti a zoom 11), già pronte per la mappa
 * ({@see CaiMapLine::forTrail()} con settore, Sezioni e link alla scheda con `da=gr`). Al massimo
 * `cai_directory.trails_map.limit` sentieri, i più lunghi; `truncated` dice alla mappa di mostrare il messaggio "zooma
 * per vederli tutti".
 *
 * Autorizzazione = `CaiTrailRegionPolicy::view` (customer GR della regione o staff con `CaiDirectoryView`).
 */
class CaiRegionalGroupTrailsMapController extends Controller
{
    public function __invoke(Request $request, CaiTrailRegion $caiTrailRegion): JsonResponse
    {
        Gate::authorize('view', $caiTrailRegion);

        $bounds = self::bounds($request->query('bbox'));
        abort_if($bounds === null, 422, 'bbox non valido: atteso ovest,sud,est,nord');

        $limit = max(1, (int) config('cai_directory.trails_map.limit', 400));
        ['rows' => $rows, 'total' => $total] = CaiRegionalGroupTrails::inBounds($caiTrailRegion->id, $bounds, $limit);
        $tolerance = CaiGeoJsonSimplifier::toleranceForZoom(self::zoom($request->query('zoom')));

        foreach ($rows as $row) {
            if ($row->trail->geometry !== null) {
                $row->trail->geometry = CaiGeoJsonSimplifier::simplify($row->trail->geometry, $tolerance);
            }
        }

        $lines = collect($rows)
            ->map(fn (CaiRegionalGroupTrailRow $row): ?CaiMapLine => CaiMapLine::forTrail(
                $row->trail,
                CaiTrailDetail::getUrl(['trailId' => $row->trail->id, 'da' => 'gr']),
                self::sectorLabel($row),
                array_map(fn (CaiSection $section): string => CaiSectionShortName::of($section->name), $row->sections()),
            ))
            ->filter()
            ->map(fn (CaiMapLine $line): array => $line->toArray())
            ->values();

        return response()
            ->json([
                'lines' => $lines,
                'total' => $total,
                'limit' => $limit,
                'truncated' => $total > count($rows),
            ], options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Cache-Control', 'private, max-age=300');
    }

    /** "LBG01 Val Brembana"; solo il codice se il settore non ha un nome proprio (il nome della fonte è il codice). */
    private static function sectorLabel(CaiRegionalGroupTrailRow $row): ?string
    {
        if ($row->sector === null) {
            return null;
        }

        $code = $row->sector->full_code ?? $row->sector->code;
        $name = $row->sector->name;

        return $name === null || $name === '' || $name === $code ? $code : trim($code.' '.$name);
    }

    /** Zoom della mappa (11–16; mancante o non valido = 11, il più semplificato). */
    public static function zoom(mixed $zoom): int
    {
        return is_string($zoom) && ctype_digit($zoom) ? max(11, min(16, (int) $zoom)) : 11;
    }

    /** `ovest,sud,est,nord` in gradi, con ovest < est e sud < nord; altrimenti `null`. */
    public static function bounds(mixed $bbox): ?CaiGeoJsonBounds
    {
        if (! is_string($bbox) || strlen($bbox) > 100) {
            return null;
        }

        $parts = explode(',', $bbox);

        if (count($parts) !== 4 || array_filter($parts, fn (string $part): bool => ! is_numeric(trim($part))) !== []) {
            return null;
        }

        [$west, $south, $east, $north] = array_map(fn (string $part): float => (float) trim($part), $parts);

        if ($west >= $east || $south >= $north || abs($south) > 90 || abs($north) > 90 || abs($west) > 180 || abs($east) > 180) {
            return null;
        }

        return new CaiGeoJsonBounds(minLat: $south, minLng: $west, maxLat: $north, maxLng: $east);
    }
}
