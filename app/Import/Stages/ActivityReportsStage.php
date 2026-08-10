<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Facades\DB;

/**
 * Stage 15 (§11.4 del PRD): importa `activity_reports` dal v1, `id`
 * conservato. Nel v1 `owner_type`/`customer_id` in realtà puntano a `users`
 * (§0.3 del PRD, glossario): `owner_type = 'customer'` → `owner_kind = 'user'`,
 * `customer_id` → `owner_user_id`. `locale` non ha una colonna sorgente su
 * `activity_reports` v1: viene derivato dall'owner (`users.locale`/
 * `organizations.locale`), stessa regola applicativa della generazione PDF
 * (§7.6/§14.x del PRD principale: "lingua dall'owner").
 *
 * `pdf_path`/`pdf_generated_at` restano sempre null (stesso trattamento di
 * `documentation_pages.pdf_path` in DocumentationStage/US-204): il v1
 * `pdf_url` punta a un file mai portato in v2, che rigenera i propri PDF in
 * coda dai dati importati — nessuna perdita perché il v1 non viene comunque
 * riletto per quel file.
 */
final class ActivityReportsStage implements ImportStage
{
    /** @var array<int, string> */
    private const LEGACY_COLUMNS = [
        'id', 'owner_type', 'customer_id', 'organization_id',
        'report_type', 'year', 'month', 'created_at', 'updated_at',
    ];

    public function name(): string
    {
        return 'activity_reports';
    }

    public function dependencies(): array
    {
        return ['users', 'organizations'];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('activity_reports')->select(self::LEGACY_COLUMNS)->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $warnings = [];
        $ambiguousOwnerCount = 0;
        $orphanOwnerCount = 0;

        /** @var array<int, string> $userLocales */
        $userLocales = DB::table('users')->pluck('locale', 'id')->all();
        /** @var array<int, string> $organizationLocales */
        $organizationLocales = DB::table('organizations')->pluck('locale', 'id')->all();

        foreach ($rows as $row) {
            $read++;

            $owner = $this->resolveOwner($row, $userLocales, $organizationLocales);

            if ($owner === null) {
                $ambiguousOwnerCount++;
                $skipped++;

                continue;
            }

            if ($owner === false) {
                $orphanOwnerCount++;
                $skipped++;

                continue;
            }

            [$ownerKind, $ownerUserId, $ownerOrganizationId, $locale] = $owner;

            $attributes = [
                'owner_kind' => $ownerKind,
                'owner_user_id' => $ownerUserId,
                'owner_organization_id' => $ownerOrganizationId,
                'period_type' => $row->report_type,
                'year' => $row->year,
                'month' => $row->month,
                'locale' => $locale,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];

            if ($context->isDryRun()) {
                continue;
            }

            $existing = DB::table('activity_reports')->where('id', $row->id)->first();

            if ($existing === null) {
                DB::table('activity_reports')->insert([
                    'id' => $row->id,
                    ...$attributes,
                    'pdf_path' => null,
                    'pdf_generated_at' => null,
                ]);
                $created++;

                continue;
            }

            if ($this->attributesDiffer($existing, $attributes)) {
                DB::table('activity_reports')->where('id', $row->id)->update($attributes);
                $updated++;
            } else {
                $skipped++;
            }
        }

        if ($ambiguousOwnerCount > 0) {
            $warnings[] = sprintf(
                '%d report attività v1 scartati: dati owner ambigui (owner_type incoerente con customer_id/organization_id, o entrambi valorizzati) — violerebbero il vincolo activity_reports_owner_check.',
                $ambiguousOwnerCount,
            );
        }

        if ($orphanOwnerCount > 0) {
            $warnings[] = sprintf(
                '%d report attività v1 scartati: owner (utente o organizzazione) inesistente in v2.',
                $orphanOwnerCount,
            );
        }

        return new StageResult(read: $read, created: $created, updated: $updated, skipped: $skipped, warnings: $warnings);
    }

    /**
     * Risolve l'owner di una riga v1: restituisce `[owner_kind, owner_user_id, owner_organization_id, locale]`,
     * `null` se i dati v1 sono ambigui (violerebbero il CHECK), `false` se l'owner referenziato non esiste in v2.
     *
     * @param  array<int, string>  $userLocales
     * @param  array<int, string>  $organizationLocales
     * @return array{0: string, 1: ?int, 2: ?int, 3: string}|null|false
     */
    private function resolveOwner(object $row, array $userLocales, array $organizationLocales): array|null|false
    {
        $isCustomer = $row->owner_type === 'customer';
        $isOrganization = $row->owner_type === 'organization';

        if (! $isCustomer && ! $isOrganization) {
            return null;
        }

        if ($row->customer_id !== null && $row->organization_id !== null) {
            return null;
        }

        if ($isCustomer) {
            if ($row->customer_id === null) {
                return null;
            }

            if (! array_key_exists($row->customer_id, $userLocales)) {
                return false;
            }

            return ['user', (int) $row->customer_id, null, $userLocales[$row->customer_id]];
        }

        if ($row->organization_id === null) {
            return null;
        }

        if (! array_key_exists($row->organization_id, $organizationLocales)) {
            return false;
        }

        return ['organization', null, (int) $row->organization_id, $organizationLocales[$row->organization_id]];
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
