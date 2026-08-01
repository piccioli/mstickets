<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage 17 (§11.4 del PRD): importa `fundraising_opportunities` dal v1, `id`
 * conservato, mapping diretto colonna per colonna. `evaluated_by`/`evaluated_at`/
 * `evaluation_positive_total`/`evaluation_negative_total`/`evaluation_total` non
 * hanno colonna sorgente in v1: valorizzati a `null` solo al primo insert e MAI
 * inclusi nel diff/update di una riga già esistente (stesso principio insert-only
 * di `TicketsStage`/US-205 per `status_changed_at`/`previous_status`), perché
 * dopo il go-live un utente reale può valutare un'opportunità in v2 (US-215/§6.6.2
 * ricalcola i totali dalle righe `fundraising_evaluation_scores`) e una
 * riesecuzione dello stage non deve mai sovrascrivere quella valutazione con `null`.
 */
final class FundraisingOpportunitiesStage implements ImportStage
{
    /** @var array<int, string> */
    private const LEGACY_COLUMNS = [
        'id', 'name', 'official_url', 'endowment_fund', 'deadline', 'program_name', 'sponsor',
        'cofinancing_quota', 'max_contribution', 'territorial_scope', 'beneficiary_requirements',
        'lead_requirements', 'created_by', 'responsible_user_id', 'created_at', 'updated_at',
    ];

    public function name(): string
    {
        return 'fundraising_opportunities';
    }

    public function dependencies(): array
    {
        return ['users'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('fundraising_opportunities')->select(self::LEGACY_COLUMNS)->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $orphanUserCount = 0;

        $existingUserIds = DB::table('users')->pluck('id')->all();

        foreach ($rows as $row) {
            $read++;

            if (! in_array($row->created_by, $existingUserIds, true) || ! in_array($row->responsible_user_id, $existingUserIds, true)) {
                $orphanUserCount++;
                $skipped++;

                continue;
            }

            $attributes = [
                'name' => $row->name,
                'official_url' => $row->official_url,
                'endowment_fund' => $row->endowment_fund,
                'deadline' => $row->deadline,
                'program_name' => $row->program_name,
                'sponsor' => $row->sponsor,
                'cofinancing_quota' => $row->cofinancing_quota,
                'max_contribution' => $row->max_contribution,
                'territorial_scope' => $row->territorial_scope,
                'beneficiary_requirements' => $row->beneficiary_requirements,
                'lead_requirements' => $row->lead_requirements,
                'created_by' => $row->created_by,
                'responsible_user_id' => $row->responsible_user_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];

            if ($context->isDryRun()) {
                continue;
            }

            $existing = DB::table('fundraising_opportunities')->where('id', $row->id)->first();

            if ($existing === null) {
                DB::table('fundraising_opportunities')->insert([
                    'id' => $row->id,
                    ...$attributes,
                    'evaluated_by' => null,
                    'evaluated_at' => null,
                    'evaluation_positive_total' => null,
                    'evaluation_negative_total' => null,
                    'evaluation_total' => null,
                ]);
                $created++;

                continue;
            }

            if ($this->attributesDiffer($existing, $attributes)) {
                DB::table('fundraising_opportunities')->where('id', $row->id)->update($attributes);
                $updated++;
            } else {
                $skipped++;
            }
        }

        $warnings = [];

        if ($orphanUserCount > 0) {
            $warnings[] = sprintf(
                '%d opportunità fundraising v1 scartate: created_by/responsible_user_id inesistente in v2.',
                $orphanUserCount,
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
