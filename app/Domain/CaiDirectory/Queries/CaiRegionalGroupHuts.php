<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Queries;

use App\Domain\CaiDirectory\Enums\CaiHutContractStatus;
use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Models\CaiHut;
use App\Domain\CaiDirectory\Support\CaiHutContractExpiry;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupHutRow;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupHutsData;
use App\Domain\Identity\Enums\Region;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Rifugi di sola lettura di un Gruppo Regionale (Fase 10.5), da `cai_huts` (nessuna chiamata all'API):
 *
 * - i rifugi delle **Sezioni del GR** (`branch_code` fra le Sezioni con il prefisso del GR,
 *   {@see CaiRegionalGroupDashboardMetrics::sectionsOf()}: la SAT `9218…` resta fuori dal GR Alto Adige `9219`);
 * - **più** i rifugi **intestati al GR**: `branch_code` che inizia per `95` e `branch_name` uguale, senza distinzione
 *   di maiuscole, a "GR {etichetta della regione}" (oggi solo `9500046` "GR Sicilia"). La loro "Sezione" è
 *   "GR {regione}".
 *
 * Oltre a quella del prefisso, una query per le Sezioni e una per i rifugi (nessun N+1). Stato del contratto da
 * {@see CaiHutContractExpiry}, lo stesso della scheda del rifugio.
 */
final class CaiRegionalGroupHuts
{
    /** Colonne di `cai_huts` che servono a elenco, mappa e riepilogo (non i JSON grezzi della scheda). */
    private const array COLUMNS = ['id_cai', 'branch_code', 'branch_name', 'name', 'alias', 'type', 'status',
        'latitude', 'longitude', 'altitude', 'beds', 'photos', 'management', 'source_updated_at'];

    public static function forRegion(Region $region, ?CarbonInterface $today = null): CaiRegionalGroupHutsData
    {
        $today ??= CarbonImmutable::today();
        $groupName = self::groupName($region);
        $prefix = CaiRegionalGroupDashboardMetrics::prefixOf($region);

        /** @var array<string, string> $sectionNames codice → nome */
        $sectionNames = $prefix === null
            ? []
            : CaiRegionalGroupDashboardMetrics::sectionsOf($prefix)
                ->orderBy('name')
                ->pluck('name', 'codice_cai')
                ->map(fn (mixed $name): string => (string) $name)
                ->all();

        $huts = self::hutsQuery(array_map('strval', array_keys($sectionNames)), $groupName)
            ->orderBy('name')
            ->get(self::COLUMNS);

        $rows = [];

        foreach ($huts as $hut) {
            $ownedByGroup = ! array_key_exists((string) $hut->branch_code, $sectionNames);
            $expiry = CaiHutContractExpiry::of($hut);

            $rows[] = new CaiRegionalGroupHutRow(
                hut: $hut,
                sectionName: $ownedByGroup ? $groupName : $sectionNames[(string) $hut->branch_code],
                ownedByGroup: $ownedByGroup,
                contractEnd: $expiry->endDate,
                contractStatus: $expiry->status($today),
                contractNote: $expiry->note($today),
            );
        }

        $withHuts = [];

        foreach ($rows as $row) {
            if (! $row->ownedByGroup) {
                $withHuts[(string) $row->hut->branch_code] = true;
            }
        }

        $without = [];

        foreach ($sectionNames as $code => $name) {
            if (! isset($withHuts[(string) $code])) {
                $without[] = ['codiceCai' => (string) $code, 'name' => $name];
            }
        }

        return new CaiRegionalGroupHutsData(
            codiceCai: $prefix === null ? null : $prefix.'000',
            groupName: $groupName,
            huts: $rows,
            sectionsCount: count($sectionNames),
            sectionsWithHutsCount: count($withHuts),
            sectionsWithoutHuts: $without,
            total: count($rows),
            active: count(array_filter($rows, fn (CaiRegionalGroupHutRow $r): bool => $r->hut->status === CaiHutStatus::Active)),
            beds: array_sum(array_map(fn (CaiRegionalGroupHutRow $r): int => $r->hut->beds ?? 0, $rows)),
            expiringContracts: self::countContracts($rows, CaiHutContractStatus::Expiring),
            expiredContracts: self::countContracts($rows, CaiHutContractStatus::Expired),
        );
    }

    /** "GR Toscana": nome del GR e "Sezione" dei rifugi intestati al GR. */
    public static function groupName(Region $region): string
    {
        return 'GR '.$region->label();
    }

    /**
     * @param  list<string>  $sectionCodes
     * @return Builder<CaiHut>
     */
    private static function hutsQuery(array $sectionCodes, string $groupName): Builder
    {
        return CaiHut::query()->where(function (Builder $query) use ($sectionCodes, $groupName): void {
            if ($sectionCodes !== []) {
                $query->whereIn('branch_code', $sectionCodes);
            }

            $query->orWhere(fn (Builder $owned) => $owned
                ->where('branch_code', 'like', '95%')
                ->whereRaw('LOWER(branch_name) = ?', [mb_strtolower($groupName)]));
        });
    }

    /**
     * @param  list<CaiRegionalGroupHutRow>  $rows
     */
    private static function countContracts(array $rows, CaiHutContractStatus $status): int
    {
        return count(array_filter($rows, fn (CaiRegionalGroupHutRow $r): bool => $r->contractStatus === $status));
    }
}
