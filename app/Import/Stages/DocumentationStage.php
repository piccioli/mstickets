<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Stages\Concerns\GeneratesProvisionalSlugs;
use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage 5 (§11.4 del PRD): importa `documentations` dal v1 in
 * `documentation_pages`, `id` conservato. Il v1 ha `Documentation::creator()`
 * verso una colonna `creator_id` inesistente (§16.2 #13, anti-pattern
 * esplicito): questo stage non la riproduce, `documentation_pages` non ha
 * alcuna colonna/relazione autore. `pdf_url` del v1 non è mappato: in v2
 * `pdf_path`/`pdf_generated_at` sono rigenerati in coda dal comando
 * `documentation:regenerate-pdfs` (§6.4.3), non importati da v1.
 */
final class DocumentationStage implements ImportStage
{
    use GeneratesProvisionalSlugs;

    /** @var array<int, string> */
    private const LEGACY_COLUMNS = ['id', 'name', 'description', 'category', 'created_at', 'updated_at'];

    public function name(): string
    {
        return 'documentation';
    }

    public function dependencies(): array
    {
        return [];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('documentations')->select(self::LEGACY_COLUMNS)->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $existingSlugs = DB::table('documentation_pages')->pluck('slug')->all();

        foreach ($rows as $row) {
            $read++;

            if ($context->isDryRun()) {
                continue;
            }

            $attributes = [
                'title' => $row->name,
                'body' => $row->description,
                'category' => $row->category,
            ];

            $existing = DB::table('documentation_pages')->where('id', $row->id)->first();

            if ($existing === null) {
                DB::table('documentation_pages')->insert([
                    'id' => $row->id,
                    'slug' => $this->uniqueSlug($row->name, $existingSlugs),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                    ...$attributes,
                ]);
                $created++;

                continue;
            }

            if ($this->attributesDiffer($existing, $attributes)) {
                DB::table('documentation_pages')->where('id', $row->id)->update($attributes);
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
