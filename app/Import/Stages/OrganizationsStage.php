<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage 3 (§11.4 del PRD): importa `organizations` dal v1, `id` conservato,
 * mapping diretto. Propedeutico a `organization_members` (stesso file),
 * `activity_reports` (owner_kind = organization, US-212) e ai filtri ticket
 * per organizzazione del richiedente.
 */
final class OrganizationsStage implements ImportStage
{
    /** @var array<int, string> */
    private const LEGACY_COLUMNS = ['id', 'name', 'activity_report_language', 'created_at', 'updated_at'];

    public function name(): string
    {
        return 'organizations';
    }

    public function dependencies(): array
    {
        return [];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('organizations')->select(self::LEGACY_COLUMNS)->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $read++;

            if ($context->isDryRun()) {
                continue;
            }

            $attributes = [
                'name' => $row->name,
                'locale' => $row->activity_report_language,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];

            $existing = DB::table('organizations')->where('id', $row->id)->first();

            if ($existing === null) {
                DB::table('organizations')->insert(['id' => $row->id, ...$attributes]);
                $created++;

                continue;
            }

            if ($this->attributesDiffer($existing, $attributes)) {
                DB::table('organizations')->where('id', $row->id)->update($attributes);
                $updated++;
            } else {
                $skipped++;
            }
        }

        return new StageResult(read: $read, created: $created, updated: $updated, skipped: $skipped);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function attributesDiffer(object $existing, array $attributes): bool
    {
        foreach ($attributes as $column => $value) {
            if ((string) ($existing->{$column} ?? '') !== (string) ($value ?? '')) {
                return true;
            }
        }

        return false;
    }
}
