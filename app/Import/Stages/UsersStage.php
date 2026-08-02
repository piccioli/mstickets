<?php

declare(strict_types=1);

namespace App\Import\Stages;

use App\Import\Anonymization\Anonymizer;
use App\Import\Inspect\Analyzers\DuplicateEmailAnalyzer;
use App\Import\Stages\Contracts\ImportStage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Stage 1 (§11.4 del PRD): importa `users` dal v1, `id` conservato, mapping
 * colonna per colonna. `users.roles` non viene toccato qui (se ne occupa lo
 * stage `roles_permissions`, US-202, dipendente da questo). `help_desk_chat`/
 * `help_desk_chat_url` restano fuori mapping: la feature non è confermata
 * (Q17 del PRD) e la colonna v2 non esiste ancora.
 *
 * Con `--anonymize` (§11.8, US-217) `name`/`email` sono sostituiti da
 * {@see Anonymizer}, deterministico per `id` v1: una riesecuzione produce
 * sempre la stessa identità fittizia per lo stesso utente, così il confronto
 * di `attributesDiffer()` resta stabile (mai un `updated` spurio alla seconda
 * esecuzione anonimizzata).
 *
 * `password` (US-R01): con `--anonymize` sostituita dall'hash Laravel di una
 * password fissa nota ({@see Anonymizer::passwordHash()}), mai l'hash v1
 * reale fuori produzione. Bcrypt sala casualmente ad ogni chiamata, quindi
 * `password` è insert-only, come `released_at`/`done_at` in `TicketsStage`
 * (US-205): esclusa dall'array `$attributes` usato per il diff/update,
 * altrimenti ogni riesecuzione con `--anonymize` genererebbe un hash diverso
 * e verrebbe segnalata come un `updated` spurio pur non essendo cambiato
 * nulla di sostanziale.
 */
final class UsersStage implements ImportStage
{
    /** @var array<int, string> */
    private const LEGACY_COLUMNS = [
        'id', 'name', 'email', 'email_verified_at', 'password', 'remember_token',
        'activity_report_language', 'google_drive_url', 'google_drive_budget_url',
        'created_at', 'updated_at',
    ];

    public function name(): string
    {
        return 'users';
    }

    public function dependencies(): array
    {
        return [];
    }

    public function run(ImportContext $context): StageResult
    {
        $query = DB::connection('legacy')->table('users')->select(self::LEGACY_COLUMNS)->orderBy('id');

        if ($context->limit() !== null) {
            $query->limit($context->limit());
        }

        $rows = $query->get();

        $warnings = $this->duplicateEmailWarnings($rows);

        $anonymizer = $context->shouldAnonymize() ? Anonymizer::default() : null;

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
                'name' => $anonymizer?->nameFor($row->id) ?? $row->name,
                'email' => $anonymizer?->emailFor($row->id) ?? $row->email,
                'email_verified_at' => $row->email_verified_at,
                'remember_token' => $row->remember_token,
                'locale' => $row->activity_report_language,
                'drive_url' => $row->google_drive_url,
                'drive_budget_url' => $row->google_drive_budget_url,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];

            $existing = DB::table('users')->where('id', $row->id)->first();

            if ($existing === null) {
                DB::table('users')->insert([
                    'id' => $row->id,
                    'password' => $anonymizer?->passwordHash() ?? $row->password,
                    ...$attributes,
                ]);
                $created++;

                continue;
            }

            if ($this->attributesDiffer($existing, $attributes)) {
                DB::table('users')->where('id', $row->id)->update($attributes);
                $updated++;
            } else {
                $skipped++;
            }
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

    /**
     * @param  Collection<int, stdClass>  $rows
     * @return array<int, string>
     */
    private function duplicateEmailWarnings(Collection $rows): array
    {
        $duplicates = DuplicateEmailAnalyzer::analyze(
            $rows->map(static fn (stdClass $row): array => ['id' => $row->id, 'email' => $row->email])->all(),
        );

        return array_map(
            static fn (array $duplicate): string => sprintf(
                'Email duplicata a meno del case "%s": %d utenti v1 con id [%s].',
                $duplicate['email_lower'],
                $duplicate['count'],
                implode(', ', $duplicate['ids']),
            ),
            $duplicates,
        );
    }
}
