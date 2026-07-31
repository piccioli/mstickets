<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Stages\Concerns\GeneratesProvisionalSlugs;
use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage 6 (§11.4 del PRD): importa `tags` dal v1, `id` conservato. Il morph
 * polimorfico v1 (`tags.taggable_*`, non la pivot `taggables` che alimenta
 * invece lo stage `ticket_tags`, US-207) collassa a tag semplici, TRANNE il
 * link a `Documentation` che diventa la FK esplicita `documentation_id`
 * (§3.2 del PRD).
 */
final class TagsStage implements ImportStage
{
    use GeneratesProvisionalSlugs;

    private const LEGACY_DOCUMENTATION_TAGGABLE_TYPE = 'App\\Models\\Documentation';

    /** @var array<int, string> */
    private const LEGACY_COLUMNS = [
        'id', 'name', 'description', 'estimate', 'taggable_id', 'taggable_type', 'created_at', 'updated_at',
    ];

    public function name(): string
    {
        return 'tags';
    }

    public function dependencies(): array
    {
        return ['documentation'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('tags')->select(self::LEGACY_COLUMNS)->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $warnings = [];
        $otherTaggableTypeCount = 0;

        $existingDocumentationIds = DB::table('documentation_pages')->pluck('id')->all();
        $existingSlugs = DB::table('tags')->pluck('slug')->all();

        foreach ($rows as $row) {
            $read++;

            $documentationId = null;

            if ($row->taggable_type !== null) {
                if ($row->taggable_type !== self::LEGACY_DOCUMENTATION_TAGGABLE_TYPE) {
                    $otherTaggableTypeCount++;
                } elseif (in_array($row->taggable_id, $existingDocumentationIds, true)) {
                    $documentationId = $row->taggable_id;
                } else {
                    $warnings[] = sprintf(
                        'Tag v1 #%d (%s) collassato a tag semplice: documentazione #%d collegata inesistente in v2.',
                        $row->id,
                        $row->name,
                        $row->taggable_id,
                    );
                }
            }

            if ($context->isDryRun()) {
                continue;
            }

            $attributes = [
                'name' => $row->name,
                'description' => $row->description,
                'estimated_hours' => $row->estimate,
                'documentation_id' => $documentationId,
            ];

            $existing = DB::table('tags')->where('id', $row->id)->first();

            if ($existing === null) {
                DB::table('tags')->insert([
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
                DB::table('tags')->where('id', $row->id)->update($attributes);
                $updated++;
            } else {
                $skipped++;
            }
        }

        if ($otherTaggableTypeCount > 0) {
            $warnings[] = sprintf(
                '%d tag v1 con taggable_type diverso da Documentation: collassati a tag semplici, link perso (§3.2 del PRD).',
                $otherTaggableTypeCount,
            );
        }

        return new StageResult(read: $read, created: $created, updated: $updated, skipped: $skipped, warnings: $warnings);
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
