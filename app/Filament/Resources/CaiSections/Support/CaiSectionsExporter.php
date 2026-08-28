<?php

declare(strict_types=1);

namespace App\Filament\Resources\CaiSections\Support;

use App\Domain\CaiDirectory\Models\CaiSection;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export dell'elenco sezioni CAI (US-805) per lo staff: prende sempre una collezione già
 * filtrata (la query corrente di `CaiSectionsTable`, con gli eventuali filtri/ricerca
 * applicati), mai `CaiSection::query()->get()` diretto, per rispettare l'AC "solo sezioni
 * correntemente filtrate/visibili". Stesso set di colonne per CSV/XLSX/GeoJSON: i campi utili
 * per un'analisi esterna (coordinate, contatto utente collegato) che non sono già colonne
 * visibili in tabella, non l'intero dettaglio RUNTS/bilanci/allegati (quello resta nella vista
 * di dettaglio, US-804).
 */
class CaiSectionsExporter
{
    /**
     * @var list<string>
     */
    private const HEADERS = [
        'codice_cai', 'name', 'region', 'address', 'phone', 'email', 'pec',
        'founded_year', 'members_count', 'latitude', 'longitude', 'linked_user_email',
    ];

    /**
     * @param  Collection<int, CaiSection>  $sections
     */
    public static function csv(Collection $sections, string $filename = 'sezioni-cai.csv'): StreamedResponse
    {
        $content = self::buildCsvContent($sections);

        return response()->streamDownload(
            fn () => print $content,
            $filename,
            ['Content-Type' => 'text/csv'],
        );
    }

    /**
     * @param  Collection<int, CaiSection>  $sections
     */
    public static function xlsx(Collection $sections, string $filename = 'sezioni-cai.xlsx'): StreamedResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'cai-sections-export-');

        $writer = new XlsxWriter;
        $writer->openToFile($tempPath);
        $writer->addRow(Row::fromValues(self::HEADERS));

        foreach ($sections as $section) {
            $writer->addRow(Row::fromValues(self::row($section)));
        }

        $writer->close();

        $content = (string) file_get_contents($tempPath);
        unlink($tempPath);

        return response()->streamDownload(
            fn () => print $content,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /**
     * @param  Collection<int, CaiSection>  $sections
     */
    public static function geoJson(Collection $sections, string $filename = 'sezioni-cai.geojson'): StreamedResponse
    {
        $content = (string) json_encode(self::buildGeoJson($sections), JSON_THROW_ON_ERROR);

        return response()->streamDownload(
            fn () => print $content,
            $filename,
            ['Content-Type' => 'application/geo+json'],
        );
    }

    /**
     * @param  Collection<int, CaiSection>  $sections
     */
    private static function buildCsvContent(Collection $sections): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, self::HEADERS);

        foreach ($sections as $section) {
            fputcsv($handle, self::row($section));
        }

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    /**
     * @param  Collection<int, CaiSection>  $sections
     * @return array{type: string, features: list<array<string, mixed>>}
     */
    private static function buildGeoJson(Collection $sections): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => $sections->map(fn (CaiSection $section): array => [
                'type' => 'Feature',
                'geometry' => ($section->latitude !== null && $section->longitude !== null)
                    ? [
                        'type' => 'Point',
                        'coordinates' => [(float) $section->longitude, (float) $section->latitude],
                    ]
                    : null,
                'properties' => [
                    'codice_cai' => $section->codice_cai,
                    'name' => $section->name,
                    'region' => $section->region,
                    'address' => $section->address,
                    'phone' => $section->phone,
                    'email' => $section->email,
                    'pec' => $section->pec,
                    'founded_year' => $section->founded_year,
                    'members_count' => $section->members_count,
                    'linked_user_email' => $section->user?->email,
                ],
            ])->values()->all(),
        ];
    }

    /**
     * @return list<scalar|null>
     */
    private static function row(CaiSection $section): array
    {
        return [
            $section->codice_cai,
            $section->name,
            $section->region,
            $section->address,
            $section->phone,
            $section->email,
            $section->pec,
            $section->founded_year,
            $section->members_count,
            $section->latitude !== null ? (float) $section->latitude : null,
            $section->longitude !== null ? (float) $section->longitude : null,
            $section->user?->email,
        ];
    }
}
